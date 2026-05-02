<?php
// export/export_stock_excel.php - Export Excel du stock
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';

// Récupérer les paramètres de filtre
$filter_status = $_GET['status'] ?? 'all';

$sql = "
    SELECT 
        sp.part_number as 'Référence',
        sp.name as 'Nom',
        sp.quantity as 'Quantité',
        sp.min_quantity as 'Seuil minimum',
        sp.location as 'Emplacement',
        sp.supplier as 'Fournisseur',
        sp.unit_price as 'Prix unitaire (€)',
        DATE_FORMAT(sp.last_restock, '%d/%m/%Y') as 'Dernier réapprov.',
        CASE 
            WHEN sp.quantity <= sp.min_quantity THEN 'Stock critique'
            WHEN sp.quantity <= sp.min_quantity * 2 THEN 'À surveiller'
            ELSE 'OK'
        END as 'Statut'
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
$rows[] = ['Référence', 'Nom', 'Quantité', 'Seuil minimum', 'Emplacement', 'Fournisseur', 'Prix unitaire (€)', 'Dernier réapprov.', 'Statut'];

foreach($parts as $part) {
    $rows[] = [
        $part['Référence'],
        $part['Nom'],
        $part['Quantité'],
        $part['Seuil minimum'],
        $part['Emplacement'] ?? '',
        $part['Fournisseur'] ?? '',
        number_format($part['Prix unitaire (€)'], 2),
        $part['Dernier réapprov.'] ?? '',
        $part['Statut']
    ];
}

// Générer le fichier
$filename = 'stock_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>