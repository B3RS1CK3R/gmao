<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    die("Accès interdit");
}

$parent_type = $_POST['parent_type'] ?? '';
$parent_id = intval($_POST['parent_id'] ?? 0);
$label = trim($_POST['label'] ?? '');
$external_path = trim($_POST['external_path'] ?? '');

// Récupérer la page de retour (referer) ou construire une URL par défaut
$referer = $_SERVER['HTTP_REFERER'] ?? "?page=equipment_detail&id=$parent_id";

if ($parent_type !== 'equipment' || $parent_id <= 0) {
    header("Location: $referer&error=invalid_parent");
    exit;
}

if (empty($external_path)) {
    header("Location: $referer&error=empty_path");
    exit;
}

// Accepter le chemin tel quel
$mime = 'link';
if (file_exists($external_path)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $external_path);
    finfo_close($finfo);
}

$stmt = $pdo->prepare("INSERT INTO attachments (parent_type, parent_id, original_name, external_path, mime, created_by) VALUES (?, ?, ?, ?, ?, ?)");
$result = $stmt->execute([$parent_type, $parent_id, $label ?: basename($external_path), $external_path, $mime, $_SESSION['user_id']]);

if ($result) {
    header("Location: $referer&msg=document_added");
} else {
    header("Location: $referer&error=db_error");
}
exit;