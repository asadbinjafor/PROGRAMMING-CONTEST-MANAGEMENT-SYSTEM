<?php
declare(strict_types=1);

namespace PCMS\Support;

use RuntimeException;
use Throwable;

final class Database
{
    private static mixed $connection = null;

    public static function connection(): mixed
    {
        if (self::$connection !== null) return self::$connection;
        if (!extension_loaded('oci8')) {
            throw new RuntimeException('The OCI8 PHP extension is required. Follow the setup section in README.md.');
        }
        $descriptor = sprintf('//%s:%s/%s', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '1521'), Env::get('DB_SERVICE', 'XE'));
        $connection = @\oci_connect(Env::get('DB_USERNAME', ''), Env::get('DB_PASSWORD', ''), $descriptor, Env::get('DB_CHARSET', 'AL32UTF8'));
        if ($connection === false) {
            $error = \oci_error();
            throw new RuntimeException('Oracle connection failed: ' . ($error['message'] ?? 'unknown error'));
        }
        return self::$connection = $connection;
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::statement($sql, $params);
        $rows = [];
        while (($row = \oci_fetch_array($statement, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) !== false) {
            $rows[] = array_change_key_case($row, CASE_LOWER);
        }
        \oci_free_statement($statement);
        return $rows;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        return self::all($sql, $params)[0] ?? null;
    }

    public static function scalar(string $sql, array $params = []): mixed
    {
        $row = self::one($sql, $params);
        return $row === null ? null : reset($row);
    }

    public static function execute(string $sql, array $params = [], bool $commit = true): int
    {
        $statement = self::statement($sql, $params, $commit ? 32 : 0);
        $count = \oci_num_rows($statement);
        \oci_free_statement($statement);
        return $count;
    }

    public static function transaction(callable $callback): mixed
    {
        $connection = self::connection();
        try {
            $result = $callback();
            \oci_commit($connection);
            return $result;
        } catch (Throwable $error) {
            \oci_rollback($connection);
            throw $error;
        }
    }

    private static function statement(string $sql, array $params, int $mode = 0): mixed
    {
        $connection = self::connection();
        $statement = \oci_parse($connection, $sql);
        if ($statement === false) throw new RuntimeException('Unable to parse database statement.');
        $bindings = [];
        foreach ($params as $name => $value) {
            $key = ':' . ltrim((string)$name, ':');
            $bindings[$key] = $value;
            \oci_bind_by_name($statement, $key, $bindings[$key], -1);
        }
        if (!@\oci_execute($statement, $mode)) {
            $error = \oci_error($statement);
            \oci_free_statement($statement);
            throw new RuntimeException('Database operation failed: ' . ($error['message'] ?? 'unknown error'));
        }
        return $statement;
    }
}
