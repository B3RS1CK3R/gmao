<?php
echo "<h2>Diagnostic MySQL</h2>";

try {
    // Test connexion simple
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connexion à MySQL réussie</p>";
    
    // Lister les bases existantes
    $stmt = $pdo->query("SHOW DATABASES");
    echo "<p>📁 Bases de données existantes :</p><ul>";
    while($row = $stmt->fetch()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
    echo "<p>Solutions :</p>";
    echo "<ul>";
    echo "<li>Démarrez MySQL dans XAMPP Control Panel</li>";
    echo "<li>Vérifiez que le port 3306 n'est pas bloqué</li>";
    echo "<li>Réinstallez MySQL si nécessaire</li>";
    echo "</ul>";
}
?>