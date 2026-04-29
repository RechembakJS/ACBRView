<?php

declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

define('ACBRVIEW_ROOT',      __DIR__);
define('ACBRVIEW_DATA_DIR',  __DIR__ . '/data');
define('ACBRVIEW_JSON_PATH', __DIR__ . '/data/nfse_servicos.json');
define('ACBRVIEW_LOCK_PATH', __DIR__ . '/data/cache.lock');
define('ACBRVIEW_INI_URL',
    'https://svn.code.sf.net/p/acbr/code/trunk2/Fontes/ACBrDFe/ACBrNFSeX/ACBrNFSeXServicos.ini'
);

require __DIR__ . '/src/NfseServicosData.php';
