<?php
// api/delete_attachment.php - Supprimer une pièce jointe avec vérification du mot de passe
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Accès interdit");
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    die("ID invalide");
}

// Vérifier que l'utilisateur a le droit de supprimer (admin, superviseur, ou créateur)
$stmt = $pdo->prepare("SELECT created_by FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$att = $stmt->fetch();
if (!$att) {
    die("Document non trouvé");
}
if (!in_array($_SESSION['role'], ['admin', 'supervisor']) && $_SESSION['user_id'] != $att['created_by']) {
    die("Permission refusée");
}

// Vérifier le mot de passe
if (empty($_POST['confirm_password'])) {
    die("Mot de passe requis");
}
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
    // Rediriger avec erreur
    $referer = $_SERVER['HTTP_REFERER'] ?? '?page=dashboard';
    header("Location: $referer&error=wrong_password");
    exit;
}

// Supprimer le fichier physique si c'est un fichier uploadé
$stmt = $pdo->prepare("SELECT filename, parent_id FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$att = $stmt->fetch();
if ($att && !empty($att['filename'])) {
    $file_path = __DIR__ . '/../uploads/attachments/equipment/' . $att['parent_id'] . '/' . $att['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// Supprimer l'entrée en base
$stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
$stmt->execute([$id]);

logUserAction($_SESSION['user_id'], 'attachment_deleted', "Attachment ID: $id deleted");

$referer = $_SERVER['HTTP_REFERER'] ?? '?page=dashboard';
header("Location: $referer&msg=document_deleted");
exit;