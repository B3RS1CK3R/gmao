<?php
// pages/equipment_delete.php - Confirmation de suppression d'un équipement
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=equipment');
    exit();
}

// Récupération de l'équipement
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();

if (!$eq) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Vérifier si l'équipement a des interventions associées (non annulées)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM interventions WHERE equipment_id = ? AND task_status != 'cancelled'");
$stmt->execute([$id]);
$interv_count = $stmt->fetch()['count'] ?? 0;

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_message']);
?>

<style>
    .delete-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .delete-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .info-row {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .label {
        font-weight: 600;
        width: 150px;
        display: inline-block;
    }
    .input-group {
        position: relative;
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }
    .input-group .form-control {
        position: relative;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group .btn-outline-secondary {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border: 1px solid #ddd;
        border-left: none;
        background: white;
    }
    .input-group .btn-outline-secondary:hover {
        background: #f0f0f0;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-alt"></i> <?php echo t('delete_equipment'); ?></h2>
        <a href="?page=equipment" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if ($interv_count > 0): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('cannot_delete_equipment_with_interventions'); ?>
            (<?php echo $interv_count; ?> interventions)
        </div>
        <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    <?php else: ?>
        <div class="delete-card">
            <div class="delete-header">
                <i class="fas fa-exclamation-triangle"></i> <?php echo t('confirm_deletion'); ?>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo t('delete_confirm_equipment'); ?>
                </div>

                <div class="info-row">
                    <span class="label"><?php echo t('code'); ?> :</span>
                    <?php echo htmlspecialchars($eq['code']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('name'); ?> :</span>
                    <?php echo htmlspecialchars($eq['name']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('type'); ?> :</span>
                    <?php echo htmlspecialchars($eq['type']); ?>
                </div>
                <div class="info-row">
                    <span class="label"><?php echo t('status'); ?> :</span>
                    <?php echo t($eq['status']); ?>
                </div>

                <hr>

                <p class="text-muted"><i class="fas fa-info-circle"></i> <?php echo t('delete_warning_equipment'); ?></p>

                <form method="POST" action="?page=equipment_delete_action">
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
                        <a href="?page=equipment" class="btn btn-secondary">
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