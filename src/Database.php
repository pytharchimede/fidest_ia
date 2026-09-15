<?php

namespace FidestIA;

use RuntimeException;

class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $db = getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: '';
            $user = getenv('DB_USERNAME') ?: getenv('DB_USER') ?: '';
            $pass = getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '';
            $port = (int) (getenv('DB_PORT') ?: 3306);
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            try {
                self::$pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\PDOException $e) {
                throw new RuntimeException('Database connection failed.', 0, $e);
            }
        }
        return self::$pdo;
    }
}
