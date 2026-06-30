<?php
// pages/team_add.php - Formulaire de création d'équipe avec sélection multiple

if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>Accès refusé</div>";
    return;
}

$error = '';
$message = '';

// Récupérer tous les techniciens actifs
$technicians = $pdo->query("SELECT id, firstname, lastname FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = $_POST['team_name'] ?? '';
    $leader_id = !empty($_POST['leader_id']) ? $_POST['leader_id'] : null;
    $description = $_POST['description'] ?? '';
    $members = $_POST['members'] ?? []; // Tableau des IDs des techniciens sélectionnés
    
    if(empty($team_name)) {
        $error = "❌ Le nom de l'équipe est requis";
    } else {
        try {
            // Créer l'équipe
            $stmt = $pdo->prepare("INSERT INTO teams (name, description, leader_id) VALUES (?, ?, ?)");
            $result = $stmt->execute([$team_name, $description, $leader_id]);
            
            if($result) {
                $team_id = $pdo->lastInsertId();
                
                // Ajouter le leader comme membre si un leader est sélectionné
                if($leader_id) {
                    $stmt2 = $pdo->prepare("INSERT INTO team_members (team_id, technician_id, role) VALUES (?, ?, 'leader')");
                    $stmt2->execute([$team_id, $leader_id]);
                    
                    // Retirer le leader du tableau des membres pour éviter doublon
                    $members = array_filter($members, function($m) use ($leader_id) {
                        return $m != $leader_id;
                    });
                }
                
                // Ajouter les autres membres
                $stmt3 = $pdo->prepare("INSERT INTO team_members (team_id, technician_id, role) VALUES (?, ?, 'member')");
                foreach($members as $member_id) {
                    $stmt3->execute([$team_id, $member_id]);
                }
                
                $message = "✅ Équipe créée avec succès";
                echo "<meta http-equiv='refresh' content='1;url=?page=technicians'>";
            } else {
                $error = "❌ Erreur lors de la création de l'équipe";
            }
        } catch (PDOException $e) {
            $error = "❌ Erreur : " . $e->getMessage();
        }
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
        background: linear-gradient(135deg, #28a745, #1e7e34);
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
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-success:hover {
        filter: brightness(0.95);
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
    .members-list {
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 10px;
    }
    .member-checkbox {
        padding: 8px 10px;
        margin: 0;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }
    .member-checkbox:last-child {
        border-bottom: none;
    }
    .member-checkbox:hover {
        background: #f8f9fa;
    }
    .member-checkbox input {
        margin-right: 10px;
    }
    .info-message {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header">
                    <i class="fas fa-users-rectangle"></i> Créer une nouvelle équipe
                </div>
                <div class="card-body p-4">
                    <div class="info-message">
                        <i class="fas fa-info-circle"></i> 
                        Le leader sélectionné sera automatiquement ajouté comme membre de l'équipe.
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="?page=team_add">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'équipe <span class="text-danger">*</span></label>
                            <input type="text" name="team_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Leader de l'équipe</label>
                            <select name="leader_id" id="leader_id" class="form-select">
                                <option value="">-- Aucun leader --</option>
                                <?php foreach($technicians as $tech): ?>
                                <option value="<?php echo $tech['id']; ?>">
                                    <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Le leader sera automatiquement ajouté aux membres</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Membres de l'équipe</label>
                            <div class="members-list" id="members-list">
                                <?php foreach($technicians as $tech): ?>
                                <div class="member-checkbox" data-tech-id="<?php echo $tech['id']; ?>">
                                    <label>
                                        <input type="checkbox" name="members[]" value="<?php echo $tech['id']; ?>" class="member-check">
                                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">Sélectionnez les techniciens qui feront partie de l'équipe</small>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Créer l'équipe
                            </button>
                            <a href="?page=technicians" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quand le leader est sélectionné, cocher automatiquement sa case dans la liste des membres
document.getElementById('leader_id').addEventListener('change', function() {
    const leaderId = this.value;
    const checkboxes = document.querySelectorAll('.member-check');
    
    checkboxes.forEach(function(checkbox) {
        if(checkbox.value == leaderId) {
            checkbox.checked = true;
            // Désactiver la case pour éviter de décocher le leader
            checkbox.disabled = true;
            // Ajouter une indication visuelle
            const parentDiv = checkbox.closest('.member-checkbox');
            if(parentDiv) {
                parentDiv.style.backgroundColor = '#e8f5e9';
                parentDiv.style.opacity = '0.8';
            }
        } else {
            // Réactiver les autres cases si nécessaire
            if(!checkbox.disabled === false) {
                checkbox.disabled = false;
                const parentDiv = checkbox.closest('.member-checkbox');
                if(parentDiv) {
                    parentDiv.style.backgroundColor = '';
                    parentDiv.style.opacity = '';
                }
            }
        }
    });
});

// Initialisation : si un leader était pré-sélectionné (cas d'édition)
document.addEventListener('DOMContentLoaded', function() {
    const leaderSelect = document.getElementById('leader_id');
    if(leaderSelect && leaderSelect.value) {
        const event = new Event('change');
        leaderSelect.dispatchEvent(event);
    }
});
</script>