<?php
// pages/interventions_delete.php - Confirmation de suppression (annulation)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: ?page=interventions');
    exit();
}

// Récupération de l'intervention
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if(!$interv) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_password'])) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if(password_verify($_POST['confirm_password'], $user['password'])) {
        $stmt2 = $pdo->prepare("UPDATE interventions SET status = 'cancelled', task_status = 'cloturee' WHERE id = ?");
        $stmt2->execute([$id]);
        logUserAction($_SESSION['user_id'], 'intervention_deleted', "Intervention ID: {$id} cancelled");
        echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
        exit();
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
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-trash-alt"></i> <?php echo t('cancel_intervention'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo t('delete_confirm'); ?><br>
                        <strong><?php echo htmlspecialchars($interv['title']); ?></strong>
                    </div>
                    <p><?php echo t('delete_warning'); ?></p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                            <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>