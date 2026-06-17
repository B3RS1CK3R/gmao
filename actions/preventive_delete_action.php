<?php
// actions/preventive_delete_action.php - Traitement de la suppression d'une maintenance préventive

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    header('Location: ?page=preventive&err=access_denied');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?page=preventive');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=preventive_delete&id=' . $id . '&error=csrf_invalid');
    exit();
}

$confirm_password = $_POST['confirm_password'] ?? '';

// Vérification du mot de passe de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND is_active = 1");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($confirm_password, $user['password'])) {
    header('Location: ?page=preventive_delete&id=' . $id . '&error=wrong_password');
    exit();
}

// Récupération des infos pour le log
$stmt = $pdo->prepare("SELECT pm.reference, e.name as equipment_name 
                        FROM preventive_maintenance pm 
                        JOIN equipment e ON pm.equipment_id = e.id 
                        WHERE pm.id = ?");
$stmt->execute([$id]);
$pm = $stmt->fetch();

if (!$pm) {
    header('Location: ?page=preventive&err=not_found');
    exit();
}

// Suppression
$stmt = $pdo->prepare("DELETE FROM preventive_maintenance WHERE id = ?");
$deleted = $stmt->execute([$id]);

if ($deleted) {
    logUserAction(
        $_SESSION['user_id'], 
        'preventive_deleted', 
        "Référence: {$pm['reference']} - Équipement: {$pm['equipment_name']} (ID: $id)"
    );
    
    header('Location: ?page=preventive&msg=maintenance_deleted_success');
} else {
    header('Location: ?page=preventive&err=delete_failed');
}

exit();
?>