<?php
// export/export_interventions_excel.php - Export Excel des interventions
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';

// Récupérer les paramètres de filtre
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Construire la requête SQL
$sql = "
    SELECT 
        i.task_number as 'N° Tâche',
        i.title as 'Titre',
        e.name as 'Équipement',
        e.code as 'Code équipement',
        i.type as 'Type',
        i.priority as 'Priorité',
        i.task_status as 'Statut',
        DATE_FORMAT(i.created_at, '%d/%m/%Y %H:%i') as 'Date création',
        DATE_FORMAT(i.intervention_date, '%d/%m/%Y') as 'Date prévue',
        t.firstname as 'Technicien prénom',
        t.lastname as 'Technicien nom',
        i.duration_hours as 'Durée (h)',
        i.planned_duration as 'Durée prévue',
        i.zone as 'Zone',
        i.localisation as 'Localisation'
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    WHERE 1=1
";

$params = [];

if($filter_type != 'all') {
    $sql .= " AND i.type = ?";
    $params[] = $filter_type;
}

if($filter_status != 'all') {
    $sql .= " AND i.task_status = ?";
    $params[] = $filter_status;
}

if($filter_type == 'date_range') {
    $sql .= " AND DATE(i.created_at) BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interventions = $stmt->fetchAll();

// Préparer les données pour l'export
$rows = [];
$rows[] = [
    'N° Tâche', 'Titre', 'Équipement', 'Code équipement', 'Type', 
    'Priorité', 'Statut', 'Date création', 'Date prévue', 
    'Technicien prénom', 'Technicien nom', 'Durée (h)', 'Durée prévue', 
    'Zone', 'Localisation'
];

foreach($interventions as $inv) {
    $rows[] = [
        $inv['N° Tâche'] ?? 'N/A',
        $inv['Titre'],
        $inv['Équipement'],
        $inv['Code équipement'],
        $inv['Type'],
        $inv['Priorité'],
        $inv['Statut'],
        $inv['Date création'],
        $inv['Date prévue'] ?? 'Non planifiée',
        $inv['Technicien prénom'] ?? '',
        $inv['Technicien nom'] ?? '',
        $inv['Durée (h)'] ?? '',
        $inv['Durée prévue'] ?? '',
        $inv['Zone'] ?? '',
        $inv['Localisation'] ?? ''
    ];
}

// Générer le fichier
$filename = 'interventions_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>