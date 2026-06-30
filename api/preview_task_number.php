<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}
require_once '../config/database.php';

$type = $_POST['type'] ?? '';
$prefix = trim($_POST['prefix'] ?? '');
$use_year = isset($_POST['use_year']) ? (bool)$_POST['use_year'] : false;
$use_month = isset($_POST['use_month']) ? (bool)$_POST['use_month'] : false;
$digits = (int)($_POST['digits'] ?? 4);

if (!in_array($type, ['intervention', 'preventive'])) {
    echo json_encode(['error' => 'Type invalide']);
    exit;
}

// Récupérer la configuration actuelle pour ce type
$stmt = $pdo->prepare("SELECT * FROM task_format_settings WHERE type = ?");
$stmt->execute([$type]);
$config = $stmt->fetch();

$lastNum = (int)$config['last_number'];
$lastYear = $config['last_year'];
$lastMonth = $config['last_month'];
$resetOnYear = (bool)$config['reset_on_year_change'];
$resetOnMonth = (bool)$config['reset_on_month_change'];
$currentYear = (int)date('y');
$currentMonth = (int)date('m');

// Déterminer si un reset aurait lieu
$needReset = false;
if ($use_year && $resetOnYear && $lastYear !== null && $currentYear != $lastYear) {
    $needReset = true;
}
if (!$needReset && $use_month && $resetOnMonth && $lastMonth !== null && $currentMonth != $lastMonth) {
    $needReset = true;
}

$nextNum = $lastNum + 1;
if ($needReset) $nextNum = 1;

$numberPart = str_pad($nextNum, $digits, '0', STR_PAD_LEFT);
$parts = [$prefix];
if ($use_year) {
    $datePart = date('y');
    if ($use_month) {
        $datePart .= date('m');
    }
    $parts[] = $datePart;
}
$parts[] = $numberPart;

$result = implode('-', $parts);
echo json_encode(['preview' => $result]);