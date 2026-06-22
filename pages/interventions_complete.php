<?php
// pages/intervention_complete.php - Terminer une intervention
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor', 'technician'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=interventions');
    exit();
}

// Récupération de l'intervention avec équipement
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

// Récupération des pièces utilisées
$partsStmt = $pdo->prepare("
    SELECT sp.part_number, sp.name, sm.quantity, sm.movement_date
    FROM stock_movements sm
    JOIN spare_parts sp ON sm.part_id = sp.id
    WHERE sm.related_type = 'intervention' AND sm.related_id = ?
    ORDER BY sm.movement_date DESC
");
$partsStmt->execute([$id]);
$parts_used = $partsStmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } else {
        $duration = !empty($_POST['duration_hours']) ? floatval($_POST['duration_hours']) : null;
        $report = trim($_POST['completion_report'] ?? '');

        $sql = "UPDATE interventions SET 
                task_status = 'completed',
                completed_date = NOW(),
                duration_hours = ?,
                completion_report = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$duration, $report, $id])) {
            logUserAction($_SESSION['user_id'], 'intervention_completed', "Intervention ID: $id completed");
            $_SESSION['flash_message'] = t('save_success');
            header('Location: ?page=intervention_view&id=' . $id);
            exit();
        } else {
            $error = t('save_error');
        }
    }
}
?>
<style>
    .confirm-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .confirm-header { background: linear-gradient(135deg, #17a2b8, #0c6b7e); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-success { background: #28a745; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-success:hover { background: #218838; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary:hover { background: #5a6268; }
    .info-row { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .info-row:last-child { border-bottom: none; }
    .label { font-weight: 600; width: 150px; display: inline-block; }
    textarea[readonly] { background: #f8f9fa; cursor: default; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-check-circle"></i> <?php echo t('complete_intervention'); ?></h2>
        <a href="?page=intervention_view&id=<?php echo $id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="confirm-card">
                <div class="confirm-header">
                    <i class="fas fa-check-circle"></i> <?php echo t('confirm_completion'); ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <?= csrf_input() ?>

                        <div class="info-row">
                            <span class="label"><?php echo t('task_number'); ?> :</span>
                            <strong><?php echo htmlspecialchars($interv['task_number'] ?? '#' . $interv['id']); ?></strong>
                        </div>
                        <div class="info-row">
                            <span class="label"><?php echo t('equipment'); ?> :</span>
                            <?php echo htmlspecialchars($interv['equipment_name']); ?>
                            <br><span class="text-muted" style="margin-left:150px;"><?php echo htmlspecialchars($interv['equipment_code']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label"><?php echo t('title'); ?> :</span>
                            <?php echo htmlspecialchars($interv['title']); ?>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label"><?php echo t('duration_hours'); ?> (step 0.5)</label>
                            <input type="number" name="duration_hours" class="form-control" step="0.5" min="0" value="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo t('completion_report'); ?></label>
                            <textarea name="completion_report" class="form-control" rows="10" placeholder="<?php echo t('report_placeholder'); ?>"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo t('parts_used'); ?></label>
                            <textarea class="form-control" rows="8" readonly style="background:#f8f9fa; font-family: monospace;"><?php
if (empty($parts_used)) {
    echo t('no_parts_used');
} else {
    foreach ($parts_used as $p) {
        echo $p['part_number'] . ' - ' . $p['name'] . ' : ' . $p['quantity'] . ' (le ' . format_date_local($p['movement_date'], 'short') . ")\n";
    }
}
?></textarea>
                            <small class="text-muted"><?php echo t('parts_used_readonly'); ?></small>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> <?php echo t('confirm_complete'); ?>
                            </button>
                            <a href="?page=intervention_view&id=<?php echo $id; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> <?php echo t('cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Espace pour plus d'infos si besoin -->
        </div>
    </div>
</div>