<?php
// export/export_interventions_excel.php - Export Excel des interventions
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../vendor/SimpleXLSXGen.php';

// Récupérer les paramètres de filtre
$filter_type = $_GET['type'] ?? 'all';
$filter_status = $_GET['status'] ?? 'all';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Construire la requête SQL
$sql = "
    SELECT 
        i.task_number as 'Task Number',
        i.title as 'Title',
        e.name as 'Equipment',
        e.code as 'Equipment Code',
        i.type as 'Type',
        i.priority as 'Priority',
        i.task_status as 'Status',
        DATE_FORMAT(i.created_at, '%d/%m/%Y %H:%i') as 'Creation Date',
        DATE_FORMAT(i.intervention_date, '%d/%m/%Y') as 'Planned Date',
        t.firstname as 'Technician Firstname',
        t.lastname as 'Technician Lastname',
        i.duration_hours as 'Duration (h)',
        i.planned_duration as 'Planned Duration',
        i.zone as 'Zone',
        i.localisation as 'Location'
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
    'Task Number', 'Title', 'Equipment', 'Equipment Code', 'Type', 
    'Priority', 'Status', 'Creation Date', 'Planned Date', 
    'Technician Firstname', 'Technician Lastname', 'Duration (h)', 'Planned Duration', 
    'Zone', 'Location'
];

foreach($interventions as $inv) {
    $rows[] = [
        $inv['Task Number'] ?? 'N/A',
        $inv['Title'],
        $inv['Equipment'],
        $inv['Equipment Code'],
        t($inv['Type']),
        t($inv['Priority']),
        t($inv['Status']),
        $inv['Creation Date'],
        $inv['Planned Date'] ?? t('not_planned'),
        $inv['Technician Firstname'] ?? '',
        $inv['Technician Lastname'] ?? '',
        $inv['Duration (h)'] ?? '',
        $inv['Planned Duration'] ?? '',
        $inv['Zone'] ?? '',
        $inv['Location'] ?? ''
    ];
}

// Générer le fichier
$filename = 'interventions_' . date('Y-m-d_H-i-s') . '.xlsx';
SimpleXLSXGen::fromArray($rows)->setFilename($filename)->download();
?>