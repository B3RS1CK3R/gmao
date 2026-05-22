<?php
/**
 * includes/lang.php - Système de traduction GMAO (Français / Anglais)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retourne la langue actuelle
 */
function getCurrentLanguage() {
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    if (isset($_COOKIE['gmao_lang'])) {
        $lang = $_COOKIE['gmao_lang'];
        if (in_array($lang, ['en', 'fr'])) {
            $_SESSION['lang'] = $lang;
            return $lang;
        }
    }
    
    // Détection navigateur
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($lang, ['fr', 'en'])) {
            return $lang;
        }
    }
    
    return 'fr'; // Français par défaut
}

/**
 * Change la langue
 */
function setLanguage($lang) {
    if (!in_array($lang, ['en', 'fr'])) {
        $lang = 'fr';
    }
    $_SESSION['lang'] = $lang;
    setcookie('gmao_lang', $lang, time() + (86400 * 365), '/');
    return $lang;
}

/**
 * Fonction de traduction principale
 */
function t($key) {
    $lang = getCurrentLanguage();
    static $translations = null;
    
    if ($translations === null) {
        $file = __DIR__ . "/languages/{$lang}.php";
        if (file_exists($file)) {
            $translations = require $file;
        } else {
            $translations = require __DIR__ . "/languages/fr.php"; // fallback
        }
    }
    
    return $translations[$key] ?? $key;
}