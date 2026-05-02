<?php
// test_session.php
session_start();
echo "<h2>État de la session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Test utilisateur</h2>";
require_once 'config/database.php';
$stmt = $pdo->query("SELECT id, username, role FROM users");
$users = $stmt->fetchAll();
foreach($users as $user) {
    echo "ID: {$user['id']} - {$user['username']} - Rôle: {$user['role']}<br>";
}
?>