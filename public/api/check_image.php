<?php
require __DIR__ . '/../../vendor/autoload.php';

use FidestIA\DocumentClassifier;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$docType = $_POST['doc_type'] ?? 'JUSTIFICATIF_FEB';

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Image manquante ou invalide']);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
$tmp = $_FILES['image']['tmp_name'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp);
finfo_close($finfo);
if (!in_array($mime, $allowed)) {
    echo json_encode(['error' => 'Type de fichier non autorisé', 'mime' => $mime]);
    exit;
}

// Save to temp location under public/uploads/tmp for potential debugging
$saveDir = __DIR__ . '/../../public/uploads/tmp';
if (!is_dir($saveDir)) @mkdir($saveDir, 0755, true);
$safeName = time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $_FILES['image']['name']);
$dest = $saveDir . '/' . $safeName;
move_uploaded_file($tmp, $dest);

$cls = DocumentClassifier::classify($dest);

// Policy: recevable UNIQUEMENT si screenshot de site/prix OU photo de papier
$recevable = in_array($cls['class'], ['screenshot_site_prix', 'photo_papier']);

$response = [
    'doc_type' => $docType,
    'image' => 'uploads/tmp/' . $safeName,
    'classification' => $cls,
    'decision' => $recevable ? 'ACCEPTE' : 'REFUSE',
    'reasons' => $recevable
        ? [['code' => 'DOC_CLASS', 'message' => 'Document recevable: ' . $cls['class']]]
        : [['code' => 'DOC_CLASS', 'message' => 'Document irrecevable: ' . $cls['class'] . ' (seuls papiers et captures de prix sont acceptés)']]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
