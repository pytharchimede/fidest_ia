<?php

namespace FidestIA\Services;

use FidestIA\Repositories\DocumentRepository;

final class ValidationEngine
{
    public function __construct(private readonly DocumentRepository $documents) {}

    public function validate(int $documentTypeId, array $data, array $rules): array
    {
        $results = [];

        foreach ($rules as $rule) {
            $passed = true;
            $context = [];
            $type = $rule['rule_type'];
            $field = $rule['field_name'] ?? null;

            if ($type === 'unique_field' && $field) {
                $value = $data[$field] ?? null;
                $passed = !$this->documents->existsByField($documentTypeId, $field, $value);
                $context = ['field' => $field, 'value' => $value];
            }

            if ($type === 'unique_field_with_scope' && $field) {
                $value = $data[$field] ?? null;
                $scopeFields = json_decode((string) ($rule['scope_fields'] ?? '[]'), true) ?: [];
                $scope = [];
                foreach ($scopeFields as $scopeField) {
                    $scope[$scopeField] = $data[$scopeField] ?? null;
                }
                $passed = !$this->documents->existsByField($documentTypeId, $field, $value, $scope);
                $context = ['field' => $field, 'value' => $value, 'scope' => $scope];
            }

            if ($type === 'required_field' && $field) {
                $passed = isset($data[$field]) && trim((string) $data[$field]) !== '';
                $context = ['field' => $field];
            }

            $results[] = [
                'rule_id' => (int) $rule['id'],
                'rule' => $rule['name'],
                'passed' => $passed,
                'severity' => $rule['severity'],
                'message' => $passed ? 'Contrôle validé.' : $rule['error_message'],
                'context' => $context,
            ];
        }

        $valid = true;
        foreach ($results as $result) {
            if (!$result['passed'] && $result['severity'] === 'error') {
                $valid = false;
                break;
            }
        }

        return ['valid' => $valid, 'results' => $results];
    }
}
