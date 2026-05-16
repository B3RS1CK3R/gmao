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

// Récupération des pièces jointes (documents, images)
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'intervention' AND parent_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$attachments = $stmt->fetchAll();
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

// Génération du QR Code pour l'équipement
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("http://" . $_SERVER['HTTP_HOST'] . "/gmao_GEMINI/index.php?page=equipment_detail&id=" . $intervention['equipment_id']);
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
    .qr-code {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    .qr-code img {
        max-width: 150px;
        height: auto;
        margin-bottom: 10px;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-tools"></i> 
            Intervention Details : <?php echo htmlspecialchars($intervention['title']); ?>
            <small class="text-muted">(<?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?>)</small>
        </h2>
        <div>
            <a href="?page=interventions" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor')): ?>
                <a href="?page=interventions&action=edit&id=<?php echo $intervention['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ROW with 2 columns -->
    <div class="row">
        
        <!-- LEFT COLUMN (col-md-5) -->
        <div class="col-md-5">
            
            <!-- Card: Identification -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-info-circle"></i> Identification
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong>Task Number</strong></td>
                            <td><?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Title</strong></td>
                            <td><?php echo htmlspecialchars($intervention['title']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created At</strong></td>
                            <td><?php echo format_date_us($intervention['created_at'], true); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Created By</strong></td>
                            <td><?php echo htmlspecialchars($intervention['created_by_name'] ?? $intervention['reported_by']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Priority</strong></td>
                            <td><span class="priority-badge priority-<?php echo $intervention['priority']; ?>"><?php echo ucfirst($intervention['priority']); ?></span></td>
                        </tr>
                        <tr>
                            <td><strong>Status</strong></td>
                            <td>
                                <span class="status-badge status-<?php echo $intervention['task_status']; ?>">
                                <?php 
                                $status_labels = [
                                    'a_faire' => 'To Do',
                                    'en_cours' => 'In Progress',
                                    'termine' => 'Completed',
                                    'cloturee' => 'Closed'
                                ];
                                echo $status_labels[$intervention['task_status']] ?? $intervention['task_status'];
                                ?>
                                </span>
                            </td>
                        </tr>
                    </div>
                </div>
            </div>
            
            <!-- Card: Equipment -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-microchip"></i> Equipment
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong>Name</strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Code</strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_code']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Location</strong></td>
                            <td><?php echo htmlspecialchars($intervention['equipment_location'] ?: 'Not specified'); ?></td>
                        </tr>
                        <?php if($intervention['zone']): ?>
                        <tr>
                            <td><strong>Zone</strong></td>
                            <td><?php echo htmlspecialchars($intervention['zone']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if($intervention['localisation']): ?>
                        <tr>
                            <td><strong>Localisation</strong></td>
                            <td><?php echo htmlspecialchars($intervention['localisation']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                    <div class="mt-3">
                        <a href="?page=equipment_detail&id=<?php echo $intervention['equipment_id']; ?>" class="btn btn-primary w-100">
                            <i class="fas fa-eye"></i> View Equipment
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Card: QR Code -->
            <div class="info-card">
                <div class="card-header-custom info">
                    <i class="fas fa-qrcode"></i> QR Code
                </div>
                <div class="card-body p-4">
                    <div class="qr-code">
                        <img src="<?php echo $qrUrl; ?>" alt="QR Code for <?php echo htmlspecialchars($intervention['equipment_name']); ?>">
                        <p class="mb-0 small text-muted">
                            Scan this QR code to view equipment details
                        </p>
                        <div class="mt-2">
                            <button onclick="copyQrUrl()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-copy"></i> Copy URL
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Card: Technician -->
            <div class="info-card">
                <div class="card-header-custom <?php echo $intervention['intervenant_id'] ? 'success' : 'warning'; ?>">
                    <i class="fas fa-user-cog"></i> Technician
                </div>
                <div class="card-body p-4">
                    <?php if($intervention['firstname']): ?>
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td style="width: 40%;"><strong>Name</strong></td>
                                <td><?php echo htmlspecialchars($intervention['firstname'] . ' ' . $intervention['lastname']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Specialty</strong></td>
                                <td><?php echo htmlspecialchars($intervention['specialty']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone</strong></td>
                                <td><?php echo htmlspecialchars($intervention['technician_phone'] ?: 'Not provided'); ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No technician assigned</p>
                    <?php endif; ?>
                    
                    <?php if($intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee' && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor')): ?>
                        <div class="mt-3">
                            <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" data-bs-target="#assignModal">
                                <i class="fas fa-user-plus"></i> Assign Technician
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
                    <i class="fas fa-calendar-alt"></i> Planning
                </div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td style="width: 40%;"><strong>Task Type</strong></td>
                            <td>
                                <?php 
                                $type_labels = [
                                    'revision' => 'Revision',
                                    'depannage' => 'Repair',
                                    'installation' => 'Installation',
                                    'maintenance_preventive' => 'Preventive',
                                    'controle' => 'Inspection',
                                    'autre' => 'Other'
                                ];
                                $type_text = $type_labels[$intervention['task_type']] ?? '';
                                if(!$type_text) {
                                    $type_text = $intervention['type'] == 'corrective' ? 'Corrective' : ($intervention['type'] == 'preventive' ? 'Preventive' : 'Emergency');
                                }
                                echo $type_text;
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Planned Date</strong></td>
                            <td>
                                <?php echo $intervention['intervention_date'] ? format_date_us($intervention['intervention_date'], false) : 'Not planned'; ?>
                                <?php if(strtotime($intervention['intervention_date']) < time() && $intervention['task_status'] != 'termine' && $intervention['task_status'] != 'cloturee'): ?>
                                    <span class="badge bg-danger ms-2">Overdue</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Planned Duration</strong></td>
                            <td><?php echo htmlspecialchars($intervention['planned_duration'] ?? 'Not specified'); ?></td>
                        </tr>
                        <?php if($intervention['duration_hours']): ?>
                        <tr>
                            <td><strong>Actual Duration</strong></td>
                            <td><?php echo $intervention['duration_hours']; ?> hours<\/td>
                        </tr>
                        <?php endif; ?>
                        <?php if($intervention['completed_date']): ?>
                        <tr>
                            <td><strong>Completion Date</strong></td>
                            <td><?php echo format_date_us($intervention['completed_date'], true); ?><\/td>
                        </tr>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Card: Description -->
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-clipboard-list"></i> Description
                </div>
                <div class="card-body p-4">
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($intervention['description'] ?: 'No description')); ?></p>
                </div>
            </div>
            
            <!-- Card: Completion Report -->
            <?php if($intervention['completion_report']): ?>
            <div class="info-card">
                <div class="card-header-custom success">
                    <i class="fas fa-file-alt"></i> Completion Report
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
                    <i class="fas fa-boxes"></i> Parts Used
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Part Number</th><th>Name</th><th>Qty</th><th>Unit Price</th><th>Total</th>
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
                                    <td colspan="4" class="text-end"><strong>Total</strong></span>
                                    <td><strong><?php echo number_format($total_cost, 2); ?> €</strong></span>
                                </tr>
                            </tbody>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Card: History -->
            <?php if(!empty($history)): ?>
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-history"></i> Modifications History
                </div>
                <div class="card-body p-3">
                    <?php foreach($history as $h): ?>
                    <div class="history-item">
                        <div class="d-flex justify-content-between">
                            <span><?php echo $h['action']; ?></span>
                            <small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                        </div>
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
                    <a href="?page=interventions&action=complete&id=<?php echo $intervention['id']; ?>" class="btn btn-success">Complete</a>
                <?php endif; ?>
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <a href="?page=interventions&action=edit&id=<?php echo $intervention['id']; ?>" class="btn btn-warning">Edit</a>
                    <button type="button" class="btn btn-danger" onclick="confirmCancel()">Cancel</button>
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
                <h5 class="modal-title">Assign Technician</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=interventions&action=assign&id=<?php echo $intervention['id']; ?>">
                <div class="modal-body">
                    <p><strong>Intervention:</strong> <?php echo htmlspecialchars($intervention['title']); ?></p>
                    <div class="mb-3">
                        <label class="form-label">Technician</label>
                        <select name="technician_id" class="form-select" required>
                            <option value="">-- Select a technician --</option>
                            <?php foreach($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmCancel() {
    if(confirm('Are you sure you want to cancel this intervention?')) {
        var password = prompt('Please enter your password to confirm:');
        if(password) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=interventions&action=delete&id=<?php echo $intervention['id']; ?>';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'confirm_password';
            input.value = password;
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function copyQrUrl() {
    const url = '<?php echo "http://" . $_SERVER['HTTP_HOST'] . "/gmao_GEMINI/index.php?page=equipment_detail&id=" . $intervention['equipment_id']; ?>';
    navigator.clipboard.writeText(url).then(function() {
        alert('Equipment URL copied to clipboard!');
    }, function() {
        alert('Failed to copy URL');
    });
}
</script>