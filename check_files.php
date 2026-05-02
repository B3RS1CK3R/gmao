<?php
echo "<h2>Vérification des fichiers GMAO</h2>";

$files = [
    'index.php',
    'config/database.php',
    'includes/functions.php',
    'pages/dashboard.php',
    'pages/login.php'
];

foreach($files as $file) {
    if(file_exists($file)) {
        echo "<span style='color:green'>✅ $file - OK</span><br>";
    } else {
        echo "<span style='color:red'>❌ $file - MANQUANT</span><br>";
    }
}

// Test connexion BDD
echo "<h3>Test base de données :</h3>";
if(file_exists('config/database.php')) {
    require_once 'config/database.php';
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        echo "<span style='color:green'>✅ Connexion BDD OK - " . $stmt->fetch()['count'] . " utilisateurs</span><br>";
    } catch(Exception $e) {
        echo "<span style='color:red'>❌ Erreur BDD : " . $e->getMessage() . "</span><br>";
    }
}
?>