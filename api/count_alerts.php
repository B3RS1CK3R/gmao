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

// Utiliser la fonction unifiée
$total_count = countAllAlerts($pdo);

// Stocker en session pour un accès rapide
$_SESSION['total_alerts_count'] = $total_count;

echo json_encode(['count' => $total_count]);
?>