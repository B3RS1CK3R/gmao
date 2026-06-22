<?php
// pages/settings.php - Configuration du système (admin only)
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$message = '';
$error = '';

// S'assurer que la table system_settings existe
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `system_settings` (
        `setting_key` VARCHAR(50) PRIMARY KEY,
        `setting_value` TEXT NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'timezone_offset'");
$current_offset = $stmt->fetchColumn();
if (!$current_offset) {
    $current_offset = '+02:00';
}

// Récupérer les formats de numéros de tâches
$stmt = $pdo->query("SELECT * FROM task_format_settings");
$formats = [];
while ($row = $stmt->fetch()) {
    $formats[$row['type']] = $row;
}

// ========== TRAITEMENT SUPPRESSION DÉFINITIVE ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['permanent_delete'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } elseif (empty($_POST['confirm_password'])) {
        $error = t('password_required');
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($_POST['confirm_password'], $user['password'])) {
            $error = t('password_error');
        } else {
            $result = processPermanentDeletion($pdo, $_POST, $_SESSION['user_id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// ========== TRAITEMENT RÉINITIALISATION BASE DE DONNÉES ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_database'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } elseif (empty($_POST['reset_password'])) {
        $error = t('password_required');
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($_POST['reset_password'], $user['password'])) {
            $error = t('password_error');
        } else {
            $result = processDatabaseReset($pdo, $_POST, $_SESSION['user_id']);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Récupération des éléments supprimés
$deleted_interventions = $pdo->query("
    SELECT i.id, i.task_number, i.title, e.name as equipment_name
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    WHERE i.task_status = 'cancelled'
    ORDER BY i.task_number
")->fetchAll();

$deleted_preventives = $pdo->query("
    SELECT pm.id, pm.task_number, pm.title, e.name as equipment_name
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    WHERE pm.task_status = 'cancelled'
    ORDER BY pm.task_number
")->fetchAll();

$deleted_equipments = $pdo->query("
    SELECT id, code, name, type
    FROM equipment
    WHERE status = 'retired'
    ORDER BY code
")->fetchAll();

// Compter le nombre d'éléments par table
$count_equipment = $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$count_interventions = $pdo->query("SELECT COUNT(*) FROM interventions")->fetchColumn();
$count_preventives = $pdo->query("SELECT COUNT(*) FROM preventive_maintenance")->fetchColumn();
$count_technicians = $pdo->query("SELECT COUNT(*) FROM technicians")->fetchColumn();
$count_stock = $pdo->query("SELECT COUNT(*) FROM spare_parts")->fetchColumn();

// ========== TRAITEMENT DES AUTRES FORMULAIRES ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_interv'])) {
        $interv_prefix = trim($_POST['interv_prefix']);
        $interv_year = isset($_POST['interv_use_year']) ? 1 : 0;
        $interv_month = isset($_POST['interv_use_month']) ? 1 : 0;
        $interv_digits = (int)$_POST['interv_digits'];
        $reset_year = isset($_POST['interv_reset_year']) ? 1 : 0;
        $reset_month = isset($_POST['interv_reset_month']) ? 1 : 0;
        $pdo->prepare("UPDATE task_format_settings SET prefix = ?, use_year = ?, use_month = ?, digits = ?, reset_on_year_change = ?, reset_on_month_change = ? WHERE type = 'intervention'")
            ->execute([$interv_prefix, $interv_year, $interv_month, $interv_digits, $reset_year, $reset_month]);
        $message = t('save_success');
    }
    if (isset($_POST['save_prev'])) {
        $prev_prefix = trim($_POST['prev_prefix']);
        $prev_year = isset($_POST['prev_use_year']) ? 1 : 0;
        $prev_month = isset($_POST['prev_use_month']) ? 1 : 0;
        $prev_digits = (int)$_POST['prev_digits'];
        $reset_year = isset($_POST['prev_reset_year']) ? 1 : 0;
        $reset_month = isset($_POST['prev_reset_month']) ? 1 : 0;
        $pdo->prepare("UPDATE task_format_settings SET prefix = ?, use_year = ?, use_month = ?, digits = ?, reset_on_year_change = ?, reset_on_month_change = ? WHERE type = 'preventive'")
            ->execute([$prev_prefix, $prev_year, $prev_month, $prev_digits, $reset_year, $reset_month]);
        $message = t('save_success');
    }
    if (isset($_POST['save_timezone_offset'])) {
        $new_offset = $_POST['timezone_offset'];
        if (preg_match('/^[+-]\d{2}:\d{2}$/', $new_offset)) {
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('timezone_offset', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$new_offset, $new_offset]);
            $message = t('timezone_updated') . " : $new_offset";
            $pdo->exec("SET time_zone = '$new_offset'");
            $current_offset = $new_offset;
        } else {
            $error = t('timezone_invalid');
        }
    }

    $stmt = $pdo->query("SELECT * FROM task_format_settings");
    $formats = [];
    while ($row = $stmt->fetch()) $formats[$row['type']] = $row;
}
?>

<style>
    .config-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .config-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .config-header i {
        margin-right: 8px;
    }
    .config-header .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        vertical-align: middle;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-check-input {
        margin-right: 8px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .preview-code {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 8px;
        font-family: monospace;
        font-size: 14px;
        margin-top: 10px;
    }
    .info-text {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 8px 12px;
        border-radius: 8px;
        margin: 10px 0;
        font-size: 13px;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-success:hover {
        background: #218838;
    }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .delete-card {
        border: 2px solid #dc3545;
    }
    .delete-card .config-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    .reset-card {
        border: 2px solid #fd7e14;
    }
    .reset-card .config-header {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
    }
    .deleted-item-row {
        transition: background 0.2s;
    }
    .deleted-item-row:hover {
        background: #f8f9fa;
    }
    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin: 10px 0 5px 0;
        padding: 5px 10px;
        background: #e9ecef;
        border-radius: 6px;
    }
    .reset-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    .reset-item:last-child {
        border-bottom: none;
    }
    .reset-item:hover {
        background: #f8f9fa;
    }
    .badge-count {
        font-size: 12px;
        padding: 3px 10px;
    }
    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    /* Correction sidebar et badges */
    .settings-page .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    .settings-page .config-header .badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    .settings-page .config-header .badge.bg-light {
        background: rgba(255,255,255,0.2) !important;
        color: white !important;
    }
</style>

<div class="container-fluid settings-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cog"></i> <?php echo t('configuration'); ?></h2>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- ========== SECTION 1 : NUMÉROS DE TÂCHES ========== -->
    <div class="row">
        <div class="col-md-6">
            <div class="config-card">
                <div class="config-header"><i class="fas fa-tasks"></i> <?php echo t('task_numbers_interventions'); ?></div>
                <div class="card-body p-4">
                    <form method="POST" id="form_interv">
                        <div class="mb-3"><label class="form-label"><?php echo t('prefix'); ?> (<?php echo t('max_4_chars'); ?>)</label><input type="text" name="interv_prefix" class="form-control" maxlength="4" value="<?php echo htmlspecialchars($formats['intervention']['prefix']); ?>" required></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_use_year" class="form-check-input" id="interv_year" <?php echo $formats['intervention']['use_year'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_year'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_use_month" class="form-check-input" id="interv_month" <?php echo $formats['intervention']['use_month'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_month'); ?></label></div>
                        <div class="mb-3"><label class="form-label"><?php echo t('digits_counter'); ?></label><select name="interv_digits" class="form-select">
                            <option value="4" <?php echo $formats['intervention']['digits'] == 4 ? 'selected' : ''; ?>>4 <?php echo t('digits'); ?> (0001)</option>
                            <option value="5" <?php echo $formats['intervention']['digits'] == 5 ? 'selected' : ''; ?>>5 <?php echo t('digits'); ?> (00001)</option>
                            <option value="6" <?php echo $formats['intervention']['digits'] == 6 ? 'selected' : ''; ?>>6 <?php echo t('digits'); ?> (000001)</option>
                        </select></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_reset_year" class="form-check-input" id="interv_reset_year" <?php echo $formats['intervention']['reset_on_year_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_year_change'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_reset_month" class="form-check-input" id="interv_reset_month" <?php echo $formats['intervention']['reset_on_month_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_month_change'); ?></label></div>
                        <div class="info-text"><i class="fas fa-info-circle"></i> <?php echo t('reset_info'); ?></div>
                        <div class="mt-3">
                            <button type="submit" name="save_interv" class="btn btn-primary"><?php echo t('save'); ?></button>
                            <button type="button" class="btn btn-danger ms-2" onclick="resetCounter('intervention')"><?php echo t('reset_counter'); ?></button>
                        </div>
                    </form>
                    <div class="mt-3"><strong><?php echo t('preview_next'); ?> :</strong><div class="preview-code" id="preview_interv"><?php echo htmlspecialchars(generateTaskNumber($pdo, 'intervention', true)); ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="config-card">
                <!-- SUPPRESSION DU BADGE D'ALERTE -->
                <div class="config-header"><i class="fas fa-calendar-check"></i> <?php echo t('task_numbers_preventive'); ?></div>
                <div class="card-body p-4">
                    <form method="POST" id="form_prev">
                        <div class="mb-3"><label class="form-label"><?php echo t('prefix'); ?> (<?php echo t('max_4_chars'); ?>)</label><input type="text" name="prev_prefix" class="form-control" maxlength="4" value="<?php echo htmlspecialchars($formats['preventive']['prefix']); ?>" required></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_use_year" class="form-check-input" id="prev_year" <?php echo $formats['preventive']['use_year'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_year'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_use_month" class="form-check-input" id="prev_month" <?php echo $formats['preventive']['use_month'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_month'); ?></label></div>
                        <div class="mb-3"><label class="form-label"><?php echo t('digits_counter'); ?></label><select name="prev_digits" class="form-select">
                            <option value="4" <?php echo $formats['preventive']['digits'] == 4 ? 'selected' : ''; ?>>4 <?php echo t('digits'); ?> (0001)</option>
                            <option value="5" <?php echo $formats['preventive']['digits'] == 5 ? 'selected' : ''; ?>>5 <?php echo t('digits'); ?> (00001)</option>
                            <option value="6" <?php echo $formats['preventive']['digits'] == 6 ? 'selected' : ''; ?>>6 <?php echo t('digits'); ?> (000001)</option>
                        </select></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_reset_year" class="form-check-input" id="prev_reset_year" <?php echo $formats['preventive']['reset_on_year_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_year_change'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_reset_month" class="form-check-input" id="prev_reset_month" <?php echo $formats['preventive']['reset_on_month_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_month_change'); ?></label></div>
                        <div class="info-text"><i class="fas fa-info-circle"></i> <?php echo t('reset_info'); ?></div>
                        <div class="mt-3">
                            <button type="submit" name="save_prev" class="btn btn-primary"><?php echo t('save'); ?></button>
                            <button type="button" class="btn btn-danger ms-2" onclick="resetCounter('preventive')"><?php echo t('reset_counter'); ?></button>
                        </div>
                    </form>
                    <div class="mt-3"><strong><?php echo t('preview_next'); ?> :</strong><div class="preview-code" id="preview_prev"><?php echo htmlspecialchars(generateTaskNumber($pdo, 'preventive', true)); ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SECTION 2 : FUSEAU HORAIRE ========== -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="config-card">
                <div class="config-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <i class="fas fa-globe"></i> <?php echo t('timezone_settings'); ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo t('timezone_offset_label'); ?></label>
                                <select name="timezone_offset" class="form-select" required>
                                    <option value="+00:00" <?php echo ($current_offset == '+00:00') ? 'selected' : ''; ?>>UTC (+00:00)</option>
                                    <option value="+01:00" <?php echo ($current_offset == '+01:00') ? 'selected' : ''; ?>>UTC+01:00 (<?php echo t('timezone_cet'); ?>)</option>
                                    <option value="+02:00" <?php echo ($current_offset == '+02:00') ? 'selected' : ''; ?>>UTC+02:00 (<?php echo t('timezone_cest'); ?>)</option>
                                    <option value="-05:00" <?php echo ($current_offset == '-05:00') ? 'selected' : ''; ?>>UTC-05:00 (<?php echo t('timezone_est'); ?>)</option>
                                    <option value="-04:00" <?php echo ($current_offset == '-04:00') ? 'selected' : ''; ?>>UTC-04:00 (<?php echo t('timezone_edt'); ?>)</option>
                                </select>
                                <small class="text-muted"><?php echo t('timezone_help'); ?></small>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" name="save_timezone_offset" class="btn btn-success"><i class="fas fa-save"></i> <?php echo t('save'); ?></button>
                            </div>
                        </div>
                    </form>
                    <div class="info-text mt-3">
                        <i class="fas fa-info-circle"></i> <?php echo t('current_mysql_time'); ?> : <strong><?php
                            $stmt = $pdo->query("SELECT NOW()");
                            echo $stmt->fetchColumn();
                        ?></strong><br>
                        <?php echo t('current_php_time'); ?> : <strong><?php echo date('Y-m-d H:i:s'); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== SECTION 3 : GESTION DES ÉLÉMENTS SUPPRIMÉS ========== -->
    <?php if (!empty($deleted_interventions) || !empty($deleted_preventives) || !empty($deleted_equipments)): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="config-card delete-card">
                <div class="config-header">
                    <i class="fas fa-trash-alt"></i> <?php echo t('deleted_items_management'); ?>
                    <span class="badge bg-light ms-2">
                        <?php 
                            $total_deleted = count($deleted_interventions) + count($deleted_preventives) + count($deleted_equipments);
                            echo $total_deleted . ' ' . t('items');
                        ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong><?php echo t('warning'); ?></strong> : <?php echo t('permanent_delete_warning'); ?>
                    </div>
                    
                    <form method="POST" id="permanentDeleteForm" onsubmit="return confirmPermanentDelete();">
                        <?= csrf_input() ?>
                        <input type="hidden" name="permanent_delete" value="1">
                        
                        <!-- Interventions supprimées -->
                        <?php if (!empty($deleted_interventions)): ?>
                        <div class="section-title">
                            <i class="fas fa-tasks"></i> <?php echo t('cancelled_interventions'); ?> (<?php echo count($deleted_interventions); ?>)
                            <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="toggleSelectAll('interventions')">
                                <i class="fas fa-check-double"></i> <?php echo t('select_all'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="select_all_interventions" onchange="toggleAll('interventions', this.checked)"></th>
                                        <th><?php echo t('task_number'); ?></th>
                                        <th><?php echo t('equipment'); ?></th>
                                        <th><?php echo t('title'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_interventions as $item): ?>
                                    <tr class="deleted-item-row">
                                        <td><input type="checkbox" name="delete_interventions[]" value="<?php echo $item['id']; ?>" class="interventions-check"></td>
                                        <td><?php echo htmlspecialchars($item['task_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Maintenances supprimées -->
                        <?php if (!empty($deleted_preventives)): ?>
                        <div class="section-title mt-3">
                            <i class="fas fa-calendar-check"></i> <?php echo t('cancelled_preventives'); ?> (<?php echo count($deleted_preventives); ?>)
                            <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="toggleSelectAll('preventives')">
                                <i class="fas fa-check-double"></i> <?php echo t('select_all'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="select_all_preventives" onchange="toggleAll('preventives', this.checked)"></th>
                                        <th><?php echo t('task_number'); ?></th>
                                        <th><?php echo t('equipment'); ?></th>
                                        <th><?php echo t('title'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_preventives as $item): ?>
                                    <tr class="deleted-item-row">
                                        <td><input type="checkbox" name="delete_preventives[]" value="<?php echo $item['id']; ?>" class="preventives-check"></td>
                                        <td><?php echo htmlspecialchars($item['task_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['equipment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Équipements supprimés -->
                        <?php if (!empty($deleted_equipments)): ?>
                        <div class="section-title mt-3">
                            <i class="fas fa-microchip"></i> <?php echo t('deleted_equipments'); ?> (<?php echo count($deleted_equipments); ?>)
                            <button type="button" class="btn btn-sm btn-outline-secondary float-end" onclick="toggleSelectAll('equipments')">
                                <i class="fas fa-check-double"></i> <?php echo t('select_all'); ?>
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="select_all_equipments" onchange="toggleAll('equipments', this.checked)"></th>
                                        <th><?php echo t('code'); ?></th>
                                        <th><?php echo t('name'); ?></th>
                                        <th><?php echo t('type'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deleted_equipments as $item): ?>
                                    <tr class="deleted-item-row">
                                        <td><input type="checkbox" name="delete_equipments[]" value="<?php echo $item['id']; ?>" class="equipments-check"></td>
                                        <td><?php echo htmlspecialchars($item['code']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['type']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Confirmation mot de passe -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label"><i class="fas fa-lock"></i> <?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_password" id="permanent_delete_password" class="form-control" required autocomplete="off" placeholder="<?php echo t('enter_admin_password'); ?>">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('permanent_delete_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="submit" class="btn btn-danger btn-lg w-100" id="permanentDeleteBtn">
                                        <i class="fas fa-trash-alt"></i> <?php echo t('permanent_delete'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========== SECTION 4 : RÉINITIALISATION DE LA BASE DE DONNÉES ========== -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="config-card reset-card">
                <div class="config-header">
                    <i class="fas fa-database"></i> <?php echo t('database_reset'); ?>
                    <span class="badge bg-light ms-2">
                        <i class="fas fa-exclamation-triangle text-warning"></i> <?php echo t('irreversible_action'); ?>
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="warning-box">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x float-start me-3"></i>
                        <strong>⚠️ <?php echo t('warning'); ?> :</strong> <?php echo t('database_reset_warning'); ?><br>
                        <small class="text-muted"><?php echo t('task_counters_will_reset'); ?></small>
                    </div>

                    <form method="POST" id="resetDatabaseForm" onsubmit="return confirmResetDatabase();">
                        <?= csrf_input() ?>
                        <input type="hidden" name="reset_database" value="1">

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;"><input type="checkbox" id="select_all_reset" onchange="toggleAllReset(this.checked)"></th>
                                        <th><?php echo t('table'); ?></th>
                                        <th><?php echo t('items_count'); ?></th>
                                        <th><?php echo t('description'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="reset-item">
                                        <td><input type="checkbox" name="reset_equipment" value="1" class="reset-check"></td>
                                        <td><i class="fas fa-microchip text-primary"></i> <strong><?php echo t('equipment'); ?></strong></td>
                                        <td><span class="badge bg-secondary badge-count"><?php echo $count_equipment; ?></span></td>
                                        <td><small class="text-muted"><?php echo t('reset_equipment_desc'); ?></small></td>
                                    </tr>
                                    <tr class="reset-item">
                                        <td><input type="checkbox" name="reset_interventions" value="1" class="reset-check"></td>
                                        <td><i class="fas fa-tools text-info"></i> <strong><?php echo t('interventions'); ?></strong></td>
                                        <td><span class="badge bg-secondary badge-count"><?php echo $count_interventions; ?></span></td>
                                        <td><small class="text-muted"><?php echo t('reset_interventions_desc'); ?></small></td>
                                    </tr>
                                    <tr class="reset-item">
                                        <td><input type="checkbox" name="reset_preventives" value="1" class="reset-check"></td>
                                        <td><i class="fas fa-calendar-check text-warning"></i> <strong><?php echo t('preventive_maintenance'); ?></strong></td>
                                        <td><span class="badge bg-secondary badge-count"><?php echo $count_preventives; ?></span></td>
                                        <td><small class="text-muted"><?php echo t('reset_preventives_desc'); ?></small></td>
                                    </tr>
                                    <tr class="reset-item">
                                        <td><input type="checkbox" name="reset_technicians" value="1" class="reset-check"></td>
                                        <td><i class="fas fa-user-cog text-success"></i> <strong><?php echo t('technicians'); ?></strong></td>
                                        <td><span class="badge bg-secondary badge-count"><?php echo $count_technicians; ?></span></td>
                                        <td><small class="text-muted"><?php echo t('reset_technicians_desc'); ?></small></td>
                                    </tr>
                                    <tr class="reset-item">
                                        <td><input type="checkbox" name="reset_stock" value="1" class="reset-check"></td>
                                        <td><i class="fas fa-boxes text-danger"></i> <strong><?php echo t('stock'); ?></strong></td>
                                        <td><span class="badge bg-secondary badge-count"><?php echo $count_stock; ?></span></td>
                                        <td><small class="text-muted"><?php echo t('reset_stock_desc'); ?></small></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllReset(true)">
                                <i class="fas fa-check-double"></i> <?php echo t('select_all'); ?>
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" onclick="toggleAllReset(false)">
                                <i class="fas fa-times"></i> <?php echo t('deselect_all'); ?>
                            </button>
                        </div>

                        <hr>

                        <!-- Confirmation mot de passe -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label"><i class="fas fa-lock"></i> <?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" name="reset_password" id="reset_database_password" class="form-control" required autocomplete="off" placeholder="<?php echo t('enter_admin_password_reset'); ?>">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('reset_database_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="submit" class="btn btn-warning btn-lg w-100" id="resetDatabaseBtn">
                                        <i class="fas fa-database"></i> <?php echo t('reset'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour réinitialisation du compteur -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-key"></i> <?php echo t('reset_counter_confirm'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo t('reset_counter_warning'); ?></p>
                <div class="mb-3">
                    <label class="form-label"><?php echo t('confirm_password'); ?></label>
                    <input type="password" id="resetPassword" class="form-control" required autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="confirmResetBtn"><?php echo t('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
// ========== GESTION DES SÉLECTIONS ==========
function toggleAll(type, checked) {
    var checkboxes = document.querySelectorAll('.' + type + '-check');
    checkboxes.forEach(function(cb) {
        cb.checked = checked;
    });
}

function toggleSelectAll(type) {
    var selectAllCheckbox = document.getElementById('select_all_' + type);
    if (selectAllCheckbox) {
        var newState = !selectAllCheckbox.checked;
        selectAllCheckbox.checked = newState;
        toggleAll(type, newState);
    }
}

function toggleAllReset(checked) {
    var checkboxes = document.querySelectorAll('.reset-check');
    checkboxes.forEach(function(cb) {
        cb.checked = checked;
    });
    var selectAll = document.getElementById('select_all_reset');
    if (selectAll) {
        selectAll.checked = checked;
    }
}

// ========== CONFIRMATION SUPPRESSION DÉFINITIVE ==========
function confirmPermanentDelete() {
    var checked = document.querySelectorAll('input[type="checkbox"]:checked');
    var count = 0;
    checked.forEach(function(cb) {
        if (cb.name && cb.name.startsWith('delete_')) {
            count++;
        }
    });
    
    if (count === 0) {
        alert('<?php echo t('select_at_least_one'); ?>');
        return false;
    }
    
    var password = document.getElementById('permanent_delete_password').value;
    if (!password) {
        alert('<?php echo t('password_required'); ?>');
        return false;
    }
    
    return confirm('<?php echo t('permanent_delete_confirm'); ?> ' + count + ' <?php echo t('items'); ?>. <?php echo t('irreversible_action_confirm'); ?>');
}

// ========== CONFIRMATION RÉINITIALISATION ==========
function confirmResetDatabase() {
    var checked = document.querySelectorAll('.reset-check:checked');
    if (checked.length === 0) {
        alert('<?php echo t('select_at_least_one_table'); ?>');
        return false;
    }
    
    var password = document.getElementById('reset_database_password').value;
    if (!password) {
        alert('<?php echo t('password_required'); ?>');
        return false;
    }
    
    var tableNames = [];
    checked.forEach(function(cb) {
        var row = cb.closest('tr');
        if (row) {
            var name = row.querySelector('td:nth-child(2)')?.textContent?.trim() || cb.name;
            tableNames.push(name);
        }
    });
    
    return confirm('<?php echo t('reset_confirm_message'); ?>\n\n' + 
                    tableNames.join('\n') + 
                    '\n\n<?php echo t('irreversible_action_confirm'); ?>');
}

// ========== AFFICHAGE/MASQUAGE MOT DE PASSE ==========
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    if (button && button.tagName === 'BUTTON') {
        if (field.type === 'password') {
            field.type = 'text';
            button.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            field.type = 'password';
            button.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
}

// ========== RÉINITIALISATION COMPTEUR ==========
let currentResetType = '';

function resetCounter(type) {
    currentResetType = type;
    document.getElementById('resetPassword').value = '';
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}

document.getElementById('confirmResetBtn').addEventListener('click', function() {
    const password = document.getElementById('resetPassword').value;
    if (!password) {
        alert("<?php echo t('password_required'); ?>");
        return;
    }
    fetch('api/reset_counter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ type: currentResetType, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + data.error);
        }
    })
    .catch(err => { alert('Erreur réseau'); });
});

// ========== APERÇU DYNAMIQUE DES NUMÉROS ==========
document.addEventListener('DOMContentLoaded', function() {
    function getBasePath() {
        let path = window.location.pathname;
        let match = path.match(/^\/[^\/]*/);
        return match ? match[0] : '';
    }
    const basePath = getBasePath();

    function updatePreview(type) {
        let prefix, useYear, useMonth, digits, previewDiv;
        if (type === 'intervention') {
            prefix = document.querySelector('#form_interv input[name="interv_prefix"]').value;
            useYear = document.querySelector('#form_interv input[name="interv_use_year"]').checked ? 1 : 0;
            useMonth = document.querySelector('#form_interv input[name="interv_use_month"]').checked ? 1 : 0;
            digits = document.querySelector('#form_interv select[name="interv_digits"]').value;
            previewDiv = document.getElementById('preview_interv');
        } else {
            prefix = document.querySelector('#form_prev input[name="prev_prefix"]').value;
            useYear = document.querySelector('#form_prev input[name="prev_use_year"]').checked ? 1 : 0;
            useMonth = document.querySelector('#form_prev input[name="prev_use_month"]').checked ? 1 : 0;
            digits = document.querySelector('#form_prev select[name="prev_digits"]').value;
            previewDiv = document.getElementById('preview_prev');
        }
        if (!previewDiv) return;

        if (useMonth && !useYear) {
            alert("<?php echo t('month_requires_year'); ?>");
            if (type === 'intervention') document.querySelector('#form_interv input[name="interv_use_month"]').checked = false;
            else document.querySelector('#form_prev input[name="prev_use_month"]').checked = false;
            updatePreview(type);
            return;
        }

        fetch(basePath + '/api/preview_task_number.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                type: type,
                prefix: prefix,
                use_year: useYear,
                use_month: useMonth,
                digits: digits
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.preview) previewDiv.innerHTML = data.preview;
            else if (data.error) previewDiv.innerHTML = 'Erreur : ' + data.error;
        })
        .catch(err => { previewDiv.innerHTML = 'Erreur AJAX'; console.error(err); });
    }

    const intervFields = document.querySelectorAll('#form_interv input, #form_interv select');
    intervFields.forEach(field => field.addEventListener('change', () => updatePreview('intervention')));
    const prevFields = document.querySelectorAll('#form_prev input, #form_prev select');
    prevFields.forEach(field => field.addEventListener('change', () => updatePreview('preventive')));

    updatePreview('intervention');
    updatePreview('preventive');
});
</script>