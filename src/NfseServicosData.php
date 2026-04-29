<?php

declare(strict_types=1);

/**
 * Gerencia cache local (JSON) do arquivo INI do ACBr NFSe.
 *
 * Política de atualização:
 *  - Máximo 3 GETs à fonte remota por dia civil (fuso America/Sao_Paulo).
 *  - Janelas: slot '08' (≥ 08h < 12h), slot '12' (≥ 12h < 18h), slot '18' (≥ 18h).
 *  - Cold start (sem JSON ou JSON sem municípios): um GET imediato, qualquer horário.
 *    Se ocorrer antes das 08h, o slot '08' é marcado como já atendido para não
 *    disparar um segundo GET ao cruzar a janela das 08h.
 *  - Falha de rede ou parse inválido: mantém o JSON anterior íntegro; registra erro
 *    no meta.ultimo_erro sem sobrescrever os dados bons.
 *  - Concorrência: flock em arquivo .lock garante um único download simultâneo.
 *  - Gravação atômica: escreve em .tmp e rename() para o destino final.
 */
final class NfseServicosData
{
    // -----------------------------------------------------------------
    // API pública
    // -----------------------------------------------------------------

    /**
     * Garante que o cache esteja atualizado conforme a política e retorna os dados.
     *
     * @return array{meta: array<string,mixed>, municipios: list<array<string,mixed>>}
     */
    public static function load(): array
    {
        self::ensureDataDir();
        self::refreshIfNeeded();

        $cache = self::readCache();

        return $cache ?? self::emptyPayload('Dados ainda não disponíveis. Tente novamente em instantes.');
    }

    // -----------------------------------------------------------------
    // Lógica de refresh
    // -----------------------------------------------------------------

    private static function refreshIfNeeded(): void
    {
        $now       = new DateTimeImmutable('now');
        $coldStart = self::isColdStart();
        $slotToMark = self::decideSlot($now, $coldStart);

        if ($slotToMark !== null) {
            self::lockedFetch($now, $slotToMark, $coldStart);
        }
    }

    /**
     * Decide se um fetch é necessário e qual slot marcar.
     * Retorna null quando não há necessidade de fetch.
     */
    private static function decideSlot(DateTimeImmutable $now, bool $coldStart): ?string
    {
        $hoje          = $now->format('Y-m-d');
        $meta          = self::readMeta();
        $consultasHoje = (int) ($meta['consultas_rede_por_data'][$hoje] ?? 0);

        if ($consultasHoje >= 3) {
            return null;
        }

        if ($coldStart) {
            // Permite fetch em qualquer hora.
            // Se estiver antes das 08h, marca o slot '08' para evitar re-fetch logo em seguida.
            $hora = (int) $now->format('G');
            return $hora < 8 ? '08' : (self::currentSlot($now) ?? '08');
        }

        $slot = self::currentSlot($now);
        if ($slot === null) {
            return null; // antes das 08h sem cold start — só leitura de cache
        }

        $slotsPreenchidos = (array) ($meta['slots_preenchidos'][$hoje] ?? []);
        if (in_array($slot, $slotsPreenchidos, true)) {
            return null;
        }

        return $slot;
    }

    private static function lockedFetch(DateTimeImmutable $now, string $slotToMark, bool $coldStart): void
    {
        $fp = @fopen(ACBRVIEW_LOCK_PATH, 'c+');
        if ($fp === false) {
            return;
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);

            return;
        }

        try {
            // Double-check: outro processo pode ter gravado enquanto esperávamos o lock.
            $hoje          = $now->format('Y-m-d');
            $meta          = self::readMeta();
            $consultasHoje = (int) ($meta['consultas_rede_por_data'][$hoje] ?? 0);

            if ($consultasHoje >= 3) {
                return;
            }

            if (!$coldStart) {
                $slots = (array) ($meta['slots_preenchidos'][$hoje] ?? []);
                if (in_array($slotToMark, $slots, true)) {
                    return;
                }
            } elseif (!self::isColdStart()) {
                // Outro processo já populou o cache durante a espera pelo lock.
                // Verificar se o slot ainda precisa ser marcado.
                $slots = (array) ($meta['slots_preenchidos'][$hoje] ?? []);
                if (in_array($slotToMark, $slots, true)) {
                    return;
                }
            }

            $ini = self::downloadIni();
            if ($ini === null) {
                self::recordError($meta, $now, 'Falha no download do INI remoto.');

                return;
            }

            $municipios = self::parseMunicipios($ini);
            if (empty($municipios)) {
                self::recordError($meta, $now, 'Parse do INI não retornou municípios válidos.');

                return;
            }

            // Atualizar contadores no meta
            if (!is_array($meta['consultas_rede_por_data'] ?? null)) {
                $meta['consultas_rede_por_data'] = [];
            }
            if (!is_array($meta['slots_preenchidos'] ?? null)) {
                $meta['slots_preenchidos'] = [];
            }
            if (!isset($meta['slots_preenchidos'][$hoje])) {
                $meta['slots_preenchidos'][$hoje] = [];
            }

            $meta['consultas_rede_por_data'][$hoje] = $consultasHoje + 1;

            if (!in_array($slotToMark, $meta['slots_preenchidos'][$hoje], true)) {
                $meta['slots_preenchidos'][$hoje][] = $slotToMark;
            }

            $payload = [
                'meta' => [
                    'fonte'                    => ACBRVIEW_INI_URL,
                    'atualizado_em'            => $now->format(DateTimeInterface::ATOM),
                    'consultas_rede_por_data'  => $meta['consultas_rede_por_data'],
                    'slots_preenchidos'        => $meta['slots_preenchidos'],
                    'total_municipios'         => count($municipios),
                    'ultimo_erro'              => null,
                ],
                'municipios' => $municipios,
            ];

            self::atomicJsonWrite($payload);
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    // -----------------------------------------------------------------
    // Lógica de slot
    // -----------------------------------------------------------------

    /** Retorna o identificador do slot atual ou null (antes das 08h). */
    private static function currentSlot(DateTimeImmutable $now): ?string
    {
        $h = (int) $now->format('G');

        if ($h < 8) {
            return null;
        }
        if ($h < 12) {
            return '08';
        }
        if ($h < 18) {
            return '12';
        }

        return '18';
    }

    // -----------------------------------------------------------------
    // Download
    // -----------------------------------------------------------------

    private static function downloadIni(): ?string
    {
        $body = self::downloadViaFileGetContents();

        if ($body === null && function_exists('curl_init')) {
            $body = self::downloadViaCurl();
        }

        if ($body === null || $body === '') {
            return null;
        }

        // Garantir UTF-8: preg_match('//u') valida sem precisar de mbstring
        if (!preg_match('//u', $body)) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $body);
            if ($converted !== false && $converted !== '') {
                $body = $converted;
            }
        }

        return $body;
    }

    private static function downloadViaFileGetContents(): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 30,
                'header'  => "User-Agent: ACBRView/1.0 PHP\r\nAccept: text/plain, */*\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = @file_get_contents(ACBRVIEW_INI_URL, false, $ctx);

        return ($body !== false && $body !== '') ? $body : null;
    }

    private static function downloadViaCurl(): ?string
    {
        $ch = curl_init(ACBRVIEW_INI_URL);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_USERAGENT      => 'ACBRView/1.0 PHP',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $httpCode !== 200 || $body === '') {
            return null;
        }

        return $body;
    }

    // -----------------------------------------------------------------
    // Parser INI → municípios
    // -----------------------------------------------------------------

    /**
     * @return list<array{codigo_ibge: string, nome: string, uf: string, provedor: string, versao: string|null, links: list<array{chave: string, url: string}>}>
     */
    private static function parseMunicipios(string $iniContent): array
    {
        $parsed = @parse_ini_string($iniContent, true, INI_SCANNER_RAW);

        if (!is_array($parsed)) {
            return [];
        }

        // 1º passo: coletar URLs base de cada seção de provedor (chave não numérica)
        // Municípios sem URL própria herdam estas como fallback.
        $provedorLinks = []; // ['NomeProvedor' => ['chave' => ..., 'url' => ...][]]
        foreach ($parsed as $section => $rows) {
            $section = (string) $section;
            if (preg_match('/^\d{7}$/', $section) || !is_array($rows)) {
                continue;
            }
            $provedorLinks[$section] = self::extractLinksFromRows($rows);
        }

        // 2º passo: construir municípios com merge provedor → cidade
        $out = [];
        foreach ($parsed as $section => $rows) {
            $section = (string) $section;
            if (!preg_match('/^\d{7}$/', $section) || !is_array($rows)) {
                continue;
            }

            $nome = trim((string) ($rows['Nome'] ?? ''));
            $uf   = strtoupper(trim((string) ($rows['UF'] ?? '')));

            if ($nome === '' || strlen($uf) !== 2) {
                continue;
            }

            $provedor = trim((string) ($rows['Provedor'] ?? ''));
            $versao   = isset($rows['Versao']) ? trim((string) $rows['Versao']) : null;
            if ($versao === '') {
                $versao = null;
            }

            // Links base do provedor global (se existir)
            $baseLinks = [];
            if ($provedor !== '' && isset($provedorLinks[$provedor])) {
                foreach ($provedorLinks[$provedor] as $l) {
                    $baseLinks[$l['chave']] = $l;
                }
            }

            // Links específicos da cidade sobrescrevem os do provedor
            foreach (self::extractLinksFromRows($rows) as $l) {
                $baseLinks[$l['chave']] = $l;
            }

            $out[] = [
                'codigo_ibge' => $section,
                'nome'        => $nome,
                'uf'          => $uf,
                'provedor'    => $provedor,
                'versao'      => $versao,
                'links'       => array_values($baseLinks),
            ];
        }

        usort(
            $out,
            static fn (array $a, array $b): int =>
                strcmp($a['uf'], $b['uf']) ?: strcasecmp($a['nome'], $b['nome'])
        );

        return $out;
    }

    /**
     * Extrai pares {chave, url} de uma seção do INI (provedor ou município).
     *
     * @param  array<string,mixed>                     $rows
     * @return list<array{chave: string, url: string}>
     */
    private static function extractLinksFromRows(array $rows): array
    {
        static $skip = ['Nome', 'UF', 'Provedor', 'Versao', 'Params'];

        $links = [];

        foreach ($rows as $key => $val) {
            if (in_array($key, $skip, true)) {
                continue;
            }

            $val = trim((string) $val);

            if ($val === '' || $val === '*') {
                continue;
            }

            if (preg_match('#^https?://#i', $val)) {
                $links[] = ['chave' => $key, 'url' => $val];
            }
        }

        // Extrair URLProducao / URLHomologacao embutidas em Params
        if (isset($rows['Params'])) {
            self::extractParamsUrls((string) $rows['Params'], $links);
        }

        return $links;
    }

    /**
     * @param list<array{chave: string, url: string}> $links
     */
    private static function extractParamsUrls(string $params, array &$links): void
    {
        foreach (explode('|', $params) as $part) {
            if (!str_contains($part, ':')) {
                continue;
            }

            [$k, $v] = explode(':', $part, 2);
            $k = trim($k);
            $v = trim($v);

            if (!in_array($k, ['URLProducao', 'URLHomologacao'], true)) {
                continue;
            }

            if (preg_match('#^https?://#i', $v)) {
                $links[] = ['chave' => 'Params.' . $k, 'url' => $v];
            }
        }
    }

    // -----------------------------------------------------------------
    // Leitura de cache e meta
    // -----------------------------------------------------------------

    /** @return array{meta: array<string,mixed>, municipios: list<array<string,mixed>>}|null */
    private static function readCache(): ?array
    {
        $raw = @file_get_contents(ACBRVIEW_JSON_PATH);

        if ($raw === false || $raw === '') {
            return null;
        }

        $data = json_decode($raw, true);

        if (!is_array($data) || !isset($data['municipios'], $data['meta'])) {
            return null;
        }

        return $data;
    }

    /** @return array<string,mixed> */
    private static function readMeta(): array
    {
        $cache = self::readCache();

        if ($cache === null) {
            return ['consultas_rede_por_data' => [], 'slots_preenchidos' => []];
        }

        $meta = $cache['meta'];

        if (!is_array($meta['consultas_rede_por_data'] ?? null)) {
            $meta['consultas_rede_por_data'] = [];
        }
        if (!is_array($meta['slots_preenchidos'] ?? null)) {
            $meta['slots_preenchidos'] = [];
        }

        return $meta;
    }

    private static function isColdStart(): bool
    {
        if (!is_file(ACBRVIEW_JSON_PATH)) {
            return true;
        }

        $cache = self::readCache();

        return $cache === null || empty($cache['municipios']);
    }

    // -----------------------------------------------------------------
    // Gravação
    // -----------------------------------------------------------------

    /** @param array{meta: array<string,mixed>, municipios: list<array<string,mixed>>} $payload */
    private static function atomicJsonWrite(array $payload): void
    {
        $tmp  = ACBRVIEW_JSON_PATH . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        file_put_contents($tmp, $json, LOCK_EX);
        rename($tmp, ACBRVIEW_JSON_PATH);
    }

    /**
     * Registra erro no meta sem destruir os dados de municípios já existentes.
     *
     * @param array<string,mixed> $currentMeta
     */
    private static function recordError(array $currentMeta, DateTimeImmutable $now, string $msg): void
    {
        $cache = self::readCache();

        if ($cache === null) {
            // Sem cache válido existente; não grava arquivo vazio.
            return;
        }

        $cache['meta']['ultimo_erro'] = [
            'mensagem' => $msg,
            'em'       => $now->format(DateTimeInterface::ATOM),
        ];

        self::atomicJsonWrite($cache);
    }

    // -----------------------------------------------------------------
    // Utilitários
    // -----------------------------------------------------------------

    private static function ensureDataDir(): void
    {
        if (!is_dir(ACBRVIEW_DATA_DIR)) {
            mkdir(ACBRVIEW_DATA_DIR, 0755, true);
        }
    }

    /**
     * @return array{meta: array<string,mixed>, municipios: list<array<string,mixed>>}
     */
    private static function emptyPayload(string $msg): array
    {
        return [
            'meta' => [
                'fonte'                   => ACBRVIEW_INI_URL,
                'atualizado_em'           => null,
                'consultas_rede_por_data' => [],
                'slots_preenchidos'       => [],
                'total_municipios'        => 0,
                'ultimo_erro'             => [
                    'mensagem' => $msg,
                    'em'       => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
                ],
            ],
            'municipios' => [],
        ];
    }
}
