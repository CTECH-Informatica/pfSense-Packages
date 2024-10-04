#!/usr/local/bin/php
<?php

declare(strict_types=1);
set_time_limit(0);
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
date_default_timezone_set('America/Sao_Paulo');

require_once 'squid_report.inc.php';

function main()
{
    $db = connect_db();

    $db->exec('VACUUM;');
    $db->exec('pragma wal_checkpoint(truncate);');
}

try {
    main();
} catch (Throwable $exception) {
    error($exception);
}
