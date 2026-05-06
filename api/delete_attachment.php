<?php
// api/delete_attachment.php - Delete an attachment (file + db record)
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthenticated']);
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_id']);
    exit();
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if(!validate_csrf_fallback($csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'invalid_csrf']);
    exit();
}

// Fetch attachment
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$att = $stmt->fetch();
if(!$att) {
    http_response_code(404);
    echo json_encode(['error' => 'not_found']);
    exit();
}

// Permission: only admins/supervisors or uploader can delete
if(!in_array($_SESSION['role'], ['admin','supervisor']) && $_SESSION['user_id'] != $att['created_by']) {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    exit();
}

$filePath = __DIR__ . '/../uploads/attachments/' . $att['parent_type'] . '/' . $att['parent_id'] . '/' . $att['filename'];
if(is_file($filePath)) {
    @unlink($filePath);
}

$del = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
$del->execute([$id]);

// Redirect back or return JSON
if(!empty($_SERVER['HTTP_REFERER'])) {
    header('Location: ' . $_SERVER['HTTP_REFERER'] . '&deleted=1');
    exit();
}

echo json_encode(['success' => true]);
exit();
