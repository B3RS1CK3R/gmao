<?php
// actions/preventive_add_action.php - Traitement de l'ajout d'une maintenance préventive
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=preventive');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=preventive_add');
    exit();
}

// Récupération des données
$equipment_id = intval($_POST['equipment_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$frequency_days = intval($_POST['frequency_days'] ?? 0);
$last_done = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
$instructions = trim($_POST['instructions'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$planned_duration = $_POST['planned_duration'] ?? '4h';
$zone = trim($_POST['zone'] ?? '');
$localisation = trim($_POST['localisation'] ?? '');

// Gestion de l'assignation (priorité : équipe > technicien > prestataire)
$technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
$team_id = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
$contractor_id = !empty($_POST['contractor_id']) ? intval($_POST['contractor_id']) : null;

if ($team_id) {
    $technician_id = null;
    $contractor_id = null;
} elseif ($technician_id) {
    $contractor_id = null;
}

// Validation
if ($equipment_id <= 0 || empty($title) || $frequency_days < 1) {
    $_SESSION['flash_error'] = t('required_fields_missing');
    header('Location: ?page=preventive_add');
    exit();
}

// Vérification de l'équipement
$stmt = $pdo->prepare("SELECT id, name FROM equipment WHERE id = ?");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch();
if (!$equipment) {
    $_SESSION['flash_error'] = t('equipment_not_found');
    header('Location: ?page=preventive_add');
    exit();
}

// Calcul de next_due
$next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

// Génération du numéro de tâche
$task_number = generateTaskNumber($pdo, 'preventive');

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO preventive_maintenance (
                task_number,
                equipment_id,
                frequency_days,
                last_done,
                next_due,
                title,
                instructions,
                priority,
                planned_duration,
                zone,
                localisation,
                technician_id,
                team_id,
                contractor_id,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_number,
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
        $contractor_id
    ]);

    if (!$result) {
        throw new Exception(t('save_error'));
    }

    $preventive_id = $pdo->lastInsertId();

    $pdo->commit();

    // Journalisation
    $assignee = '';
    if ($team_id) {
        $assignee = " (équipe ID: $team_id)";
    } elseif ($technician_id) {
        $assignee = " (technicien ID: $technician_id)";
    } elseif ($contractor_id) {
        $assignee = " (prestataire ID: $contractor_id)";
    }
    logUserAction($_SESSION['user_id'], 'preventive_created', "Maintenance préventive créée: $task_number (ID: $preventive_id)$assignee");
    
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=preventive');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=preventive_add');
}

exit();