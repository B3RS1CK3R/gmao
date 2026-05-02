<?php
// pages/assign_intervention.php - Assignation d'une intervention à un technicien
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$intervention_id = $_GET['id'] ?? 0;

// Récupérer l'intervention
$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.id = ?
");
$stmt->execute([$intervention_id]);
$intervention = $stmt->fetch();

if(!$intervention) {
    echo "<div class='alert alert-danger'>" . t('intervention_not_found') . "</div>";
    return;
}

// Récupérer les techniciens actifs
$technicians = $pdo->query("
    SELECT id, firstname, lastname, specialty, status 
    FROM technicians 
    WHERE status = 'active' 
    ORDER BY lastname ASC
")->fetchAll();

// Récupérer les interventions déjà assignées au technicien pour la même date
$scheduled_date = $intervention['intervention_date'];
$interventions_taken = [];
if($scheduled_date) {
    $stmt = $pdo->prepare("
        SELECT intervenant_id, COUNT(*) as count 
        FROM interventions 
        WHERE intervention_date = ? 
        AND task_status NOT IN ('termine', 'cloturee')
        GROUP BY intervenant_id
    ");
    $stmt->execute([$scheduled_date]);
    $taken = $stmt->fetchAll();
    foreach($taken as $t) {
        $interventions_taken[$t['intervenant_id']] = $t['count'];
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $technician_id = $_POST['technician_id'];
    $scheduled_date = $_POST['scheduled_date'];
    $scheduled_time = $_POST['scheduled_time'];
    
    $stmt = $pdo->prepare("
        UPDATE interventions 
        SET intervenant_id = ?, intervention_date = ?, scheduled_time = ?, task_status = 'a_faire'
        WHERE id = ?
    ");
    $result = $stmt->execute([$technician_id, $scheduled_date, $scheduled_time, $intervention_id]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_assigned', "Technicien assigné à ID: $intervention_id");
        echo "<div class='alert alert-success'>✅ " . t('technician_assigned') . "</div>";
        echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
    } else {
        echo "<div class='alert alert-danger'>❌ " . t('save_error') . "</div>";
    }
}
?>

<style>
    .assign-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .assign-card-header {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .info-row {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .badge-priority {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-critical { background: #dc3545; color: white; }
    .badge-high { background: #fd7e14; color: white; }
    .badge-medium { background: #ffc107; color: #333; }
    .badge-low { background: #28a745; color: white; }
    .technician-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        transition: all 0.2s;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .technician-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-color: #17a2b8;
    }
    .technician-card.selected {
        border-color: #28a745;
        background: #f0fff0;
    }
    .tech-name { font-weight: bold; font-size: 16px; }
    .tech-specialty { font-size: 12px; color: #666; margin-top: 4px; }
    .tech-stats { font-size: 11px; color: #999; margin-top: 8px; display: flex; gap: 15px; }
    .workload-badge { background: #ffc107; color: #333; padding: 2px 8px; border-radius: 20px; font-size: 10px; }
    .btn-assign { background: linear-gradient(135deg, #28a745, #1e7e34); border: none; border-radius: 8px; padding: 10px 25px; color: white; font-weight: bold; }
    .btn-assign:hover { filter: brightness(0.95); color: white; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-plus"></i> <?php echo t('assign_technician'); ?></h2>
        <a href="?page=interventions" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
        </a>
    </div>
    
    <div class="row">
        <div class="col-md-5">
            <div class="assign-card">
                <div class="assign-card-header">
                    <i class="fas fa-info-circle"></i> <?php echo t('intervention_details'); ?>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <strong><?php echo t('task_number'); ?> :</strong> <?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('title'); ?> :</strong> <?php echo htmlspecialchars($intervention['title']); ?>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('equipment'); ?> :</strong> <?php echo htmlspecialchars($intervention['equipment_name']); ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($intervention['equipment_code']); ?></small>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('priority'); ?> :</strong>
                        <span class="badge-priority badge-<?php echo $intervention['priority']; ?>">
                            <?php echo t($intervention['priority']); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <strong><?php echo t('description'); ?> :</strong><br>
                        <small><?php echo nl2br(htmlspecialchars($intervention['description'] ?: t('no_description'))); ?></small>
                    </div>
                    <?php if($intervention['zone'] || $intervention['localisation']): ?>
                    <div class="info-row">
                        <strong><?php echo t('location'); ?> :</strong><br>
                        <?php 
                        $loc = [];
                        if($intervention['zone']) $loc[] = $intervention['zone'];
                        if($intervention['localisation']) $loc[] = $intervention['localisation'];
                        echo htmlspecialchars(implode(' / ', $loc));
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="assign-card">
                <div class="assign-card-header">
                    <i class="fas fa-users"></i> <?php echo t('select_technician'); ?>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="assignForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label"><?php echo t('planned_date'); ?> *</label>
                                <input type="date" name="scheduled_date" id="scheduled_date" class="form-control" 
                                       value="<?php echo $intervention['intervention_date'] ?: date('Y-m-d', strtotime('+7 days')); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><?php echo t('scheduled_time'); ?></label>
                                <input type="time" name="scheduled_time" id="scheduled_time" class="form-control" 
                                       value="<?php echo $intervention['scheduled_time'] ?: '09:00'; ?>">
                            </div>
                        </div>
                        
                        <div id="techniciansList">
                            <?php foreach($technicians as $tech): 
                                $workload = $interventions_taken[$tech['id']] ?? 0;
                                $workload_class = $workload >= 3 ? 'workload-badge' : '';
                            ?>
                            <div class="technician-card" data-id="<?php echo $tech['id']; ?>" onclick="selectTechnician(<?php echo $tech['id']; ?>)">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="tech-name"><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></div>
                                        <div class="tech-specialty"><i class="fas fa-tools"></i> <?php echo htmlspecialchars($tech['specialty'] ?: t('no_specialty')); ?></div>
                                        <div class="tech-stats">
                                            <span><i class="fas fa-calendar"></i> <?php echo $workload; ?> <?php echo t('interventions_today'); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="technician_id" id="tech_<?php echo $tech['id']; ?>" value="<?php echo $tech['id']; ?>" style="transform: scale(1.2);">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn-assign w-100">
                                <i class="fas fa-user-check"></i> <?php echo t('confirm_assignment'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectTechnician(id) {
    document.querySelectorAll('.technician-card').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelector(`.technician-card[data-id="${id}"]`).classList.add('selected');
    document.getElementById(`tech_${id}`).checked = true;
}

document.getElementById('assignForm').addEventListener('submit', function(e) {
    const selected = document.querySelector('input[name="technician_id"]:checked');
    if(!selected) {
        e.preventDefault();
        alert('<?php echo t('select_technician_warning'); ?>');
    }
});
</script>