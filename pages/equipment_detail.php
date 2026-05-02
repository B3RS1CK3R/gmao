<?php
// pages/equipment_detail.php - Fiche détaillée d'un équipement
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: index.php?page=equipment');
    exit();
}

// Récupération de l'équipement
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$equipment = $stmt->fetch();

if(!$equipment) {
    echo "<div class='alert alert-danger'>" . t('equipment_not_found') . "</div>";
    return;
}

// Récupération des interventions liées
$stmt = $pdo->prepare("
    SELECT i.*, t.firstname, t.lastname 
    FROM interventions i 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    WHERE i.equipment_id = ? 
    ORDER BY i.created_at DESC
");
$stmt->execute([$id]);
$interventions = $stmt->fetchAll();

// Récupération des maintenances préventives
$stmt = $pdo->prepare("
    SELECT * FROM preventive_maintenance 
    WHERE equipment_id = ? 
    ORDER BY next_due ASC
");
$stmt->execute([$id]);
$preventives = $stmt->fetchAll();

// Calcul des statistiques
$total_interventions = count($interventions);
$completed = 0;
$pending = 0;
$total_duration = 0;

foreach($interventions as $inv) {
    if($inv['task_status'] == 'termine') {
        $completed++;
        $total_duration += $inv['duration_hours'];
    } elseif($inv['task_status'] == 'a_faire' || $inv['task_status'] == 'en_cours') {
        $pending++;
    }
}

$avg_duration = $completed > 0 ? round($total_duration / $completed, 1) : 0;

// Récupération de l'historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('equipment_created', 'equipment_updated', 'equipment_deleted', 'equipment_restored')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Calcul MTBF et MTTR
$mtbf = calculateMTBF($id);
$mttr = calculateMTTR($id);
$availability = calculateAvailability($id);
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .info-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .info-card-header.warning { background: linear-gradient(135deg, #fd7e14, #e06a0a); }
    .info-card-header.danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    .info-card-header.success { background: linear-gradient(135deg, #28a745, #1e7e34); }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active { background: #28a745; color: white; }
    .status-maintenance { background: #ffc107; color: #333; }
    .status-broken { background: #dc3545; color: white; }
    .status-retired { background: #6c757d; color: white; }
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    .kpi-small {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 10px;
        text-align: center;
    }
    .kpi-small .number {
        font-size: 20px;
        font-weight: bold;
    }
    .history-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child { border-bottom: none; }
    .action-buttons { display: flex; gap: 10px; margin-top: 20px; }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover { filter: brightness(0.95); }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover { background: #e06a0a; color: white; }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-danger:hover { background: #c82333; }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-info:hover { background: #138496; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-microchip"></i> 
        <?php echo htmlspecialchars($equipment['name']); ?>
        <small class="text-muted">(<?php echo htmlspecialchars($equipment['code']); ?>)</small>
    </h2>
    <div>
        <a href="?page=equipment" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=equipment&action=edit&id=<?php echo $equipment['id']; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
        </a>
        <?php endif; ?>
        <a href="?page=equipment_qr&id=<?php echo $equipment['id']; ?>" class="btn btn-info">
            <i class="fas fa-qrcode"></i> <?php echo t('qr_code'); ?>
        </a>
    </div>
</div>

<div class="row">
    <!-- Colonne gauche -->
    <div class="col-md-4">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-info-circle"></i> <?php echo t('general_info'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 40%;"><strong><?php echo t('code'); ?></strong></td><td><?php echo htmlspecialchars($equipment['code']); ?></td></tr>
                    <tr><td><strong><?php echo t('name'); ?></strong></td><td><?php echo htmlspecialchars($equipment['name']); ?></td></tr>
                    <tr><td><strong><?php echo t('type'); ?></strong></td><td><?php echo htmlspecialchars($equipment['type'] ?: t('not_specified')); ?></td></tr>
                    <tr><td><strong><?php echo t('location'); ?></strong></td><td><?php echo htmlspecialchars($equipment['location'] ?: t('not_specified')); ?></td></tr>
                    <tr><td><strong><?php echo t('supplier'); ?></strong></td><td><?php echo htmlspecialchars($equipment['supplier'] ?: t('not_specified')); ?></td></tr>
                    <tr><td><strong><?php echo t('status'); ?></strong></td>
                        <td><span class="status-badge status-<?php echo $equipment['status']; ?>">
                            <?php 
                            $status_labels = [
                                'active' => '🟢 ' . t('active'),
                                'maintenance' => '🟡 ' . t('maintenance'),
                                'broken' => '🔴 ' . t('broken'),
                                'retired' => '⚫ ' . t('retired')
                            ];
                            echo $status_labels[$equipment['status']] ?? $equipment['status'];
                            ?>
                        </span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header warning">
                <i class="fas fa-calendar-alt"></i> <?php echo t('dates'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 50%;"><strong><?php echo t('purchase_date'); ?></strong></td><td><?php echo $equipment['purchase_date'] ? date('d/m/Y', strtotime($equipment['purchase_date'])) : t('not_specified'); ?></td></tr>
                    <tr><td><strong><?php echo t('warranty_end'); ?></strong></td>
                        <td>
                            <?php if($equipment['warranty_end']): ?>
                                <?php echo date('d/m/Y', strtotime($equipment['warranty_end'])); ?>
                                <?php if(strtotime($equipment['warranty_end']) < time()): ?>
                                    <span class="badge bg-danger ms-2"><?php echo t('expired'); ?></span>
                                <?php elseif(strtotime($equipment['warranty_end']) < strtotime('+30 days')): ?>
                                    <span class="badge bg-warning ms-2"><?php echo t('expiring_soon'); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php echo t('not_specified'); ?>
                            <?php endif; ?>
                         </td>
                    </tr>
                    <tr><td><strong><?php echo t('created_at'); ?></strong></td><td><?php echo date('d/m/Y H:i', strtotime($equipment['created_at'])); ?></td></tr>
                </table>
            </div>
        </div>
        
        <?php if($equipment['technical_specs']): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-cogs"></i> <?php echo t('technical_specs'); ?>
            </div>
            <div class="card-body p-4">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($equipment['technical_specs'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- KPIs -->
        <div class="row">
            <div class="col-4">
                <div class="kpi-small text-center">
                    <div class="number"><?php echo $mtbf > 0 ? number_format($mtbf, 0) . 'h' : 'N/A'; ?></div>
                    <small><?php echo t('mtbf'); ?></small>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi-small text-center">
                    <div class="number"><?php echo $mttr > 0 ? number_format($mttr, 1) . 'h' : 'N/A'; ?></div>
                    <small><?php echo t('mttr'); ?></small>
                </div>
            </div>
            <div class="col-4">
                <div class="kpi-small text-center">
                    <div class="number"><?php echo $availability; ?>%</div>
                    <small><?php echo t('availability'); ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Colonne droite -->
    <div class="col-md-8">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_interventions; ?></div>
                    <div class="text-muted"><?php echo t('total_interventions'); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $completed; ?></div>
                    <div class="text-muted"><?php echo t('completed'); ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $avg_duration; ?>h</div>
                    <div class="text-muted"><?php echo t('avg_duration'); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Interventions en attente -->
        <?php if($pending > 0): ?>
        <div class="info-card">
            <div class="info-card-header danger">
                <i class="fas fa-clock"></i> <?php echo t('pending_interventions'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('task_number'); ?></th><th><?php echo t('title'); ?></th><th><?php echo t('priority'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('date'); ?></th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($interventions as $inv): ?>
                                <?php if($inv['task_status'] == 'a_faire' || $inv['task_status'] == 'en_cours'): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                    <td><span class="badge bg-<?php echo $inv['priority'] == 'critical' ? 'danger' : ($inv['priority'] == 'high' ? 'warning' : 'secondary'); ?>"><?php echo t($inv['priority']); ?></span></td>
                                    <td><?php echo $inv['task_status'] == 'a_faire' ? t('pending') : t('in_progress'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?></td>
                                    <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Historique des interventions -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> <?php echo t('interventions_history'); ?>
            </div>
            <div class="card-body p-0">
                <?php if(empty($interventions)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p><?php echo t('no_interventions'); ?></p>
                        <a href="?page=intervention_add&equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> <?php echo t('create_intervention'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('task_number'); ?></th>
                                    <th><?php echo t('title'); ?></th>
                                    <th><?php echo t('priority'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('date'); ?></th>
                                    <th><?php echo t('duration'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($interventions as $inv): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                    <td><span class="badge bg-<?php echo $inv['priority'] == 'critical' ? 'danger' : ($inv['priority'] == 'high' ? 'warning' : 'secondary'); ?>"><?php echo t($inv['priority']); ?></span></td>
                                    <td>
                                        <?php 
                                        if($inv['task_status'] == 'a_faire') echo t('pending');
                                        elseif($inv['task_status'] == 'en_cours') echo t('in_progress');
                                        elseif($inv['task_status'] == 'termine') echo t('completed');
                                        else echo t('closed');
                                        ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?></td>
                                    <td><?php echo $inv['duration_hours'] ? $inv['duration_hours'] . 'h' : '-'; ?></td>
                                    <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Maintenances préventives -->
        <?php if(!empty($preventives)): ?>
        <div class="info-card">
            <div class="info-card-header warning">
                <i class="fas fa-calendar-check"></i> <?php echo t('preventive_maintenances'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('frequency'); ?></th><th><?php echo t('last_done'); ?></th><th><?php echo t('next_due'); ?></th><th><?php echo t('instructions'); ?></th><th><?php echo t('team'); ?></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($preventives as $pm): ?>
                            <tr>
                                <td><?php echo t('every') . ' ' . $pm['frequency_days'] . ' ' . t('days'); ?></td>
                                <td><?php echo $pm['last_done'] ? date('d/m/Y', strtotime($pm['last_done'])) : t('never'); ?></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($pm['next_due'])); ?>
                                    <?php if(strtotime($pm['next_due']) < time()): ?>
                                        <span class="badge bg-danger ms-2"><?php echo t('overdue'); ?></span>
                                    <?php endif; ?>
                                 </td>
                                <td><?php echo nl2br(htmlspecialchars($pm['instructions'])); ?></td>
                                <td><?php echo htmlspecialchars($pm['assigned_team'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Historique des modifications -->
        <?php if(!empty($history)): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-edit"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body p-3">
                <?php foreach($history as $h): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <span>
                            <?php
                            $action_icons = [
                                'equipment_created' => '🟢 ' . t('created'),
                                'equipment_updated' => '✏️ ' . t('modified'),
                                'equipment_deleted' => '🗑️ ' . t('deactivated'),
                                'equipment_restored' => '🔄 ' . t('restored')
                            ];
                            echo $action_icons[$h['action']] ?? $h['action'];
                            ?>
                        </span>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></small>
                    </div>
                    <small class="text-muted">
                        <?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> 
                        (IP: <?php echo htmlspecialchars($h['ip_address']); ?>)
                    </small>
                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($h['details']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions rapides -->
        <div class="action-buttons">
            <a href="?page=intervention_add&equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
            </a>
            <a href="?page=preventive_add&equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-warning">
                <i class="fas fa-calendar-plus"></i> <?php echo t('plan_maintenance'); ?>
            </a>
        </div>
    </div>
</div>