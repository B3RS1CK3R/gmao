<?php
// actions/preventive_edit_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $next_due = date('Y-m-d', strtotime($_POST['last_done'] . ' + ' . $_POST['frequency_days'] . ' days'));
    $sql = "UPDATE preventive_maintenance SET 
            equipment_id = ?, frequency_days = ?, last_done = ?, next_due = ?, instructions = ?, assigned_team = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $_POST['last_done'],
        $next_due,
        $_POST['instructions'],
        $_POST['assigned_team'],
        $id
    ]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_updated', "Preventive maintenance ID: {$id} updated");
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