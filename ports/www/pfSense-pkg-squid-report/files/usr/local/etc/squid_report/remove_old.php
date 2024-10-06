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

    $days_retain = (int)config_get_path('installedpackages/squidreport/config/0/squid_report_logs_retain', 60);

    delete_logs_old($db, $days_retain);
}

try {
    main();
} catch (Throwable $exception) {
    error($exception);
}
