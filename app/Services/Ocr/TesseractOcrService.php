<?php

namespace FidestIA\Services\Ocr;

use FidestIA\Contracts\OcrEngineInterface;
use RuntimeException;
use thiagoalessio\TesseractOCR\TesseractOCR;

final class TesseractOcrService implements OcrEngineInterface
{
    public function __construct(
        private readonly string $binary = 'tesseract',
        private readonly string $languages = 'fra+eng'
    ) {}

    public function extract(string $absolutePath, string $mimeType): array
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('Document introuvable pour OCR.');
        }

        if ($mimeType === 'application/pdf') {
            throw new RuntimeException('PDF reçu. Sur hébergement mutualisé, convertir d’abord les pages PDF en images (Poppler/Ghostscript) ou activer un adaptateur OCR distant.');
        }

        $ocr = new TesseractOCR($absolutePath);
        $ocr->executable($this->binary);
        $ocr->lang(...explode('+', $this->languages));
        $ocr->psm(6);

        $text = trim((string) $ocr->run());

        return [
            'text' => $text,
            'confidence' => null,
            'meta' => [
                'engine' => 'tesseract',
                'languages' => $this->languages,
            ],
        ];
    }
}
