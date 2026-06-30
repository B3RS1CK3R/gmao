<?php
// pages/contractors_restore.php - Restauration d'un prestataire extérieur
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=contractors');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM contractors WHERE id = ? AND status = 'inactive'");
$stmt->execute([$id]);
$contractor = $stmt->fetch();

if (!$contractor) {
    echo "<div class='alert alert-warning'>" . t('contractor_not_found_or_not_deleted') . "</div>";
    echo "<a href='?page=contractors' class='btn btn-secondary'><i class='fas fa-arrow-left'></i> " . t('back') . "</a>";
    return;
}

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<style>
    .restore-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .restore-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 15px 20px; font-weight: bold; }
    .info-row { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .label { font-weight: 600; width: 150px; display: inline-block; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-undo-alt"></i> <?php echo t('restore_contractor'); ?></h2>
        <a href="?page=contractors" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="restore-card">
        <div class="restore-header"><i class="fas fa-undo-alt"></i> <?php echo t('restore_contractor'); ?></div>
        <div class="card-body p-4">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <?php echo t('restore_confirm_contractor'); ?>
            </div>

            <div class="info-row">
                <span class="label"><?php echo t('company_name'); ?></span>
                <?php echo htmlspecialchars($contractor['company_name']); ?>
            </div>
            <div class="info-row">
                <span class="label"><?php echo t('siret'); ?></span>
                <?php echo htmlspecialchars($contractor['siret'] ?: '-'); ?>
            </div>
            <div class="info-row">
                <span class="label"><?php echo t('email'); ?></span>
                <?php echo htmlspecialchars($contractor['email'] ?: '-'); ?>
            </div>

            <form method="POST" action="?page=contractors_restore_action">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-undo-alt"></i> <?php echo t('restore'); ?></button>
                    <a href="?page=contractors" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>