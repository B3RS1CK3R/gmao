<?php
// install_complet.php - Installation complète et automatique
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation GMAO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; padding: 10px; margin: 5px 0; background: #d4edda; border-left: 4px solid green; }
        .error { color: red; padding: 10px; margin: 5px 0; background: #f8d7da; border-left: 4px solid red; }
        .info { color: blue; padding: 10px; margin: 5px 0; background: #d1ecf1; border-left: 4px solid blue; }
        h1 { color: #333; }
        button { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 20px; }
        button:hover { background: #218838; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🚀 Installation de la GMAO Industrielle</h1>
    <hr>";

try {
    // Connexion à MySQL sans base de données
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='info'>✓ Connexion à MySQL réussie</div>";
    
    // Supprimer l'ancienne base si elle existe
    $pdo->exec("DROP DATABASE IF EXISTS gmao_db");
    echo "<div class='info'>✓ Ancienne base supprimée (si existante)</div>";
    
    // Créer la nouvelle base
    $pdo->exec("CREATE DATABASE gmao_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✓ Base de données 'gmao_db' créée</div>";
    
    // Utiliser la base
    $pdo->exec("USE gmao_db");
    
    // Création des tables
    $sql = "
    -- Table des utilisateurs
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100),
        role ENUM('admin', 'supervisor', 'technician', 'viewer') DEFAULT 'technician',
        email VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Table des équipements
    CREATE TABLE IF NOT EXISTS equipment (
        id INT PRIMARY KEY AUTO_INCREMENT,
        code VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(200) NOT NULL,
        type VARCHAR(100),
        location VARCHAR(200),
        supplier VARCHAR(100),
        purchase_date DATE,
        warranty_end DATE,
        technical_specs TEXT,
        qr_code VARCHAR(255),
        status ENUM('active', 'maintenance', 'broken', 'retired') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Table des maintenances préventives
    CREATE TABLE IF NOT EXISTS preventive_maintenance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT,
        frequency_days INT,
        last_done DATE,
        next_due DATE,
        instructions TEXT,
        assigned_team VARCHAR(100),
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        INDEX idx_next_due (next_due)
    );
    
    -- Table des interventions
    CREATE TABLE IF NOT EXISTS interventions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT,
        type ENUM('corrective', 'preventive', 'emergency') DEFAULT 'corrective',
        priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        title VARCHAR(200) NOT NULL,
        description TEXT,
        reported_by VARCHAR(100),
        assigned_to VARCHAR(100),
        start_date DATETIME,
        end_date DATETIME,
        duration_hours DECIMAL(5,2),
        cost DECIMAL(10,2),
        parts_used TEXT,
        resolution_notes TEXT,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        INDEX idx_status (status),
        INDEX idx_priority (priority)
    );
    
    -- Table des pièces détachées
    CREATE TABLE IF NOT EXISTS spare_parts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        part_number VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(200),
        quantity INT DEFAULT 0,
        min_quantity INT DEFAULT 5,
        location VARCHAR(100),
        supplier VARCHAR(100),
        unit_price DECIMAL(10,2),
        last_restock DATE,
        INDEX idx_quantity (quantity),
        INDEX idx_min_quantity (min_quantity)
    );
    
    -- Table des mouvements de stock
    CREATE TABLE IF NOT EXISTS stock_movements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        part_id INT,
        movement_type ENUM('in', 'out'),
        quantity INT,
        intervention_id INT,
        reason TEXT,
        movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (part_id) REFERENCES spare_parts(id) ON DELETE CASCADE,
        FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE SET NULL,
        INDEX idx_movement_date (movement_date)
    );
    ";
    
    // Exécuter chaque requête séparément
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($queries as $query) {
        if (!empty($query)) {
            $pdo->exec($query);
        }
    }
    
    echo "<div class='success'>✓ 6 tables créées avec succès</div>";
    
    // Créer l'utilisateur admin avec mot de passe hashé
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $hashed_password, 'Administrateur', 'admin', 'admin@gmao.com']);
    echo "<div class='success'>✓ Utilisateur admin créé (admin / admin123)</div>";
    
    // Ajouter des données de démonstration
    echo "<div class='info'>📊 Ajout de données de démonstration...</div>";
    
    // Ajouter quelques équipements
    $equipments = [
        ['MAC-001', 'Machine CNC 5 axes', 'Fraiseuse', 'Atelier A', 'Mazak', '2023-01-15', '2025-01-15', 'Puissance: 15kW, Vitesse: 12000 RPM'],
        ['PRE-002', 'Presse hydraulique 200T', 'Presse', 'Atelier B', 'Hydram', '2022-06-20', '2024-06-20', 'Force: 200 tonnes, Course: 500mm'],
        ['CON-003', 'Convoyeur bande 10m', 'Convoyeur', 'Ligne 1', 'FlexLink', '2023-03-10', '2026-03-10', 'Débit: 500 kg/h'],
        ['COM-004', 'Compresseur 75kW', 'Compresseur', 'Local technique', 'Atlas Copco', '2021-11-05', '2024-11-05', 'Pression: 8 bars, Débit: 10m³/min']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($equipments as $eq) {
        $stmt->execute($eq);
    }
    echo "<div class='success'>✓ 4 équipements ajoutés</div>";
    
    // Ajouter des maintenances préventives
    $preventive = [
        [1, 30, '2024-01-01', '2024-01-31', 'Nettoyage, lubrification, calibration', 'Team A'],
        [2, 90, '2024-01-15', '2024-04-15', 'Vérification hydraulique, changement joints', 'Team B'],
        [3, 45, '2024-01-10', '2024-02-24', 'Contrôle courroies, graissage roulements', 'Team A'],
        [4, 60, '2024-01-20', '2024-03-20', 'Changement filtres, vidange', 'Team C']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO preventive_maintenance (equipment_id, frequency_days, last_done, next_due, instructions, assigned_team) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($preventive as $pm) {
        $stmt->execute($pm);
    }
    echo "<div class='success'>✓ 4 maintenances préventives planifiées</div>";
    
    // Ajouter des pièces détachées
    $parts = [
        ['ROU-001', 'Roulement à billes 6204', 50, 10, 'Étagère A1', 'SKF', 12.50, '2024-01-15'],
        ['FIL-002', 'Filtre à huile', 25, 5, 'Étagère B3', 'Mann', 8.90, '2024-01-20'],
        ['COU-003', 'Courroie trapézoïdale', 15, 8, 'Étagère C2', 'Contitech', 15.30, '2024-01-10'],
        ['MOT-004', 'Moteur électrique 5kW', 3, 2, 'Zone stock', 'Siemens', 450.00, '2023-12-01']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO spare_parts (part_number, name, quantity, min_quantity, location, supplier, unit_price, last_restock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($parts as $part) {
        $stmt->execute($part);
    }
    echo "<div class='success'>✓ 4 pièces détachées ajoutées</div>";
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold; text-align: center;'>✅ INSTALLATION TERMINÉE AVEC SUCCÈS !</div>";
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<p><strong>Identifiants de connexion :</strong></p>";
    echo "<p style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
    echo "👤 Utilisateur : <strong>admin</strong><br>";
    echo "🔑 Mot de passe : <strong>admin123</strong>";
    echo "</p>";
    echo "<button onclick=\"window.location.href='index.php'\">🚀 Accéder à la GMAO</button>";
    echo "<br><br>";
    echo "<small style='color: #666;'>⚠️ Pour des raisons de sécurité, supprimez le fichier install_complet.php après installation.</small>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>💡 Solutions :<br>";
    echo "- Vérifiez que MySQL est démarré (XAMPP/WAMP)<br>";
    echo "- Vérifiez les identifiants dans config/database.php<br>";
    echo "- Redémarrez le service MySQL</div>";
}
?>
</div>
</body>
</html>