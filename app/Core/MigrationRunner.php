<?php

namespace FidestIA\Core;

use PDO;
use RuntimeException;
use Throwable;

final class MigrationRunner
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $migrationsPath
    ) {}

    public function run(): array
    {
        if (!is_dir($this->migrationsPath)) {
            throw new RuntimeException('Dossier de migrations introuvable : ' . $this->migrationsPath);
        }

        $this->ensureMigrationsTable();
        $files = glob(rtrim($this->migrationsPath, '/') . '/*.sql') ?: [];
        sort($files, SORT_NATURAL);

        $applied = [];
        foreach ($files as $file) {
            $name = basename($file);
            if ($this->isApplied($name)) {
                continue;
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException('Impossible de lire la migration : ' . $name);
            }

            try {
                foreach ($this->splitStatements($sql) as $statement) {
                    if (trim($statement) !== '') {
                        $this->db->exec($statement);
                    }
                }

                $stmt = $this->db->prepare('INSERT INTO schema_migrations (migration, checksum) VALUES (?, ?)');
                $stmt->execute([$name, hash('sha256', $sql)]);
                $applied[] = $name;
            } catch (Throwable $e) {
                throw new RuntimeException('Échec de migration ' . $name . ' : ' . $e->getMessage(), 0, $e);
            }
        }

        return $applied;
    }

    private function ensureMigrationsTable(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                checksum CHAR(64) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function isApplied(string $name): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
        $stmt->execute([$name]);
        return (bool) $stmt->fetchColumn();
    }

    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $escaped = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($quote === null && $char === '-' && $next === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $buffer .= "\n";
                continue;
            }

            if ($quote === null && $char === '#') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $buffer .= "\n";
                continue;
            }

            if ($quote === null && $char === '/' && $next === '*') {
                $i += 2;
                while ($i < $length - 1 && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                    $i++;
                }
                $i++;
                continue;
            }

            if ($quote !== null) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === "'" || $char === '"' || $char === '`') {
                $quote = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $trimmed = trim($buffer);
                if ($trimmed !== '') {
                    $statements[] = $trimmed;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $trimmed = trim($buffer);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
