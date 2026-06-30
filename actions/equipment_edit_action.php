<?php
// actions/equipment_edit_action.php - Traitement de la modification d'équipement
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits
if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    header('Location: ?page=equipment&err=' . urlencode(t('access_denied')));
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
if (!$id) {
    header('Location: ?page=equipment');
    exit();
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=equipment_edit&id=' . $id . '&err=' . urlencode(t('csrf_invalid')));
    exit();
}

// Récupération des données
$code = trim($_POST['code'] ?? '');
$name = trim($_POST['name'] ?? '');
$type = trim($_POST['type'] ?? '');
$location = trim($_POST['location'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$status = $_POST['status'] ?? 'active';
$purchase_date = !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null;
$warranty_end = !empty($_POST['warranty_end']) ? $_POST['warranty_end'] : null;
$technical_specs = trim($_POST['technical_specs'] ?? '');
$probability_score = intval($_POST['probability_score'] ?? 1);
$severity_score = intval($_POST['severity_score'] ?? 1);

// Validation
if (empty($code) || empty($name)) {
    header('Location: ?page=equipment_edit&id=' . $id . '&err=' . urlencode(t('required_fields_missing')));
    exit();
}

// Vérification du code unique (exclure l'équipement en cours)
$check = $pdo->prepare("SELECT id FROM equipment WHERE code = ? AND id != ?");
$check->execute([$code, $id]);
if ($check->fetch()) {
    header('Location: ?page=equipment_edit&id=' . $id . '&err=' . urlencode(t('code_already_exists')));
    exit();
}

try {
    $pdo->beginTransaction();

    // Mise à jour de l'équipement
    $sql = "UPDATE equipment SET 
                code = ?, 
                name = ?, 
                type = ?, 
                location = ?, 
                supplier = ?, 
                status = ?,
                purchase_date = ?, 
                warranty_end = ?, 
                technical_specs = ?,
                probability_score = ?,
                severity_score = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $code, $name, $type, $location, $supplier, $status,
        $purchase_date, $warranty_end, $technical_specs,
        $probability_score, $severity_score, $id
    ]);

    if (!$result) {
        throw new Exception(t('save_error'));
    }

    // Gestion des pièces détachées associées
    // Supprimer les anciennes associations
    try {
        $pdo->prepare("DELETE FROM equipment_parts WHERE equipment_id = ?")->execute([$id]);
    } catch (PDOException $e) {
        // Table peut ne pas exister, on la crée
        $pdo->exec("CREATE TABLE IF NOT EXISTS `equipment_parts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_id` INT NOT NULL,
            `part_id` INT NOT NULL,
            `quantity_needed` INT NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_equipment_part` (`equipment_id`, `part_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Insérer les nouvelles associations
    if (isset($_POST['part_id']) && is_array($_POST['part_id'])) {
        $part_ids = $_POST['part_id'];
        $quantities = $_POST['quantity'] ?? [];
        
        $insertPart = $pdo->prepare("INSERT INTO equipment_parts (equipment_id, part_id, quantity_needed) VALUES (?, ?, ?)");
        foreach ($part_ids as $index => $part_id) {
            if (!empty($part_id)) {
                $quantity = isset($quantities[$index]) ? intval($quantities[$index]) : 1;
                if ($quantity < 1) $quantity = 1;
                $insertPart->execute([$id, $part_id, $quantity]);
            }
        }
    }

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'equipment_updated', "Equipment updated: $code (ID: $id)");
    header('Location: ?page=equipment&msg=' . urlencode(t('save_success')));

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ?page=equipment_edit&id=' . $id . '&err=' . urlencode($e->getMessage()));
}
exit();