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
    $page = filter_var($_GET['page'], FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 1,
            'min_range' => 1,
        ]
    ]);

    $limit = filter_var($_GET['limit'], FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 50,
            'min_range' => 20,
        ]
    ]);

    $search = mb_strtolower(filter_var($_GET['search'] ?? '', FILTER_UNSAFE_RAW));

    $user = mb_strtolower(urldecode(filter_var($_GET['user'] ?? '', FILTER_UNSAFE_RAW)));

    $ip = filter_var($_GET['ip'] ?? '', FILTER_VALIDATE_IP);

    $initialDate = filter_var($_GET['initialDate'] ?? '', FILTER_CALLBACK, ['options' => 'validate_date']);
    $endDate = filter_var($_GET['endDate'] ?? '', FILTER_CALLBACK, ['options' => 'validate_date']);

    $db = connect_db();

    [$logs, $count] = search_logs(
        $db,
        $page,
        $limit,
        [
            'search' => $search,
            'user' => $user,
            'ip' => $ip,
            'initialDate' => $initialDate,
            'endDate' => $endDate,
        ]
    );

    echo json_encode([
        'total' => (int)$count,
        'page' => $page,
        'limit' => $limit,
        'items' => array_map(static function (array $log) {
            return [
                'date' => date('d/m/Y H:i:s', (int)$log['timestamp']),
                'ip' => $log['client_ip'],
                'code' => $log['http_status_code'],
                'size' => $log['size'],
                'url' => $log['request_url'],
                'host' => parse_url($log['request_url'], PHP_URL_HOST),
                'user' => urldecode($log['username']),
            ];
        }, $logs),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    error($exception);

    http_response_code(500);
    header('HTTP/1.1 500 Internal Server Error');

    echo json_encode([
        'message' => $exception->getMessage(),
    ]);
}