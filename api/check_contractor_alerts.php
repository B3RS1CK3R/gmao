<?php
// api/check_contractor_alerts.php - Vérification des alertes prestataires
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

// Empêcher l'exécution multiple simultanée
$lockFile = __DIR__ . '/../tmp/contractor_alerts.lock';
if (!is_dir(__DIR__ . '/../tmp')) {
    mkdir(__DIR__ . '/../tmp', 0755, true);
}
if (file_exists($lockFile) && (time() - filemtime($lockFile) < 300)) {
    echo json_encode(['status' => 'skipped', 'message' => 'Already running']);
    exit();
}
file_put_contents($lockFile, time());

try {
    $today = date('Y-m-d');
    $alerts_sent = 0;
    $alerts_errors = 0;

    // Récupérer tous les prestataires avec alertes activées
    $contractors = $pdo->query("
        SELECT * FROM contractors 
        WHERE status = 'active' AND alert_enabled = 1
    ")->fetchAll();

    foreach ($contractors as $contractor) {
        $days_before_1 = intval($contractor['alert_days_before_1'] ?? 90);
        $days_before_2 = intval($contractor['alert_days_before_2'] ?? 21);
        
        // ----- Interventions -----
        if ($contractor['alert_sidebar_intervention'] || $contractor['alert_popup_intervention'] || $contractor['alert_email_intervention']) {
            $interventions = $pdo->prepare("
                SELECT i.*, e.name as equipment_name
                FROM interventions i
                JOIN equipment e ON i.equipment_id = e.id
                WHERE i.contractor_id = ? 
                AND i.task_status NOT IN ('completed', 'closed', 'cancelled')
                AND i.intervention_date IS NOT NULL
                AND i.intervention_date >= CURDATE()
            ");
            $interventions->execute([$contractor['id']]);
            
            foreach ($interventions->fetchAll() as $inv) {
                $days_remaining = (strtotime($inv['intervention_date']) - strtotime($today)) / 86400;
                $days_remaining = ceil($days_remaining);
                
                if ($days_remaining <= $days_before_1 && $days_remaining > 0) {
                    sendContractorAlert($pdo, $contractor, $inv, 'intervention', 'before_1', $days_remaining);
                }
                if ($days_remaining <= $days_before_2 && $days_remaining > 0) {
                    sendContractorAlert($pdo, $contractor, $inv, 'intervention', 'before_2', $days_remaining);
                }
            }
        }

        // ----- Maintenances préventives -----
        if ($contractor['alert_sidebar_maintenance'] || $contractor['alert_popup_maintenance'] || $contractor['alert_email_maintenance']) {
            $preventives = $pdo->prepare("
                SELECT pm.*, e.name as equipment_name
                FROM preventive_maintenance pm
                JOIN equipment e ON pm.equipment_id = e.id
                WHERE pm.contractor_id = ? 
                AND pm.task_status != 'completed'
                AND pm.next_due IS NOT NULL
                AND pm.next_due >= CURDATE()
            ");
            $preventives->execute([$contractor['id']]);
            
            foreach ($preventives->fetchAll() as $pm) {
                $days_remaining = (strtotime($pm['next_due']) - strtotime($today)) / 86400;
                $days_remaining = ceil($days_remaining);
                
                if ($days_remaining <= $days_before_1 && $days_remaining > 0) {
                    sendContractorAlert($pdo, $contractor, $pm, 'preventive', 'before_1', $days_remaining);
                }
                if ($days_remaining <= $days_before_2 && $days_remaining > 0) {
                    sendContractorAlert($pdo, $contractor, $pm, 'preventive', 'before_2', $days_remaining);
                }
            }
        }
    }

    // Nettoyer le lock
    unlink($lockFile);

    echo json_encode([
        'status' => 'success',
        'alerts_sent' => $alerts_sent,
        'alerts_errors' => $alerts_errors
    ]);

} catch (Exception $e) {
    @unlink($lockFile);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Envoyer une alerte prestataire
 */
function sendContractorAlert($pdo, $contractor, $item, $type, $alert_type, $days_remaining) {
    global $alerts_sent, $alerts_errors;
    
    $related_id = $item['id'];
    $task_number = $item['task_number'] ?? '#' . $item['id'];
    $equipment_name = $item['equipment_name'] ?? '-';
    $scheduled_date = $type === 'intervention' ? ($item['intervention_date'] ?? null) : ($item['next_due'] ?? null);
    
    // Déterminer les méthodes d'alerte activées
    if ($type === 'intervention') {
        $sidebar_enabled = $contractor['alert_sidebar_intervention'] ?? 1;
        $popup_enabled = $contractor['alert_popup_intervention'] ?? 1;
        $email_enabled = $contractor['alert_email_intervention'] ?? 1;
    } else {
        $sidebar_enabled = $contractor['alert_sidebar_maintenance'] ?? 1;
        $popup_enabled = $contractor['alert_popup_maintenance'] ?? 1;
        $email_enabled = $contractor['alert_email_maintenance'] ?? 1;
    }
    
    // Vérifier si l'alerte a déjà été envoyée
    $check = $pdo->prepare("
        SELECT id FROM contractor_alerts 
        WHERE contractor_id = ? AND related_type = ? AND related_id = ? AND alert_type = ?
    ");
    $check->execute([$contractor['id'], $type, $related_id, $alert_type]);
    
    if ($check->fetch()) {
        return; // Alerte déjà envoyée
    }
    
    try {
        // 1. Insérer dans la base de données
        $insert = $pdo->prepare("
            INSERT INTO contractor_alerts (
                contractor_id, related_type, related_id, alert_type, 
                task_number, equipment_name, scheduled_date, days_remaining
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $contractor['id'], $type, $related_id, $alert_type,
            $task_number, $equipment_name, $scheduled_date, $days_remaining
        ]);
        
        $alert_id = $pdo->lastInsertId();
        
        // 2. Log dans user_logs
        logUserAction(null, 'contractor_alert', "Alerte prestataire: {$contractor['company_name']} - $task_number ($days_remaining jours restants)");
        
        // 3. Envoyer un email si activé
        if ($email_enabled) {
            sendContractorAlertEmail($contractor, $item, $type, $days_remaining);
        }
        
        // 4. Sidebar et Popup sont gérés par la base de données
        //    - Sidebar : affichage dans le badge via count_contractor_alerts.php
        //    - Popup : affichage dans la page alerts.php et via JavaScript
        
        $alerts_sent++;
        
    } catch (Exception $e) {
        $alerts_errors++;
        error_log("Erreur alerte prestataire: " . $e->getMessage());
    }
}

/**
 * Envoyer l'email d'alerte
 */
function sendContractorAlertEmail($contractor, $item, $type, $days_remaining) {
    $task_number = $item['task_number'] ?? '#' . $item['id'];
    $equipment_name = $item['equipment_name'] ?? '-';
    $scheduled_date = $type === 'intervention' ? ($item['intervention_date'] ?? 'Non définie') : ($item['next_due'] ?? 'Non définie');
    
    $subject = "🔔 Alerte prestataire - {$contractor['company_name']} - $task_number";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
            .info { margin: 5px 0; }
            .bold { font-weight: bold; }
        </style>
    </head>
    <body>
        <h2>🔔 <span class='bold'>Contacter le prestataire</span></h2>
        <div class='alert-box'>
            <p class='info'><strong>Prestataire :</strong> {$contractor['company_name']}</p>
            <p class='info'><strong>N° Tâche :</strong> $task_number</p>
            <p class='info'><strong>Équipement :</strong> $equipment_name</p>
            <p class='info'><strong>Date prévue :</strong> $scheduled_date</p>
            <p class='info'><strong>Jours restants :</strong> $days_remaining jours</p>
            <p class='info'><strong>Type :</strong> " . ucfirst($type) . "</p>
        </div>
        <p>
            <a href='https://{$_SERVER['HTTP_HOST']}/gmao_GEMINI/index.php?page=contractor_detail&id={$contractor['id']}' 
                style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                Voir le prestataire
            </a>
        </p>
        <hr>
        <small>Cet email est généré automatiquement par le système GMAO.</small>
    </body>
    </html>
    ";
    
    // Envoyer aux destinataires d'alertes
    if (defined('ALERT_EMAILS') && !empty(ALERT_EMAILS)) {
        sendEmail(ALERT_EMAILS, $subject, $message, true);
    }
}
?>