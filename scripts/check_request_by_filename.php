<?php
require __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    fwrite(STDERR, "Usage: php check_request_by_filename.php <filename>\n");
    exit(1);
}

$file = $argv[1];
try {
    $pdo = FidestIA\Database::getConnection();
    $stmt = $pdo->prepare('SELECT request_id,label,uploaded_file,ocr_text,decision FROM requests_ia WHERE uploaded_file LIKE ? LIMIT 20');
    $stmt->execute(['%' . $file]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(2);
}
