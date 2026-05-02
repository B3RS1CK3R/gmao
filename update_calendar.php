<?php
// update_calendar.php - Mise à jour pour le planning visuel
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Planning visuel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: green; padding: 10px; background: #d4edda; border-left: 4px solid green; margin: 5px 0; }
        .error { color: red; padding: 10px; background: #f8d7da; border-left: 4px solid red; margin: 5px 0; }
        .info { color: blue; padding: 10px; background: #d1ecf1; border-left: 4px solid blue; margin: 5px 0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>📅 Mise à jour - Planning visuel (Calendrier)</h1>
    <hr>";

try {
    // Ajout de colonnes pour le calendrier
    $sql1 = "ALTER TABLE interventions ADD COLUMN IF NOT EXISTS calendar_color VARCHAR(7) DEFAULT '#3788d8'";
    $pdo->exec($sql1);
    echo "<div class='success'>✓ Colonne calendar_color ajoutée</div>";
    
    $sql2 = "ALTER TABLE interventions ADD COLUMN IF NOT EXISTS all_day BOOLEAN DEFAULT FALSE";
    $pdo->exec($sql2);
    echo "<div class='success'>✓ Colonne all_day ajoutée</div>";
    
    $sql3 = "ALTER TABLE interventions ADD COLUMN IF NOT EXISTS end_date DATETIME NULL";
    $pdo->exec($sql3);
    echo "<div class='success'>✓ Colonne end_date ajoutée</div>";
    
    // Table des rappels
    $sql4 = "
    CREATE TABLE IF NOT EXISTS calendar_reminders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        intervention_id INT,
        reminder_minutes INT DEFAULT 60,
        reminder_sent BOOLEAN DEFAULT FALSE,
        reminder_type ENUM('email', 'push', 'both') DEFAULT 'email',
        FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql4);
    echo "<div class='success'>✓ Table calendar_reminders créée</div>";
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold;'>✅ Mise à jour terminée avec succès !</div>";
    echo "<div style='margin-top: 20px;'>
            <a href='index.php?page=calendar' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📅 Accéder au calendrier</a>
          </div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
</div>
</body>
</html>