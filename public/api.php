<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

try {
    $data = NfseServicosData::load();
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(
        ['error' => 'Erro interno ao carregar os dados.'],
        JSON_UNESCAPED_UNICODE
    );
}
