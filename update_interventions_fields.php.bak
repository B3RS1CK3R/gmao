<?php
// update_interventions_fields.php - Ajout des nouveaux champs pour les interventions
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Champs interventions</title>
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
    <h1>📝 Mise à jour - Champs des interventions</h1>
    <hr>";

try {
    // Ajout des nouvelles colonnes
    $columns = [
        "task_number VARCHAR(20) UNIQUE",
        "intervenant_id INT NULL",
        "task_status ENUM('a_faire', 'en_cours', 'termine', 'cloturee') DEFAULT 'a_faire'",
        "intervention_date DATE NULL",
        "task_type ENUM('revision', 'depannage', 'installation', 'maintenance_preventive', 'controle', 'autre') DEFAULT 'revision'",
        "zone VARCHAR(100)",
        "localisation VARCHAR(200)",
        "planned_duration ENUM('1h', '2h', '2h30', '3h', '4h', '6h', '8h', '1j', '2j', '3j') DEFAULT '4h'",
        "completed_date DATETIME NULL",
        "completion_report TEXT"
    ];
    
    foreach($columns as $column) {
        $col_name = explode(' ', $column)[0];
        try {
            $pdo->exec("ALTER TABLE interventions ADD COLUMN $column");
            echo "<div class='success'>✓ Colonne '$col_name' ajoutée</div>";
        } catch(PDOException $e) {
            if(strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<div class='info'>ℹ️ Colonne '$col_name' existe déjà</div>";
            } else {
                echo "<div class='error'>❌ Erreur pour '$col_name' : " . $e->getMessage() . "</div>";
            }
        }
    }
    
    // Création d'une séquence pour le numéro de tâche
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS task_sequence (
                id INT PRIMARY KEY AUTO_INCREMENT,
                last_number INT DEFAULT 260031,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Insérer la valeur initiale si vide
        $stmt = $pdo->query("SELECT COUNT(*) FROM task_sequence");
        if($stmt->fetchColumn() == 0) {
            $pdo->exec("INSERT INTO task_sequence (last_number) VALUES (260031)");
            echo "<div class='success'>✓ Séquence des tâches initialisée (démarre à 260032)</div>";
        }
    } catch(PDOException $e) {
        echo "<div class='error'>❌ Erreur création séquence : " . $e->getMessage() . "</div>";
    }
    
    // Mise à jour des interventions existantes avec un numéro de tâche
    $stmt = $pdo->query("SELECT id FROM interventions WHERE task_number IS NULL");
    $existing = $stmt->fetchAll();
    
    $seq_stmt = $pdo->prepare("UPDATE task_sequence SET last_number = last_number + 1");
    $update_stmt = $pdo->prepare("UPDATE interventions SET task_number = ? WHERE id = ?");
    
    foreach($existing as $row) {
        $seq_stmt->execute();
        $seq = $pdo->query("SELECT last_number FROM task_sequence")->fetchColumn();
        $update_stmt->execute(["TASK-" . $seq, $row['id']]);
    }
    
    if(count($existing) > 0) {
        echo "<div class='success'>✓ " . count($existing) . " interventions existantes mises à jour avec un numéro de tâche</div>";
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