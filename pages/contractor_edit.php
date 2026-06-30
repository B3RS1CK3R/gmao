<?php
// pages/contractor_edit.php - Modification d'un prestataire extérieur avec assignation d'équipements
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
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

// Récupération des équipements déjà assignés à ce prestataire
$assigned_equipments = [];
$stmt = $pdo->prepare("SELECT equipment_id FROM contractor_equipment WHERE contractor_id = ?");
$stmt->execute([$id]);
while ($row = $stmt->fetch()) {
    $assigned_equipments[] = $row['equipment_id'];
}

// Récupération de tous les équipements actifs
$equipments = $pdo->query("SELECT id, code, name, warranty_end FROM equipment WHERE status = 'active' ORDER BY code")->fetchAll();

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #fd7e14, #e06a0a); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
    .btn-warning:hover { background: #e06a0a; color: white; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary:hover { background: #5a6268; }
    .required { color: #dc3545; }
    .section-title { font-size: 14px; font-weight: 600; color: #495057; margin: 15px 0 10px 0; padding: 5px 10px; background: #e9ecef; border-radius: 6px; }
    .equipment-list { max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px; }
    .equipment-item { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 15px; }
    .equipment-item:last-child { border-bottom: none; }
    .equipment-item:hover { background: #f8f9fa; }
    .equipment-item .form-check { margin: 0; }
    .select-all-row { background: #f8f9fa; padding: 10px 15px; border-radius: 6px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; }
    .badge-warranty { font-size: 11px; padding: 3px 10px; }
    .badge-warranty-expired { background: #dc3545; color: white; }
    .badge-warranty-soon { background: #ffc107; color: #333; }
    .badge-warranty-ok { background: #28a745; color: white; }
    .equipment-assigned { background: #d4edda !important; border-left: 3px solid #28a745; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_contractor'); ?> : <?php echo htmlspecialchars($contractor['company_name']); ?></h2>
        <a href="?page=contractors" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_contractor'); ?></div>
        <div class="card-body p-4">
            <form method="POST" action="?page=contractor_edit_action" id="contractorForm">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <!-- Informations de l'entreprise -->
                <div class="section-title"><i class="fas fa-building"></i> <?php echo t('company_information'); ?></div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label"><?php echo t('company_name'); ?> <span class="required">*</span></label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($contractor['company_name']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="status" class="form-select">
                            <option value="active" <?php if ($contractor['status'] == 'active') echo 'selected'; ?>><?php echo t('active'); ?></option>
                            <option value="inactive" <?php if ($contractor['status'] == 'inactive') echo 'selected'; ?>><?php echo t('inactive'); ?></option>
                            <option value="suspended" <?php if ($contractor['status'] == 'suspended') echo 'selected'; ?>><?php echo t('suspended'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('siret'); ?></label>
                        <input type="text" name="siret" class="form-control" value="<?php echo htmlspecialchars($contractor['siret']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('vat_number'); ?></label>
                        <input type="text" name="vat_number" class="form-control" value="<?php echo htmlspecialchars($contractor['vat_number']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('phone'); ?></label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($contractor['phone']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('email'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($contractor['email']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('website'); ?></label>
                        <input type="text" name="website" class="form-control" value="<?php echo htmlspecialchars($contractor['website']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('address'); ?></label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($contractor['address']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('postal_code'); ?></label>
                        <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($contractor['postal_code']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('city'); ?></label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($contractor['city']); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('country'); ?></label>
                        <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($contractor['country']); ?>">
                    </div>
                </div>

                <!-- Contact -->
                <div class="section-title"><i class="fas fa-user"></i> <?php echo t('contact_information'); ?></div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_firstname'); ?></label>
                        <input type="text" name="contact_firstname" class="form-control" value="<?php echo htmlspecialchars($contractor['contact_firstname']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_lastname'); ?></label>
                        <input type="text" name="contact_lastname" class="form-control" value="<?php echo htmlspecialchars($contractor['contact_lastname']); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_phone'); ?></label>
                        <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($contractor['contact_phone']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_email'); ?></label>
                        <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($contractor['contact_email']); ?>">
                    </div>
                </div>

                <!-- Contrat -->
                <div class="section-title"><i class="fas fa-tools"></i> <?php echo t('contract_details'); ?></div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('specialty'); ?></label>
                        <input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($contractor['specialty']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contract_number'); ?></label>
                        <input type="text" name="contract_number" class="form-control" value="<?php echo htmlspecialchars($contractor['contract_number'] ?? ''); ?>" placeholder="<?php echo t('contract_number_placeholder'); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contract_start'); ?></label>
                        <input type="date" name="contract_start" class="form-control" value="<?php echo $contractor['contract_start']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contract_end'); ?></label>
                        <input type="date" name="contract_end" class="form-control" value="<?php echo $contractor['contract_end']; ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('notes'); ?></label>
                        <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars($contractor['notes']); ?>">
                    </div>
                </div>

                <!-- ========== PARAMÈTRES DES ALERTES ========== -->
                <div class="section-title"><i class="fas fa-bell"></i> <?php echo t('alert_settings'); ?></div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_enabled" id="alert_enabled" value="1" <?php echo ($contractor['alert_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_enabled">
                                <strong><?php echo t('enable_alerts'); ?></strong>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row" id="alertSettings">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('alert_days_before_1'); ?></label>
                        <div class="input-group">
                            <input type="number" name="alert_days_before_1" class="form-control" value="<?php echo $contractor['alert_days_before_1'] ?? 90; ?>" min="1">
                            <span class="input-group-text"><?php echo t('days_s'); ?></span>
                        </div>
                        <small class="text-muted"><?php echo t('alert_days_before_1_help'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('alert_days_before_2'); ?></label>
                        <div class="input-group">
                            <input type="number" name="alert_days_before_2" class="form-control" value="<?php echo $contractor['alert_days_before_2'] ?? 21; ?>" min="1">
                            <span class="input-group-text"><?php echo t('days_s'); ?></span>
                        </div>
                        <small class="text-muted"><?php echo t('alert_days_before_2_help'); ?></small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-tools"></i> <?php echo t('alert_for_interventions'); ?></label>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_sidebar_intervention" id="alert_sidebar_intervention" value="1" <?php echo ($contractor['alert_sidebar_intervention'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_sidebar_intervention">
                                <i class="fas fa-bell text-primary"></i> <?php echo t('alert_sidebar'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_popup_intervention" id="alert_popup_intervention" value="1" <?php echo ($contractor['alert_popup_intervention'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_popup_intervention">
                                <i class="fas fa-window-restore text-success"></i> <?php echo t('alert_popup'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_email_intervention" id="alert_email_intervention" value="1" <?php echo ($contractor['alert_email_intervention'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_email_intervention">
                                <i class="fas fa-envelope text-info"></i> <?php echo t('alert_email'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold"><i class="fas fa-calendar-check"></i> <?php echo t('alert_for_maintenances'); ?></label>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_sidebar_maintenance" id="alert_sidebar_maintenance" value="1" <?php echo ($contractor['alert_sidebar_maintenance'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_sidebar_maintenance">
                                <i class="fas fa-bell text-primary"></i> <?php echo t('alert_sidebar'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_popup_maintenance" id="alert_popup_maintenance" value="1" <?php echo ($contractor['alert_popup_maintenance'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_popup_maintenance">
                                <i class="fas fa-window-restore text-success"></i> <?php echo t('alert_popup'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="alert_email_maintenance" id="alert_email_maintenance" value="1" <?php echo ($contractor['alert_email_maintenance'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="alert_email_maintenance">
                                <i class="fas fa-envelope text-info"></i> <?php echo t('alert_email'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ========== ASSIGNATION DES ÉQUIPEMENTS ========== -->
                <div class="section-title"><i class="fas fa-microchip"></i> <?php echo t('assign_equipment_to_contractor'); ?></div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <?php echo t('assign_equipment_help'); ?>
                            <small class="d-block text-muted mt-1"><?php echo t('assigned_equipment_highlight'); ?></small>
                        </div>
                        
                        <div class="select-all-row">
                            <span><strong><?php echo t('select_all'); ?></strong></span>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllEquipments(true)">
                                    <i class="fas fa-check-double"></i> <?php echo t('select_all'); ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllEquipments(false)">
                                    <i class="fas fa-times"></i> <?php echo t('deselect_all'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="equipment-list">
                            <?php if (empty($equipments)): ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    <?php echo t('no_equipment_available'); ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($equipments as $eq): 
                                    $is_assigned = in_array($eq['id'], $assigned_equipments);
                                    $warranty_status = '';
                                    $warranty_label = '';
                                    if (!empty($eq['warranty_end'])) {
                                        $now = time();
                                        $warranty_ts = strtotime($eq['warranty_end']);
                                        if ($warranty_ts < $now) {
                                            $warranty_status = 'badge-warranty-expired';
                                            $warranty_label = '🔴 ' . t('expired');
                                        } elseif ($warranty_ts < strtotime('+30 days')) {
                                            $warranty_status = 'badge-warranty-soon';
                                            $warranty_label = '🟡 ' . t('expiring_soon');
                                        } else {
                                            $warranty_status = 'badge-warranty-ok';
                                            $warranty_label = '🟢 ' . t('ok');
                                        }
                                    } else {
                                        $warranty_status = 'badge-warranty-ok';
                                        $warranty_label = '⚪ ' . t('not_specified');
                                    }
                                ?>
                                <div class="equipment-item <?php echo $is_assigned ? 'equipment-assigned' : ''; ?>">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input equipment-check" name="equipment_ids[]" value="<?php echo $eq['id']; ?>" id="eq_<?php echo $eq['id']; ?>" <?php echo $is_assigned ? 'checked' : ''; ?>>
                                    </div>
                                    <div style="flex: 1;">
                                        <strong><?php echo htmlspecialchars($eq['code']); ?></strong>
                                        <span class="text-muted">- <?php echo htmlspecialchars($eq['name']); ?></span>
                                        <?php if ($is_assigned): ?>
                                            <span class="badge bg-success ms-2"><?php echo t('assigned'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge badge-warranty <?php echo $warranty_status; ?>">
                                            <?php echo $warranty_label; ?>
                                            <?php if (!empty($eq['warranty_end'])): ?>
                                                <small>(<?php echo format_date_local($eq['warranty_end'], 'short'); ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=contractors" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectAllEquipments(select) {
    const checkboxes = document.querySelectorAll('.equipment-check');
    checkboxes.forEach(cb => {
        cb.checked = select;
    });
}
</script>