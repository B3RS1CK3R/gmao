<?php
// actions/equipment_edit_action.php - Traitement POST de la modification d'équipement
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
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
        logUserAction($_SESSION['user_id'], 'equipment_updated', "Equipment ID: {$id} updated");
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