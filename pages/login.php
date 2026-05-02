<?php
// pages/login.php - Page de connexion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';

$error = '';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        if($user['is_active']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $update = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
            $update->execute([$ip, $user['id']]);
            
            $log = $pdo->prepare("INSERT INTO user_logs (user_id, action, details, ip_address) VALUES (?, 'login_success', ?, ?)");
            $log->execute([$user['id'], t('login_success'), $ip]);
            
            header('Location: index.php?page=dashboard');
            exit();
        } else {
            $error = t('account_disabled');
        }
    } else {
        $error = t('login_error');
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            margin-top: 100px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeInUp 0.6s ease-out;
            overflow: hidden;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 30px;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: scale(1.02);
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #aaa;
        }
        .input-icon input {
            padding-left: 45px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card border-0">
                    <div class="login-header">
                        <i class="fas fa-industry fa-3x mb-3"></i>
                        <h3 class="mb-0"><?php echo t('login_title'); ?></h3>
                        <p class="mb-0 mt-2 opacity-75"><?php echo t('login_subtitle'); ?></p>
                    </div>
                    <div class="login-body">
                        <?php if($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3 input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="username" class="form-control form-control-lg" placeholder="<?php echo t('username'); ?>" required autofocus>
                            </div>
                            <div class="mb-3 input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" class="form-control form-control-lg" placeholder="<?php echo t('password'); ?>" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary btn-login w-100 btn-lg">
                                <i class="fas fa-sign-in-alt"></i> <?php echo t('login'); ?>
                            </button>
                        </form>
                        <hr class="my-4">
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> <?php echo t('demo_accounts'); ?> :<br>
                                <strong>admin</strong> / admin123<br>
                                <strong>superviseur</strong> / demo123<br>
                                <strong>technicien</strong> / demo123<br>
                                <strong>visiteur</strong> / demo123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>