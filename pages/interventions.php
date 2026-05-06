<?php
// pages/interventions.php - Full interventions management (CRUD)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== ACTION PROCESSING ==========

// Quick status change
if($action == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $stmt = $pdo->prepare("UPDATE interventions SET task_status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['id']]);
    logUserAction($_SESSION['user_id'], 'intervention_status_change', "Status changed for ID: {$_GET['id']} to {$_GET['status']}");
    $message = "✅ " . t('status_updated');
    echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
}

// Assign a technician
if($action == 'assign' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("UPDATE interventions SET intervenant_id = ? WHERE id = ?");
    $stmt->execute([$_POST['technician_id'], $_GET['id']]);
    logUserAction($_SESSION['user_id'], 'intervention_assigned', "Technician assigned to ID: {$_GET['id']}");
    $message = "✅ " . t('technician_assigned');
    echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
}

// Complete an intervention with report
if($action == 'complete' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $pdo->prepare("
        UPDATE interventions 
        SET task_status = 'termine', 
            completed_date = NOW(), 
            completion_report = ?,
            duration_hours = COALESCE(?, duration_hours)
        WHERE id = ?
    ");
    $stmt->execute([$_POST['completion_report'], $_POST['duration_hours'], $_GET['id']]);
    logUserAction($_SESSION['user_id'], 'intervention_completed', "Intervention completed ID: {$_GET['id']}");
    $message = "✅ " . t('intervention_completed');
    echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
}

// Delete (soft delete - cancellation) with password validation
if($action == 'delete' && isset($_GET['id'])) {
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor') {
        if(isset($_POST['confirm_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if(password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt2 = $pdo->prepare("UPDATE interventions SET status = 'cancelled', task_status = 'cloturee' WHERE id = ?");
                $stmt2->execute([$_GET['id']]);
                logUserAction($_SESSION['user_id'], 'intervention_deleted', "Intervention ID: {$_GET['id']} cancelled");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
            } else {
                $error = "❌ " . t('password_error');
            }
        }
    }
}

// Edit an intervention
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
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
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_updated', "Intervention ID: {$_GET['id']} updated");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Fetch technicians list
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Fetch interventions with all details
$interventions = $pdo->query("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location,
           t.id as technician_id, t.firstname, t.lastname, t.specialty
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    ORDER BY 
        CASE i.task_status 
            WHEN 'a_faire' THEN 1
            WHEN 'en_cours' THEN 2
            WHEN 'termine' THEN 3
            WHEN 'cloturee' THEN 4
            ELSE 5
        END,
        i.intervention_date ASC,
        i.created_at DESC
")->fetchAll();

// Intervention statistics
$total = count($interventions);
$a_faire = count(array_filter($interventions, function($i) { return $i['task_status'] == 'a_faire'; }));
$en_cours = count(array_filter($interventions, function($i) { return $i['task_status'] == 'en_cours'; }));
$termine = count(array_filter($interventions, function($i) { return $i['task_status'] == 'termine'; }));
$cloturee = count(array_filter($interventions, function($i) { return $i['task_status'] == 'cloturee'; }));

// Fetch modifications history for each intervention
$history = [];
foreach($interventions as $inv) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('intervention_created', 'intervention_updated', 'intervention_status_change', 
                         'intervention_assigned', 'intervention_completed', 'intervention_deleted')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$inv['id']}%"]);
    $history[$inv['id']] = $stmt->fetchAll();
}

// ========== ASSIGNMENT MODAL ==========
if($action == 'assign' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $interv = $stmt->fetch();
    if(!$interv) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
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
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-user-plus"></i> <?php echo t('assign_technician'); ?>
    </div>
    <div class="card-body p-4">
        <p><strong><?php echo t('title'); ?> :</strong> <?php echo htmlspecialchars($interv['title']); ?></p>
        <p><strong><?php echo t('task_number'); ?> :</strong> <?php echo htmlspecialchars($interv['task_number'] ?? 'N/A'); ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo t('technician'); ?></label>
                <select name="technician_id" class="form-select" required>
                    <option value="">-- <?php echo t('select_technician'); ?> --</option>
                    <?php foreach($technicians as $tech): ?>
                    <option value="<?php echo $tech['id']; ?>" <?php if($interv['intervenant_id'] == $tech['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> <?php echo t('assign'); ?></button>
                <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== COMPLETION MODAL ==========
if($action == 'complete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT i.*, e.name as equipment_name FROM interventions i JOIN equipment e ON i.equipment_id = e.id WHERE i.id = ?");
    $stmt->execute([$_GET['id']]);
    $interv = $stmt->fetch();
    if(!$interv) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
        <i class="fas fa-check-circle"></i> <?php echo t('complete_intervention'); ?>
    </div>
    <div class="card-body p-4">
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
<?php
return;
endif;

// ========== DELETE CONFIRMATION MODAL ==========
if($action == 'delete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $interv = $stmt->fetch();
    if(!$interv) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <i class="fas fa-trash-alt"></i> <?php echo t('cancel_intervention'); ?>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($interv['title']); ?></strong>
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
<?php
return;
endif;

// ========== EDIT FORM ==========
if($action == 'edit' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT i.*, e.name as equipment_name FROM interventions i JOIN equipment e ON i.equipment_id = e.id WHERE i.id = ?");
    $stmt->execute([$_GET['id']]);
    $interv = $stmt->fetch();
    if(!$interv) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
    
    $equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status = 'active' ORDER BY name")->fetchAll();
    $intervenants = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
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
        <i class="fas fa-edit"></i> <?php echo t('edit_intervention'); ?> : <?php echo htmlspecialchars($interv['task_number'] ?? 'N/A'); ?>
    </div>
    <div class="card-body p-4">
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
<?php
return;
endif;
?>

<style>
    .stats-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
        margin-bottom: 15px;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .priority-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .priority-critical { background: #dc3545; color: white; }
    .priority-high { background: #fd7e14; color: white; }
    .priority-medium { background: #ffc107; color: #333; }
    .priority-low { background: #28a745; color: white; }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-a_faire { background: #6c757d; color: white; }
    .status-en_cours { background: #17a2b8; color: white; }
    .status-termine { background: #28a745; color: white; }
    .status-cloturee { background: #343a40; color: white; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .intervention-table {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .intervention-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
    }
    .intervention-table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    .intervention-table tr:hover {
        background: #f8f9fa;
    }
    .history-item {
        padding: 5px 0;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tools"></i> <?php echo t('interventions'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'technician'): ?>
        <a href="?page=intervention_add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
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
    
    <!-- Statistics cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('all')">
                <div class="stats-number text-primary"><?php echo $total; ?></div>
                <div class="text-muted"><?php echo t('total'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('a_faire')">
                <div class="stats-number text-secondary"><?php echo $a_faire; ?></div>
                <div class="text-muted"><?php echo t('to_do'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('en_cours')">
                <div class="stats-number text-info"><?php echo $en_cours; ?></div>
                <div class="text-muted"><?php echo t('in_progress'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('termine')">
                <div class="stats-number text-success"><?php echo $termine; ?></div>
                <div class="text-muted"><?php echo t('completed'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick filters -->
    <div class="filter-bar">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="btn-group" role="group">
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('all')"><?php echo t('all'); ?></button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('a_faire')"><?php echo t('to_do'); ?></button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('en_cours')"><?php echo t('in_progress'); ?></button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('termine')"><?php echo t('completed'); ?></button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('cloturee')"><?php echo t('closed'); ?></button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <small class="text-muted">
                    <i class="fas fa-chart-simple"></i> <?php echo t('total'); ?>: <?php echo $total; ?> <?php echo t('interventions'); ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Interventions list -->
    <div class="intervention-table">
        <div class="table-responsive">
            <table class="table mb-0" id="interventionsTable">
                <thead>
                    <tr>
                        <th><?php echo t('task_number'); ?></th>
                        <th><?php echo t('equipment'); ?></th>
                        <th><?php echo t('title'); ?></th>
                        <th><?php echo t('priority'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('technician'); ?></th>
                        <th><?php echo t('planned_date'); ?></th>
                        <th><?php echo t('last_modifications'); ?></th>
                        <th class="text-center"><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($interventions as $inv): ?>
                    <tr data-status="<?php echo $inv['task_status']; ?>">
                        <td>
                            <strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong>
                            <?php if($inv['completion_report']): ?>
                                <i class="fas fa-file-alt text-muted ms-1" title="<?php echo t('report'); ?>"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($inv['equipment_name']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($inv['equipment_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($inv['title']); ?></td>
                        <td>
                            <span class="priority-badge priority-<?php echo $inv['priority']; ?>">
                                <?php echo t($inv['priority']); ?>
                            </span>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" style="width: 120px;" 
                                    onchange="if(confirm('<?php echo t('status_confirm'); ?>')) window.location.href='?page=interventions&action=change_status&id=<?php echo $inv['id']; ?>&status='+this.value"
                                    onclick="event.stopPropagation()">
                                <option value="a_faire" <?php if($inv['task_status'] == 'a_faire') echo 'selected'; ?>><?php echo t('to_do'); ?></option>
                                <option value="en_cours" <?php if($inv['task_status'] == 'en_cours') echo 'selected'; ?>><?php echo t('in_progress'); ?></option>
                                <option value="termine" <?php if($inv['task_status'] == 'termine') echo 'selected'; ?>><?php echo t('completed'); ?></option>
                                <option value="cloturee" <?php if($inv['task_status'] == 'cloturee') echo 'selected'; ?>><?php echo t('closed'); ?></option>
                            </select>
                        </td>
                        <td>
                            <?php if($inv['firstname']): ?>
                                <?php echo htmlspecialchars($inv['firstname'] . ' ' . $inv['lastname']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($inv['specialty']); ?></small>
                            <?php else: ?>
                                <span class="text-muted"><?php echo t('unassigned'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $inv['intervention_date'] ? date('m/d/Y', strtotime($inv['intervention_date'])) : '-'; ?>
                        </td>
                        <td style="max-width: 150px;">
                            <?php if(!empty($history[$inv['id']])): ?>
                                <?php foreach(array_slice($history[$inv['id']], 0, 2) as $h): ?>
                                <div class="history-item">
                                    <?php
                                    $action_icons = [
                                        'intervention_created' => '🟢 ' . t('created'),
                                        'intervention_updated' => '✏️ ' . t('modified'),
                                        'intervention_status_change' => '📊 ' . t('status_changed'),
                                        'intervention_assigned' => '👤 ' . t('assigned'),
                                        'intervention_completed' => '✅ ' . t('completed'),
                                        'intervention_deleted' => '🗑️ ' . t('cancelled')
                                    ];
                                    echo isset($action_icons[$h['action']]) ? $action_icons[$h['action']] : $h['action'];
                                    ?>
                                    <br><small class="text-muted"><?php echo date('m/d/Y H:i', strtotime($h['created_at'])); ?></small>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center action-buttons" onclick="event.stopPropagation()">
                            <a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if($inv['task_status'] != 'termine' && $inv['task_status'] != 'cloturee'): ?>
                                <a href="?page=interventions&action=complete&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('complete'); ?>">
                                    <i class="fas fa-check"></i>
                                </a>
                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=interventions&action=assign&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('assign'); ?>">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                    <a href="?page=interventions&action=edit&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=interventions&action=delete&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('cancel'); ?>" onclick="return confirm('<?php echo t('delete_confirm'); ?>')">

                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-lock"></i></span>
                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=interventions&action=edit&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterByStatus(status) {
    const rows = document.querySelectorAll('#interventionsTable tbody tr');
    rows.forEach(row => {
        if(status === 'all') {
            row.style.display = '';
        } else if(row.getAttribute('data-status') === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>