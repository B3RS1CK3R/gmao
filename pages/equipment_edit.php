<?php
// pages/equipment_edit.php - Formulaire d'édition (sans traitement POST)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=equipment');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();
if (!$eq) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

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
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_equipment'); ?> : <?php echo htmlspecialchars($eq['code']); ?></h2>
        <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_equipment'); ?></div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($eq['code']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($eq['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('type'); ?></label>
                        <input type="text" name="type" class="form-control" value="<?php echo htmlspecialchars($eq['type']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('location'); ?></label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($eq['location']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('supplier'); ?></label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($eq['supplier']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="status" class="form-select">
                            <option value="active" <?php if($eq['status'] == 'active') echo 'selected'; ?>><?php echo t('active'); ?></option>
                            <option value="maintenance" <?php if($eq['status'] == 'maintenance') echo 'selected'; ?>><?php echo t('maintenance'); ?></option>
                            <option value="broken" <?php if($eq['status'] == 'broken') echo 'selected'; ?>><?php echo t('broken'); ?></option>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                            <option value="retired" <?php if($eq['status'] == 'retired') echo 'selected'; ?>><?php echo t('retired'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('purchase_date'); ?></label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo $eq['purchase_date']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('warranty_end'); ?></label>
                        <input type="date" name="warranty_end" class="form-control" value="<?php echo $eq['warranty_end']; ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('technical_specs'); ?></label>
                        <textarea name="technical_specs" class="form-control" rows="3"><?php echo htmlspecialchars($eq['technical_specs']); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('probability_score'); ?></label>
                        <select name="probability_score" class="form-select">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php if(($eq['probability_score'] ?? 1) == $i) echo 'selected'; ?>><?php echo $i; ?> - <?php echo t(['very_low','low','medium','high','very_high'][$i-1]); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('severity_score'); ?></label>
                        <select name="severity_score" class="form-select">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php if(($eq['severity_score'] ?? 1) == $i) echo 'selected'; ?>><?php echo $i; ?> - <?php echo t(['negligible','minor','moderate','serious','critical'][$i-1]); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>