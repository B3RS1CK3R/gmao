<?php
// pages/technician_detail.php - Fiche détaillée d'un technicien
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: index.php?page=technicians');
    exit();
}

// Récupération du technicien
$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
$stmt->execute([$id]);
$technician = $stmt->fetch();

if(!$technician) {
    echo "<div class='alert alert-danger'>" . t('technician_not_found') . "</div>";
    return;
}

// Récupération des interventions assignées
$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.intervenant_id = ?
    ORDER BY i.intervention_date ASC, i.created_at DESC
");
$stmt->execute([$id]);
$interventions = $stmt->fetchAll();

// Statistiques des interventions
$total_interventions = count($interventions);
$completed = 0;
$pending = 0;
$in_progress = 0;
$cancelled = 0;
$total_duration = 0;

foreach($interventions as $inv) {
    if($inv['task_status'] == 'termine') {
        $completed++;
        $total_duration += $inv['duration_hours'];
    } elseif($inv['task_status'] == 'en_cours') {
        $in_progress++;
    } elseif($inv['task_status'] == 'a_faire') {
        $pending++;
    } elseif($inv['task_status'] == 'cloturee') {
        $cancelled++;
    }
}

$avg_duration = $completed > 0 ? round($total_duration / $completed, 1) : 0;
$completion_rate = $total_interventions > 0 ? round(($completed / $total_interventions) * 100, 1) : 0;

// Prochaines interventions (à venir)
$upcoming = array_filter($interventions, function($inv) {
    return $inv['intervention_date'] && strtotime($inv['intervention_date']) >= time() && $inv['task_status'] != 'termine' && $inv['task_status'] != 'cloturee';
});
usort($upcoming, function($a, $b) {
    return strtotime($a['intervention_date']) - strtotime($b['intervention_date']);
});
$upcoming = array_slice($upcoming, 0, 5);

// Récupération de l'historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('technician_created', 'technician_updated', 'technician_deleted', 'technician_restored')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Récupération des compétences du technicien
$stmt = $pdo->prepare("
    SELECT * FROM technician_skills WHERE technician_id = ?
");
$stmt->execute([$id]);
$skills = $stmt->fetchAll();
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .info-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .info-card-header.warning {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
    }
    .info-card-header.success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }
    .info-card-header.info {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active { background: #28a745; color: white; }
    .status-inactive { background: #6c757d; color: white; }
    .status-on_leave { background: #ffc107; color: #333; }
    .priority-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    .priority-critical { background: #dc3545; color: white; }
    .priority-high { background: #fd7e14; color: white; }
    .priority-medium { background: #ffc107; color: #333; }
    .priority-low { background: #28a745; color: white; }
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 10px;
        transition: transform 0.2s;
    }
    .stat-box:hover {
        transform: translateY(-3px);
    }
    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    
    /* ===== NOUVEAU STYLE POUR LES 4 CADRES STATISTIQUES ===== */
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
        margin-bottom: 20px;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
    .stats-label {
        color: #6c757d;
        font-size: 14px;
        margin-top: 5px;
    }
    
    .history-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .skill-tag {
        display: inline-block;
        background: #e9ecef;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin: 3px;
    }
    .skill-level {
        font-size: 10px;
        margin-left: 5px;
    }
    .skill-expert { background: #28a745; color: white; }
    .skill-advanced { background: #17a2b8; color: white; }
    .skill-intermediate { background: #ffc107; color: #333; }
    .skill-beginner { background: #6c757d; color: white; }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
    }
    .btn-primary:hover {
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
    .btn-danger {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .btn-info {
        background: #17a2b8;
        border: none;
        border-radius: 6px;
        padding: 4px 8px;
    }
    .btn-info:hover {
        background: #138496;
    }
    .btn-sm {
        padding: 4px 8px;
        font-size: 12px;
    }
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 10px 12px;
    }
    .table-light th {
        background: #f8f9fa;
        color: #333;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-user-cog"></i> 
        <?php echo t('technician_detail'); ?> : 
        <?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?>
        <small class="text-muted">(<?php echo htmlspecialchars($technician['employee_id']); ?>)</small>
    </h2>
    <div>
        <a href="?page=technicians" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?php echo t('view_technicians'); ?>
        </a>
        <?php if($technician['status'] != 'inactive'): ?>
        <a href="?page=technicians&action=edit&id=<?php echo $technician['id']; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Colonne gauche - Informations personnelles -->
    <div class="col-md-4">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-id-card"></i> <?php echo t('personal_info'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td style="width: 40%;"><strong><?php echo t('employee_id'); ?></strong></td>
                        <td><?php echo htmlspecialchars($technician['employee_id']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('fullname'); ?></strong></td>
                        <td><?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('phone'); ?></strong></td>
                        <td><?php echo htmlspecialchars($technician['phone'] ?: t('not_provided')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('email'); ?></strong></td>
                        <td><?php echo htmlspecialchars($technician['email'] ?: t('not_provided')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('status'); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo $technician['status']; ?>">
                                <?php 
                                if($technician['status'] == 'active') echo '🟢 ' . t('active');
                                elseif($technician['status'] == 'inactive') echo '⚫ ' . t('inactive');
                                else echo '🟡 ' . t('on_leave');
                                ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('hire_date'); ?></strong></td>
                        <td><?php echo $technician['hire_date'] ? format_date_us($technician['hire_date'], false) : t('not_provided'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php echo t('seniority'); ?></strong></td>
                        <td>
                            <?php if($technician['hire_date']): 
                                $hire = new DateTime($technician['hire_date']);
                                $now = new DateTime();
                                $diff = $hire->diff($now);
                                echo $diff->y . ' ' . t('year_s') . ' ' . t('and') . ' ' . $diff->m . ' ' . t('month_s');
                            else: 
                                echo '-';
                            endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Compétences -->
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-tools"></i> <?php echo t('skills'); ?>
            </div>
            <div class="card-body p-4">
                <?php if(empty($skills)): ?>
                    <p class="text-muted text-center mb-0"><?php echo t('no_skills'); ?></p>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-tag skill-<?php echo $skill['skill_level']; ?>">
                                <?php echo htmlspecialchars($skill['equipment_type']); ?>
                                <span class="skill-level">
                                    <?php 
                                    if($skill['skill_level'] == 'expert') echo '🏆 ' . t('expert');
                                    elseif($skill['skill_level'] == 'advanced') echo '📈 ' . t('advanced');
                                    elseif($skill['skill_level'] == 'intermediate') echo '📌 ' . t('intermediate');
                                    else echo '🌱 ' . t('beginner');
                                    ?>
                                </span>
                                <?php if($skill['certified']): ?>
                                    <i class="fas fa-certificate" title="<?php echo t('certified'); ?>"></i>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Historique des modifications -->
        <?php if(!empty($history)): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body p-3">
                <?php foreach($history as $h): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <span>
                            <?php
                            echo t($h['action']);
                            ?>
                        </span>
                        <small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                    </div>
                    <small class="text-muted">
                        <?php echo t('by'); ?> : <?php echo htmlspecialchars($h['username'] ?? t('unknown')); ?> 
                        (IP: <?php echo htmlspecialchars($h['ip_address']); ?>)
                    </small>
                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($h['details']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Colonne droite - Statistiques et interventions -->
    <div class="col-md-8">
        <!-- ===== STATISTIQUES MODIFIÉES (style identique à technicians.php) ===== -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" style="color: #667eea;"><?php echo $total_interventions; ?></div>
                    <div class="stats-label"><?php echo t('total_interventions'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" style="color: #28a745;"><?php echo $completed; ?></div>
                    <div class="stats-label"><?php echo t('completed'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" style="color: #17a2b8;"><?php echo $completion_rate; ?>%</div>
                    <div class="stats-label"><?php echo t('completion_rate'); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-number" style="color: #fd7e14;"><?php echo $avg_duration; ?>h</div>
                    <div class="stats-label"><?php echo t('avg_duration'); ?></div>
                </div>
            </div>
        </div>

        <!-- Interventions en cours -->
        <?php if($in_progress > 0): ?>
        <div class="info-card">
            <div class="info-card-header warning">
                <i class="fas fa-spinner fa-pulse"></i> <?php echo t('interventions'); ?> <?php echo t('in_progress'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('task_number'); ?></th><th><?php echo t('equipment'); ?></th><th><?php echo t('title'); ?></th><th><?php echo t('priority'); ?></th><th><?php echo t('planned_date'); ?></th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($interventions as $inv): ?>
                                <?php if($inv['task_status'] == 'en_cours'): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                    <td><span class="priority-badge priority-<?php echo $inv['priority']; ?>"><?php echo t($inv['priority']); ?></span></td>
                                    <td><?php echo $inv['intervention_date'] ? format_date_us($inv['intervention_date'], false) : '-'; ?></td>
                                    <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Prochaines interventions -->
        <?php if(!empty($upcoming)): ?>
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-calendar-alt"></i> <?php echo t('upcoming_interventions'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th><?php echo t('task_number'); ?></th><th><?php echo t('equipment'); ?></th><th><?php echo t('title'); ?></th><th><?php echo t('priority'); ?></th><th><?php echo t('date'); ?></th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming as $inv): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                                <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                <td><span class="priority-badge priority-<?php echo $inv['priority']; ?>"><?php echo t($inv['priority']); ?></span></td>
                                <td><?php echo format_date_us($inv['intervention_date'], false); ?></td>
                                <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Historique complet des interventions -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> <?php echo t('interventions_history'); ?>
            </div>
            <div class="card-body p-0">
                <?php if(empty($interventions)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p><?php echo t('no_interventions'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('task_number'); ?></th>
                                    <th><?php echo t('equipment'); ?></th>
                                    <th><?php echo t('title'); ?></th>
                                    <th><?php echo t('priority'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('date'); ?></th>
                                    <th><?php echo t('duration'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($interventions as $inv): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title']); ?></td>
                                    <td><span class="priority-badge priority-<?php echo $inv['priority']; ?>"><?php echo t($inv['priority']); ?></span></td>
                                    <td>
                                        <?php
                                        $status_icons = [
                                            'a_faire' => '📋',
                                            'en_cours' => '🔧',
                                            'termine' => '✅',
                                            'cloturee' => '🔒'
                                        ];
                                        echo ($status_icons[$inv['task_status']] ?? '📌') . ' ' . t($inv['task_status']);
                                        ?>
                                    </td>
                                    <td><?php echo $inv['intervention_date'] ? format_date_us($inv['intervention_date'], false) : '-'; ?></td>
                                    <td><?php echo $inv['duration_hours'] ? $inv['duration_hours'] . 'h' : '-'; ?></td>
                                    <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Planning du technicien -->
        <div class="info-card">
            <div class="info-card-header success">
                <i class="fas fa-chart-bar"></i> <?php echo t('weekly_schedule'); ?>
            </div>
            <div class="card-body p-4">
                <?php
                // Calcul du nombre d'interventions par jour de la semaine
                $week_days = [t('monday'), t('tuesday'), t('wednesday'), t('thursday'), t('friday'), t('saturday'), t('sunday')];
                $day_counts = array_fill(0, 7, 0);

                foreach($interventions as $inv) {
                    if($inv['intervention_date'] && strtotime($inv['intervention_date']) >= strtotime('-30 days')) {
                        $day_num = date('N', strtotime($inv['intervention_date'])) - 1;
                        $day_counts[$day_num]++;
                    }
                }
                ?>
                <div class="row">
                    <?php foreach($week_days as $index => $day): ?>
                    <div class="col text-center">
                        <div class="small text-muted"><?php echo substr($day, 0, 3); ?></div>
                        <div class="h4 mb-0 text-primary"><?php echo $day_counts[$index]; ?></div>
                        <small><?php echo t('interv_short'); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <?php if($technician['status'] != 'inactive'): ?>
        <div class="action-buttons">
            <a href="?page=planning&technician=<?php echo $technician['id']; ?>" class="btn btn-primary">
                <i class="fas fa-calendar-alt"></i> <?php echo t('view_planning'); ?>
            </a>
            <button type="button" class="btn btn-danger" onclick="confirmDeactivate()">
                <i class="fas fa-user-slash"></i> <?php echo t('deactivate_technician'); ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmDeactivate() {
    if(confirm('<?php echo t('deactivate_technician_confirm'); ?>')) {
        window.location.href = '?page=technicians&action=delete&id=<?php echo $technician['id']; ?>';
    }
}
</script>