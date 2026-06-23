<?php
// api/count_contractor_alerts.php - Compter les alertes prestataires
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit();
}

// Compter les alertes non lues (dans les 7 derniers jours)
$stmt = $pdo->query("
    SELECT COUNT(*) as count 
    FROM contractor_alerts 
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$count = $stmt->fetchColumn();

echo json_encode(['count' => intval($count)]);