<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Repositories\DocumentRepository;
use FidestIA\Repositories\ValidationRuleRepository;
use FidestIA\Services\DocumentAnalysisService;
use FidestIA\Services\DocumentExtractionService;
use FidestIA\Services\DocumentStorageService;
use FidestIA\Services\Ocr\TesseractOcrService;
use FidestIA\Services\ValidationEngine;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

require dirname(__DIR__, 3) . '/vendor/autoload.php';

$configFile = dirname(__DIR__, 3) . '/config/app.php';
if (!is_file($configFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuration absente. Copiez config/app.example.php vers config/app.php.']);
    exit;
}

$config = require $configFile;

try {
    if (!isset($_FILES['document'])) {
        throw new RuntimeException('Le champ document est requis.');
    }

    $typeCode = trim((string) ($_POST['document_type'] ?? ''));
    if ($typeCode === '') {
        throw new RuntimeException('Le champ document_type est requis.');
    }

    $db = Database::connection($config);
    $documents = new DocumentRepository($db);
    $rules = new ValidationRuleRepository($db);
    $storagePath = $config['storage']['documents_path'] ?? dirname(__DIR__, 3) . '/storage/documents';

    $service = new DocumentAnalysisService(
        new DocumentStorageService($storagePath),
        new TesseractOcrService(
            $config['ocr']['binary'] ?? 'tesseract',
            $config['ocr']['languages'] ?? 'fra+eng'
        ),
        new DocumentExtractionService(),
        new ValidationEngine($documents),
        $documents,
        $rules
    );

    $result = $service->analyze(
        $_FILES['document'],
        $typeCode,
        isset($_POST['client_reference']) ? trim((string) $_POST['client_reference']) : null
    );

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
