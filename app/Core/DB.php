<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static ?PDO $instance = null;

    public static function pdo(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $connection = Config::get('database.default', 'mysql');
        $dbConfig = Config::get("database.connections.{$connection}", []);

        if (empty($dbConfig)) {
            throw new PDOException("Database configuration for [{$connection}] not found.");
        }

        $driver = $dbConfig['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:" . ($dbConfig['database'] ?? ':memory:');
            $user = null;
            $password = null;
        } else {
            $host = $dbConfig['host'] ?? '127.0.0.1';
            $port = $dbConfig['port'] ?? 3306;
            $dbname = $dbConfig['database'] ?? 'msantha_poultry';
            $charset = $dbConfig['charset'] ?? 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            $user = $dbConfig['username'] ?? 'root';
            $password = $dbConfig['password'] ?? '';
        }

        $options = $dbConfig['options'] ?? [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        self::$instance = new PDO($dsn, $user, $password, $options);
        return self::$instance;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$instance = $pdo;
    }

    public static function beginTransaction(): bool
    {
        return self::pdo()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::pdo()->commit();
    }

    public static function rollBack(): bool
    {
        if (self::pdo()->inTransaction()) {
            return self::pdo()->rollBack();
        }
        return false;
    }

    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }
}

