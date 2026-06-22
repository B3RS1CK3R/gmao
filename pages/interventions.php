<?php
// pages/interventions.php - Liste principale des interventions
// auth handled centrally in index.php

$action = $_GET['action'] ?? 'list';
$active_filter = isset($_GET['filter']) ? $_GET['filter'] : 'a_faire';

// ========== CORRECTION AUTOMATIQUE DES STATUTS ==========
try {
    $check = $pdo->query("SHOW COLUMNS FROM interventions LIKE 'task_status'");
    if ($check->rowCount() > 0) {
        $pdo->exec("ALTER TABLE interventions MODIFY task_status ENUM('a_faire', 'en_cours', 'termine', 'cloturee', 'cancelled') DEFAULT 'a_faire'");
        $pdo->exec("UPDATE interventions SET task_status = 'cancelled' WHERE task_status NOT IN ('a_faire', 'en_cours', 'termine', 'cloturee') OR task_status IS NULL OR task_status = ''");
    }
} catch (PDOException $e) {}

// ========== REDIRECTIONS ==========
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

// Quick status change
if($action == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $stmt = $pdo->prepare("UPDATE interventions SET task_status = ? WHERE id = ?");
    $stmt->execute([$_GET['status'], $_GET['id']]);
    logUserAction($_SESSION['user_id'], 'intervention_status_change', "Status changed for ID: {$_GET['id']} to {$_GET['status']}");
    $message = "✅ " . t('status_updated');
    echo "<meta http-equiv='refresh' content='1;url=?page=interventions&filter=" . $_GET['status'] . "'>";
}

// Fetch all interventions
$all_interventions = $pdo->query("
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
            WHEN 'cancelled' THEN 5
            WHEN 'a_faire' THEN 1
            WHEN 'en_cours' THEN 2
            WHEN 'termine' THEN 3
            WHEN 'cloturee' THEN 4
            ELSE 6
        END,
        COALESCE(i.intervention_date, i.created_at) ASC,
        i.created_at DESC
")->fetchAll();

// Statistiques
$stats = [
    'all' => count($all_interventions),
    'a_faire' => 0,
    'en_cours' => 0,
    'termine' => 0,
    'cloturee' => 0,
    'cancelled' => 0
];
foreach($all_interventions as $inv) {
    if (isset($stats[$inv['task_status']])) {
        $stats[$inv['task_status']]++;
    } else {
        $stats['a_faire']++;
        $pdo->prepare("UPDATE interventions SET task_status = 'a_faire' WHERE id = ?")->execute([$inv['id']]);
    }
}

// Filtrer les interventions
$interventions = array_filter($all_interventions, function($inv) use ($active_filter) {
    if ($active_filter == 'all') return true;
    return $inv['task_status'] == $active_filter;
});

// Définir les libellés des filtres
$filter_labels = [
    'all' => t('all'),
    'a_faire' => t('to_do'),
    'en_cours' => t('in_progress'),
    'termine' => t('completed'),
    'cloturee' => t('closed'),
    'cancelled' => t('cancelled')
];

$filter_icons = [
    'all' => '',
    'a_faire' => '⚪',
    'en_cours' => '🔵',
    'termine' => '🟢',
    'cloturee' => '⚫',
    'cancelled' => '🔴'
];

$filter_colors = [
    'all' => '#667eea',
    'a_faire' => '#6c757d',
    'en_cours' => '#17a2b8',
    'termine' => '#28a745',
    'cloturee' => '#343a40',
    'cancelled' => '#dc3545'
];

// Historique
$history = [];
foreach($all_interventions as $inv) {
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
    .card-header-custom i {
        margin-right: 8px;
    }
    
    /* Cartes de statistiques */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.2s;
        cursor: pointer;
        border: 3px solid transparent;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-card.active {
        border-color: #667eea;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
    .stats-label {
        font-size: 13px;
        color: #6c757d;
        margin-top: 4px;
    }
    
    /* Badges */
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
    .status-cancelled { background: #dc3545; color: white; }
    
    /* Actions */
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        max-width: 105px;
        margin: 0 auto;
    }
    .action-buttons .btn {
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
    
    /* Tableau */
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
    
    /* Historique */
    .history-item {
        padding: 5px 0;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    
    /* Boutons */
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
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
    }
    .btn-info:hover {
        background: #138496;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 6px;
    }
    .btn-success:hover {
        background: #218838;
    }
    .form-select-sm {
        font-size: 12px;
        padding: 4px 8px;
    }
    .text-muted {
        color: #6c757d !important;
    }
    
    /* Légende */
    .legend-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 15px;
        text-align: center;
    }
    .legend-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .legend-item:hover {
        transform: translateY(-2px);
        background: #e9ecef;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        .legend-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 480px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .legend-grid {
            grid-template-columns: repeat(2, 1fr);
        }
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
    
    <?php if(isset($_GET['msg']) || !empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['msg'] ?? $message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($_GET['err']) || !empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['err'] ?? $error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    
    <!-- Statistiques (filtres) -->
    <div class="stats-grid">
        <?php foreach($stats as $key => $value): ?>
        <div class="stats-card <?php echo ($active_filter == $key) ? 'active' : ''; ?>" 
             onclick="window.location.href='?page=interventions&filter=<?php echo $key; ?>'">
            <div class="stats-number" style="color: <?php echo $filter_colors[$key] ?? '#667eea'; ?>;"><?php echo $value; ?></div>
            <div class="stats-label"><?php echo $filter_icons[$key] ?? ''; ?> <?php echo $filter_labels[$key] ?? $key; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Liste -->
    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-list"></i> <?php echo t('intervention_list'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
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
                        <tr>
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
                                <?php if($inv['task_status'] == 'cancelled'): ?>
                                    <span class="status-badge status-cancelled">🔴 <?php echo t('cancelled'); ?></span>
                                <?php else: ?>
                                <select class="form-select form-select-sm" style="width: 120px;" 
                                        onchange="if(confirm('<?php echo t('status_confirm'); ?>')) window.location.href='?page=interventions&action=change_status&id=<?php echo $inv['id']; ?>&status='+this.value"
                                        onclick="event.stopPropagation()">
                                    <option value="a_faire" <?php if($inv['task_status'] == 'a_faire') echo 'selected'; ?>><?php echo t('to_do'); ?></option>
                                    <option value="en_cours" <?php if($inv['task_status'] == 'en_cours') echo 'selected'; ?>><?php echo t('in_progress'); ?></option>
                                    <option value="termine" <?php if($inv['task_status'] == 'termine') echo 'selected'; ?>><?php echo t('completed'); ?></option>
                                    <option value="cloturee" <?php if($inv['task_status'] == 'cloturee') echo 'selected'; ?>><?php echo t('closed'); ?></option>
                                </select>
                                <?php endif; ?>
                            </td>
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
                                <?php if($inv['task_status'] != 'cancelled'): ?>
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
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-ban"></i> <?php echo t('cancelled'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($interventions) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                <?php echo t('no_interventions'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="info-card">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #6c757d, #495057);">
                    <i class="fas fa-info-circle"></i> <?php echo t('legend'); ?>
                </div>
                <div class="card-body p-3">
                    <div class="legend-grid">
                        <div class="legend-item">
                            <span class="status-badge status-a_faire">⚪ <?php echo t('to_do'); ?></span>
                            <small><?php echo t('to_do_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-en_cours">🔵 <?php echo t('in_progress'); ?></span>
                            <small><?php echo t('in_progress_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-termine">🟢 <?php echo t('completed'); ?></span>
                            <small><?php echo t('completed_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-cloturee">⚫ <?php echo t('closed'); ?></span>
                            <small><?php echo t('closed_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-cancelled">🔴 <?php echo t('cancelled'); ?></span>
                            <small><?php echo t('cancelled_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="badge bg-secondary">📊</span>
                            <small><?php echo t('click_stats_to_filter'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>