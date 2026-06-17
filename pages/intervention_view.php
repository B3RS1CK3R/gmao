<?php
// pages/intervention_view.php - Fiche détaillée d'une intervention
// auth handled centrally in index.php

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
    LEFT JOIN technicians t ON i.technician_id = t.id
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

// Récupération des pièces jointes (documents, images)
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'intervention' AND parent_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
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
        background: #667eea;
        color: white;
        padding: 12px 20px;
        font-weight: bold;
    }
    .card-header-custom.warning { background: #fd7e14; }
    .card-header-custom.danger { background: #dc3545; }
    .card-header-custom.success { background: #28a745; }
    .card-header-custom.info { background: #17a2b8; }
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
    .history-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child { border-bottom: none; }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-primary {
        background: #667eea;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover { background: #5a67d8; }
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
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover { background: #e06a0a; }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .w-100 { width: 100%; }
    .table-borderless td, .table-borderless th { border: none; }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-tools"></i> 
            <?php echo t('intervention_details'); ?> : <?php echo htmlspecialchars($intervention['title']); ?>
            <small class="text-muted">(<?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?>)</small>
        </h2>
        <div>
            <a href="?page=interventions" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>

    <!-- ROW with 2 columns -->
    <div class="row">
        
        <!-- LEFT COLUMN (col-md-5) -->
        <div class="col-md-5">
            
            <!-- Card: Identification -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-info-circle"></i> <?php echo t('identification'); ?>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong><?php echo t('task_number'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('title'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['title']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('created_at'); ?></strong></td>
                            <td><?php echo format_date_local($intervention['created_at'], 'long', true); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('created_by'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['created_by_name'] ?? $intervention['reported_by']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('priority'); ?></strong></td>
                            <td><span class="priority-badge priority-<?php echo $intervention['priority']; ?>"><?php echo t($intervention['priority']); ?></span></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('status'); ?></strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $intervention['task_status']; ?>">
                                <?php 
                                $status_labels = [
                                    'a_faire' => t('to_do'),
                                    'en_cours' => t('in_progress'),
                                    'termine' => t('completed'),
                                    'cloturee' => t('closed')
                                ];
                                echo $status_labels[$intervention['task_status']] ?? $intervention['task_status'];
                                ?>
                                </span>
                            </td>
                        </tr>
                    <tr>
                </div>
            </div>
            
            <!-- Card: Equipment -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-microchip"></i> <?php echo t('equipment'); ?>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong><?php echo t('name'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('code'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_code']); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('location'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_location'] ?: 'Not specified'); ?></td>
                        </tr>
                        <?php if($intervention['zone']): ?>
                        <tr>
                            <td><strong><?php echo t('zone'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['zone']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if($intervention['localisation']): ?>
                        <tr>
                            <td><strong><?php echo t('localisation'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['localisation']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <div class="mt-3">
                        <a href="?page=equipment_detail&id=<?php echo $intervention['equipment_id']; ?>" class="btn btn-primary w-100">
                            <i class="fas fa-eye"></i> <?php echo t('view_equipment'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Card: Technician -->
            <div class="info-card">
                <div class="card-header-custom <?php echo $intervention['technician_id'] ? 'success' : 'warning'; ?>">
                    <i class="fas fa-user-cog"></i> <?php echo t('technician'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($intervention['firstname']): ?>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td style="width: 40%;"><strong><?php echo t('name'); ?></strong></td>
                                <td><?php echo htmlspecialchars($intervention['firstname'] . ' ' . $intervention['lastname']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('specialty'); ?></strong></td>
                                <td><?php echo htmlspecialchars($intervention['specialty']); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo t('phone'); ?></strong></td>
                                <td><?php echo htmlspecialchars($intervention['technician_phone'] ?: t('not_provided')); ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No technician assigned</p>
                    <?php endif; ?>
                    
                    <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor')): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fas fa-user-plus"></i> <?php echo t('assign_technician'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div> <!-- END LEFT COLUMN -->
        
        <!-- RIGHT COLUMN (col-md-7) -->
        <div class="col-md-7">
            
            <!-- Card: Planning -->
            <div class="info-card">
                <div class="card-header-custom info">
                    <i class="fas fa-calendar-alt"></i> <?php echo t('planning'); ?>
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong><?php echo t('task_type'); ?></strong></td>
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
                        <tr>
                            <td><strong><?php echo t('planned_date'); ?></strong></td>
                            <td>
                                <?php echo $intervention['intervention_date'] ? format_date_local($intervention['intervention_date'], 'long', false) : t('not_planned'); ?>
                                <?php if(strtotime($intervention['intervention_date']) < time() && $intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
                                    <span class="badge bg-danger ms-2"><?php echo t('overdue'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?php echo t('planned_duration'); ?></strong></td>
                            <td><?php echo htmlspecialchars($intervention['planned_duration'] ?? t('not_specified')); ?></td>
                        </tr>
                        <?php if($intervention['duration_hours']): ?>
                        <tr>
                            <td><strong><?php echo t('actual_duration'); ?></strong></td>
                            <td><?php echo $intervention['duration_hours']; ?>h</span>
                        </tr>
                        <?php endif; ?>
                        <?php if($intervention['completed_date']): ?>
                        <tr>
                            <td><strong><?php echo t('completion_date'); ?></strong></td>
                            <td><?php echo format_date_local($intervention['completed_date'], 'long', true); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            
            <!-- Card: Description -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-clipboard-list"></i> <?php echo t('description'); ?>
                </div>
                <div class="card-body p-4">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($intervention['description'] ?: t('no_description'))); ?></p>
                </div>
            </div>
            
            <!-- Card: Completion Report -->
            <?php if($intervention['completion_report']): ?>
            <div class="info-card">
                <div class="card-header-custom success">
                    <i class="fas fa-file-alt"></i> <?php echo t('completion_report'); ?>
                </div>
                <div class="card-body p-4">
                    <?php echo nl2br(htmlspecialchars($intervention['completion_report'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Card: Parts Used -->
            <?php if(!empty($used_parts)): ?>
            <div class="info-card">
                <div class="card-header-custom info">
                    <i class="fas fa-boxes"></i> <?php echo t('parts_used'); ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('part_number'); ?></th><th><?php echo t('name'); ?></th><th><?php echo t('quantity'); ?></th><th><?php echo t('unit_price'); ?></th><th><?php echo t('total'); ?></th>
                                </tr>
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
                                    <td><?php echo number_format($part['unit_price'], 2); ?> €</span>
                                    <td><?php echo number_format($subtotal, 2); ?> €</span>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-active">
                                    <td colspan="4" class="text-end"><strong><?php echo t('total'); ?></strong></span>
                                    <td><strong><?php echo number_format($total_cost, 2); ?> €</strong></span>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Card: History (améliorée avec task_number) -->
            <?php if(!empty($history)): 
                // Récupérer les numéros de tâche pour tous les IDs d'intervention
                $task_numbers = [];
                foreach($history as $h) {
                    if (preg_match('/ID: (\d+)/', $h['details'], $matches)) {
                        $interv_id = $matches[1];
                        if (!isset($task_numbers[$interv_id])) {
                            $stmt = $pdo->prepare("SELECT task_number FROM interventions WHERE id = ?");
                            $stmt->execute([$interv_id]);
                            $task_numbers[$interv_id] = $stmt->fetchColumn();
                        }
                    }
                }
            ?>
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-history"></i> <?php echo t('modification_history'); ?>
                </div>
                <div class="card-body p-3">
                    <?php foreach($history as $h):
                        $icon = '';
                        $action_text = '';
                        $task_info = '';
                        
                        // Extraire l'ID de l'intervention
                        if (preg_match('/ID: (\d+)/', $h['details'], $matches)) {
                            $interv_id = $matches[1];
                            $task_info = $task_numbers[$interv_id] ?? 'ID ' . $interv_id;
                        } elseif (preg_match('/(TASK-\d+)/', $h['details'], $matches)) {
                            $task_info = $matches[1];
                        }
                        
                        switch($h['action']) {
                            case 'intervention_created': $icon = '🟢 '; $action_text = t('intervention_created'); break;
                            case 'intervention_updated': $icon = '✏️ '; $action_text = t('intervention_updated'); break;
                            case 'intervention_status_change': $icon = '📊 '; $action_text = t('intervention_status_changed'); break;
                            case 'intervention_assigned': $icon = '👤 '; $action_text = t('intervention_assigned'); break;
                            case 'intervention_completed': $icon = '✅ '; $action_text = t('intervention_completed'); break;
                            case 'intervention_deleted': $icon = '🗑️ '; $action_text = t('intervention_cancelled'); break;
                            default: $icon = '📌 '; $action_text = t('modified_short');
                        }
                        
                        $message = $action_text . ($task_info ? ' : ' . $task_info : '');
                        $username = $h['username'] ?? t('system');
                        if ($username === 'system') $username = t('system');
                    ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between">
                                <span><strong><?php echo $icon . $message; ?></strong></span>
                                <small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                            </div>
                            <div class="small text-muted mt-1"><?php echo t('by'); ?> : <?php echo htmlspecialchars($username); ?> (IP: <?php echo htmlspecialchars($h['ip_address'] ?? '-'); ?>)</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Actions -->
            <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
            <div class="action-buttons">
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'technician'): ?>
                    <a href="?page=interventions_complete&id=<?php echo $intervention['id']; ?>" class="btn btn-success"><?php echo t('complete'); ?></a>
                <?php endif; ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <a href="?page=interventions_edit&id=<?php echo $intervention['id']; ?>" class="btn btn-warning"><?php echo t('edit'); ?></a>
                    <a href="?page=interventions_delete&id=<?php echo $intervention['id']; ?>" class="btn btn-danger"><?php echo t('cancel'); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
        </div> <!-- END RIGHT COLUMN -->
        
    </div> <!-- END ROW -->
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: #17a2b8; color: white;">
                <h5 class="modal-title"><?php echo t('assign_technician'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=interventions_assign&id=<?php echo $intervention['id']; ?>">
                <div class="modal-body">
                    <p><strong><?php echo t('intervention'); ?> :</strong> <?php echo htmlspecialchars($intervention['title']); ?></p>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('technician'); ?></label>
                        <select name="technician_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_technician'); ?> --</option>
                            <?php foreach($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
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