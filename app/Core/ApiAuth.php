<?php

declare(strict_types=1);

namespace FidestIA\Core;

use RuntimeException;

final class ApiAuth
{
    public static function requireBearer(array $config): void
    {
        $enabled = (bool) ($config['api']['enabled'] ?? true);
        if (!$enabled) {
            throw new RuntimeException('API désactivée.');
        }

        $expected = trim((string) ($config['api']['bearer_token'] ?? ''));
        if ($expected === '') {
            throw new RuntimeException('API_BEARER_TOKEN n’est pas configuré sur le serveur.');
        }

        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            self::unauthorized('Jeton Bearer requis.');
        }

        $provided = trim((string) ($matches[1] ?? ''));
        if ($provided === '' || !hash_equals($expected, $provided)) {
            self::unauthorized('Jeton API invalide.');
        }
    }

    public static function applyCors(array $config): void
    {
        $allowed = trim((string) ($config['api']['allowed_origins'] ?? ''));
        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));

        if ($origin !== '' && $allowed !== '') {
            $origins = array_values(array_filter(array_map('trim', explode(',', $allowed))));
            if (in_array('*', $origins, true) || in_array($origin, $origins, true)) {
                header('Access-Control-Allow-Origin: ' . (in_array('*', $origins, true) ? '*' : $origin));
                header('Vary: Origin');
            }
        }

        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Max-Age: 86400');
    }

    private static function unauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
