<?php
// actions/interventions_delete_action.php - Traitement de la suppression d'une intervention
if (session_status() === PHP_SESSION_NONE) session_start();

// ========== CHARGEMENT DES DEPENDANCES ==========
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ========== VÉRIFICATION DES DROITS ==========
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=interventions');
    exit();
}

// ========== RÉCUPÉRATION DE L'ID ==========
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=interventions');
    exit();
}

// ========== VÉRIFICATION CSRF ==========
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=interventions_delete&id=' . $id);
    exit();
}

// ========== VÉRIFICATION DU MOT DE PASSE ==========
if (empty($_POST['confirm_password'])) {
    $_SESSION['flash_error'] = t('password_required');
    header('Location: ?page=interventions_delete&id=' . $id);
    exit();
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
    $_SESSION['flash_error'] = t('password_error');
    header('Location: ?page=interventions_delete&id=' . $id);
    exit();
}

// ========== VÉRIFICATION DU STATUT DE L'INTERVENTION ==========
$stmt = $pdo->prepare("SELECT task_status, task_number FROM interventions WHERE id = ?");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if (!$interv) {
    $_SESSION['flash_error'] = t('not_found');
    header('Location: ?page=interventions');
    exit();
}

// Empêcher la suppression d'une intervention déjà terminée
if ($interv['task_status'] == 'completed' || $interv['task_status'] == 'closed') {
    $_SESSION['flash_error'] = t('cannot_delete_completed_intervention');
    header('Location: ?page=interventions_delete&id=' . $id);
    exit();
}

// ========== DÉSACTIVATION DE L'INTERVENTION ==========
try {
    $pdo->beginTransaction();

    // Mettre à jour le statut de l'intervention
    $stmt = $pdo->prepare("UPDATE interventions SET task_status = 'cancelled' WHERE id = ?");
    if (!$stmt->execute([$id])) {
        throw new Exception(t('save_error'));
    }

    // Si une intervention est annulée, on peut aussi annuler les sorties de stock associées ?
    // Optionnel : mettre à jour les stock_movements liés
    // (Commenté car cela dépend de votre logique métier)
    /*
    $stmt = $pdo->prepare("UPDATE stock_movements SET reason = CONCAT(reason, ' (annulé)') WHERE related_type = 'intervention' AND related_id = ?");
    $stmt->execute([$id]);
    */

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'intervention_deleted', "Intervention annulée: {$interv['task_number']} (ID: $id)");
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=interventions');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=interventions_delete&id=' . $id);
}

exit();