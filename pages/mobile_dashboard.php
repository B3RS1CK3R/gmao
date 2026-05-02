<?php
// pages/mobile_dashboard.php - Interface mobile simplifiée
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';

$stats = getDashboardStats();
$alerts = getAlerts();
$technician_name = $_SESSION['username'] ?? 'Technicien';

// Récupérer les interventions du technicien connecté
$technician_id = null;
if($_SESSION['role'] == 'technician') {
    $stmt = $pdo->prepare("SELECT id FROM technicians WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $tech = $stmt->fetch();
    if($tech) {
        $technician_id = $tech['id'];
        $stmt2 = $pdo->prepare("
            SELECT i.*, e.name as equipment_name 
            FROM interventions i 
            JOIN equipment e ON i.equipment_id = e.id 
            WHERE i.intervenant_id = ? 
            AND i.task_status IN ('a_faire', 'en_cours')
            ORDER BY i.intervention_date ASC
            LIMIT 10
        ");
        $stmt2->execute([$technician_id]);
        $my_interventions = $stmt2->fetchAll();
    } else {
        $my_interventions = [];
    }
} else {
    $stmt = $pdo->query("
        SELECT i.*, e.name as equipment_name 
        FROM interventions i 
        JOIN equipment e ON i.equipment_id = e.id 
        WHERE i.task_status IN ('a_faire', 'en_cours')
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    $my_interventions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/gmao/manifest.json">
    <title>GMAO Mobile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f5f7fb;
            padding-bottom: 70px;
        }
        
        .app-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 0 0 25px 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .welcome-text {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 20px;
        }
        
        .stat-card-mobile {
            background: white;
            border-radius: 20px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        
        .stat-card-mobile:active {
            transform: scale(0.98);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .section-title {
            padding: 0 20px;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .intervention-list {
            padding: 0 15px;
        }
        
        .intervention-item {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
            transition: all 0.2s;
        }
        
        .intervention-item:active {
            transform: scale(0.99);
        }
        
        .intervention-critical {
            border-left-color: #dc3545;
            background: #fff5f5;
        }
        
        .intervention-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .intervention-meta {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            border-radius: 20px 20px 0 0;
        }
        
        .nav-item {
            text-align: center;
            padding: 8px 0;
            color: #888;
            text-decoration: none;
            flex: 1;
            transition: color 0.2s;
        }
        
        .nav-item.active {
            color: #667eea;
        }
        
        .nav-item i {
            font-size: 22px;
            display: block;
        }
        
        .nav-label {
            font-size: 10px;
            margin-top: 4px;
        }
        
        .alert-banner {
            background: #ffc107;
            color: #856404;
            padding: 12px 20px;
            margin: 15px 20px;
            border-radius: 12px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .fab-add {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background: #28a745;
            width: 56px;
            height: 56px;
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-decoration: none;
            font-size: 24px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="app-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="welcome-text"><?php echo t('welcome'); ?>,</div>
                <div class="user-name"><?php echo htmlspecialchars($technician_name); ?></div>
            </div>
            <div>
                <span id="connectionStatus" class="badge bg-success">
                    <i class="fas fa-wifi"></i> <?php echo t('online'); ?>
                </span>
            </div>
        </div>
        <div class="mt-3">
            <div class="small">📅 <?php echo date('l d F Y', time()); ?></div>
        </div>
    </div>
    
    <!-- Alertes -->
    <?php if(count($alerts) > 0): ?>
        <div class="alert-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?php echo count($alerts); ?> <?php echo t('alerts_active'); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card-mobile" onclick="window.location.href='?page=mobile_equipment'">
            <div class="stat-number"><?php echo $stats['total_equipment']; ?></div>
            <div class="stat-label"><i class="fas fa-microchip"></i> <?php echo t('equipment'); ?></div>
        </div>
        <div class="stat-card-mobile" onclick="window.location.href='?page=mobile_interventions'">
            <div class="stat-number"><?php echo $stats['active_interventions']; ?></div>
            <div class="stat-label"><i class="fas fa-tools"></i> <?php echo t('in_progress'); ?></div>
        </div>
        <div class="stat-card-mobile">
            <div class="stat-number"><?php echo $stats['completed_interventions'] ?? 0; ?></div>
            <div class="stat-label"><i class="fas fa-check-circle"></i> <?php echo t('completed'); ?></div>
        </div>
        <div class="stat-card-mobile">
            <div class="stat-number"><?php echo $stats['avg_intervention_duration']; ?>h</div>
            <div class="stat-label"><i class="fas fa-clock"></i> <?php echo t('avg_duration'); ?></div>
        </div>
    </div>
    
    <!-- Mes interventions -->
    <div class="section-title">
        <span><i class="fas fa-clock"></i> <?php echo t('my_interventions'); ?></span>
        <a href="?page=mobile_interventions" style="font-size: 12px;"><?php echo t('view_all'); ?> <i class="fas fa-chevron-right"></i></a>
    </div>
    
    <div class="intervention-list" id="interventionList">
        <?php if(empty($my_interventions)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p><?php echo t('no_interventions_assigned'); ?></p>
            </div>
        <?php else: ?>
            <?php foreach($my_interventions as $interv): ?>
                <div class="intervention-item <?php echo $interv['priority'] == 'critical' ? 'intervention-critical' : ''; ?>" 
                     onclick="window.location.href='?page=mobile_intervention_detail&id=<?php echo $interv['id']; ?>'">
                    <div class="intervention-title"><?php echo htmlspecialchars($interv['title']); ?></div>
                    <div class="small text-muted">
                        <i class="fas fa-microchip"></i> <?php echo htmlspecialchars($interv['equipment_name']); ?>
                    </div>
                    <div class="intervention-meta">
                        <span class="badge bg-<?php 
                            echo $interv['priority'] == 'critical' ? 'danger' : 
                                ($interv['priority'] == 'high' ? 'warning' : 'secondary'); ?>">
                            <?php echo t($interv['priority']); ?>
                        </span>
                        <span><?php echo $interv['intervention_date'] ? date('d/m/Y', strtotime($interv['intervention_date'])) : t('not_planned'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Action rapide QR Code -->
    <div style="padding: 20px;">
        <div class="card text-center border-0 shadow-sm" style="border-radius: 20px;">
            <div class="card-body">
                <i class="fas fa-qrcode fa-2x text-primary mb-2"></i>
                <h6><?php echo t('scan_qr_code'); ?></h6>
                <p class="small text-muted"><?php echo t('scan_qr_desc'); ?></p>
                <button class="btn btn-primary btn-sm w-100" onclick="window.location.href='?page=mobile_scan'">
                    <i class="fas fa-camera"></i> <?php echo t('scan'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Barre de navigation inférieure -->
    <div class="bottom-nav">
        <a href="?page=mobile_dashboard" class="nav-item active">
            <i class="fas fa-home"></i>
            <span class="nav-label"><?php echo t('home'); ?></span>
        </a>
        <a href="?page=mobile_interventions" class="nav-item">
            <i class="fas fa-tools"></i>
            <span class="nav-label"><?php echo t('interventions'); ?></span>
        </a>
        <a href="?page=mobile_equipment" class="nav-item">
            <i class="fas fa-microchip"></i>
            <span class="nav-label"><?php echo t('equipment'); ?></span>
        </a>
        <a href="?page=mobile_scan" class="nav-item">
            <i class="fas fa-qrcode"></i>
            <span class="nav-label"><?php echo t('scan'); ?></span>
        </a>
        <a href="?page=mobile_profile" class="nav-item">
            <i class="fas fa-user"></i>
            <span class="nav-label"><?php echo t('profile'); ?></span>
        </a>
    </div>
    
    <a href="?page=mobile_intervention_add" class="fab-add">
        <i class="fas fa-plus"></i>
    </a>
    
    <script>
        function updateConnectionStatus() {
            const statusEl = document.getElementById('connectionStatus');
            if (navigator.onLine) {
                statusEl.innerHTML = '<i class="fas fa-wifi"></i> <?php echo t('online'); ?>';
                statusEl.classList.remove('bg-secondary');
                statusEl.classList.add('bg-success');
            } else {
                statusEl.innerHTML = '<i class="fas fa-plug"></i> <?php echo t('offline'); ?>';
                statusEl.classList.remove('bg-success');
                statusEl.classList.add('bg-secondary');
            }
        }
        
        window.addEventListener('online', updateConnectionStatus);
        window.addEventListener('offline', updateConnectionStatus);
        updateConnectionStatus();
        
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/gmao/sw.js')
                .then(reg => console.log('Service Worker enregistrÃ©', reg))
                .catch(err => console.log('Erreur Service Worker', err));
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>