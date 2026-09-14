<?php

namespace FidestIA\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(array $config): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $db = $config['database'] ?? [];
        $host = $db['host'] ?? 'localhost';
        $port = (int) ($db['port'] ?? 3306);
        $name = $db['name'] ?? '';
        $user = $db['user'] ?? '';
        $password = $db['password'] ?? '';
        $charset = $db['charset'] ?? 'utf8mb4';

        try {
            self::$connection = new PDO(
                "mysql:host={$host};port={$port};dbname={$name};charset={$charset}",
                $user,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$connection;
    }
}
