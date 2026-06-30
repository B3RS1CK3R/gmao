<?php
// actions/preventive_edit_action.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    $_SESSION['flash_error'] = t('access_denied');
    header('Location: ?page=preventive');
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    $_SESSION['flash_error'] = t('invalid_id');
    header('Location: ?page=preventive');
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    $_SESSION['flash_error'] = t('csrf_invalid');
    header('Location: ?page=preventive_edit&id=' . $id);
    exit();
}

// Vérification existence
$stmt = $pdo->prepare("SELECT task_status FROM preventive_maintenance WHERE id = ?");
$stmt->execute([$id]);
$existing = $stmt->fetch();

if (!$existing) {
    $_SESSION['flash_error'] = t('not_found');
    header('Location: ?page=preventive');
    exit();
}

if (in_array($existing['task_status'] ?? '', ['completed', 'closed', 'cancelled'])) {
    $_SESSION['flash_error'] = t('cannot_edit_completed_preventive');
    header('Location: ?page=preventive_view&id=' . $id);
    exit();
}

// Données du formulaire
$equipment_id     = intval($_POST['equipment_id'] ?? 0);
$frequency_days   = intval($_POST['frequency_days'] ?? 0);
$last_done        = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
$title            = trim($_POST['title'] ?? '');
$instructions     = trim($_POST['instructions'] ?? '');
$priority         = $_POST['priority'] ?? 'medium';
$planned_duration = trim($_POST['planned_duration'] ?? '4h');
$zone             = trim($_POST['zone'] ?? '');
$localisation     = trim($_POST['localisation'] ?? '');

$technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
$team_id       = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
$contractor_id = !empty($_POST['contractor_id']) ? intval($_POST['contractor_id']) : null;

if ($team_id) $technician_id = null;

// Validations
if ($equipment_id <= 0 || empty($title) || $frequency_days < 1) {
    $_SESSION['flash_error'] = t('required_fields_missing');
    header('Location: ?page=preventive_edit&id=' . $id);
    exit();
}

$last_done = date('Y-m-d', strtotime($last_done));
$next_due  = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

// Récupération anciennes données
$stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE id = ?");
$stmt->execute([$id]);
$old_data = $stmt->fetch(PDO::FETCH_ASSOC);

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
    $stmt->execute([
        $equipment_id, $frequency_days, $last_done, $next_due,
        $title, $instructions, $priority, $planned_duration,
        $zone, $localisation,
        $technician_id, $team_id, $contractor_id, $id
    ]);

    $pdo->commit();

    logUserAction(
        $_SESSION['user_id'], 
        'preventive_updated', 
        "Maintenance préventive modifiée ID: $id"
    );
    
    $_SESSION['flash_message'] = t('save_success');
    header('Location: ?page=preventive');

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = "Erreur : " . $e->getMessage();
    header('Location: ?page=preventive_edit&id=' . $id);
}

exit();