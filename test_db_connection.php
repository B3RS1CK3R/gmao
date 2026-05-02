<?php
// test_db_connection.php
echo "<h2>Test connexion MySQL</h2>";

// Test 1: Connexion simple
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connexion MySQL réussie (sans base)</p>";
    
    // Test 2: Lister les bases
    $stmt = $pdo->query("SHOW DATABASES");
    echo "<p>Bases disponibles :</p><ul>";
    while($row = $stmt->fetch()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
    // Test 3: Connexion à gmao_db
    $pdo2 = new PDO("mysql:host=localhost;dbname=gmao_db;charset=utf8", "root", "");
    echo "<p style='color:green'>✅ Connexion à gmao_db réussie</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>