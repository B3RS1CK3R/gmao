<?php
// actions/intervention_add_action.php - Traitement de l'ajout d'une intervention
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor', 'technician'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=interventions');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=intervention_add');
    exit();
}

// Récupération des données
$equipment_id = intval($_POST['equipment_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$type = $_POST['type'] ?? 'corrective';
$priority = $_POST['priority'] ?? 'medium';
$task_status = $_POST['task_status'] ?? 'a_faire';
$description = trim($_POST['description'] ?? '');
$reported_by = trim($_POST['reported_by'] ?? $_SESSION['username'] ?? 'system');
$intervention_date = !empty($_POST['intervention_date']) ? $_POST['intervention_date'] : null;
$task_type = $_POST['task_type'] ?? 'revision';
$zone = trim($_POST['zone'] ?? '');
$localisation = trim($_POST['localisation'] ?? '');
$planned_duration = $_POST['planned_duration'] ?? '4h';

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
if ($equipment_id <= 0 || empty($title)) {
    $_SESSION['flash_error'] = t('required_fields_missing');
    header('Location: ?page=intervention_add');
    exit();
}

// Vérification de l'existence de l'équipement
$stmt = $pdo->prepare("SELECT id, name FROM equipment WHERE id = ?");
$stmt->execute([$equipment_id]);
$equipment = $stmt->fetch();
if (!$equipment) {
    $_SESSION['flash_error'] = t('equipment_not_found');
    header('Location: ?page=intervention_add');
    exit();
}

// Génération du numéro de tâche
$task_number = generateTaskNumber($pdo, 'intervention');

try {
    $pdo->beginTransaction();

    // Vérifier les colonnes disponibles
    $columns = ['task_number', 'equipment_id', 'type', 'priority', 'title', 'description', 'reported_by', 'task_status', 'task_type', 'zone', 'localisation', 'planned_duration', 'created_at'];
    $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', '?', 'NOW()'];
    $values = [
        $task_number,
        $equipment_id,
        $type,
        $priority,
        $title,
        $description,
        $reported_by,
        $task_status,
        $task_type,
        $zone,
        $localisation,
        $planned_duration
    ];

    // Ajouter intervention_date si disponible
    if ($intervention_date) {
        $columns[] = 'intervention_date';
        $placeholders[] = '?';
        $values[] = $intervention_date;
    }

    // Ajouter technician_id si disponible
    if ($technician_id !== null) {
        $columns[] = 'technician_id';
        $placeholders[] = '?';
        $values[] = $technician_id;
    }

    // Ajouter team_id si disponible
    if ($team_id !== null) {
        $columns[] = 'team_id';
        $placeholders[] = '?';
        $values[] = $team_id;
    }

    // Ajouter contractor_id si disponible
    if ($contractor_id !== null) {
        $columns[] = 'contractor_id';
        $placeholders[] = '?';
        $values[] = $contractor_id;
    }

    // Construction de la requête
    $sql = "INSERT INTO interventions (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    $intervention_id = $pdo->lastInsertId();

    // Si un technicien est assigné, ajouter au planning
    if ($technician_id && $intervention_date) {
        try {
            $scheduled_start = $intervention_date . ' 09:00:00';
            $stmt = $pdo->prepare("
                INSERT INTO work_schedule (technician_id, intervention_id, scheduled_start, status) 
                VALUES (?, ?, ?, 'scheduled')
            ");
            $stmt->execute([$technician_id, $intervention_id, $scheduled_start]);
        } catch (PDOException $e) {
            // Ignorer si la table work_schedule n'existe pas
        }
    }

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
    logUserAction($_SESSION['user_id'], 'intervention_created', "Intervention créée: $task_number (ID: $intervention_id)$assignee");
    
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=interventions');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=intervention_add');
}

exit();