<?php
// pages/technicians_delete.php - Confirmation de suppression (soft delete)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
$stmt->execute([$id]);
$tech = $stmt->fetch();

if(!$tech) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$error = '';
$message = '';

// Traitement de la suppression
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_password'])) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if(password_verify($_POST['confirm_password'], $user['password'])) {
        $stmt2 = $pdo->prepare("UPDATE technicians SET status = 'inactive' WHERE id = ?");
        $stmt2->execute([$id]);
        logUserAction($_SESSION['user_id'], 'technician_deleted', "Technician ID: {$id} deactivated");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
    } else {
        $error = "❌ " . t('password_error');
    }
}
?>

<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
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
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-trash-alt"></i> <?php echo t('delete_technician'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></strong>
                    </div>
                    <p><?php echo t('delete_warning_technician'); ?></p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                            <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>