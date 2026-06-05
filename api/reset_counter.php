<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès interdit']);
    exit;
}
require_once '../config/database.php';

$type = $_POST['type'] ?? '';
$password = $_POST['password'] ?? '';

if (!in_array($type, ['intervention', 'preventive'])) {
    echo json_encode(['success' => false, 'error' => 'Type invalide']);
    exit;
}

// Vérifier le mot de passe
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Mot de passe incorrect']);
    exit;
}

// Réinitialiser le compteur
$pdo->prepare("UPDATE task_format_settings SET last_number = 0 WHERE type = ?")->execute([$type]);
echo json_encode(['success' => true]);