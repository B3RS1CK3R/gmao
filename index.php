<?php
// index.php - VERSION FACTORISÉE (250 lignes) - CORRIGÉE POUR ANGLAIS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/lang.php';

$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'list';

if ($page !== 'login' && !isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Pages qui utilisent la structure complète (sidebar + header)
$pages_with_sidebar = [
    'dashboard', 'performance', 'equipment', 'interventions', 
    'preventive', 'stock', 'technicians', 'planning', 'alerts',
    'mail_settings', 'users', 'export_center', 'profile', 'technician_detail'
];

// Pages simples sans sidebar
$pages_without_sidebar = [
    'equipment_qr', 'equipment_detail', 'assign_intervention', 
    'technician_schedule', 'intervention_add', 'intervention_view', 
    'calendar', 'equipment_edit', 'intervention_edit', 'preventive_edit',
    'stock_detail', 'technician_edit'
];

// Pages mobiles
$mobile_pages = [
    'mobile_dashboard', 'mobile_interventions', 'mobile_equipment', 
    'mobile_scan', 'mobile_profile', 'mobile_intervention_detail',
    'mobile_intervention_add', 'mobile_equipment_detail'
];

// ========== PAGES SIMPLES SANS SIDEBAR ==========
if (in_array($page, $pages_without_sidebar)) {
    require_once ROOT_PATH . '/includes/functions.php';
    include ROOT_PATH . '/pages/' . $page . '.php';
} 
// ========== PAGES MOBILES ==========
elseif (in_array($page, $mobile_pages)) {
    include ROOT_PATH . '/pages/' . $page . '.php';
} 
// ========== PAGES AVEC SIDEBAR ==========
elseif (in_array($page, $pages_with_sidebar)) {
    require_once ROOT_PATH . '/includes/functions.php';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GMAO Industrial - <?php echo ucfirst(str_replace('_', ' ', $page)); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="/gmao/assets/css/toast.css">
        <?php if($page == 'performance' || $page == 'dashboard'): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <?php endif; ?>
        <style>
            *{margin:0;padding:0;box-sizing:border-box}
            body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f4f6f9}
            .sidebar{min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);box-shadow:2px 0 10px rgba(0,0,0,0.1)}
            .sidebar .nav-link{color:rgba(255,255,255,0.9);padding:12px 20px;margin:5px 0;border-radius:10px;transition:all 0.3s}
            .sidebar .nav-link:hover{background:rgba(255,255,255,0.2);transform:translateX(5px);color:white}
            .sidebar .nav-link.active{background:rgba(255,255,255,0.3);color:white}
            .stat-card{transition:transform 0.3s;cursor:pointer;border:none;border-radius:15px}
            .stat-card:hover{transform:translateY(-5px)}
            .navbar-top{background:white;box-shadow:0 2px 10px rgba(0,0,0,0.1);padding:15px;border-radius:10px;margin-bottom:20px}
            .main-content{animation:fadeIn 0.5s ease-out}
            @keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
            .card{border-radius:15px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-bottom:20px;border:none}
            .card-header{border-radius:15px 15px 0 0;font-weight:bold}
            .table{background:white;border-radius:10px;overflow:hidden}
            .btn{border-radius:8px;padding:8px 20px}
            .btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);border:none}
            .btn-primary:hover{filter:brightness(0.95)}
            .btn-secondary{background:#6c757d;border:none}
            .btn-secondary:hover{background:#5a6268}
            .btn-warning{background:#fd7e14;border:none;color:white}
            .btn-warning:hover{background:#e06a0a}
            .btn-danger{background:#dc3545;border:none}
            .btn-info{background:#17a2b8;border:none}
            .btn-success{background:#28a745;border:none}
            .notification-badge{position:relative}
            .badge-count{position:absolute;top:0px;right:8px;background:#dc3545;color:white;border-radius:50%;padding:2px 6px;font-size:10px;font-weight:bold;min-width:18px;text-align:center;animation:pulse 1s infinite}
            @keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.1)}100%{transform:scale(1)}}
            .priority-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600}
            .priority-critical{background:#dc3545;color:white}
            .priority-high{background:#fd7e14;color:white}
            .priority-medium{background:#ffc107;color:#333}
            .priority-low{background:#28a745;color:white}
            .status-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600}
            .status-a_faire{background:#6c757d;color:white}
            .status-en_cours{background:#17a2b8;color:white}
            .status-termine{background:#28a745;color:white}
            .status-cloturee{background:#343a40;color:white}
            .action-buttons .btn{padding:4px 8px;margin:0 2px;border-radius:6px}
            .role-badge{display:inline-block;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600}
            .role-admin{background:#dc3545;color:white}
            .role-supervisor{background:#fd7e14;color:white}
            .role-technician{background:#17a2b8;color:white}
            .role-viewer{background:#6c757d;color:white}
            .status-active{color:#28a745;font-weight:bold}
            .status-inactive{color:#dc3545;font-weight:bold}
            .form-control, .form-select{border-radius:8px;border:1px solid #ddd;padding:10px 12px}
            .form-control:focus, .form-select:focus{border-color:#667eea;box-shadow:0 0 0 0.2rem rgba(102,126,234,0.25)}
            .alert{border-radius:10px;padding:12px 20px}
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-2 p-0 sidebar text-white">
                    <div class="p-3">
                        <h4 class="text-center mb-4"><i class="fas fa-industry"></i> GMAO Pro</h4>
                        <hr class="bg-light">
                        <nav class="nav flex-column">
                            <a class="nav-link <?php echo $page == 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard"><i class="fas fa-tachometer-alt"></i> <?php echo t('dashboard'); ?></a>
                            <a class="nav-link <?php echo $page == 'calendar' ? 'active' : ''; ?>" href="?page=calendar"><i class="fas fa-calendar-alt"></i> <?php echo t('planning'); ?></a>
                            <a class="nav-link <?php echo $page == 'equipment' ? 'active' : ''; ?>" href="?page=equipment"><i class="fas fa-microchip"></i> <?php echo t('equipment'); ?></a>
                            <a class="nav-link <?php echo $page == 'interventions' ? 'active' : ''; ?>" href="?page=interventions"><i class="fas fa-tools"></i> <?php echo t('interventions'); ?></a>
                            <a class="nav-link <?php echo $page == 'preventive' ? 'active' : ''; ?>" href="?page=preventive"><i class="fas fa-calendar-alt"></i> <?php echo t('preventive_maintenance'); ?></a>
                            <a class="nav-link <?php echo $page == 'stock' ? 'active' : ''; ?>" href="?page=stock"><i class="fas fa-boxes"></i> <?php echo t('stock'); ?></a>
                            <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'admin')): ?>
                            <a class="nav-link <?php echo $page == 'technicians' ? 'active' : ''; ?>" href="?page=technicians"><i class="fas fa-users"></i> <?php echo t('technicians'); ?></a>
                            <a class="nav-link <?php echo $page == 'planning' ? 'active' : ''; ?>" href="?page=planning"><i class="fas fa-calendar-week"></i> <?php echo t('planning'); ?></a>
                            <?php endif; ?>
                            <a class="nav-link position-relative notification-badge <?php echo $page == 'alerts' ? 'active' : ''; ?>" href="?page=alerts"><i class="fas fa-bell"></i> <?php echo t('alerts'); ?><span class="badge-count" style="display:none">0</span></a>
                            <?php if(isset($_SESSION['role']) && ($_SESSION['role'] == 'supervisor' || $_SESSION['role'] == 'admin')): ?>
                            <a class="nav-link <?php echo $page == 'performance' ? 'active' : ''; ?>" href="?page=performance"><i class="fas fa-chart-line"></i> <?php echo t('performance_analysis'); ?></a>
                            <?php endif; ?>
                            <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                            <a class="nav-link <?php echo $page == 'mail_settings' ? 'active' : ''; ?>" href="?page=mail_settings"><i class="fas fa-envelope"></i> <?php echo t('email_config'); ?></a>
                            <a class="nav-link <?php echo $page == 'users' ? 'active' : ''; ?>" href="?page=users"><i class="fas fa-users-cog"></i> <?php echo t('users'); ?></a>
                            <a class="nav-link <?php echo $page == 'export_center' ? 'active' : ''; ?>" href="?page=export_center"><i class="fas fa-download"></i> <?php echo t('export'); ?>/<?php echo t('import'); ?></a>
                            <?php endif; ?>
                            <a class="nav-link <?php echo $page == 'profile' ? 'active' : ''; ?>" href="?page=profile"><i class="fas fa-user-circle"></i> <?php echo t('profile'); ?></a>
                            <hr class="bg-light">
                            <a class="nav-link text-danger" href="?page=logout"><i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?></a>
                        </nav>
                    </div>
                </div>
                <div class="col-md-10 p-4">
                    <div class="navbar-top">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-0"><i class="fas fa-<?php echo $page == 'dashboard' ? 'tachometer-alt' : ($page == 'performance' ? 'chart-line' : ($page == 'equipment' ? 'microchip' : ($page == 'interventions' ? 'tools' : ($page == 'preventive' ? 'calendar-alt' : ($page == 'technicians' ? 'users' : ($page == 'planning' ? 'calendar-week' : ($page == 'alerts' ? 'bell' : 'boxes'))))))); ?>"></i> <?php echo ucfirst(str_replace('_', ' ', $page)); ?></h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge bg-info p-2"><i class="fas fa-user"></i> <?php echo $_SESSION['username'] ?? 'Technician'; ?></span>
                                <span class="badge bg-secondary p-2 ms-2"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="main-content">
                        <?php include ROOT_PATH . '/pages/' . $page . '.php'; ?>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="/gmao/assets/js/alerts.js"></script>
        <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/gmao/sw.js').then(function(registration) {
                    console.log('Service Worker registered:', registration.scope);
                }).catch(function(error) {
                    console.log('Service Worker error:', error);
                });
            });
        }
        let audioContext = null;
        let soundEnabled = localStorage.getItem('gmao_sound_enabled') === 'true';
        function initAudio() {
            if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioContext.state === 'suspended') {
                audioContext.resume();
            }
        }
        document.body.addEventListener('click', function() {
            if (soundEnabled && !audioContext) {
                initAudio();
            }
        }, { once: true });
        function updateSoundIcon() {
            var icon = document.getElementById('soundIcon');
            var toggle = document.getElementById('soundToggle');
            if(icon) {
                if(soundEnabled) {
                    icon.className = 'fas fa-volume-up';
                    if(toggle) toggle.classList.remove('sound-off');
                } else {
                    icon.className = 'fas fa-volume-mute';
                    if(toggle) toggle.classList.add('sound-off');
                }
            }
        }
        function toggleSound() {
            soundEnabled = !soundEnabled;
            localStorage.setItem('gmao_sound_enabled', soundEnabled);
            updateSoundIcon();
            if(soundEnabled) {
                initAudio();
            }
        }
        updateSoundIcon();
        </script>
    </body>
    </html>
    <?php
} 
// ========== PAGE LOGIN ==========
elseif ($page == 'login') {
    include ROOT_PATH . '/pages/login.php';
} 
// ========== PAGE LOGOUT ==========
elseif ($page == 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit();
}
// ========== DEFAUT ==========
else {
    header('Location: index.php?page=dashboard');
    exit();
}
?>