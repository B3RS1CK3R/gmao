<?php
// pages/preventive_assign.php - Assigner une maintenance préventive
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Vérification des droits (admin ou superviseur)
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor')) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

// Fonction pour récupérer une tâche avec ses infos d'équipement
function getPreventiveTaskWithEquipment($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT pm.*, e.name as equipment_name, e.code as equipment_code
        FROM preventive_maintenance pm
        JOIN equipment e ON pm.equipment_id = e.id
        WHERE pm.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Récupérer la tâche
$task = getPreventiveTaskWithEquipment($pdo, $id);

if (!$task) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Récupérer la liste des techniciens actifs
$technicians = getAllTechnicians('active');

// Récupérer la liste des équipes (table "teams")
$teams = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name");
    $teams = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table teams peut ne pas exister
}

$error = '';
$success = '';

// Traitement du formulaire d'assignation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } else {
        $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
        $team_id       = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
        $scheduled_date = !empty($_POST['scheduled_date']) ? $_POST['scheduled_date'] : null;
        $comments      = trim($_POST['comments'] ?? '');

        if ($technician_id && $team_id) {
            $error = t('assign_technician_or_team');
        } elseif (!$technician_id && !$team_id) {
            $error = t('select_technician_or_team');
        } else {
            $sql = "UPDATE preventive_maintenance SET 
                        technician_id = ?,
                        team_id = ?,
                        scheduled_date = ?,
                        assign_comments = ?,
                        assigned_at = NOW()
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$technician_id, $team_id, $scheduled_date, $comments, $id]);

            if ($result) {
                $target = $technician_id ? "technicien ID $technician_id" : "équipe ID $team_id";
                logUserAction($_SESSION['user_id'], 'preventive_assigned', "Maintenance ID $id assignée à $target");
                $success = t('assign_success');
                // Recharger la tâche avec la jointure
                $task = getPreventiveTaskWithEquipment($pdo, $id);
            } else {
                $error = t('save_error');
            }
        }
    }
}
?>

<style>
    .assign-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .assign-header { background: linear-gradient(135deg, #17a2b8, #0c6b7e); color: white; padding: 15px 20px; font-weight: bold; }
    .info-row { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
    .form-label { font-weight: 500; }
    .btn-primary { background: #17a2b8; border: none; border-radius: 8px; }
    .btn-primary:hover { background: #138496; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-check"></i> <?php echo t('assign_preventive_maintenance'); ?></h2>
        <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="assign-card">
                <div class="assign-header"><i class="fas fa-info-circle"></i> <?php echo t('task_details'); ?></div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <strong><?php echo t('task_number'); ?> :</strong>
                        <?php echo htmlspecialchars($task['reference'] ?? generateTaskNumber($pdo, 'preventive', true)); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('equipment'); ?> :</strong>
                        <?php
                        // Protection contre les clés manquantes
                        $code = $task['equipment_code'] ?? '';
                        $name = $task['equipment_name'] ?? '';
                        echo htmlspecialchars($code . ' - ' . $name);
                        ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('title'); ?> :</strong>
                        <?php echo htmlspecialchars($task['title']); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('frequency_days'); ?> :</strong>
                        <?php echo $task['frequency_days'] . ' ' . t('days_s'); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('next_due'); ?> :</strong>
                        <?php echo format_date_local($task['next_due'], 'short'); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('instructions'); ?> :</strong><br>
                        <?php echo nl2br(htmlspecialchars($task['instructions'] ?? '')); ?>
                    </div>
                    <?php if (!empty($task['technician_id']) || !empty($task['team_id'])): ?>
                        <div class="info-row bg-success text-white">
                            <strong><?php echo t('current_assignment'); ?> :</strong>
                            <?php
                            if (!empty($task['technician_id'])) {
                                $tech = getTechnician($task['technician_id']);
                                echo t('technician') . ': ' . htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']);
                            } elseif (!empty($task['team_id'])) {
                                $stmt = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
                                $stmt->execute([$task['team_id']]);
                                $team = $stmt->fetch();
                                echo t('team') . ': ' . htmlspecialchars($team['name']);
                            }
                            ?>
                            <?php if (!empty($task['scheduled_date'])): ?>
                                <br><strong><?php echo t('scheduled_date'); ?> :</strong>
                                <?php echo format_date_local($task['scheduled_date'], 'short'); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="assign-card">
                <div class="assign-header"><i class="fas fa-tools"></i> <?php echo t('assign_to'); ?></div>
                <div class="card-body p-4">
                    <form method="POST">
                        <?php echo csrf_input(); ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('technician'); ?></label>
                            <select name="technician_id" class="form-select" id="technicianSelect">
                                <option value="">-- <?php echo t('select_technician'); ?> --</option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>"
                                        <?php echo (!empty($task['technician_id']) && $task['technician_id'] == $tech['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('team'); ?></label>
                            <select name="team_id" class="form-select" id="teamSelect">
                                <option value="">-- <?php echo t('select_team'); ?> --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?php echo $team['id']; ?>"
                                        <?php echo (!empty($task['team_id']) && $task['team_id'] == $team['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($team['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php echo t('team_overrides_technician'); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('scheduled_date'); ?></label>
                            <input type="date" name="scheduled_date" class="form-control"
                                    value="<?php echo !empty($task['scheduled_date']) ? $task['scheduled_date'] : date('Y-m-d', strtotime($task['next_due'])); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('comments'); ?></label>
                            <textarea name="comments" class="form-control" rows="3"
                                    placeholder="<?php echo t('optional_instructions'); ?>"><?php echo htmlspecialchars($task['assign_comments'] ?? ''); ?></textarea>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="assign" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo t('assign'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('technicianSelect').addEventListener('change', function() {
    if (this.value) document.getElementById('teamSelect').value = '';
});
document.getElementById('teamSelect').addEventListener('change', function() {
    if (this.value) document.getElementById('technicianSelect').value = '';
});
</script>