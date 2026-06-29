<?php
// api/count_alerts.php - Retourne le nombre d'alertes actives
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Récupérer les alertes système (maintenances, stock, garanties)
$system_alerts = getAlerts();
$system_count = count($system_alerts);

// Récupérer les alertes des prestataires depuis la session
$contractor_count = $_SESSION['contractor_alerts_count'] ?? 0;

// Si les alertes prestataires existent mais le compteur est à 0, compter manuellement
if (empty($contractor_count) && !empty($_SESSION['contractor_alerts'])) {
    $sidebar_alerts = array_filter($_SESSION['contractor_alerts'], function($alert) {
        return $alert['show_sidebar'] ?? true;
    });
    $contractor_count = count($sidebar_alerts);
}

// Total
$total_count = $system_count + $contractor_count;

echo json_encode(['count' => $total_count]);
?>