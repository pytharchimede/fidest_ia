<?php

namespace FidestIA\Services\Ocr;

use FidestIA\Contracts\OcrEngineInterface;
use RuntimeException;
use thiagoalessio\TesseractOCR\TesseractOCR;

final class TesseractOcrService implements OcrEngineInterface
{
    public function __construct(
        private readonly string $binary = 'tesseract',
        private readonly string $languages = 'fra+eng',
        private readonly string $pdfBinary = 'pdftoppm',
        private readonly int $maxPdfPages = 30
    ) {}

    public function extract(string $absolutePath, string $mimeType): array
    {
        if (!is_file($absolutePath)) {
            throw new RuntimeException('Document introuvable pour OCR.');
        }

        if ($mimeType === 'application/pdf') {
            return $this->extractPdf($absolutePath);
        }

        return [
            'text' => $this->ocrImage($absolutePath),
            'confidence' => null,
            'meta' => [
                'engine' => 'tesseract',
                'languages' => $this->languages,
                'source' => 'image',
                'pages' => 1,
            ],
        ];
    }

    private function extractPdf(string $pdfPath): array
    {
        if (!$this->canExecuteCommands()) {
            throw new RuntimeException(
                'OCR PDF indisponible : PHP interdit l’exécution de commandes système. '
                . 'Activez exec() ou utilisez un adaptateur OCR distant.'
            );
        }

        if (!$this->binaryExists($this->pdfBinary)) {
            throw new RuntimeException(
                "OCR PDF indisponible : le binaire {$this->pdfBinary} (Poppler) est absent du serveur. "
                . 'Sous Ubuntu : sudo apt install poppler-utils.'
            );
        }

        $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'fidest_ia_pdf_' . bin2hex(random_bytes(8));

        if (!mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Impossible de créer le dossier temporaire pour le PDF.');
        }

        $prefix = $tmpDir . DIRECTORY_SEPARATOR . 'page';
        $command = escapeshellcmd($this->pdfBinary)
            . ' -f 1 -l ' . max(1, $this->maxPdfPages)
            . ' -r 200 -png '
            . escapeshellarg($pdfPath) . ' '
            . escapeshellarg($prefix)
            . ' 2>&1';

        $output = [];
        $exitCode = 0;

        try {
            exec($command, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    'Échec de conversion du PDF avec Poppler : ' . trim(implode("\n", $output))
                );
            }

            $pages = glob($prefix . '-*.png') ?: [];
            natsort($pages);
            $pages = array_values($pages);

            if ($pages === []) {
                throw new RuntimeException('Aucune page exploitable n’a été produite depuis le PDF.');
            }

            $texts = [];
            foreach ($pages as $index => $page) {
                $pageText = trim($this->ocrImage($page));
                $texts[] = "--- PAGE " . ($index + 1) . " ---\n" . $pageText;
            }

            return [
                'text' => trim(implode("\n\n", $texts)),
                'confidence' => null,
                'meta' => [
                    'engine' => 'tesseract+poppler',
                    'languages' => $this->languages,
                    'source' => 'pdf',
                    'pages' => count($pages),
                    'max_pages' => $this->maxPdfPages,
                ],
            ];
        } finally {
            foreach (glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($tmpDir);
        }
    }

    private function ocrImage(string $path): string
    {
        $ocr = new TesseractOCR($path);
        $ocr->executable($this->binary);
        $ocr->lang(...array_filter(explode('+', $this->languages)));
        $ocr->psm(6);

        return trim((string) $ocr->run());
    }

    private function canExecuteCommands(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('exec', $disabled, true);
    }

    private function binaryExists(string $binary): bool
    {
        $output = [];
        $code = 1;
        exec('command -v ' . escapeshellarg($binary) . ' 2>/dev/null', $output, $code);
        return $code === 0 && !empty($output);
    }
}
