<?php
// actions/technician_edit_action.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    die("Accès interdit");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=technicians');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération de l'ancien technicien pour le log
    $stmtOld = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmtOld->execute([$id]);
    $oldTechnician = $stmtOld->fetch();
    
    $sql = "UPDATE technicians SET 
            employee_id = ?, firstname = ?, lastname = ?, phone = ?, email = ?, 
            specialty = ?, hire_date = ?, status = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $_POST['employee_id'],
        $_POST['firstname'],
        $_POST['lastname'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['specialty'],
        $_POST['hire_date'],
        $_POST['status'],
        $id
    ]);
    
    if ($result) {
        // Gestion des compétences
        if (isset($_POST['skills'])) {
            $pdo->prepare("DELETE FROM technician_skills WHERE technician_id = ?")->execute([$id]);
            $stmtIns = $pdo->prepare("INSERT INTO technician_skills (technician_id, equipment_type, skill_level, certified) VALUES (?, ?, ?, ?)");
            foreach ($_POST['skills'] as $skill) {
                if (!empty($skill['equipment_type'])) {
                    $certified = isset($skill['certified']) ? 1 : 0;
                    $stmtIns->execute([$id, $skill['equipment_type'], $skill['skill_level'], $certified]);
                }
            }
        }
        
        $technicianName = $_POST['firstname'] . ' ' . $_POST['lastname'];
        logTechnicianUpdate($_SESSION['user_id'], $id, $technicianName, $oldTechnician, $_POST);
        header('Location: ?page=technicians&msg=' . urlencode(t('save_success')));
        exit();
    } else {
        header('Location: ?page=technician_edit&id=' . $id . '&error=' . urlencode(t('save_error')));
        exit();
    }
} else {
    header('Location: ?page=technicians');
    exit();
}