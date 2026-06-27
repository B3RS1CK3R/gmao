<?php
// actions/intervention_edit_action.php - Traitement de la modification d'une intervention
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor', 'technician'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=interventions');
    exit();
}

// Récupération de l'ID
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=interventions');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=intervention_edit&id=' . $id);
    exit();
}

// Récupération de l'intervention existante
$stmt = $pdo->prepare("SELECT task_status FROM interventions WHERE id = ?");
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = t('not_found');
    header('Location: ?page=interventions');
    exit();
}

// Empêcher la modification d'une intervention terminée
if (in_array($existing['task_status'], ['completed', 'closed', 'cancelled'])) {
    $_SESSION['flash_error'] = t('cannot_edit_completed_intervention');
    header('Location: ?page=intervention_view&id=' . $id);
    exit();
}

// Récupération des données
$equipment_id = intval($_POST['equipment_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$type = $_POST['type'] ?? 'corrective';
$priority = $_POST['priority'] ?? 'medium';
$task_status = $_POST['task_status'] ?? 'a_faire';
$description = trim($_POST['description'] ?? '');
$reported_by = trim($_POST['reported_by'] ?? '');
$scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
$planned_duration = $_POST['planned_duration'] ?? '4h';
$zone = trim($_POST['zone'] ?? '');
$localisation = trim($_POST['localisation'] ?? '');
$task_type = $_POST['task_type'] ?? 'revision';

// ========== LOGIQUE D'ASSIGNATION CORRIGÉE ==========
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
    header('Location: ?page=intervention_edit&id=' . $id);
    exit();
}

try {
    $pdo->beginTransaction();

    // Construction de la requête UPDATE
    $sql = "UPDATE interventions SET 
                equipment_id = ?,
                type = ?,
                priority = ?,
                title = ?,
                description = ?,
                reported_by = ?,
                task_status = ?,
                intervention_date = ?,
                planned_duration = ?,
                task_type = ?,
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
        $type,
        $priority,
        $title,
        $description,
        $reported_by,
        $task_status,
        $scheduled_date,
        $planned_duration,
        $task_type,
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

    // Mise à jour du planning si technicien assigné
    if ($technician_id && $scheduled_date) {
        try {
            $scheduled_start = $scheduled_date . ' 09:00:00';
            $stmt = $pdo->prepare("
                UPDATE work_schedule 
                SET technician_id = ?, scheduled_start = ? 
                WHERE intervention_id = ?
            ");
            $stmt->execute([$technician_id, $scheduled_start, $id]);
            
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO work_schedule (technician_id, intervention_id, scheduled_start, status) 
                    VALUES (?, ?, ?, 'scheduled')
                ");
                $stmt->execute([$technician_id, $id, $scheduled_start]);
            }
        } catch (PDOException $e) {
            // Ignorer si la table work_schedule n'existe pas
        }
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
    logUserAction($_SESSION['user_id'], 'intervention_updated', "Intervention modifiée ID: $id$assignee");
    
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=interventions');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: ?page=intervention_edit&id=' . $id);
}

exit();