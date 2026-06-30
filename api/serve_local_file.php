<?php
// api/serve_local_file.php - Servir un fichier local pour le télécharger ou l'ouvrir
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die("Accès interdit");
}

$file_path = $_GET['path'] ?? '';
if (empty($file_path)) {
    http_response_code(400);
    die("Chemin manquant");
}

// Sécurité : interdire les chemins contenant ".." pour éviter les traversées de répertoires
if (strpos($file_path, '..') !== false) {
    http_response_code(403);
    die("Chemin invalide");
}

// Vérifier que le fichier existe
if (!file_exists($file_path)) {
    http_response_code(404);
    die("Fichier introuvable");
}

// Déterminer le type MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Envoyer les en-têtes
header('Content-Type: ' . $mime);
header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Lire le fichier et le servir
readfile($file_path);
exit;