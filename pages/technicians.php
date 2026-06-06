<?php
// pages/technicians.php - Liste principale des techniciens + affichage des équipes
if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

// Récupération des éventuels messages
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$error = '';

// Filtre de statut
$status_filter = $_GET['status'] ?? 'all';
$technicians = [];

if($_SESSION['role'] == 'admin') {
    if($status_filter == 'all') {
        $technicians = $pdo->query("SELECT * FROM technicians ORDER BY lastname ASC")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE status = ? ORDER BY lastname ASC");
        $stmt->execute([$status_filter]);
        $technicians = $stmt->fetchAll();
    }
} else {
    if($status_filter == 'all' || $status_filter == 'inactive') {
        $technicians = $pdo->query("SELECT * FROM technicians WHERE status != 'inactive' ORDER BY lastname ASC")->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE status = ? AND status != 'inactive' ORDER BY lastname ASC");
        $stmt->execute([$status_filter]);
        $technicians = $stmt->fetchAll();
    }
}

// Nombre d'interventions actives par technicien
$interventions_count = [];
foreach($technicians as $tech) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE technician_id = ? AND task_status NOT IN ('termine', 'cloturee')");
    $stmt->execute([$tech['id']]);
    $interventions_count[$tech['id']] = $stmt->fetchColumn();
}

// Historique des modifications
$history = [];
foreach($technicians as $tech) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('technician_created', 'technician_updated', 'technician_deleted', 'technician_restored')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$tech['id']}%"]);
    $history[$tech['id']] = $stmt->fetchAll();
}

// Statistiques
$active_count = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'active'")->fetchColumn();
$leave_count = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'on_leave'")->fetchColumn();
$inactive_count = $pdo->query("SELECT COUNT(*) FROM technicians WHERE status = 'inactive'")->fetchColumn();

// Liste des équipes
$teams = $pdo->query("
    SELECT t.*, COUNT(tm.technician_id) as member_count, 
           CONCAT(tech.firstname, ' ', tech.lastname) as leader_name
    FROM teams t 
    LEFT JOIN team_members tm ON t.id = tm.team_id
    LEFT JOIN technicians tech ON t.leader_id = tech.id
    GROUP BY t.id
    ORDER BY t.name ASC
")->fetchAll();
?>

<style>
    .info-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .card-header-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; font-weight: bold; }
    .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-active { background: #28a745; color: white; }
    .status-inactive { background: #6c757d; color: white; }
    .status-on_leave { background: #ffc107; color: #333; }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; border-radius: 6px; }
    .legend-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center; }
    .legend-item { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px; background: #f8f9fa; border-radius: 10px; }
    .stats-card { text-align: center; padding: 15px; background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s; cursor: pointer; }
    .stats-card:hover { transform: translateY(-3px); }
    .stats-number { font-size: 28px; font-weight: bold; }
    .history-item { padding: 5px 0; font-size: 10px; border-bottom: 1px solid #eee; }
    .history-item:last-child { border-bottom: none; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> <?php echo t('technicians'); ?></h2>
        <div>
            <a href="?page=technician_add" class="btn btn-primary me-2"><i class="fas fa-plus"></i> <?php echo t('add_technician'); ?></a>
            <a href="?page=team_add" class="btn btn-success"><i class="fas fa-users-rectangle"></i> Créer équipe</a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-4"><div class="stats-card" onclick="location.href='?page=technicians&status=active'"><div class="stats-number text-success"><?php echo $active_count; ?></div><div class="text-muted"><?php echo t('technicians_active'); ?></div></div></div>
        <div class="col-md-4"><div class="stats-card" onclick="location.href='?page=technicians&status=on_leave'"><div class="stats-number text-warning"><?php echo $leave_count; ?></div><div class="text-muted"><?php echo t('on_leave'); ?></div></div></div>
        <div class="col-md-4"><div class="stats-card" onclick="location.href='?page=technicians&status=inactive'"><div class="stats-number text-secondary"><?php echo $inactive_count; ?></div><div class="text-muted"><?php echo t('inactive'); ?></div></div></div>
    </div>

    <!-- Liste des techniciens -->
    <div class="info-card">
        <div class="card-header-custom"><i class="fas fa-list"></i> <?php echo t('technician_list'); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('employee_id'); ?></th><th><?php echo t('lastname'); ?></th><th><?php echo t('firstname'); ?></th>
                            <th><?php echo t('specialty'); ?></th><th><?php echo t('status'); ?></th><th><?php echo t('hire_date'); ?></th>
                            <th><?php echo t('active_interventions'); ?></th><th><?php echo t('last_modifications'); ?></th><th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($technicians as $tech): ?>
                        <tr class="table-row-clickable" onclick="window.location.href='?page=technician_detail&id=<?php echo $tech['id']; ?>'">
                            <td><strong><?php echo htmlspecialchars($tech['employee_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($tech['lastname']); ?></td>
                            <td><?php echo htmlspecialchars($tech['firstname']); ?></td>
                            <td><?php echo htmlspecialchars($tech['specialty'] ?: '-'); ?></td>
                            <td><span class="status-badge status-<?php echo $tech['status']; ?>"><?php if($tech['status'] == 'active') echo '🟢 '.t('active'); elseif($tech['status'] == 'inactive') echo '⚫ '.t('inactive'); else echo '🟡 '.t('on_leave'); ?></span></td>
                            <td><?php echo $tech['hire_date'] ? format_date_us($tech['hire_date'], false) : '-'; ?></td>
                            <td><?php $count = $interventions_count[$tech['id']] ?? 0; if($count > 0) echo '<span class="badge bg-warning text-dark">'.$count.'</span>'; else echo '<span class="text-muted">0</span>'; ?></td>
                            <td style="max-width: 120px;">
                                <?php if(!empty($history[$tech['id']])): ?>
                                    <?php foreach(array_slice($history[$tech['id']], 0, 2) as $h): ?>
                                    <div class="history-item">
                                        <?php $icons = ['technician_created'=>'🟢 '.t('created'), 'technician_updated'=>'✏️ '.t('modified'), 'technician_deleted'=>'🗑️ '.t('deactivated'), 'technician_restored'=>'🔄 '.t('restored')]; echo $icons[$h['action']] ?? $h['action']; ?>
                                        <br><small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?><small class="text-muted">-</small><?php endif; ?>
                            </span>
                            <td class="text-center action-buttons" onclick="event.stopPropagation()">
                                <a href="?page=technician_detail&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="?page=technician_edit&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <?php if($tech['status'] != 'inactive'): ?>
                                    <a href="?page=technician_delete&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                                <?php else: ?>
                                    <a href="?page=technicians_restore&id=<?php echo $tech['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-undo-alt"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Module Équipes -->
    <div class="info-card mt-5">
        <div class="card-header-custom d-flex justify-content-between align-items-center"><span><i class="fas fa-users-rectangle"></i> Équipes</span></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr><th>Nom de l'équipe</th><th>Leader</th><th>Membres</th><th>Description</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($teams as $team): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($team['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($team['leader_name'] ?? 'Aucun'); ?></td>
                            <td><span class="badge bg-primary"><?php echo $team['member_count']; ?></span></td>
                            <td><?php echo htmlspecialchars($team['description'] ?? '-'); ?></td>
                            <td class="text-center">
                                <a href="?page=team_detail&team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="?page=team_delete&team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="row mb-4"><div class="col-12"><div class="info-card"><div class="card-header-custom"><i class="fas fa-info-circle"></i> <?php echo t('legend'); ?></div><div class="card-body p-3"><div class="legend-grid"><div class="legend-item"><span class="status-badge status-active">🟢 <?php echo t('active'); ?></span><small><?php echo t('active_desc'); ?></small></div><div class="legend-item"><span class="status-badge status-on_leave">🟡 <?php echo t('on_leave'); ?></span><small><?php echo t('on_leave_desc'); ?></small></div><div class="legend-item"><span class="status-badge status-inactive">⚫ <?php echo t('inactive'); ?></span><small><?php echo t('inactive_desc'); ?></small></div></div></div></div></div></div>
</div>