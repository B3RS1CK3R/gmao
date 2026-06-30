<?php
// api/get_alerts.php - API endpoint for real-time alerts
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => t('unauthenticated')]);
    exit();
}

// Forcer la mise à jour des alertes
$alerts_data = getAllAlerts($pdo, true);

// Compter par priorité
$counts = [
    'critical' => 0,
    'warning' => 0,
    'info' => 0,
    'total' => 0
];

// Construire la liste pour le JS (format attendu par alerts.js)
$alerts = [];
$lang = getCurrentLanguage();

foreach ($alerts_data as $alert) {
    // Ne pas inclure les alertes qui ne sont pas affichées dans la sidebar
    if (!($alert['show_sidebar'] ?? true)) continue;
    
    // Déterminer le message selon la langue
    if ($lang === 'fr' && isset($alert['message_fr'])) {
        $message = $alert['message_fr'];
    } elseif (isset($alert['message_en'])) {
        $message = $alert['message_en'];
    } else {
        $message = $alert['message'] ?? 'Alerte';
    }
    
    // Déterminer le type pour le JS
    $type = $alert['type'] ?? 'system';
    $priority = $alert['priority'] ?? 'warning';
    
    // ===== DÉTERMINER LE TITRE EN FONCTION DU TYPE =====
    $title = $type; // Par défaut
    switch($type) {
        case 'backup_reminder':
            $title = $lang === 'fr' ? 'Rappel de sauvegarde' : 'Backup reminder';
            break;
        case 'contractor_intervention':
            $title = $lang === 'fr' ? 'Intervention prestataire' : 'Contractor intervention';
            break;
        case 'contractor_maintenance':
            $title = $lang === 'fr' ? 'Maintenance prestataire' : 'Contractor maintenance';
            break;
        case 'maintenance_overdue':
            $title = $lang === 'fr' ? 'Maintenance en retard' : 'Maintenance overdue';
            break;
        case 'stock_critical':
            $title = $lang === 'fr' ? 'Stock critique' : 'Critical stock';
            break;
        case 'warranty_expired':
            $title = $lang === 'fr' ? 'Garantie expirée' : 'Warranty expired';
            break;
        case 'warranty_upcoming':
            $title = $lang === 'fr' ? 'Garantie prochainement expirée' : 'Warranty expiring soon';
            break;
        case 'unassigned_intervention':
            $title = $lang === 'fr' ? 'Intervention non assignée' : 'Unassigned intervention';
            break;
        case 'critical_intervention':
            $title = $lang === 'fr' ? 'Intervention critique' : 'Critical intervention';
            break;
        default:
            $title = $alert['title'] ?? $type;
    }
    // =====================================================
    
    // Construire l'alerte au format attendu par alerts.js
    $alert_item = [
        'id' => $alert['id'] ?? 'alert_' . time() . '_' . uniqid(),
        'type' => $type,
        'priority' => $priority,
        'title' => $title,  // Titre traduit
        'message' => $message,
        'url' => $alert['url'] ?? '?page=alerts',
        'timestamp' => time()
    ];
    
    // Ajouter des informations supplémentaires si disponibles
    if (isset($alert['contractor_name'])) {
        $alert_item['contractor'] = $alert['contractor_name'];
    }
    if (isset($alert['days_until'])) {
        $alert_item['days_until'] = $alert['days_until'];
    }
    if (isset($alert['equipment_name'])) {
        $alert_item['equipment'] = $alert['equipment_name'];
    }
    
    $alerts[] = $alert_item;
    
    // Compter par priorité
    if (isset($counts[$priority])) {
        $counts[$priority]++;
    }
    $counts['total']++;
}

// Mettre à jour le compteur en session
$_SESSION['total_alerts_count'] = $counts['total'];

// Retourner les données au format attendu par alerts.js
echo json_encode([
    'success' => true,
    'alerts' => $alerts,
    'counts' => $counts,
    'last_check' => date('Y-m-d H:i:s')
]);
?>