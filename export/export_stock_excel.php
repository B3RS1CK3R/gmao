<?php
// export/export_stock_excel.php - Export Excel du stock
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

$sql = "
    SELECT 
        sp.part_number as 'Reference',
        sp.name as 'Name',
        sp.quantity as 'Quantity',
        sp.min_quantity as 'Minimum threshold',
        sp.location as 'Location',
        sp.supplier as 'Supplier',
        sp.unit_price as 'Unit price (€)',
        DATE_FORMAT(sp.last_restock, '%d/%m/%Y') as 'Last restock',
        CASE 
            WHEN sp.quantity <= sp.min_quantity THEN 'critical_stock'
            WHEN sp.quantity <= sp.min_quantity * 2 THEN 'to_monitor'
            ELSE 'sufficient'
        END as 'Status'
    FROM spare_parts sp
    WHERE sp.quantity >= 0
";

if($filter_status == 'critical') {
    $sql .= " AND sp.quantity <= sp.min_quantity";
} elseif($filter_status == 'warning') {
    $sql .= " AND sp.quantity > sp.min_quantity AND sp.quantity <= sp.min_quantity * 2";
} elseif($filter_status == 'ok') {
    $sql .= " AND sp.quantity > sp.min_quantity * 2";
}

$sql .= " ORDER BY sp.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$parts = $stmt->fetchAll();

// Préparer les données pour l'export
$rows = [];
$rows[] = ['Reference', 'Name', 'Quantity', 'Minimum threshold', 'Location', 'Supplier', 'Unit price (€)', 'Last restock', 'Status'];

foreach($parts as $part) {
    $rows[] = [
        $part['Reference'],
        $part['Name'],
        $part['Quantity'],
        $part['Minimum threshold'],
        $part['Location'] ?? '',
        $part['Supplier'] ?? '',
        number_format($part['Unit price (€)'], 2),
        $part['Last restock'] ?? '',
        t($part['Status'])
    ];
}

// Générer le fichier
$filename = 'stock_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>