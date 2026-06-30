<?php
// actions/equipment_add_action.php - Traitement de l'ajout d'équipement
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    header('Location: ?page=equipment&err=' . urlencode(t('access_denied')));
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=equipment_add&err=' . urlencode(t('csrf_invalid')));
    exit();
}

// Récupération des données
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? '');
$location = trim($_POST['location'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
$warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
$technical_specs = trim($_POST['technical_specs'] ?? '');
$probability_score = intval($_POST['probability_score'] ?? 1);
$severity_score = intval($_POST['severity_score'] ?? 1);
$document_path = trim($_POST['document_path'] ?? '');

// Validation
if (empty($code) || empty($name)) {
    header('Location: ?page=equipment_add&err=' . urlencode(t('required_fields_missing')));
    exit();
}

// Vérification du code unique
$check = $pdo->prepare("SELECT id FROM equipment WHERE code = ?");
$check->execute([$code]);
if ($check->fetch()) {
    header('Location: ?page=equipment_add&err=' . urlencode(t('code_already_exists')));
    exit();
}

try {
    $pdo->beginTransaction();

    // Insertion de l'équipement
    $sql = "INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs, probability_score, severity_score, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $code, $name, $type, $location, $supplier, $purchase_date, $warranty_end, $technical_specs, $probability_score, $severity_score
    ]);

    if (!$result) {
        throw new Exception(t('save_error'));
    }

    $equipment_id = $pdo->lastInsertId();

    // Gestion des pièces détachées associées
    if (isset($_POST['part_id']) && is_array($_POST['part_id'])) {
        $part_ids = $_POST['part_id'];
        $quantities = $_POST['quantity'] ?? [];
        
        // Vérifier que la table equipment_parts existe
        try {
            $pdo->query("SELECT 1 FROM equipment_parts LIMIT 0");
        } catch (PDOException $e) {
            // Créer la table si elle n'existe pas
            $pdo->exec("CREATE TABLE IF NOT EXISTS `equipment_parts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `equipment_id` INT NOT NULL,
                `part_id` INT NOT NULL,
                `quantity_needed` INT NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_equipment_part` (`equipment_id`, `part_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }

        // Insertion des pièces
        $insertPart = $pdo->prepare("INSERT INTO equipment_parts (equipment_id, part_id, quantity_needed) VALUES (?, ?, ?)");
        foreach ($part_ids as $index => $part_id) {
            if (!empty($part_id)) {
                $quantity = isset($quantities[$index]) ? intval($quantities[$index]) : 1;
                if ($quantity < 1) $quantity = 1;
                $insertPart->execute([$equipment_id, $part_id, $quantity]);
            }
        }
    }

    // Gestion du document
    if (!empty($document_path)) {
        $docStmt = $pdo->prepare("INSERT INTO attachments (parent_id, parent_type, file_path, uploaded_by) VALUES (?, 'equipment', ?, ?)");
        $docStmt->execute([$equipment_id, $document_path, $_SESSION['user_id']]);
    }

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'equipment_created', "Equipment created: $code (ID: $equipment_id)");
    header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ?page=equipment_add&err=' . urlencode($e->getMessage()));
}
exit();