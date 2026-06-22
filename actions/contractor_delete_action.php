<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    header('Location: ?page=contractors&err=' . urlencode(t('access_denied')));
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    header('Location: ?page=contractors');
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=contractor_delete&id=' . $id . '&err=' . urlencode(t('csrf_invalid')));
    exit();
}

if (empty($_POST['confirm_password'])) {
    header('Location: ?page=contractor_delete&id=' . $id . '&err=' . urlencode(t('password_required')));
    exit();
}

// Vérifier le mot de passe
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
    header('Location: ?page=contractor_delete&id=' . $id . '&err=' . urlencode(t('password_error')));
    exit();
}

// Désactiver le prestataire
$stmt = $pdo->prepare("UPDATE contractors SET status = 'inactive' WHERE id = ?");
if ($stmt->execute([$id])) {
    logUserAction($_SESSION['user_id'], 'contractor_deleted', "Prestataire désactivé (ID: $id)");
    header('Location: ?page=contractors&msg=' . urlencode(t('save_success')));
} else {
    header('Location: ?page=contractor_delete&id=' . $id . '&err=' . urlencode(t('save_error')));
}
exit();