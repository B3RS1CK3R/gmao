<?php
// actions/preventive_add_action.php - Traitement POST pour preventive_add
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_number = generateTaskNumber($pdo, 'preventive', false);
    $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
    if ($team_id) $technician_id = null;

    $sql = "INSERT INTO preventive_maintenance (task_number, equipment_id, frequency_days, last_done, next_due, title, instructions, technician_id, team_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_number,
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $_POST['last_done'],
        $_POST['next_due'],
        $_POST['title'],
        $_POST['instructions'],
        $technician_id,
        $team_id
    ]);

    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_created', "Preventive created: $task_number");
        header('Location: ?page=preventive&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=preventive_add&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=preventive');
    exit();
}