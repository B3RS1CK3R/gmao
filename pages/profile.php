<?php
// pages/profile.php - User Profile
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
        if($stmt->execute([$_POST['fullname'], $_POST['email'], $user_id])) {
            logUserAction($user_id, 'profile_updated', 'Profile updated');
            $message = "✅ " . t('save_success');
            $user = getUser($user_id);
        } else {
            $error = "❌ " . t('save_error');
        }
    }
    
    if(isset($_POST['change_password'])) {
        if(password_verify($_POST['current_password'], $user['password'])) {
            if($_POST['new_password'] == $_POST['confirm_password']) {
                if(strlen($_POST['new_password']) >= 6) {
                    updateUserPassword($user_id, $_POST['new_password']);
                    logUserAction($user_id, 'password_changed', 'Password changed');
                    $message = "✅ " . t('save_success');
                } else {
                    $error = "❌ " . t('password_too_short');
                }
            } else {
                $error = "❌ " . t('password_mismatch');
            }
        } else {
            $error = "❌ " . t('current_password') . " incorrect";
        }
    }
}
?>

<style>
    .profile-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 30px;
        margin-bottom: 20px;
        text-align: center;
    }
    .profile-avatar {
        font-size: 80px;
        margin-bottom: 20px;
    }
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .info-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .role-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .role-admin { background: #dc3545; color: white; }
    .role-supervisor { background: #fd7e14; color: white; }
    .role-technician { background: #17a2b8; color: white; }
    .role-viewer { background: #6c757d; color: white; }
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
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .input-group {
        position: relative;
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }
    .input-group .form-control {
        position: relative;
        flex: 1 1 auto;
        width: 1%;
        min-width: 0;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
    .input-group .btn-outline-secondary {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border: 1px solid #ddd;
        border-left: none;
        background: white;
    }
    .input-group .btn-outline-secondary:hover {
        background: #f0f0f0;
    }
</style>

<div class="row">
    <div class="col-md-4">
        <div class="profile-card">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <h4><?php echo htmlspecialchars($user['fullname'] ?: $user['username']); ?></h4>
            <p class="mb-0">
                <span class="role-badge role-<?php echo $user['role']; ?>">
                    <?php 
                    if($user['role'] == 'admin') echo '👑 ' . t('admin');
                    elseif($user['role'] == 'supervisor') echo '📋 ' . t('supervisor');
                    elseif($user['role'] == 'technician') echo '🔧 ' . t('technician_role');
                    else echo '👁️ ' . t('viewer');
                    ?>
                </span>
            </p>
            <hr class="bg-light">
            <div class="small">
                <i class="fas fa-calendar"></i> <?php echo t('member_since'); ?><br>
                <?php echo $user['created_at'] ? format_date_us($user['created_at'], true) : '-'; ?>
            </div>
            <div class="small mt-2">
                <i class="fas fa-clock"></i> <?php echo t('last_connection'); ?><br>
                <?php echo $user['last_login'] ? format_date_us($user['last_login'], true) : t('never'); ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
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
            <div class="info-card-header">
                <i class="fas fa-user"></i> <?php echo t('personal_info'); ?>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('username'); ?></label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('fullname'); ?></label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('email'); ?></label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('role'); ?></label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary"><?php echo t('update_profile'); ?></button>
                </form>
            </div>
        </div>
        
        <div class="info-card mt-4">
            <div class="info-card-header">
                <i class="fas fa-key"></i> <?php echo t('change_password'); ?>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('current_password'); ?></label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('new_password'); ?></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted"><?php echo t('password_too_short'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('confirm_password'); ?></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="passwordMatchMsg"></small>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning"><?php echo t('change_password'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    // Find the button (next sibling or previous sibling depending on structure)
    let button = field.nextElementSibling;
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

function checkPasswordMatch() {
    const password = document.getElementById('new_password')?.value;
    const confirm = document.getElementById('confirm_password')?.value;
    const msgSpan = document.getElementById('passwordMatchMsg');
    
    if (msgSpan) {
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

const newPwd = document.getElementById('new_password');
const confirmPwd = document.getElementById('confirm_password');
if (newPwd && confirmPwd) {
    newPwd.addEventListener('keyup', checkPasswordMatch);
    confirmPwd.addEventListener('keyup', checkPasswordMatch);
}
</script>