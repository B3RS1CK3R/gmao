<?php
// pages/mobile_interventions.php - Liste des interventions pour mobile
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';

$technician_id = null;
$technician_name = '';

if($_SESSION['role'] == 'technician') {
    $stmt = $pdo->prepare("SELECT id, firstname, lastname FROM technicians WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $tech = $stmt->fetch();
    if($tech) {
        $technician_id = $tech['id'];
        $technician_name = $tech['firstname'] . ' ' . $tech['lastname'];
    }
}

$filter = $_GET['filter'] ?? 'all';
$sql = "
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    WHERE 1=1
";
$params = [];

if($technician_id) {
    $sql .= " AND i.intervenant_id = ?";
    $params[] = $technician_id;
}

if($filter == 'pending') {
    $sql .= " AND i.task_status = 'a_faire'";
} elseif($filter == 'in_progress') {
    $sql .= " AND i.task_status = 'en_cours'";
} elseif($filter == 'completed') {
    $sql .= " AND i.task_status = 'termine'";
}

$sql .= " ORDER BY i.intervention_date ASC, i.created_at DESC LIMIT 50";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interventions = $stmt->fetchAll();

// Statistiques
$all_count = count($interventions);
$pending_count = count(array_filter($interventions, function($i) { return $i['task_status'] == 'a_faire'; }));
$in_progress_count = count(array_filter($interventions, function($i) { return $i['task_status'] == 'en_cours'; }));
$completed_count = count(array_filter($interventions, function($i) { return $i['task_status'] == 'termine'; }));
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <title><?php echo t('my_interventions'); ?> - GMAO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f5f7fb; padding-bottom: 70px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .app-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 0 0 25px 25px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .back-btn { color: white; text-decoration: none; font-size: 18px; display: inline-block; margin-bottom: 10px; }
        .back-btn i { margin-right: 5px; }
        .page-title { font-size: 24px; font-weight: bold; margin: 0; }
        .tech-name { font-size: 12px; opacity: 0.8; margin-top: 5px; }
        .stats-row { display: flex; gap: 10px; padding: 0 15px; margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 15px; padding: 12px; text-align: center; flex: 1; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .stat-number { font-size: 22px; font-weight: bold; }
        .stat-label { font-size: 10px; color: #666; margin-top: 4px; }
        .filter-tabs { display: flex; gap: 10px; padding: 0 15px; margin-bottom: 20px; overflow-x: auto; }
        .filter-tab { background: white; padding: 8px 16px; border-radius: 20px; font-size: 13px; white-space: nowrap; color: #666; text-decoration: none; transition: all 0.2s; }
        .filter-tab.active { background: #667eea; color: white; }
        .intervention-item { background: white; border-radius: 15px; padding: 15px; margin: 0 15px 12px 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: all 0.2s; cursor: pointer; border-left: 4px solid #667eea; }
        .intervention-item:active { transform: scale(0.99); }
        .intervention-critical { border-left-color: #dc3545; background: #fff5f5; }
        .intervention-high { border-left-color: #fd7e14; background: #fff8f0; }
        .intervention-title { font-weight: bold; font-size: 16px; margin-bottom: 5px; }
        .intervention-equipment { font-size: 12px; color: #666; margin-bottom: 8px; }
        .intervention-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
        .badge-priority { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-critical { background: #dc3545; color: white; }
        .badge-high { background: #fd7e14; color: white; }
        .badge-medium { background: #ffc107; color: #333; }
        .badge-low { background: #28a745; color: white; }
        .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .badge-pending { background: #6c757d; color: white; }
        .badge-progress { background: #17a2b8; color: white; }
        .badge-completed { background: #28a745; color: white; }
        .intervention-date { font-size: 11px; color: #999; margin-top: 8px; display: flex; align-items: center; gap: 5px; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; display: flex; justify-content: space-around; padding: 10px 0; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); border-radius: 20px 20px 0 0; }
        .nav-item { text-align: center; padding: 8px 0; color: #888; text-decoration: none; flex: 1; transition: color 0.2s; }
        .nav-item.active { color: #667eea; }
        .nav-item i { font-size: 22px; display: block; }
        .nav-label { font-size: 10px; margin-top: 4px; }
        .fab-add { position: fixed; bottom: 80px; right: 20px; background: #28a745; width: 56px; height: 56px; border-radius: 28px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-decoration: none; font-size: 24px; z-index: 1000; transition: transform 0.2s; }
        .fab-add:active { transform: scale(0.95); }
        .empty-state { text-align: center; padding: 50px 20px; color: #999; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="app-header">
        <a href="?page=mobile_dashboard" class="back-btn"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
        <h1 class="page-title"><?php echo t('my_interventions'); ?></h1>
        <?php if($technician_name): ?>
            <div class="tech-name"><i class="fas fa-user"></i> <?php echo htmlspecialchars($technician_name); ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-row">
        <div class="stat-card" onclick="window.location.href='?page=mobile_interventions&filter=all'">
            <div class="stat-number" style="color: #667eea;"><?php echo $all_count; ?></div>
            <div class="stat-label"><?php echo t('total'); ?></div>
        </div>
        <div class="stat-card" onclick="window.location.href='?page=mobile_interventions&filter=pending'">
            <div class="stat-number" style="color: #6c757d;"><?php echo $pending_count; ?></div>
            <div class="stat-label"><?php echo t('pending'); ?></div>
        </div>
        <div class="stat-card" onclick="window.location.href='?page=mobile_interventions&filter=in_progress'">
            <div class="stat-number" style="color: #17a2b8;"><?php echo $in_progress_count; ?></div>
            <div class="stat-label"><?php echo t('in_progress'); ?></div>
        </div>
        <div class="stat-card" onclick="window.location.href='?page=mobile_interventions&filter=completed'">
            <div class="stat-number" style="color: #28a745;"><?php echo $completed_count; ?></div>
            <div class="stat-label"><?php echo t('completed'); ?></div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="filter-tabs">
        <a href="?page=mobile_interventions&filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>"><?php echo t('all'); ?></a>
        <a href="?page=mobile_interventions&filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>"><?php echo t('pending'); ?></a>
        <a href="?page=mobile_interventions&filter=in_progress" class="filter-tab <?php echo $filter == 'in_progress' ? 'active' : ''; ?>"><?php echo t('in_progress'); ?></a>
        <a href="?page=mobile_interventions&filter=completed" class="filter-tab <?php echo $filter == 'completed' ? 'active' : ''; ?>"><?php echo t('completed'); ?></a>
    </div>
    
    <!-- Liste des interventions -->
    <?php if(empty($interventions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p><?php echo t('no_interventions_found'); ?></p>
            <a href="?page=mobile_intervention_add" class="btn btn-primary btn-sm mt-2"><?php echo t('create_intervention'); ?></a>
        </div>
    <?php else: ?>
        <?php foreach($interventions as $inv): 
            $priority_class = '';
            if($inv['priority'] == 'critical') $priority_class = 'intervention-critical';
            elseif($inv['priority'] == 'high') $priority_class = 'intervention-high';
            
            $badge_priority_class = '';
            if($inv['priority'] == 'critical') $badge_priority_class = 'badge-critical';
            elseif($inv['priority'] == 'high') $badge_priority_class = 'badge-high';
            elseif($inv['priority'] == 'medium') $badge_priority_class = 'badge-medium';
            else $badge_priority_class = 'badge-low';
            
            $badge_status_class = '';
            $status_text = '';
            if($inv['task_status'] == 'a_faire') {
                $badge_status_class = 'badge-pending';
                $status_text = t('pending');
            } elseif($inv['task_status'] == 'en_cours') {
                $badge_status_class = 'badge-progress';
                $status_text = t('in_progress');
            } elseif($inv['task_status'] == 'termine') {
                $badge_status_class = 'badge-completed';
                $status_text = t('completed');
            } else {
                $badge_status_class = 'badge-pending';
                $status_text = t('closed');
            }
        ?>
            <div class="intervention-item <?php echo $priority_class; ?>" 
                 onclick="window.location.href='?page=mobile_intervention_detail&id=<?php echo $inv['id']; ?>'">
                <div class="intervention-title"><?php echo htmlspecialchars($inv['title']); ?></div>
                <div class="intervention-equipment">
                    <i class="fas fa-microchip"></i> <?php echo htmlspecialchars($inv['equipment_name']); ?>
                    <?php if($inv['equipment_code']): ?>
                        <span class="text-muted">(<?php echo htmlspecialchars($inv['equipment_code']); ?>)</span>
                    <?php endif; ?>
                </div>
                <?php if($inv['equipment_location']): ?>
                    <div class="intervention-equipment text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($inv['equipment_location']); ?>
                    </div>
                <?php endif; ?>
                <div class="intervention-meta">
                    <span class="badge-priority <?php echo $badge_priority_class; ?>">
                        <?php echo t($inv['priority']); ?>
                    </span>
                    <span class="badge-status <?php echo $badge_status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                <?php if($inv['intervention_date']): ?>
                    <div class="intervention-date">
                        <i class="fas fa-calendar-alt"></i> <?php echo format_date_us($inv['intervention_date'], false); ?>
                        <?php if($inv['scheduled_time']): ?>
                            <i class="fas fa-clock ms-2"></i> <?php echo date('H:i', strtotime($inv['scheduled_time'])); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if($inv['task_number']): ?>
                    <div class="intervention-date">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($inv['task_number']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <a href="?page=mobile_intervention_add" class="fab-add">
        <i class="fas fa-plus"></i>
    </a>
    
    <div class="bottom-nav">
        <a href="?page=mobile_dashboard" class="nav-item">
            <i class="fas fa-home"></i>
            <span class="nav-label"><?php echo t('home'); ?></span>
        </a>
        <a href="?page=mobile_interventions" class="nav-item active">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/gmao_GEMINI/sw.js')
                .then(reg => console.log('Service Worker enregistrÃ©', reg))
                .catch(err => console.log('Erreur Service Worker', err));
        }
    </script>
</body>
</html>