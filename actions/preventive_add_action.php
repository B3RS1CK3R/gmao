<?php
// actions/preventive_add_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $last_done = $_POST['last_done'] ?: date('Y-m-d');
    $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $_POST['frequency_days'] . ' days'));
    
    $sql = "INSERT INTO preventive_maintenance (equipment_id, frequency_days, last_done, next_due, instructions, assigned_team) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $last_done,
        $next_due,
        $_POST['instructions'],
        $_POST['assigned_team']
    ]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_created', "Preventive maintenance created for equipment ID: {$_POST['equipment_id']}");
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