<?php
// pages/equipment.php - Liste des équipements
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$active_filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

// Redirections
if ($action == 'add') { header('Location: ?page=equipment_add'); exit(); }
if ($action == 'edit' && isset($_GET['id'])) { header('Location: ?page=equipment_edit&id=' . intval($_GET['id'])); exit(); }
if ($action == 'delete' && isset($_GET['id'])) { header('Location: ?page=equipment_delete&id=' . intval($_GET['id'])); exit(); }
if ($action == 'restore' && isset($_GET['id'])) { header('Location: ?page=equipment_restore&id=' . intval($_GET['id'])); exit(); }

// Récupération de tous les équipements
$all_equipments = $pdo->query("SELECT * FROM equipment ORDER BY name")->fetchAll();

// Statistiques
$stats = [
    'all' => count($all_equipments),
    'active' => 0,
    'maintenance' => 0,
    'broken' => 0,
    'retired' => 0
];
foreach($all_equipments as $eq) {
    if (isset($stats[$eq['status']])) {
        $stats[$eq['status']]++;
    }
}

// Filtrer
$equipments = array_filter($all_equipments, function($eq) use ($active_filter) {
    if ($active_filter == 'all') return true;
    return $eq['status'] == $active_filter;
});

// Libellés des filtres
$filter_labels = [
    'all' => t('all'),
    'active' => t('active'),
    'maintenance' => t('maintenance'),
    'broken' => t('broken'),
    'retired' => t('deleted')
];
$filter_icons = [
    'all' => '',
    'active' => '🟢',
    'maintenance' => '🟡',
    'broken' => '🔴',
    'retired' => '⚫'
];
$filter_colors = [
    'all' => '#667eea',
    'active' => '#28a745',
    'maintenance' => '#ffc107',
    'broken' => '#dc3545',
    'retired' => '#6c757d'
];

// Attachments
$attachmentCounts = [];
try {
    $stmt = $pdo->query("SELECT parent_id, COUNT(*) as c FROM attachments WHERE parent_type='equipment' GROUP BY parent_id");
    foreach($stmt->fetchAll() as $r) { $attachmentCounts[$r['parent_id']] = $r['c']; }
} catch (PDOException $e) {}

// Historique
$history = [];
foreach($all_equipments as $eq) {
    $stmt = $pdo->prepare("SELECT * FROM user_logs WHERE action IN ('equipment_created', 'equipment_updated', 'equipment_deleted', 'equipment_restored') AND details LIKE ? ORDER BY created_at DESC LIMIT 3");
    $stmt->execute(["%ID: {$eq['id']}%"]);
    $history[$eq['id']] = $stmt->fetchAll();
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
    .status-active { background: #28a745; color: white; }
    .status-maintenance { background: #ffc107; color: #333; }
    .status-broken { background: #dc3545; color: white; }
    .status-retired { background: #6c757d; color: white; }
    
    .action-buttons { white-space: nowrap; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .history-item { padding: 5px 0; font-size: 11px; border-bottom: 1px solid #eee; }
    .badge.bg-orange { background-color: #fd7e14 !important; color: white; }
    
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
        <h2><i class="fas fa-microchip"></i> <?php echo t('equipment'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=equipment_add" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo t('add_equipment'); ?></a>
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
             onclick="window.location.href='?page=equipment&filter=<?php echo $key; ?>'">
            <div class="stats-number" style="color: <?php echo $filter_colors[$key] ?? '#667eea'; ?>;"><?php echo $value; ?></div>
            <div class="stats-label"><?php echo $filter_icons[$key] ?? ''; ?> <?php echo $filter_labels[$key] ?? $key; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Liste -->
    <div class="info-card">
        <div class="card-header-custom"><i class="fas fa-list"></i> <?php echo t('equipment_list'); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('code'); ?></th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('type'); ?></th>
                            <th><?php echo t('location'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('criticality'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($equipments as $eq): 
                            $criticality = (($eq['probability_score'] ?? 1) * ($eq['severity_score'] ?? 1));
                            if ($criticality >= 20) $criticalityClass = 'danger';
                            elseif ($criticality >= 12) $criticalityClass = 'orange';
                            elseif ($criticality >= 6) $criticalityClass = 'warning';
                            else $criticalityClass = 'success';
                        ?>
                        <tr class="table-row-clickable" onclick="window.location.href='?page=equipment_detail&id=<?php echo $eq['id']; ?>'">
                            <td>
                                <strong><?php echo htmlspecialchars($eq['code']); ?></strong>
                                <?php if($eq['warranty_end'] && strtotime($eq['warranty_end']) < time()): ?>
                                    <span class="badge bg-danger"><?php echo t('warranty_expired'); ?></span>
                                <?php elseif($eq['warranty_end'] && strtotime($eq['warranty_end']) < strtotime('+30 days')): ?>
                                    <span class="badge bg-warning text-dark"><?php echo t('warranty_expiring_soon'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($eq['name']); ?></td>
                            <td><?php echo htmlspecialchars($eq['type']); ?></td>
                            <td><?php echo htmlspecialchars($eq['location']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $eq['status']; ?>">
                                    <?php echo $filter_icons[$eq['status']] ?? ''; ?> <?php echo $filter_labels[$eq['status']] ?? $eq['status']; ?>
                                </span>
                            </td>
                            <td class="text-center"><span class="badge bg-<?php echo $criticalityClass; ?>"><?php echo $criticality; ?></span></td>
                            <td style="max-width: 200px;">
                                <?php if(!empty($history[$eq['id']])): ?>
                                    <div class="history-item"><small class="text-muted"><?php echo format_date_local($history[$eq['id']][0]['created_at'], 'long', true); ?></small></div>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center action-buttons" onclick="event.stopPropagation()">
                                <?php if($eq['status'] != 'retired'): ?>
                                    <a href="?page=equipment_attachments&equipment_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-light" title="<?php echo t('attachments'); ?>">
                                        <i class="fas fa-paperclip"></i>
                                        <span class="badge bg-secondary ms-1"><?php echo $attachmentCounts[$eq['id']] ?? 0; ?></span>
                                    </a>
                                    <a href="?page=equipment_qr&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('qr_code'); ?>"><i class="fas fa-qrcode"></i></a>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=equipment_edit&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>"><i class="fas fa-edit"></i></a>
                                    <button type="button" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $eq['id']; ?>"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=equipment_qr&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('qr_code'); ?>"><i class="fas fa-qrcode"></i></a>
                                    <a href="?page=equipment_restore&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('restore'); ?>"><i class="fas fa-undo-alt"></i></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <div class="modal fade" id="deleteModal<?php echo $eq['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title"><i class="fas fa-trash-alt"></i> <?php echo t('delete_equipment'); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="?page=equipment_delete&id=<?php echo $eq['id']; ?>">
                                        <div class="modal-body">
                                            <div class="alert alert-warning mb-3"><i class="fas fa-exclamation-triangle"></i> <?php echo t('delete_confirm_equipment'); ?></div>
                                            <p><strong><?php echo t('equipment'); ?> :</strong> <?php echo htmlspecialchars($eq['name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($eq['code']); ?></small></p>
                                            <p class="text-muted small"><i class="fas fa-info-circle"></i> <?php echo t('delete_warning'); ?></p>
                                            <div class="mb-3">
                                                <label class="form-label"><i class="fas fa-lock"></i> <?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                                                <input type="password" name="confirm_password" class="form-control" required autocomplete="off">
                                                <small class="text-muted"><?php echo t('password_required_to_delete'); ?></small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                                            <button type="submit" class="btn btn-danger"><?php echo t('confirm_delete'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(count($equipments) == 0): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x d-block mb-2"></i><?php echo t('no_equipment_found'); ?></td></tr>
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
                        <div class="legend-item"><span class="status-badge status-active">🟢 <?php echo t('active'); ?></span><small><?php echo t('active_description'); ?></small></div>
                        <div class="legend-item"><span class="status-badge status-maintenance">🟡 <?php echo t('maintenance'); ?></span><small><?php echo t('maintenance_description'); ?></small></div>
                        <div class="legend-item"><span class="status-badge status-broken">🔴 <?php echo t('broken'); ?></span><small><?php echo t('broken_description'); ?></small></div>
                        <div class="legend-item"><span class="status-badge status-retired">⚫ <?php echo t('deleted'); ?></span><small><?php echo t('retired_description'); ?></small></div>
                        <div class="legend-item"><span class="badge bg-secondary">📊</span><small><?php echo t('click_stats_to_filter'); ?></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>