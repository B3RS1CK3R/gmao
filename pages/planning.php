<?php
// pages/planning.php - Planning des interventions
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$selected_date = $_GET['date'] ?? date('Y-m-d');
$filter_technician = isset($_GET['technician']) ? intval($_GET['technician']) : null;
$filter_status = $_GET['status'] ?? 'all';

// Récupérer la liste des techniciens pour le filtre
$technicians = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname")->fetchAll();

// Récupérer les interventions de la journée sélectionnée
$sql = "
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location,
           t.id as technician_id, t.firstname, t.lastname, t.specialty
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    WHERE DATE(i.intervention_date) = ?
";
$params = [$selected_date];

if($filter_technician) {
    $sql .= " AND i.intervenant_id = ?";
    $params[] = $filter_technician;
}

if($filter_status != 'all') {
    $sql .= " AND i.task_status = ?";
    $params[] = $filter_status;
}

$sql .= " ORDER BY i.priority = 'critical' DESC, i.priority = 'high' DESC, i.scheduled_time ASC, i.created_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interventions = $stmt->fetchAll();

// Récupérer les statistiques du jour
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN task_status = 'a_faire' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN task_status = 'en_cours' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN task_status = 'termine' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high
    FROM interventions 
    WHERE DATE(intervention_date) = ?
");
$stmt->execute([$selected_date]);
$stats = $stmt->fetch();

// Récupérer les interventions des 7 prochains jours pour le mini calendrier
$week_dates = [];
for($i = -3; $i <= 3; $i++) {
    $date = date('Y-m-d', strtotime($selected_date . ' + ' . $i . ' days'));
    $week_dates[$date] = [
        'date' => $date,
        'count' => 0,
        'day' => date('D', strtotime($date)),
        'day_num' => date('d', strtotime($date)),
        'month' => date('M', strtotime($date))
    ];
}

$stmt = $pdo->prepare("
    SELECT DATE(intervention_date) as int_date, COUNT(*) as count
    FROM interventions 
    WHERE intervention_date BETWEEN DATE_SUB(?, INTERVAL 3 DAY) AND DATE_ADD(?, INTERVAL 3 DAY)
    GROUP BY DATE(intervention_date)
");
$stmt->execute([$selected_date, $selected_date]);
$week_counts = $stmt->fetchAll();

foreach($week_counts as $wc) {
    if(isset($week_dates[$wc['int_date']])) {
        $week_dates[$wc['int_date']]['count'] = $wc['count'];
    }
}
?>

<style>
    .planning-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .planning-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .planning-card-header.warning {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
        margin-bottom: 15px;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-card .number {
        font-size: 28px;
        font-weight: bold;
    }
    .intervention-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.08);
        transition: all 0.2s;
        border-left: 4px solid;
        cursor: pointer;
    }
    .intervention-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .intervention-critical { border-left-color: #dc3545; background: #fff5f5; }
    .intervention-high { border-left-color: #fd7e14; background: #fff8f0; }
    .intervention-medium { border-left-color: #ffc107; background: #fffdf5; }
    .intervention-low { border-left-color: #28a745; background: #f5fff5; }
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
    .status-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    .status-a_faire { background: #6c757d; color: white; }
    .status-en_cours { background: #17a2b8; color: white; }
    .status-termine { background: #28a745; color: white; }
    .status-cloturee { background: #343a40; color: white; }
    .week-day {
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .week-day:hover {
        background: #e9ecef;
    }
    .week-day.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .week-day .count {
        font-size: 18px;
        font-weight: bold;
    }
    .filter-bar {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
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
    .btn-outline-secondary {
        border-radius: 8px;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-calendar-week"></i> <?php echo t('planning_title'); ?></h2>
        <div>
            <a href="?page=intervention_add" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
            </a>
            <a href="?page=calendar" class="btn btn-secondary ms-2">
                <i class="fas fa-calendar-alt"></i> <?php echo t('calendar_view'); ?>
            </a>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="filter-bar">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="btn-group" role="group">
                    <a href="?page=planning&date=<?php echo date('Y-m-d', strtotime($selected_date . ' -1 day')); ?><?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?><?php echo $filter_status != 'all' ? '&status=' . $filter_status : ''; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> <?php echo t('previous_day'); ?>
                    </a>
                    <a href="?page=planning&date=<?php echo date('Y-m-d'); ?><?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?><?php echo $filter_status != 'all' ? '&status=' . $filter_status : ''; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-calendar-day"></i> <?php echo t('today'); ?>
                    </a>
                    <a href="?page=planning&date=<?php echo date('Y-m-d', strtotime($selected_date . ' +1 day')); ?><?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?><?php echo $filter_status != 'all' ? '&status=' . $filter_status : ''; ?>" class="btn btn-outline-secondary">
                        <?php echo t('next_day'); ?> <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <input type="date" id="datePicker" class="form-control" value="<?php echo $selected_date; ?>" style="width: auto; display: inline-block;">
            </div>
            <div class="col-md-2">
                <select id="technicianFilter" class="form-select">
                    <option value=""><?php echo t('all_technicians'); ?></option>
                    <?php foreach($technicians as $tech): ?>
                    <option value="<?php echo $tech['id']; ?>" <?php echo $filter_technician == $tech['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Mini calendrier semaine -->
    <div class="planning-card mb-4">
        <div class="planning-card-header">
            <i class="fas fa-calendar-week"></i> <?php echo t('week_navigation'); ?>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach($week_dates as $date => $info): ?>
                <div class="col text-center">
                    <div class="week-day <?php echo $date == $selected_date ? 'active' : ''; ?>"
                         onclick="window.location.href='?page=planning&date=<?php echo $date; ?><?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?><?php echo $filter_status != 'all' ? '&status=' . $filter_status : ''; ?>'">
                        <div class="small text-uppercase"><?php echo $info['day']; ?></div>
                        <div class="count"><?php echo $info['day_num']; ?></div>
                        <div class="small"><?php echo $info['month']; ?></div>
                        <div class="small mt-1">
                            <?php if($info['count'] > 0): ?>
                                <span class="badge bg-primary"><?php echo $info['count']; ?> int.</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Statistiques du jour -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=planning&date=<?php echo $selected_date; ?><?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?>'">
                <div class="number text-primary"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="text-muted"><?php echo t('total_today'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=planning&date=<?php echo $selected_date; ?>&status=pending<?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?>'">
                <div class="number text-secondary"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="text-muted"><?php echo t('pending'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=planning&date=<?php echo $selected_date; ?>&status=in_progress<?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?>'">
                <div class="number text-info"><?php echo $stats['in_progress'] ?? 0; ?></div>
                <div class="text-muted"><?php echo t('in_progress'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=planning&date=<?php echo $selected_date; ?>&status=completed<?php echo $filter_technician ? '&technician=' . $filter_technician : ''; ?>'">
                <div class="number text-success"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="text-muted"><?php echo t('completed'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Interventions du jour -->
    <div class="planning-card">
        <div class="planning-card-header">
            <i class="fas fa-list"></i> 
            <?php echo t('interventions_for'); ?> <?php echo date('l d F Y', strtotime($selected_date)); ?>
            <?php if($filter_technician): 
                $tech_filter = array_filter($technicians, function($t) use ($filter_technician) { return $t['id'] == $filter_technician; });
                $tech_filter = reset($tech_filter);
            ?>
                <span class="badge bg-light text-dark ms-2"><?php echo t('filtered_by'); ?>: <?php echo htmlspecialchars($tech_filter['firstname'] . ' ' . $tech_filter['lastname']); ?></span>
                <a href="?page=planning&date=<?php echo $selected_date; ?><?php echo $filter_status != 'all' ? '&status=' . $filter_status : ''; ?>" class="btn btn-sm btn-light ms-2">✖ <?php echo t('clear_filters'); ?></a>
            <?php endif; ?>
            <?php if($filter_status != 'all'): ?>
                <span class="badge bg-light text-dark ms-2"><?php echo t('filtered_by_status'); ?>: <?php echo t($filter_status); ?></span>
                <?php if(!$filter_technician): ?>
                <a href="?page=planning&date=<?php echo $selected_date; ?>" class="btn btn-sm btn-light ms-2">✖ <?php echo t('clear_filters'); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="card-body p-3">
            <?php if(empty($interventions)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-calendar-check fa-3x mb-3"></i>
                    <p><?php echo t('no_interventions_planned'); ?></p>
                    <a href="?page=intervention_add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> <?php echo t('plan_intervention'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php 
                $current_time = '';
                foreach($interventions as $inv):
                    $card_class = '';
                    if($inv['priority'] == 'critical') {
                        $card_class = 'intervention-critical';
                    } elseif($inv['priority'] == 'high') {
                        $card_class = 'intervention-high';
                    } elseif($inv['priority'] == 'medium') {
                        $card_class = 'intervention-medium';
                    } else {
                        $card_class = 'intervention-low';
                    }
                    
                    $hour = $inv['scheduled_time'] ? substr($inv['scheduled_time'], 0, 5) : 'non_horaire';
                    if($hour != $current_time && $inv['scheduled_time']):
                        $current_time = $hour;
                ?>
                    <div class="mb-2 mt-3">
                        <span class="badge bg-secondary"><?php echo $hour; ?></span>
                        <hr class="my-2">
                    </div>
                <?php endif; ?>
                <div class="intervention-card <?php echo $card_class; ?>" onclick="window.location.href='?page=intervention_view&id=<?php echo $inv['id']; ?>'">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-microchip fa-fw me-2 text-secondary"></i>
                                <div>
                                    <strong><?php echo htmlspecialchars($inv['equipment_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($inv['equipment_code']); ?></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="fw-bold"><?php echo htmlspecialchars($inv['title']); ?></div>
                            <div class="small text-muted">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($inv['task_number'] ?? 'N/A'); ?>
                                <?php if($inv['localisation']): ?>
                                    • <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($inv['localisation']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <span class="priority-badge priority-<?php echo $inv['priority']; ?>">
                                <?php 
                                if($inv['priority'] == 'critical') echo '🔴 ' . t('critical');
                                elseif($inv['priority'] == 'high') echo '🟠 ' . t('high');
                                elseif($inv['priority'] == 'medium') echo '🟡 ' . t('medium');
                                else echo '🟢 ' . t('low');
                                ?>
                            </span>
                            <span class="status-badge status-<?php echo $inv['task_status']; ?> ms-1">
                                <?php 
                                if($inv['task_status'] == 'a_faire') echo '📋 ' . t('to_do');
                                elseif($inv['task_status'] == 'en_cours') echo '🔧 ' . t('in_progress');
                                elseif($inv['task_status'] == 'termine') echo '✅ ' . t('completed');
                                else echo '🔒 ' . t('closed');
                                ?>
                            </span>
                        </div>
                        <div class="col-md-3">
                            <?php if($inv['firstname']): ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-circle fa-fw me-2 text-info"></i>
                                    <div>
                                        <?php echo htmlspecialchars($inv['firstname'] . ' ' . $inv['lastname']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($inv['specialty']); ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-warning">
                                    <i class="fas fa-user-slash"></i> <?php echo t('unassigned'); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if($inv['description']): ?>
                    <div class="mt-2 small text-muted">
                        <i class="fas fa-align-left"></i> <?php echo htmlspecialchars(substr($inv['description'], 0, 100)); ?>
                        <?php if(strlen($inv['description']) > 100): ?>...<?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Date picker
document.getElementById('datePicker').addEventListener('change', function() {
    let url = '?page=planning&date=' + this.value;
    const technician = document.getElementById('technicianFilter').value;
    const statusFilter = '<?php echo $filter_status; ?>';
    if(technician) {
        url += '&technician=' + technician;
    }
    if(statusFilter && statusFilter !== 'all') {
        url += '&status=' + statusFilter;
    }
    window.location.href = url;
});

// Filtre technicien
document.getElementById('technicianFilter').addEventListener('change', function() {
    let url = '?page=planning&date=<?php echo $selected_date; ?>';
    if(this.value) {
        url += '&technician=' + this.value;
    }
    const statusFilter = '<?php echo $filter_status; ?>';
    if(statusFilter && statusFilter !== 'all') {
        url += '&status=' + statusFilter;
    }
    window.location.href = url;
});

// Rafraîchissement automatique toutes les 60 secondes
setTimeout(function() {
    location.reload();
}, 60000);
</script>