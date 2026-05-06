<?php
// export/export_equipment_excel.php - Export Excel des équipements
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';

// Récupérer les paramètres de filtre
$filter_status = $_GET['status'] ?? 'all';

// Construire la requête SQL
$sql = "
    SELECT 
        e.code as 'Code',
        e.name as 'Name',
        e.type as 'Type',
        e.location as 'Location',
        e.supplier as 'Supplier',
        DATE_FORMAT(e.purchase_date, '%d/%m/%Y') as 'Purchase Date',
        DATE_FORMAT(e.warranty_end, '%d/%m/%Y') as 'Warranty End',
        e.status as 'Status',
        DATE_FORMAT(e.created_at, '%d/%m/%Y') as 'Creation Date'
    FROM equipment e
    WHERE 1=1
";

if($filter_status != 'all') {
    $sql .= " AND e.status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY e.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params ?? []);
$equipments = $stmt->fetchAll();

// Préparer les données pour l'export
$rows = [];
$rows[] = ['Code', 'Name', 'Type', 'Location', 'Supplier', 'Purchase Date', 'Warranty End', 'Status', 'Creation Date'];

foreach($equipments as $eq) {
    $rows[] = [
        $eq['Code'],
        $eq['Name'],
        $eq['Type'] ?? '',
        $eq['Location'] ?? '',
        $eq['Supplier'] ?? '',
        $eq['Purchase Date'] ?? '',
        $eq['Warranty End'] ?? '',
        t($eq['Status']),
        $eq['Creation Date']
    ];
}

// Générer le fichier
$filename = 'equipment_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>