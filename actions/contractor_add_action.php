<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    header('Location: ?page=contractors&err=' . urlencode(t('access_denied')));
    exit();
}

if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
    header('Location: ?page=contractor_add&err=' . urlencode(t('csrf_invalid')));
    exit();
}

$company_name = trim($_POST['company_name'] ?? '');
if (empty($company_name)) {
    header('Location: ?page=contractor_add&err=' . urlencode(t('required_fields_missing')));
    exit();
}

// Vérifier si le nom existe déjà
$check = $pdo->prepare("SELECT id FROM contractors WHERE company_name = ?");
$check->execute([$company_name]);
if ($check->fetch()) {
    header('Location: ?page=contractor_add&err=' . urlencode(t('contractor_already_exists')));
    exit();
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO contractors (
        company_name, siret, vat_number, phone, email, website,
        address, postal_code, city, country,
        contact_firstname, contact_lastname, contact_phone, contact_email,
        specialty, contract_number, status, notes, contract_start, contract_end,
        alert_enabled, alert_days_before_1, alert_days_before_2,
        alert_sidebar_intervention, alert_popup_intervention, alert_email_intervention,
        alert_sidebar_maintenance, alert_popup_maintenance, alert_email_maintenance
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $company_name,
        $_POST['siret'] ?? null,
        $_POST['vat_number'] ?? null,
        $_POST['phone'] ?? null,
        $_POST['email'] ?? null,
        $_POST['website'] ?? null,
        $_POST['address'] ?? null,
        $_POST['postal_code'] ?? null,
        $_POST['city'] ?? null,
        $_POST['country'] ?? 'France',
        $_POST['contact_firstname'] ?? null,
        $_POST['contact_lastname'] ?? null,
        $_POST['contact_phone'] ?? null,
        $_POST['contact_email'] ?? null,
        $_POST['specialty'] ?? null,
        $_POST['contract_number'] ?? null,
        $_POST['status'] ?? 'active',
        $_POST['notes'] ?? null,
        !empty($_POST['contract_start']) ? $_POST['contract_start'] : null,
        !empty($_POST['contract_end']) ? $_POST['contract_end'] : null,
        isset($_POST['alert_enabled']) ? 1 : 0,
        intval($_POST['alert_days_before_1'] ?? 90),
        intval($_POST['alert_days_before_2'] ?? 21),
        isset($_POST['alert_sidebar_intervention']) ? 1 : 0,
        isset($_POST['alert_popup_intervention']) ? 1 : 0,
        isset($_POST['alert_email_intervention']) ? 1 : 0,
        isset($_POST['alert_sidebar_maintenance']) ? 1 : 0,
        isset($_POST['alert_popup_maintenance']) ? 1 : 0,
        isset($_POST['alert_email_maintenance']) ? 1 : 0
    ]);

    if (!$result) {
        throw new Exception(t('save_error'));
    }

    $contractor_id = $pdo->lastInsertId();

    // Gestion des équipements assignés
    if (isset($_POST['equipment_ids']) && is_array($_POST['equipment_ids'])) {
        $insertStmt = $pdo->prepare("INSERT INTO contractor_equipment (contractor_id, equipment_id) VALUES (?, ?)");
        foreach ($_POST['equipment_ids'] as $equipment_id) {
            $equipment_id = intval($equipment_id);
            if ($equipment_id > 0) {
                $insertStmt->execute([$contractor_id, $equipment_id]);
            }
        }
    }

    $pdo->commit();

    logUserAction($_SESSION['user_id'], 'contractor_created', "Prestataire créé: $company_name (ID: $contractor_id)");
    header('Location: ?page=contractors&msg=' . urlencode(t('save_success')));

} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: ?page=contractor_add&err=' . urlencode($e->getMessage()));
}
exit();