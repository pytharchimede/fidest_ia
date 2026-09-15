<?php

declare(strict_types=1);

namespace FidestIA\Services;

use FidestIA\Contracts\OcrEngineInterface;
use FidestIA\Repositories\DocumentRepository;
use FidestIA\Repositories\ValidationRuleRepository;
use RuntimeException;
use FidestIA\Services\DocumentIntelligence\{AnomalyDetector,ConfidenceCalculator,TextNormalizer};

final class DocumentAnalysisService
{
    public function __construct(
        private readonly DocumentStorageService $storage,
        private readonly OcrEngineInterface $ocr,
        private readonly DocumentExtractionService $extractor,
        private readonly DocumentClassifierService $classifier,
        private readonly ValidationEngine $validator,
        private readonly DocumentRepository $documents,
        private readonly ValidationRuleRepository $rules
    ) {}

    public function analyze(array $file, string $documentTypeCode, ?string $clientReference = null, ?int $apiClientId = null): array
    {
        $auto = strtoupper($documentTypeCode) === 'AUTO';
        $type = $auto
            ? $this->documents->findTypeByCode('GENERAL')
            : $this->documents->findTypeByCode($documentTypeCode);

        if (!$type) {
            throw new RuntimeException('Type de document inconnu ou inactif.');
        }

        $stored = $this->storage->store($file);
        $uuid = $this->uuidV4();

        $documentId = $this->documents->create([
            'uuid' => $uuid,
            'document_type_id' => (int) $type['id'],
            'api_client_id' => $apiClientId,
            'client_reference' => $clientReference,
            'original_name' => $stored['original_name'],
            'stored_name' => $stored['stored_name'],
            'storage_path' => $stored['relative_path'],
            'mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
            'sha256' => $stored['sha256'],
            'status' => 'processing',
        ]);

        try {
            $ocr = $this->ocr->extract($stored['absolute_path'], $stored['mime_type']);
            $normalizedText=(new TextNormalizer())->normalize($ocr['text']);

            if ($auto) {
                $detected = $this->classifier->classify($normalizedText, $this->documents->allTypes(true));
                if ($detected) {
                    $type = $detected;
                    $this->documents->updateDocumentType($documentId, (int) $type['id']);
                }
            }

            $schema = json_decode((string) ($type['extraction_schema'] ?? '{}'), true) ?: [];
            $data = $this->extractor->extract($normalizedText, $type['code'], $schema);
            $fieldsWithConfidence=$this->extractor->extractWithConfidence($normalizedText,$type['code']);
            if ($type['code'] === 'GENERAL') {
                $data = [
                    'title' => $this->firstUsefulLine($ocr['text']),
                    'text_length' => mb_strlen($ocr['text']),
                    'lines' => count(preg_split('/\R/u', trim($ocr['text'])) ?: []),
                ];
            }

            $rules = $this->rules->forDocumentType((int) $type['id']);
            $validation = $this->validator->validate((int) $type['id'], $data, $rules);
            $anomalies=(new AnomalyDetector())->detect($fieldsWithConfidence,(string)$type['code'],$ocr['confidence'],$type['classification_confidence']??null);
            $scores=(new ConfidenceCalculator())->calculate($ocr['confidence'],$type['classification_confidence']??null,$fieldsWithConfidence,$validation,$anomalies);
            $status = $validation['valid'] ? 'validated' : 'rejected';

            foreach ($validation['results'] as $result) {
                $this->rules->saveResult(
                    $documentId,
                    $result['rule_id'],
                    $result['passed'],
                    $result['severity'],
                    $result['message'],
                    $result['context']
                );
            }

            $this->documents->updateAnalysis($documentId, $ocr['text'], $data, $status, $ocr['confidence']);
            $this->documents->updateIntelligence($documentId,$normalizedText,$anomalies,$scores);

            return [
                'success' => true,
                'document_id' => $documentId,
                'uuid' => $uuid,
                'status' => $status,
                'classification' => [
                    'automatic' => $auto,
                    'score' => $type['classification_score'] ?? null,
                    'confidence' => $type['classification_confidence'] ?? null,
                    'signals' => $type['classification_signals'] ?? [],
                    'fallback_to_general' => $auto && ($type['code'] ?? '') === 'GENERAL',
                ],
                'document_type' => ['code' => $type['code'], 'name' => $type['name']],
                'file' => [
                    'original_name' => $stored['original_name'],
                    'mime_type' => $stored['mime_type'],
                    'size' => $stored['file_size'],
                    'sha256' => $stored['sha256'],
                ],
                'ocr' => [
                    'engine' => $ocr['meta']['engine'] ?? 'unknown',
                    'confidence' => $ocr['confidence'],
                    'text' => $ocr['text'],
                ],
                'engine' => $ocr['meta']['engine'] ?? 'unknown',
                'raw_text' => $ocr['text'],
                'normalized_text' => $normalizedText,
                'fields' => $fieldsWithConfidence,
                'anomalies' => $anomalies,
                'scores' => $scores,
                'warnings' => array_values(array_filter($anomalies,fn(array $a):bool=>$a['severity']!=='info')),
                'pages' => $ocr['meta']['page_results'] ?? [],
                'metadata' => $ocr['meta'],
                'data' => $data,
                'validation' => $validation,
            ];
        } catch (\Throwable $e) {
            $this->documents->updateAnalysis($documentId, '', [], 'error', null);
            throw $e;
        }
    }

    private function firstUsefulLine(string $text): ?string
    {
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if (mb_strlen($line) >= 3) {
                return mb_substr($line, 0, 180);
            }
        }
        return null;
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
