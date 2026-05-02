<?php
session_start();
echo "<h2>Diagnostic Session</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Langue dans session: " . ($_SESSION['lang'] ?? 'non définie') . "\n";
echo "Contenu complet de \$_SESSION:\n";
print_r($_SESSION);
echo "</pre>";

// Vérifier le cookie
echo "<h2>Cookie de session</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Lien pour changer la langue
echo "<hr>";
echo "<a href='?set_lang=en'>English</a> | <a href='?set_lang=fr'>Français</a>";

if(isset($_GET['set_lang'])) {
    $_SESSION['lang'] = $_GET['set_lang'];
    echo "<p style='color:green'>Langue forcée à: " . $_SESSION['lang'] . "</p>";
    echo "<meta http-equiv='refresh' content='2'>";
}
?>