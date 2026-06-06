<?php
// pages/equipment_delete.php - Suppression d'équipement (soft delete)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(!$id) {
    header('Location: ?page=equipment');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();
if(!$eq) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_password'])) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if(password_verify($_POST['confirm_password'], $user['password'])) {
        $stmt2 = $pdo->prepare("UPDATE equipment SET status = 'retired' WHERE id = ?");
        $stmt2->execute([$id]);
        logUserAction($_SESSION['user_id'], 'equipment_deleted', "Equipment ID: {$id} deactivated");
        header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        $error = t('password_error');
    }
}
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 15px 20px; font-weight: bold; }
    .btn-danger { background: #dc3545; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="form-card">
                <div class="form-card-header"><i class="fas fa-trash-alt"></i> <?php echo t('delete_equipment'); ?></div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?php echo t('delete_confirm_equipment'); ?></div>
                    <p><strong><?php echo t('equipment'); ?> :</strong> <?php echo htmlspecialchars($eq['name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($eq['code']); ?></small></p>
                    <p class="text-muted small"><?php echo t('delete_warning'); ?></p>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                            <small class="text-muted"><?php echo t('password_required_to_delete'); ?></small>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm_delete'); ?></button>
                            <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>