<?php
// pages/equipment_detail.php - Fiche détaillée d'un équipement
// Detailed view page for a single equipment item

// Include core functions and enforce user authentication
require_once __DIR__ . '/../includes/functions.php';
requireLogin();  // Redirect to login if user is not authenticated

// Get equipment ID from URL query parameter, sanitize to integer
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// If ID is missing or invalid, show error and exit early
if(!$id) {
    echo "<div class='alert alert-danger'>" . t('missing_id') . "</div>";
    return;
}

// Fetch all equipment data from database using the ID
$equipment = getEquipmentDetails($id);

// If equipment not found, show error and stop rendering
if(!$equipment) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Base URL for generating absolute paths (used for file downloads)
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// --- Load Attachments ---
// Fetch all files/documents attached to this equipment
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'equipment' AND parent_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();

// --- Load Interventions History ---
// Get maintenance/repair records for this equipment (most recent first)
$interventions = getMaintenanceHistory($id);

// --- Load Change Log (User Logs) ---
// Get all changes made to this equipment: creation, updates, deletion, restoration
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

// Define status labels for reuse
$status_labels = [
    'active' => '🟢 ' . t('active'),
    'maintenance' => '🟡 ' . t('maintenance'),
    'broken' => '🔴 ' . t('broken'),
    'retired' => '⚫ ' . t('retired')
];
?>

<!-- Page Layout: Bootstrap grid system with responsive columns -->
<div class="row g-3">
    
    <!-- LEFT COLUMN (col-md-6) -->
    <div class="col-md-6">
        <!-- General Information Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> <?php echo t('general_info'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <!-- Equipment Code -->
                    <tr><td style="width: 40%;"><strong><?php echo t('code'); ?></strong></td>
                    <td><?php echo htmlspecialchars($equipment['code']); ?></span>
                </tr>
                    <!-- Equipment Name -->
                    <tr><td><strong><?php echo t('name'); ?></strong></span>
                    <td><?php echo htmlspecialchars($equipment['name']); ?></span>
                </tr>
                    <!-- Type (or fallback message) -->
                    <tr><td><strong><?php echo t('type'); ?></strong></span>
                    <td><?php echo htmlspecialchars($equipment['type'] ?: t('not_specified')); ?></span>
                </tr>
                    <!-- Physical location -->
                    <tr><td><strong><?php echo t('location'); ?></strong></span>
                    <td><?php echo htmlspecialchars($equipment['location'] ?: t('not_specified')); ?></span>
                </tr>
                    <!-- Supplier/Vendor info -->
                    <tr><td><strong><?php echo t('supplier'); ?></strong></span>
                    <td><?php echo htmlspecialchars($equipment['supplier'] ?: t('not_specified')); ?></span>
                </tr>
                    <!-- Status with color-coded badge -->
                    <tr>
                        <td><strong><?php echo t('status'); ?></strong></span>
                        <td>
                            <span class="status-badge status-<?php echo $equipment['status']; ?>">
                                <?php echo $status_labels[$equipment['status']] ?? $equipment['status']; ?>
                            </span>
                        </span>
                    </tr>
                </table>
            </div>
        </div>
    </div>  <!-- End of LEFT COLUMN -->

    <!-- RIGHT COLUMN (col-md-6) -->
    <div class="col-md-6">
        
        <!-- Technical Specifications Card -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="fas fa-cogs"></i> <?php echo t('technical_specs'); ?>
            </div>
            <div class="card-body">
                <p class="mb-0"><?php echo nl2br(htmlspecialchars($equipment['technical_specs'] ?: t('not_specified'))); ?></p>
            </div>
        </div>
        
        <!-- Dates Card -->
        <div class="card" style="max-height:180px; overflow:auto;">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-calendar-alt"></i> <?php echo t('dates'); ?>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <!-- Purchase Date -->
                    <tr>
                        <td style="width: 40%;"><strong><?php echo t('purchase_date'); ?></strong></td>
                        <td><?php echo format_date_local($equipment['purchase_date'], 'long', false); ?></td>
                    </tr>
                    <!-- Warranty End Date with expiration warnings -->
                    <tr>
                        <td style="width: 40%;"><strong><?php echo t('warranty_end'); ?></strong></td>
                        <td><?php if($equipment['warranty_end']): ?>
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
                    <!-- Record creation timestamp -->
                    <tr>
                        <td style="width: 40%;"><strong><?php echo t('created_at'); ?></strong></td>
                        <td><?php echo format_date_local($equipment['created_at'], 'long', true); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
    </div>  <!-- End of RIGHT COLUMN -->

    <!-- ============================================ -->
    <!-- FULL WIDTH SECTIONS (span both columns)      -->
    <!-- ============================================ -->

    <!-- Full Width Section: Documents / Attachments -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-paperclip"></i> <?php echo t('documents'); ?>
            </div>
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if(!empty($att['external_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($att['external_path']); ?>" target="_blank" class="btn btn-info" title="<?php echo t('open_document'); ?>">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <button type="button" class="btn btn-secondary" title="Copy link to clipboard" onclick="copyToClipboard('<?php echo htmlspecialchars($att['external_path']); ?>')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" target="_blank" class="btn btn-secondary" title="<?php echo t('view'); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-info" title="Copy folder path to clipboard" onclick="copyToClipboard('<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2 small text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($att['external_path'] ?? ''); ?></div>
                                    
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor' || $_SESSION['user_id'] == $att['created_by']): ?>
                                        <div class="mt-2">
                                            <form action="api/delete_attachment.php" method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo t('delete_confirm'); ?>');">
                                                <input type="hidden" name="id" value="<?php echo $att['id']; ?>">
                                                <?php echo csrf_input(); ?>
                                                <button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i> <?php echo t('delete'); ?></button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add new document link form - only for admin/supervisor (with browse button) -->
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <hr>
                    <div class="mb-2">
                        <form id="add-attachment-link-form" action="api/add_attachment_link.php" method="post" class="mb-2">
                            <input type="hidden" name="parent_type" value="equipment">
                            <input type="hidden" name="parent_id" value="<?php echo $equipment['id']; ?>">
                            <?php echo csrf_input(); ?>
                            <div class="mb-2">
                                <label class="form-label small"><?php echo t('document_label'); ?></label>
                                <input type="text" name="label" class="form-control form-control-sm" placeholder="Technical sheet, Manual...">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small"><?php echo t('document_path'); ?></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="external_path" id="external_path" class="form-control" placeholder="https://... or C:\\path\\to\\file.pdf" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="browseFile()">
                                        <i class="fas fa-folder-open"></i> <?php echo t('browse'); ?>
                                    </button>
                                </div>
                                <small class="text-muted"><?php echo t('doc_path_placeholder'); ?></small>
                            </div>
                            <button class="btn btn-sm btn-primary" type="submit"><?php echo t('save'); ?></button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Preventive Maintenances Section (only shown if records exist) -->
    <?php if(!empty($preventives)): ?>
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-calendar-check"></i> <?php echo t('preventive_maintenance'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo t('frequency'); ?></th>
                                <th><?php echo t('last_done'); ?></th>
                                <th><?php echo t('next_due'); ?></th>
                                <th><?php echo t('instructions'); ?></th>
                                <th><?php echo t('team'); ?></th>
                            </tr>
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

    <!-- Quick Actions Bar -->
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

    <!-- Full Width Section: Interventions History -->
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

    <!-- Full Width Section: Modifications History (Audit Log) -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body">
                <?php if(empty($history)): ?>
                    <div class="text-muted"><?php echo t('no_history'); ?></div>
                <?php else: ?>
                    <?php foreach($history as $h):
                        // Récupérer le nom de l'équipement si présent dans le log ou via requête
                        $equipment_name = null;
                        if (preg_match('/Name: ([^,]+)/', $h['details'], $name_match)) {
                            $equipment_name = $name_match[1];
                        } elseif (preg_match('/Equipment ID: (\d+)/', $h['details'], $id_match)) {
                            // Ancien log : chercher le nom en base
                            $stmt_name = $pdo->prepare("SELECT name FROM equipment WHERE id = ?");
                            $stmt_name->execute([$id_match[1]]);
                            $equipment_name = $stmt_name->fetchColumn();
                        }

                        // Déterminer l'action et l'icône
                        $icon = '';
                        $action_key = '';
                        switch($h['action']) {
                            case 'equipment_created':
                                $icon = '🟢 ';
                                $action_key = 'equipment_created';
                                break;
                            case 'equipment_updated':
                                $icon = '✏️ ';
                                $action_key = 'equipment_updated';
                                break;
                            case 'equipment_deleted':
                                $icon = '🗑️ ';
                                $action_key = 'equipment_deleted';
                                break;
                            case 'equipment_restored':
                                $icon = '🔄 ';
                                $action_key = 'equipment_restored';
                                break;
                            default:
                                $icon = '📌 ';
                                $action_key = 'modified_short';
                        }

                        // Construire le message
                        if ($equipment_name) {
                            $message = t($action_key) . ' : ' . htmlspecialchars($equipment_name);
                        } else {
                            $message = t($action_key);
                        }
                    ?>
                        <div class="history-item mb-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong><?php echo $icon . $message; ?></strong>
                                </div>
                                <small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                            </div>
                            <small class="text-muted"><?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> (IP: <?php echo htmlspecialchars($h['ip_address'] ?? '-'); ?>)</small>
                            <!-- On n'affiche pas les détails bruts pour éviter le texte technique -->
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>  <!-- End of main grid row -->

<script>
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    alert('✓ Path copied to clipboard:\n' + text + '\n\nYou can now paste this path into File Explorer to open the folder.');
}

// Function to open file browser and fill the path field
function browseFile() {
    var input = document.createElement('input');
    input.type = 'file';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (file) {
            // Some browsers expose file.path, others only name.
            var path = file.path || file.name;
            document.getElementById('external_path').value = path;
        }
    };
    input.click();
}
</script>