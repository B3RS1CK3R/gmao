<?php
// actions/preventive_delete_action.php - Traitement de l'annulation d'une maintenance préventive
if (session_status() === PHP_SESSION_NONE) session_start();

// ========== CHARGEMENT DES DEPENDANCES ==========
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// ========== VÉRIFICATION DES DROITS ==========
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=preventive');
    exit();
}

// ========== RÉCUPÉRATION DE L'ID ==========
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=preventive');
    exit();
}

// ========== VÉRIFICATION CSRF ==========
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=preventive_delete&id=' . $id);
    exit();
}

// ========== VÉRIFICATION DU MOT DE PASSE ==========
if (empty($_POST['confirm_password'])) {
    $_SESSION['flash_error'] = t('password_required');
    header('Location: ?page=preventive_delete&id=' . $id);
    exit();
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
    $_SESSION['flash_error'] = t('password_error');
    header('Location: ?page=preventive_delete&id=' . $id);
    exit();
}

// ========== VÉRIFICATION DE LA MAINTENANCE ==========
// Vérifier d'abord si la colonne task_status existe, sinon utiliser status
try {
    // Essayer d'utiliser task_status
    $stmt = $pdo->prepare("SELECT task_status, task_number FROM preventive_maintenance WHERE id = ?");
    $stmt->execute([$id]);
    $pm = $stmt->fetch();
    $status_field = 'task_status';
} catch (PDOException $e) {
    // Si task_status n'existe pas, essayer status
    try {
        $stmt = $pdo->prepare("SELECT status, task_number FROM preventive_maintenance WHERE id = ?");
        $stmt->execute([$id]);
        $pm = $stmt->fetch();
        $status_field = 'status';
    } catch (PDOException $e2) {
        // Si aucun des deux n'existe, récupérer seulement l'ID
        $stmt = $pdo->prepare("SELECT id, task_number FROM preventive_maintenance WHERE id = ?");
        $stmt->execute([$id]);
        $pm = $stmt->fetch();
        $status_field = null;
    }
}

if (!$pm) {
    $_SESSION['flash_error'] = t('not_found');
    header('Location: ?page=preventive');
    exit();
}

// Vérifier le statut si disponible
if ($status_field) {
    $current_status = $pm[$status_field] ?? 'pending';
    if ($current_status == 'completed') {
        $_SESSION['flash_error'] = t('cannot_delete_completed_preventive');
        header('Location: ?page=preventive_delete&id=' . $id);
        exit();
    }
}

// ========== ANNULATION DE LA MAINTENANCE ==========
try {
    $pdo->beginTransaction();

    // Mettre à jour le statut
    if ($status_field) {
        // Mettre à jour le statut existant
        $stmt = $pdo->prepare("UPDATE preventive_maintenance SET $status_field = 'cancelled' WHERE id = ?");
        if (!$stmt->execute([$id])) {
            throw new Exception(t('save_error'));
        }
    } else {
        // Si aucune colonne de statut n'existe, on ajoute la colonne task_status
        $pdo->exec("ALTER TABLE preventive_maintenance ADD COLUMN task_status VARCHAR(20) DEFAULT 'pending'");
        $stmt = $pdo->prepare("UPDATE preventive_maintenance SET task_status = 'cancelled' WHERE id = ?");
        if (!$stmt->execute([$id])) {
            throw new Exception(t('save_error'));
        }
    }

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'preventive_deleted', "Maintenance préventive annulée: {$pm['task_number']} (ID: $id)");
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=preventive');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=preventive_delete&id=' . $id);
}

exit();