<?php

declare(strict_types=1);

chdir(dirname(__DIR__));
//set_time_limit(0);
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
date_default_timezone_set('America/Sao_Paulo');

require_once '/usr/local/etc/squid_report/squid_report.inc.php';

header('X-Powered-By: CTECH/0.0.0');
header('Content-language: pt-br');
header('Content-Type: application/json');

try {
    $db = connect_db();

    $users = search_users($db);

    echo json_encode(array_map(static function (array $user) {
        return urldecode($user['username']);
    }, $users), JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    log_error("[squidreport] {$exception->getMessage()}");
    log_error("[squidreport] {$exception->getTraceAsString()}");

    http_response_code(500);
    header('HTTP/1.1 500 Internal Server Error');

    echo json_encode([
        'message' => $exception->getMessage(),
    ]);
}