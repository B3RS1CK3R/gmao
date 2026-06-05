<?php
require_once 'config/database.php';

$users = [
    ['admin', 'admin123', 'admin'],
    ['superviseur', 'demo123', 'supervisor'],
    ['technicien', 'demo123', 'technician'],
    ['visiteur', 'demo123', 'viewer']
];

foreach ($users as $user) {
    $username = $user[0];
    $plain = $user[1];
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    echo "Mot de passe mis à jour pour $username ($plain)<br>";
}
echo "Terminé. Supprimez ce fichier.";