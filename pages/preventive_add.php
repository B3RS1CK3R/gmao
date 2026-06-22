<?php
// pages/preventive_add.php - Formulaire complet d'ajout de maintenance préventive
$equipment_id_param = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

// Récupérer les données
$equipments = $pdo->query("SELECT id, code, name, location, zone FROM equipment WHERE status IN ('active', 'maintenance') ORDER BY name")->fetchAll();
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
$contractors = $pdo->query("SELECT id, company_name, specialty FROM contractors WHERE status = 'active' ORDER BY company_name")->fetchAll();

// Générer le prochain numéro de tâche (aperçu)
$next_task_number = generateTaskNumber($pdo, 'preventive', true);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_number = generateTaskNumber($pdo, 'preventive', false);
    $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
    $team_id = !empty($_POST['team_id']) ? intval($_POST['team_id']) : null;
    $contractor_id = !empty($_POST['contractor_id']) ? intval($_POST['contractor_id']) : null;
    
    // Priorité : équipe > technicien > prestataire
    if ($team_id) {
        $technician_id = null;
        $contractor_id = null;
    } elseif ($technician_id) {
        $contractor_id = null;
    }

    // Recalcul de next_due côté serveur pour éviter les incohérences
    $last_done = $_POST['last_done'] ?: date('Y-m-d');
    $frequency_days = intval($_POST['frequency_days']);
    $next_due = date('Y-m-d', strtotime($last_done . ' + ' . $frequency_days . ' days'));

    $sql = "INSERT INTO preventive_maintenance (
        task_number, equipment_id, frequency_days, last_done, next_due, title, instructions,
        technician_id, team_id, contractor_id, priority, planned_duration
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_number,
        $_POST['equipment_id'],
        $frequency_days,
        $last_done,
        $next_due,
        $_POST['title'],
        $_POST['instructions'],
        $technician_id,
        $team_id,
        $contractor_id,
        $_POST['priority'],
        $_POST['planned_duration']
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
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .card-header-custom {
        background: #28a745;
        color: white;
        padding: 12px 20px;
        font-weight: bold;
    }
    .task-number-display {
        font-size: 20px;
        font-weight: bold;
        color: #28a745;
        background: #e8f5e9;
        padding: 8px 15px;
        border-radius: 10px;
        display: inline-block;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
        color: #4a5568;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 10px 12px;
    }
    .btn-success {
        background: #28a745;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 600;
    }
    .btn-secondary {
        background: #718096;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
    }
    .alert-fixed {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .assignment-section {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #e9ecef;
    }
    .assignment-section .section-title {
        font-size: 14px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
    }
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

        <!-- Ligne 2 : deux colonnes -->
        <div class="row">
            <div class="col-md-6">
                <!-- Équipement et localisation -->
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
                <!-- Instructions -->
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-clipboard-list me-2"></i> <?php echo t('instructions'); ?></div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('title'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Ex: Inspection annuelle">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea name="instructions" class="form-control" rows="3" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 3 : deux colonnes -->
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
                                    <input type="number" name="frequency_days" id="frequency_days" class="form-control" min="1" required>
                                    <span class="input-group-text"><?php echo t('days_s'); ?></span>
                                </div>
                                <small class="text-muted"><?php echo t('frequency_help'); ?></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('priority'); ?></label>
                                <select name="priority" class="form-select">
                                    <option value="low"><?php echo t('low'); ?></option>
                                    <option value="medium" selected><?php echo t('medium'); ?></option>
                                    <option value="high"><?php echo t('high'); ?></option>
                                    <option value="critical"><?php echo t('critical'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('last_done'); ?></label>
                                <input type="date" name="last_done" id="last_done" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('planned_date'); ?></label>
                                <input type="date" name="next_due" id="next_due" class="form-control" readonly style="background-color: #e9ecef;">
                                <small class="text-muted"><?php echo t('auto_calculated'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <!-- Assignation avec prestataires -->
                <div class="info-card">
                    <div class="card-header-custom"><i class="fas fa-users me-2"></i> <?php echo t('assignment'); ?></div>
                    <div class="card-body p-4">
                        <div class="assignment-section">
                            <div class="section-title"><i class="fas fa-user-cog"></i> <?php echo t('assign_to'); ?></div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?php echo t('technician'); ?></label>
                                    <select name="technician_id" id="technicianSelect" class="form-select">
                                        <option value="">-- <?php echo t('unassigned'); ?> --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>"><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('or_select_contractor'); ?></small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?php echo t('contractor'); ?></label>
                                    <select name="contractor_id" id="contractorSelect" class="form-select">
                                        <option value="">-- <?php echo t('select_contractor'); ?> --</option>
                                        <?php foreach ($contractors as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['company_name'] . (isset($c['specialty']) && !empty($c['specialty']) ? ' (' . $c['specialty'] . ')' : '')); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('or_select_technician'); ?></small>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-12 mb-2">
                                    <label class="form-label"><?php echo t('team'); ?></label>
                                    <select name="team_id" class="form-select">
                                        <option value="">-- <?php echo t('select_team'); ?> --</option>
                                        <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('team_overrides_technician'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ligne 4 : Paramètres (pleine largeur) -->
        <div class="info-card">
            <div class="card-header-custom"><i class="fas fa-cog me-2"></i> <?php echo t('settings'); ?></div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('task_type'); ?></label>
                        <input type="text" class="form-control" value="<?php echo t('preventive_maintenance'); ?>" disabled>
                        <input type="hidden" name="task_type" value="maintenance_preventive">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('intervention_type'); ?></label>
                        <input type="text" class="form-control" value="<?php echo t('preventive'); ?>" disabled>
                        <input type="hidden" name="type" value="preventive">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('planned_duration'); ?></label>
                        <select name="planned_duration" class="form-select">
                            <option value="1h">1h</option>
                            <option value="2h">2h</option>
                            <option value="2h30">2h30</option>
                            <option value="3h">3h</option>
                            <option value="4h" selected>4h</option>
                            <option value="6h">6h</option>
                            <option value="8h">8h</option>
                            <option value="1j">1j</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-success btn-lg px-5"><i class="fas fa-save me-2"></i> <?php echo t('create'); ?></button>
            <a href="?page=preventive" class="btn btn-secondary btn-lg ms-3 px-5"><i class="fas fa-times me-2"></i> <?php echo t('cancel'); ?></a>
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

// Fonction pour mettre à jour la date prévue automatiquement
function updateDueDate() {
    const lastDone = document.getElementById('last_done').value;
    const frequency = parseInt(document.getElementById('frequency_days').value);
    
    if (lastDone && !isNaN(frequency) && frequency > 0) {
        const date = new Date(lastDone);
        date.setDate(date.getDate() + frequency);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        document.getElementById('next_due').value = `${year}-${month}-${day}`;
    } else {
        document.getElementById('next_due').value = '';
    }
}

const lastDoneInput = document.getElementById('last_done');
const frequencyInput = document.getElementById('frequency_days');
if (lastDoneInput) lastDoneInput.addEventListener('change', updateDueDate);
if (frequencyInput) frequencyInput.addEventListener('input', updateDueDate);
updateDueDate();

// Gestion de l'exclusion mutuelle technicien / prestataire
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