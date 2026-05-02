<?php
// update_db_g.php - Mise à jour pour gestion utilisateurs avancée
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Gestion utilisateurs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: green; padding: 10px; background: #d4edda; border-left: 4px solid green; margin: 5px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-left: 4px solid red; margin: 5px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border-left: 4px solid blue; margin: 5px 0; }
        h1 { color: #333; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔐 Mise à jour - Gestion des utilisateurs avancée</h1>
    <hr>";

try {
    // 1. Ajouter colonnes manquantes à la table users
    $sql1 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login DATETIME NULL";
    $pdo->exec($sql1);
    echo "<div class='success'>✓ Colonne last_login ajoutée</div>";
    
    $sql2 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_ip VARCHAR(45) NULL";
    $pdo->exec($sql2);
    echo "<div class='success'>✓ Colonne last_ip ajoutée</div>";
    
    $sql3 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE";
    $pdo->exec($sql3);
    echo "<div class='success'>✓ Colonne is_active ajoutée</div>";
    
    $sql4 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL";
    $pdo->exec($sql4);
    echo "<div class='success'>✓ Colonne reset_token ajoutée</div>";
    
    $sql5 = "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL";
    $pdo->exec($sql5);
    echo "<div class='success'>✓ Colonne reset_expires ajoutée</div>";
    
    // 2. Table des logs d'actions
    $sql6 = "
    CREATE TABLE IF NOT EXISTS user_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )";
    $pdo->exec($sql6);
    echo "<div class='success'>✓ Table user_logs créée</div>";
    
    // 3. Table des sessions
    $sql7 = "
    CREATE TABLE IF NOT EXISTS user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        payload TEXT,
        last_activity INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_last_activity (last_activity)
    )";
    $pdo->exec($sql7);
    echo "<div class='success'>✓ Table user_sessions créée</div>";
    
    // 4. Mettre à jour les rôles existants
    $sql8 = "UPDATE users SET role = 'admin' WHERE username = 'admin'";
    $pdo->exec($sql8);
    echo "<div class='success'>✓ Rôles des utilisateurs mis à jour</div>";
    
    // 5. Ajouter des utilisateurs de démonstration
    $hashed_password = password_hash('demo123', PASSWORD_DEFAULT);
    
    // Vérifier si le superviseur existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['superviseur']);
    if($stmt->fetchColumn() == 0) {
        $stmt2 = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->execute(['superviseur', $hashed_password, 'Superviseur', 'supervisor', 'superviseur@gmao.com', 1]);
        echo "<div class='success'>✓ Utilisateur superviseur ajouté (superviseur / demo123)</div>";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['technicien']);
    if($stmt->fetchColumn() == 0) {
        $stmt2 = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->execute(['technicien', $hashed_password, 'Technicien', 'technician', 'technicien@gmao.com', 1]);
        echo "<div class='success'>✓ Utilisateur technicien ajouté (technicien / demo123)</div>";
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['visiteur']);
    if($stmt->fetchColumn() == 0) {
        $stmt2 = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->execute(['visiteur', $hashed_password, 'Visiteur', 'viewer', 'visiteur@gmao.com', 1]);
        echo "<div class='success'>✓ Utilisateur visiteur ajouté (visiteur / demo123)</div>";
    }
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold;'>✅ Mise à jour terminée avec succès !</div>";
    echo "<div style='margin-top: 20px;'>
            <a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Retour à la GMAO</a>
          </div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
</div>
</body>
</html>