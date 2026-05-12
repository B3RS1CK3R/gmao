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
    
} catch(PDOException $e) {
    // If connection fails, stop execution and show error message
    die("Database Connection Error: " . $e->getMessage());
}

/**
 * SESSION MANAGEMENT NOTE:
 * Sessions should be started at the entry point (index.php) or within specific functions.
 * Avoid starting sessions here to prevent "Headers already sent" errors.
 */
// session_start(); // DO NOT UNCOMMENT THIS LINE
?>