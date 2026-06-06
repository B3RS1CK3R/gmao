<?php
// pages/preventive_complete.php - Validation d'une maintenance préventive (crée une intervention)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id) {
    header('Location: ?page=preventive');
    exit();
}

$stmt = $pdo->prepare("SELECT pm.*, e.name as equipment_name FROM preventive_maintenance pm JOIN equipment e ON pm.equipment_id = e.id WHERE pm.id = ?");
$stmt->execute([$id]);
$pm = $stmt->fetch();
if(!$pm) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$today = date('Y-m-d');
$next_due = date('Y-m-d', strtotime($today . ' + ' . $pm['frequency_days'] . ' days'));

// Mettre à jour la maintenance préventive
$pdo->prepare("UPDATE preventive_maintenance SET last_done = ?, next_due = ? WHERE id = ?")->execute([$today, $next_due, $id]);

// Générer un numéro de tâche
$task_number = generateTaskNumber($pdo, 'preventive', false);

// Créer l'intervention associée
$stmt3 = $pdo->prepare("
    INSERT INTO interventions (
        task_number, equipment_id, type, priority, title, description, 
        reported_by, task_type, intervention_date, task_status
    ) VALUES (?, ?, 'preventive', 'medium', ?, ?, 'system', 'maintenance_preventive', DATE_ADD(CURDATE(), INTERVAL ? DAY), 'a_faire')
");
$stmt3->execute([
    $task_number,
    $pm['equipment_id'],
    "Maintenance préventive - " . $pm['equipment_name'],
    $pm['instructions'],
    $pm['frequency_days']
]);

logUserAction($_SESSION['user_id'], 'preventive_completed', "Preventive maintenance ID: {$id} validated");
header('Location: ?page=preventive&msg=' . urlencode(t('save_success')));
exit();
?>