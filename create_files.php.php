<?php
// create_files.php - EXÉCUTEZ-LE UNE FOIS POUR CRÉER TOUS LES FICHIERS
$files = [
    'config/database.php' => '<?php
define(\'DB_HOST\', \'localhost\');
define(\'DB_NAME\', \'gmao_db\');
define(\'DB_USER\', \'root\');
define(\'DB_PASS\', \'\');
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
session_start();
?>',
    // Ajoutez ici les autres fichiers...
];

foreach($files as $path => $content) {
    $dir = dirname($path);
    if(!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($path, $content);
    echo "✓ Créé : $path<br>";
}
echo "✅ Tous les fichiers créés !";
?>