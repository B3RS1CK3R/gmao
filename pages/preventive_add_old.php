<?php
// pages/preventive_add.php - Ajout d'une maintenance préventive
$equipment_id_param = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

// Fetch necessary data for the form
$equipments = $pdo->query("SELECT id, code, name, location, zone FROM equipment WHERE status IN ('active', 'maintenance') ORDER BY name")->fetchAll();
$technicians = $pdo->query("SELECT id, firstname, lastname, speciality FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();

// Generate the next task number for display
$next_task_number = generateTaskNumber($pdo, 'preventive', true);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_number = generateTaskNumber($pdo, 'preventive', false);
    $technician_id  = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id        = !empty($_POST['team_id']) ? $_POST['team_id'] : null;
    if ($team_id) $technician_id = null;

    $sql = "INSERT INTO preventive_maintenance (
        task_number, equipment_id, frequency_days, last_done, next_due, title, instructions, technician_id, team_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_number,
        $_POST['equipment_id'],
        $_POST['frequency_days'],
        $_POST['last_done'],
        $_POST['next_due'],
        $_POST['title'],
        $_POST['instructions'],
        $technician_id,
        $team_id
    ]);

    if ($result) {
        logUserAction($_SESSION['user_id'], 'preventive_created', "Preventive created: $task_number");
        $message = "✅ " . t('preventive_created') . " " . t('task_number') . ": <strong>$task_number</strong>";
        echo "<script>setTimeout(() => { window.location.href = '?page=preventive'; }, 2000);</script>";
    } else {
        $error = "❌ " . t('save_error');
    }
}
?>

<style>
    .info-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .card-header-custom { background: #28a745; color: white; padding: 12px 20px; font-weight: bold; }
    .task-number-display { font-size: 20px; font-weight: bold; color: #28a745; background: #e8f5e9; padding: 8px 15px; border-radius: 10px; display: inline-block; }
    .form-label { font-weight: 500; margin-bottom: 5px; color: #4a5568; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px 12px; }
    .btn-success { background: #28a745; border: none; border-radius: 8px; padding: 10px 25px; font-weight: 600; }
    .btn-secondary { background: #718096; border: none; border-radius: 8px; padding: 10px 25px; }
</style>

<div class="container-fluid">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show alert-fixed"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-fixed"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle text-primary"></i> <?php echo t('add_maintenance'); ?></h2>
        <a href="?page=preventive" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> <?php echo t('back_to_list'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Ligne 1 : Identification (pleine largeur) -->
        <div class="info-card">
            <div class="card-header-custom"><i class="fas fa-tag me-2"></i> <?php echo t('identification'); ?></div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('task_number'); ?></label>
                        <div><span class="task-number-display"><?php echo $next_task_number; ?></span></div>
                        <small class="text-muted"><?php echo t('auto_increment'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('created_at'); ?></label>
                        <div><span class="task-number-display" style="background: #edf2f7; color: #4a5568;"><?php echo format_date_local(date('Y-m-d H:i:s'), 'long', true); ?></span></div>
                        <small class="text-muted"><?php echo t('current_datetime'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 2 : Two columns -->
        <div class="row">
            <div class="col-md-6">
                <!-- Equipment and Location -->
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-map-marker-alt me-2"></i> <?php echo t('equipment_and_location'); ?></div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                            <select name="equipment_id" id="equipment_id" class="form-select" required>
                                <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                                <?php foreach ($equipments as $eq): ?>
                                <option value="<?php echo $eq['id']; ?>" 
                                        data-zone="<?php echo htmlspecialchars($eq['zone'] ?? ''); ?>" 
                                        data-location="<?php echo htmlspecialchars($eq['location'] ?? ''); ?>" <?php echo ($equipment_id_param == $eq['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('zone'); ?></label><input type="text" name="zone" id="zone" class="form-control" placeholder="<?php echo t('zone_placeholder'); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('localisation'); ?></label><input type="text" name="localisation" id="localisation" class="form-control" placeholder="<?php echo t('localisation_placeholder'); ?>"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Instruction -->
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-clipboard-list me-2"></i> <?php echo t('instruction'); ?></div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('title'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Ex: Remplacement filtre"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('details'); ?></label>
                            <textarea name="instructions" class="form-control" rows="3" placeholder="<?php echo t('details_placeholder'); ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 3 : Two columns -->
        <div class="row">
            <div class="col-md-6">
                <!-- Planning -->
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-calendar-alt me-2"></i> <?php echo t('planning'); ?></div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('frequency_days'); ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="frequency_days" class="form-control" min="1" required>
                                    <span class="input-group-text"><?php echo t('days_s'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('last_done'); ?></label>
                            <input type="date" name="last_done" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-users me-2"></i> <?php echo t('assignment'); ?></div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('technician'); ?></label>
                                <select name="technician_id" class="form-select">
                                    <option value="">-- <?php echo t('unassigned'); ?> --</option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('team'); ?></label>
                                <select name="team_id" class="form-select">
                                    <option value="">-- <?php echo t('select_team'); ?> --</option>
                                    <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-success btn-lg px-5"><i class="fas fa-save me-2"></i> Créer</button>
            <a href="?page=preventive" class="btn btn-secondary btn-lg ms-3 px-5"><i class="fas fa-times me-2"></i> Annuler</a>
        </div>
    </form>
</div>

<script>
document.getElementById('equipment_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById('zone').value = selectedOption.getAttribute('data-zone') || '';
    document.getElementById('localisation').value = selectedOption.getAttribute('data-location') || '';
});
if (document.getElementById('equipment_id').value) {
    document.getElementById('equipment_id').dispatchEvent(new Event('change'));
}
</script>