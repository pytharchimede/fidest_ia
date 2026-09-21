<?php

declare(strict_types=1);

use FidestIA\Core\Database;
use FidestIA\Core\DeploymentBootstrap;
use FidestIA\Core\Env;
use FidestIA\Core\MigrationRunner;

require __DIR__ . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'FidestIA\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
    foreach ([__DIR__ . '/app/', __DIR__ . '/src/'] as $directory) {
        $file = $directory . $relative;
        if (is_file($file)) { require_once $file; return; }
    }
}, true, true);

Env::load(__DIR__ . '/.env');

$documentsPath = (string) Env::get('DOCUMENTS_PATH', 'storage/documents');
if (!str_starts_with($documentsPath, '/')) $documentsPath = __DIR__ . '/' . ltrim($documentsPath, '/');

$config = [
    'app' => [
        'name' => (string) Env::get('APP_NAME', 'FIDEST IA'),
        'env' => (string) Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => (string) Env::get('APP_URL', 'http://localhost/fidest_ia'),
        'auto_migrate' => Env::bool('AUTO_MIGRATE', true),
        'auto_bootstrap' => Env::bool('AUTO_BOOTSTRAP', true),
    ],
    'admin' => ['token' => (string) Env::get('ADMIN_TOKEN', '')],
    'api' => [
        'enabled' => Env::bool('API_ENABLED', true),
        'bearer_token' => (string) Env::get('API_BEARER_TOKEN', ''),
        'allowed_origins' => (string) Env::get('API_ALLOWED_ORIGINS', ''),
    ],
    'ai' => ['enabled' => Env::bool('AI_ENABLED', false)],
    'database' => [
        'host' => (string) Env::get('DB_HOST', 'localhost'),
        'port' => (int) Env::get('DB_PORT', 3306),
        'name' => (string) Env::get('DB_DATABASE', ''),
        'user' => (string) Env::get('DB_USERNAME', ''),
        'password' => (string) Env::get('DB_PASSWORD', ''),
        'charset' => (string) Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'ocr' => [
        'driver' => (string) Env::get('OCR_DRIVER', 'auto'),
        'binary' => (string) Env::get('OCR_BINARY', 'tesseract'),
        'embedded_binary' => (string) Env::get('OCR_EMBEDDED_BINARY', __DIR__ . '/tools/tesseract/squashfs-root/AppRun'),
        'languages' => (string) Env::get('OCR_LANGUAGES', 'fra+eng'),
        'timeout' => (int) Env::get('OCR_TIMEOUT_SECONDS', 120),
        'shared_hosting_mode' => Env::bool('OCR_SHARED_HOSTING_MODE', false),
        'optimize_documents' => Env::bool('OCR_OPTIMIZE_DOCUMENTS', true),
        'max_image_width' => (int) Env::get('OCR_MAX_IMAGE_WIDTH', 1400),
        'omp_thread_limit' => max(1, (int) Env::get('OCR_OMP_THREAD_LIMIT', 1)),
        'lock_file' => (string) Env::get('OCR_LOCK_FILE', __DIR__ . '/storage/locks/ocr.lock'),
        'lock_wait_seconds' => max(0, (int) Env::get('OCR_LOCK_WAIT_SECONDS', 2)),
    ],
    'resources' => [
        'guard_enabled' => Env::bool('SERVER_RESOURCE_GUARD', true),
        'max_load_per_cpu' => max(0.10, (float) Env::get('SERVER_MAX_LOAD_PER_CPU', 1.20)),
        'max_memory_percent' => max(1, min(100, (int) Env::get('SERVER_MAX_MEMORY_PERCENT', 85))),
        'retry_after_seconds' => max(1, (int) Env::get('SERVER_RESOURCE_RETRY_AFTER', 15)),
    ],
    'pdf' => [
        'converter' => (string) Env::get('PDF_CONVERTER', 'auto'),
        'poppler_binary' => (string) Env::get('PDF_POPPLER_BINARY', 'pdftoppm'),
        'gs_binary' => (string) Env::get('PDF_GS_BINARY', '/bin/gs'),
        'imagemagick_binary' => (string) Env::get('PDF_IMAGEMAGICK_BINARY', '/bin/convert'),
        'dpi' => (int) Env::get('PDF_DPI', 150),
        'shared_hosting_dpi' => (int) Env::get('PDF_SHARED_HOSTING_DPI', 100),
        'max_pages' => (int) Env::get('PDF_MAX_PAGES', 20),
    ],
    'storage' => [
        'documents_path' => $documentsPath,
        'max_upload_mb' => (int) Env::get('MAX_UPLOAD_MB', 15),
    ],
];

if ($config['app']['auto_bootstrap']) $config['deployment'] = (new DeploymentBootstrap(__DIR__))->run($config);
if ($config['app']['auto_migrate'] && $config['database']['name'] !== '') {
    $db = Database::connection($config);
    $config['migrations_applied'] = (new MigrationRunner($db, __DIR__ . '/database/migrations'))->run();
}
return $config;
