<?php
require __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$imagesDir = __DIR__ . '/../../public/uploads/images';
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
$out = [];
if (is_dir($imagesDir)) {
    foreach (scandir($imagesDir) as $f) {
        if (in_array($f, ['.', '..'])) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $out[] = ['name' => $f, 'url' => 'uploads/images/' . rawurlencode($f)];
    }
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
