<?php
echo "<h2>Test connexion MySQL</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    echo "<p style='color:green'>✅ Connexion MySQL réussie !</p>";
    
    $stmt = $pdo->query("SHOW DATABASES");
    echo "<p>Bases de données disponibles :</p><ul>";
    while($row = $stmt->fetch()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>