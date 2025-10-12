<?php

namespace FidestIA;

class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $db = getenv('DB_NAME') ?: 'fidestci_app_db';
            $user = getenv('DB_USER') ?: 'fidestci_ulrich';
            $pass = getenv('DB_PASS') ?: '@Succes2019';
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            try {
                self::$pdo = new \PDO($dsn, $user, $pass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\PDOException $e) {
                http_response_code(500);
                exit(json_encode(['error' => 'DB connection failed', 'detail' => $e->getMessage()]));
            }
        }
        return self::$pdo;
    }
}
