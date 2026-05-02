<?php
// test_t_function.php
session_start();
$_SESSION['lang'] = 'en';
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/lang.php';

echo "<h1>Test de la fonction t()</h1>";
echo "Langue: " . getCurrentLanguage() . "<br>";
echo "t('equipment') = " . t('equipment') . "<br>";
echo "t('add_equipment') = " . t('add_equipment') . "<br>";
echo "t('code') = " . t('code') . "<br>";
echo "t('name') = " . t('name') . "<br>";
echo "t('type') = " . t('type') . "<br>";
echo "t('location') = " . t('location') . "<br>";
echo "t('status') = " . t('status') . "<br>";
echo "t('actions') = " . t('actions') . "<br>";
?>