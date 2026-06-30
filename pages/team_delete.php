<?php
// pages/team_delete.php - Suppression d'une équipe (avec confirmation mot de passe)
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
if(!$team_id) {
    header('Location: ?page=technicians');
    exit();
}

// Récupérer le nom de l'équipe
$stmt = $pdo->prepare("SELECT name FROM teams WHERE id = ?");
$stmt->execute([$team_id]);
$team = $stmt->fetch();
if(!$team) {
    echo "<div class='alert alert-danger'>Équipe non trouvée.</div>";
    return;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_password'])) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if(password_verify($_POST['confirm_password'], $user['password'])) {
        // Supprimer d'abord les membres (liens), puis l'équipe
        $pdo->prepare("DELETE FROM team_members WHERE team_id = ?")->execute([$team_id]);
        $pdo->prepare("DELETE FROM teams WHERE id = ?")->execute([$team_id]);
        logUserAction($_SESSION['user_id'], 'team_deleted', "Team ID: {$team_id} deleted");
        header('Location: ?page=technicians&msg=' . urlencode(t('team_deleted')));
        exit();
    } else {
        $error = t('password_error');
    }
}
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 15px 20px; font-weight: bold; }
    .btn-danger { background: #dc3545; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="form-card">
                <div class="form-card-header"><i class="fas fa-trash-alt"></i> Supprimer l'équipe</div>
                <div class="card-body p-4">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <?php echo t('delete_confirm'); ?> : <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                    </div>
                    <p>Cette action supprimera définitivement l'équipe et tous ses membres (les techniciens restent inchangés).</p>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><?php echo t('confirm_password'); ?></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> <?php echo t('confirm'); ?></button>
                            <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>