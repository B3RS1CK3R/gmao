<?php
// pages/contractor_add.php - Ajout d'un prestataire extérieur avec assignation d'équipements
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

// Récupération des équipements actifs pour assignation
$equipments = $pdo->query("SELECT id, code, name, warranty_end FROM equipment WHERE status = 'active' ORDER BY code")->fetchAll();
?>
<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-primary { background: #6f42c1; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-primary:hover { background: #5a32a3; }
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
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_contractor'); ?></h2>
        <a href="?page=contractors" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-plus-circle"></i> <?php echo t('add_contractor'); ?></div>
        <div class="card-body p-4">
            <form method="POST" action="?page=contractor_add_action" id="contractorForm">
                <?= csrf_input() ?>
                
                <!-- Informations de l'entreprise -->
                <div class="section-title"><i class="fas fa-building"></i> <?php echo t('company_information'); ?></div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label"><?php echo t('company_name'); ?> <span class="required">*</span></label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="status" class="form-select">
                            <option value="active"><?php echo t('active'); ?></option>
                            <option value="inactive"><?php echo t('inactive'); ?></option>
                            <option value="suspended"><?php echo t('suspended'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('siret'); ?></label>
                        <input type="text" name="siret" class="form-control" placeholder="123 456 789 00012" maxlength="14">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('vat_number'); ?></label>
                        <input type="text" name="vat_number" class="form-control" placeholder="FR12345678901">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('phone'); ?></label>
                        <input type="text" name="phone" class="form-control" placeholder="01 23 45 67 89">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('email'); ?></label>
                        <input type="email" name="email" class="form-control" placeholder="contact@entreprise.fr">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('website'); ?></label>
                        <input type="text" name="website" class="form-control" placeholder="https://www.entreprise.fr">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('address'); ?></label>
                        <input type="text" name="address" class="form-control" placeholder="10 rue de la République">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('postal_code'); ?></label>
                        <input type="text" name="postal_code" class="form-control" placeholder="75001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('city'); ?></label>
                        <input type="text" name="city" class="form-control" placeholder="Paris">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('country'); ?></label>
                        <input type="text" name="country" class="form-control" value="France">
                    </div>
                </div>

                <!-- Contact -->
                <div class="section-title"><i class="fas fa-user"></i> <?php echo t('contact_information'); ?></div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_firstname'); ?></label>
                        <input type="text" name="contact_firstname" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_lastname'); ?></label>
                        <input type="text" name="contact_lastname" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_phone'); ?></label>
                        <input type="text" name="contact_phone" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contact_email'); ?></label>
                        <input type="email" name="contact_email" class="form-control">
                    </div>
                </div>

                <!-- Contrat -->
                <div class="section-title"><i class="fas fa-tools"></i> <?php echo t('contract_details'); ?></div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('specialty'); ?></label>
                        <input type="text" name="specialty" class="form-control" placeholder="<?php echo t('specialty_placeholder'); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('notes'); ?></label>
                        <input type="text" name="notes" class="form-control" placeholder="<?php echo t('notes_placeholder'); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contract_start'); ?></label>
                        <input type="date" name="contract_start" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('contract_end'); ?></label>
                        <input type="date" name="contract_end" class="form-control">
                    </div>
                </div>

                <!-- ========== ASSIGNATION DES ÉQUIPEMENTS ========== -->
                <div class="section-title"><i class="fas fa-microchip"></i> <?php echo t('assign_equipment_to_contractor'); ?></div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <?php echo t('assign_equipment_help'); ?>
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
                                <div class="equipment-item">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input equipment-check" name="equipment_ids[]" value="<?php echo $eq['id']; ?>" id="eq_<?php echo $eq['id']; ?>">
                                    </div>
                                    <div style="flex: 1;">
                                        <strong><?php echo htmlspecialchars($eq['code']); ?></strong>
                                        <span class="text-muted">- <?php echo htmlspecialchars($eq['name']); ?></span>
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
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
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