<?php
// install.php - Installation complète de la GMAO (ordre corrigé)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation GMAO</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; padding: 10px; background: #d4edda; border-left: 4px solid green; margin: 5px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-left: 4px solid red; margin: 5px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border-left: 4px solid blue; margin: 5px 0; }
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
    // Connexion à MySQL
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✓ Connexion à MySQL réussie</div>";
    
    // Supprimer l'ancienne base si elle existe
    $pdo->exec("DROP DATABASE IF EXISTS gmao_db");
    echo "<div class='info'>✓ Ancienne base supprimée (si existante)</div>";
    
    // Créer la nouvelle base
    $pdo->exec("CREATE DATABASE gmao_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✓ Base de données 'gmao_db' créée</div>";
    
    // Utiliser la base
    $pdo->exec("USE gmao_db");
    
    // ========== CRÉATION DES TABLES DANS LE BON ORDRE ==========
    
    // 1. Table des utilisateurs
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100),
        role ENUM('admin', 'supervisor', 'technician', 'viewer') DEFAULT 'technician',
        email VARCHAR(100),
        last_login DATETIME NULL,
        last_ip VARCHAR(45) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        reset_token VARCHAR(255) NULL,
        reset_expires DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Table 'users' créée</div>";
    
    // 2. Table des techniciens (avant interventions)
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS technicians (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        employee_id VARCHAR(50) UNIQUE,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        specialty VARCHAR(200),
        certifications TEXT,
        hire_date DATE,
        status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_status (status)
    )");
    echo "<div class='success'>✓ Table 'technicians' créée</div>";
    
    // 3. Table des équipements
    $pdo->exec("
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
        total_operating_hours INT DEFAULT 0,
        last_failure_date DATETIME NULL,
        total_failures INT DEFAULT 0,
        total_repair_time INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Table 'equipment' créée</div>";
    
    // 4. Table des maintenances préventives
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS preventive_maintenance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT,
        frequency_days INT,
        last_done DATE,
        next_due DATE,
        instructions TEXT,
        assigned_team VARCHAR(100),
        last_alert_sent DATETIME NULL,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        INDEX idx_next_due (next_due)
    )");
    echo "<div class='success'>✓ Table 'preventive_maintenance' créée</div>";
    
    // 5. Table des interventions (après equipment et technicians)
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS interventions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        task_number VARCHAR(20) UNIQUE,
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
        intervenant_id INT NULL,
        task_status ENUM('pending', 'in_progress', 'completed', 'closed') DEFAULT 'pending',
        intervention_date DATE NULL,
        scheduled_time TIME NULL,
        task_type ENUM('revision', 'depannage', 'installation', 'maintenance_preventive', 'controle', 'autre') DEFAULT 'revision',
        zone VARCHAR(100),
        localisation VARCHAR(200),
        planned_duration ENUM('1h', '2h', '2h30', '3h', '4h', '6h', '8h', '1j', '2j', '3j') DEFAULT '4h',
        completed_date DATETIME NULL,
        completion_report TEXT,
        calendar_color VARCHAR(7) DEFAULT '#3788d8',
        all_day BOOLEAN DEFAULT FALSE,
        alert_sent DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        FOREIGN KEY (intervenant_id) REFERENCES technicians(id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_priority (priority),
        INDEX idx_task_number (task_number),
        INDEX idx_scheduled_date (intervention_date)
    )");
    echo "<div class='success'>✓ Table 'interventions' créée</div>";
    
    // 6. Table des pièces détachées
    $pdo->exec("
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
        last_alert_sent DATETIME NULL,
        documentation_path VARCHAR(500) NULL,
        INDEX idx_quantity (quantity),
        INDEX idx_min_quantity (min_quantity)
    )");
    echo "<div class='success'>✓ Table 'spare_parts' créée</div>";
    
    // 7. Table des mouvements de stock
    $pdo->exec("
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
    )");
    echo "<div class='success'>✓ Table 'stock_movements' créée</div>";
    
    // 8. Table des compétences
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS technician_skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        technician_id INT,
        equipment_type VARCHAR(100),
        skill_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
        certified BOOLEAN DEFAULT FALSE,
        certification_date DATE,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        UNIQUE KEY unique_skill (technician_id, equipment_type)
    )");
    echo "<div class='success'>✓ Table 'technician_skills' créée</div>";
    
    // 9. Table des disponibilités
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS technician_availability (
        id INT PRIMARY KEY AUTO_INCREMENT,
        technician_id INT,
        available_date DATE,
        start_time TIME,
        end_time TIME,
        is_available BOOLEAN DEFAULT TRUE,
        notes TEXT,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        UNIQUE KEY unique_availability (technician_id, available_date),
        INDEX idx_date (available_date)
    )");
    echo "<div class='success'>✓ Table 'technician_availability' créée</div>";
    
    // 10. Table du planning
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS work_schedule (
        id INT PRIMARY KEY AUTO_INCREMENT,
        technician_id INT,
        intervention_id INT,
        scheduled_start DATETIME,
        scheduled_end DATETIME,
        actual_start DATETIME,
        actual_end DATETIME,
        status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE,
        INDEX idx_scheduled_start (scheduled_start)
    )");
    echo "<div class='success'>✓ Table 'work_schedule' créée</div>";
    
    // 11. Table des performances
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS performance_metrics (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT,
        date_recorded DATE,
        mtbf DECIMAL(10,2),
        mttr DECIMAL(10,2),
        availability DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        INDEX idx_date (date_recorded)
    )");
    echo "<div class='success'>✓ Table 'performance_metrics' créée</div>";
    
    // 12. Table des logs utilisateurs
    $pdo->exec("
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
    )");
    echo "<div class='success'>✓ Table 'user_logs' créée</div>";
    
    // 13. Table des sessions
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS user_sessions (
        id VARCHAR(128) PRIMARY KEY,
        user_id INT,
        ip_address VARCHAR(45),
        user_agent VARCHAR(255),
        payload TEXT,
        last_activity INT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_last_activity (last_activity)
    )");
    echo "<div class='success'>✓ Table 'user_sessions' créée</div>";
    
    // 14. Table de séquence des tâches
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS task_sequence (
        id INT PRIMARY KEY AUTO_INCREMENT,
        last_number INT DEFAULT 260031,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<div class='success'>✓ Table 'task_sequence' créée</div>";
    
    // 15. Table des rappels calendrier
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS calendar_reminders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        intervention_id INT,
        reminder_minutes INT DEFAULT 60,
        reminder_sent BOOLEAN DEFAULT FALSE,
        reminder_type ENUM('email', 'push', 'both') DEFAULT 'email',
        FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE
    )");
    echo "<div class='success'>✓ Table 'calendar_reminders' créée</div>";
    
    // 16. Table des logs de rapports
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS report_log (
        id INT PRIMARY KEY,
        last_report_sent DATETIME NULL
    )");
    echo "<div class='success'>✓ Table 'report_log' créée</div>";
    
    // Insérer la séquence initiale
    $pdo->exec("INSERT INTO task_sequence (id, last_number) VALUES (1, 260031) ON DUPLICATE KEY UPDATE last_number = 260031");
    
    // Créer l'utilisateur admin
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', $hashed_password, 'Administrateur', 'admin', 'admin@gmao.com', 1]);
    echo "<div class='success'>✓ Utilisateur admin créé (admin / admin123)</div>";
    
    // Ajouter des équipements de démonstration
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
    echo "<small style='color: #666;'>⚠️ Pour des raisons de sécurité, supprimez le fichier install.php après installation.</small>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>💡 Vérifiez que MySQL est démarré dans XAMPP</div>";
}
?>
</div>
</body>
</html>