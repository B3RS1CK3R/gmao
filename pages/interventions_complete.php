<?php
// pages/interventions_complete.php - Formulaire de complétion d'une intervention
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor' && $_SESSION['role'] != 'technician') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: ?page=interventions');
    exit();
}

// Récupération de l'intervention
$stmt = $pdo->prepare("SELECT i.*, e.name as equipment_name FROM interventions i JOIN equipment e ON i.equipment_id = e.id WHERE i.id = ?");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if(!$interv) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("
        UPDATE interventions 
        SET task_status = 'termine', 
            completed_date = NOW(), 
            completion_report = ?,
            duration_hours = COALESCE(?, duration_hours)
        WHERE id = ?
    ");
    $result = $stmt->execute([$_POST['completion_report'], $_POST['duration_hours'], $id]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_completed', "Intervention completed ID: {$id}");
        $message = "✅ " . t('intervention_completed');
        echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
    } else {
        $error = "❌ " . t('save_error');
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
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .btn-success {
        background: #28a745;
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
                    <i class="fas fa-check-circle"></i> <?php echo t('complete_intervention'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p><strong><?php echo t('title'); ?> :</strong> <?php echo htmlspecialchars($interv['title']); ?></p>
                    <p><strong><?php echo t('task_number'); ?> :</strong> <?php echo htmlspecialchars($interv['task_number'] ?? 'N/A'); ?></p>
                    <p><strong><?php echo t('equipment'); ?> :</strong> <?php echo htmlspecialchars($interv['equipment_name']); ?></p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('duration_hours'); ?></label>
                            <input type="number" step="0.5" name="duration_hours" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('completion_report'); ?></label>
                            <textarea name="completion_report" class="form-control" rows="4" placeholder="<?php echo t('report_placeholder'); ?>" required></textarea>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> <?php echo t('confirm'); ?></button>
                            <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>