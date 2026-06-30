<?php
require_once 'config/database.php';

// 1. Ajouter les colonnes manquantes à la table users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Colonne is_active ajoutée.<br>";
} catch (PDOException $e) {
    echo "Note : colonne is_active existe déjà ou erreur : " . $e->getMessage() . "<br>";
}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL");
    echo "Colonne last_login ajoutée.<br>";
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL");
    echo "Colonne last_ip ajoutée.<br>";
} catch (PDOException $e) {}

// 2. Créer les utilisateurs
$users = [
    ['admin', 'admin123', 'Administrateur', 'admin', 'admin@gmao.com'],
    ['superviseur', 'demo123', 'Superviseur', 'supervisor', 'superviseur@gmao.com'],
    ['technicien', 'demo123', 'Technicien', 'technician', 'technicien@gmao.com'],
    ['visiteur', 'demo123', 'Visiteur', 'viewer', 'visiteur@gmao.com'],
];

foreach ($users as $user) {
    $username = $user[0];
    $plain_password = $user[1];
    $fullname = $user[2];
    $role = $user[3];
    $email = $user[4];
    
    $hash = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Supprimer l'utilisateur s'il existe déjà
    $pdo->prepare("DELETE FROM users WHERE username = ?")->execute([$username]);
    
    // Insérer (la colonne is_active prendra la valeur par défaut 1)
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hash, $fullname, $role, $email]);
    
    echo "Utilisateur <strong>$username</strong> créé avec le mot de passe <strong>$plain_password</strong><br>";
}

echo "<hr>Terminé. Vous pouvez maintenant vous connecter.";