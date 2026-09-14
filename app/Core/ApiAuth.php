<?php

declare(strict_types=1);

namespace FidestIA\Core;

use FidestIA\Repositories\ApiClientRepository;
use PDO;
use RuntimeException;

final class ApiAuth
{
    public static function authenticate(array $config, PDO $db, ?string $requiredScope = null): array
    {
        if (!(bool) ($config['api']['enabled'] ?? true)) {
            throw new RuntimeException('API désactivée.');
        }

        $token = self::bearerToken();

        // Master token kept only for administration / emergency access.
        $master = trim((string) ($config['api']['bearer_token'] ?? ''));
        if ($master !== '' && hash_equals($master, $token)) {
            return [
                'type' => 'master',
                'name' => 'Master',
                'scopes' => ['*'],
            ];
        }

        $repository = new ApiClientRepository($db);
        $client = $repository->findByPlainKey($token);
        if (!$client) {
            self::unauthorized('Clé API invalide, expirée ou révoquée.');
        }

        $scopes = json_decode((string) ($client['scopes'] ?? '[]'), true) ?: [];
        if ($requiredScope !== null && !in_array('*', $scopes, true) && !in_array($requiredScope, $scopes, true)) {
            self::forbidden('Cette clé API ne possède pas le scope requis : ' . $requiredScope);
        }

        $repository->touchLastUsed((int) $client['id']);

        return [
            'type' => 'client',
            'id' => (int) $client['id'],
            'name' => $client['name'],
            'key_prefix' => $client['key_prefix'],
            'scopes' => $scopes,
        ];
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

        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Max-Age: 86400');
    }

    private static function bearerToken(): string
    {
        $header = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
        if ($header === '' && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = (string) ($headers['Authorization'] ?? $headers['authorization'] ?? '');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
            self::unauthorized('Clé API Bearer requise.');
        }

        $token = trim((string) ($matches[1] ?? ''));
        if ($token === '') {
            self::unauthorized('Clé API Bearer requise.');
        }

        return $token;
    }

    private static function unauthorized(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private static function forbidden(string $message): never
    {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
