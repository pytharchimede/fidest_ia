<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Core\DeploymentBootstrap;
use FidestIA\Core\Env;
use FidestIA\Core\MigrationRunner;

require __DIR__ . '/vendor/autoload.php';

Env::load(__DIR__ . '/.env');

$documentsPath = (string) Env::get('DOCUMENTS_PATH', 'storage/documents');
if (!str_starts_with($documentsPath, '/')) {
    $documentsPath = __DIR__ . '/' . ltrim($documentsPath, '/');
}

$config = [
    'app' => [
        'name' => (string) Env::get('APP_NAME', 'FIDEST IA'),
        'env' => (string) Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => (string) Env::get('APP_URL', 'http://localhost/fidest_ia'),
        'auto_migrate' => Env::bool('AUTO_MIGRATE', true),
        'auto_bootstrap' => Env::bool('AUTO_BOOTSTRAP', true),
    ],
    'api' => [
        'enabled' => Env::bool('API_ENABLED', true),
        'bearer_token' => (string) Env::get('API_BEARER_TOKEN', ''),
        'allowed_origins' => (string) Env::get('API_ALLOWED_ORIGINS', ''),
    ],
    'database' => [
        'host' => (string) Env::get('DB_HOST', 'localhost'),
        'port' => (int) Env::get('DB_PORT', 3306),
        'name' => (string) Env::get('DB_DATABASE', ''),
        'user' => (string) Env::get('DB_USERNAME', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset' => (string) Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'ocr' => [
        'driver' => (string) Env::get('OCR_DRIVER', 'tesseract'),
        'binary' => (string) Env::get('OCR_BINARY', 'tesseract'),
        'languages' => (string) Env::get('OCR_LANGUAGES', 'fra+eng'),
    ],
    'storage' => [
        'documents_path' => $documentsPath,
        'max_upload_mb' => (int) Env::get('MAX_UPLOAD_MB', 15),
    ],
];

if ($config['app']['auto_bootstrap']) {
    $config['deployment'] = (new DeploymentBootstrap(__DIR__))->run($config);
}

if ($config['app']['auto_migrate'] && $config['database']['name'] !== '') {
    $db = Database::connection($config);
    $config['migrations_applied'] = (new MigrationRunner($db, __DIR__ . '/database/migrations'))->run();
}

return $config;
