<?php
// actions/preventive_edit_action.php - Traitement de la modification d'une maintenance préventive
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=preventive');
    exit();
}

// Récupération de l'ID
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=preventive');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=preventive_edit&id=' . $id);
    exit();
}

// Récupération des données
$equipment_id = intval($_POST['equipment_id'] ?? 0);
$frequency_days = intval($_POST['frequency_days'] ?? 0);
$last_done = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
$title = trim($_POST['title'] ?? '');
$instructions = trim($_POST['instructions'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$planned_duration = $_POST['planned_duration'] ?? '4h';
$zone = trim($_POST['zone'] ?? '');
$localisation = trim($_POST['localisation'] ?? '');

// Gestion de l'assignation (priorité : équipe > technicien > prestataire)
$technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
$team_id = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
$contractor_id = !empty($_POST['contractor_id']) ? intval($_POST['contractor_id']) : null;

// Règle : Si une équipe est sélectionnée, le technicien est ignoré
if ($team_id) {
    $technician_id = null;
}
// Le prestataire peut être combiné avec un technicien OU une équipe
// Aucune modification nécessaire pour contractor_id

// Validation
if ($equipment_id <= 0 || empty($title)) {
    $_SESSION['flash_error'] = t('required_fields_missing');
    header('Location: ?page=intervention_edit&id=' . $id);
    exit();
}

// Vérification de l'équipement
$stmt = $pdo->prepare("SELECT id FROM equipment WHERE id = ?");
$stmt->execute([$equipment_id]);
if (!$stmt->fetch()) {
    $_SESSION['flash_error'] = t('equipment_not_found');
    header('Location: ?page=preventive_edit&id=' . $id);
    exit();
}

// Calcul de next_due
$next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

try {
    $pdo->beginTransaction();

    $sql = "UPDATE preventive_maintenance SET 
                equipment_id = ?,
                frequency_days = ?,
                last_done = ?,
                next_due = ?,
                title = ?,
                instructions = ?,
                priority = ?,
                planned_duration = ?,
                zone = ?,
                localisation = ?,
                technician_id = ?,
                team_id = ?,
                contractor_id = ?,
                updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $equipment_id,
        $frequency_days,
        $last_done,
        $next_due,
        $title,
        $instructions,
        $priority,
        $planned_duration,
        $zone,
        $localisation,
        $technician_id,
        $team_id,
        $contractor_id,
        $id
    ]);

    if (!$result) {
        throw new Exception(t('save_error'));
    }

    $pdo->commit();

    // Journalisation
    $assignee = '';
    if ($team_id && $contractor_id) {
        $assignee = " (équipe ID: $team_id + prestataire ID: $contractor_id)";
    } elseif ($technician_id && $contractor_id) {
        $assignee = " (technicien ID: $technician_id + prestataire ID: $contractor_id)";
    } elseif ($team_id) {
        $assignee = " (équipe ID: $team_id)";
    } elseif ($technician_id) {
        $assignee = " (technicien ID: $technician_id)";
    } elseif ($contractor_id) {
        $assignee = " (prestataire ID: $contractor_id)";
    }
    logUserAction($_SESSION['user_id'], 'preventive_updated', "Preventive modifiée: ID $id$assignee");
    
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=preventive');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=preventive_edit&id=' . $id);
}

exit();