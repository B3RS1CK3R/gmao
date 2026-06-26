<?php
/**
 * index.php - Point d'entrée principal GMAO
 * Version avec topbar et sidebar en fichiers séparés
 */

// ====================== DEBUG MODE ======================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =======================================================

// Start output buffering to avoid "headers already sent" when pages perform redirects
if (ob_get_level() == 0) {
    ob_start();
}

// Secure session cookie params before starting session
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    $secureFlag = true;
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => $secureFlag,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params($cookieParams['lifetime'], $cookieParams['path'] . '; samesite=Lax', $cookieParams['domain'], $secureFlag, true);
    }
    session_start();
}

date_default_timezone_set('Europe/Paris');

// ====================== GESTION LANGUE ======================
if (isset($_GET['setlang'])) {
    require_once 'includes/lang.php';
    setLanguage($_GET['setlang']);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

require_once 'includes/lang.php';
// ===========================================================

require_once 'config/database.php';
require_once 'includes/functions.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$public_pages = ['login', 'setlang'];
if (!in_array($page, $public_pages, true)) {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?page=login');
        exit();
    }
}

if ($page === 'login') {
    require_once 'pages/login.php';
    exit();
}
if ($page === 'logout') {
    session_destroy();
    header('Location: index.php?page=login');
    exit();
}

// ====================== TRAITEMENT DES ACTIONS ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if ($page === 'equipment_add') {
        require_once 'actions/equipment_add_action.php';
        exit();
    }
    if ($page === 'equipment_edit') {
        require_once 'actions/equipment_edit_action.php';
        exit();
    }
    if ($page === 'equipment_delete_action') {
        require_once 'actions/equipment_delete_action.php';
        exit();
    }
    
    if ($page === 'preventive_add_action') {
        require_once 'actions/preventive_add_action.php';
        exit();
    }
    if ($page === 'preventive_edit_action') {
        require_once 'actions/preventive_edit_action.php';
        exit();
    }
    if ($page === 'preventive_delete_action') {
        require_once 'actions/preventive_delete_action.php';
        exit();
    }
    
    if ($page === 'intervention_add_action') {
        require_once 'actions/intervention_add_action.php';
        exit();
    }
    if ($page === 'intervention_edit_action') {
        require_once 'actions/intervention_edit_action.php';
        exit();
    }
    if ($page === 'interventions_delete_action') {
        require_once 'actions/interventions_delete_action.php';
        exit();
    }
    
    if ($page === 'technician_add') {
        require_once 'actions/technician_add_action.php';
        exit();
    }
    if ($page === 'technician_edit') {
        require_once 'actions/technician_edit_action.php';
        exit();
    }
    
    if ($page === 'contractor_add_action') {
        require_once 'actions/contractor_add_action.php';
        exit();
    }
    if ($page === 'contractor_edit_action') {
        require_once 'actions/contractor_edit_action.php';
        exit();
    }
    if ($page === 'contractor_delete_action') {
        require_once 'actions/contractor_delete_action.php';
        exit();
    }
    if ($page === 'contractors_restore_action') {
        require_once 'actions/contractors_restore_action.php';
        exit();
    }
}

// ====================== TRAITEMENT DES ACTIONS GET ======================
if ($page === 'preventive_delete_action' && isset($_GET['id'])) {
    require_once 'actions/preventive_delete_action.php';
    exit();
}
if ($page === 'equipment_delete_action' && isset($_GET['id'])) {
    require_once 'actions/equipment_delete_action.php';
    exit();
}
if ($page === 'interventions_delete_action' && isset($_GET['id'])) {
    require_once 'actions/interventions_delete_action.php';
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GMAO - <?php echo t($page); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/toast.css">
    
    <style>
        /* ===== STYLES GLOBAUX ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }
        body { 
            background-color: #f8f9fa; 
            overflow: hidden;
            padding-left: 250px; /* Largeur de la sidebar */
            padding-top: 60px; /* Hauteur de la topbar */
        }
        
        /* ===== CONTENU PRINCIPAL ===== */
        .main-content {
            padding: 20px;
            height: calc(100vh - 60px);
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 992px) {
            body {
                padding-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 56px;
            }
            
            .main-content {
                padding: 15px;
                height: calc(100vh - 56px);
            }
        }
    </style>
</head>
<body>

<!-- ===== SIDEBAR ===== -->
<?php require_once 'includes/sidebar.php'; ?>

<!-- ===== TOPBAR ===== -->
<?php require_once 'includes/topbar.php'; ?>

<!-- ===== CONTENU PRINCIPAL ===== -->
<div class="main-content" id="mainContent">
    <?php
    // ===== ROUTAGE DES PAGES =====
    switch($page) {
        
        // ===== DASHBOARD =====
        case 'dashboard':
            require_once 'pages/dashboard.php';
            break;
        
        // ===== TECHNICIENS =====
        case 'technicians':
            require_once 'pages/technicians.php';
            break;
        case 'technician_add':
            require_once 'pages/technician_add.php';
            break;
        case 'technician_edit':
            require_once 'pages/technician_edit.php';
            break;
        case 'technician_delete':
            require_once 'pages/technician_delete.php';
            break;
        case 'technician_detail':
            require_once 'pages/technician_detail.php';
            break;
        case 'technicians_restore':
            require_once 'pages/technicians_restore.php';
            break;
        
        // ===== ÉQUIPES =====
        case 'team_add':
            require_once 'pages/team_add.php';
            break;
        case 'team_detail':
            require_once 'pages/team_detail.php';
            break;
        case 'team_delete':
            require_once 'pages/team_delete.php';
            break;
        
        // ===== ALERTES =====
        case 'alerts':
            require_once 'pages/alerts.php';
            break;
        
        // ===== UTILISATEURS =====
        case 'users':
            require_once 'pages/users.php';
            break;
        
        // ===== PROFIL =====
        case 'profile':
            require_once 'pages/profile.php';
            break;
        
        // ===== CRITICALITÉ =====
        case 'criticality':
            require_once 'pages/criticality_matrix.php';
            break;
        
        // ===== EXPORT =====
        case 'export':
            require_once 'pages/export_center.php';
            break;
        
        // ===== CONFIGURATION EMAIL =====
        case 'email_config':
            require_once 'pages/mail_settings.php';
            break;
        
        // ===== MIGRATIONS =====
        case 'migrations':
            require_once 'pages/admin_migrations.php';
            break;
        
        // ===== PERFORMANCES =====
        case 'performance':
            require_once 'pages/performance.php';
            break;
        
        // ===== MAINTENANCES PRÉVENTIVES =====
        case 'preventive':
            require_once 'pages/preventive.php';
            break;
        case 'preventive_assign':
            require_once 'pages/preventive_assign.php';
            break;
        case 'preventive_add':
            require_once 'pages/preventive_add.php';
            break;
        case 'preventive_edit':
            require_once 'pages/preventive_edit.php';
            break;
        case 'preventive_delete':
            require_once 'pages/preventive_delete.php';
            break;
        case 'preventive_complete':
            require_once 'pages/preventive_complete.php';
            break;
        case 'preventive_view':
            require_once 'pages/preventive_view.php';
            break;
        
        // ===== INTERVENTIONS =====
        case 'interventions':
            require_once 'pages/interventions.php';
            break;
        case 'interventions_assign':
            require_once 'pages/interventions_assign.php';
            break;
        case 'intervention_add':
            require_once 'pages/intervention_add.php';
            break;
        case 'intervention_edit':
            require_once 'pages/intervention_edit.php';
            break;
        case 'intervention_view':
            require_once 'pages/intervention_view.php';
            break;
        case 'intervention_detail':
            require_once 'pages/intervention_detail.php';
            break;
        case 'interventions_complete':
            require_once 'pages/interventions_complete.php';
            break;
        case 'interventions_delete':
            require_once 'pages/interventions_delete.php';
            break;
        
        // ===== ÉQUIPEMENTS =====
        case 'equipment':
            require_once 'pages/equipment.php';
            break;
        case 'equipment_add':
            require_once 'pages/equipment_add.php';
            break;
        case 'equipment_edit':
            require_once 'pages/equipment_edit.php';
            break;
        case 'equipment_delete':
            require_once 'pages/equipment_delete.php';
            break;
        case 'equipment_detail':
            require_once 'pages/equipment_detail.php';
            break;
        case 'equipment_qr':
            require_once 'pages/equipment_qr.php';
            break;
        case 'equipment_attachments':
            require_once 'pages/equipment_attachments.php';
            break;
        case 'equipment_restore':
            require_once 'pages/equipment_restore.php';
            break;
        
        // ===== STOCK =====
        case 'stock':
            require_once 'pages/stock.php';
            break;
        case 'stock_add':
            require_once 'pages/stock_add.php';
            break;
        case 'stock_edit':
            require_once 'pages/stock_edit.php';
            break;
        case 'stock_delete':
            require_once 'pages/stock_delete.php';
            break;
        case 'stock_detail':
            require_once 'pages/stock_detail.php';
            break;
        
        // ===== PRESTATAIRES =====
        case 'contractors':
            require_once 'pages/contractors.php';
            break;
        case 'contractor_add':
            require_once 'pages/contractor_add.php';
            break;
        case 'contractor_edit':
            require_once 'pages/contractor_edit.php';
            break;
        case 'contractor_detail':
            require_once 'pages/contractor_detail.php';
            break;
        case 'contractor_delete':
            require_once 'pages/contractor_delete.php';
            break;
        case 'contractors_restore':
            require_once 'pages/contractors_restore.php';
            break;

        // ===== PLANIFICATION =====
        case 'planning':
            require_once 'pages/planning.php';
            break;
        case 'calendar':
            require_once 'pages/calendar.php';
            break;
        
        // ===== PARAMÈTRES =====
        case 'settings':
            require_once 'pages/settings.php';
            break;
        
        // ===== PERMISSIONS =====
        case 'permissions':
            require_once 'pages/permissions.php';
            break;
        
        // ===== RAPPORTS =====
        case 'reports':
            require_once 'pages/reports.php';
            break;
        
        // ===== CAS PAR DÉFAUT =====
        default:
            if (file_exists("pages/{$page}.php")) {
                require_once "pages/{$page}.php";
            } else {
                echo "<div class='alert alert-warning mt-4'>Page <strong>" . htmlspecialchars($page) . "</strong> non trouvée ou en cours de développement.</div>";
            }
            break;
    }
    ?>
</div>

<!-- ===== SCRIPTS ===== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/alerts.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== EFFET SCROLL SUR TOPBAR =====
    const topbar = document.getElementById('mainTopbar');
    const mainContent = document.getElementById('mainContent');
    
    if (topbar && mainContent) {
        mainContent.addEventListener('scroll', function() {
            if (this.scrollTop > 10) {
                topbar.classList.add('scrolled');
            } else {
                topbar.classList.remove('scrolled');
            }
        });
    }
});
</script>

</body>
</html>