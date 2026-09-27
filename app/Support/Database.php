<?php
declare(strict_types=1);

namespace PCMS\Support;

use PDO;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) return self::$connection;
        if (!extension_loaded('pdo_pgsql')) throw new RuntimeException('The PDO PostgreSQL extension is required.');
        $url = Env::get('DATABASE_URL');
        if ($url) {
            $parts = parse_url($url);
            if ($parts === false || !isset($parts['host'], $parts['path'])) throw new RuntimeException('Invalid DATABASE_URL.');
            $host = $parts['host'];
            $port = (string)($parts['port'] ?? 5432);
            $name = ltrim($parts['path'], '/');
            $user = rawurldecode($parts['user'] ?? '');
            $password = rawurldecode($parts['pass'] ?? '');
            parse_str($parts['query'] ?? '', $options);
            $sslmode = (string)($options['sslmode'] ?? Env::get('DB_SSLMODE', 'require'));
        } else {
            $host = (string)Env::get('DB_HOST', '127.0.0.1');
            $port = (string)Env::get('DB_PORT', '5432');
            $name = (string)Env::get('DB_NAME', 'pcms');
            $user = (string)Env::get('DB_USER', 'pcms');
            $password = (string)Env::get('DB_PASSWORD', '');
            $sslmode = (string)Env::get('DB_SSLMODE', 'require');
        }
        if (!in_array($sslmode, ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'], true)) throw new RuntimeException('Invalid DB_SSLMODE.');
        foreach ([$host, $port, $name, $sslmode] as $part) {
            if (str_contains($part, ';') || str_contains($part, "\n")) throw new RuntimeException('Invalid database configuration.');
        }
        $dsn = "pgsql:host={$host};port={$port};dbname={$name};sslmode={$sslmode}";
        return self::$connection = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
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
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    public static function transaction(callable $callback): mixed
    {
        $connection = self::connection();
        $connection->beginTransaction();
        try {
            $result = $callback();
            $connection->commit();
            return $result;
        } catch (Throwable $error) {
            if ($connection->inTransaction()) $connection->rollBack();
            throw $error;
        }
    }

}
