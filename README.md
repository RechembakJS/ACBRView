# ACBRView

Interface web para consulta dos provedores e municípios NFS-e do projeto [ACBr](https://acbr.sourceforge.io/).

## Requisitos

- PHP 8.1 ou superior
- Extensão `mbstring` habilitada
- A pasta `data/` deve ter permissão de escrita pelo processo PHP

## Como rodar localmente

```bash
# A partir da raiz do projeto
php -S localhost:8080 -t public
```

Abra <http://localhost:8080> no navegador.

## Estrutura

```
ACBRView/
├── bootstrap.php          # Constantes e require da classe de dados
├── src/
│   └── NfseServicosData.php  # Cache, download, parse e lógica de slots
├── public/
│   ├── index.php          # Interface web (SPA com Alpine.js + Tailwind)
│   ├── api.php            # Endpoint JSON: GET /api.php
│   └── .htaccess          # Apache: fallback para index.php
├── data/
│   ├── .gitignore         # Ignora nfse_servicos.json e arquivos temporários
│   └── nfse_servicos.json # Gerado automaticamente (não versionar)
└── README.md
```

## Política de cache

| Situação | Comportamento |
|----------|---------------|
| Primeiro acesso / sem JSON | Um download imediato, qualquer horário |
| Slots diários | Até 3 downloads por dia: às 08h, 12h e 18h (fuso `America/Sao_Paulo`) |
| Teto diário atingido | Apenas leitura do JSON local, sem rede |
| Bootstrap antes das 08h | Slot `08` marcado como atendido — evita download duplicado ao cruzar a janela |
| Falha de rede ou parse | JSON anterior mantido íntegro; erro registrado em `meta.ultimo_erro` |

## Fonte dos dados

```
https://svn.code.sf.net/p/acbr/code/trunk2/Fontes/ACBrDFe/ACBrNFSeX/ACBrNFSeXServicos.ini
```

## Uso em produção com cron (opcional)

Para garantir os horários exatos de atualização independente de tráfego, adicione ao crontab:

```cron
0 8,12,18 * * * php /caminho/para/ACBRView/public/api.php > /dev/null 2>&1
```

A lógica de slots continuará funcionando normalmente (não gera downloads duplicados).
