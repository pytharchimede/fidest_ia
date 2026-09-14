<?php

declare(strict_types=1);

namespace FidestIA\Repositories;

use PDO;

final class ApiClientRepository
{
    public function __construct(private readonly PDO $db) {}

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, key_prefix, scopes, active, last_used_at, expires_at, created_at, revoked_at
             FROM api_clients
             ORDER BY id DESC'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(string $name, array $scopes, ?string $expiresAt = null): array
    {
        $secret = bin2hex(random_bytes(32));
        $prefix = 'fia_live_' . bin2hex(random_bytes(4));
        $plain = $prefix . '_' . $secret;
        $hash = hash('sha256', $plain);

        $stmt = $this->db->prepare(
            'INSERT INTO api_clients (name, key_prefix, key_hash, scopes, expires_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $prefix,
            $hash,
            json_encode(array_values(array_unique($scopes)), JSON_UNESCAPED_UNICODE),
            $expiresAt ?: null,
        ]);

        return [
            'id' => (int) $this->db->lastInsertId(),
            'name' => $name,
            'key' => $plain,
            'key_prefix' => $prefix,
            'scopes' => array_values(array_unique($scopes)),
            'expires_at' => $expiresAt ?: null,
        ];
    }

    public function findByPlainKey(string $plainKey): ?array
    {
        $hash = hash('sha256', $plainKey);
        $stmt = $this->db->prepare(
            'SELECT * FROM api_clients
             WHERE key_hash = ?
               AND active = 1
               AND (expires_at IS NULL OR expires_at > NOW())
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function touchLastUsed(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE api_clients SET last_used_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function revoke(int $id): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE api_clients
             SET active = 0, revoked_at = NOW()
             WHERE id = ? AND active = 1'
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
}
