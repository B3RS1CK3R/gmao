<?php
// update_db.php - Exécuter une fois pour mettre à jour la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour base de données GMAO</title>
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
    <h1>🔄 Mise à jour de la base de données GMAO</h1>
    <hr>";

try {
    // Connexion à la base de données
    require_once 'config/database.php';
    
    echo "<div class='info'>✓ Connexion à la base de données réussie</div>";
    
    // 1. Ajout des colonnes pour les alertes email
    $queries = [
        "ALTER TABLE preventive_maintenance ADD COLUMN IF NOT EXISTS last_alert_sent DATETIME NULL",
        "ALTER TABLE spare_parts ADD COLUMN IF NOT EXISTS last_alert_sent DATETIME NULL",
        "ALTER TABLE interventions ADD COLUMN IF NOT EXISTS alert_sent DATETIME NULL"
    ];
    
    foreach($queries as $query) {
        try {
            $pdo->exec($query);
            echo "<div class='success'>✓ Exécuté : " . htmlspecialchars($query) . "</div>";
        } catch(PDOException $e) {
            if(strpos($e->getMessage(), 'Duplicate column') === false) {
                echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
            } else {
                echo "<div class='info'>ℹ️ La colonne existe déjà</div>";
            }
        }
    }
    
    // 2. Création de la table report_log
    $createTable = "
    CREATE TABLE IF NOT EXISTS report_log (
        id INT PRIMARY KEY,
        last_report_sent DATETIME NULL
    )";
    
    try {
        $pdo->exec($createTable);
        echo "<div class='success'>✓ Table report_log créée ou déjà existante</div>";
    } catch(PDOException $e) {
        echo "<div class='error'>❌ Erreur création table : " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold; text-align: center;'>✅ MISE À JOUR TERMINÉE AVEC SUCCÈS !</div>";
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<button onclick=\"window.location.href='index.php'\">🚀 Retour à la GMAO</button>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur de connexion : " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'>💡 Vérifiez que :<br>";
    echo "- MySQL est démarré dans XAMPP<br>";
    echo "- Les identifiants dans config/database.php sont corrects<br>";
    echo "- La base de données gmao_db existe</div>";
}
?>
</div>
</body>
</html>