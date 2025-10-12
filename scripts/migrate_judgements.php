<?php
require __DIR__ . '/../vendor/autoload.php';

use FidestIA\Database;

$pdo = Database::getConnection();

$queries = [
    "CREATE TABLE IF NOT EXISTS judgements_ia (
      id INT AUTO_INCREMENT PRIMARY KEY,
      agent VARCHAR(100) DEFAULT 'etalonIA',
      request_id VARCHAR(100) DEFAULT NULL,
      image_path VARCHAR(1024) DEFAULT NULL,
      label VARCHAR(255) DEFAULT NULL,
      judgement ENUM('YES','NO') DEFAULT NULL,
      author VARCHAR(100) DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $q) {
    echo "Running judgement migration...\n";
    $pdo->exec($q);
}

echo "Judgement migrations done.\n";
