<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ?page=contractors&err=' . urlencode(t('access_denied')));
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    header('Location: ?page=contractors');
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=contractors_restore&id=' . $id . '&err=' . urlencode(t('csrf_invalid')));
    exit();
}

$stmt = $pdo->prepare("UPDATE contractors SET status = 'active' WHERE id = ?");
if ($stmt->execute([$id])) {
    logUserAction($_SESSION['user_id'], 'contractor_restored', "Prestataire réactivé (ID: $id)");
    header('Location: ?page=contractors&msg=' . urlencode(t('save_success')));
} else {
    header('Location: ?page=contractors_restore&id=' . $id . '&err=' . urlencode(t('save_error')));
}
exit();