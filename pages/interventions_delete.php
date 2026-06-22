<?php
// pages/interventions_delete.php - Confirmation de suppression d'une intervention
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=interventions');
    exit();
}

$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if (!$interv) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_message']);
?>
<style>
    .delete-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .delete-header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; }
    .form-control { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-danger { background: #dc3545; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-danger:hover { background: #c82333; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary:hover { background: #5a6268; }
    .info-row { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .label { font-weight: 600; width: 150px; display: inline-block; }
    .input-group { position: relative; display: flex; flex-wrap: nowrap; align-items: stretch; width: 100%; }
    .input-group .form-control { position: relative; flex: 1 1 auto; width: 1%; min-width: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; }
    .input-group .btn-outline-secondary { border-top-left-radius: 0; border-bottom-left-radius: 0; border: 1px solid #ddd; border-left: none; background: white; }
    .input-group .btn-outline-secondary:hover { background: #f0f0f0; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-alt"></i> <?php echo t('delete_intervention'); ?></h2>
        <a href="?page=interventions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($interv['task_status'] == 'completed' || $interv['task_status'] == 'closed'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <?php echo t('cannot_delete_completed_intervention'); ?>
        </div>
        <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    <?php else: ?>
        <div class="delete-card">
            <div class="delete-header">
                <i class="fas fa-exclamation-triangle"></i> <?php echo t('confirm_deletion'); ?>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo t('delete_confirm_intervention'); ?>
                </div>

                <div class="info-row">
                    <span class="label"><?php echo t('task_number'); ?> :</span>
                    <?php echo htmlspecialchars($interv['task_number'] ?? '#' . $interv['id']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('equipment'); ?> :</span>
                    <?php echo htmlspecialchars($interv['equipment_code'] . ' - ' . $interv['equipment_name']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('title'); ?> :</span>
                    <?php echo htmlspecialchars($interv['title']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('type'); ?> :</span>
                    <?php 
                    $type_labels = [
                        'corrective' => '🔧 ' . t('corrective'),
                        'preventive' => '📋 ' . t('preventive'),
                        'emergency' => '🚨 ' . t('emergency')
                    ];
                    echo $type_labels[$interv['type']] ?? $interv['type'];
                    ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('priority'); ?> :</span>
                    <?php 
                    $priority_labels = [
                        'low' => '🟢 ' . t('low'),
                        'medium' => '🟡 ' . t('medium'),
                        'high' => '🟠 ' . t('high'),
                        'critical' => '🔴 ' . t('critical')
                    ];
                    echo $priority_labels[$interv['priority']] ?? $interv['priority'];
                    ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('status'); ?> :</span>
                    <?php 
                    $status_labels = [
                        'pending' => '🟡 ' . t('pending'),
                        'in_progress' => '🔵 ' . t('in_progress'),
                        'completed' => '🟢 ' . t('completed'),
                        'cancelled' => '⚫ ' . t('cancelled'),
                        'closed' => '⚫ ' . t('closed')
                    ];
                    echo $status_labels[$interv['task_status']] ?? $interv['task_status'];
                    ?>
                </div>

                <hr>

                <p class="text-muted"><i class="fas fa-info-circle"></i> <?php echo t('delete_warning_intervention'); ?></p>

                <form method="POST" action="?page=interventions_delete_action">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted"><?php echo t('password_required_to_delete'); ?></small>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> <?php echo t('confirm_delete'); ?>
                        </button>
                        <a href="?page=interventions" class="btn btn-secondary">
                            <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    if (button && button.tagName === 'BUTTON') {
        if (field.type === 'password') {
            field.type = 'text';
            button.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            field.type = 'password';
            button.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
}
</script>