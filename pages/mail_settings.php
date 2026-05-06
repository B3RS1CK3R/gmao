<?php
// pages/mail_settings.php - Configuration des emails (admin uniquement)
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$settings_file = __DIR__ . '/../config/mail_config.php';
$current_settings = file_exists($settings_file) ? file_get_contents($settings_file) : '';
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $alert_emails_array = array_map('trim', explode(',', $_POST['alert_emails']));
    $alert_emails_php = var_export($alert_emails_array, true);
    
    $config_content = "<?php
// config/mail_config.php - Configuration email
// DerniÃ¨re modification : " . date('Y-m-d H:i:s') . "

// Configuration SMTP
define('SMTP_HOST', '{$_POST['smtp_host']}');
define('SMTP_PORT', {$_POST['smtp_port']});
define('SMTP_USER', '{$_POST['smtp_user']}');
define('SMTP_PASS', '{$_POST['smtp_pass']}');
define('SMTP_SECURE', '{$_POST['smtp_secure']}');

// Email expÃ©diteur
define('FROM_EMAIL', '{$_POST['from_email']}');
define('FROM_NAME', '{$_POST['from_name']}');

// Destinataires des alertes
define('ALERT_EMAILS', $alert_emails_php);
?>";
    
    if(file_put_contents($settings_file, $config_content)) {
        logUserAction($_SESSION['user_id'], 'mail_settings_updated', 'Email configuration updated');
        $message = "✅ " . t('save_success');
        
        if(!empty($_POST['test_email'])) {
            $test_subject = "✅ " . t('test_email_subject');
            $test_message = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { padding: 20px; background: #f8f9fa; border-radius: 0 0 10px 10px; }
                </style>
            </head>
            <body>
                <div class='header'>
                    <h2>GMAO Industrielle</h2>
                </div>
                <div class='content'>
                    <h3>✅ " . t('test_email_success') . "</h3>
                    <p>" . t('test_email_message') . "</p>
                    <p>" . t('test_email_date') . " : " . format_date_us(date('Y-m-d H:i:s'), true) . "</p>
                    <hr>
                    <small>GMAO Industrielle - " . t('login_subtitle') . "</small>
                </div>
            </body>
            </html>
            ";
            if(sendEmail($_POST['test_email'], $test_subject, $test_message, true)) {
                $message .= "<br>📧 " . t('test_email_sent') . " {$_POST['test_email']}";
            } else {
                $error = "⚠️ " . t('test_email_failed');
            }
        }
    } else {
        $error = "❌ " . t('save_error');
    }
}

// Récupérer les valeurs actuelles pour les afficher dans le formulaire
$smtp_host = '';
$smtp_port = '587';
$smtp_user = '';
$smtp_pass = '';
$smtp_secure = 'tls';
$from_email = 'noreply@gmao.com';
$from_name = 'GMAO Industrielle';
$alert_emails = '';

if(file_exists($settings_file)) {
    $content = file_get_contents($settings_file);
    if(preg_match("/define\('SMTP_HOST', '([^']+)'\)/", $content, $m)) { $smtp_host = $m[1]; }
    if(preg_match("/define\('SMTP_PORT', (\d+)\)/", $content, $m)) { $smtp_port = $m[1]; }
    if(preg_match("/define\('SMTP_USER', '([^']+)'\)/", $content, $m)) { $smtp_user = $m[1]; }
    if(preg_match("/define\('SMTP_PASS', '([^']+)'\)/", $content, $m)) { $smtp_pass = $m[1]; }
    if(preg_match("/define\('SMTP_SECURE', '([^']+)'\)/", $content, $m)) { $smtp_secure = $m[1]; }
    if(preg_match("/define\('FROM_EMAIL', '([^']+)'\)/", $content, $m)) { $from_email = $m[1]; }
    if(preg_match("/define\('FROM_NAME', '([^']+)'\)/", $content, $m)) { $from_name = $m[1]; }
    if(preg_match("/define\('ALERT_EMAILS', array\(([^)]+)\)/", $content, $m)) {
        $alert_emails = str_replace(["'", " ", "\n", "\r"], "", $m[1]);
    }
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
    .config-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .config-card-header.info {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }
    .help-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 5px;
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
    .btn-outline-secondary {
        border-radius: 8px;
    }
    .list-unstyled li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .list-unstyled li:last-child {
        border-bottom: none;
    }
    .alert {
        border-radius: 10px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-envelope"></i> <?php echo t('email_config'); ?></h2>
        <a href="?page=dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
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
    
    <div class="row">
        <div class="col-md-8">
            <div class="config-card">
                <div class="config-card-header">
                    <i class="fas fa-cog"></i> <?php echo t('smtp_settings'); ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label"><?php echo t('smtp_host'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="smtp_host" class="form-control" value="<?php echo htmlspecialchars($smtp_host); ?>" required>
                                <div class="help-text"><?php echo t('smtp_host_help'); ?></div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label"><?php echo t('smtp_port'); ?> <span class="text-danger">*</span></label>
                                <input type="number" name="smtp_port" class="form-control" value="<?php echo $smtp_port; ?>" required>
                                <div class="help-text"><?php echo t('smtp_port_help'); ?></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('smtp_user'); ?></label>
                                <input type="email" name="smtp_user" class="form-control" value="<?php echo htmlspecialchars($smtp_user); ?>" placeholder="votre.email@gmail.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('smtp_password'); ?></label>
                                <div class="input-group">
                                    <input type="password" name="smtp_pass" id="smtp_pass" class="form-control" value="<?php echo htmlspecialchars($smtp_pass); ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="help-text">
                                    <?php echo t('smtp_password_help'); ?>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('smtp_secure'); ?></label>
                                <select name="smtp_secure" class="form-select">
                                    <option value="tls" <?php if($smtp_secure == 'tls') echo 'selected'; ?>><?php echo t('tls'); ?></option>
                                    <option value="ssl" <?php if($smtp_secure == 'ssl') echo 'selected'; ?>><?php echo t('ssl'); ?></option>
                                    <option value="" <?php if($smtp_secure == '') echo 'selected'; ?>><?php echo t('none'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('sender_email'); ?> <span class="text-danger">*</span></label>
                                <input type="email" name="from_email" class="form-control" value="<?php echo htmlspecialchars($from_email); ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo t('sender_name'); ?></label>
                                <input type="text" name="from_name" class="form-control" value="<?php echo htmlspecialchars($from_name); ?>">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo t('alert_recipients'); ?> <span class="text-danger">*</span></label>
                                <input type="text" name="alert_emails" class="form-control" value="<?php echo htmlspecialchars($alert_emails); ?>" placeholder="email1@exemple.com,email2@exemple.com" required>
                                <div class="help-text"><?php echo t('alert_recipients_help'); ?></div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo t('test_email'); ?></label>
                                <input type="email" name="test_email" class="form-control" placeholder="test@exemple.com">
                                <div class="help-text"><?php echo t('test_email_help'); ?></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo t('save_and_test'); ?>
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="resetForm()">
                                <i class="fas fa-undo"></i> <?php echo t('reset'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="config-card">
                <div class="config-card-header info">
                    <i class="fas fa-question-circle"></i> <?php echo t('help'); ?>
                </div>
                <div class="card-body p-4">
                    <h6 class="mb-3">📖 <?php echo t('common_providers'); ?></h6>
                    <ul class="list-unstyled">
                        <li><strong>Gmail :</strong><br><code>smtp.gmail.com</code> - <?php echo t('port'); ?> 587 - TLS</li>
                        <li><strong>Outlook/Office365 :</strong><br><code>smtp.office365.com</code> - <?php echo t('port'); ?> 587 - TLS</li>
                        <li><strong>Yahoo :</strong><br><code>smtp.mail.yahoo.com</code> - <?php echo t('port'); ?> 465 - SSL</li>
                        <li><strong>Free :</strong><br><code>smtp.free.fr</code> - <?php echo t('port'); ?> 465 - SSL</li>
                        <li><strong>Orange :</strong><br><code>smtp.orange.fr</code> - <?php echo t('port'); ?> 465 - SSL</li>
                        <li><strong>OVH :</strong><br><code>ssl0.ovh.net</code> - <?php echo t('port'); ?> 587 - TLS</li>
                    </ul>
                </div>
            </div>
            
            <div class="config-card">
                <div class="config-card-header info">
                    <i class="fas fa-bell"></i> <?php echo t('automatic_alerts'); ?>
                </div>
                <div class="card-body p-4">
                    <p><?php echo t('alerts_description'); ?></p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-calendar-times text-warning"></i> <?php echo t('maintenance_overdue'); ?></li>
                        <li><i class="fas fa-boxes text-danger"></i> <?php echo t('critical_stock'); ?></li>
                        <li><i class="fas fa-skull-crosswalk text-danger"></i> <?php echo t('critical_intervention'); ?></li>
                        <li><i class="fas fa-chart-line text-info"></i> <?php echo t('weekly_reports'); ?></li>
                    </ul>
                    <hr>
                    <p class="small text-muted mb-0">
                        <i class="fas fa-clock"></i> <?php echo t('alerts_check_hourly'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passInput = document.getElementById('smtp_pass');
    if(passInput.type === 'password') {
        passInput.type = 'text';
    } else {
        passInput.type = 'password';
    }
}

function resetForm() {
    if(confirm('<?php echo t('reset_confirm'); ?>')) {
        document.querySelector('form').reset();
        document.querySelector('input[name="smtp_port"]').value = '587';
        document.querySelector('select[name="smtp_secure"]').value = 'tls';
        document.querySelector('input[name="from_email"]').value = 'noreply@gmao.com';
        document.querySelector('input[name="from_name"]').value = 'GMAO Industrielle';
    }
}
</script>