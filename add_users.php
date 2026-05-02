<?php
// add_users.php - Ajouter les utilisateurs manquants
require_once 'config/database.php';

echo "<h2>Ajout des utilisateurs</h2>";

$hashed = password_hash('demo123', PASSWORD_DEFAULT);

// Vérifier si superviseur existe déjà
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute(['superviseur']);
if($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['superviseur', $hashed, 'Superviseur', 'supervisor', 'superviseur@gmao.com', 1]);
    echo "<p style='color:green'>✓ Utilisateur 'superviseur' ajouté (mot de passe: demo123)</p>";
} else {
    echo "<p>ℹ️ Utilisateur 'superviseur' existe déjà</p>";
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute(['technicien']);
if($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['technicien', $hashed, 'Technicien', 'technician', 'technicien@gmao.com', 1]);
    echo "<p style='color:green'>✓ Utilisateur 'technicien' ajouté (mot de passe: demo123)</p>";
} else {
    echo "<p>ℹ️ Utilisateur 'technicien' existe déjà</p>";
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
$stmt->execute(['visiteur']);
if($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['visiteur', $hashed, 'Visiteur', 'viewer', 'visiteur@gmao.com', 1]);
    echo "<p style='color:green'>✓ Utilisateur 'visiteur' ajouté (mot de passe: demo123)</p>";
} else {
    echo "<p>ℹ️ Utilisateur 'visiteur' existe déjà</p>";
}

echo "<hr>";
echo "<a href='index.php?page=users'>Retour à la gestion des utilisateurs</a>";
?>