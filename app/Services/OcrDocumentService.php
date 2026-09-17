<?php

declare(strict_types=1);

namespace FidestIA\Services;

use FidestIA\Contracts\OcrEngineInterface;
use FidestIA\Repositories\DocumentRepository;
use RuntimeException;

final class OcrDocumentService
{
    public function __construct(
        private readonly DocumentStorageService $storage,
        private readonly OcrEngineInterface $ocr,
        private readonly DocumentRepository $documents,
        private readonly string $defaultLanguages = 'fra+eng'
    ) {}

    public function extract(array $file, ?int $apiClientId = null, ?string $clientReference = null, ?string $language = null): array
    {
        $language = trim((string) ($language ?: $this->defaultLanguages));
        if ($language === '' || !preg_match('/^[a-z]{3}(?:\+[a-z]{3})*$/i', $language)) {
            throw new RuntimeException('Langue OCR invalide. Format attendu : fra ou fra+eng.');
        }

        $type = $this->documents->findTypeByCode('GENERAL');
        if (!$type) {
            throw new RuntimeException('Le type documentaire GENERAL est indisponible.');
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

        $started = microtime(true);
        try {
            $ocr = $this->ocr->extract($stored['absolute_path'], $stored['mime_type']);
            $text = (string) ($ocr['text'] ?? '');
            $confidence = isset($ocr['confidence']) ? (float) $ocr['confidence'] : null;
            $this->documents->updateAnalysis($documentId, $text, [], 'validated', $confidence);
            $meta = is_array($ocr['meta'] ?? null) ? $ocr['meta'] : [];
            $pages = isset($meta['page_results']) && is_array($meta['page_results']) ? count($meta['page_results']) : 1;

            return [
                'document_id' => $uuid,
                'internal_id' => $documentId,
                'filename' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'ocr' => [
                    'text' => $text,
                    'language' => $language,
                    'engine' => $meta['engine'] ?? 'unknown',
                    'confidence' => $confidence,
                    'pages' => max(1, $pages),
                    'processing_time_ms' => (int) round((microtime(true) - $started) * 1000),
                ],
            ];
        } catch (\Throwable $e) {
            $this->documents->updateAnalysis($documentId, '', [], 'error', null);
            throw $e;
        }
    }

    private function uuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
