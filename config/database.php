<?php
/**
 * config/database.php - Database Connection Configuration
 * Sets up the PDO connection for the entire application.
 */

// Database connection parameters
define('DB_HOST', 'localhost');
define('DB_NAME', 'gmao_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Initialize PDO connection with UTF-8 charset
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", 
                    DB_USER, 
                    DB_PASS);
    
    // Configure PDO to throw exceptions on errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array for easier data access
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Récupérer l'offset stocké dans system_settings
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'timezone_offset'");
    $offset = $stmt->fetchColumn();
    if (!$offset) {
        $offset = '+02:00'; // Défaut France été/hiver ? mais fixe +02:00
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('timezone_offset', ?) ON DUPLICATE KEY UPDATE setting_value = ?")
            ->execute([$offset, $offset]);
    }
    // Appliquer l'offset à MySQL
    $pdo->exec("SET time_zone = '$offset'");
    // Après SET, vérifier le fuseau effectif
    $test = $pdo->query("SELECT @@session.time_zone")->fetchColumn();
    error_log("MySQL time_zone after SET: " . $test);
    // Si $test vaut '+00:00' ou 'SYSTEM', alors SET a échoué.

    // Pour PHP, utiliser un fuseau nommé cohérent avec l'offset (ex: Europe/Paris pour +02:00)
    date_default_timezone_set('Europe/Paris');
}
    catch (PDOException $e) {
    // Handle connection errors gracefully
    die("Database connection failed: " . $e->getMessage());
}
/**
 * SESSION MANAGEMENT NOTE:
 * Sessions should be started at the entry point (index.php) or within specific functions.
 * Avoid starting sessions here to prevent "Headers already sent" errors.
 */
// session_start(); // DO NOT UNCOMMENT THIS LINE

// Optionnel : définir le fuseau horaire
$pdo->exec("SET time_zone = '+00:00'");
?>