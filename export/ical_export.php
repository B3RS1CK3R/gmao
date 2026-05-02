<?php
// export/ical_export.php - Export du calendrier au format iCal
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="gmao_calendar.ics"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//GMAO//NONSGML v1.0//FR\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo "X-WR-CALNAME:GMAO - Interventions\r\n";
echo "X-WR-CALDESC:Planning des interventions de maintenance\r\n";
echo "X-WR-TIMEZONE:Europe/Paris\r\n";

// Récupérer les interventions avec date
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name 
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.intervention_date IS NOT NULL
");
$interventions = $stmt->fetchAll();

foreach($interventions as $inv) {
    $date = new DateTime($inv['intervention_date']);
    $summary = $inv['task_number'] . ' - ' . $inv['title'] . ' (' . $inv['equipment_name'] . ')';
    $description = $inv['description'] . "\n\nÉquipement: " . $inv['equipment_name'];
    if($inv['zone']) $description .= "\nZone: " . $inv['zone'];
    if($inv['localisation']) $description .= "\nLocalisation: " . $inv['localisation'];
    
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . md5($inv['id'] . $inv['task_number']) . "@gmao\r\n";
    echo "DTSTAMP:" . date('Ymd\THis\Z') . "\r\n";
    echo "DTSTART:" . $date->format('Ymd') . "\r\n";
    echo "DTEND:" . $date->format('Ymd') . "\r\n";
    echo "SUMMARY:" . $summary . "\r\n";
    echo "DESCRIPTION:" . str_replace(["\n", "\r"], "\\n", $description) . "\r\n";
    echo "STATUS:" . ($inv['task_status'] == 'termine' ? 'COMPLETED' : 'CONFIRMED') . "\r\n";
    echo "PRIORITY:" . ($inv['priority'] == 'critical' ? 1 : ($inv['priority'] == 'high' ? 2 : ($inv['priority'] == 'medium' ? 3 : 5))) . "\r\n";
    echo "END:VEVENT\r\n";
}

echo "END:VCALENDAR\r\n";
?>