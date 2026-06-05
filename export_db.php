<?php
/**
 * export_db.php - Script de sauvegarde automatique de la base de données gmao_db
 * Placez ce fichier dans le dossier racine de votre projet (ex: C:\xampp\htdocs\gmao_GEMINI\)
 * Exécutez-le via navigateur ou ligne de commande (php export_db.php)
 * Pour une automatisation, utilisez le planificateur de tâches (Windows) ou cron (Linux)
 */

// Configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'gmao_db';

$backupDir = __DIR__ . '/backups/';  // Dossier où stocker les sauvegardes
$maxFiles = 10; // Garder les 10 dernières sauvegardes uniquement

// Créer le dossier s'il n'existe pas
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Nom du fichier de sauvegarde (date et heure)
$filename = $backupDir . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';

// Commande mysqldump (adapter le chemin si nécessaire)
$mysqldump = 'C:\xampp\mysql\bin\mysqldump.exe';
$command = "\"$mysqldump\" --host=$host --user=$user --password=$pass --opt $dbname > \"$filename\" 2>&1";

// Exécuter la commande
exec($command, $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Sauvegarde réussie : " . basename($filename) . "\n";
    
    // Nettoyer les anciennes sauvegardes
    $files = glob($backupDir . $dbname . '_*.sql');
    if (count($files) > $maxFiles) {
        usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
        $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
        foreach ($filesToDelete as $f) {
            unlink($f);
            echo "🗑️ Ancienne sauvegarde supprimée : " . basename($f) . "\n";
        }
    }
} else {
    echo "❌ Erreur lors de l'export :\n";
    echo implode("\n", $output);
}
?>