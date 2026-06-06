<?php
// pages/team_detail.php - Détail d'une équipe
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
if(!$team_id) {
    header('Location: ?page=technicians');
    exit();
}

// Récupérer les infos de l'équipe
$stmt = $pdo->prepare("
    SELECT t.*, 
            CONCAT(leader.firstname, ' ', leader.lastname) as leader_name,
            leader.id as leader_id
    FROM teams t
    LEFT JOIN technicians leader ON t.leader_id = leader.id
    WHERE t.id = ?
");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if(!$team) {
    echo "<div class='alert alert-danger'>Équipe non trouvée.</div>";
    return;
}

// Récupérer les membres
$stmt = $pdo->prepare("
    SELECT tm.*, tech.firstname, tech.lastname, tech.specialty
    FROM team_members tm
    JOIN technicians tech ON tm.technician_id = tech.id
    WHERE tm.team_id = ?
    ORDER BY tm.role DESC, tech.lastname ASC
");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll();
?>

<style>
    .info-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .card-header-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; font-weight: bold; }
    .member-list { list-style: none; padding: 0; }
    .member-list li { padding: 8px 0; border-bottom: 1px solid #eee; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users-rectangle"></i> Équipe : <?php echo htmlspecialchars($team['name']); ?></h2>
        <div>
            <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
            <a href="?page=team_delete&team_id=<?php echo $team['id']; ?>" class="btn btn-danger ms-2"><i class="fas fa-trash"></i> Supprimer l'équipe</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="info-card">
                <div class="card-header-custom"><i class="fas fa-info-circle"></i> Informations générales</div>
                <div class="card-body p-4">
                    <table class="table table-sm table-borderless">
                        <tr><td><strong>Nom :</strong></td><td><?php echo htmlspecialchars($team['name']); ?></td></tr>
                        <tr><td><strong>Leader :</strong></td><td><?php echo $team['leader_name'] ? htmlspecialchars($team['leader_name']) : 'Aucun'; ?></td></tr>
                        <tr><td><strong>Description :</strong></td><td><?php echo nl2br(htmlspecialchars($team['description'] ?: '-')); ?></td></tr>
                        <tr><td><strong>Date de création :</strong></td><td><?php echo format_date_local($team['created_at'], 'long', true); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="info-card">
                <div class="card-header-custom"><i class="fas fa-users"></i> Membres (<?php echo count($members); ?>)</div>
                <div class="card-body p-4">
                    <?php if(empty($members)): ?>
                        <p class="text-muted">Aucun membre.</p>
                    <?php else: ?>
                        <ul class="member-list">
                            <?php foreach($members as $m): ?>
                            <li>
                                <?php if($m['role'] == 'leader'): ?>
                                    <i class="fas fa-crown text-warning"></i> 
                                <?php else: ?>
                                    <i class="fas fa-user"></i> 
                                <?php endif; ?>
                                <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                <span class="text-muted">(<?php echo htmlspecialchars($m['specialty']); ?>)</span>
                                <?php if($m['role'] == 'leader'): ?>
                                    <span class="badge bg-warning ms-2">Leader</span>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>