<?php

declare(strict_types=1);

require_once '/usr/local/pkg/squid-report.inc';

function error(Throwable $exception)
{
    log_error("[squidreport] {$exception->getMessage()}");
    log_error("[squidreport] {$exception->getTraceAsString()}");
}

function connect_db(): PDO
{
    try {
        $file_db = SQUID_REPORT_FILES . '/database.sqlite3';

        $db = new PDO("sqlite:{$file_db}");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('pragma synchronous = NORMAL;');
        $db->exec('pragma journal_mode = WAL;');
        $db->exec('pragma journal_size_limit = 6144000;');
        $db->exec('pragma auto_vacuum = FULL;');

        return $db;
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function check_schema(PDO $db)
{
    $sql_table_access_log = <<<SQL
        CREATE TABLE IF NOT EXISTS tb_access_logs (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            timestamp DECIMAL(15, 3) NOT NULL,
            client_ip CHAR(15) NOT NULL,
            http_status_code INTEGER(3) NOT NULL,
            size,
            request_url TEXT NOT NULL,
            username VARCHAR(50) NOT NULL
        );
SQL;

    $db->exec($sql_table_access_log);

    $sql_view_users = <<<SQL
        CREATE VIEW IF NOT EXISTS vw_users AS
            SELECT
                DISTINCT(username)
            FROM tb_access_logs
        ;
SQL;

    $db->exec($sql_view_users);

    $sql_view_ips = <<<SQL
        CREATE VIEW IF NOT EXISTS vw_client_ips AS
            SELECT
                DISTINCT(client_ip)
            FROM tb_access_logs
        ;
SQL;

    $db->exec($sql_view_ips);

    $db->exec('CREATE TABLE IF NOT EXISTS tb_client_ips AS SELECT * FROM vw_client_ips;');

    $db->exec('CREATE TABLE IF NOT EXISTS tb_users AS SELECT * FROM vw_users;');

    $sql_index_timestamp = <<<SQL
        CREATE INDEX IF NOT EXISTS idx_access_logs_timestamp ON tb_access_logs (timestamp);
SQL;

    $db->exec($sql_index_timestamp);

    $sql_index_client_ip = <<<SQL
        CREATE INDEX IF NOT EXISTS idx_access_logs_client_ip ON tb_access_logs (client_ip);
SQL;

    $db->exec($sql_index_client_ip);

    $sql_index_username = <<<SQL
        CREATE INDEX IF NOT EXISTS idx_access_logs_username ON tb_access_logs (username);
SQL;

    $db->exec($sql_index_username);

    $sql_index_request_url = <<<SQL
        CREATE INDEX IF NOT EXISTS idx_access_logs_request_url ON tb_access_logs (request_url);
SQL;

    $db->exec($sql_index_request_url);
}

function prepare_insert_log(PDO $db)
{
    try {
        $sql = <<<SQL
            INSERT INTO tb_access_logs (
                timestamp,
                client_ip,
                http_status_code,
                size,
                request_url,
                username
            ) VALUES (
                :timestamp,
                :client_ip,
                :http_status_code,
                :size,
                :request_url,
                :username
            )
SQL;

        return $db->prepare($sql);
    } catch (Throwable $exception) {
        error($exception);
        die();
    }
}

function delete_logs(PDO $db, int $startDate, int $endDate)
{
    try {
        $stmt = $db->prepare('DELETE FROM tb_access_logs WHERE timestamp >= :start AND timestamp <= :end;');
        $stmt->bindValue(':start', $startDate, PDO::PARAM_INT);
        $stmt->bindValue(':end', $endDate, PDO::PARAM_INT);
        $stmt->execute();
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function delete_logs_old(PDO $db, int $daysBefore)
{
    try {
        $date = new DateTime();
        $date = $date->sub(new DateInterval("P{$daysBefore}D"));
        $date->setTime(0, 0, 0, 0);

        $stmt = $db->prepare('DELETE FROM tb_access_logs WHERE timestamp < :timestamp;');
        $stmt->bindValue(':timestamp', $date->getTimestamp(), PDO::PARAM_INT);
        $stmt->execute();
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function update_ips(PDO $db)
{
    try {
        $db->exec('DELETE FROM tb_client_ips;');
        $db->exec('INSERT INTO tb_client_ips SELECT * FROM vw_client_ips;');
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function update_users(PDO $db)
{
    try {
        $db->exec('DELETE FROM tb_users;');
        $db->exec('INSERT INTO tb_users SELECT * FROM vw_users;');
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function search_logs(PDO $db, int $page = 1, int $limit = 50, array $filters = [])
{
    try {
        $select = [
            'timestamp',
            'client_ip',
            'http_status_code',
            'size',
            'request_url',
            'username',
        ];
        $where = $parameters = [];

        $sql = 'SELECT __SELECT__ FROM tb_access_logs';

        if (array_key_exists('search', $filters) && !empty($filters['search'])) {
            $where[] = "request_url LIKE :request_url";
            $parameters['request_url'] = "%{$filters['search']}%";
        }

        if (array_key_exists('user', $filters) && !empty($filters['user'])) {
            $where[] = "username = :username";
            $parameters['username'] = $filters['user'];
        }

        if (array_key_exists('ip', $filters) && !empty($filters['ip'])) {
            $where[] = "client_ip = :client_ip";
            $parameters['client_ip'] = $filters['ip'];
        }

        if (array_key_exists('initialDate', $filters) && $filters['initialDate'] instanceof DateTimeInterface) {
            $where[] = 'timestamp >= :initial_date';
            $parameters['initial_date'] = $filters['initialDate']->getTimestamp();
        }

        if (array_key_exists('endDate', $filters) && $filters['endDate'] instanceof DateTimeInterface) {
            $where[] = 'timestamp <= :end_date';
            $parameters['end_date'] = $filters['endDate']->getTimestamp();
        }

        if (count($where)) {
            $where = implode(' AND ', $where);

            $sql .= " WHERE {$where}";
        }

        $sql .= ' ORDER BY timestamp DESC';

        $offset = $limit * ($page - 1);
        $sql .= " LIMIT {$limit} OFFSET {$offset}";

        $stmtLogs = $db->prepare(str_replace('__SELECT__', implode(',', $select), $sql));

        foreach ($parameters as $key => $value) {
            $stmtLogs->bindValue(":{$key}", $value);
        }

        $stmtLogs->execute();

        return [
            $stmtLogs->fetchAll(PDO::FETCH_ASSOC),
            0,
        ];
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function search_users(PDO $db)
{
    try {
        $stmt = $db->prepare('SELECT username FROM tb_users');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function search_ips(PDO $db)
{
    try {
        $stmt = $db->prepare('SELECT client_ip FROM tb_client_ips');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        error($exception);
        throw $exception;
    }
}

function validate_date(string $date, string $format = 'Y-m-d H:i') {
    $dateTime = DateTime::createFromFormat($format, $date);

    if ($dateTime && $dateTime->format($format) === $date) {
        return $dateTime;
    }

    return false;
}
