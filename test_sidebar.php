<?php
// test_sidebar.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h2>Test Sidebar</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Rôle: " . ($_SESSION['role'] ?? 'non défini') . "</p>";

// Test requête simple
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "<p style='color:green'>✅ Connexion BDD OK - " . $count . " utilisateurs</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Erreur BDD: " . $e->getMessage() . "</p>";
}
?>