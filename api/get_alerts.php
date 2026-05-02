<?php
// api/get_alerts.php - API pour récupérer les alertes en temps réel
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

session_start();
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$alerts = [];
$counts = [
    'critical' => 0,
    'warning' => 0,
    'info' => 0
];

// 1. Maintenances préventives en retard
$stmt = $pdo->query("
    SELECT pm.*, e.name as equipment_name 
    FROM preventive_maintenance pm 
    JOIN equipment e ON pm.equipment_id = e.id 
    WHERE pm.next_due < CURDATE() 
    AND e.status = 'active'
");
$overdue = $stmt->fetchAll();

foreach($overdue as $task) {
    $days = (strtotime(date('Y-m-d')) - strtotime($task['next_due'])) / 86400;
    
    $alerts[] = [
        'id' => 'pm_' . $task['id'],
        'type' => 'maintenance_overdue',
        'priority' => $days > 30 ? 'critical' : 'warning',
        'title' => 'Maintenance en retard',
        'message' => "{$task['equipment_name']} : en retard de " . abs($days) . " jours",
        'url' => '/gmao/index.php?page=preventive',
        'timestamp' => time()
    ];
    
    $counts[$days > 30 ? 'critical' : 'warning']++;
}

// 2. Stock critique
$stmt = $pdo->query("
    SELECT * FROM spare_parts 
    WHERE quantity <= min_quantity
");
$lowStock = $stmt->fetchAll();

foreach($lowStock as $part) {
    $ratio = $part['quantity'] / $part['min_quantity'];
    
    $alerts[] = [
        'id' => 'stock_' . $part['id'],
        'type' => 'stock_critical',
        'priority' => $ratio < 0.3 ? 'critical' : 'warning',
        'title' => 'Stock critique',
        'message' => "{$part['name']} : plus que {$part['quantity']} unités (min: {$part['min_quantity']})",
        'url' => '/gmao/index.php?page=stock',
        'timestamp' => time()
    ];
    
    $counts[$ratio < 0.3 ? 'critical' : 'warning']++;
}

// 3. Interventions critiques non assignées
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name 
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.priority = 'critical' 
    AND i.status = 'pending'
    AND i.technician_id IS NULL
");
$critical = $stmt->fetchAll();

foreach($critical as $intervention) {
    $hours = (time() - strtotime($intervention['created_at'])) / 3600;
    
    $alerts[] = [
        'id' => 'interv_' . $intervention['id'],
        'type' => 'critical_intervention',
        'priority' => 'critical',
        'title' => '🚨 Intervention critique',
        'message' => "{$intervention['title']} sur {$intervention['equipment_name']} - Il y a " . round($hours) . "h",
        'url' => '/gmao/index.php?page=interventions',
        'timestamp' => time()
    ];
    
    $counts['critical']++;
}

// 4. Garanties expirant bientôt
$stmt = $pdo->query("
    SELECT name, warranty_end 
    FROM equipment 
    WHERE warranty_end IS NOT NULL 
    AND warranty_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
");
$warranty = $stmt->fetchAll();

foreach($warranty as $eq) {
    $days = (strtotime($eq['warranty_end']) - time()) / 86400;
    
    if($days < 0) {
        $alerts[] = [
            'id' => 'warranty_' . $eq['name'],
            'type' => 'warranty_expired',
            'priority' => 'critical',
            'title' => 'Garantie expirée',
            'message' => "{$eq['name']} : garantie expirée depuis " . abs(round($days)) . " jours",
            'url' => '/gmao/index.php?page=equipment',
            'timestamp' => time()
        ];
        $counts['critical']++;
    } elseif($days <= 30) {
        $alerts[] = [
            'id' => 'warranty_' . $eq['name'],
            'type' => 'warranty_expiring',
            'priority' => 'warning',
            'title' => 'Garantie bientôt expirée',
            'message' => "{$eq['name']} : plus que " . round($days) . " jours de garantie",
            'url' => '/gmao/index.php?page=equipment',
            'timestamp' => time()
        ];
        $counts['warning']++;
    }
}

// 5. Limiter le nombre d'alertes
$alerts = array_slice($alerts, 0, 20);

echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'counts' => $counts,
    'last_check' => date('Y-m-d H:i:s')
]);
?>