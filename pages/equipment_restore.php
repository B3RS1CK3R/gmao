<?php
// pages/equipment_restore.php - Restaurer un équipement retiré
if($_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id) {
    header('Location: ?page=equipment');
    exit();
}

$stmt = $pdo->prepare("SELECT name FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();
if(!$eq) {
    header('Location: ?page=equipment');
    exit();
}

$stmt2 = $pdo->prepare("UPDATE equipment SET status = 'active' WHERE id = ?");
$stmt2->execute([$id]);
logUserAction($_SESSION['user_id'], 'equipment_restored', "Equipment ID: {$id}, Name: {$eq['name']} reactivated");
header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));
exit();
?>