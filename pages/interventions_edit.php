<?php
// pages/interventions_edit.php - Formulaire d'édition d'une intervention
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
$stmt = $pdo->prepare("SELECT i.*, e.name as equipment_name FROM interventions i JOIN equipment e ON i.equipment_id = e.id WHERE i.id = ?");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if(!$interv) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status = 'active' ORDER BY name")->fetchAll();

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE interventions SET 
            title = ?,
            description = ?,
            priority = ?,
            task_status = ?,
            intervention_date = ?,
            task_type = ?,
            zone = ?,
            localisation = ?,
            planned_duration = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['priority'],
        $_POST['task_status'],
        !empty($_POST['intervention_date']) ? $_POST['intervention_date'] : null,
        $_POST['task_type'],
        $_POST['zone'],
        $_POST['localisation'],
        $_POST['planned_duration'],
        $id
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_updated', "Intervention ID: {$id} updated");
        $message = "✅ " . t('save_success');
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
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
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
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
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
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-edit"></i> <?php echo t('edit_intervention'); ?> : <?php echo htmlspecialchars($interv['task_number'] ?? 'N/A'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                                <select name="equipment_id" class="form-select" required>
                                    <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                                    <?php foreach($equipments as $eq): ?>
                                    <option value="<?php echo $eq['id']; ?>" <?php if($interv['equipment_id'] == $eq['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('title'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($interv['title']); ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo t('description'); ?></label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($interv['description']); ?></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('priority'); ?></label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?php if($interv['priority'] == 'low') echo 'selected'; ?>><?php echo t('low'); ?></option>
                                    <option value="medium" <?php if($interv['priority'] == 'medium') echo 'selected'; ?>><?php echo t('medium'); ?></option>
                                    <option value="high" <?php if($interv['priority'] == 'high') echo 'selected'; ?>><?php echo t('high'); ?></option>
                                    <option value="critical" <?php if($interv['priority'] == 'critical') echo 'selected'; ?>><?php echo t('critical'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('status'); ?></label>
                                <select name="task_status" class="form-select">
                                    <option value="a_faire" <?php if($interv['task_status'] == 'a_faire') echo 'selected'; ?>><?php echo t('to_do'); ?></option>
                                    <option value="en_cours" <?php if($interv['task_status'] == 'en_cours') echo 'selected'; ?>><?php echo t('in_progress'); ?></option>
                                    <option value="termine" <?php if($interv['task_status'] == 'termine') echo 'selected'; ?>><?php echo t('completed'); ?></option>
                                    <option value="cloturee" <?php if($interv['task_status'] == 'cloturee') echo 'selected'; ?>><?php echo t('closed'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('planned_date'); ?></label>
                                <input type="date" name="intervention_date" class="form-control" value="<?php echo $interv['intervention_date']; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('task_type'); ?></label>
                                <select name="task_type" class="form-select">
                                    <option value="revision" <?php if($interv['task_type'] == 'revision') echo 'selected'; ?>><?php echo t('revision'); ?></option>
                                    <option value="depannage" <?php if($interv['task_type'] == 'depannage') echo 'selected'; ?>><?php echo t('repair'); ?></option>
                                    <option value="installation" <?php if($interv['task_type'] == 'installation') echo 'selected'; ?>><?php echo t('installation'); ?></option>
                                    <option value="maintenance_preventive" <?php if($interv['task_type'] == 'maintenance_preventive') echo 'selected'; ?>><?php echo t('preventive_maintenance'); ?></option>
                                    <option value="controle" <?php if($interv['task_type'] == 'controle') echo 'selected'; ?>><?php echo t('inspection'); ?></option>
                                    <option value="autre" <?php if($interv['task_type'] == 'autre') echo 'selected'; ?>><?php echo t('other'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('planned_duration'); ?></label>
                                <select name="planned_duration" class="form-select">
                                    <option value="1h" <?php if($interv['planned_duration'] == '1h') echo 'selected'; ?>>1h</option>
                                    <option value="2h" <?php if($interv['planned_duration'] == '2h') echo 'selected'; ?>>2h</option>
                                    <option value="2h30" <?php if($interv['planned_duration'] == '2h30') echo 'selected'; ?>>2h30</option>
                                    <option value="3h" <?php if($interv['planned_duration'] == '3h') echo 'selected'; ?>>3h</option>
                                    <option value="4h" <?php if($interv['planned_duration'] == '4h') echo 'selected'; ?>>4h</option>
                                    <option value="6h" <?php if($interv['planned_duration'] == '6h') echo 'selected'; ?>>6h</option>
                                    <option value="8h" <?php if($interv['planned_duration'] == '8h') echo 'selected'; ?>>8h</option>
                                    <option value="1j" <?php if($interv['planned_duration'] == '1j') echo 'selected'; ?>>1j</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('zone'); ?></label>
                                <input type="text" name="zone" class="form-control" value="<?php echo htmlspecialchars($interv['zone']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('localisation'); ?></label>
                                <input type="text" name="localisation" class="form-control" value="<?php echo htmlspecialchars($interv['localisation']); ?>">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                            <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>