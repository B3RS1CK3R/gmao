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

$alerts = getAlerts(); // Utilise la fonction existante
$count = count($alerts);

echo json_encode(['count' => $count]);