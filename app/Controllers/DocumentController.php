<?php

namespace FidestIA\Controllers;

use FidestIA\Services\DocumentAnalysisService;
use RuntimeException;

final class DocumentController
{
    public function __construct(private readonly DocumentAnalysisService $service) {}

    public function analyze(array $files, array $input): array
    {
        if (!isset($files['document'])) {
            throw new RuntimeException('Le champ document est requis.');
        }

        $type = trim((string) ($input['document_type'] ?? ''));
        if ($type === '') {
            throw new RuntimeException('Le champ document_type est requis.');
        }

        return $this->service->analyze(
            $files['document'],
            $type,
            isset($input['client_reference']) ? trim((string) $input['client_reference']) : null
        );
    }
}
