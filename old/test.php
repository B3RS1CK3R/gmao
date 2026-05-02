<?php
echo "=== TEST GMAO ===<br>";
echo "Chemin : " . __DIR__ . "<br>";
echo "PHP Version : " . phpversion() . "<br>";

// Vérifier les dossiers
$folders = ['config', 'includes', 'pages'];
foreach($folders as $folder) {
    if(is_dir($folder)) {
        echo "✅ Dossier $folder existe<br>";
    } else {
        echo "❌ Dossier $folder manquant<br>";
    }
}

// Vérifier les fichiers
$files = ['config/database.php', 'includes/functions.php'];
foreach($files as $file) {
    if(file_exists($file)) {
        echo "✅ Fichier $file existe<br>";
    } else {
        echo "❌ Fichier $file manquant<br>";
    }
}
?>