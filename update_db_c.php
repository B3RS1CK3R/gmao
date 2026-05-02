<?php
// update_db_c.php - Mise à jour pour indicateurs MTBF/MTTR
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Indicateurs MTBF/MTTR</title>
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
    <h1>📊 Mise à jour - Indicateurs MTBF/MTTR</h1>
    <hr>";

try {
    // Ajout de colonnes pour suivre les temps
    $sql1 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS total_operating_hours INT DEFAULT 0";
    $pdo->exec($sql1);
    echo "<div class='success'>✓ Colonne total_operating_hours ajoutée</div>";
    
    $sql2 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS last_failure_date DATETIME NULL";
    $pdo->exec($sql2);
    echo "<div class='success'>✓ Colonne last_failure_date ajoutée</div>";
    
    $sql3 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS total_failures INT DEFAULT 0";
    $pdo->exec($sql3);
    echo "<div class='success'>✓ Colonne total_failures ajoutée</div>";
    
    $sql4 = "ALTER TABLE equipment ADD COLUMN IF NOT EXISTS total_repair_time INT DEFAULT 0";
    $pdo->exec($sql4);
    echo "<div class='success'>✓ Colonne total_repair_time ajoutée</div>";
    
    // Table pour l'historique des indicateurs
    $sql5 = "
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
    )";
    $pdo->exec($sql5);
    echo "<div class='success'>✓ Table performance_metrics créée</div>";
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold;'>✅ Mise à jour terminée !</div>";
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