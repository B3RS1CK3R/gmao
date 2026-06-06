<?php
// actions/technician_add_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "INSERT INTO technicians (employee_id, firstname, lastname, phone, email, specialty, hire_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['employee_id'],
        $_POST['firstname'],
        $_POST['lastname'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['specialty'],
        $_POST['hire_date'],
        $_POST['status']
    ]);
    
    if ($result) {
        $technicianName = $_POST['firstname'] . ' ' . $_POST['lastname'];
        logUserAction($_SESSION['user_id'], 'technician_created', "[{$technicianName}] - Technician created (ID: {$_POST['employee_id']}, Role: {$_POST['specialty']})");
        header('Location: ?page=technicians&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=technician_add&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=technicians');
    exit();
}