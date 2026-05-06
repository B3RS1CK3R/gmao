<?php
// api/update_intervention_date.php - Mise à jour date intervention
header('Content-Type: application/json');
session_start();

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['id']) || !isset($data['date'])) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE interventions SET intervention_date = ? WHERE id = ?");
    $result = $stmt->execute([$data['date'], $data['id']]);
    
    echo json_encode(['success' => $result]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>