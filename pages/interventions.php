<?php
// pages/interventions.php - Liste principale des interventions
// auth handled centrally in index.php

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Rediriger vers les pages spécifiques pour les actions
if($action == 'assign' && isset($_GET['id'])) {
    header('Location: ?page=interventions_assign&id=' . intval($_GET['id']));
    exit();
}
if($action == 'complete' && isset($_GET['id'])) {
    header('Location: ?page=interventions_complete&id=' . intval($_GET['id']));
    exit();
}
if($action == 'edit' && isset($_GET['id'])) {
    header('Location: ?page=interventions_edit&id=' . intval($_GET['id']));
    exit();
}
if($action == 'delete' && isset($_GET['id'])) {
    header('Location: ?page=interventions_delete&id=' . intval($_GET['id']));
    exit();
}

// Quick status change (inline sans formulaire)
if($action == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $stmt = $pdo->prepare("UPDATE interventions SET task_status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['id']]);
    logUserAction($_SESSION['user_id'], 'intervention_status_change', "Status changed for ID: {$_GET['id']} to {$_GET['status']}");
    $message = "✅ " . t('status_updated');
    echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
}

// Fetch technicians list (pour les besoins de l'affichage)
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Fetch interventions with all details - INCLUT L'ÉQUIPE
$interventions = $pdo->query("
    SELECT i.*,
            e.name as equipment_name,
            e.code as equipment_code,
            e.location as equipment_location,
            t.id as technician_id,
            t.firstname, t.lastname, t.specialty,
            team.name as team_name
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.technician_id = t.id
    LEFT JOIN teams team ON i.team_id = team.id
    ORDER BY 
        CASE i.task_status 
            WHEN 'a_faire' THEN 1
            WHEN 'en_cours' THEN 2
            WHEN 'termine' THEN 3
            WHEN 'cloturee' THEN 4
            ELSE 5
        END,
        COALESCE(i.intervention_date, i.created_at) ASC,
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
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
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
    
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        max-width: 105px;
        margin: 0 auto;
    }
    .action-buttons .btn, .action-buttons .disabled-icon {
        padding: 5px;
        margin: 0;
        border-radius: 6px;
        font-size: 12px;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        flex: 0 0 30px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .table {
        min-width: 1000px;
    }
    .table-dark th {
        background: #212529;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        vertical-align: middle;
    }
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    .table tr:hover {
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
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover {
        filter: brightness(0.95);
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
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 6px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 6px;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 6px;
    }
    .form-select-sm {
        font-size: 12px;
        padding: 4px 8px;
    }
    .text-muted {
        color: #6c757d !important;
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
                <div class="stats-number" style="color: #667eea;"><?php echo $total; ?></div>
                <div class="text-muted"><?php echo t('total'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('a_faire')">
                <div class="stats-number" style="color: #6c757d;"><?php echo $a_faire; ?></div>
                <div class="text-muted"><?php echo t('to_do'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('en_cours')">
                <div class="stats-number" style="color: #17a2b8;"><?php echo $en_cours; ?></div>
                <div class="text-muted"><?php echo t('in_progress'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterByStatus('termine')">
                <div class="stats-number" style="color: #28a745;"><?php echo $termine; ?></div>
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
    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-list"></i> <?php echo t('intervention_list'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="interventionsTable">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('task_number'); ?></th>
                            <th><?php echo t('equipment'); ?></th>
                            <th><?php echo t('title'); ?></th>
                            <th><?php echo t('priority'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('technician'); ?><br><?php echo t('team'); ?></th>
                            <th><?php echo t('planned_date'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center" style="width: 120px;"><?php echo t('actions'); ?></th>
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
                            <!-- Colonne Technicien / Équipe -->
                            <td>
                                <?php 
                                $hasTeam = !empty($inv['team_name']);
                                $hasTech = !empty($inv['firstname']);
                                
                                if($hasTeam && $hasTech) {
                                    echo '<span class="badge bg-info">' . htmlspecialchars($inv['team_name']) . '</span><br>';
                                    echo '<small>' . htmlspecialchars($inv['firstname'] . ' ' . $inv['lastname']) . '</small>';
                                } elseif($hasTeam) {
                                    echo '<span class="badge bg-info">' . htmlspecialchars($inv['team_name']) . '</span>';
                                    echo '<br><small class="text-muted">' . t('team_assigned') . '</small>';
                                } elseif($hasTech) {
                                    echo htmlspecialchars($inv['firstname'] . ' ' . $inv['lastname']);
                                    echo '<br><small class="text-muted">' . htmlspecialchars($inv['specialty']) . '</small>';
                                } else {
                                    echo '<span class="text-muted">' . t('unassigned') . '</span>';
                                }
                                ?>
                            </td>
                            <!-- Date prévue - CORRECTION : utilisation de format_date_local -->
                            <td>
                                <?php echo $inv['intervention_date'] ? format_date_local($inv['intervention_date'], 'short', false) : '-'; ?>
                            </td>
                            <td style="max-width: 150px;">
                                <?php if(!empty($history[$inv['id']])): ?>
                                    <?php foreach(array_slice($history[$inv['id']], 0, 1) as $h): ?>
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
                                        <br><small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
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
                                    <a href="?page=interventions_complete&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('complete'); ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                        <a href="?page=interventions_assign&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('assign'); ?>">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                        <a href="?page=interventions_edit&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="?page=interventions_delete&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('cancel'); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="disabled-icon"><i class="fas fa-lock"></i></span>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                        <a href="?page=interventions_edit&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                            <i class="fas fa-pen"></i>
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