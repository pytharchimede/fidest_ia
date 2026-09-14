<?php

declare(strict_types=1);

use FidestIA\Core\ApiAuth;
use FidestIA\Core\Database;
use FidestIA\Repositories\DocumentRepository;
use FidestIA\Repositories\ValidationRuleRepository;
use FidestIA\Services\DocumentAnalysisService;
use FidestIA\Services\DocumentClassifierService;
use FidestIA\Services\DocumentExtractionService;
use FidestIA\Services\DocumentStorageService;
use FidestIA\Services\Ocr\TesseractOcrService;
use FidestIA\Services\ValidationEngine;

$root = dirname(__DIR__, 3);
$config = require $root . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
ApiAuth::applyCors($config);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    ApiAuth::requireBearer($config);

    $route = '/' . trim((string) ($_GET['_route'] ?? ''), '/');
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $db = Database::connection($config);
    $documents = new DocumentRepository($db);

    if ($method === 'GET' && $route === '/health') {
        respond(200, [
            'success' => true,
            'service' => 'FIDEST IA',
            'api_version' => 'v1',
            'status' => 'ok',
        ]);
    }

    if ($route === '/document-types' && $method === 'GET') {
        $types = array_map(static function (array $type): array {
            $schema = json_decode((string) ($type['extraction_schema'] ?? '{}'), true) ?: [];
            return [
                'id' => (int) $type['id'],
                'code' => $type['code'],
                'name' => $type['name'],
                'description' => $type['description'],
                'fields' => $schema['fields'] ?? [],
                'keywords' => $schema['keywords'] ?? [],
            ];
        }, $documents->allTypes(true));

        respond(200, ['success' => true, 'data' => $types]);
    }

    if ($route === '/document-types' && $method === 'POST') {
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Le nom du type est requis.');
        }

        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
            $code = trim(strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '_', $ascii)), '_');
        }
        if ($code === '' || in_array($code, ['AUTO', 'GENERAL'], true)) {
            throw new RuntimeException('Code de type invalide ou réservé.');
        }

        $fields = array_values(array_filter(array_map('trim', (array) ($payload['fields'] ?? []))));
        $keywords = array_values(array_filter(array_map('trim', (array) ($payload['keywords'] ?? []))));

        $id = $documents->createType([
            'code' => $code,
            'name' => $name,
            'description' => $payload['description'] ?? null,
            'extraction_schema' => ['fields' => $fields, 'keywords' => $keywords],
        ]);

        respond(201, ['success' => true, 'id' => $id, 'code' => $code]);
    }

    if ($route === '/documents/analyze' && $method === 'POST') {
        if (!isset($_FILES['document'])) {
            throw new RuntimeException('Le champ document est requis.');
        }

        $maxUploadMb = (int) ($config['storage']['max_upload_mb'] ?? 15);
        if ((int) ($_FILES['document']['size'] ?? 0) > ($maxUploadMb * 1024 * 1024)) {
            throw new RuntimeException("Le document dépasse la taille maximale autorisée de {$maxUploadMb} Mo.");
        }

        $rules = new ValidationRuleRepository($db);
        $service = new DocumentAnalysisService(
            new DocumentStorageService($config['storage']['documents_path'] ?? $root . '/storage/documents'),
            new TesseractOcrService($config['ocr']['binary'] ?? 'tesseract', $config['ocr']['languages'] ?? 'fra+eng'),
            new DocumentExtractionService(),
            new DocumentClassifierService(),
            new ValidationEngine($documents),
            $documents,
            $rules
        );

        $typeCode = trim((string) ($_POST['document_type'] ?? 'AUTO')) ?: 'AUTO';
        $clientReference = isset($_POST['client_reference']) ? trim((string) $_POST['client_reference']) : null;

        respond(200, $service->analyze($_FILES['document'], $typeCode, $clientReference));
    }

    respond(404, ['success' => false, 'error' => 'Route API introuvable.']);
} catch (Throwable $e) {
    respond(422, ['success' => false, 'error' => $e->getMessage()]);
}

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
