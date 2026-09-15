<?php

declare(strict_types=1);

namespace FidestIA\Core;

use FidestIA\Repositories\ApiClientRepository;
use PDO;

final class ApiAuth
{
    public static function authenticate(array $config, PDO $db, ?string $requiredScope = null): array
    {
        if (!(bool) ($config['api']['enabled'] ?? true)) {
            throw new ApiException(503, 'API_DISABLED', 'API désactivée.');
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
        if (!$client) throw new ApiException(401, 'INVALID_API_KEY', 'Clé API invalide.');
        if (!(bool) $client['active'] || $client['revoked_at'] !== null) throw new ApiException(401, 'REVOKED_API_KEY', 'Clé API révoquée.');
        if ($client['expires_at'] !== null && strtotime((string) $client['expires_at']) <= time()) throw new ApiException(401, 'EXPIRED_API_KEY', 'Clé API expirée.');

        $scopes = json_decode((string) ($client['scopes'] ?? '[]'), true) ?: [];
        if ($requiredScope !== null && !in_array('*', $scopes, true) && !in_array($requiredScope, $scopes, true)) {
            throw new ApiException(403, 'INSUFFICIENT_SCOPE', 'Scope requis : ' . $requiredScope);
        }

        $rate = $repository->consumeRateLimit((int) $client['id'], (int) ($client['rate_limit_per_minute'] ?? 60));
        header('X-RateLimit-Limit: ' . $rate['limit']); header('X-RateLimit-Remaining: ' . $rate['remaining']);
        if (!$rate['allowed']) { header('Retry-After: ' . $rate['retry_after']); throw new ApiException(429, 'RATE_LIMIT_EXCEEDED', 'Limite de requêtes atteinte.'); }

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
            throw new ApiException(401, 'MISSING_API_KEY', 'Clé API Bearer requise.');
        }

        $token = trim((string) ($matches[1] ?? ''));
        if ($token === '') {
            throw new ApiException(401, 'MISSING_API_KEY', 'Clé API Bearer requise.');
        }

        return $token;
    }

}
