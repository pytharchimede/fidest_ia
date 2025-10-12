<?php
require __DIR__ . '/../../vendor/autoload.php';

use FidestIA\Database;

header('Content-Type: application/json; charset=utf-8');
$pdo = Database::getConnection();

// Random item source: training_examples_ia or fiches table if exists
$source = $_GET['source'] ?? 'images';
if ($source === 'examples') {
    $stmt = $pdo->prepare('SELECT * FROM training_examples_ia ORDER BY RAND() LIMIT 1');
    $stmt->execute();
    $row = $stmt->fetch();
    echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// default: serve random image from uploads/images with optional associated label from requests_ia
$imagesDir = __DIR__ . '/../../public/uploads/images';
$files = [];
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
if (is_dir($imagesDir)) {
    foreach (scandir($imagesDir) as $f) {
        if (in_array($f, ['.', '..'])) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $files[] = $f;
    }
}
if (count($files) === 0) {
    echo json_encode(['error' => 'no_images']);
    exit;
}
$pick = $files[array_rand($files)];
$path = 'uploads/images/' . rawurlencode($pick);

// try to find matching request by filename (best-effort if table exists)
$label = null;
$request_id = null;
try {
    $stmt = $pdo->prepare('SELECT label, request_id FROM requests_ia WHERE uploaded_file LIKE ? LIMIT 1');
    $stmt->execute(['%' . $pick]);
    $r = $stmt->fetch();
    if ($r) {
        $label = $r['label'];
        $request_id = $r['request_id'];
    }
} catch (Throwable $e) {
    // table may not exist yet; ignore and proceed without label
}

echo json_encode(['image' => $path, 'label' => $label, 'request_id' => $request_id], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
