<?php
// pages/profile.php - Contenu du profil
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

$user_id = $_SESSION['user_id'];
$user = getUser($user_id);
$message = '';
$error = '';

// Traitement du changement de langue
if(isset($_POST['change_language'])) {
    setLanguage($_POST['language']);
    $message = t('language_updated');
    // Rediriger pour rafraîchir la page
    echo "<meta http-equiv='refresh' content='1;url=?page=profile'>";
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
        if($stmt->execute([$_POST['fullname'], $_POST['email'], $user_id])) {
            logUserAction($user_id, 'profile_updated', 'Profil mis à jour');
            $message = "✅ " . t('save_success');
            $user = getUser($user_id);
        }
    }
    
    if(isset($_POST['change_password'])) {
        if(password_verify($_POST['current_password'], $user['password'])) {
            if($_POST['new_password'] == $_POST['confirm_password']) {
                if(strlen($_POST['new_password']) >= 6) {
                    updateUserPassword($user_id, $_POST['new_password']);
                    logUserAction($user_id, 'password_changed', 'Mot de passe modifié');
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
                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
            </div>
            <div class="small mt-2">
                <i class="fas fa-clock"></i> <?php echo t('last_connection'); ?><br>
                <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'; ?>
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
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('new_password'); ?></label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                        <small class="text-muted"><?php echo t('password_too_short'); ?></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo t('confirm_password'); ?></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                        <small class="text-muted" id="passwordMatchMsg"></small>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-warning"><?php echo t('change_password'); ?></button>
                </form>
            </div>
        </div>
        
        <!-- Sélecteur de langue -->
        <div class="info-card mt-4">
            <div class="info-card-header">
                <i class="fas fa-language"></i> <?php echo t('select_language'); ?>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="?page=profile">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <select name="language" class="form-select" id="languageSelect">
                                <option value="en" <?php echo (getCurrentLanguage() == 'en') ? 'selected' : ''; ?>>🇬🇧 English</option>
                                <option value="fr" <?php echo (getCurrentLanguage() == 'fr') ? 'selected' : ''; ?>>🇫🇷 Français</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <button type="submit" name="change_language" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo t('save'); ?>
                            </button>
                        </div>
                    </div>
                </form>
                <div class="mt-3 small text-muted">
                    <i class="fas fa-info-circle"></i> <?php echo t('language_updated'); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkPasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    const msgSpan = document.getElementById('passwordMatchMsg');
    
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

document.getElementById('new_password').addEventListener('keyup', checkPasswordMatch);
document.getElementById('confirm_password').addEventListener('keyup', checkPasswordMatch);
</script>