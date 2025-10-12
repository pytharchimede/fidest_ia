<?php
require __DIR__ . '/../vendor/autoload.php';

use FidestIA\Database;

$pdo = Database::getConnection();

$queries = [
    "CREATE TABLE IF NOT EXISTS requests_ia (
      id INT AUTO_INCREMENT PRIMARY KEY,
      request_id VARCHAR(100) UNIQUE,
      user_id INT DEFAULT NULL,
      label VARCHAR(255),
      declared_amount INT,
      uploaded_file VARCHAR(255),
      ocr_text LONGTEXT,
      decision ENUM('ACCEPTE','REFUSE','HUMAN_REVIEW') DEFAULT 'HUMAN_REVIEW',
      decision_reason JSON DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS invoices_ia (
      id INT AUTO_INCREMENT PRIMARY KEY,
      request_id VARCHAR(100),
      invoice_number VARCHAR(100),
      vendor VARCHAR(255),
      amount INT,
      date_invoice DATE DEFAULT NULL,
      UNIQUE(invoice_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
    "CREATE TABLE IF NOT EXISTS training_examples_ia (
      id INT AUTO_INCREMENT PRIMARY KEY,
      agent VARCHAR(100) DEFAULT 'etalonIA',
      label VARCHAR(255),
      ocr_text LONGTEXT,
      decision ENUM('ACCEPTE','REFUSE','HUMAN_REVIEW') DEFAULT 'HUMAN_REVIEW',
      meta JSON DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $q) {
    echo "Running migration...\n";
    $pdo->exec($q);
}

echo "Migrations done.\n";

// Ensure additional columns for auditing/versioning exist
$check = $pdo->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requests_ia' AND COLUMN_NAME = ?");
$cols = [['reviewed_by', 'VARCHAR(100) NULL'], ['agent_version', 'VARCHAR(50) NULL']];
foreach ($cols as $col) {
    [$name, $definition] = $col;
    $check->execute([$name]);
    $r = $check->fetch();
    if ($r && $r['c'] == 0) {
        echo "Adding column $name to requests_ia...\n";
        $pdo->exec("ALTER TABLE requests_ia ADD COLUMN $name $definition");
    }
}

// training_examples_ia already has meta/created_at; ensure author/agent_version in meta by convention
echo "Migration finalization complete.\n";
