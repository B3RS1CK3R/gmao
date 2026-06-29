<?php
// api/get_alerts.php - API endpoint for real-time alerts

// Include core helpers so translation function `t()` and DB are available early
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// session is ensured inside includes/functions.php but make sure it's started
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => t('unauthenticated')]);
    exit();
}

// Forcer la mise à jour des alertes prestataires
checkContractorAlerts($pdo);

$alerts = [];
$counts = [
    'critical' => 0,
    'warning' => 0,
    'info' => 0
];

// 1. Overdue preventive maintenance
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
    $days_rounded = abs(round($days));
    
    $alerts[] = [
        'id' => 'pm_' . $task['id'],
        'type' => 'maintenance_overdue',
        'priority' => $days > 30 ? 'critical' : 'warning',
        'title' => t('maintenance_overdue'),
        'message' => $task['equipment_name'] . ': ' . t('overdue_by') . ' ' . $days_rounded . ' ' . t('count_days'),
        'url' => '/gmao_GEMINI/index.php?page=preventive',
        'timestamp' => time()
    ];
    
    $counts[$days > 30 ? 'critical' : 'warning']++;
}

// 2. Critical stock
$stmt = $pdo->query("
    SELECT * FROM spare_parts 
    WHERE quantity <= min_quantity
");
$lowStock = $stmt->fetchAll();

foreach($lowStock as $part) {
    $ratio = $part['quantity'] / ($part['min_quantity'] ?: 1);
    
    $alerts[] = [
        'id' => 'stock_' . $part['id'],
        'type' => 'stock_critical',
        'priority' => $ratio < 0.3 ? 'critical' : 'warning',
        'title' => t('stock_critical_title'),
        'message' => $part['name'] . ': ' . t('only') . ' ' . $part['quantity'] . ' ' . t('units') . ' (min: ' . $part['min_quantity'] . ')',
        'url' => '/gmao_GEMINI/index.php?page=stock',
        'timestamp' => time()
    ];
    
    $counts[$ratio < 0.3 ? 'critical' : 'warning']++;
}

// 3. Unassigned critical interventions
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name 
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.priority = 'critical' 
    AND i.task_status != 'termine'
    AND i.task_status != 'cloturee'
    AND i.technician_id IS NULL
");
$critical = $stmt->fetchAll();

foreach($critical as $intervention) {
    $hours = (time() - strtotime($intervention['created_at'])) / 3600;
    $hours_rounded = round($hours);
    
    $alerts[] = [
        'id' => 'interv_' . $intervention['id'],
        'type' => 'critical_intervention',
        'priority' => 'critical',
        'title' => t('critical_intervention'),
        'message' => $intervention['title'] . ' ' . t('on') . ' ' . $intervention['equipment_name'] . ' - ' . $hours_rounded . ' ' . t('hours'),
        'url' => '/gmao_GEMINI/index.php?page=interventions',
        'timestamp' => time()
    ];
    
    $counts['critical']++;
}

// 4. Warranties expiring soon
$stmt = $pdo->query("
    SELECT name, warranty_end, code 
    FROM equipment 
    WHERE warranty_end IS NOT NULL 
    AND warranty_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND status != 'retired'
");
$warranty = $stmt->fetchAll();

foreach($warranty as $eq) {
    $days = (strtotime($eq['warranty_end']) - time()) / 86400;
    $days_rounded = round($days);
    
    if($days < 0) {
        $alerts[] = [
            'id' => 'warranty_' . $eq['code'],
            'type' => 'warranty_expired',
            'priority' => 'critical',
            'title' => t('warranty_expired_title'),
            'message' => $eq['name'] . ': ' . t('warranty_expired') . ' ' . abs($days_rounded) . ' ' . t('days_ago'),
            'url' => '/gmao_GEMINI/index.php?page=equipment',
            'timestamp' => time()
        ];
        $counts['critical']++;
    } elseif($days <= 30) {
        $alerts[] = [
            'id' => 'warranty_' . $eq['code'],
            'type' => 'warranty_upcoming',
            'priority' => 'warning',
            'title' => t('warranty_upcoming_title'),
            'message' => $eq['name'] . ': ' . $days_rounded . ' ' . t('days_left'),
            'url' => '/gmao_GEMINI/index.php?page=equipment',
            'timestamp' => time()
        ];
        $counts['warning']++;
    }
}

// 5. Unassigned interventions for more than 3 days
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name 
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.technician_id IS NULL 
    AND i.task_status = 'a_faire'
    AND i.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
");
$unassigned = $stmt->fetchAll();

foreach($unassigned as $inv) {
    $days = (time() - strtotime($inv['created_at'])) / 86400;
    $days_rounded = round($days);
    
    $alerts[] = [
        'id' => 'unassigned_' . $inv['id'],
        'type' => 'unassigned_intervention',
        'priority' => 'warning',
        'title' => t('unassigned_intervention_title'),
        'message' => $inv['title'] . ' - ' . t('waiting_assignment') . ' ' . $days_rounded . ' ' . t('count_days'),
        'url' => '/gmao_GEMINI/index.php?page=interventions&action=assign&id=' . $inv['id'],
        'timestamp' => time()
    ];
    
    $counts['warning']++;
}

// ==========================================
// 6. CONTRACTOR ALERTS (NOUVEAU)
// ==========================================
if (!empty($_SESSION['contractor_alerts'])) {
    foreach ($_SESSION['contractor_alerts'] as $alert) {
        // Déterminer la priorité
        $priority = $alert['level'] == 2 ? 'critical' : 'warning';
        $type = ($alert['type'] == 'intervention') ? 'contractor_intervention' : 'contractor_maintenance';
        
        // Déterminer le titre
        $title = ($alert['type'] == 'intervention') 
            ? t('contractor_intervention_alert') 
            : t('contractor_maintenance_alert');
        
        // Déterminer l'URL
        $url = ($alert['type'] == 'intervention') 
            ? '/gmao_GEMINI/index.php?page=intervention_view&id=' . ($alert['intervention_id'] ?? 0)
            : '/gmao_GEMINI/index.php?page=preventive_view&id=' . ($alert['maintenance_id'] ?? 0);
        
        $alerts[] = [
            'id' => 'contractor_' . ($alert['intervention_id'] ?? $alert['maintenance_id'] ?? time()),
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'message' => $alert['contractor_name'] . ': ' . ($alert['intervention_title'] ?? $alert['maintenance_title']) . ' - ' . $alert['days_until'] . ' ' . t('days_left'),
            'url' => $url,
            'timestamp' => time(),
            'contractor' => $alert['contractor_name'],
            'days_until' => $alert['days_until']
        ];
        
        $counts[$priority]++;
    }
}
// ==========================================

// Limit number of alerts
$alerts = array_slice($alerts, 0, 20);

echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'counts' => $counts,
    'last_check' => date('Y-m-d H:i:s')
]);
?>