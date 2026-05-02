<?php
// pages/technicians.php - Gestion complète des techniciens (CRUD)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Vérification des droits (admin ou superviseur seulement)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// ========== TRAITEMENT DES ACTIONS ==========

// Ajout d'un technicien
if($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "INSERT INTO technicians (employee_id, firstname, lastname, phone, email, specialty, hire_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['employee_id'],
        $_POST['firstname'],
        $_POST['lastname'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['specialty'],
        $_POST['hire_date'],
        $_POST['status']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'technician_created', "Technicien créé: {$_POST['employee_id']}");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Modification d'un technicien
if($action == 'edit' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $sql = "UPDATE technicians SET 
            employee_id = ?, 
            firstname = ?, 
            lastname = ?, 
            phone = ?, 
            email = ?, 
            specialty = ?, 
            hire_date = ?, 
            status = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['employee_id'],
        $_POST['firstname'],
        $_POST['lastname'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['specialty'],
        $_POST['hire_date'],
        $_POST['status'],
        $_GET['id']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'technician_updated', "Technicien ID: {$_GET['id']} modifié");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Suppression (soft delete - désactivation) avec validation mot de passe
if($action == 'delete' && isset($_GET['id']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor')) {
    if(isset($_POST['confirm_password'])) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if(password_verify($_POST['confirm_password'], $user['password'])) {
            $stmt2 = $pdo->prepare("UPDATE technicians SET status = 'inactive' WHERE id = ?");
            $stmt2->execute([$_GET['id']]);
            logUserAction($_SESSION['user_id'], 'technician_deleted', "Technicien ID: {$_GET['id']} désactivé");
            $message = "✅ " . t('save_success');
            echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
        } else {
            $error = "❌ " . t('password_error');
        }
    }
}

// Réactivation d'un technicien (admin uniquement)
if($action == 'restore' && isset($_GET['id']) && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE technicians SET status = 'active' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logUserAction($_SESSION['user_id'], 'technician_restored', "Technicien ID: {$_GET['id']} réactivé");
    $message = "✅ " . t('save_success');
    echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
}

// Récupération des techniciens
if($_SESSION['role'] == 'admin') {
    $technicians = $pdo->query("SELECT * FROM technicians ORDER BY lastname ASC")->fetchAll();
} else {
    $technicians = $pdo->query("SELECT * FROM technicians WHERE status != 'inactive' ORDER BY lastname ASC")->fetchAll();
}

// Récupération des interventions assignées à chaque technicien
$interventions_count = [];
foreach($technicians as $tech) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE intervenant_id = ? AND task_status NOT IN ('termine', 'cloturee')");
    $stmt->execute([$tech['id']]);
    $interventions_count[$tech['id']] = $stmt->fetchColumn();
}

// Récupération de l'historique des modifications pour chaque technicien
$history = [];
foreach($technicians as $tech) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('technician_created', 'technician_updated', 'technician_deleted', 'technician_restored')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$tech['id']}%"]);
    $history[$tech['id']] = $stmt->fetchAll();
}

// Statistiques
$active_count = count(array_filter($technicians, function($t) { return $t['status'] == 'active'; }));
$leave_count = count(array_filter($technicians, function($t) { return $t['status'] == 'on_leave'; }));
$inactive_count = count(array_filter($technicians, function($t) { return $t['status'] == 'inactive'; }));

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
        <i class="fas fa-user-plus"></i> <?php echo t('add_technician'); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('employee_id'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('firstname'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="firstname" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('lastname'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="lastname" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('phone'); ?></label>
                    <input type="tel" name="phone" class="form-control" placeholder="06 12 34 56 78">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('email'); ?></label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('specialty'); ?></label>
                    <input type="text" name="specialty" class="form-control" placeholder="<?php echo t('specialty_placeholder'); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('hire_date'); ?></label>
                    <input type="date" name="hire_date" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('status'); ?></label>
                    <select name="status" class="form-select">
                        <option value="active">🟢 <?php echo t('active'); ?></option>
                        <option value="inactive">🔴 <?php echo t('inactive'); ?></option>
                        <option value="on_leave">🟡 <?php echo t('on_leave'); ?></option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== FORMULAIRE DE MODIFICATION ==========
if($action == 'edit' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $tech = $stmt->fetch();
    if(!$tech) {
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
        <i class="fas fa-user-edit"></i> <?php echo t('edit_technician'); ?> : <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
    </div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('employee_id'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($tech['employee_id']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('firstname'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($tech['firstname']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('lastname'); ?> <span class="text-danger">*</span></label>
                    <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($tech['lastname']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('phone'); ?></label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($tech['phone']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('email'); ?></label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($tech['email']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('specialty'); ?></label>
                    <input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($tech['specialty']); ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('hire_date'); ?></label>
                    <input type="date" name="hire_date" class="form-control" value="<?php echo $tech['hire_date']; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label"><?php echo t('status'); ?></label>
                    <select name="status" class="form-select">
                        <option value="active" <?php if($tech['status'] == 'active') echo 'selected'; ?>>🟢 <?php echo t('active'); ?></option>
                        <option value="inactive" <?php if($tech['status'] == 'inactive') echo 'selected'; ?>>🔴 <?php echo t('inactive'); ?></option>
                        <option value="on_leave" <?php if($tech['status'] == 'on_leave') echo 'selected'; ?>>🟡 <?php echo t('on_leave'); ?></option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
            </div>
        </form>
    </div>
</div>
<?php
return;
endif;

// ========== MODAL DE CONFIRMATION SUPPRESSION ==========
if($action == 'delete' && isset($_GET['id'])):
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $tech = $stmt->fetch();
    if(!$tech) {
        echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
        return;
    }
?>
<div class="form-card">
    <div class="form-card-header" style="background: linear-gradient(135deg, #dc3545, #c82333);">
        <i class="fas fa-trash-alt"></i> <?php echo t('delete_technician'); ?>
    </div>
    <div class="card-body p-4">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></strong>
        </div>
        <p><?php echo t('delete_warning_technician'); ?></p>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><?php echo t('confirm_password'); ?></label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
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
    .status-inactive { background: #6c757d; color: white; }
    .status-on_leave { background: #ffc107; color: #333; }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .history-item {
        padding: 5px 0;
        font-size: 10px;
        border-bottom: 1px solid #eee;
    }
    .history-item:last-child {
        border-bottom: none;
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
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> <?php echo t('technicians'); ?></h2>
        <a href="?page=technicians&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('add_technician'); ?>
        </a>
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
    
    <!-- Cartes statistiques -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card" onclick="window.location.href='?page=technicians&status=active'">
                <div class="stats-number text-success"><?php echo $active_count; ?></div>
                <div class="text-muted"><?php echo t('technicians_active'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" onclick="window.location.href='?page=technicians&status=on_leave'">
                <div class="stats-number text-warning"><?php echo $leave_count; ?></div>
                <div class="text-muted"><?php echo t('on_leave'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card" onclick="window.location.href='?page=technicians&status=inactive'">
                <div class="stats-number text-secondary"><?php echo $inactive_count; ?></div>
                <div class="text-muted"><?php echo t('inactive'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Liste des techniciens -->
    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-list"></i> <?php echo t('technician_list'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('employee_id'); ?></th>
                            <th><?php echo t('lastname'); ?></th>
                            <th><?php echo t('firstname'); ?></th>
                            <th><?php echo t('specialty'); ?></th>
                            <th><?php echo t('phone'); ?></th>
                            <th><?php echo t('email'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('hire_date'); ?></th>
                            <th><?php echo t('active_interventions'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($technicians as $tech): ?>
                        <tr class="table-row-clickable" onclick="window.location.href='?page=technician_detail&id=<?php echo $tech['id']; ?>'">
                            <td><strong><?php echo htmlspecialchars($tech['employee_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($tech['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($tech['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($tech['specialty'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($tech['phone'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($tech['email'] ?: '-'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $tech['status']; ?>">
                                    <?php 
                                    if($tech['status'] == 'active') echo '🟢 ' . t('active');
                                    elseif($tech['status'] == 'inactive') echo '⚫ ' . t('inactive');
                                    else echo '🟡 ' . t('on_leave');
                                    ?>
                                </span>
                            </td>
                            <td><?php echo $tech['hire_date'] ? date('d/m/Y', strtotime($tech['hire_date'])) : '-'; ?></td>
                            <td>
                                <?php 
                                $count = $interventions_count[$tech['id']] ?? 0;
                                if($count > 0) {
                                    echo '<span class="badge bg-warning text-dark">' . $count . '</span>';
                                } else {
                                    echo '<span class="text-muted">0</span>';
                                }
                                ?>
                            </td>
                            <td style="max-width: 120px;">
                                <?php if(!empty($history[$tech['id']])): ?>
                                    <?php foreach(array_slice($history[$tech['id']], 0, 2) as $h): ?>
                                    <div class="history-item">
                                        <?php
                                        $action_icons = [
                                            'technician_created' => '🟢 ' . t('created'),
                                            'technician_updated' => '✏️ ' . t('modified'),
                                            'technician_deleted' => '🗑️ ' . t('deactivated'),
                                            'technician_restored' => '🔄 ' . t('restored')
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
                                <?php if($tech['status'] != 'inactive'): ?>
                                    <a href="?page=technician_detail&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=technicians&action=edit&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=technicians&action=delete&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="?page=technician_detail&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=technicians&action=restore&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('restore'); ?>" onclick="return confirm('<?php echo t('restore_confirm'); ?>')">
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
                        <div><span class="status-badge status-active">🟢 <?php echo t('active'); ?></span> <small><?php echo t('active_desc'); ?></small></div>
                        <div><span class="status-badge status-on_leave">🟡 <?php echo t('on_leave'); ?></span> <small><?php echo t('on_leave_desc'); ?></small></div>
                        <div><span class="status-badge status-inactive">⚫ <?php echo t('inactive'); ?></span> <small><?php echo t('inactive_desc'); ?></small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>