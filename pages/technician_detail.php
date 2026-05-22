<?php
// pages/technician_detail.php - Fiche détaillée d'un technicien (Version Bilingue)
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

// Récupération des interventions
$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.intervenant_id = ?
    ORDER BY i.intervention_date ASC, i.created_at DESC
");
$stmt->execute([$id]);
$interventions = $stmt->fetchAll();

// Statistiques
$total_interventions = count($interventions);
$completed = 0;
$in_progress = 0;
$total_duration = 0;

foreach($interventions as $inv) {
    if($inv['task_status'] == 'termine') {
        $completed++;
        $total_duration += $inv['duration_hours'] ?? 0;
    } elseif($inv['task_status'] == 'en_cours') {
        $in_progress++;
    }
}

$avg_duration = $completed > 0 ? round($total_duration / $completed, 1) : 0;
$completion_rate = $total_interventions > 0 ? round(($completed / $total_interventions) * 100, 1) : 0;

// Prochaines interventions
$upcoming = array_filter($interventions, function($inv) {
    return $inv['intervention_date'] && strtotime($inv['intervention_date']) >= time() 
           && !in_array($inv['task_status'], ['termine', 'cloturee']);
});
usort($upcoming, fn($a, $b) => strtotime($a['intervention_date']) - strtotime($b['intervention_date']));
$upcoming = array_slice($upcoming, 0, 5);

// Historique des modifications
$stmt = $pdo->prepare("
    SELECT ul.*, u.username 
    FROM user_logs ul
    LEFT JOIN users u ON ul.user_id = u.id
    WHERE ul.action IN ('technician_created', 'technician_updated', 'technician_deleted', 'technician_restored')
    AND ul.details LIKE ?
    ORDER BY ul.created_at DESC
    LIMIT 30
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Compétences
$stmt = $pdo->prepare("SELECT * FROM technician_skills WHERE technician_id = ?");
$stmt->execute([$id]);
$skills = $stmt->fetchAll();
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .info-card-header {
        padding: 15px 20px;
        font-weight: bold;
        color: white;
    }
    .stats-card {
        text-align: center;
        padding: 18px 10px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.2s;
    }
    .stats-card:hover { transform: translateY(-4px); }
    .stats-number { font-size: 26px; font-weight: bold; }
    .skill-tag { display: inline-block; padding: 6px 14px; border-radius: 20px; margin: 4px; font-size: 13px; }
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

<!-- Ligne 1 : Informations + Statistiques -->
<div class="row">
    <!-- Colonne gauche -->
    <div class="col-md-4">
        <!-- Personal Information -->
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-id-card"></i> <?php echo t('personal_info'); ?>
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr><td><strong><?php echo t('employee_id'); ?></strong></td><td><?php echo htmlspecialchars($technician['employee_id']); ?></td></tr>
                    <tr><td><strong><?php echo t('fullname'); ?></strong></td><td><?php echo htmlspecialchars($technician['firstname'] . ' ' . $technician['lastname']); ?></td></tr>
                    <tr><td><strong><?php echo t('phone'); ?></strong></td><td><?php echo htmlspecialchars($technician['phone'] ?: t('not_provided')); ?></td></tr>
                    <tr><td><strong><?php echo t('email'); ?></strong></td><td><?php echo htmlspecialchars($technician['email'] ?: t('not_provided')); ?></td></tr>
                    <tr><td><strong><?php echo t('status'); ?></strong></td>
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
                    <tr><td><strong><?php echo t('hire_date'); ?></strong></td><td><?php echo $technician['hire_date'] ? format_date_us($technician['hire_date'], false) : t('not_provided'); ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Skills -->
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <i class="fas fa-tools"></i> <?php echo t('skills'); ?>
            </div>
            <div class="card-body p-4">
                <?php if(empty($skills)): ?>
                    <p class="text-muted text-center"><?php echo t('no_skills'); ?></p>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-tag bg-light">
                                <?php echo htmlspecialchars($skill['equipment_type']); ?> 
                                <small>(<?php echo t($skill['skill_level']); ?>)</small>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Colonne droite -->
    <div class="col-md-8">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-number text-primary"><?php echo $total_interventions; ?></div>
                    <div><?php echo t('total_interventions'); ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-number text-success"><?php echo $completed; ?></div>
                    <div><?php echo t('completed'); ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-number text-info"><?php echo $completion_rate; ?>%</div>
                    <div><?php echo t('completion_rate'); ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="stats-card">
                    <div class="stats-number text-warning"><?php echo $avg_duration; ?>h</div>
                    <div><?php echo t('avg_duration'); ?></div>
                </div>
            </div>
        </div>

        <!-- Weekly Schedule -->
        <div class="info-card">
            <div class="info-card-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                <i class="fas fa-chart-bar"></i> <?php echo t('weekly_schedule'); ?>
            </div>
            <div class="card-body p-4">
                <?php
                $week_days = [t('monday'), t('tuesday'), t('wednesday'), t('thursday'), t('friday'), t('saturday'), t('sunday')];
                $day_counts = array_fill(0, 7, 0);
                foreach($interventions as $inv) {
                    if($inv['intervention_date'] && strtotime($inv['intervention_date']) >= strtotime('-30 days')) {
                        $day_num = date('N', strtotime($inv['intervention_date'])) - 1;
                        $day_counts[$day_num]++;
                    }
                }
                ?>
                <div class="row text-center">
                    <?php foreach($week_days as $i => $day): ?>
                    <div class="col">
                        <small class="text-muted"><?php echo substr($day, 0, 3); ?></small><br>
                        <strong class="text-primary"><?php echo $day_counts[$i]; ?></strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Interventions -->
<?php if(!empty($upcoming)): ?>
<div class="info-card">
    <div class="info-card-header" style="background: linear-gradient(135deg, #17a2b8, #138496);">
        <i class="fas fa-calendar-alt"></i> <?php echo t('upcoming_interventions'); ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo t('task_number'); ?></th>
                        <th><?php echo t('equipment'); ?></th>
                        <th><?php echo t('title'); ?></th>
                        <th><?php echo t('priority'); ?></th>
                        <th><?php echo t('date'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($upcoming as $inv): ?>
                    <tr onclick="window.location.href='?page=intervention_view&id=<?php echo $inv['id']; ?>'" style="cursor:pointer;">
                        <td><strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong></td>
                        <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                        <td><?php echo htmlspecialchars($inv['title']); ?></td>
                        <td><span class="badge bg-warning"><?php echo t($inv['priority']); ?></span></td>
                        <td><?php echo format_date_us($inv['intervention_date'], false); ?></td>
                        <td><a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info" onclick="event.stopPropagation()"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Interventions History -->
<div class="info-card">
    <div class="info-card-header">
        <i class="fas fa-history"></i> <?php echo t('interventions_history'); ?>
    </div>
    <div class="card-body p-0">
        <?php if(empty($interventions)): ?>
            <p class="text-center text-muted py-5"><?php echo t('no_interventions'); ?></p>
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
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($interventions as $inv): ?>
                        <tr onclick="window.location.href='?page=intervention_view&id=<?php echo $inv['id']; ?>'" style="cursor:pointer;">
                            <td><strong><?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($inv['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($inv['title']); ?></td>
                            <td><span class="badge bg-<?php echo $inv['priority']=='critical'?'danger':'warning'; ?>"><?php echo t($inv['priority']); ?></span></td>
                            <td>
                                <?php
                                $status_icons = [
                                    'a_faire' => '📋 ' . t('to_do'),
                                    'en_cours' => '🔧 ' . t('in_progress'),
                                    'termine' => '✅ ' . t('completed'),
                                    'cloturee' => '🔒 ' . t('closed')
                                ];
                                echo $status_icons[$inv['task_status']] ?? $inv['task_status'];
                                ?>
                            </td>
                            <td><?php echo $inv['intervention_date'] ? format_date_us($inv['intervention_date'], false) : '-'; ?></td>
                            <td><?php echo $inv['duration_hours'] ? $inv['duration_hours'].'h' : '-'; ?></td>
                            <td class="text-center" onclick="event.stopPropagation()">
                                <a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <?php if(!in_array($inv['task_status'], ['termine','cloturee'])): ?>
                                    <a href="?page=interventions&action=complete&id=<?php echo $inv['id']; ?>" 
                                       class="btn btn-sm btn-success" onclick="return confirm('<?php echo t('complete_confirm'); ?>')">
                                        <i class="fas fa-check-circle"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ligne finale : Historique + Skills -->
<div class="row">
    <div class="col-md-7">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> <?php echo t('modifications_history'); ?>
            </div>
            <div class="card-body p-3">
                <?php if(empty($history)): ?>
                    <p class="text-muted text-center py-4"><?php echo t('no_history'); ?></p>
                <?php else: ?>
                    <?php foreach($history as $h): ?>
                    <div class="history-item border-bottom py-2">
                        <strong><?php echo t($h['action']); ?></strong> 
                        <small class="text-muted float-end"><?php echo format_date_us($h['created_at'], true); ?></small>
                        <div class="small text-muted">Par : <?php echo htmlspecialchars($h['username'] ?? 'System'); ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="info-card h-100">
            <div class="info-card-header" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                <i class="fas fa-tools"></i> <?php echo t('skills'); ?>
            </div>
            <div class="card-body p-4">
                <?php if(empty($skills)): ?>
                    <p class="text-muted text-center"><?php echo t('no_skills'); ?></p>
                <?php else: ?>
                    <div class="d-flex flex-wrap">
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-tag bg-light">
                                <?php echo htmlspecialchars($skill['equipment_type']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Boutons d'action -->
<?php if($technician['status'] != 'inactive'): ?>
<div class="mt-4">
    <a href="?page=planning&technician=<?php echo $technician['id']; ?>" class="btn btn-primary">
        <i class="fas fa-calendar-alt"></i> <?php echo t('view_planning'); ?>
    </a>
    <button type="button" class="btn btn-danger" onclick="confirmDeactivate()">
        <i class="fas fa-user-slash"></i> <?php echo t('deactivate_technician'); ?>
    </button>
</div>
<?php endif; ?>

<script>
function confirmDeactivate() {
    if(confirm('<?php echo t('deactivate_technician_confirm'); ?>')) {
        window.location.href = '?page=technicians&action=delete&id=<?php echo $technician['id']; ?>';
    }
}
</script>