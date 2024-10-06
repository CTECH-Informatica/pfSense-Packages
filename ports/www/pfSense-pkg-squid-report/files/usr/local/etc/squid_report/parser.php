#!/usr/local/bin/php
<?php

declare(strict_types=1);
set_time_limit(0);
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR);
date_default_timezone_set('America/Sao_Paulo');

require_once 'squid_report.inc.php';

function parameters(): array
{
    $short = $long = [];

    // read all the log files
    $short[] = 'f';
    $long[] = 'full';

    // read and recreate today logs only
    $short[] = 't';
    $long[] = 'today';

    // read and recreate yesterday logs only
    $short[] = 'y';
    $long[] = 'yesterday';

    $short = implode('', $short);

    return getopt($short, $long);
}

function date_range($parameters): array
{
    if (isset($parameters['full']) || isset($parameters['f'])) {
        return [
            mktime(0, 0, 0, 1, 1, 1970),
            mktime(23, 59, 59),
        ];
    }

    if (isset($parameters['today']) || isset($parameters['t'])) {
        return [
            mktime(0, 0, 0),
            mktime(23, 59, 59),
        ];
    }

    if (isset($parameters['yesterday']) || isset($parameters['y'])) {
        return [
            mktime(0, 0, 0, (int)date('m'), date('d') - 1),
            mktime(23, 59, 59, (int)date('m'), date('d') - 1),
        ];
    }

    throw new InvalidArgumentException('Invalid arguments for date range provided');
}

function warning_line_handler(string $line, string $filename): callable
{
    $line = trim($line);

    return static function (int $errno, string $errstr, string $errfile, string $errline) use ($line, $filename) {
        log_error("[squidreport] {$errno} -  {$errline}: $errstr || {$line} ({$filename})");
    };
}

function main()
{
    $db = connect_db();

    $parameters = parameters();
    [$startDate, $endDate] = date_range($parameters);

    delete_logs($db, $startDate, $endDate);

    $logs_dir = config_get_path('installedpackages/squid/config/0/log_dir', '/var/squid/logs');
    $file = 'access.log';
    $files = glob("{$logs_dir}/{$file}*");
    natsort($files);
    $foundLine = false;
    $stmt_insert = prepare_insert_log($db);

    foreach ($files as $filename) {
        $db->beginTransaction();

        $fp = popen("cat {$filename}", 'r');

        while (($line = fgets($fp)) !== false) {
            set_error_handler(warning_line_handler($line, $filename), E_WARNING|E_NOTICE);

            $line = trim($line);
            $timestamp = (float)substr($line, 0, 14);

            if ($timestamp < $startDate || $timestamp > $endDate) {
                restore_error_handler();

                if (!$foundLine) {
                    continue;
                }

                break 2;
            }

            $split = mb_split('\s+', $line);

            if (count($split) !== 10) {
                restore_error_handler();
                log_error("[squidreport] Invalid line content: '${line}' ({$filename})");
                continue;
            }

            [
                $timestamp,
                $response_time,
                $client_ip,
                $result_codes,
                $size,
                $request_method,
                $request_url,
                $username,
                $hierarchy_code,
                $mime_type,
            ] = $split;

            [$squid_request_status, $http_status_code] = explode('/',$result_codes);
            [$squid_hierarchy_status, $server_ip] = explode('/', $hierarchy_code);

            if ($client_ip === '127.0.0.1' || $squid_request_status === 'NONE') {
                restore_error_handler();
                continue;
            }

            try {
                $stmt_insert->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
                $stmt_insert->bindValue(':client_ip', $client_ip, PDO::PARAM_STR);
                $stmt_insert->bindValue(':http_status_code', $http_status_code, PDO::PARAM_INT);
                $stmt_insert->bindValue(':size', $size, PDO::PARAM_INT);
                $stmt_insert->bindValue(':request_url', mb_strtolower($request_url), PDO::PARAM_STR);
                $stmt_insert->bindValue(':username', mb_strtolower(urldecode($username)), PDO::PARAM_STR);

                $stmt_insert->execute();
            } catch (Throwable $exception) {
                log_error("[squidreport] {$line} ({$filename})");
                log_error("[squidreport] {$exception->getMessage()}");
                log_error("[squidreport] {$exception->getTraceAsString()}");
            }

            $foundLine = true;
            restore_error_handler();
        }

        pclose($fp);

        $db->commit();
    }

    update_ips($db);
    update_users($db);
}

try {
    main();
} catch (Throwable $exception) {
    error($exception);
}
