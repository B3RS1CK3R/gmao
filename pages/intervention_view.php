<?php
// pages/intervention_view.php - Fiche détaillée d'une intervention
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: index.php?page=interventions');
    exit();
}

// Récupération de l'intervention
$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location,
           t.id as technician_id, t.firstname, t.lastname, t.specialty, t.phone as technician_phone,
           u.username as created_by_name
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    LEFT JOIN users u ON i.reported_by = u.username
    WHERE i.id = ?
");
$stmt->execute([$id]);
$intervention = $stmt->fetch();

if(!$intervention) {
    echo "<div class='alert alert-danger'>" . t('intervention_not_found') . "</div>";
    return;
}

// Récupération de l'historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('intervention_created', 'intervention_updated', 'intervention_status_change', 
                     'intervention_assigned', 'intervention_completed', 'intervention_deleted')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Récupération des pièces utilisées
$stmt = $pdo->prepare("
    SELECT sp.*, sm.quantity 
    FROM stock_movements sm
    JOIN spare_parts sp ON sm.part_id = sp.id
    WHERE sm.intervention_id = ?
");
$stmt->execute([$id]);
$used_parts = $stmt->fetchAll();

// Récupération des techniciens pour assignation
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
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
    .info-card-header.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .priority-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
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
        font-size: 12px;
        font-weight: 600;
    }
    .status-a_faire { background: #6c757d; color: white; }
    .status-en_cours { background: #17a2b8; color: white; }
    .status-termine { background: #28a745; color: white; }
    .status-cloturee { background: #343a40; color: white; }
    .history-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child { border-bottom: none; }
    .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
    .report-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
        border-left: 4px solid #28a745;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover { filter: brightness(0.95); }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary:hover { background: #5a6268; }
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
        <i class="fas fa-tools"></i> 
        <?php echo htmlspecialchars($intervention['title']); ?>
        <small class="text-muted">(<?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?>)</small>
    </h2>
    <div>
        <a href="?page=interventions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
        <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
            <a href="?page=interventions&action=edit&id=<?php echo $intervention['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Colonne gauche -->
    <div class="col-md-5">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-info-circle"></i> <?php echo t('identification'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 40%;"><strong><?php echo t('task_number'); ?></strong></td><td><code><?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?></code></td></tr>
                    <tr><td><strong><?php echo t('title'); ?></strong></td><td><?php echo htmlspecialchars($intervention['title']); ?></td></tr>
                    <tr><td><strong><?php echo t('created_at'); ?></strong></td><td><?php echo date('d/m/Y H:i', strtotime($intervention['created_at'])); ?></td></tr>
                    <tr><td><strong><?php echo t('created_by'); ?></strong></td><td><?php echo htmlspecialchars($intervention['created_by_name'] ?? $intervention['reported_by']); ?></td></tr>
                    <tr><td><strong><?php echo t('priority'); ?></strong></td>
                        <td><span class="priority-badge priority-<?php echo $intervention['priority']; ?>"><?php echo t($intervention['priority']); ?></span></td>
                    </tr>
                    <tr><td><strong><?php echo t('status'); ?></strong></td>
                        <td><span class="status-badge status-<?php echo $intervention['task_status']; ?>">
                            <?php 
                            $status_labels = [
                                'a_faire' => t('to_do'),
                                'en_cours' => t('in_progress'),
                                'termine' => t('completed'),
                                'cloturee' => t('closed')
                            ];
                            echo $status_labels[$intervention['task_status']] ?? $intervention['task_status'];
                            ?>
                        </span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-microchip"></i> <?php echo t('equipment'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 40%;"><strong><?php echo t('name'); ?></strong></td><td><?php echo htmlspecialchars($intervention['equipment_name']); ?></td></tr>
                    </td><td><strong><?php echo t('code'); ?></strong></td><td><?php echo htmlspecialchars($intervention['equipment_code']); ?></td></tr>
                    <tr><td><strong><?php echo t('location'); ?></strong></td><td><?php echo htmlspecialchars($intervention['equipment_location'] ?: t('not_specified')); ?></td></tr>
                    <?php if($intervention['zone']): ?>
                    <tr><td><strong><?php echo t('zone'); ?></strong></td><td><?php echo htmlspecialchars($intervention['zone']); ?></td></tr>
                    <?php endif; ?>
                    <?php if($intervention['localisation']): ?>
                    <tr><td><strong><?php echo t('localisation'); ?></strong></td><td><?php echo htmlspecialchars($intervention['localisation']); ?></td></tr>
                    <?php endif; ?>
                </table>
                <div class="mt-2">
                    <a href="?page=equipment_detail&id=<?php echo $intervention['equipment_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-eye"></i> <?php echo t('view_equipment'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <div class="info-card-header <?php echo $intervention['intervenant_id'] ? 'success' : 'warning'; ?>">
                <i class="fas fa-user-cog"></i> <?php echo t('technician'); ?>
            </div>
            <div class="card-body p-4">
                <?php if($intervention['firstname']): ?>
                    <table class="table table-sm table-borderless">
                        <tr><td style="width: 40%;"><strong><?php echo t('name'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['firstname'] . ' ' . $intervention['lastname']); ?></td>
                        </tr>
                        <tr><td><strong><?php echo t('specialty'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['specialty']); ?></td>
                        </tr>
                        <tr><td><strong><?php echo t('phone'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['technician_phone'] ?: t('not_specified')); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center mb-3"><?php echo t('no_technician_assigned'); ?></p>
                <?php endif; ?>
                
                <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor')): ?>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-info w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                            <i class="fas fa-user-plus"></i> <?php echo t('assign_technician'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Colonne droite -->
    <div class="col-md-7">
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-calendar-alt"></i> <?php echo t('planning'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 40%;"><strong><?php echo t('task_type'); ?></strong></td>
                        <td>
                            <?php 
                            $type_labels = [
                                'revision' => t('revision'),
                                'depannage' => t('repair'),
                                'installation' => t('installation'),
                                'maintenance_preventive' => t('preventive_maintenance_short'),
                                'controle' => t('inspection'),
                                'autre' => t('other')
                            ];
                            $type_text = $type_labels[$intervention['task_type']] ?? '';
                            if(!$type_text) {
                                $type_text = $intervention['type'] == 'corrective' ? t('corrective') : ($intervention['type'] == 'preventive' ? t('preventive') : t('emergency'));
                            }
                            echo $type_text;
                            ?>
                        </td>
                    </tr>
                    <tr><td><strong><?php echo t('planned_date'); ?></strong></td>
                        <td><?php echo $intervention['intervention_date'] ? date('d/m/Y', strtotime($intervention['intervention_date'])) : t('not_planned'); ?>
                        <?php if(strtotime($intervention['intervention_date']) < time() && $intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
                            <span class="badge bg-danger ms-2"><?php echo t('overdue'); ?></span>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <tr><td><strong><?php echo t('planned_duration'); ?></strong></td><td><?php echo htmlspecialchars($intervention['planned_duration'] ?? t('not_specified')); ?></td></tr>
                    <?php if($intervention['duration_hours']): ?>
                    <tr><td><strong><?php echo t('actual_duration'); ?></strong></td><td><?php echo $intervention['duration_hours']; ?> <?php echo t('hours'); ?></td></tr>
                    <?php endif; ?>
                    <?php if($intervention['completed_date']): ?>
                    <tr><td><strong><?php echo t('completion_date'); ?></strong></td><td><?php echo date('d/m/Y H:i', strtotime($intervention['completed_date'])); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Description -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-clipboard-list"></i> <?php echo t('description'); ?>
            </div>
            <div class="card-body p-4">
                <p><?php echo nl2br(htmlspecialchars($intervention['description'] ?: t('no_description'))); ?></p>
            </div>
        </div>
        
        <!-- Rapport -->
        <?php if($intervention['completion_report']): ?>
        <div class="info-card">
            <div class="info-card-header success">
                <i class="fas fa-file-alt"></i> <?php echo t('completion_report'); ?>
            </div>
            <div class="card-body p-4">
                <div class="report-box">
                    <?php echo nl2br(htmlspecialchars($intervention['completion_report'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Pièces utilisées -->
        <?php if(!empty($used_parts)): ?>
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-boxes"></i> <?php echo t('parts_used'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('part_number'); ?></th><th><?php echo t('name'); ?></th><th><?php echo t('quantity'); ?></th><th><?php echo t('unit_price'); ?></th><th><?php echo t('total'); ?></th></tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_cost = 0;
                            foreach($used_parts as $part): 
                                $subtotal = $part['unit_price'] * $part['quantity'];
                                $total_cost += $subtotal;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($part['part_number']); ?></td>
                                <td><?php echo htmlspecialchars($part['name']); ?></td>
                                <td><?php echo $part['quantity']; ?></td>
                                <td><?php echo number_format($part['unit_price'], 2); ?> €</td>
                                <td><?php echo number_format($subtotal, 2); ?> €</td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-active">
                                <td colspan="4" class="text-end"><strong><?php echo t('total'); ?></strong></td>
                                <td><strong><?php echo number_format($total_cost, 2); ?> €</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Historique -->
        <?php if(!empty($history)): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body p-3">
                <?php foreach($history as $h): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <span>
                            <?php
                            $action_icons = [
                                'intervention_created' => '🟢 ' . t('created'),
                                'intervention_updated' => '✏️ ' . t('modified'),
                                'intervention_status_change' => '📊 ' . t('status_changed'),
                                'intervention_assigned' => '👤 ' . t('assigned'),
                                'intervention_completed' => '✅ ' . t('completed'),
                                'intervention_deleted' => '🗑️ ' . t('cancelled')
                            ];
                            echo $action_icons[$h['action']] ?? $h['action'];
                            ?>
                        </span>
                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></small>
                    </div>
                    <small class="text-muted"><?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> (IP: <?php echo htmlspecialchars($h['ip_address']); ?>)</small>
                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($h['details']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
        <div class="action-buttons">
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'technician'): ?>
                <a href="?page=interventions&action=complete&id=<?php echo $intervention['id']; ?>" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> <?php echo t('complete_intervention'); ?>
                </a>
            <?php endif; ?>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                <a href="?page=interventions&action=edit&id=<?php echo $intervention['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                </a>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                    <i class="fas fa-trash"></i> <?php echo t('cancel'); ?>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal d'assignation -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> <?php echo t('assign_technician'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=interventions&action=assign&id=<?php echo $intervention['id']; ?>">
                <div class="modal-body">
                    <p><strong><?php echo t('intervention'); ?> :</strong> <?php echo htmlspecialchars($intervention['title']); ?></p>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('technician'); ?></label>
                        <select name="technician_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_technician'); ?> --</option>
                            <?php foreach($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>" <?php echo $intervention['intervenant_id'] == $tech['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                    <button type="submit" class="btn btn-info"><?php echo t('assign'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmCancel() {
    if(confirm('<?php echo t('delete_confirm'); ?>')) {
        window.location.href = '?page=interventions&action=delete&id=<?php echo $intervention['id']; ?>';
    }
}
</script>