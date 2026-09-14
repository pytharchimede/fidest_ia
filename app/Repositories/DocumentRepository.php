<?php

namespace FidestIA\Repositories;

use PDO;

final class DocumentRepository
{
    public function __construct(private readonly PDO $db) {}

    public function findTypeByCode(string $code): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM document_types WHERE code = ? AND active = 1 LIMIT 1');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO documents
            (uuid, document_type_id, client_reference, original_name, stored_name, storage_path, mime_type, file_size, sha256, status)
            VALUES (:uuid, :document_type_id, :client_reference, :original_name, :stored_name, :storage_path, :mime_type, :file_size, :sha256, :status)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateAnalysis(int $id, string $text, array $extractedData, string $status, ?float $confidence): void
    {
        $stmt = $this->db->prepare('UPDATE documents SET extracted_text = ?, extracted_data = ?, status = ?, confidence = ? WHERE id = ?');
        $stmt->execute([$text, json_encode($extractedData, JSON_UNESCAPED_UNICODE), $status, $confidence, $id]);
    }

    public function existsByField(int $documentTypeId, string $field, mixed $value, array $scope = []): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $sql = 'SELECT id, extracted_data FROM documents WHERE document_type_id = ? AND status <> \'error\'';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$documentTypeId]);

        while ($row = $stmt->fetch()) {
            $data = json_decode((string) ($row['extracted_data'] ?? '{}'), true) ?: [];
            if (($data[$field] ?? null) != $value) {
                continue;
            }
            $scopeMatches = true;
            foreach ($scope as $scopeField => $scopeValue) {
                if (($data[$scopeField] ?? null) != $scopeValue) {
                    $scopeMatches = false;
                    break;
                }
            }
            if ($scopeMatches) {
                return true;
            }
        }
        return false;
    }

    public function latest(int $limit = 20): array
    {
        $stmt = $this->db->prepare('SELECT d.*, dt.code AS document_type_code, dt.name AS document_type_name FROM documents d LEFT JOIN document_types dt ON dt.id=d.document_type_id ORDER BY d.id DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
