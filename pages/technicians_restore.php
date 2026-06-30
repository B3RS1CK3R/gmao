<?php
// pages/technicians_restore.php - Restaurer un technicien (admin uniquement)
if($_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id) {
    header('Location: ?page=technicians');
    exit();
}

$stmt = $pdo->prepare("UPDATE technicians SET status = 'active' WHERE id = ?");
$stmt->execute([$id]);
logUserAction($_SESSION['user_id'], 'technician_restored', "Technician ID: {$id} reactivated");
header('Location: ?page=technicians&msg=' . urlencode(t('save_success')));
exit();
?>