<?php
// update_stock_doc.php - Ajout du champ documentation_path
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour GMAO - Documentation pièces</title>
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
    <h1>📁 Mise à jour - Documentation des pièces détachées</h1>
    <hr>";

try {
    // Ajout de la colonne documentation_path
    $sql = "ALTER TABLE spare_parts ADD COLUMN IF NOT EXISTS documentation_path VARCHAR(500) NULL";
    $pdo->exec($sql);
    echo "<div class='success'>✓ Colonne 'documentation_path' ajoutée avec succès</div>";
    
    echo "<hr>";
    echo "<div class='success' style='font-size: 18px; font-weight: bold;'>✅ Mise à jour terminée avec succès !</div>";
    echo "<div style='margin-top: 20px;'>
            <a href='index.php?page=stock' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Retour à la GMAO</a>
          </div>";
    
} catch(PDOException $e) {
    echo "<div class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
</div>
</body>
</html>