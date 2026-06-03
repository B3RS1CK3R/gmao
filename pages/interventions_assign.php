<?php
// pages/interventions_assign.php - Assignation d'une équipe ou d'un technicien
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: ?page=interventions');
    exit();
}

// Récupération de l'intervention
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$id]);
$interv = $stmt->fetch();

if(!$interv) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

// Charger toutes les équipes
$teams = $pdo->query("SELECT id, name FROM teams ORDER BY name")->fetchAll();

// Charger tous les techniciens actifs
$technicians = $pdo->query("
    SELECT id, firstname, lastname, specialty 
    FROM technicians 
    WHERE status = 'active' 
    ORDER BY lastname
")->fetchAll();

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_id = !empty($_POST['team_id']) ? $_POST['team_id'] : null;
    $technician_id = !empty($_POST['technician_id']) ? $_POST['technician_id'] : null;
    
    $stmt = $pdo->prepare("UPDATE interventions SET team_id = ?, technician_id = ? WHERE id = ?");
    $result = $stmt->execute([$team_id, $technician_id, $id]);
    
    if($result) {
        logUserAction($_SESSION['user_id'], 'intervention_assigned', "Team ID: $team_id, Technician ID: $technician_id assigned to ID: {$id}");
        $message = "✅ " . t('assign_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=interventions'>";
    } else {
        $error = "❌ " . t('save_error');
    }
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
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    hr {
        margin: 20px 0;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-user-plus"></i> <?php echo t('assign_team_or_technician'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <p><strong><?php echo t('title'); ?> :</strong> <?php echo htmlspecialchars($interv['title']); ?></p>
                    <p><strong><?php echo t('task_number'); ?> :</strong> <?php echo htmlspecialchars($interv['task_number'] ?? 'N/A'); ?></p>
                    
                    <form method="POST">
                        <!-- Assignation d'une équipe -->
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('assign_team'); ?></label>
                            <select name="team_id" id="team_id" class="form-select">
                                <option value="">-- <?php echo t('no_team'); ?> --</option>
                                <?php foreach($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>" <?php if($interv['team_id'] == $team['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php echo t('team_assign_help'); ?></small>
                        </div>
                        
                        <hr>
                        
                        <!-- Assignation d'un technicien (individuel) -->
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('assign_technician'); ?></label>
                            <select name="technician_id" id="technician_id" class="form-select">
                                <option value="">-- <?php echo t('no_technician'); ?> --</option>
                                <?php foreach($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>" <?php if($interv['technician_id'] == $tech['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname'] . ' (' . $tech['specialty'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted"><?php echo t('technician_assign_help'); ?></small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?php echo t('assign_info'); ?>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-info"><i class="fas fa-save"></i> <?php echo t('assign'); ?></button>
                            <a href="?page=interventions" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>