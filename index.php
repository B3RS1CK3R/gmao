<?php
/**
 * index.php - Point d'entrée principal GMAO
 * Version finale - Routage corrigé
 */

// ====================== DEBUG MODE ======================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// =======================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
require_once 'includes/functions.php';   // Fonctions globales

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Page Login traitée en premier
if ($page === 'login') {
    require_once 'pages/login.php';
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
    
    <style>
        body { background-color: #f8f9fa; }
        .main-content { min-height: 100vh; padding: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        
        <!-- Sidebar -->
        <?php require_once 'includes/sidebar.php'; ?>

        <!-- Contenu principal -->
        <main class="col-md-9 col-lg-10 main-content">
            
            <!-- Topbar -->
            <?php require_once 'includes/topbar.php'; ?>

            <div class="container-fluid mt-3">
                <?php
                
                switch($page) {
                    
                    case 'dashboard':
                        require_once 'pages/dashboard.php';
                        break;
                        
                    case 'technicians':
                        require_once 'pages/technicians.php';
                        break;
                        
                    case 'technician_detail':
                        require_once 'pages/technician_detail.php';
                        break;
                        
                    case 'alerts':
                        require_once 'pages/alerts.php';
                        break;
                        
                    case 'users':
                        require_once 'pages/users.php';
                        break;
                        
                    case 'profile':
                        require_once 'pages/profile.php';
                        break;
                    
                    case 'criticality':
                        require_once 'pages/criticality_matrix.php';
                        break;
                    
                    case 'export':
                        require_once 'pages/export_center.php';
                        break;
                    
                    case 'email_config':
                        require_once 'pages/mail_settings.php';
                        break;
                    
                    case 'migrations':
                        require_once 'pages/admin_migrations.php';
                        break;
                    
                    case 'performance':
                        require_once 'pages/performance.php';
                        break;
                    
                    case 'preventive':
                        require_once 'pages/preventive.php';
                        break;
                        
                        if (file_exists("pages/{$page}.php")) {
                            require_once "pages/{$page}.php";
                        } else {
                            echo "<div class='alert alert-info'>La page <strong>" . ucfirst(str_replace('_', ' ', $page)) . "</strong> est en cours de développement.</div>";
                        }
                        break;
                        
                    // Autres pages existantes
                    case 'equipment':
                    case 'interventions':
                    case 'planning':
                    case 'stock':
                    case 'reports':
                    case 'permissions':
                        if (file_exists("pages/{$page}.php")) {
                            require_once "pages/{$page}.php";
                        } else {
                            echo "<div class='alert alert-info'>La page <strong>" . ucfirst($page) . "</strong> est en cours de développement.</div>";
                        }
                        break;
                        
                    case 'logout':
                        session_destroy();
                        header('Location: index.php?page=login');
                        exit();
                        break;
                        
                    default:
                        echo "<div class='alert alert-warning mt-4'>Page <strong>" . htmlspecialchars($page) . "</strong> non trouvée ou en cours de développement.</div>";
                        break;
                }
                
                ?>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>