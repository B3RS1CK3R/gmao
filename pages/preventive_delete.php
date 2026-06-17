<?php
// pages/preventive_delete.php - Formulaire de confirmation de suppression
ob_start();
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    ob_end_flush();
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

$stmt = $pdo->prepare("SELECT pm.*, e.name as equipment_name 
                       FROM preventive_maintenance pm 
                       JOIN equipment e ON pm.equipment_id = e.id 
                       WHERE pm.id = ?");
$stmt->execute([$id]);
$pm = $stmt->fetch();

if (!$pm) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    ob_end_flush();
    return;
}

$error = $_GET['error'] ?? '';
ob_end_flush();
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
                <div class="form-card-header"><i class="fas fa-trash-alt"></i> <?php echo t('delete_maintenance'); ?></div>
                <div class="card-body p-4">
                    <?php if ($error == 'wrong_password'): ?>
                        <div class="alert alert-danger"><?php echo t('password_error'); ?></div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($pm['equipment_name']); ?></strong>
                    </div>
                    <p><?php echo t('delete_warning'); ?></p>

                    <form method="POST" action="?page=preventive_delete_action&id=<?php echo $id; ?>">
                        <?= csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                            <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>