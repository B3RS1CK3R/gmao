<?php
// api/get_equipment.php - API pour récupérer les infos d'un équipement
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? null;

if(!$id) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit();
}

try {
    if(is_numeric($id)) {
        $stmt = $pdo->prepare("SELECT id, code, name, type, location, status FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT id, code, name, type, location, status FROM equipment WHERE code = ?");
        $stmt->execute([$id]);
    }
    
    $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($equipment) {
        echo json_encode(['success' => true, 'equipment' => $equipment]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Equipment not found']);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>