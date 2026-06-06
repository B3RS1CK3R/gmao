<?php
// pages/preventive_add.php - Formulaire d'ajout de maintenance préventive (sans traitement POST)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status IN ('active', 'maintenance') ORDER BY name")->fetchAll();
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-success { background: linear-gradient(135deg, #28a745, #1e7e34); border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_maintenance'); ?></h2>
        <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-plus-circle"></i> <?php echo t('add_maintenance'); ?></div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                        <select name="equipment_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                            <?php foreach($equipments as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('frequency_days'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="frequency_days" class="form-control" min="1" required>
                            <span class="input-group-text"><?php echo t('days_s'); ?></span>
                        </div>
                        <small class="text-muted"><?php echo t('frequency_help'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('last_done'); ?></label>
                        <input type="date" name="last_done" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('assigned_team'); ?></label>
                        <input type="text" name="assigned_team" class="form-control" placeholder="<?php echo t('team_placeholder'); ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('instructions'); ?></label>
                        <textarea name="instructions" class="form-control" rows="4" placeholder="<?php echo t('instructions_placeholder'); ?>"></textarea>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                    <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>