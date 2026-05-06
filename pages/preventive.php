<?php
// pages/preventive.php - Preventive maintenance management
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== ACTION PROCESSING ==========

// Add preventive maintenance
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $last_done = $_POST['last_done'] ?: date('Y-m-d');
    $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $_POST['frequency_days'] . ' days'));
    
    $sql = "INSERT INTO preventive_maintenance (equipment_id, frequency_days, last_done, next_due, instructions, assigned_team) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $last_done,
        $next_due,
        $_POST['instructions'],
        $_POST['assigned_team']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'preventive_created', "Preventive maintenance created for equipment ID: {$_POST['equipment_id']}");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=preventive'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Edit preventive maintenance
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $next_due = date('Y-m-d', strtotime($_POST['last_done'] . ' + ' . $_POST['frequency_days'] . ' days'));
    
    $sql = "UPDATE preventive_maintenance SET 
            equipment_id = ?, 
            frequency_days = ?, 
            last_done = ?, 
            next_due = ?, 
            instructions = ?, 
            assigned_team = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $_POST['last_done'],
        $next_due,
        $_POST['instructions'],
        $_POST['assigned_team'],
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'preventive_updated', "Preventive maintenance ID: {$_GET['id']} updated");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=preventive'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Delete preventive maintenance
if($action == 'delete' && isset($_GET['id'])) {
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor') {
        if(isset($_POST['confirm_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if(password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt2 = $pdo->prepare("DELETE FROM preventive_maintenance WHERE id = ?");
                $stmt2->execute([$_GET['id']]);
                logUserAction($_SESSION['user_id'], 'preventive_deleted', "Preventive maintenance ID: {$_GET['id']} deleted");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=preventive'>";
            } else {
                $error = "❌ " . t('password_error');
            }
        }
    }
}

// Validate maintenance (mark as completed)
if($action == 'complete' && isset($_GET['id'])) {
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $pm = $stmt->fetch();
    
    if($pm) {
        $next_due = date('Y-m-d', strtotime($today . ' + ' . $pm['frequency_days'] . ' days'));
        
        $stmt2 = $pdo->prepare("UPDATE preventive_maintenance SET last_done = ?, next_due = ? WHERE id = ?");
        $stmt2->execute([$today, $next_due, $_GET['id']]);
        
        $stmt3 = $pdo->prepare("
            INSERT INTO interventions (task_number, equipment_id, type, priority, title, description, reported_by, task_type, intervention_date, task_status)
            SELECT 
                CONCAT('PREV-', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')),
                equipment_id,
                'preventive',
                'medium',
                CONCAT('Preventive maintenance - ', e.name),
                instructions,
                'system',
                'maintenance_preventive',
                DATE_ADD(CURDATE(), INTERVAL frequency_days DAY),
                'a_faire'
            FROM preventive_maintenance pm
            JOIN equipment e ON pm.equipment_id = e.id
            WHERE pm.id = ?
        ");
        $stmt3->execute([$_GET['id']]);
        
        logUserAction($_SESSION['user_id'], 'preventive_completed', "Preventive maintenance ID: {$_GET['id']} validated");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=preventive'>";
    }
}

// Fetch equipment for forms
$equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status = 'active' ORDER BY name")->fetchAll();

// Fetch preventive maintenances
$preventives = $pdo->query("
    SELECT pm.*, e.name as equipment_name, e.code as equipment_code
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    ORDER BY 
        CASE WHEN pm.next_due < CURDATE() THEN 0 ELSE 1 END,
        pm.next_due ASC
")->fetchAll();

// ========== ADD FORM ==========
if($action == 'add'):
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
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-plus-circle"></i> <?php echo t('add_maintenance'); ?>
    </div>
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
                        <span class="input-group-text"><?php echo t('days'); ?></span>
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
<?php
return;
endif;

// ========== EDIT FORM ==========
if($action == 'edit' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $pm = $stmt->fetch();
    if(!$pm) {
        echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
        return;
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
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-edit"></i> <?php echo t('edit_maintenance'); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                    <select name="equipment_id" class="form-select" required>
                        <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                        <?php foreach($equipments as $eq): ?>
                        <option value="<?php echo $eq['id']; ?>" <?php if($pm['equipment_id'] == $eq['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('frequency_days'); ?> <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="frequency_days" class="form-control" min="1" value="<?php echo $pm['frequency_days']; ?>" required>
                        <span class="input-group-text"><?php echo t('days'); ?></span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('last_done'); ?></label>
                    <input type="date" name="last_done" class="form-control" value="<?php echo $pm['last_done']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('next_due'); ?></label>
                    <input type="date" name="next_due" class="form-control" value="<?php echo $pm['next_due']; ?>" readonly>
                    <small class="text-muted">Calculated automatically</small>
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
<?php
return;
endif;

// ========== DELETE CONFIRMATION MODAL ==========
if($action == 'delete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT pm.*, e.name as equipment_name FROM preventive_maintenance pm JOIN equipment e ON pm.equipment_id = e.id WHERE pm.id = ?");
    $stmt->execute([$_GET['id']]);
    $pm = $stmt->fetch();
    if(!$pm) {
        echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <i class="fas fa-trash-alt"></i> <?php echo t('delete_maintenance'); ?>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($pm['equipment_name']); ?></strong>
        </div>
        <p><?php echo t('delete_warning'); ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .card-header-custom {
        padding: 12px 20px;
        font-weight: bold;
        color: white;
    }
    .card-header-custom.overdue { background: linear-gradient(135deg, #dc3545, #c82333); }
    .card-header-custom.upcoming { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; }
    .card-header-custom.ok { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-overdue { background: #dc3545; color: white; }
    .status-upcoming { background: #ffc107; color: #333; }
    .status-ok { background: #28a745; color: white; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-calendar-alt"></i> <?php echo t('preventive_maintenance'); ?></h2>
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
    <a href="?page=preventive&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo t('plan_maintenance'); ?>
    </a>
    <?php endif; ?>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics summary -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="info-card text-center">
            <div class="card-body">
                <h3 class="text-danger"><?php 
                    $overdue_count = 0;
                    foreach($preventives as $p) {
                        if(strtotime($p['next_due']) < time()) $overdue_count++;
                    }
                    echo $overdue_count;
                ?></h3>
                <p class="text-muted mb-0"><?php echo t('overdue'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-card text-center">
            <div class="card-body">
                <h3 class="text-warning"><?php 
                    $upcoming_count = 0;
                    foreach($preventives as $p) {
                        $days = (strtotime($p['next_due']) - time()) / 86400;
                        if(strtotime($p['next_due']) >= time() && $days <= 30) $upcoming_count++;
                    }
                    echo $upcoming_count;
                ?></h3>
                <p class="text-muted mb-0"><?php echo t('upcoming'); ?> (30 <?php echo t('days'); ?>)</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-card text-center">
            <div class="card-body">
                <h3 class="text-success"><?php 
                    $ok_count = 0;
                    foreach($preventives as $p) {
                        $days = (strtotime($p['next_due']) - time()) / 86400;
                        if(strtotime($p['next_due']) >= time() && $days > 30) $ok_count++;
                    }
                    echo $ok_count;
                ?></h3>
                <p class="text-muted mb-0">Maintenances OK</p>
            </div>
        </div>
    </div>
</div>

<!-- Preventive maintenance list -->
<div class="info-card">
    <div class="card-header-custom" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
        <i class="fas fa-list"></i> <?php echo t('maintenance_list'); ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?php echo t('equipment'); ?></th>
                        <th><?php echo t('code'); ?></th>
                        <th><?php echo t('frequency'); ?></th>
                        <th><?php echo t('last_done'); ?></th>
                        <th><?php echo t('next_due'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('team'); ?></th>
                        <th class="text-center"><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($preventives as $pm): 
                        $days_diff = (strtotime($pm['next_due']) - time()) / 86400;
                        $status_class = '';
                        $status_text = '';
                        
                        if(strtotime($pm['next_due']) < time()) {
                            $status_class = 'status-overdue';
                            $status_text = '🔴 ' . t('overdue');
                        } elseif($days_diff <= 30) {
                            $status_class = 'status-upcoming';
                            $status_text = '🟡 ' . t('upcoming') . ' (< 30d)';
                        } else {
                            $status_class = 'status-ok';
                            $status_text = '🟢 OK';
                        }
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($pm['equipment_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pm['equipment_code']); ?></td>
                        <td><?php echo t('every'); ?> <?php echo $pm['frequency_days']; ?> <?php echo t('days'); ?><br>
                            <small class="text-muted">(<?php echo round($pm['frequency_days'] / 30, 1); ?> <?php echo t('months'); ?>)</small>
                        </td>
                        <td><?php echo $pm['last_done'] ? date('m/d/Y', strtotime($pm['last_done'])) : t('never'); ?></td>
                        <td>
                            <?php echo date('m/d/Y', strtotime($pm['next_due'])); ?>
                            <?php if(strtotime($pm['next_due']) < time()): ?>
                                <br><small class="text-danger"><?php echo t('overdue_by'); ?> <?php echo abs(round($days_diff)); ?> <?php echo t('days'); ?></small>
                            <?php elseif($days_diff <= 30): ?>
                                <br><small class="text-warning"><?php echo t('in'); ?> <?php echo round($days_diff); ?> <?php echo t('days'); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                        <td><?php echo htmlspecialchars($pm['assigned_team'] ?: t('unassigned')); ?></td>
                        <td class="text-center action-buttons">
                            <a href="?page=preventive&action=complete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('validate'); ?>" onclick="return confirm('<?php echo t('validate_confirm'); ?>')">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                            <a href="?page=preventive&action=edit&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?page=preventive&action=delete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" onclick="return confirm('<?php echo t('delete_confirm'); ?>')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="info-card">
            <div class="card-body">
                <div class="d-flex justify-content-center gap-4">
                    <div><span class="status-badge status-overdue">🔴 <?php echo t('overdue'); ?></span> <small>Maintenance to perform immediately</small></div>
                    <div><span class="status-badge status-upcoming">🟡 <?php echo t('upcoming'); ?> (< 30d)</span> <small>Maintenance to schedule soon</small></div>
                    <div><span class="status-badge status-ok">🟢 OK</span> <small>Maintenance on schedule</small></div>
                </div>
            </div>
        </div>
    </div>
</div>