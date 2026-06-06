<?php
// actions/equipment_add_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
    
    $sql = "INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs, probability_score, severity_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'], $_POST['name'], $_POST['type'], $_POST['location'], $_POST['supplier'],
        $purchase_date, $warranty_end, $_POST['technical_specs'], $_POST['probability_score'], $_POST['severity_score']
    ]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'equipment_created', "Equipment created: {$_POST['code']}");
        header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=equipment_add&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=equipment');
    exit();
}