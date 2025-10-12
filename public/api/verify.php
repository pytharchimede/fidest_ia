<?php
require __DIR__ . '/../../vendor/autoload.php';

use FidestIA\OCR;
use FidestIA\DecisionEngine;
use FidestIA\Database;

header('Content-Type: application/json; charset=utf-8');

// Simple upload handling
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$label = $_POST['label'] ?? '';
$amount = intval($_POST['amount'] ?? 0);

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Fichier manquant ou erreur upload']);
    exit;
}

// Basic upload validations
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB

$uploadDir = __DIR__ . '/../../uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$tmp = $_FILES['file']['tmp_name'];
$name = basename($_FILES['file']['name']);

// validate size and type
if ($_FILES['file']['size'] > $maxSize) {
    echo json_encode(['error' => 'Fichier trop volumineux']);
    exit;
}
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $tmp);
finfo_close($finfo);
if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['error' => 'Type de fichier non autorisé', 'mime' => $mime]);
    exit;
}

$target = $uploadDir . '/' . time() . '_' . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $name);
if (!move_uploaded_file($tmp, $target)) {
    echo json_encode(['error' => 'Impossible de déplacer le fichier uploadé']);
    exit;
}

// OCR optionally disabled: many shared hosts cannot run Tesseract.
$ocr_text = '';
if (!empty($_POST['ocr_text'])) {
    // mode text-only: client fournit déjà l'OCR (par ex. via JS ou service externe)
    $ocr_text = trim($_POST['ocr_text']);
} elseif (getenv('OCR_ENABLED') === '1') {
    try {
        $ocr_text = OCR::fromImage($target);
    } catch (Throwable $e) {
        $ocr_text = '';
    }
} else {
    // leave ocr_text empty — DecisionEngine will detect short text and refuse
    $ocr_text = '';
}

$engine = new DecisionEngine();
$res = $engine->decide($label, $amount, $ocr_text, ['min_ocr_chars' => 20]);

$response = [
    'request_id' => 'req_' . time(),
    'decision' => $res['decision'],
    'score' => null,
    'reasons' => $res['reasons'],
    'actions' => [],
    'raw_ocr_text' => mb_substr($ocr_text, 0, 5000),
    'meta' => ['uploaded_file' => basename($target)]
];

// Persist request + invoice metadata into DB (best-effort)
try {
    $pdo = Database::getConnection();
    $reqId = $response['request_id'];
    $stmt = $pdo->prepare('INSERT INTO requests_ia (request_id, label, declared_amount, uploaded_file, ocr_text, decision, decision_reason) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$reqId, $label, $amount, basename($target), $ocr_text, $res['decision'], json_encode($res['reasons'], JSON_UNESCAPED_UNICODE)]);

    // extract simple invoice metadata
    $invoice_number = null;
    if (preg_match('/(FACTURE|INVOICE)\s*[:#-]?\s*(\w[\w-\/]+)/i', $ocr_text, $m)) $invoice_number = $m[2];
    // date detection
    $date_invoice = null;
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $ocr_text, $md)) $date_invoice = $md[1];
    elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $ocr_text, $md2)) $date_invoice = date('Y-m-d', strtotime(str_replace('/', '-', $md2[1])));
    // vendor heuristics (ligne en haut contenant Société/SA/Ltd)
    $vendor = null;
    if (preg_match('/(?:Soci[eé]t[eé]|SARL|SA|Ltd|LLC)\s*[\S\s]{0,40}/i', $ocr_text, $mv)) $vendor = trim($mv[0]);

    $invoice_amount = $res['invoice_price'] ?? null;
    if ($invoice_number || $invoice_amount) {
        // try insert invoice, ignore duplicate invoice_number
        $stmt2 = $pdo->prepare('INSERT INTO invoices_ia (request_id, invoice_number, vendor, amount, date_invoice) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt2->execute([$reqId, $invoice_number, $vendor, $invoice_amount, $date_invoice]);
        } catch (PDOException $e) {
            // duplicate invoice_number -> log
        }
    }
} catch (Throwable $e) {
    // ignore DB errors for now but include in response
    $response['db_error'] = $e->getMessage();
}

// Optional: if HUMAN_REVIEW and HF token configured, call HF to draft message (not enabled by default)
if ($res['decision'] === 'HUMAN_REVIEW' && getenv('HF_API_TOKEN')) {
    $prompt = "Explique clairement pourquoi la demande a été placée en REVISION HUMAINE.\nLabel: $label\nMontant déclaré: $amount\nContexte: " . implode('; ', array_map(fn($r) => $r['message'], $res['reasons']));
    $text = call_hf_textgen(getenv('HF_MODEL') ?: 'gpt2', getenv('HF_API_TOKEN'), $prompt);
    if ($text) $response['human_message'] = $text;
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function call_hf_textgen($model, $token, $prompt)
{
    $url = "https://api-inference.huggingface.co/models/$model";
    $data = json_encode(['inputs' => $prompt, 'options' => ['wait_for_model' => true]]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) return null;
    $json = json_decode($res, true);
    if (isset($json[0]['generated_text'])) return $json[0]['generated_text'];
    if (is_string($json)) return $json;
    return null;
}
