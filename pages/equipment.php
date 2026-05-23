<?php

// pages/equipment.php - Full equipment management (CRUD)
// if(!isset($_SESSION['user_id'])) {
//     header('Location: index.php?page=login');
//     exit();
// }

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== ACTION PROCESSING ==========

// Add equipment
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
    
    $sql = "INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs, probability_score, severity_score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'],
        $_POST['name'],
        $_POST['type'],
        $_POST['location'],
        $_POST['supplier'],
        $purchase_date,
        $warranty_end,
        $_POST['technical_specs'],
        $_POST['probability_score'],
        $_POST['severity_score']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'equipment_created', "Equipment created: {$_POST['code']}");
        $message = "✅ " . t('save_success');
        $timestamp = time();
        echo "<meta http-equiv='refresh' content='1;url=?page=equipment&_t=$timestamp'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Edit equipment
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmtOld = $pdo->prepare("SELECT code, name FROM equipment WHERE id = ?");
    $stmtOld->execute([$_GET['id']]);
    $oldEquipment = $stmtOld->fetch();
    
    $purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
    $warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
    
    $sql = "UPDATE equipment SET 
            code = ?, 
            name = ?, 
            type = ?, 
            location = ?, 
            supplier = ?, 
            purchase_date = ?, 
            warranty_end = ?, 
            technical_specs = ?,
            probability_score = ?,
            severity_score = ?,
            status = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'],
        $_POST['name'],
        $_POST['type'],
        $_POST['location'],
        $_POST['supplier'],
        $purchase_date,
        $warranty_end,
        $_POST['technical_specs'],
        $_POST['probability_score'],
        $_POST['severity_score'],
        $_POST['status'],
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'equipment_updated', "Equipment ID: {$_GET['id']} updated");
        $message = "✅ " . t('save_success');
        $timestamp = time();
        echo "<meta http-equiv='refresh' content='1;url=?page=equipment&_t=$timestamp'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Delete (soft delete - deactivation)
if($action == 'delete' && isset($_GET['id'])) {
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor') {
        if(isset($_POST['confirm_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if(password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt2 = $pdo->prepare("UPDATE equipment SET status = 'retired' WHERE id = ?");
                $stmt2->execute([$_GET['id']]);
                logUserAction($_SESSION['user_id'], 'equipment_deleted', "Equipment ID: {$_GET['id']} deactivated");
                $message = "✅ " . t('save_success');
                $timestamp = time();
                echo "<meta http-equiv='refresh' content='1;url=?page=equipment&_t=$timestamp'>";
            } else {
                $error = "❌ " . t('password_error');
            }
        }
    }
}

// Restore equipment (admin only)
if($action == 'restore' && isset($_GET['id']) && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE equipment SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logUserAction($_SESSION['user_id'], 'equipment_restored', "Equipment ID: {$_GET['id']} reactivated");
    $message = "✅ " . t('save_success');
    $timestamp = time();
    echo "<meta http-equiv='refresh' content='1;url=?page=equipment&_t=$timestamp'>";
}

// Fetch equipment (including retired for admin)
if($_SESSION['role'] == 'admin') {
    $equipments = $pdo->query("SELECT * FROM equipment ORDER BY name")->fetchAll();
} else {
    $equipments = $pdo->query("SELECT * FROM equipment WHERE status != 'retired' ORDER BY name")->fetchAll();
}

// Count attachments per equipment
$attachmentCounts = [];
try {
    $stmt = $pdo->query("SELECT parent_id, COUNT(*) as c FROM attachments WHERE parent_type='equipment' GROUP BY parent_id");
    foreach($stmt->fetchAll() as $r) { $attachmentCounts[$r['parent_id']] = $r['c']; }
} catch (PDOException $e) {
    $attachmentCounts = [];
}

// Fetch modifications history for each equipment
$history = [];
foreach($equipments as $eq) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('equipment_created', 'equipment_updated', 'equipment_deleted', 'equipment_restored')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$eq['id']}%"]);
    $history[$eq['id']] = $stmt->fetchAll();
}

// ========== ADD FORM ==========
if($action == 'add'):
?>
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-primary:hover {
        filter: brightness(0.95);
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .info-message {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-plus-circle"></i> <?php echo t('add_equipment'); ?>
    </div>
    <div class="card-body p-4">
        <div class="info-message">
            <i class="fas fa-info-circle"></i> 
            <?php echo t('add_equipment_info'); ?>
            <small class="d-block mt-1 text-muted"><?php echo t('save_before_adding_documents'); ?></small>
        </div>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('type'); ?></label>
                    <input type="text" name="type" class="form-control" placeholder="<?php echo t('type_help'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('location'); ?></label>
                    <input type="text" name="location" class="form-control" placeholder="<?php echo t('location_help'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('supplier'); ?></label>
                    <input type="text" name="supplier" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('purchase_date'); ?></label>
                    <input type="date" name="purchase_date" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('warranty_end'); ?></label>
                    <input type="date" name="warranty_end" class="form-control">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?php echo t('technical_specs'); ?></label>
                    <textarea name="technical_specs" class="form-control" rows="3" placeholder="<?php echo t('technical_specs_help'); ?>"></textarea>
                </div>
                
                <!-- Criticality scores -->
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('probability_score'); ?></label>
                    <select name="probability_score" class="form-select">
                        <option value="1">1 - <?php echo t('very_low'); ?></option>
                        <option value="2">2 - <?php echo t('low'); ?></option>
                        <option value="3">3 - <?php echo t('medium'); ?></option>
                        <option value="4">4 - <?php echo t('high'); ?></option>
                        <option value="5">5 - <?php echo t('very_high'); ?></option>
                    </select>
                    <small class="text-muted"><?php echo t('probability_help'); ?></small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('severity_score'); ?></label>
                    <select name="severity_score" class="form-select">
                        <option value="1">1 - <?php echo t('negligible'); ?></option>
                        <option value="2">2 - <?php echo t('minor'); ?></option>
                        <option value="3">3 - <?php echo t('moderate'); ?></option>
                        <option value="4">4 - <?php echo t('serious'); ?></option>
                        <option value="5">5 - <?php echo t('critical'); ?></option>
                    </select>
                    <small class="text-muted"><?php echo t('severity_help'); ?></small>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<!-- Documents section for ADD mode - No attachments possible yet -->
<div class="info-card mt-3">
    <div class="card-header-custom" style="background: linear-gradient(135deg, #6c757d, #5a6268);">
        <i class="fas fa-paperclip"></i> <?php echo t('documents'); ?>
    </div>
    <div class="card-body p-3">
        <div class="alert alert-info mb-0">
            <i class="fas fa-info-circle"></i> 
            <?php echo t('save_before_adding_documents'); ?>
        </div>
    </div>
</div>

<?php
return;
endif;

// ========== EDIT FORM ==========
if($action == 'edit' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $eq = $stmt->fetch();
    if(!$eq) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-edit"></i> <?php echo t('edit_equipment'); ?> : <?php echo htmlspecialchars($eq['code']); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($eq['code']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($eq['name']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('type'); ?></label>
                    <input type="text" name="type" class="form-control" value="<?php echo htmlspecialchars($eq['type']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('location'); ?></label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($eq['location']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('supplier'); ?></label>
                    <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($eq['supplier']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('status'); ?></label>
                    <select name="status" class="form-select">
                        <option value="active" <?php if($eq['status'] == 'active') echo 'selected'; ?>><?php echo t('active'); ?></option>
                        <option value="maintenance" <?php if($eq['status'] == 'maintenance') echo 'selected'; ?>><?php echo t('maintenance'); ?></option>
                        <option value="broken" <?php if($eq['status'] == 'broken') echo 'selected'; ?>><?php echo t('broken'); ?></option>
                        <?php if($_SESSION['role'] == 'admin'): ?>
                        <option value="retired" <?php if($eq['status'] == 'retired') echo 'selected'; ?>><?php echo t('retired'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('purchase_date'); ?></label>
                    <input type="date" name="purchase_date" class="form-control" value="<?php echo $eq['purchase_date']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('warranty_end'); ?></label>
                    <input type="date" name="warranty_end" class="form-control" value="<?php echo $eq['warranty_end']; ?>">
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label"><?php echo t('technical_specs'); ?></label>
                    <textarea name="technical_specs" class="form-control" rows="3"><?php echo htmlspecialchars($eq['technical_specs']); ?></textarea>
                </div>
                
                <!-- Criticality scores -->
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('probability_score'); ?></label>
                    <select name="probability_score" class="form-select">
                        <option value="1" <?php if(($eq['probability_score'] ?? 1) == 1) echo 'selected'; ?>>1 - <?php echo t('very_low'); ?></option>
                        <option value="2" <?php if(($eq['probability_score'] ?? 1) == 2) echo 'selected'; ?>>2 - <?php echo t('low'); ?></option>
                        <option value="3" <?php if(($eq['probability_score'] ?? 1) == 3) echo 'selected'; ?>>3 - <?php echo t('medium'); ?></option>
                        <option value="4" <?php if(($eq['probability_score'] ?? 1) == 4) echo 'selected'; ?>>4 - <?php echo t('high'); ?></option>
                        <option value="5" <?php if(($eq['probability_score'] ?? 1) == 5) echo 'selected'; ?>>5 - <?php echo t('very_high'); ?></option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('severity_score'); ?></label>
                    <select name="severity_score" class="form-select">
                        <option value="1" <?php if(($eq['severity_score'] ?? 1) == 1) echo 'selected'; ?>>1 - <?php echo t('negligible'); ?></option>
                        <option value="2" <?php if(($eq['severity_score'] ?? 1) == 2) echo 'selected'; ?>>2 - <?php echo t('minor'); ?></option>
                        <option value="3" <?php if(($eq['severity_score'] ?? 1) == 3) echo 'selected'; ?>>3 - <?php echo t('moderate'); ?></option>
                        <option value="4" <?php if(($eq['severity_score'] ?? 1) == 4) echo 'selected'; ?>>4 - <?php echo t('serious'); ?></option>
                        <option value="5" <?php if(($eq['severity_score'] ?? 1) == 5) echo 'selected'; ?>>5 - <?php echo t('critical'); ?></option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>

<!-- Documents / Attachments (on edit page) -->
<div class="info-card mt-3">
    <div class="form-card-header">
        <i class="fas fa-paperclip"></i> <?php echo t('documents'); ?>
    </div>
    <div class="card-body p-3">
        <?php
            $stmt = $pdo->prepare("SELECT * FROM attachments WHERE parent_type = 'equipment' AND parent_id = ? ORDER BY created_at DESC");
            $stmt->execute([$eq['id']]);
            $attachments_edit = $stmt->fetchAll();
            $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        ?>

        <?php if(empty($attachments_edit)): ?>
            <div class="text-muted"><?php echo t('no_documents'); ?></div>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach($attachments_edit as $att): ?>
                    <div style="width:260px; text-align:left; border:1px solid #f0f0f0; padding:8px; border-radius:8px;">
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
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
            <hr>
            <form id="equip-edit-upload-form" action="api/upload_attachment.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="parent_type" value="equipment">
                <input type="hidden" name="parent_id" value="<?php echo $eq['id']; ?>">
                <?php echo csrf_input(); ?>
                <div class="mb-2">
                    <input id="equip-edit-file-input" type="file" name="file" required>
                </div>
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-upload"></i> <?php echo t('upload'); ?></button>
            </form>
            <script>
            (function(){
                var form = document.getElementById('equip-edit-upload-form');
                if(!form) return;
                var input = document.getElementById('equip-edit-file-input');
                var maxBytes = 10 * 1024 * 1024; // 10MB
                form.addEventListener('submit', function(e){
                    if(input.files && input.files[0]){
                        if(input.files[0].size > maxBytes){
                            e.preventDefault();
                            alert('File is too large (max 10 MB).');
                            return false;
                        }
                    }
                });
            })();
            </script>
        <?php endif; ?>
    </div>
</div>
<?php
return;
endif;

// ========== DELETE CONFIRMATION MODAL ==========
if($action == 'delete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $eq = $stmt->fetch();
    if(!$eq) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <i class="fas fa-trash-alt"></i> <?php echo t('delete'); ?> <?php echo t('equipment'); ?>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($eq['name']); ?></strong> (<?php echo htmlspecialchars($eq['code']); ?>)
        </div>
        <p><?php echo t('delete_warning'); ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-active { background: #28a745; color: white; }
    .status-maintenance { background: #ffc107; color: #333; }
    .status-broken { background: #dc3545; color: white; }
    .status-retired { background: #6c757d; color: white; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .history-item {
        padding: 5px 0;
        font-size: 11px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .btn-group-sm .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    /* Custom badge color for orange */
    .badge.bg-orange {
        background-color: #fd7e14 !important;
        color: white;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-microchip"></i> <?php echo t('equipment'); ?></h2>
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
    <a href="?page=equipment&action=add" class="btn btn-primary">
        <i class="fas fa-plus"></i> <?php echo t('add_equipment'); ?>
    </a>
    <?php endif; ?>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="info-card">
    <div class="card-header-custom">
        <i class="fas fa-list"></i> <?php echo t('equipment_list'); ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th><?php echo t('code'); ?></th>
                        <th><?php echo t('name'); ?></th>
                        <th><?php echo t('type'); ?></th>
                        <th><?php echo t('location'); ?></th>
                        <th><?php echo t('status'); ?></th>
                        <th><?php echo t('criticality'); ?></th>
                        <th><?php echo t('last_modifications'); ?></th>
                        <th class="text-center"><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($equipments as $eq): 
                        $criticality = (($eq['probability_score'] ?? 1) * ($eq['severity_score'] ?? 1));
                        // 4 couleurs : vert, jaune, orange, rouge
                        if ($criticality >= 20) {
                            $criticalityClass = 'danger';
                        } elseif ($criticality >= 12) {
                            $criticalityClass = 'orange';
                        } elseif ($criticality >= 6) {
                            $criticalityClass = 'warning';
                        } else {
                            $criticalityClass = 'success';
                        }
                    ?>
                    <tr class="table-row-clickable" onclick="window.location.href='?page=equipment_detail&id=<?php echo $eq['id']; ?>'">
                        <td><strong><?php echo htmlspecialchars($eq['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($eq['name']); ?></td>
                        <td><?php echo htmlspecialchars($eq['type']); ?></td>
                        <td><?php echo htmlspecialchars($eq['location']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $eq['status']; ?>">
                                <?php
                                $status_labels = [
                                    'active' => '🟢 ' . t('active'),
                                    'maintenance' => '🟡 ' . t('maintenance'),
                                    'broken' => '🔴 ' . t('broken'),
                                    'retired' => '⚫ ' . t('retired')
                                ];
                                echo $status_labels[$eq['status']] ?? $eq['status'];
                                ?>
                            </span>
                        </span>
                        <td class="text-center">
                            <span class="badge bg-<?php echo $criticalityClass; ?>"><?php echo $criticality; ?></span>
                        </span>
                        <td style="max-width: 200px;">
                            <?php if(!empty($history[$eq['id']])):
                                $h = $history[$eq['id']][0]; ?>
                                <div class="history-item">
                                    <small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                                </div>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </span>
                        <td class="text-center action-buttons" onclick="event.stopPropagation()">
                            <?php if($eq['status'] != 'retired'): ?>
                                <a href="?page=equipment_attachments&equipment_id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-light" title="<?php echo t('attachments'); ?>">
                                    <i class="fas fa-paperclip"></i>
                                    <span class="badge bg-secondary ms-1">
                                        <?php echo !empty($attachmentCounts[$eq['id']]) ? $attachmentCounts[$eq['id']] : 0; ?>
                                    </span>
                                </a>
                                <a href="?page=equipment_qr&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('qr_code'); ?>">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                <a href="?page=equipment&action=edit&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?page=equipment&action=delete&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" onclick="return confirm('<?php echo t('delete_confirm'); ?>')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if($_SESSION['role'] == 'admin'): ?>
                                <a href="?page=equipment_qr&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('qr_code'); ?>">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <a href="?page=equipment&action=restore&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('restore'); ?>" onclick="return confirm('<?php echo t('restore_confirm'); ?>')">
                                    <i class="fas fa-undo-alt"></i>
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="info-card">
            <div class="card-body">
                <div class="d-flex justify-content-center gap-4">
                    <div><span class="status-badge status-active">🟢 <?php echo t('active'); ?></span> <small><?php echo t('active_description'); ?></small></div>
                    <div><span class="status-badge status-maintenance">🟡 <?php echo t('maintenance'); ?></span> <small><?php echo t('maintenance_description'); ?></small></div>
                    <div><span class="status-badge status-broken">🔴 <?php echo t('broken'); ?></span> <small><?php echo t('broken_description'); ?></small></div>
                    <div><span class="status-badge status-retired">⚫ <?php echo t('retired'); ?></span> <small><?php echo t('retired_description'); ?></small></div>
                </div>
                <div class="d-flex justify-content-center gap-4 mt-2">
                    <div><span class="badge bg-success">1-5</span> <small><?php echo t('low_criticality'); ?></small></div>
                    <div><span class="badge bg-warning">6-10</span> <small><?php echo t('medium_criticality'); ?></small></div>
                    <div><span class="badge bg-orange">11-15</span> <small><?php echo t('high_criticality'); ?></small></div>
                    <div><span class="badge bg-danger">16-25</span> <small><?php echo t('very_high_criticality'); ?></small></div>
                </div>
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
    alert('📁 Folder path copied to clipboard:\n' + text + '\n\nYou can now paste this path into File Explorer to open the folder.');
}
</script>