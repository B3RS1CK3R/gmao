<?php
// pages/equipment_detail.php - Fiche détaillée d'un équipement
// Detailed view page for a single equipment item

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

// --- Récupérer le prestataire associé à cet équipement ---
$contractor = null;
$stmt = $pdo->prepare("
    SELECT c.* 
    FROM contractor_equipment ce
    JOIN contractors c ON ce.contractor_id = c.id
    WHERE ce.equipment_id = ? AND c.status = 'active'
    LIMIT 1
");
$stmt->execute([$id]);
$contractor = $stmt->fetch();

// --- Load Attachments ---
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'equipment' AND parent_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();

// --- Load Interventions History ---
$interventions = getMaintenanceHistory($id);

// --- Load Change Log (User Logs) ---
$stmt = $pdo->prepare("
    SELECT ul.*, u.username 
    FROM user_logs ul 
    LEFT JOIN users u ON u.id = ul.user_id 
    WHERE ul.action IN ('equipment_created','equipment_updated','equipment_deleted','equipment_restored') 
    AND (ul.details LIKE ? OR ul.details LIKE ?)
    ORDER BY ul.created_at DESC
");
$stmt->execute(["%ID: {$id}%", "%[{$equipment['code']}]%"]);
$history = $stmt->fetchAll();

// --- Load Preventive Maintenance Schedule ---
$stmt = $pdo->prepare("SELECT * FROM preventive_maintenance WHERE equipment_id = ? ORDER BY next_due DESC");
$stmt->execute([$id]);
$preventives = $stmt->fetchAll();

$status_labels = [
    'active' => '🟢 ' . t('active'),
    'maintenance' => '🟡 ' . t('maintenance'),
    'broken' => '🔴 ' . t('broken'),
    'retired' => '⚫ ' . t('retired')
];
?>

<div class="row g-3">
    
    <!-- LEFT COLUMN -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-info-circle"></i> <?php echo t('general_info'); ?></div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 40%;"><strong><?php echo t('code'); ?></strong></td>
                        <td><?php echo htmlspecialchars($equipment['code']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('name'); ?></strong></td>
                        <td><?php echo htmlspecialchars($equipment['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('type'); ?></strong></td>
                        <td><?php echo htmlspecialchars($equipment['type'] ?: t('not_specified')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('location'); ?></strong></td>
                        <td><?php echo htmlspecialchars($equipment['location'] ?: t('not_specified')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('supplier'); ?></strong></td>
                        <td>
                            <?php if ($contractor): ?>
                                <a href="?page=contractor_detail&id=<?php echo $contractor['id']; ?>" class="text-decoration-none">
                                    <i class="fas fa-building text-primary"></i>
                                    <?php echo htmlspecialchars($contractor['company_name']); ?>
                                    <span class="badge bg-info ms-1"><?php echo t('contractor'); ?></span>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($equipment['supplier'] ?: t('not_specified')); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('status'); ?></strong></td>
                        <td><span class="status-badge status-<?php echo $equipment['status']; ?>"><?php echo $status_labels[$equipment['status']] ?? $equipment['status']; ?></span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-cogs"></i> <?php echo t('technical_specs'); ?></div>
            <div class="card-body"><?php echo nl2br(htmlspecialchars($equipment['technical_specs'] ?: t('not_specified'))); ?></div>
        </div>
        <div class="card" style="max-height:180px; overflow:auto;">
            <div class="card-header bg-warning text-dark"><i class="fas fa-calendar-alt"></i> <?php echo t('dates'); ?></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 40%;"><strong><?php echo t('purchase_date'); ?></strong></td>
                        <td><?php echo format_date_local($equipment['purchase_date'], 'long', false); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('warranty_end'); ?></strong></td>
                        <td>
                            <?php if($equipment['warranty_end']): ?>
                                <?php echo format_date_local($equipment['warranty_end'], 'long', false); ?>
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
                    <tr>
                        <td><strong><?php echo t('created_at'); ?></strong></td>
                        <td><?php echo format_date_local($equipment['created_at'], 'long', true); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- FULL WIDTH: DOCUMENTS -->
    <div class="col-12">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'document_added'): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo t('document_added_success'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo t('document_add_error_' . $_GET['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><i class="fas fa-paperclip"></i> <?php echo t('documents'); ?></div>
            <div class="card-body" style="max-height:340px; overflow:auto;">
                <?php if(empty($attachments)): ?>
                    <div class="text-muted"><?php echo t('no_documents'); ?></div>
                <?php else: ?>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach($attachments as $att): ?>
                            <div class="card" style="width:320px;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo htmlspecialchars($att['original_name'] ?: ($att['external_path'] ?: $att['filename'])); ?></strong>
                                            <div class="small text-muted"><?php echo htmlspecialchars($att['mime'] === 'link' ? 'link' : $att['mime']); ?></div>
                                        </div>
                                        <div class="document-actions d-flex gap-1">
                                            <?php
                                            // Fichier uploadé (présence de filename)
                                            if (!empty($att['filename'])) {
                                                $file_url = $baseUrl . '/uploads/attachments/equipment/' . $att['parent_id'] . '/' . $att['filename'];
                                            ?>
                                                <a href="<?php echo htmlspecialchars($file_url); ?>" target="_blank" class="btn btn-sm btn-info" title="<?php echo t('open_document'); ?>">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" title="<?php echo t('copy_path'); ?>" onclick="copyToClipboard('<?php echo htmlspecialchars($file_url); ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php
                                            }
                                            // Lien externe ou chemin local (external_path)
                                            elseif (!empty($att['external_path'])) {
                                                $path = $att['external_path'];
                                                if (preg_match('/^https?:\/\//i', $path)) {
                                                    $url = $path;
                                                } else {
                                                    $url = 'api/serve_local_file.php?path=' . urlencode($path);
                                                }
                                            ?>
                                                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-sm btn-info" title="<?php echo t('open_document'); ?>">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-secondary" title="<?php echo t('copy_path'); ?>" onclick="copyToClipboard('<?php echo htmlspecialchars($path); ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php } ?>
                                            <!-- Bouton supprimer avec modale -->
                                            <button type="button" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" data-bs-toggle="modal" data-bs-target="#deleteAttachmentModal<?php echo $att['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <!-- Modale de confirmation de suppression -->
                                            <div class="modal fade" id="deleteAttachmentModal<?php echo $att['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-danger text-white">
                                                            <h5 class="modal-title"><i class="fas fa-trash-alt"></i> <?php echo t('delete_document'); ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="api/delete_attachment.php">
                                                            <input type="hidden" name="id" value="<?php echo $att['id']; ?>">
                                                            <?php echo csrf_input(); ?>
                                                            <div class="modal-body">
                                                                <div class="alert alert-warning mb-3">
                                                                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('delete_confirm_document'); ?>
                                                                </div>
                                                                <p><strong><?php echo t('document'); ?> :</strong> <?php echo htmlspecialchars($att['original_name'] ?: ($att['external_path'] ?: $att['filename'])); ?></p>
                                                                <p class="text-muted small"><i class="fas fa-info-circle"></i> <?php echo t('delete_warning'); ?></p>
                                                                <div class="mb-3">
                                                                    <label class="form-label"><i class="fas fa-lock"></i> <?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                                                                    <div class="input-group">
                                                                        <input type="password" name="confirm_password" id="confirm_password_<?php echo $att['id']; ?>" class="form-control" required autocomplete="off">
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password_<?php echo $att['id']; ?>')">
                                                                            <i class="fas fa-eye"></i>
                                                                        </button>
                                                                    </div>
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
                                        </div>
                                    </div>
                                    <div class="mt-2 small text-truncate"><?php echo htmlspecialchars($att['external_path'] ?? $att['filename']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulaire d'upload -->
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <hr>
                    <form id="upload-form" action="api/upload_attachment.php" method="post" enctype="multipart/form-data" class="mb-2">
                        <input type="hidden" name="parent_type" value="equipment">
                        <input type="hidden" name="parent_id" value="<?php echo $equipment['id']; ?>">
                        <?php echo csrf_input(); ?>
                        <div class="mb-2">
                            <label class="form-label small"><?php echo t('document_label'); ?></label>
                            <input type="text" name="label" class="form-control form-control-sm" placeholder="Nom affiché">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small"><?php echo t('document_file'); ?></label>
                            <input type="file" name="file" class="form-control form-control-sm" required>
                            <small class="text-muted"><?php echo t('max_file_size'); ?> : 10 MB</small>
                        </div>
                        <button class="btn btn-sm btn-primary" type="submit"><?php echo t('upload'); ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preventive Maintenances -->
    <?php if(!empty($preventives)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark"><i class="fas fa-calendar-check"></i> <?php echo t('preventive_maintenance'); ?></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('frequency'); ?></th><th><?php echo t('last_done'); ?></th><th><?php echo t('next_due'); ?></th><th><?php echo t('instructions'); ?></th><th><?php echo t('team'); ?></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($preventives as $pm): ?>
                            <tr>
                                <td><?php echo t('every') . ' ' . $pm['frequency_days'] . ' ' . t('days_s'); ?></td>
                                <td><?php echo $pm['last_done'] ? format_date_local($pm['last_done'], 'long', false) : t('never'); ?></td>
                                <td>
                                    <?php echo format_date_local($pm['next_due'], 'long', false); ?>
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

    <!-- Quick Actions -->
    <div class="col-12">
        <div class="action-buttons d-flex gap-2">
            <a href="?page=intervention_add&equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-primary"><?php echo t('new_intervention'); ?></a>
            <a href="?page=preventive_add&equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-warning"><?php echo t('plan_maintenance'); ?></a>
            <?php if ($contractor): ?>
                <a href="?page=contractor_detail&id=<?php echo $contractor['id']; ?>" class="btn btn-info">
                    <i class="fas fa-building"></i> <?php echo t('view_contractor'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Interventions History -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-history"></i> <?php echo t('interventions_history'); ?></div>
            <div class="card-body p-0">
                <?php if(empty($interventions)): ?>
                    <div class="text-center text-muted py-4"><?php echo t('no_interventions'); ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th><?php echo t('task_number'); ?></th><th><?php echo t('title'); ?></th><th><?php echo t('priority'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('date'); ?></th><th><?php echo t('duration'); ?></th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($interventions as $inv): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                    <td><span class="badge bg-<?php echo $inv['priority'] == 'critical' ? 'danger' : ($inv['priority'] == 'high' ? 'warning' : 'secondary'); ?>"><?php echo t($inv['priority']); ?></span></td>
                                    <td>
                                        <?php 
                                        if($inv['task_status'] == 'a_faire') echo t('to_do');
                                        elseif($inv['task_status'] == 'en_cours') echo t('in_progress');
                                        elseif($inv['task_status'] == 'termine') echo t('completed');
                                        else echo t('closed');
                                        ?>
                                    </td>
                                    <td><?php echo format_date_local($inv['created_at'], 'long', false); ?></td>
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

    <!-- Modifications History -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><i class="fas fa-edit"></i> <?php echo t('modifications_history'); ?></div>
            <div class="card-body">
                <?php if(empty($history)): ?>
                    <div class="text-muted"><?php echo t('no_history'); ?></div>
                <?php else: ?>
                    <?php foreach($history as $h):
                        $equipment_name = null;
                        if (preg_match('/Name: ([^,]+)/', $h['details'], $name_match)) {
                            $equipment_name = $name_match[1];
                        } elseif (preg_match('/Equipment ID: (\d+)/', $h['details'], $id_match)) {
                            $stmt_name = $pdo->prepare("SELECT name FROM equipment WHERE id = ?");
                            $stmt_name->execute([$id_match[1]]);
                            $equipment_name = $stmt_name->fetchColumn();
                        }
                        $icon = '';
                        $action_key = '';
                        switch($h['action']) {
                            case 'equipment_created': $icon = '🟢 '; $action_key = 'equipment_created'; break;
                            case 'equipment_updated': $icon = '✏️ '; $action_key = 'equipment_updated'; break;
                            case 'equipment_deleted': $icon = '🗑️ '; $action_key = 'equipment_deleted'; break;
                            case 'equipment_restored': $icon = '🔄 '; $action_key = 'equipment_restored'; break;
                            default: $icon = '📌 '; $action_key = 'modified_short';
                        }
                        $message = ($equipment_name) ? t($action_key) . ' : ' . htmlspecialchars($equipment_name) : t($action_key);
                    ?>
                        <div class="history-item mb-3">
                            <div class="d-flex justify-content-between">
                                <div><strong><?php echo $icon . $message; ?></strong></div>
                                <small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                            </div>
                            <small class="text-muted"><?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> (IP: <?php echo htmlspecialchars($h['ip_address'] ?? '-'); ?>)</small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    alert('✓ Path copied to clipboard:\n' + text);
}

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    const button = field.nextElementSibling;
    if (field.type === 'password') {
        field.type = 'text';
        button.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        field.type = 'password';
        button.innerHTML = '<i class="fas fa-eye"></i>';
    }
}
</script>