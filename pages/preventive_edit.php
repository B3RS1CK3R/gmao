<?php
// pages/preventive_edit.php - Formulaire d'édition (sans traitement POST)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE id = ?");
$stmt->execute([$id]);
$pm = $stmt->fetch();
if (!$pm) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status != 'retired' ORDER BY name")->fetchAll();
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #fd7e14, #e06a0a); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_maintenance'); ?></h2>
        <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_maintenance'); ?></div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                        <select name="equipment_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                            <?php foreach($equipments as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>" <?php if($pm['equipment_id'] == $eq['id']) echo 'selected'; ?>><?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('frequency_days'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="frequency_days" class="form-control" min="1" value="<?php echo $pm['frequency_days']; ?>" required>
                            <span class="input-group-text"><?php echo t('days_s'); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('last_done'); ?></label>
                        <input type="date" name="last_done" class="form-control" value="<?php echo $pm['last_done']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('next_due'); ?></label>
                        <input type="date" name="next_due" class="form-control" value="<?php echo $pm['next_due']; ?>" readonly>
                        <small class="text-muted"><?php echo t('calculated_automatically'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('assigned_team'); ?></label>
                        <input type="text" name="assigned_team" class="form-control" value="<?php echo htmlspecialchars($pm['assigned_team']); ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('instructions'); ?></label>
                        <textarea name="instructions" class="form-control" rows="4"><?php echo htmlspecialchars($pm['instructions']); ?></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('save'); ?></button>
                    <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>