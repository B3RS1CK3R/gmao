<?php
// api/add_attachment_link.php - Ajoute un lien ou chemin vers un document
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'access_denied']);
    exit;
}

$parent_type = $_POST['parent_type'] ?? '';
$parent_id = intval($_POST['parent_id'] ?? 0);
$label = trim($_POST['label'] ?? '');
$external_path = trim($_POST['external_path'] ?? '');

if ($parent_type !== 'equipment' || $parent_id <= 0) {
    echo json_encode(['error' => 'invalid_parent']);
    exit;
}

if (empty($external_path)) {
    echo json_encode(['error' => 'invalid_path', 'message' => 'Path cannot be empty']);
    exit;
}

// Validation : URL ou chemin absolu Windows
$valid = false;
if (preg_match('/^https?:\/\//i', $external_path) || preg_match('/^ftp:\/\//i', $external_path)) {
    $valid = true;
    $mime = 'link';
} elseif (preg_match('/^[a-zA-Z]:\\\\/', $external_path) || preg_match('/^\\\\\\\\/', $external_path)) {
    $valid = true;
    // Déterminer le mime si fichier local existe
    $mime = 'link';
    if (file_exists($external_path)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $external_path);
        finfo_close($finfo);
    }
} else {
    echo json_encode(['error' => 'invalid_path', 'message' => 'external_path must be a valid URL or absolute file path (e.g., C:\\path\\to\\file.pdf)']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO attachments (parent_type, parent_id, original_name, external_path, mime, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$result = $stmt->execute([$parent_type, $parent_id, $label ?: basename($external_path), $external_path, $mime, $_SESSION['user_id']]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'db_error']);
}