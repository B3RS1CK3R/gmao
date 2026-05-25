<?php
// pages/users.php - User Management (admin only)
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Traitement des actions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($action == 'add') {
        if($_POST['password'] !== $_POST['confirm_password']) {
            $error = "❌ " . t('password_mismatch');
        } elseif(strlen($_POST['password']) < 6) {
            $error = "❌ " . t('password_too_short');
        } else {
            if(createUser($_POST['username'], $_POST['password'], $_POST['fullname'], $_POST['role'], $_POST['email'])) {
                logUserAction($_SESSION['user_id'], 'user_created', "User created: {$_POST['username']} (Role: {$_POST['role']}, Full name: {$_POST['fullname']})");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=users'>";
            } else {
                $error = "❌ " . t('save_error');
            }
        }
    }
    
    if($action == 'edit' && isset($_GET['id'])) {
        if(updateUser($_GET['id'], $_POST['fullname'], $_POST['role'], $_POST['email'], $_POST['is_active'])) {
            logUserAction($_SESSION['user_id'], 'user_updated', "User modified ID: {$_GET['id']} - New role: {$_POST['role']}, Status: " . ($_POST['is_active'] ? 'Active' : 'Inactive'));
            $message = "✅ " . t('save_success');
            echo "<meta http-equiv='refresh' content='1;url=?page=users'>";
        } else {
            $error = "❌ " . t('save_error');
        }
    }
    
    if($action == 'reset_password' && isset($_GET['id'])) {
        if(strlen($_POST['new_password']) < 6) {
            $error = "❌ " . t('password_too_short');
        } elseif($_POST['new_password'] !== $_POST['confirm_new_password']) {
            $error = "❌ " . t('password_mismatch');
        } else {
            if(updateUserPassword($_GET['id'], $_POST['new_password'])) {
                $user = getUser($_GET['id']);
                logUserAction($_SESSION['user_id'], 'password_reset', "Password reset for user: " . ($user ? $user['username'] : "ID: {$_GET['id']}") . " by administrator");
                $message = "✅ " . t('save_success');
                echo "<meta http-equiv='refresh' content='1;url=?page=users'>";
            } else {
                $error = "❌ " . t('save_error');
            }
        }
    }
}

if($action == 'delete' && isset($_GET['id'])) {
    $user = getUser($_GET['id']);
    if(deleteUser($_GET['id'])) {
        logUserAction($_SESSION['user_id'], 'user_deleted', "User permanently deleted: " . ($user ? $user['username'] : "ID: {$_GET['id']}") . " (Role: " . ($user ? $user['role'] : 'unknown') . ")");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=users'>";
    } else {
        $error = "❌ " . t('delete_error');
    }
}

if($message && $action != 'add' && $action != 'edit' && $action != 'reset_password') {
    echo "<meta http-equiv='refresh' content='1;url=?page=users'>";
}

$users = getAllUsers();
$logs = getUserLogs(null, 50);
if(!$logs) $logs = [];
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-card-header.success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }
    .form-card-header.warning {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
    }
    .form-card-header.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    .role-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .role-admin { background: #dc3545; color: white; }
    .role-supervisor { background: #fd7e14; color: white; }
    .role-technician { background: #17a2b8; color: white; }
    .role-viewer { background: #6c757d; color: white; }
    .status-active { color: #28a745; font-weight: bold; }
    .status-inactive { color: #dc3545; font-weight: bold; }
    
    /* Tableau des utilisateurs */
    .users-table {
        width: 100%;
        border-collapse: collapse;
    }
    .users-table th, .users-table td {
        padding: 12px 10px;
        vertical-align: middle;
    }
    .users-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
    }
    .users-table tr:hover {
        background: #f8f9fa;
    }
    
    /* Largeurs des colonnes */
    .col-username { width: 10%; }
    .col-fullname { width: 12%; }
    .col-email { width: 18%; }
    .col-role { width: 12%; }
    .col-status { width: 8%; }
    .col-lastlogin { width: 12%; }
    .col-technician { width: 12%; }
    .col-actions { width: 12%; }
    
    /* Boutons d'action */
    .action-buttons {
        display: flex;
        gap: 5px;
        justify-content: center;
        flex-wrap: nowrap;
    }
    .action-buttons .btn {
        padding: 5px 10px;
        font-size: 12px;
        border-radius: 5px;
        white-space: nowrap;
    }
    
    /* Journal des actions - Improved */
    .log-table {
        width: 100%;
        border-collapse: collapse;
    }
    .log-table th, .log-table td {
        padding: 10px 12px;
        vertical-align: middle;
    }
    .log-table th {
        background: #343a40;
        color: white;
        font-weight: 600;
    }
    .log-col-date { width: 14%; }
    .log-col-user { width: 10%; }
    .log-col-action { width: 14%; }
    .log-col-details { width: 52%; }
    .log-col-ip { width: 10%; }
    
    /* Style for log details to make them more readable */
    .log-details-content {
        font-size: 13px;
        line-height: 1.4;
        color: #333;
    }
    .log-badge {
        display: inline-block;
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 11px;
    }
    
    /* Formulaires */
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
    .btn-sm {
        padding: 5px 10px;
        font-size: 11px;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        .users-table-container {
            overflow-x: auto;
        }
        .users-table {
            min-width: 900px;
        }
        .log-table-container {
            overflow-x: auto;
        }
        .log-table {
            min-width: 800px;
        }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users-cog"></i> <?php echo t('users'); ?></h2>
        <a href="?page=users&action=add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('add_user'); ?>
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
    
    <!-- Formulaire d'ajout -->
    <?php if($action == 'add'): ?>
    <div class="form-card">
        <div class="form-card-header success">
            <i class="fas fa-user-plus"></i> <?php echo t('add_user'); ?>
        </div>
        <div class="card-body p-4">
            <form method="POST" id="addUserForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('username'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('fullname'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="fullname" id="fullname" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('email'); ?></label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('role'); ?> <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="viewer">👁️ <?php echo t('viewer'); ?></option>
                            <option value="technician">🔧 <?php echo t('technician'); ?></option>
                            <option value="supervisor">📋 <?php echo t('supervisor'); ?></option>
                            <option value="admin">👑 <?php echo t('administrator'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('password'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted"><?php echo t('password_too_short'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('confirm_password'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="passwordMatchMsg"></small>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                    <a href="?page=users" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire d'édition -->
    <?php if($action == 'edit' && isset($_GET['id'])): 
        $user = getUser($_GET['id']);
        if($user):
    ?>
    <div class="form-card">
        <div class="form-card-header warning">
            <i class="fas fa-user-edit"></i> <?php echo t('edit_user'); ?> : <?php echo htmlspecialchars($user['username']); ?>
        </div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('fullname'); ?></label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('email'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('role'); ?></label>
                        <select name="role" class="form-select">
                            <option value="viewer" <?php if($user['role'] == 'viewer') echo 'selected'; ?>><?php echo t('viewer'); ?></option>
                            <option value="technician" <?php if($user['role'] == 'technician') echo 'selected'; ?>><?php echo t('technician'); ?></option>
                            <option value="supervisor" <?php if($user['role'] == 'supervisor') echo 'selected'; ?>><?php echo t('supervisor'); ?></option>
                            <option value="admin" <?php if($user['role'] == 'admin') echo 'selected'; ?>><?php echo t('administrator'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="is_active" class="form-select">
                            <option value="1" <?php if($user['is_active']) echo 'selected'; ?>><?php echo t('active'); ?></option>
                            <option value="0" <?php if(!$user['is_active']) echo 'selected'; ?>><?php echo t('inactive'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=users" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; endif; ?>
    
    <!-- Formulaire de réinitialisation mot de passe -->
    <?php if($action == 'reset_password' && isset($_GET['id'])): 
        $user = getUser($_GET['id']);
        if($user && $user['username'] != 'admin'):
    ?>
    <div class="form-card">
        <div class="form-card-header danger">
            <i class="fas fa-key"></i> <?php echo t('reset_password'); ?> : <?php echo htmlspecialchars($user['username']); ?>
        </div>
        <div class="card-body p-4">
            <form method="POST" id="resetPasswordForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('new_password'); ?></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted"><?php echo t('password_too_short'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('confirm_password'); ?></label>
                        <div class="input-group">
                            <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="resetPasswordMatchMsg"></small>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-key"></i> <?php echo t('reset_password'); ?></button>
                    <a href="?page=users" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; endif; ?>
    
    <!-- Liste des utilisateurs -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> <?php echo t('user_list'); ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th class="col-username"><?php echo t('username'); ?></th>
                            <th class="col-fullname"><?php echo t('fullname'); ?></th>
                            <th class="col-email"><?php echo t('email'); ?></th>
                            <th class="col-role"><?php echo t('role'); ?></th>
                            <th class="col-status"><?php echo t('status'); ?></th>
                            <th class="col-lastlogin"><?php echo t('last_connection'); ?></th>
                            <th class="col-technician"><?php echo t('technician'); ?></th>
                            <th class="col-actions"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): 
                            $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM technicians WHERE user_id = ?");
                            $stmt->execute([$user['id']]);
                            $tech = $stmt->fetch();
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php 
                                    if($user['role'] == 'admin') echo '👑 ' . t('admin');
                                    elseif($user['role'] == 'supervisor') echo '📋 ' . t('supervisor');
                                    elseif($user['role'] == 'technician') echo '🔧 ' . t('technician');
                                    else echo '👁️ ' . t('viewer');
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if($user['is_active']): ?>
                                    <span class="status-active"><i class="fas fa-circle" style="font-size: 8px;"></i> <?php echo t('active'); ?></span>
                                <?php else: ?>
                                    <span class="status-inactive"><i class="fas fa-circle" style="font-size: 8px;"></i> <?php echo t('inactive'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $user['last_login'] ? format_date_us($user['last_login'], true) : t('never'); ?></td>
                            <td>
                                <?php if($tech): ?>
                                    <a href="?page=technician_detail&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info" style="white-space: nowrap;">
                                        <i class="fas fa-user-cog"></i> <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                    </a>
                                <?php elseif($user['role'] == 'technician'): ?>
                                    <span class="text-muted">-</span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <?php if($user['username'] != 'admin'): ?>
                                    <a href="?page=users&action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=users&action=reset_password&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('reset_password'); ?>">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a href="?page=users&action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>" onclick="return confirm('<?php echo t('delete_confirm'); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted"><i class="fas fa-lock"></i> <?php echo t('protected'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Journal des actions - IMPROVED VERSION with clearer messages -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> <?php echo t('action_log'); ?></h5>
        </div>
        <div class="card-body p-0">
            <div class="log-table-container">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th class="log-col-date"><?php echo t('date'); ?></th>
                            <th class="log-col-user"><?php echo t('user'); ?></th>
                            <th class="log-col-action"><?php echo t('action'); ?></th>
                            <th class="log-col-details"><?php echo t('details'); ?></th>
                            <th class="log-col-ip"><?php echo t('ip'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox"></i> <?php echo t('no_logs'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): 
                                // Generate detailed, human-readable action descriptions
                                $action_icon = '';
                                $action_label = '';
                                $detailed_message = '';
                                
                                switch($log['action']) {
                                    case 'login_success':
                                        $action_icon = '🔓';
                                        $action_label = 'Login Success';
                                        $detailed_message = 'User successfully authenticated and logged into the system';
                                        break;
                                        
                                    case 'login_failed':
                                        $action_icon = '🔒';
                                        $action_label = 'Login Failed';
                                        $detailed_message = 'Failed login attempt - invalid credentials';
                                        break;
                                        
                                    case 'logout':
                                        $action_icon = '🚪';
                                        $action_label = 'Logout';
                                        $detailed_message = 'User logged out of the system';
                                        break;
                                        
                                    case 'user_created':
                                        $action_icon = '👤+';
                                        $action_label = 'User Created';
                                        $detailed_message = 'New user account created: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'user_updated':
                                        $action_icon = '✏️';
                                        $action_label = 'User Updated';
                                        $detailed_message = 'User account modified: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'user_deleted':
                                        $action_icon = '🗑️';
                                        $action_label = 'User Deleted';
                                        $detailed_message = 'User account permanently removed: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'user_restored':
                                        $action_icon = '🔄';
                                        $action_label = 'User Restored';
                                        $detailed_message = 'Deleted user account restored: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'password_reset':
                                        $action_icon = '🔑';
                                        $action_label = 'Password Reset';
                                        $detailed_message = 'User password was reset by administrator: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'password_changed':
                                        $action_icon = '🔐';
                                        $action_label = 'Password Changed';
                                        $detailed_message = 'User changed their own password';
                                        break;
                                        
                                    case 'profile_updated':
                                        $action_icon = '📝';
                                        $action_label = 'Profile Updated';
                                        $detailed_message = 'User profile information modified: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'role_changed':
                                        $action_icon = '🎭';
                                        $action_label = 'Role Changed';
                                        $detailed_message = 'User role/permission level modified: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'equipment_created':
                                        $action_icon = '🖥️+';
                                        $action_label = 'Equipment Added';
                                        $detailed_message = 'New equipment added to inventory: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'equipment_updated':
                                        $action_icon = '🖥️✏️';
                                        $action_label = 'Equipment Updated';
                                        $detailed_message = 'Equipment information modified: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'equipment_deleted':
                                        $action_icon = '🖥️🗑️';
                                        $action_label = 'Equipment Deleted';
                                        $detailed_message = 'Equipment removed from inventory: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'intervention_created':
                                        $action_icon = '🔧+';
                                        $action_label = 'Intervention Created';
                                        $detailed_message = 'New maintenance intervention created: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'intervention_updated':
                                        $action_icon = '🔧✏️';
                                        $action_label = 'Intervention Updated';
                                        $detailed_message = 'Maintenance intervention modified: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'intervention_completed':
                                        $action_icon = '✅';
                                        $action_label = 'Intervention Completed';
                                        $detailed_message = 'Maintenance intervention marked as completed: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'attachment_uploaded':
                                        $action_icon = '📎+';
                                        $action_label = 'File Uploaded';
                                        $detailed_message = 'Document/file attached to record: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    case 'attachment_deleted':
                                        $action_icon = '📎🗑️';
                                        $action_label = 'File Deleted';
                                        $detailed_message = 'Document/file removed: ' . htmlspecialchars($log['details']);
                                        break;
                                        
                                    default:
                                        $action_icon = '📌';
                                        $action_label = ucwords(str_replace('_', ' ', $log['action']));
                                        $detailed_message = htmlspecialchars($log['details']);
                                }
                                
                                // If details are empty, provide a default meaningful message
                                if(empty($log['details']) || trim($log['details']) == '') {
                                    $detailed_message = 'No additional details available for this action';
                                } elseif(empty($action_label) || $action_label == ucwords(str_replace('_', ' ', $log['action']))) {
                                    $detailed_message = htmlspecialchars($log['details']);
                                }
                            ?>
                            <tr>
                                <td class="log-date"><small><?php echo format_date_us($log['created_at'], true); ?></small></td>
                                <td class="log-user">
                                    <strong><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></strong>
                                    <?php if(isset($log['username']) && $log['username'] == 'admin'): ?>
                                        <span class="log-badge">Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td class="log-action">
                                    <span style="white-space: nowrap;">
                                        <?php echo $action_icon; ?> 
                                        <strong><?php echo $action_label; ?></strong>
                                    </span>
                                </td>
                                <td class="log-details">
                                    <div class="log-details-content">
                                        <?php echo $detailed_message; ?>
                                    </div>
                                </td>
                                <td class="log-ip"><code><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    if (field.type === 'password') {
        field.type = 'text';
        button.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        field.type = 'password';
        button.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('password')?.value;
    const confirm = document.getElementById('confirm_password')?.value;
    const msgSpan = document.getElementById('passwordMatchMsg');
    if(msgSpan) {
        if (password !== confirm) {
            msgSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> <?php echo t('password_mismatch'); ?></span>';
            return false;
        } else if (password === '' && confirm === '') {
            msgSpan.innerHTML = '';
            return false;
        } else {
            msgSpan.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> <?php echo t('password_match'); ?></span>';
            return true;
        }
    }
    return true;
}

function checkResetPasswordMatch() {
    const password = document.getElementById('new_password')?.value;
    const confirm = document.getElementById('confirm_new_password')?.value;
    const msgSpan = document.getElementById('resetPasswordMatchMsg');
    if(msgSpan) {
        if (password !== confirm) {
            msgSpan.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> <?php echo t('password_mismatch'); ?></span>';
            return false;
        } else if (password === '' && confirm === '') {
            msgSpan.innerHTML = '';
            return false;
        } else {
            msgSpan.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> <?php echo t('password_match'); ?></span>';
            return true;
        }
    }
    return true;
}

const pwd = document.getElementById('password');
const confirmPwd = document.getElementById('confirm_password');
if(pwd && confirmPwd) {
    pwd.addEventListener('keyup', checkPasswordMatch);
    confirmPwd.addEventListener('keyup', checkPasswordMatch);
}

const newPwd = document.getElementById('new_password');
const confirmNewPwd = document.getElementById('confirm_new_password');
if(newPwd && confirmNewPwd) {
    newPwd.addEventListener('keyup', checkResetPasswordMatch);
    confirmNewPwd.addEventListener('keyup', checkResetPasswordMatch);
}
</script>