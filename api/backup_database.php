<?php
// api/backup_database.php - Export SQL de la base de données
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Accès interdit');
}

require_once '../config/database.php';

// Mettre à jour la date de dernière sauvegarde
$stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('last_backup_date', NOW()) ON DUPLICATE KEY UPDATE setting_value = NOW()");
$stmt->execute();

// Récupérer le nom de la base
$dbname = DB_NAME;

// Exporter toutes les tables
$output = "-- GMAO Database Backup\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $output .= "-- Structure de la table `$table`\n";
    $create = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_ASSOC);
    $output .= "DROP TABLE IF EXISTS `$table`;\n";
    $output .= $create['Create Table'] . ";\n\n";
    
    $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rows)) {
        $output .= "-- Données de la table `$table`\n";
        foreach ($rows as $row) {
            $columns = array_keys($row);
            $escaped = array_map(function($val) use ($pdo) {
                if ($val === null) return 'NULL';
                return $pdo->quote($val);
            }, array_values($row));
            $output .= "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $escaped) . ");\n";
        }
        $output .= "\n";
    }
}
$output .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Forcer le téléchargement
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="gmao_backup_' . date('Ymd_His') . '.sql"');
header('Content-Length: ' . strlen($output));
echo $output;
exit;