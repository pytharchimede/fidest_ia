<?php

return [
    'app' => [
        'name' => 'FIDEST IA',
        'env' => 'production',
        'debug' => false,
        'url' => 'http://localhost',
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'fidestci_ia_db',
        'user' => 'fidestci_ulrich',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
    'ocr' => [
        'driver' => 'tesseract',
        'binary' => 'tesseract',
        'languages' => 'fra+eng',
    ],
    'storage' => [
        'documents_path' => __DIR__ . '/../storage/documents',
        'max_upload_mb' => 15,
    ],
];
