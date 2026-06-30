<?php
// actions/equipment_delete_action.php - Traitement de la suppression d'un équipement
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=equipment');
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=equipment');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=equipment_delete&id=' . $id);
    exit();
}

// Vérification du mot de passe
if (empty($_POST['confirm_password'])) {
    $_SESSION['flash_error'] = t('password_required');
    header('Location: ?page=equipment_delete&id=' . $id);
    exit();
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
    $_SESSION['flash_error'] = t('password_error');
    header('Location: ?page=equipment_delete&id=' . $id);
    exit();
}

// Vérifier si l'équipement existe et s'il a des interventions
$stmt = $pdo->prepare("SELECT code, name FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();

if (!$eq) {
    $_SESSION['flash_error'] = t('not_found');
    header('Location: ?page=equipment');
    exit();
}

// Vérifier les interventions associées
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interventions WHERE equipment_id = ? AND task_status != 'cancelled'");
$stmt->execute([$id]);
$interv_count = $stmt->fetch()['count'] ?? 0;

if ($interv_count > 0) {
    $_SESSION['flash_error'] = t('cannot_delete_equipment_with_interventions') . " ($interv_count interventions)";
    header('Location: ?page=equipment_delete&id=' . $id);
    exit();
}

// Suppression de l'équipement
try {
    $pdo->beginTransaction();

    // Supprimer les pièces associées
    $pdo->prepare("DELETE FROM equipment_parts WHERE equipment_id = ?")->execute([$id]);

    // Supprimer les documents associés
    $pdo->prepare("DELETE FROM attachments WHERE parent_type = 'equipment' AND parent_id = ?")->execute([$id]);

    // Supprimer l'équipement
    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
    if (!$stmt->execute([$id])) {
        throw new Exception(t('save_error'));
    }

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'equipment_deleted', "Equipment deleted: {$eq['code']} (ID: $id)");
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=equipment');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=equipment_delete&id=' . $id);
}

exit();