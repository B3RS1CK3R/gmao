<?php
// actions/preventive_edit_action.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor')) {
    die("Accès interdit");
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    die("Erreur CSRF");
}

// Récupération de l'ID (peut être en GET ou POST)
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des champs POST (identique au formulaire preventive_edit.php)
    $equipment_id   = intval($_POST['equipment_id'] ?? 0);
    $frequency_days = intval($_POST['frequency_days'] ?? 0);
    $last_done      = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
    $title          = trim($_POST['title'] ?? '');
    $instructions   = trim($_POST['instructions'] ?? '');
    $technician_id  = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id        = (isset($_POST['team_id']) && $_POST['team_id'] !== '') ? intval($_POST['team_id']) : null;

    // Validation
    if ($equipment_id <= 0 || empty($title)) {
        header('Location: ?page=preventive_edit&id=' . $id . '&error=missing_fields');
        exit();
    }
    if ($frequency_days < 1) {
        header('Location: ?page=preventive_edit&id=' . $id . '&error=invalid_frequency');
        exit();
    }

    // Si un team est sélectionné, on ignore le technicien
    if ($team_id) {
        $technician_id = null;
    }

    // Calcul de la prochaine échéance
    $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

    // Mise à jour (note : la colonne assigned_team n'existe pas, on utilise team_id)
    $sql = "UPDATE preventive_maintenance SET 
                equipment_id = ?, 
                frequency_days = ?, 
                last_done = ?, 
                next_due = ?, 
                title = ?, 
                instructions = ?, 
                technician_id = ?, 
                team_id = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $equipment_id,
        $frequency_days,
        $last_done,
        $next_due,
        $title,
        $instructions,
        $technician_id,
        $team_id,
        $id
    ]);

    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_updated', "Maintenance préventive ID: {$id} mise à jour");
        header('Location: ?page=preventive&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=preventive_edit&id=' . $id . '&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=preventive');
    exit();
}
?>