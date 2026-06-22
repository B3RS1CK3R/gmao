<?php
// pages/contractor_detail.php - Détail d'un prestataire extérieur
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor', 'technician'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=contractors');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM contractors WHERE id = ?");
$stmt->execute([$id]);
$contractor = $stmt->fetch();

if (!$contractor) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Récupération des équipements assignés à ce prestataire
$assigned_equipments = [];
$stmt = $pdo->prepare("
    SELECT ce.*, e.code, e.name, e.type, e.location, e.status, e.warranty_end
    FROM contractor_equipment ce
    JOIN equipment e ON ce.equipment_id = e.id
    WHERE ce.contractor_id = ?
    ORDER BY e.code
");
$stmt->execute([$id]);
$assigned_equipments = $stmt->fetchAll();

// Historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('contractor_created', 'contractor_updated', 'contractor_deleted', 'contractor_restored')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Interventions associées à ce prestataire (si colonne contractor_id existe)
$interventions = [];
try {
    $stmt = $pdo->prepare("
        SELECT i.*, e.name as equipment_name
        FROM interventions i
        JOIN equipment e ON i.equipment_id = e.id
        WHERE i.contractor_id = ?
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$id]);
    $interventions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignorer si la colonne n'existe pas
}
?>

<style>
    .detail-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .detail-header {
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .info-row {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .label {
        font-weight: 600;
        width: 180px;
        display: inline-block;
        color: #495057;
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background: #28a745;
        color: white;
    }
    .status-inactive {
        background: #6c757d;
        color: white;
    }
    .status-suspended {
        background: #fd7e14;
        color: white;
    }
    .history-item {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .equipment-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
    }
    .equipment-status-active {
        background: #d4edda;
        color: #155724;
    }
    .equipment-status-maintenance {
        background: #fff3cd;
        color: #856404;
    }
    .equipment-status-broken {
        background: #f8d7da;
        color: #721c24;
    }
    .equipment-status-retired {
        background: #e2e3e5;
        color: #383d41;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-building"></i> <?php echo htmlspecialchars($contractor['company_name']); ?></h2>
        <div>
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                <a href="?page=contractor_edit&id=<?php echo $id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                </a>
                <?php if ($contractor['status'] != 'inactive'): ?>
                    <a href="?page=contractor_delete&id=<?php echo $id; ?>" class="btn btn-danger">
                        <i class="fas fa-trash"></i> <?php echo t('delete'); ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="?page=contractors" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <!-- Informations de l'entreprise -->
            <div class="detail-card">
                <div class="detail-header"><i class="fas fa-building"></i> <?php echo t('company_information'); ?></div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="label"><?php echo t('company_name'); ?></span>
                        <?php echo htmlspecialchars($contractor['company_name']); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('siret'); ?></span>
                        <?php echo htmlspecialchars($contractor['siret'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('vat_number'); ?></span>
                        <?php echo htmlspecialchars($contractor['vat_number'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('phone'); ?></span>
                        <?php echo htmlspecialchars($contractor['phone'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('email'); ?></span>
                        <?php if (!empty($contractor['email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($contractor['email']); ?>"><?php echo htmlspecialchars($contractor['email']); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('website'); ?></span>
                        <?php if (!empty($contractor['website'])): ?>
                            <a href="<?php echo htmlspecialchars($contractor['website']); ?>" target="_blank"><?php echo htmlspecialchars($contractor['website']); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('address'); ?></span>
                        <?php 
                        $address_parts = [];
                        if (!empty($contractor['address'])) $address_parts[] = $contractor['address'];
                        if (!empty($contractor['postal_code'])) $address_parts[] = $contractor['postal_code'];
                        if (!empty($contractor['city'])) $address_parts[] = $contractor['city'];
                        if (!empty($contractor['country'])) $address_parts[] = $contractor['country'];
                        echo htmlspecialchars(!empty($address_parts) ? implode(', ', $address_parts) : '-');
                        ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('status'); ?></span>
                        <span class="status-badge status-<?php echo $contractor['status']; ?>">
                            <?php 
                            $status_labels = [
                                'active' => '🟢 ' . t('active'),
                                'inactive' => '⚫ ' . t('inactive'),
                                'suspended' => '🟠 ' . t('suspended')
                            ];
                            echo $status_labels[$contractor['status']] ?? $contractor['status'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #17a2b8, #0c6b7e);">
                    <i class="fas fa-user"></i> <?php echo t('contact_information'); ?>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="label"><?php echo t('contact_firstname'); ?></span>
                        <?php echo htmlspecialchars($contractor['contact_firstname'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('contact_lastname'); ?></span>
                        <?php echo htmlspecialchars($contractor['contact_lastname'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('contact_phone'); ?></span>
                        <?php echo htmlspecialchars($contractor['contact_phone'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('contact_email'); ?></span>
                        <?php if (!empty($contractor['contact_email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($contractor['contact_email']); ?>"><?php echo htmlspecialchars($contractor['contact_email']); ?></a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Détails du contrat -->
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #fd7e14, #e06a0a);">
                    <i class="fas fa-tools"></i> <?php echo t('contract_details'); ?>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="label"><?php echo t('specialty'); ?></span>
                        <?php echo htmlspecialchars($contractor['specialty'] ?: '-'); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('notes'); ?></span>
                        <?php echo nl2br(htmlspecialchars($contractor['notes'] ?: '-')); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('contract_start'); ?></span>
                        <?php echo $contractor['contract_start'] ? format_date_local($contractor['contract_start'], 'long', false) : '-'; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('contract_end'); ?></span>
                        <?php echo $contractor['contract_end'] ? format_date_local($contractor['contract_end'], 'long', false) : '-'; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('created_at'); ?></span>
                        <?php echo format_date_local($contractor['created_at'], 'long', true); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('updated_at'); ?></span>
                        <?php echo format_date_local($contractor['updated_at'], 'long', true); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- ========== ÉQUIPEMENTS ASSIGNÉS ========== -->
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <i class="fas fa-microchip"></i> <?php echo t('assigned_equipment'); ?>
                    <span class="badge bg-light text-dark ms-2"><?php echo count($assigned_equipments); ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($assigned_equipments)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                            <?php echo t('no_equipment_assigned'); ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo t('code'); ?></th>
                                        <th><?php echo t('name'); ?></th>
                                        <th><?php echo t('type'); ?></th>
                                        <th><?php echo t('status'); ?></th>
                                        <th><?php echo t('warranty_end'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_equipments as $eq): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($eq['code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($eq['name']); ?></td>
                                        <td><?php echo htmlspecialchars($eq['type'] ?: '-'); ?></td>
                                        <td>
                                            <span class="equipment-badge equipment-status-<?php echo $eq['status']; ?>">
                                                <?php 
                                                $eq_status_labels = [
                                                    'active' => '🟢 ' . t('active'),
                                                    'maintenance' => '🟡 ' . t('maintenance'),
                                                    'broken' => '🔴 ' . t('broken'),
                                                    'retired' => '⚫ ' . t('retired')
                                                ];
                                                echo $eq_status_labels[$eq['status']] ?? $eq['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($eq['warranty_end'])): ?>
                                                <?php 
                                                $warranty_ts = strtotime($eq['warranty_end']);
                                                $now = time();
                                                if ($warranty_ts < $now) {
                                                    echo '<span class="text-danger">' . format_date_local($eq['warranty_end'], 'short') . ' ⚠️</span>';
                                                } elseif ($warranty_ts < strtotime('+30 days')) {
                                                    echo '<span class="text-warning">' . format_date_local($eq['warranty_end'], 'short') . ' ⏳</span>';
                                                } else {
                                                    echo format_date_local($eq['warranty_end'], 'short');
                                                }
                                                ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique -->
            <?php if (!empty($history)): ?>
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #6c757d, #495057);">
                    <i class="fas fa-history"></i> <?php echo t('modification_history'); ?>
                </div>
                <div class="card-body p-3">
                    <?php foreach ($history as $h): ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <?php
                                    $action_icons = [
                                        'contractor_created' => '🟢 ' . t('created'),
                                        'contractor_updated' => '✏️ ' . t('updated'),
                                        'contractor_deleted' => '🗑️ ' . t('deleted'),
                                        'contractor_restored' => '🔄 ' . t('restored')
                                    ];
                                    echo $action_icons[$h['action']] ?? $h['action'];
                                    ?>
                                </span>
                                <small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                            </div>
                            <small class="text-muted"><?php echo t('by') . ' : ' . htmlspecialchars($h['username'] ?? 'Inconnu'); ?></small>
                            <?php if (!empty($h['details'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($h['details']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Interventions associées -->
            <?php if (!empty($interventions)): ?>
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #0d6efd, #0a58ca);">
                    <i class="fas fa-tasks"></i> <?php echo t('related_interventions'); ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('task_number'); ?></th>
                                    <th><?php echo t('equipment'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventions as $inv): ?>
                                <tr onclick="window.location.href='?page=intervention_view&id=<?php echo $inv['id']; ?>'" style="cursor: pointer;">
                                    <td><?php echo htmlspecialchars($inv['task_number'] ?? '#' . $inv['id']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                                    <td>
                                        <?php 
                                        $inv_status_labels = [
                                            'a_faire' => '⚪ ' . t('to_do'),
                                            'en_cours' => '🔵 ' . t('in_progress'),
                                            'termine' => '🟢 ' . t('completed'),
                                            'cloturee' => '⚫ ' . t('closed'),
                                            'cancelled' => '🔴 ' . t('cancelled')
                                        ];
                                        echo $inv_status_labels[$inv['task_status']] ?? $inv['task_status'];
                                        ?>
                                    </td>
                                    <td><?php echo format_date_local($inv['created_at'], 'short', true); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>