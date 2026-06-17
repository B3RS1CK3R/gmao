<?php
// actions/preventive_add_action.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    header('Location: index.php?page=preventive&err=' . urlencode(t('access_denied')));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_id = intval($_POST['equipment_id'] ?? 0);
    $frequency_days = intval($_POST['frequency_days'] ?? 0);
    $last_done = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
    $title = trim($_POST['title'] ?? '');
    $instructions = trim($_POST['instructions'] ?? '');
    if ($title) {
        $instructions = $title . "\n\n" . $instructions;
    }
    $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id = (isset($_POST['team_id']) && $_POST['team_id'] !== '') ? intval($_POST['team_id']) : null;
    if ($team_id) $technician_id = null;

    if ($equipment_id <= 0 || $frequency_days <= 0) {
        header('Location: index.php?page=preventive_add&err=' . urlencode("Données invalides"));
        exit();
    }

    $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));
    $reference = generateTaskNumber($pdo, 'preventive', false);

    $sql = "INSERT INTO preventive_maintenance 
            (reference, equipment_id, frequency_days, last_done, next_due, instructions, technician_id, team_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$reference, $equipment_id, $frequency_days, $last_done, $next_due, $instructions, $technician_id, $team_id]);

    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_created', "Ref: $reference | Eq: $equipment_id | Team: $team_id");
        header('Location: index.php?page=preventive&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: index.php?page=preventive_add&err=' . urlencode(t('save_error') . ' ' . implode(' ', $stmt->errorInfo())));
        exit();
    }
}

// Si GET → redirection vers la page de formulaire
header('Location: index.php?page=preventive_add');
exit();
?>