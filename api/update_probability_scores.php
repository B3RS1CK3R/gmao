<?php
// api/update_probability_scores.php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

// Only admin or supervisor can run this
if(!in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    die('Access denied');
}

global $pdo;

// Get all equipment
$stmt = $pdo->query("SELECT id FROM equipment");
$equipmentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach($equipmentIds as $eqId) {
    // Count corrective interventions in the last 12 months
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    ");
    $stmt->execute([$eqId]);
    $count = $stmt->fetchColumn();
    
    // Convert count to score 1-5
    if ($count == 0) $score = 1;
    elseif ($count <= 2) $score = 2;
    elseif ($count <= 5) $score = 3;
    elseif ($count <= 10) $score = 4;
    else $score = 5;
    
    // Update equipment
    $update = $pdo->prepare("UPDATE equipment SET probability_score = ? WHERE id = ?");
    $update->execute([$score, $eqId]);
}

echo "Probability scores updated based on last 12 months of corrective interventions.";