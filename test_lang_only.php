<?php
session_start();
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/lang.php';

// Forcer la langue
$_SESSION['lang'] = 'en';

echo "<h1>Test de traduction sans BDD</h1>";
echo "Langue actuelle: " . getCurrentLanguage() . "<br>";
echo "Dashboard: " . t('dashboard') . "<br>";
echo "Equipment: " . t('equipment') . "<br>";
echo "Interventions: " . t('interventions') . "<br>";
echo "<hr>";
echo "<a href='?set_lang=fr'>Français</a> | <a href='?set_lang=en'>English</a>";

if(isset($_GET['set_lang'])) {
    $_SESSION['lang'] = $_GET['set_lang'];
    header('Location: test_lang_only.php');
}
?>