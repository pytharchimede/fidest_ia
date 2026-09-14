<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Repositories\DocumentRepository;

header('Content-Type: application/json; charset=utf-8');

$root = dirname(__DIR__, 3);
$config = require $root . '/bootstrap.php';

try {
    $repository = new DocumentRepository(Database::connection($config));

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
        }, $repository->allTypes(true));

        echo json_encode(['success' => true, 'data' => $types], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new RuntimeException('Le nom du type est requis.');
        }

        $code = trim((string) ($payload['code'] ?? ''));
        if ($code === '') {
            $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '_', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name));
            $code = trim($code, '_');
        }
        if ($code === '' || in_array($code, ['AUTO', 'GENERAL'], true)) {
            throw new RuntimeException('Code de type invalide ou réservé.');
        }

        $fields = array_values(array_filter(array_map('trim', (array) ($payload['fields'] ?? []))));
        $keywords = array_values(array_filter(array_map('trim', (array) ($payload['keywords'] ?? []))));

        $id = $repository->createType([
            'code' => $code,
            'name' => $name,
            'description' => $payload['description'] ?? null,
            'extraction_schema' => [
                'fields' => $fields,
                'keywords' => $keywords,
            ],
        ]);

        http_response_code(201);
        echo json_encode(['success' => true, 'id' => $id, 'code' => $code], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
