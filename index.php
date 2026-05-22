<?php
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

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// ====================== PAGE LOGIN SPÉCIALE ======================
// On traite la page login AVANT d'envoyer tout HTML
if ($page === 'login') {
    if (file_exists('pages/login.php')) {
        require_once 'pages/login.php';
    } else {
        echo '<h3>Fichier login.php introuvable</h3>';
    }
    exit(); // Important : on arrête ici pour éviter d'afficher le layout
}

// ====================== LAYOUT NORMAL (pour les autres pages) ======================
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

            <div class="container-fluid">
                <?php
                switch($page) {
                    case 'dashboard':
                        require_once 'pages/dashboard.php';
                        break;
                        
                    case 'technician_detail':
                        require_once 'pages/technician_detail.php';
                        break;
                        
                    default:
                        echo '<div class="alert alert-info">Page <strong>' . htmlspecialchars($page) . '</strong></div>';
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