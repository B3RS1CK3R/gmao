<?php
// export/export_equipment_excel.php - Export Excel des équipements
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';

// Récupérer les paramètres de filtre
$filter_status = $_GET['status'] ?? 'all';

// Construire la requête SQL
$sql = "
    SELECT 
        e.code as 'Code',
        e.name as 'Nom',
        e.type as 'Type',
        e.location as 'Localisation',
        e.supplier as 'Fournisseur',
        DATE_FORMAT(e.purchase_date, '%d/%m/%Y') as 'Date achat',
        DATE_FORMAT(e.warranty_end, '%d/%m/%Y') as 'Fin garantie',
        e.status as 'Statut',
        DATE_FORMAT(e.created_at, '%d/%m/%Y') as 'Date création'
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
$rows[] = ['Code', 'Nom', 'Type', 'Localisation', 'Fournisseur', 'Date achat', 'Fin garantie', 'Statut', 'Date création'];

foreach($equipments as $eq) {
    $rows[] = [
        $eq['Code'],
        $eq['Nom'],
        $eq['Type'] ?? '',
        $eq['Localisation'] ?? '',
        $eq['Fournisseur'] ?? '',
        $eq['Date achat'] ?? '',
        $eq['Fin garantie'] ?? '',
        $eq['Statut'],
        $eq['Date création']
    ];
}

// Générer le fichier
$filename = 'equipements_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>