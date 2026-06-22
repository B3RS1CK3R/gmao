<?php
// pages/preventive_edit.php - Édition d'une maintenance préventive
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

$stmt = $pdo->prepare("
    SELECT pm.*, 
           e.name as equipment_name, 
           e.code as equipment_code,
           t.id as technician_id, 
           t.firstname, 
           t.lastname,
           team.id as team_id, 
           team.name as team_name
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    LEFT JOIN technicians t ON pm.technician_id = t.id
    LEFT JOIN teams team ON pm.team_id = team.id
    WHERE pm.id = ?
");
$stmt->execute([$id]);
$pm = $stmt->fetch();

if (!$pm) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Récupérer le contractor_id si la colonne existe
$contractor_id = null;
try {
    $check = $pdo->query("SHOW COLUMNS FROM preventive_maintenance LIKE 'contractor_id'");
    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT contractor_id FROM preventive_maintenance WHERE id = ?");
        $stmt->execute([$id]);
        $contractor_id = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    // Ignorer si la colonne n'existe pas
}

$equipments = $pdo->query("SELECT id, code, name FROM equipment WHERE status IN ('active', 'maintenance') ORDER BY name")->fetchAll();
$technicians = $pdo->query("SELECT id, firstname, lastname FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
$contractors = $pdo->query("SELECT id, company_name, specialty FROM contractors WHERE status = 'active' ORDER BY company_name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_id   = intval($_POST['equipment_id']);
    $frequency_days = intval($_POST['frequency_days']);
    $last_done      = !empty($_POST['last_done']) ? $_POST['last_done'] : date('Y-m-d');
    $title          = trim($_POST['title'] ?? '');
    $instructions   = trim($_POST['instructions'] ?? '');
    $technician_id  = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id        = (isset($_POST['team_id']) && $_POST['team_id'] !== '') ? intval($_POST['team_id']) : null;
    $contractor_id  = !empty($_POST['contractor_id']) ? intval($_POST['contractor_id']) : null;
    
    // Priorité : équipe > technicien > prestataire
    if ($team_id) {
        $technician_id = null;
        $contractor_id = null;
    } elseif ($technician_id) {
        $contractor_id = null;
    }

    if ($equipment_id <= 0 || empty($title)) {
        $error = "Veuillez sélectionner un équipement et renseigner un titre.";
    } elseif ($frequency_days < 1) {
        $error = "La fréquence doit être d'au moins 1 jour.";
    } else {
        $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

        $sql = "UPDATE preventive_maintenance SET 
                equipment_id = ?, 
                frequency_days = ?, 
                last_done = ?, 
                next_due = ?, 
                title = ?, 
                instructions = ?, 
                technician_id = ?, 
                team_id = ?,
                contractor_id = ? 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $equipment_id, 
            $frequency_days, 
            $last_done, 
            $next_due, 
            $title, 
            $instructions, 
            $technician_id, 
            $team_id,
            $contractor_id,
            $id
        ]);

        if ($result) {
            logUserAction($_SESSION['user_id'], 'preventive_updated', "ID: {$id} - Ref: {$pm['task_number']}");
            header('Location: ?page=preventive&msg=' . urlencode(t('save_success')));
            exit();
        } else {
            $error = t('save_error');
        }
    }
}
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #fd7e14, #e06a0a); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .assignment-section { background: #f8f9fa; border-radius: 10px; padding: 15px; border: 1px solid #e9ecef; }
    .assignment-section .section-title { font-size: 14px; font-weight: 600; color: #495057; margin-bottom: 15px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_maintenance'); ?> : <?php echo htmlspecialchars($pm['equipment_name']); ?></h2>
        <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_maintenance'); ?></div>
        <div class="card-body p-4">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('task_number'); ?></label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($pm['task_number'] ?? ''); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                        <select name="equipment_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                            <?php foreach ($equipments as $eq): ?>
                                <option value="<?php echo $eq['id']; ?>" <?php if ($pm['equipment_id'] == $eq['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Titre de la tâche <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($pm['title'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('frequency_days'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="frequency_days" class="form-control" min="1" value="<?php echo $pm['frequency_days']; ?>" required>
                            <span class="input-group-text"><?php echo t('days_s'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('last_done'); ?></label>
                        <input type="date" name="last_done" class="form-control" value="<?php echo $pm['last_done']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('next_due'); ?></label>
                        <input type="date" class="form-control" value="<?php echo $pm['next_due']; ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label"><?php echo t('instructions'); ?></label>
                    <textarea name="instructions" class="form-control" rows="4"><?php echo htmlspecialchars($pm['instructions'] ?? ''); ?></textarea>
                </div>

                <!-- Section Assignation avec prestataires -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="assignment-section">
                            <div class="section-title"><i class="fas fa-user-cog"></i> <?php echo t('assign_to'); ?></div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('technician'); ?></label>
                                    <select name="technician_id" id="technicianSelect" class="form-select">
                                        <option value="">-- <?php echo t('unassigned'); ?> --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?php echo $tech['id']; ?>" <?php if ($pm['technician_id'] == $tech['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('or_select_contractor'); ?></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('contractor'); ?></label>
                                    <select name="contractor_id" id="contractorSelect" class="form-select">
                                        <option value="">-- <?php echo t('select_contractor'); ?> --</option>
                                        <?php foreach ($contractors as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php if ($contractor_id == $c['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($c['company_name'] . (isset($c['specialty']) && !empty($c['specialty']) ? ' (' . $c['specialty'] . ')' : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('or_select_technician'); ?></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('team'); ?></label>
                                    <select name="team_id" class="form-select">
                                        <option value="">-- <?php echo t('select_team'); ?> --</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id']; ?>" <?php if ($pm['team_id'] == $team['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($team['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('team_overrides_technician'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <?= csrf_input() ?>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('save'); ?></button>
                    <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const technicianSelect = document.getElementById('technicianSelect');
    const contractorSelect = document.getElementById('contractorSelect');

    if (technicianSelect && contractorSelect) {
        technicianSelect.addEventListener('change', function() {
            if (this.value) {
                contractorSelect.value = '';
            }
        });

        contractorSelect.addEventListener('change', function() {
            if (this.value) {
                technicianSelect.value = '';
            }
        });
    }
});
</script>