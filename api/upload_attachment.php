<?php
// api/upload_attachment.php - Handle file uploads for equipment/intervention attachments
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthenticated']);
    exit();
}

$allowed_parents = ['equipment','intervention'];
$parent_type = $_POST['parent_type'] ?? null;
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

if(!$parent_type || !in_array($parent_type, $allowed_parents) || $parent_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_parent']);
    exit();
}

if(!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'no_file']);
    exit();
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if(!validate_csrf_fallback($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf']);
    exit();
}

$file = $_FILES['file'];
$maxSize = 10 * 1024 * 1024; // 10 MB
if($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'file_too_large']);
    exit();
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$originalName = basename($file['name']);

// Basic whitelist for mime types (images + pdf + office)
$allowed_mimes = [
    'image/jpeg','image/png','image/gif','image/webp',
    'application/pdf',
    'application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

if(!in_array($mime, $allowed_mimes)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_mime', 'mime' => $mime]);
    exit();
}

$uploadDir = __DIR__ . '/../uploads/attachments/' . $parent_type . '/' . $parent_id . '/';
if(!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$safeBase = bin2hex(random_bytes(8)) . '_' . time();
$filename = $safeBase . ($ext ? '.' . $ext : '');
$destination = $uploadDir . $filename;


if(!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'move_failed']);
    exit();
}

// Insert DB record
try {
    $stmt = $pdo->prepare("INSERT INTO attachments (parent_type, parent_id, filename, original_name, mime, size, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$parent_type, $parent_id, $filename, $originalName, $mime, $file['size'], $_SESSION['user_id']]);
    $insertId = $pdo->lastInsertId();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
    exit();
}

$referer = $_SERVER['HTTP_REFERER'] ?? '/index.php';

// Redirect back to referer
header('Location: ' . $referer . '&upload_success=1');
exit();
