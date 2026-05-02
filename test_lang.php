<?php
session_start();
require_once 'includes/lang.php';

echo "Langue actuelle: " . getCurrentLanguage() . "<br>";
echo "Dashboard: " . t('dashboard') . "<br>";
echo "Equipment: " . t('equipment') . "<br>";
echo "<hr>";
echo "<a href='?lang=fr'>Français</a> | <a href='?lang=en'>English</a>";

if(isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    header('Location: test_lang.php');
}
?>