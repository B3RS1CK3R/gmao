<?php
// pages/equipment_detail.php - Fiche détaillée d'un équipement
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if(!$id) {
    echo "<div class='alert alert-danger'>" . t('missing_id') . "</div>";
    return;
}

$equipment = getEquipmentDetails($id);
if(!$equipment) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Attachments
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'equipment' AND parent_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();

// Interventions (recent)
$interventions = getMaintenanceHistory($id);

// Modifications history (user_logs)
$stmt = $pdo->prepare("SELECT ul.*, u.username FROM user_logs ul LEFT JOIN users u ON u.id = ul.user_id WHERE ul.action IN ('equipment_created','equipment_updated','equipment_deleted','equipment_restored') AND ul.details LIKE ? ORDER BY ul.created_at DESC");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Preventive maintenances
$stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE equipment_id = ? ORDER BY next_due DESC");
$stmt->execute([$id]);
$preventives = $stmt->fetchAll();
?>
<div class="row g-3 align-items-stretch">
    <!-- Row 1: General Information (left) | Technical Specifications (right) -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> <?php echo t('general_info'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
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
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-cogs"></i> <?php echo t('technical_specs'); ?>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($equipment['technical_specs'] ?: t('not_specified'))); ?></p>
            </div>
        </div>
    </div>

    <!-- Row 2: Dates (left) | Documents (right) -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-calendar-alt"></i> <?php echo t('dates'); ?>
            </div>
            <div class="card-body" style="max-height:220px; overflow:auto;">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td style="width: 50%;"><strong><?php echo t('purchase_date'); ?></strong></td><td><?php echo format_date_us($equipment['purchase_date'], false); ?></td></tr>
                    <tr><td><strong><?php echo t('warranty_end'); ?></strong></td>
                        <td>
                            <?php if($equipment['warranty_end']): ?>
                                <?php echo format_date_us($equipment['warranty_end'], false); ?>
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
                    <tr><td><strong><?php echo t('created_at'); ?></strong></td><td><?php echo format_date_us($equipment['created_at'], true); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-paperclip"></i> Documents
            </div>
            <div class="card-body">
                <?php if(empty($attachments)): ?>
                    <div class="text-muted"><?php echo t('no_documents'); ?></div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($attachments as $att): ?>
                            <div class="card" style="width:220px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($att['original_name'] ?: ($att['external_path'] ?: $att['filename'])); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($att['mime'] === 'link' ? 'link' : $att['mime']); ?></div>
                                        </div>
                                        <div>
                                            <?php if(!empty($att['external_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($att['external_path']); ?>" target="_blank" class="btn btn-sm btn-info" title="<?php echo t('open_document'); ?>">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" target="_blank" class="btn btn-sm btn-secondary" title="<?php echo t('view'); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" download class="btn btn-sm btn-info" title="<?php echo t('download'); ?>">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2 small text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($att['external_path'] ?? ''); ?></div>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor' || $_SESSION['user_id'] == $att['created_by']): ?>
                                        <div class="mt-2">
                                            <form action="api/delete_attachment.php" method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo t('delete_confirm'); ?>');">
                                                <input type="hidden" name="id" value="<?php echo $att['id']; ?>">
                                                <?php echo csrf_input(); ?>
                                                <button class="btn btn-sm btn-danger" type="submit"><?php echo t('delete'); ?></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <hr>
                    <div class="mb-2">
                        <form id="add-attachment-link-form" action="api/add_attachment_link.php" method="post" class="mb-2">
                            <input type="hidden" name="parent_type" value="equipment">
                            <input type="hidden" name="parent_id" value="<?php echo $equipment['id']; ?>">
                            <?php echo csrf_input(); ?>
                            <div class="mb-2">
                                <label class="form-label small"><?php echo t('document_label'); ?></label>
                                <input type="text" name="label" class="form-control form-control-sm" placeholder="Fiche technique, Manuel...">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?php echo t('document_path'); ?></label>
                                <input type="text" name="external_path" class="form-control form-control-sm" placeholder="https://... or C:\\path\\to\\file.pdf" required>
                            </div>
                            <button class="btn btn-sm btn-primary" type="submit"><?php echo t('add_link'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <!-- Row 3: Interventions History (left) | Modifications History (right) -->
    <!-- Interventions History - full width -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
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
                                    <td><?php echo format_date_us($inv['created_at'], false); ?></td>
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
    </div>

    <!-- Modifications History - full width -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body">
                <?php if(empty($history)): ?>
                    <div class="text-muted"><?php echo t('no_history'); ?></div>
                <?php else: ?>
                    <?php foreach($history as $h): ?>
                        <div class="history-item mb-3">
                            <div class="d-flex justify-content-between">
                                <div><strong><?php
                                    $icon = ($h['action'] == 'equipment_updated') ? '✏️ ' : (($h['action']=='equipment_created') ? '🟢 ' : '🔄 ');
                                    echo $icon . t('modified_short');
                                ?></strong></div>
                                <small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                            </div>
                            <small class="text-muted"><?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> (IP: <?php echo htmlspecialchars($h['ip_address'] ?? '-'); ?>)</small>
                            <div class="mt-2">
                                <div><?php echo nl2br(htmlspecialchars($h['details'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preventive Maintenances (full width) -->
    <?php if(!empty($preventives)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
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
                                <td><?php echo $pm['last_done'] ? format_date_us($pm['last_done'], false) : t('never'); ?></td>
                                <td>
                                    <?php echo format_date_us($pm['next_due'], false); ?>
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
    </div>
    <?php endif; ?>

    <!-- Actions rapides -->
    <div class="col-12">
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
