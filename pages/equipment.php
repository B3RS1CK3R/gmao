<?php
// pages/equipment.php - Gestion complète des équipements (CRUD)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== TRAITEMENT DES ACTIONS ==========

// Ajout d'un équipement
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'],
        $_POST['name'],
        $_POST['type'],
        $_POST['location'],
        $_POST['supplier'],
        $_POST['purchase_date'],
        $_POST['warranty_end'],
        $_POST['technical_specs']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'equipment_created', "Équipement créé: {$_POST['code']}");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=equipment'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Modification d'un équipement
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE equipment SET 
            code = ?, 
            name = ?, 
            type = ?, 
            location = ?, 
            supplier = ?, 
            purchase_date = ?, 
            warranty_end = ?, 
            technical_specs = ?,
            status = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['code'],
        $_POST['name'],
        $_POST['type'],
        $_POST['location'],
        $_POST['supplier'],
        $_POST['purchase_date'],
        $_POST['warranty_end'],
        $_POST['technical_specs'],
        $_POST['status'],
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'equipment_updated', "Équipement ID: {$_GET['id']} modifié");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=equipment'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Suppression (soft delete - désactivation) avec validation mot de passe
if($action == 'delete' && isset($_GET['id'])) {
    if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor') {
        if(isset($_POST['confirm_password'])) {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if(password_verify($_POST['confirm_password'], $user['password'])) {
                $stmt2 = $pdo->prepare("UPDATE equipment SET status = 'retired' WHERE id = ?");
                $stmt2->execute([$_GET['id']]);
                logUserAction($_SESSION['user_id'], 'equipment_deleted', "Équipement ID: {$_GET['id']} désactivé");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=equipment'>";
            } else {
                $error = "❌ " . t('password_error');
            }
        }
    }
}

// Réactivation d'un équipement (admin uniquement)
if($action == 'restore' && isset($_GET['id']) && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE equipment SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logUserAction($_SESSION['user_id'], 'equipment_restored', "Équipement ID: {$_GET['id']} réactivé");
    $message = "✅ " . t('save_success');
    echo "<meta http-equiv='refresh' content='1;url=?page=equipment'>";
}

// Récupération des équipements (incluant les retirés pour admin)
if($_SESSION['role'] == 'admin') {
    $equipments = $pdo->query("SELECT * FROM equipment ORDER BY name")->fetchAll();
} else {
    $equipments = $pdo->query("SELECT * FROM equipment WHERE status != 'retired' ORDER BY name")->fetchAll();
}

// Récupération de l'historique des modifications pour chaque équipement
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

// ========== FORMULAIRE D'AJOUT ==========
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
        <i class="fas fa-plus-circle"></i> <?php echo t('add_equipment'); ?>
    </div>
    <div class="card-body p-4">
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
                    <input type="text" name="type" class="form-control" placeholder="Ex: Fraiseuse, Presse, Convoyeur...">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('location'); ?></label>
                    <input type="text" name="location" class="form-control" placeholder="Ex: Atelier A, Bâtiment B...">
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
                    <textarea name="technical_specs" class="form-control" rows="3" placeholder="Caractéristiques techniques, puissance, dimensions..."></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== FORMULAIRE DE MODIFICATION ==========
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
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== MODAL DE CONFIRMATION SUPPRESSION ==========
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
                        <th><?php echo t('last_modifications'); ?></th>
                        <th class="text-center"><?php echo t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($equipments as $eq): ?>
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
                        </td>
                        <td style="max-width: 200px;">
                            <?php if(!empty($history[$eq['id']])): ?>
                                <?php foreach(array_slice($history[$eq['id']], 0, 2) as $h): ?>
                                <div class="history-item">
                                    <?php
                                    $action_icons = [
                                        'equipment_created' => '🟢 ' . t('created'),
                                        'equipment_updated' => '✏️ ' . t('modified'),
                                        'equipment_deleted' => '🗑️ ' . t('deactivated'),
                                        'equipment_restored' => '🔄 ' . t('reactivated')
                                    ];
                                    echo isset($action_icons[$h['action']]) ? $action_icons[$h['action']] : $h['action'];
                                    ?>
                                    <br><small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($h['created_at'])); ?></small>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center action-buttons" onclick="event.stopPropagation()">
                            <?php if($eq['status'] != 'retired'): ?>
                                <a href="?page=equipment_qr&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('qr_code'); ?>">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                <a href="?page=equipment&action=edit&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?page=equipment&action=delete&id=<?php echo $eq['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
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
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Légende -->
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
            </div>
        </div>
    </div>
</div>