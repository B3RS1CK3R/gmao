<?php
// export/excel.php
require_once '../config/database.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    die('Accès non autorisé');
}

$type = $_GET['type'] ?? 'equipment';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gmao_export_' . $type . '_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

// Ajout BOM pour UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch($type) {
    case 'equipment':
        fputcsv($output, ['Code', 'Nom', 'Type', 'Localisation', 'Statut', 'Date création']);
        $stmt = $pdo->query("SELECT code, name, type, location, status, created_at FROM equipment");
        while($row = $stmt->fetch()) {
            fputcsv($output, $row);
        }
        break;
        
    case 'interventions':
        fputcsv($output, ['Équipement', 'Titre', 'Priorité', 'Statut', 'Date création', 'Durée']);
        $stmt = $pdo->query("
            SELECT e.name, i.title, i.priority, i.status, i.created_at, i.duration_hours 
            FROM interventions i 
            JOIN equipment e ON i.equipment_id = e.id
        ");
        while($row = $stmt->fetch()) {
            fputcsv($output, $row);
        }
        break;
        
    case 'stock':
        fputcsv($output, ['Référence', 'Nom', 'Quantité', 'Seuil min', 'Emplacement', 'Prix unitaire']);
        $stmt = $pdo->query("SELECT part_number, name, quantity, min_quantity, location, unit_price FROM spare_parts");
        while($row = $stmt->fetch()) {
            fputcsv($output, $row);
        }
        break;
}

fclose($output);
?>