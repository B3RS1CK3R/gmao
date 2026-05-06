<?php
// pages/stock.php - Full spare parts management (CRUD)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== ACTION PROCESSING ==========

// Add a spare part
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "INSERT INTO spare_parts (part_number, name, quantity, min_quantity, location, supplier, unit_price, last_restock, documentation_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['part_number'],
        $_POST['name'],
        $_POST['quantity'],
        $_POST['min_quantity'],
        $_POST['location'],
        $_POST['supplier'],
        $_POST['unit_price'],
        $_POST['last_restock'],
        $_POST['documentation_path'] ?? null
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'stock_created', "Part created: {$_POST['part_number']}");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=stock'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Edit a spare part
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE spare_parts SET 
            part_number = ?, 
            name = ?, 
            quantity = ?, 
            min_quantity = ?, 
            location = ?, 
            supplier = ?, 
            unit_price = ?, 
            last_restock = ?,
            documentation_path = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['part_number'],
        $_POST['name'],
        $_POST['quantity'],
        $_POST['min_quantity'],
        $_POST['location'],
        $_POST['supplier'],
        $_POST['unit_price'],
        $_POST['last_restock'],
        $_POST['documentation_path'] ?? null,
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'stock_updated', "Part ID: {$_GET['id']} modified");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=stock'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Delete (soft delete - deactivation) with password validation
if($action == 'delete' && isset($_GET['id'])) {
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor') {
        if(isset($_POST['confirm_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if(password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt2 = $pdo->prepare("UPDATE spare_parts SET quantity = -1, min_quantity = -1 WHERE id = ?");
                $stmt2->execute([$_GET['id']]);
                logUserAction($_SESSION['user_id'], 'stock_deleted', "Part ID: {$_GET['id']} deactivated");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=stock'>";
            } else {
                $error = "❌ " . t('password_error');
            }
        }
    }
}

// Restore a deactivated part (admin only)
if($action == 'restore' && isset($_GET['id']) && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE spare_parts SET quantity = 0, min_quantity = 5, documentation_path = NULL WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logUserAction($_SESSION['user_id'], 'stock_restored', "Part ID: {$_GET['id']} reactivated");
    $message = "✅ " . t('save_success');
    echo "<meta http-equiv='refresh' content='1;url=?page=stock'>";
}

// Stock movement (in/out)
if($action == 'movement' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $part_id = $_GET['id'];
    $movement_type = $_POST['movement_type'];
    $quantity = intval($_POST['quantity']);
    $reason = $_POST['reason'];
    
    // Fetch current quantity
    $stmt = $pdo->prepare("SELECT quantity FROM spare_parts WHERE id = ?");
    $stmt->execute([$part_id]);
    $current = $stmt->fetchColumn();
    
    if($movement_type == 'in') {
        $new_quantity = $current + $quantity;
        $movement_text = "Stock in";
    } else {
        if($current < $quantity) {
            $error = "❌ " . t('stock_insufficient') . " (available: $current)";
        } else {
            $new_quantity = $current - $quantity;
            $movement_text = "Stock out";
        }
    }
    
    if(!$error) {
        $stmt = $pdo->prepare("UPDATE spare_parts SET quantity = ?, last_restock = ? WHERE id = ?");
        $stmt->execute([$new_quantity, ($movement_type == 'in' ? date('Y-m-d') : null), $part_id]);
        
        // Record movement
        $stmt = $pdo->prepare("INSERT INTO stock_movements (part_id, movement_type, quantity, reason) VALUES (?, ?, ?, ?)");
        $stmt->execute([$part_id, $movement_type, $quantity, $reason]);
        
        logUserAction($_SESSION['user_id'], 'stock_movement', "$movement_text: $quantity x part ID: $part_id");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=stock_detail&id=$part_id'>";
    }
}

// Fetch parts (excluding deactivated for non-admin)
if($_SESSION['role'] == 'admin') {
    $parts = $pdo->query("SELECT * FROM spare_parts ORDER BY name")->fetchAll();
} else {
    $parts = $pdo->query("SELECT * FROM spare_parts WHERE quantity >= 0 ORDER BY name")->fetchAll();
}

// Fetch modifications history for each part
$history = [];
foreach($parts as $part) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('stock_created', 'stock_updated', 'stock_deleted', 'stock_restored', 'stock_movement')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$part['id']}%"]);
    $history[$part['id']] = $stmt->fetchAll();
}

// Stock statistics
$critical_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] <= $p['min_quantity'] && $p['quantity'] >= 0; 
}));
$warning_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] > $p['min_quantity'] && $p['quantity'] <= $p['min_quantity'] * 2 && $p['quantity'] >= 0; 
}));
$ok_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] > $p['min_quantity'] * 2 && $p['quantity'] >= 0; 
}));
$inactive_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] < 0; 
}));

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
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-plus-circle"></i> <?php echo t('add_part'); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('part_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="part_number" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('quantity'); ?></label>
                    <input type="number" name="quantity" class="form-control" value="0" min="0">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('min_quantity'); ?></label>
                    <input type="number" name="min_quantity" class="form-control" value="5">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('location_stock'); ?></label>
                    <input type="text" name="location" class="form-control" placeholder="<?php echo t('location_placeholder'); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('unit_price'); ?> (€)</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('supplier'); ?></label>
                    <input type="text" name="supplier" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('last_restock'); ?></label>
                    <input type="date" name="last_restock" class="form-control">
                </div>
                
                <!-- Documentation field (admin and supervisor only) -->
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                <div class="col-md-12 mb-3">
                    <label class="form-label">
                        <i class="fas fa-folder-open"></i> <?php echo t('documentation'); ?>
                    </label>
                    <div class="doc-path-input-group">
                        <input type="text" name="documentation_path" class="form-control" 
                               placeholder="<?php echo t('doc_placeholder'); ?>">
                        <small class="text-muted"><?php echo t('doc_help'); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== EDIT FORM ==========
if($action == 'edit' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM spare_parts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $part = $stmt->fetch();
    if(!$part) {
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
    .doc-path-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .btn-folder {
        background: #17a2b8;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 15px;
        cursor: pointer;
        white-space: nowrap;
    }
    .btn-folder:hover {
        background: #138496;
    }
    .doc-preview {
        margin-top: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 8px;
        font-size: 12px;
    }
    .doc-preview a {
        color: #007bff;
        text-decoration: none;
    }
    .doc-preview a:hover {
        text-decoration: underline;
    }
</style>
<div class="form-card">
    <div class="form-card-header">
        <i class="fas fa-edit"></i> <?php echo t('edit_part'); ?> : <?php echo htmlspecialchars($part['part_number']); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('part_number'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="part_number" class="form-control" value="<?php echo htmlspecialchars($part['part_number']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($part['name']); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('quantity'); ?></label>
                    <input type="number" name="quantity" class="form-control" value="<?php echo $part['quantity']; ?>" min="0">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('min_quantity'); ?></label>
                    <input type="number" name="min_quantity" class="form-control" value="<?php echo $part['min_quantity']; ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('location_stock'); ?></label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($part['location']); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label"><?php echo t('unit_price'); ?> (€)</label>
                    <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo $part['unit_price']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('supplier'); ?></label>
                    <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($part['supplier']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('last_restock'); ?></label>
                    <input type="date" name="last_restock" class="form-control" value="<?php echo $part['last_restock']; ?>">
                </div>
                
                <!-- Documentation field -->
                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                <div class="col-md-12 mb-3">
                    <label class="form-label">
                        <i class="fas fa-folder-open"></i> <?php echo t('documentation'); ?>
                    </label>
                    <div class="doc-path-input-group">
                        <input type="text" name="documentation_path" class="form-control" 
                               value="<?php echo htmlspecialchars($part['documentation_path'] ?? ''); ?>" 
                               placeholder="<?php echo t('doc_placeholder'); ?>">
                        <?php if(!empty($part['documentation_path'])): ?>
                        <button type="button" class="btn-folder" onclick="openDocumentation('<?php echo addslashes($part['documentation_path']); ?>')" title="<?php echo t('open_doc'); ?>">
                            <i class="fas fa-folder-open"></i> <?php echo t('open'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted"><?php echo t('doc_help'); ?></small>
                </div>
                <?php else: ?>
                <?php if(!empty($part['documentation_path'])): ?>
                <div class="col-md-12 mb-3">
                    <div class="doc-preview">
                        <i class="fas fa-link"></i> <strong><?php echo t('documentation'); ?> :</strong><br>
                        <a href="#" onclick="openDocumentation('<?php echo addslashes($part['documentation_path']); ?>'); return false;">
                            <i class="fas fa-file-alt"></i> <?php echo basename($part['documentation_path']); ?>
                        </a>
                        <br><small class="text-muted"><?php echo htmlspecialchars($part['documentation_path']); ?></small>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<script>
function openDocumentation(path) {
    let formattedPath = path.replace(/\\/g, '/');
    if (!formattedPath.startsWith('file:///')) {
        formattedPath = 'file:///' + formattedPath;
    }
    window.open(formattedPath, '_blank');
}
</script>
<?php
return;
endif;

// ========== DELETE CONFIRMATION MODAL ==========
if($action == 'delete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM spare_parts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $part = $stmt->fetch();
    if(!$part) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <i class="fas fa-trash-alt"></i> <?php echo t('delete_part'); ?>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($part['name']); ?></strong> (<?php echo htmlspecialchars($part['part_number']); ?>)
        </div>
        <p><?php echo t('delete_warning'); ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;
?>

<style>
    .stock-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .stock-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .status-critical { background: #dc3545; color: white; }
    .status-warning { background: #ffc107; color: #333; }
    .status-ok { background: #28a745; color: white; }
    .status-inactive { background: #6c757d; color: white; }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .progress-bar-custom {
        height: 8px;
        border-radius: 4px;
        transition: width 0.5s;
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
    .history-item {
        padding: 5px 0;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 6px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 6px;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 6px;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> <?php echo t('stock'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=stock&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('add_part'); ?>
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
    
    <!-- Statistics cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=critical'">
                <div class="stats-number text-danger"><?php echo $critical_stock; ?></div>
                <div class="text-muted"><?php echo t('critical_stock'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=warning'">
                <div class="stats-number text-warning"><?php echo $warning_stock; ?></div>
                <div class="text-muted"><?php echo t('to_monitor'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=ok'">
                <div class="stats-number text-success"><?php echo $ok_stock; ?></div>
                <div class="text-muted"><?php echo t('sufficient'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=inactive'">
                <div class="stats-number text-secondary"><?php echo $inactive_stock; ?></div>
                <div class="text-muted"><?php echo t('inactive'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Stock list -->
    <div class="stock-card">
        <div class="stock-card-header">
            <i class="fas fa-list"></i> <?php echo t('stock_list'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('part_number'); ?></th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('quantity'); ?></th>
                            <th><?php echo t('stock_level'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('location_stock'); ?></th>
                            <th><?php echo t('unit_price'); ?></th>
                            <th><?php echo t('documentation'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($parts as $part): 
                            $stock_percent = $part['min_quantity'] > 0 ? min(100, ($part['quantity'] / $part['min_quantity']) * 100) : 0;
                            
                            if($part['quantity'] < 0) {
                                $status_class = 'status-inactive';
                                $status_text = '⚫ ' . t('inactive');
                                $bg_class = '';
                            } elseif($part['quantity'] <= $part['min_quantity']) {
                                $status_class = 'status-critical';
                                $status_text = '🔴 ' . t('critical_stock');
                                $bg_class = 'table-danger';
                            } elseif($part['quantity'] <= $part['min_quantity'] * 2) {
                                $status_class = 'status-warning';
                                $status_text = '🟡 ' . t('to_monitor');
                                $bg_class = 'table-warning';
                            } else {
                                $status_class = 'status-ok';
                                $status_text = '🟢 ' . t('sufficient');
                                $bg_class = '';
                            }
                        ?>
                        <tr class="table-row-clickable <?php echo $bg_class; ?>" onclick="window.location.href='?page=stock_detail&id=<?php echo $part['id']; ?>'">
                            <td><strong><?php echo htmlspecialchars($part['part_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($part['name']); ?></td>
                            <td><?php echo $part['quantity'] >= 0 ? $part['quantity'] : '-'; ?></td>
                            <td style="width: 100px;">
                                <?php if($part['quantity'] >= 0): ?>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?php echo $stock_percent <= 30 ? 'bg-danger' : ($stock_percent <= 60 ? 'bg-warning' : 'bg-success'); ?>" 
                                         style="width: <?php echo $stock_percent; ?>%"></div>
                                </div>
                                <small><?php echo $part['quantity']; ?> / <?php echo $part['min_quantity']; ?></small>
                                <?php endif; ?>
                             </td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo htmlspecialchars($part['location'] ?: '-'); ?></td>
                            <td><?php echo number_format($part['unit_price'], 2); ?> €</td>
                            <td class="text-center">
                                <?php if(!empty($part['documentation_path'])): ?>
                                    <i class="fas fa-file-alt text-info" title="<?php echo t('documentation'); ?>"></i>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 120px;">
                                <?php if(!empty($history[$part['id']])): ?>
                                    <?php foreach(array_slice($history[$part['id']], 0, 2) as $h): ?>
                                    <div class="history-item">
                                        <?php
                                        $action_icons = [
                                            'stock_created' => '🟢 ' . t('created'),
                                            'stock_updated' => '✏️ ' . t('modified'),
                                            'stock_deleted' => '🗑️ ' . t('deactivated'),
                                            'stock_restored' => '🔄 ' . t('restored'),
                                            'stock_movement' => '📊 ' . t('movement')
                                        ];
                                        echo isset($action_icons[$h['action']]) ? $action_icons[$h['action']] : $h['action'];
                                        ?>
                                        <br><small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center action-buttons" onclick="event.stopPropagation()">
                                <?php if($part['quantity'] >= 0): ?>
                                    <button type="button" class="btn btn-sm btn-success" title="<?php echo t('stock_in'); ?>" data-bs-toggle="modal" data-bs-target="#movementInModal<?php echo $part['id']; ?>">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" title="<?php echo t('stock_out'); ?>" data-bs-toggle="modal" data-bs-target="#movementOutModal<?php echo $part['id']; ?>">
                                        <i class="fas fa-minus-circle"></i>
                                    </button>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=stock&action=edit&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-primary" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=stock&action=delete&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=stock&action=restore&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('restore'); ?>" onclick="return confirm('<?php echo t('restore_confirm'); ?>')">
                                        <i class="fas fa-undo-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Stock in modal -->
                        <div class="modal fade" id="movementInModal<?php echo $part['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> <?php echo t('stock_in'); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                                        <div class="modal-body">
                                            <p><strong><?php echo t('part'); ?> :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                                            <p><strong><?php echo t('current_stock'); ?> :</strong> <?php echo $part['quantity']; ?></p>
                                            <input type="hidden" name="movement_type" value="in">
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('quantity_to_add'); ?></label>
                                                <input type="number" name="quantity" class="form-control" min="1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('reason'); ?></label>
                                                <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo t('reason_placeholder'); ?>"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                                            <button type="submit" class="btn btn-success"><?php echo t('confirm'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stock out modal -->
                        <div class="modal fade" id="movementOutModal<?php echo $part['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-dark">
                                        <h5 class="modal-title"><i class="fas fa-minus-circle"></i> <?php echo t('stock_out'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                                        <div class="modal-body">
                                            <p><strong><?php echo t('part'); ?> :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                                            <p><strong><?php echo t('current_stock'); ?> :</strong> <?php echo $part['quantity']; ?></p>
                                            <input type="hidden" name="movement_type" value="out">
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('quantity_to_remove'); ?></label>
                                                <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $part['quantity']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('reason'); ?></label>
                                                <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo t('reason_placeholder'); ?>"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                                            <button type="submit" class="btn btn-warning"><?php echo t('confirm'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Legend -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="stock-card">
                <div class="card-body">
                    <div class="d-flex justify-content-center gap-4 flex-wrap">
                        <div><span class="status-badge status-critical">🔴 <?php echo t('critical_stock'); ?></span> <small><?php echo t('critical_desc'); ?></small></div>
                        <div><span class="status-badge status-warning">🟡 <?php echo t('to_monitor'); ?></span> <small><?php echo t('monitor_desc'); ?></small></div>
                        <div><span class="status-badge status-ok">🟢 <?php echo t('sufficient'); ?></span> <small><?php echo t('sufficient_desc'); ?></small></div>
                        <div><span class="status-badge status-inactive">⚫ <?php echo t('inactive'); ?></span> <small><?php echo t('inactive_desc'); ?></small></div>
                        <div><i class="fas fa-plus-circle text-success"></i> <small><?php echo t('stock_in'); ?></small></div>
                        <div><i class="fas fa-minus-circle text-warning"></i> <small><?php echo t('stock_out'); ?></small></div>
                        <div><i class="fas fa-file-alt text-info"></i> <small><?php echo t('documentation'); ?></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openDocumentation(path) {
    let formattedPath = path.replace(/\\/g, '/');
    if (!formattedPath.startsWith('file:///')) {
        formattedPath = 'file:///' + formattedPath;
    }
    window.open(formattedPath, '_blank');
}
</script>