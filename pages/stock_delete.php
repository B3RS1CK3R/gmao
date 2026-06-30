<?php
// pages/stock_delete.php - Supprimer une pièce détachée (désactivation)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=stock');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM spare_parts WHERE id = ?");
$stmt->execute([$id]);
$part = $stmt->fetch();
if (!$part) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } else {
        if (!isset($_POST['confirm_password']) || empty($_POST['confirm_password'])) {
            $error = t('password_required');
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if (password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt = $pdo->prepare("UPDATE spare_parts SET quantity = -1, min_quantity = -1 WHERE id = ?");
                $stmt->execute([$id]);
                logUserAction($_SESSION['user_id'], 'stock_deleted', "Part ID: $id deactivated");
                header('Location: ?page=stock&msg=' . urlencode(t('save_success')));
                exit();
            } else {
                $error = t('password_error');
            }
        }
    }
}
?>
<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; }
    .form-control { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-danger { background: #dc3545; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-alt"></i> <?php echo t('delete_part'); ?></h2>
        <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-trash-alt"></i> <?php echo t('delete_part'); ?></div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($part['name']); ?></strong> (<?php echo htmlspecialchars($part['part_number']); ?>)</div>
            <p><?php echo t('delete_warning'); ?></p>
            <form method="POST">
                <?= csrf_input() ?>
                <div class="mb-3">
                    <label class="form-label"><?php echo t('confirm_password'); ?></label>
                    <input type="password" name="confirm_password" class="form-control" required autocomplete="off">
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                    <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>