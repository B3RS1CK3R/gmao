<?php
// api/add_attachment_link.php - Save an external documentation path for equipment/intervention
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error'=>'unauthenticated']);
    exit();
}

$parent_type = $_POST['parent_type'] ?? null;
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
$label = trim($_POST['label'] ?? '');
$path = trim($_POST['external_path'] ?? '');

function is_valid_external_path($p) {
    if($p === '') return false;
    if(strlen($p) > 1024) return false;
    // Valid URL
    if(filter_var($p, FILTER_VALIDATE_URL)) return true;
    // file:// scheme
    if(stripos($p, 'file://') === 0) return true;
    // Windows absolute path (C:\...), UNC path (\\server\share) or Unix absolute (/path)
    if(preg_match('#^([a-zA-Z]:\\\\|\\\\\\\\|/)#', $p)) return true;
    return false;
}

$allowed = ['equipment','intervention'];
if(!$parent_type || !in_array($parent_type, $allowed) || $parent_id <= 0) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_parameters']);
    exit();
}

if(!is_valid_external_path($path)) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid_path', 'message' => 'external_path must be a valid URL or absolute file path']);
    exit();
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if(!validate_csrf_fallback($csrf)) {
    http_response_code(403);
    echo json_encode(['error'=>'invalid_csrf']);
    exit();
}

// normalize label
$label = substr($label, 0, 255);

// Basic normalization: prefer full URL or file:// path
// Store as provided by user

try {
    $stmt = $pdo->prepare("INSERT INTO attachments (parent_type, parent_id, filename, original_name, mime, size, created_by, external_path) VALUES (?, ?, '', ?, 'link', 0, ?, ?)");
    $orig = $label ?: basename($path);
    $stmt->execute([$parent_type, $parent_id, $orig, $_SESSION['user_id'], $path]);
    // Log the action
    if(isset($_SESSION['user_id'])) {
        $userLabel = $_SESSION['username'] ?? $_SESSION['user_id'];
        log_user_action($_SESSION['user_id'], 'attachment_link_added', "{$parent_type} ID: {$parent_id} - {$orig} - {$path}");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'db_error', 'message'=>$e->getMessage()]);
    exit();
}

// Redirect back to referer or return JSON
$referer = $_SERVER['HTTP_REFERER'] ?? '/index.php';
header('Location: ' . $referer . '&link_added=1');
exit();
