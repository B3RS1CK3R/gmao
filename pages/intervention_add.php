<?php
// pages/intervention_add.php - Formulaire complet d'ajout d'intervention
// Alignement avec la charte graphique du projet

if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$equipment_id_param = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

// Récupérer les équipements avec zone et localisation
$equipments = $pdo->query("SELECT id, code, name, location, zone FROM equipment WHERE status = 'active' ORDER BY name")->fetchAll();
$intervenants = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Générer le prochain numéro de tâche
$stmt = $pdo->query("SELECT last_number FROM task_sequence");
$last = $stmt->fetchColumn();
$next_number = ($last ? $last + 1 : 260032);
$next_task_number = "TASK-" . $next_number;

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Générer le vrai numéro
    $pdo->exec("UPDATE task_sequence SET last_number = last_number + 1");
    $stmt = $pdo->query("SELECT last_number FROM task_sequence");
    $new_number = $stmt->fetchColumn();
    $task_number = "TASK-" . $new_number;
    
    $sql = "INSERT INTO interventions (
        task_number, equipment_id, type, priority, title, description, reported_by,
        intervenant_id, task_status, intervention_date, task_type, zone, localisation, planned_duration
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_number,
        $_POST['equipment_id'],
        $_POST['type'],
        $_POST['priority'],
        $_POST['title'],
        $_POST['description'],
        $_SESSION['username'],
        !empty($_POST['intervenant_id']) ? $_POST['intervenant_id'] : null,
        $_POST['task_status'],
        !empty($_POST['intervention_date']) ? $_POST['intervention_date'] : null,
        $_POST['task_type'],
        $_POST['zone'],
        $_POST['localisation'],
        $_POST['planned_duration']
    ]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_created', "Intervention created: $task_number");
        $message = "✅ " . t('intervention_created') . " " . t('task_number') . ": <strong>$task_number</strong>";
        echo "<script>setTimeout(() => { window.location.href = '?page=interventions'; }, 2000);</script>";
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
        background: #667eea;
        color: white;
        padding: 12px 20px;
        font-weight: bold;
    }
    .task-number-display {
        font-size: 20px;
        font-weight: bold;
        color: #667eea;
        background: #f0f4ff;
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
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .btn-primary {
        background: #667eea;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 600;
    }
    .btn-primary:hover {
        background: #5a67d8;
    }
    .btn-secondary {
        background: #718096;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
    }
    .btn-secondary:hover {
        background: #4a5568;
    }
    .alert-fixed {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>

<div class="container-fluid">
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-fixed" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle text-primary"></i> <?php echo t('new_intervention'); ?></h2>
        <a href="?page=interventions" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> <?php echo t('back_to_list'); ?>
        </a>
    </div>
    
    <form method="POST" id="interventionForm">
        <div class="row">
            <!-- Colonne Gauche -->
            <div class="col-md-6">
                <!-- Section 1 : Identification -->
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="fas fa-tag me-2"></i> <?php echo t('identification'); ?>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('task_number'); ?></label>
                                <div><span class="task-number-display"><?php echo $next_task_number; ?></span></div>
                                <small class="text-muted"><?php echo t('auto_increment'); ?></small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('created_at'); ?></label>
                                <div>
                                    <span class="task-number-display" style="background: #edf2f7; color: #4a5568;">
                                        <?php echo format_date_us(date('Y-m-d H:i:s'), true); ?>
                                    </span>
                                </div>
                                <small class="text-muted"><?php echo t('current_datetime'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section 2 : Équipement et localisation -->
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="fas fa-map-marker-alt me-2"></i> <?php echo t('equipment_and_location'); ?>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                            <select name="equipment_id" id="equipment_id" class="form-select" required>
                                <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                                <?php foreach($equipments as $eq): ?>
                                <option value="<?php echo $eq['id']; ?>" 
                                        data-zone="<?php echo htmlspecialchars($eq['zone'] ?? ''); ?>"
                                        data-location="<?php echo htmlspecialchars($eq['location'] ?? ''); ?>"
                                    <?php echo ($equipment_id_param == $eq['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('zone'); ?></label>
                                <input type="text" name="zone" id="zone" class="form-control" placeholder="<?php echo t('zone_placeholder'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('localisation'); ?></label>
                                <input type="text" name="localisation" id="localisation" class="form-control" placeholder="<?php echo t('localisation_placeholder'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonne Droite -->
            <div class="col-md-6">
                <!-- Section 3 : Description -->
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="fas fa-clipboard-list me-2"></i> <?php echo t('description'); ?>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('title'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="Ex: Remplacement filtre">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('description'); ?></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="<?php echo t('description_placeholder'); ?>"></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Section 4 : Planification et Organisation -->
                <div class="info-card">
                    <div class="card-header-custom">
                        <i class="fas fa-calendar-alt me-2"></i> <?php echo t('planning_and_organisation'); ?>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('planned_date'); ?></label>
                                <input type="date" name="intervention_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
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
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('technician'); ?></label>
                                <select name="intervenant_id" class="form-select">
                                    <option value="">-- <?php echo t('unassigned'); ?> --</option>
                                    <?php foreach($intervenants as $intervenant): ?>
                                    <option value="<?php echo $intervenant['id']; ?>">
                                        <?php echo htmlspecialchars($intervenant['firstname'] . ' ' . $intervenant['lastname']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('status'); ?></label>
                                <select name="task_status" class="form-select">
                                    <option value="a_faire"><?php echo t('to_do'); ?></option>
                                    <option value="en_cours"><?php echo t('in_progress'); ?></option>
                                    <option value="termine"><?php echo t('completed'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5 : Autres paramètres -->
        <div class="info-card">
            <div class="card-header-custom">
                <i class="fas fa-cog me-2"></i> <?php echo t('settings'); ?>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('task_type'); ?></label>
                        <select name="task_type" class="form-select">
                            <option value="revision">📋 <?php echo t('revision'); ?></option>
                            <option value="depannage">🔧 <?php echo t('repair'); ?></option>
                            <option value="installation">📦 <?php echo t('installation'); ?></option>
                            <option value="maintenance_preventive">🔄 <?php echo t('preventive_maintenance_short'); ?></option>
                            <option value="controle">🔍 <?php echo t('inspection'); ?></option>
                            <option value="autre">📌 <?php echo t('other'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('intervention_type'); ?></label>
                        <select name="type" class="form-select">
                            <option value="corrective">⚠️ <?php echo t('corrective'); ?></option>
                            <option value="preventive">📅 <?php echo t('preventive'); ?></option>
                            <option value="emergency">🚨 <?php echo t('emergency'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('planned_duration'); ?></label>
                        <select name="planned_duration" class="form-select">
                            <option value="1h">1h</option><option value="2h">2h</option>
                            <option value="2h30">2h30</option><option value="3h">3h</option>
                            <option value="4h" selected>4h</option><option value="6h">6h</option>
                            <option value="8h">8h</option><option value="1j">1j</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save me-2"></i> <?php echo t('create_intervention'); ?>
            </button>
            <a href="?page=interventions" class="btn btn-secondary btn-lg ms-3 px-5">
                <i class="fas fa-times me-2"></i> <?php echo t('cancel'); ?>
            </a>
        </div>
    </form>
</div>

<script>
// Auto-fill zone and location when equipment is selected
document.getElementById('equipment_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const zone = selectedOption.getAttribute('data-zone') || '';
    const location = selectedOption.getAttribute('data-location') || '';
    
    document.getElementById('zone').value = zone;
    document.getElementById('localisation').value = location;
});

// Trigger change event if equipment is pre-selected
if (document.getElementById('equipment_id').value) {
    document.getElementById('equipment_id').dispatchEvent(new Event('change'));
}
</script>
