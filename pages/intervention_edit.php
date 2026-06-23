<?php
// pages/intervention_edit.php - Formulaire d'édition d'une intervention
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor', 'technician'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=interventions');
    exit();
}

$stmt = $pdo->prepare("
    SELECT i.*, 
           e.name as equipment_name, 
           e.code as equipment_code,
           t.id as technician_id,
           t.firstname, 
           t.lastname, 
           t.specialty,
           team.name as team_name,
           c.id as contractor_id,
           c.company_name as contractor_name
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    LEFT JOIN technicians t ON i.technician_id = t.id
    LEFT JOIN teams team ON i.team_id = team.id
    LEFT JOIN contractors c ON i.contractor_id = c.id
    WHERE i.id = ?
");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if (!$interv) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Empêcher la modification d'une intervention terminée
if (in_array($interv['task_status'], ['completed', 'closed', 'cancelled'])) {
    echo "<div class='alert alert-warning'>" . t('cannot_edit_completed_intervention') . "</div>";
    echo "<a href='?page=intervention_view&id=$id' class='btn btn-secondary'><i class='fas fa-arrow-left'></i> " . t('back') . "</a>";
    return;
}

$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

$equipments = $pdo->query("SELECT id, code, name, status FROM equipment WHERE status IN ('active', 'maintenance') ORDER BY name")->fetchAll();
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();
$contractors = $pdo->query("SELECT id, company_name, specialty FROM contractors WHERE status = 'active' ORDER BY company_name")->fetchAll();

// Récupérer les équipes
$teams = [];
try {
    $teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    // Ignorer si la table teams n'existe pas
}
?>
<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary:hover {
        background: #5a6268;
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
    .text-muted {
        color: #6c757d !important;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_intervention'); ?> : <?php echo htmlspecialchars($interv['task_number'] ?? '#' . $interv['id']); ?></h2>
        <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_intervention'); ?></div>
        <div class="card-body p-4">
            <form method="POST" action="?page=intervention_edit_action">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('task_number'); ?></label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($interv['task_number'] ?? '#' . $interv['id']); ?>" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="task_status" class="form-select">
                            <option value="a_faire" <?php if($interv['task_status'] == 'a_faire') echo 'selected'; ?>><?php echo t('to_do'); ?></option>
                            <option value="en_cours" <?php if($interv['task_status'] == 'en_cours') echo 'selected'; ?>><?php echo t('in_progress'); ?></option>
                            <option value="termine" <?php if($interv['task_status'] == 'termine') echo 'selected'; ?>><?php echo t('completed'); ?></option>
                            <option value="cloturee" <?php if($interv['task_status'] == 'cloturee') echo 'selected'; ?>><?php echo t('closed'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('equipment'); ?> <span class="text-danger">*</span></label>
                        <select name="equipment_id" class="form-select" required>
                            <option value="">-- <?php echo t('select_equipment'); ?> --</option>
                            <?php foreach ($equipments as $eq): ?>
                                <option value="<?php echo $eq['id']; ?>" <?php if($interv['equipment_id'] == $eq['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($eq['code'] . ' - ' . $eq['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('title'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($interv['title']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('type'); ?></label>
                        <select name="type" class="form-select">
                            <option value="corrective" <?php if($interv['type'] == 'corrective') echo 'selected'; ?>><?php echo t('corrective'); ?></option>
                            <option value="preventive" <?php if($interv['type'] == 'preventive') echo 'selected'; ?>><?php echo t('preventive'); ?></option>
                            <option value="emergency" <?php if($interv['type'] == 'emergency') echo 'selected'; ?>><?php echo t('emergency'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?php echo t('priority'); ?></label>
                        <select name="priority" class="form-select">
                            <option value="low" <?php if($interv['priority'] == 'low') echo 'selected'; ?>><?php echo t('low'); ?></option>
                            <option value="medium" <?php if($interv['priority'] == 'medium') echo 'selected'; ?>><?php echo t('medium'); ?></option>
                            <option value="high" <?php if($interv['priority'] == 'high') echo 'selected'; ?>><?php echo t('high'); ?></option>
                            <option value="critical" <?php if($interv['priority'] == 'critical') echo 'selected'; ?>><?php echo t('critical'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Date et durée -->
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('scheduled_date'); ?></label>
                        <input type="date" name="scheduled_date" class="form-control" value="<?php echo $interv['intervention_date']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
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
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('reported_by'); ?></label>
                        <input type="text" name="reported_by" class="form-control" value="<?php echo htmlspecialchars($interv['reported_by'] ?? ''); ?>">
                    </div>
                    
                    <!-- Description -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('description'); ?></label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($interv['description']); ?></textarea>
                    </div>
                    
                    <!-- ========== SECTION ASSIGNATION (SOUS DESCRIPTION) ========== -->
                    <div class="col-md-12">
                        <div class="assignment-section">
                            <div class="section-title"><i class="fas fa-user-cog"></i> <?php echo t('assign_to'); ?></div>
                            <div class="row">
                                <!-- Colonne 1 : Technicien -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('technician'); ?></label>
                                    <select name="technician_id" id="technicianSelect" class="form-select">
                                        <option value="">-- <?php echo t('select_technician'); ?> --</option>
                                        <?php foreach ($technicians as $tech): ?>
                                            <option value="<?php echo $tech['id']; ?>" <?php if($interv['technician_id'] == $tech['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('can_combine_with_contractor'); ?></small>
                                </div>
                                
                                <!-- Colonne 2 : Équipe -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('team'); ?></label>
                                    <select name="team_id" class="form-select">
                                        <option value="">-- <?php echo t('select_team'); ?> --</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id']; ?>" <?php if(isset($interv['team_id']) && $interv['team_id'] == $team['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($team['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('team_overrides_technician'); ?></small>
                                </div>
                                
                                <!-- Colonne 3 : Prestataire extérieur -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label"><?php echo t('contractor'); ?></label>
                                    <select name="contractor_id" id="contractorSelect" class="form-select">
                                        <option value="">-- <?php echo t('select_contractor'); ?> --</option>
                                        <?php foreach ($contractors as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php if(isset($interv['contractor_id']) && $interv['contractor_id'] == $c['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($c['company_name'] . (isset($c['specialty']) && !empty($c['specialty']) ? ' (' . $c['specialty'] . ')' : '')); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?php echo t('can_combine_with_technician_or_team'); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ========== FIN SECTION ASSIGNATION ========== -->
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const technicianSelect = document.getElementById('technicianSelect');
    const contractorSelect = document.getElementById('contractorSelect');
    const teamSelect = document.querySelector('select[name="team_id"]');

    // Règle : Si une équipe est sélectionnée, le technicien est ignoré
    if (teamSelect) {
        teamSelect.addEventListener('change', function() {
            if (this.value) {
                technicianSelect.value = '';
                technicianSelect.disabled = true;
            } else {
                technicianSelect.disabled = false;
            }
        });
        
        // Initialiser l'état
        if (teamSelect.value) {
            technicianSelect.disabled = true;
        }
    }

    // Technicien et prestataire peuvent être sélectionnés ensemble
    // Aucune exclusion mutuelle entre ces deux champs
});
</script>