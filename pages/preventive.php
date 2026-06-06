<?php
// pages/preventive.php - Liste des maintenances préventives
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Redirection vers les pages dédiées
$action = $_GET['action'] ?? 'list';
if ($action == 'add') {
    header('Location: ?page=preventive_add');
    exit();
}
if ($action == 'edit' && isset($_GET['id'])) {
    header('Location: ?page=preventive_edit&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'delete' && isset($_GET['id'])) {
    header('Location: ?page=preventive_delete&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'complete' && isset($_GET['id'])) {
    header('Location: ?page=preventive_complete&id=' . intval($_GET['id']));
    exit();
}

// Récupération des maintenances préventives
$preventives = $pdo->query("
    SELECT pm.*, e.name as equipment_name, e.code as equipment_code
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    ORDER BY 
        CASE WHEN pm.next_due < CURDATE() THEN 0 ELSE 1 END,
        pm.next_due ASC
")->fetchAll();

// Statistiques
$overdue_count = 0;
$upcoming_count = 0;
$ok_count = 0;
foreach($preventives as $p) {
    if(strtotime($p['next_due']) < time()) {
        $overdue_count++;
    } else {
        $days = (strtotime($p['next_due']) - time()) / 86400;
        if($days <= 30) $upcoming_count++;
        else $ok_count++;
    }
}
?>

<style>
    .info-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .card-header-custom { padding: 12px 20px; font-weight: bold; color: white; background: linear-gradient(135deg, #667eea, #764ba2); }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-overdue { background: #dc3545; color: white; }
    .status-upcoming { background: #ffc107; color: #333; }
    .status-ok { background: #28a745; color: white; }
    .action-buttons .btn { padding: 4px 8px; margin: 0 2px; }
    .legend-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; text-align: center; }
    .legend-item { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px; background: #f8f9fa; border-radius: 10px; transition: transform 0.2s; }
    .legend-item:hover { transform: translateY(-2px); background: #e9ecef; }
    @media (max-width: 768px) { .legend-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 480px) { .legend-grid { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-alt"></i> <?php echo t('preventive_maintenance'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=preventive_add" class="btn btn-primary"><i class="fas fa-plus"></i> <?php echo t('plan_maintenance'); ?></a>
        <?php endif; ?>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if(isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['err']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Statistics summary -->
    <div class="row mb-4">
        <div class="col-md-4"><div class="info-card text-center"><div class="card-body"><h3 class="text-danger"><?php echo $overdue_count; ?></h3><p class="text-muted mb-0"><?php echo t('overdue'); ?></p></div></div></div>
        <div class="col-md-4"><div class="info-card text-center"><div class="card-body"><h3 class="text-warning"><?php echo $upcoming_count; ?></h3><p class="text-muted mb-0"><?php echo t('upcoming'); ?> (30 <?php echo t('days_s'); ?>)</p></div></div></div>
        <div class="col-md-4"><div class="info-card text-center"><div class="card-body"><h3 class="text-success"><?php echo $ok_count; ?></h3><p class="text-muted mb-0"><?php echo t('maintenance_ok'); ?></p></div></div></div>
    </div>

    <!-- Preventive maintenance list -->
    <div class="info-card">
        <div class="card-header-custom"><i class="fas fa-list"></i> <?php echo t('preventive_maintenance_list'); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('equipment'); ?></th><th><?php echo t('code'); ?></th><th><?php echo t('frequency'); ?></th>
                            <th><?php echo t('last_done'); ?></th><th><?php echo t('next_due'); ?></th><th><?php echo t('status'); ?></th>
                            <th><?php echo t('team'); ?></th><th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($preventives as $pm): 
                            $days_diff = (strtotime($pm['next_due']) - time()) / 86400;
                            if(strtotime($pm['next_due']) < time()) {
                                $status_class = 'status-overdue';
                                $status_text = '🔴 ' . t('overdue');
                            } elseif($days_diff <= 30) {
                                $status_class = 'status-upcoming';
                                $status_text = '🟡 ' . t('upcoming') . ' (< 30d)';
                            } else {
                                $status_class = 'status-ok';
                                $status_text = '🟢 OK';
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($pm['equipment_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($pm['equipment_code']); ?></td>
                            <td><?php echo t('every'); ?> <?php echo $pm['frequency_days']; ?> <?php echo t('days_s'); ?><br><small class="text-muted">(<?php echo round($pm['frequency_days'] / 30, 1); ?> <?php echo t('month_s'); ?>)</small></td>
                            <td><?php echo $pm['last_done'] ? date('m/d/Y', strtotime($pm['last_done'])) : t('never'); ?></td>
                            <td>
                                <?php echo date('m/d/Y', strtotime($pm['next_due'])); ?>
                                <?php if(strtotime($pm['next_due']) < time()): ?><br><small class="text-danger"><?php echo t('overdue_by'); ?> <?php echo abs(round($days_diff)); ?> <?php echo t('days'); ?></small>
                                <?php elseif($days_diff <= 30): ?><br><small class="text-warning"><?php echo t('in'); ?> <?php echo round($days_diff); ?> <?php echo t('days_s'); ?></small><?php endif; ?>
                            </td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo htmlspecialchars($pm['assigned_team'] ?: t('unassigned')); ?></td>
                            <td class="text-center action-buttons">
                                <a href="?page=preventive_complete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('validate'); ?>"><i class="fas fa-check"></i></a>
                                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                <a href="?page=preventive_edit&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>"><i class="fas fa-edit"></i></a>
                                <a href="?page=preventive_delete&id=<?php echo $pm['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="row mb-4"><div class="col-12"><div class="info-card"><div class="card-header-custom"><i class="fas fa-info-circle"></i> <?php echo t('legend'); ?></div><div class="card-body p-3"><div class="legend-grid"><div class="legend-item"><span class="status-badge status-overdue">🔴 <?php echo t('overdue'); ?></span><small><?php echo t('maintenance_overdue'); ?></small></div><div class="legend-item"><span class="status-badge status-upcoming">🟡 <?php echo t('upcoming'); ?> (< 30d)</span><small><?php echo t('maintenance_upcoming'); ?></small></div><div class="legend-item"><span class="status-badge status-ok">🟢 OK</span><small><?php echo t('maintenance_ok'); ?></small></div></div></div></div></div></div>
</div>