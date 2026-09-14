<?php

namespace FidestIA\Repositories;

use PDO;

final class ValidationRuleRepository
{
    public function __construct(private readonly PDO $db) {}

    public function forDocumentType(int $documentTypeId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM validation_rules WHERE document_type_id = ? AND active = 1 ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$documentTypeId]);
        return $stmt->fetchAll();
    }

    public function saveResult(int $documentId, ?int $ruleId, bool $passed, string $severity, string $message, array $context = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO document_validation_results (document_id, validation_rule_id, passed, severity, message, context) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $documentId,
            $ruleId,
            $passed ? 1 : 0,
            $severity,
            $message,
            json_encode($context, JSON_UNESCAPED_UNICODE),
        ]);
    }
}
