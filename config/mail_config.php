<?php
// config/mail_config.php - Configuration email
// À MODIFIER AVEC VOS INFORMATIONS

// Configuration SMTP (exemple avec Gmail)
define('SMTP_HOST', 'smtp.gmail.com');     // Serveur SMTP
define('SMTP_PORT', 587);                   // Port (587 pour TLS, 465 pour SSL)
define('SMTP_USER', 'votre.email@gmail.com');  // Votre email
define('SMTP_PASS', 'votre-mot-de-passe');     // Mot de passe ou mot de passe d'application
define('SMTP_SECURE', 'tls');                // 'tls' ou 'ssl'

// Email expéditeur
define('FROM_EMAIL', 'noreply@gmao.com');
define('FROM_NAME', 'GMAO Industrielle');

// Destinataires par défaut (pour les alertes)
define('ALERT_EMAILS', [
    'maintenance@votreentreprise.com',
    'chef.atelier@votreentreprise.com'
]);
?>