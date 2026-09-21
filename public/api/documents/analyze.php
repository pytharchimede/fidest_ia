<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Repositories\DocumentRepository;
use FidestIA\Repositories\ValidationRuleRepository;
use FidestIA\Services\DocumentAnalysisService;
use FidestIA\Services\DocumentClassifierService;
use FidestIA\Services\DocumentExtractionService;
use FidestIA\Services\DocumentStorageService;
use FidestIA\Services\Ocr\OcrEngineFactory;
use FidestIA\Services\ValidationEngine;

ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function jsonResponse(array $payload, int $status = 200): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur serveur a interrompu l’analyse du document.',
        'error_code' => 'DOCUMENT_ANALYSIS_FATAL_ERROR',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
}

$root = dirname(__DIR__, 3);
try {
    $config = require $root . '/bootstrap.php';
} catch (Throwable) {
    jsonResponse(['success' => false, 'error' => 'Service documentaire temporairement indisponible.'], 500);
}

try {
    if (!isset($_FILES['document'])) {
        throw new RuntimeException('Le champ document est requis.');
    }

    $typeCode = trim((string) ($_POST['document_type'] ?? 'AUTO'));
    if ($typeCode === '') {
        $typeCode = 'AUTO';
    }

    $maxUploadMb = (int) ($config['storage']['max_upload_mb'] ?? 15);
    $maxUploadBytes = $maxUploadMb * 1024 * 1024;
    if ((int) ($_FILES['document']['size'] ?? 0) > $maxUploadBytes) {
        throw new RuntimeException("Le document dépasse la taille maximale autorisée de {$maxUploadMb} Mo.");
    }

    $db = Database::connection($config);
    $documents = new DocumentRepository($db);
    $rules = new ValidationRuleRepository($db);
    $storagePath = $config['storage']['documents_path'] ?? $root . '/storage/documents';

    $service = new DocumentAnalysisService(
        new DocumentStorageService($storagePath),
        (new OcrEngineFactory($config, $root))->create(),
        new DocumentExtractionService(),
        new DocumentClassifierService(),
        new ValidationEngine($documents),
        $documents,
        $rules
    );

    $result = $service->analyze(
        $_FILES['document'],
        $typeCode,
        isset($_POST['client_reference']) ? trim((string) $_POST['client_reference']) : null
    );

    jsonResponse($result);
} catch (Throwable $e) {
    $message = $e->getMessage();
    $resourceBusy = str_starts_with($message, 'OCR_RESOURCE_BUSY:');
    $busy = $resourceBusy || str_starts_with($message, 'OCR_BUSY:');
    if ($resourceBusy) {
        header('Retry-After: ' . max(1, (int) ($config['resources']['retry_after_seconds'] ?? 15)));
    }
    jsonResponse([
        'success' => false,
        'error' => $resourceBusy
            ? 'FIDEST IA est temporairement en pause pour protéger les ressources du serveur. Merci de patienter.'
            : ($busy ? 'Je suis occupée en ce moment. Merci de patienter.' : $message),
        'error_code' => $resourceBusy ? 'OCR_RESOURCE_BUSY' : ($busy ? 'OCR_BUSY' : 'DOCUMENT_ANALYSIS_FAILED'),
        'retry_after' => $resourceBusy ? max(1, (int) ($config['resources']['retry_after_seconds'] ?? 15)) : null,
    ], $busy ? 409 : 422);
}
