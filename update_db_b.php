<?php
// update_db_b.php - Mise à jour pour la gestion des techniciens
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Gestion des techniciens</title>
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
    <h1>👥 Mise à jour - Gestion des techniciens</h1>
    <hr>";

try {
    // Table des techniciens
    $sql_technicians = "
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
    )";
    
    $pdo->exec($sql_technicians);
    echo "<div class='success'>✓ Table 'technicians' créée</div>";
    
    // Table des compétences par équipement
    $sql_skills = "
    CREATE TABLE IF NOT EXISTS technician_skills (
        id INT PRIMARY KEY AUTO_INCREMENT,
        technician_id INT,
        equipment_type VARCHAR(100),
        skill_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
        certified BOOLEAN DEFAULT FALSE,
        certification_date DATE,
        FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
        UNIQUE KEY unique_skill (technician_id, equipment_type)
    )";
    
    $pdo->exec($sql_skills);
    echo "<div class='success'>✓ Table 'technician_skills' créée</div>";
    
    // Ajout du champ assigned_to dans interventions
    $sql_interventions = "
    ALTER TABLE interventions 
    ADD COLUMN IF NOT EXISTS technician_id INT,
    ADD COLUMN IF NOT EXISTS scheduled_date DATE,
    ADD COLUMN IF NOT EXISTS scheduled_time TIME,
    ADD COLUMN IF NOT EXISTS completion_notes TEXT,
    ADD FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    ADD INDEX idx_scheduled_date (scheduled_date),
    ADD INDEX idx_technician (technician_id)";
    
    $pdo->exec($sql_interventions);
    echo "<div class='success'>✓ Table 'interventions' mise à jour</div>";
    
    // Table des disponibilités
    $sql_availability = "
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
    )";
    
    $pdo->exec($sql_availability);
    echo "<div class='success'>✓ Table 'technician_availability' créée</div>";
    
    // Table des interventions planifiées
    $sql_schedule = "
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
    )";
    
    $pdo->exec($sql_schedule);
    echo "<div class='success'>✓ Table 'work_schedule' créée</div>";
    
    // Ajout d'un technicien de démo
    $stmt = $pdo->prepare("
        INSERT INTO technicians (employee_id, firstname, lastname, phone, email, specialty, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE firstname = VALUES(firstname)
    ");
    $stmt->execute(['TECH001', 'Jean', 'Dupont', '0612345678', 'jean.dupont@entreprise.com', 'Électricien, Mécanique', 'active']);
    echo "<div class='success'>✓ Technicien de démo ajouté</div>";
    
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