<?php
// cron/check_alerts.php - Script à exécuter périodiquement
// Ce script doit être exécuté via cron ou planifié

// Définir le chemin absolu vers le dossier racine
$rootPath = dirname(__DIR__);

// Charger les dépendances nécessaires
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/includes/lang.php';
require_once $rootPath . '/includes/functions.php';

// Activer l'affichage des erreurs pour le cron
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Démarrer une session pour stocker les alertes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "[" . date('Y-m-d H:i:s') . "] Début vérification alertes prestataires\n";

try {
    // Vérifier les alertes des prestataires
    $alerts = checkContractorAlerts($pdo);
    
    echo "[" . date('Y-m-d H:i:s') . "] " . count($alerts) . " alertes trouvées\n";
    
    // Afficher les alertes en détail
    foreach ($alerts as $alert) {
        echo "  - " . $alert['message'] . "\n";
    }
    
    // Vérifier les maintenances préventives en retard (script existant)
    $stmt = $pdo->query("
        SELECT pm.*, e.name as equipment_name 
        FROM preventive_maintenance pm 
        JOIN equipment e ON pm.equipment_id = e.id 
        WHERE pm.next_due <= CURDATE() 
        AND (pm.last_alert_sent IS NULL OR pm.last_alert_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))
    ");
    $overdue = $stmt->fetchAll();

    foreach($overdue as $maintenance) {
        echo "Envoi alerte pour maintenance : {$maintenance['equipment_name']}\n";
        sendPreventiveAlert($maintenance);
        
        $update = $pdo->prepare("UPDATE preventive_maintenance SET last_alert_sent = NOW() WHERE id = ?");
        $update->execute([$maintenance['id']]);
    }

    // 2. Vérifier le stock critique
    $stmt = $pdo->query("
        SELECT * FROM spare_parts 
        WHERE quantity <= min_quantity 
        AND (last_alert_sent IS NULL OR last_alert_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))
    ");
    $lowStock = $stmt->fetchAll();

    foreach($lowStock as $part) {
        echo "Envoi alerte stock pour : {$part['name']}\n";
        sendStockAlert($part);
        
        $update = $pdo->prepare("UPDATE spare_parts SET last_alert_sent = NOW() WHERE id = ?");
        $update->execute([$part['id']]);
    }

    // 3. Vérifier les nouvelles interventions critiques
    $stmt = $pdo->query("
        SELECT i.*, e.name as equipment_name 
        FROM interventions i 
        JOIN equipment e ON i.equipment_id = e.id 
        WHERE i.priority = 'critical' 
        AND i.status = 'pending'
        AND i.alert_sent IS NULL
    ");
    $critical = $stmt->fetchAll();

    foreach($critical as $intervention) {
        echo "Envoi alerte intervention critique : {$intervention['title']}\n";
        $equipment = ['name' => $intervention['equipment_name']];
        sendCriticalInterventionAlert($intervention, $equipment);
        
        $update = $pdo->prepare("UPDATE interventions SET alert_sent = NOW() WHERE id = ?");
        $update->execute([$intervention['id']]);
    }

    // 4. Vérifier si on est lundi pour envoyer rapport hebdomadaire
    if(date('N') == 1) {
        $stmt = $pdo->query("SELECT last_report_sent FROM report_log WHERE id = 1");
        $lastReport = $stmt->fetch();
        
        if(!$lastReport || $lastReport['last_report_sent'] < date('Y-m-d', strtotime('-6 days'))) {
            echo "Envoi rapport hebdomadaire\n";
            sendWeeklyReport();
            
            $pdo->exec("INSERT INTO report_log (id, last_report_sent) VALUES (1, NOW()) 
                        ON DUPLICATE KEY UPDATE last_report_sent = NOW()");
        }
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $e->getMessage() . "\n";
    error_log("check_alerts.php - " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Fin vérification alertes\n";
?>