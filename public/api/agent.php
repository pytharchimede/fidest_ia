<?php
require __DIR__ . '/../../vendor/autoload.php';

use FidestIA\Database;
use FidestIA\Agent;

header('Content-Type: application/json; charset=utf-8');

$pdo = Database::getConnection();
$agentName = $_GET['agent'] ?? 'etalonIA';
$agent = new Agent($agentName);

$action = $_POST['action'] ?? $_GET['action'] ?? 'predict';

if ($action === 'add_example') {
    $label = $_POST['label'] ?? '';
    $ocr_text = $_POST['ocr_text'] ?? '';
    $decision = $_POST['decision'] ?? 'HUMAN_REVIEW';
    $author = $_POST['author'] ?? null;
    $agent_version = $_POST['agent_version'] ?? null;
    $meta = ['source' => 'api'];
    if ($author) $meta['author'] = $author;
    if ($agent_version) $meta['agent_version'] = $agent_version;
    $stmt = $pdo->prepare('INSERT INTO training_examples_ia (agent,label,ocr_text,decision,meta) VALUES (?,?,?,?,?)');
    $stmt->execute([$agentName, $label, $ocr_text, $decision, json_encode($meta, JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'list_examples') {
    $stmt = $pdo->prepare('SELECT * FROM training_examples_ia WHERE agent = ? ORDER BY created_at DESC');
    $stmt->execute([$agentName]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'export_examples') {
    $format = $_GET['format'] ?? 'json';
    $stmt = $pdo->prepare('SELECT * FROM training_examples_ia WHERE agent = ? ORDER BY created_at DESC');
    $stmt->execute([$agentName]);
    $rows = $stmt->fetchAll();
    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="examples_' . $agentName . '.csv"');
        $out = fopen('php://output', 'w');
        if (count($rows)) fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $r) fputcsv($out, $r);
        exit;
    }
    echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'import_examples') {
    // accept JSON payload in body
    $body = file_get_contents('php://input');
    $arr = json_decode($body, true);
    if (!is_array($arr)) {
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO training_examples_ia (agent,label,ocr_text,decision,meta) VALUES (?,?,?,?,?)');
    $count = 0;
    foreach ($arr as $it) {
        $stmt->execute([$agentName, $it['label'] ?? '', $it['ocr_text'] ?? '', $it['decision'] ?? 'HUMAN_REVIEW', json_encode($it['meta'] ?? ['imported' => true])]);
        $count++;
    }
    echo json_encode(['imported' => $count]);
    exit;
}

// default predict
$label = $_POST['label'] ?? ($_GET['label'] ?? '');
$ocr_text = $_POST['ocr_text'] ?? ($_GET['ocr_text'] ?? '');

$res = $agent->predict($label, $ocr_text, $pdo);
// If caller asked to auto-add after confirm
if (($auto = $_POST['auto_add'] ?? $_GET['auto_add'] ?? null) && in_array($res['decision'], ['ACCEPTE', 'REFUSE'])) {
    $stmt = $pdo->prepare('INSERT INTO training_examples_ia (agent,label,ocr_text,decision,meta) VALUES (?,?,?,?,?)');
    $meta = ['source' => 'auto_add', 'agent_version' => $_POST['agent_version'] ?? null, 'author' => $_POST['author'] ?? null];
    $stmt->execute([$agentName, $label, $ocr_text, $res['decision'], json_encode($meta, JSON_UNESCAPED_UNICODE)]);
}
echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
