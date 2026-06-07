<?php
// actions/equipment_edit_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=equipment');
    exit();
}

// Récupérer l'ancien nom avant modification
$stmtOld = $pdo->prepare("SELECT code, name FROM equipment WHERE id = ?");
$stmtOld->execute([$id]);
$old = $stmtOld->fetch();
if (!$old) {
    header('Location: ?page=equipment');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
    
    $sql = "UPDATE equipment SET 
            code = ?, name = ?, type = ?, location = ?, supplier = ?, 
            purchase_date = ?, warranty_end = ?, technical_specs = ?,
            probability_score = ?, severity_score = ?, status = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'], $_POST['name'], $_POST['type'], $_POST['location'], $_POST['supplier'],
        $purchase_date, $warranty_end, $_POST['technical_specs'],
        $_POST['probability_score'], $_POST['severity_score'], $_POST['status'],
        $id
    ]);
    
    if ($result) {
        logUserAction($_SESSION['user_id'], 'equipment_updated', "Equipment ID: $id, Name: {$old['name']} updated. New code: {$_POST['code']}, New name: {$_POST['name']}");
        header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=equipment_edit&id=' . $id . '&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=equipment');
    exit();
}