<?php
// pages/preventive.php - Liste des maintenances préventives (avec prestataires)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$active_filter = isset($_GET['filter']) ? $_GET['filter'] : 'upcoming';

// Redirections
if($action == 'add') { header('Location: ?page=preventive_add'); exit(); }
if($action == 'edit' && isset($_GET['id'])) { header('Location: ?page=preventive_edit&id=' . intval($_GET['id'])); exit(); }
if($action == 'delete' && isset($_GET['id'])) { header('Location: ?page=preventive_delete&id=' . intval($_GET['id'])); exit(); }
if($action == 'complete' && isset($_GET['id'])) { header('Location: ?page=preventive_complete&id=' . intval($_GET['id'])); exit(); }

// Récupération de toutes les maintenances avec prestataires
$all_preventives = $pdo->query("
    SELECT pm.*, 
            e.name as equipment_name, 
            e.code as equipment_code,
            t.id as technician_id,
            t.firstname, t.lastname, t.specialty,
            team.name as team_name,
            c.id as contractor_id,
            c.company_name as contractor_name
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    LEFT JOIN technicians t ON pm.technician_id = t.id
    LEFT JOIN teams team ON pm.team_id = team.id
    LEFT JOIN contractors c ON pm.contractor_id = c.id
    ORDER BY 
        CASE WHEN pm.next_due < CURDATE() THEN 0 ELSE 1 END,
        pm.next_due ASC
")->fetchAll();

// Statistiques
$stats = [
    'all' => count($all_preventives),
    'overdue' => 0,
    'upcoming' => 0,
    'ok' => 0,
    'cancelled' => 0
];
foreach($all_preventives as $p) {
    if($p['task_status'] == 'cancelled') {
        $stats['cancelled']++;
    } else {
        if(strtotime($p['next_due']) < time()) {
            $stats['overdue']++;
        } else {
            $days = (strtotime($p['next_due']) - time()) / 86400;
            if($days <= 30) $stats['upcoming']++;
            else $stats['ok']++;
        }
    }
}

// Filtrer
$preventives = array_filter($all_preventives, function($p) use ($active_filter) {
    if ($active_filter == 'all') return true;
    if ($active_filter == 'cancelled') return $p['task_status'] == 'cancelled';
    
    if ($active_filter == 'overdue') {
        return $p['task_status'] != 'cancelled' && strtotime($p['next_due']) < time();
    }
    if ($active_filter == 'upcoming') {
        if ($p['task_status'] == 'cancelled') return false;
        $days = (strtotime($p['next_due']) - time()) / 86400;
        return $days <= 30 && $days >= 0;
    }
    if ($active_filter == 'ok') {
        if ($p['task_status'] == 'cancelled') return false;
        $days = (strtotime($p['next_due']) - time()) / 86400;
        return $days > 30;
    }
    return true;
});

// Libellés des filtres
$filter_labels = [
    'all' => t('all'),
    'overdue' => t('overdue'),
    'upcoming' => t('upcoming'),
    'ok' => t('ok'),
    'cancelled' => t('cancelled')
];
$filter_icons = [
    'all' => '',
    'overdue' => '🔴',
    'upcoming' => '🟡',
    'ok' => '🟢',
    'cancelled' => '⚫'
];
$filter_colors = [
    'all' => '#667eea',
    'overdue' => '#dc3545',
    'upcoming' => '#ffc107',
    'ok' => '#28a745',
    'cancelled' => '#6c757d'
];

// Historique
$history = [];
foreach($all_preventives as $pm) {
    $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE action IN ('preventive_created', 'preventive_updated', 'preventive_status_change', 'preventive_assigned', 'preventive_completed', 'preventive_deleted') AND details LIKE ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute(["%ID: {$pm['id']}%"]);
    $history[$pm['id']] = $stmt->fetchAll();
}

// Contractor badge style
$contractor_badge_style = 'background: #6f42c1; color: white; padding: 2px 10px; border-radius: 12px; font-size: 11px;';
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
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
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
    
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .contractor-badge {
        background: #6f42c1;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
    }
    .status-overdue {
        background: #dc3545;
        color: white;
    }
    .status-upcoming {
        background: #ffc107;
        color: #333;
    }
    .status-ok {
        background: #28a745;
        color: white;
    }
    .status-cancelled {
        background: #6c757d;
        color: white;
    }
    
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
    
    .table-responsive { overflow-x: auto; }
    .table { min-width: 1000px; }
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
    .table tr:hover { background: #f8f9fa; }
    
    .history-item {
        padding: 5px 0;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child { border-bottom: none; }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover { filter: brightness(0.95); }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary:hover { background: #5a6268; }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 6px;
        color: white;
    }
    .btn-warning:hover { background: #e06a0a; color: white; }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 6px;
    }
    .btn-danger:hover { background: #c82333; }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
    }
    .btn-info:hover { background: #138496; }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 6px;
    }
    .btn-success:hover { background: #218838; }
    .form-select-sm {
        font-size: 12px;
        padding: 4px 8px;
    }
    .text-muted { color: #6c757d !important; }
    
    .legend-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
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
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
        .legend-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .legend-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt"></i> <?php echo t('preventive_maintenance'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=preventive_add" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo t('plan_maintenance'); ?></a>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['err']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Statistiques (filtres) -->
    <div class="stats-grid">
        <?php foreach($stats as $key => $value): ?>
        <div class="stats-card <?php echo ($active_filter == $key) ? 'active' : ''; ?>" 
            onclick="window.location.href='?page=preventive&filter=<?php echo $key; ?>'">
            <div class="stats-number" style="color: <?php echo $filter_colors[$key] ?? '#667eea'; ?>;"><?php echo $value; ?></div>
            <div class="stats-label"><?php echo $filter_icons[$key] ?? ''; ?> <?php echo $filter_labels[$key] ?? $key; ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Liste -->
    <div class="info-card">
        <div class="card-header-custom"><i class="fas fa-list"></i> <?php echo t('preventive_maintenance_list'); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('task_number'); ?></th>
                            <th><?php echo t('equipment'); ?></th>
                            <th><?php echo t('frequency'); ?></th>
                            <th><?php echo t('last_done'); ?></th>
                            <th><?php echo t('next_due'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('assigned_to'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($preventives as $pm): 
                            $days_diff = (strtotime($pm['next_due']) - time()) / 86400;
                            $is_cancelled = ($pm['task_status'] == 'cancelled');
                            $hasTeam = !empty($pm['team_name']);
                            $hasTech = !empty($pm['firstname']) && !empty($pm['lastname']);
                            $hasContractor = !empty($pm['contractor_id']);
                            
                            if($is_cancelled) {
                                $status_class = 'status-cancelled';
                                $status_text = '⚫ ' . t('cancelled');
                            } elseif(strtotime($pm['next_due']) < time()) {
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
                            <td><strong><?php echo htmlspecialchars($pm['task_number'] ?? 'N/A'); ?></strong></td>
                            <td>
                                <?php echo htmlspecialchars($pm['equipment_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($pm['equipment_code']); ?></small>
                            </td>
                            <td>
                                <?php echo t('every'); ?> <?php echo $pm['frequency_days']; ?> <?php echo t('days_s'); ?><br>
                                <small class="text-muted">(<?php echo round($pm['frequency_days'] / 30, 1); ?> <?php echo t('month_s'); ?>)</small>
                            </td>
                            <td><?php echo $pm['last_done'] ? format_date_local($pm['last_done'], 'short', false) : t('never'); ?></td>
                            <td>
                                <?php echo format_date_local($pm['next_due'], 'short', false); ?>
                                <?php if(!$is_cancelled): ?>
                                    <?php if(strtotime($pm['next_due']) < time()): ?><br><small class="text-danger"><?php echo t('overdue_by'); ?> <?php echo abs(round($days_diff)); ?> <?php echo t('days'); ?></small>
                                    <?php elseif($days_diff <= 30): ?><br><small class="text-warning"><?php echo t('in'); ?> <?php echo round($days_diff); ?> <?php echo t('days_s'); ?></small><?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td>
                                <?php 
                                if ($hasTeam && $hasTech) {
                                    echo '<span class="badge bg-info">' . htmlspecialchars($pm['team_name']) . '</span><br>';
                                    echo '<small>' . htmlspecialchars($pm['firstname'] . ' ' . $pm['lastname']) . '</small>';
                                } elseif ($hasTeam) {
                                    echo '<span class="badge bg-info">' . htmlspecialchars($pm['team_name']) . '</span>';
                                    echo '<br><small class="text-muted">' . t('team_assigned') . '</small>';
                                } elseif ($hasContractor) {
                                    echo '<span class="badge contractor-badge">🏢 ' . htmlspecialchars($pm['contractor_name']) . '</span>';
                                    echo '<br><small class="text-muted">' . t('contractor_assigned') . '</small>';
                                } elseif ($hasTech) {
                                    echo htmlspecialchars($pm['firstname'] . ' ' . $pm['lastname']);
                                    if($pm['specialty']) echo '<br><small class="text-muted">' . htmlspecialchars($pm['specialty']) . '</small>';
                                } else {
                                    echo '<span class="text-muted">' . t('unassigned') . '</span>';
                                }
                                ?>
                            </td>
                            <td style="max-width: 150px;">
                                <?php if(!empty($history[$pm['id']])): ?>
                                    <?php foreach(array_slice($history[$pm['id']], 0, 1) as $h): ?>
                                    <div class="history-item">
                                        <?php
                                        $action_icons = [
                                            'preventive_created' => '🟢 ' . t('created'),
                                            'preventive_updated' => '✏️ ' . t('modified'),
                                            'preventive_status_change' => '📊 ' . t('status_changed'),
                                            'preventive_assigned' => '👤 ' . t('assigned'),
                                            'preventive_completed' => '✅ ' . t('completed'),
                                            'preventive_deleted' => '🗑️ ' . t('cancelled')
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
                                <?php if(!$is_cancelled): ?>
                                    <a href="?page=preventive_view&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=preventive_complete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('complete'); ?>">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                        <a href="?page=preventive_assign&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('assign'); ?>">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                        <a href="?page=preventive_edit&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <a href="?page=preventive_delete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('cancel'); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-ban"></i> <?php echo t('cancelled'); ?></span>
                                <?php endif; ?>
                            </td>
                        <tr>
                        <?php endforeach; ?>
                        <?php if(count($preventives) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                <?php echo t('no_preventive_found'); ?>
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
                            <span class="status-badge status-overdue">🔴 <?php echo t('overdue'); ?></span>
                            <small><?php echo t('maintenance_overdue'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-upcoming">🟡 <?php echo t('upcoming'); ?> (< 30d)</span>
                            <small><?php echo t('maintenance_upcoming'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-ok">🟢 <?php echo t('ok'); ?></span>
                            <small><?php echo t('maintenance_ok'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-cancelled">⚫ <?php echo t('cancelled'); ?></span>
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