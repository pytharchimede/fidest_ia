<?php
require __DIR__ . '/../../vendor/autoload.php';

use FidestIA\Database;

header('Content-Type: application/json; charset=utf-8');
$pdo = Database::getConnection();

$agent = $_POST['agent'] ?? $_GET['agent'] ?? 'etalonIA';
$request_id = $_POST['request_id'] ?? $_GET['request_id'] ?? null;
$image = $_POST['image'] ?? $_GET['image'] ?? null;
$label = $_POST['label'] ?? $_GET['label'] ?? null;
$judgement = $_POST['judgement'] ?? $_GET['judgement'] ?? null; // YES/NO
$author = $_POST['author'] ?? $_GET['author'] ?? null;

if (!$judgement) {
    echo json_encode(['error' => 'missing judgement']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO judgements_ia (agent,request_id,image_path,label,judgement,author) VALUES (?,?,?,?,?,?)');
$stmt->execute([$agent, $request_id, $image, $label, $judgement, $author]);

// Option: if judgement == YES (accepted), add as training example
if ($judgement === 'YES') {
    $stmt2 = $pdo->prepare('INSERT INTO training_examples_ia (agent,label,ocr_text,decision,meta) VALUES (?,?,?,?,?)');
    $meta = ['source' => 'judgement', 'author' => $author];
    $stmt2->execute([$agent, $label, $image, 'ACCEPTE', json_encode($meta, JSON_UNESCAPED_UNICODE)]);
}

echo json_encode(['ok' => true]);
