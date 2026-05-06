<?php
// pages/alerts.php - Alert Center
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Fetch active alerts
$alerts = [];

// 1. Critical interventions not completed
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    WHERE i.priority = 'critical' 
    AND i.task_status != 'termine' 
    AND i.task_status != 'cloturee'
    ORDER BY i.created_at DESC
");
$criticalInterventions = $stmt->fetchAll();

foreach($criticalInterventions as $inv) {
    $alerts[] = [
        'id' => 'crit_' . $inv['id'],
        'type' => 'critical_intervention',
        'priority' => 'critical',
        'title' => t('critical_intervention'),
        'message' => $inv['title'] . ' - ' . $inv['equipment_name'] . ' (' . $inv['equipment_code'] . ')',
        'details' => t('created_on') . ' : ' . format_date_us($inv['created_at'], true) . '<br>' . nl2br(htmlspecialchars(substr($inv['description'], 0, 200))),
        'url' => '?page=intervention_view&id=' . $inv['id'],
        'date' => $inv['created_at'],
        'status' => $inv['task_status']
    ];
}

// 2. Overdue preventive maintenances
$stmt = $pdo->query("
    SELECT pm.*, e.name as equipment_name, e.code as equipment_code
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    WHERE pm.next_due < CURDATE()
    ORDER BY pm.next_due ASC
");
$overdueMaintenances = $stmt->fetchAll();

foreach($overdueMaintenances as $pm) {
    $days_overdue = (strtotime(date('Y-m-d')) - strtotime($pm['next_due'])) / 86400;
    $alerts[] = [
        'id' => 'prev_' . $pm['id'],
        'type' => 'maintenance_overdue',
        'priority' => 'warning',
        'title' => t('maintenance_overdue'),
        'message' => $pm['equipment_name'] . ' (' . $pm['equipment_code'] . ') - ' . t('delay') . ' ' . round($days_overdue) . ' ' . t('days'),
        'details' => t('instructions') . ' : ' . nl2br(htmlspecialchars($pm['instructions'])) . '<br>' . t('assigned_team') . ' : ' . htmlspecialchars($pm['assigned_team']),
        'url' => '?page=preventive',
        'date' => $pm['next_due'],
        'days_overdue' => $days_overdue
    ];
}

// 3. Upcoming preventive maintenances (less than 7 days)
$stmt = $pdo->query("
    SELECT pm.*, e.name as equipment_name, e.code as equipment_code,
           DATEDIFF(pm.next_due, CURDATE()) as days_left
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    WHERE pm.next_due >= CURDATE() 
    AND pm.next_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY pm.next_due ASC
");
$upcomingMaintenances = $stmt->fetchAll();

foreach($upcomingMaintenances as $pm) {
    $alerts[] = [
        'id' => 'prev_upcoming_' . $pm['id'],
        'type' => 'maintenance_upcoming',
        'priority' => 'info',
        'title' => t('maintenance_upcoming'),
        'message' => $pm['equipment_name'] . ' (' . $pm['equipment_code'] . ') - ' . t('in') . ' ' . $pm['days_left'] . ' ' . t('days'),
        'details' => t('planned_date') . ' : ' . format_date_us($pm['next_due'], false) . '<br>' . t('instructions') . ' : ' . nl2br(htmlspecialchars($pm['instructions'])),
        'url' => '?page=preventive',
        'date' => $pm['next_due'],
        'days_left' => $pm['days_left']
    ];
}

// 4. Critical stock
$stmt = $pdo->query("
    SELECT * FROM spare_parts 
    WHERE quantity <= min_quantity 
    AND quantity >= 0
    ORDER BY (quantity / min_quantity) ASC
");
$lowStock = $stmt->fetchAll();

foreach($lowStock as $part) {
    $percentage = round(($part['quantity'] / $part['min_quantity']) * 100, 1);
    $alerts[] = [
        'id' => 'stock_' . $part['id'],
        'type' => 'stock_critical',
        'priority' => $percentage <= 50 ? 'critical' : 'warning',
        'title' => $percentage <= 50 ? t('critical_stock_title') : t('low_stock_title'),
        'message' => $part['name'] . ' (' . $part['part_number'] . ') - ' . t('stock') . ' : ' . $part['quantity'] . ' / ' . $part['min_quantity'] . ' (' . $percentage . '%)',
        'details' => t('location_stock') . ' : ' . htmlspecialchars($part['location']) . '<br>' . t('supplier') . ' : ' . htmlspecialchars($part['supplier']) . '<br>' . t('unit_price') . ' : ' . number_format($part['unit_price'], 2) . ' €',
        'url' => '?page=stock_detail&id=' . $part['id'],
        'date' => $part['last_restock'],
        'percentage' => $percentage
    ];
}

// 5. Warranties expiring soon
$stmt = $pdo->query("
    SELECT e.*, DATEDIFF(e.warranty_end, CURDATE()) as days_left
    FROM equipment e
    WHERE e.warranty_end IS NOT NULL 
    AND e.warranty_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND e.status != 'retired'
    ORDER BY e.warranty_end ASC
");
$warrantyExpiring = $stmt->fetchAll();

foreach($warrantyExpiring as $eq) {
    if($eq['days_left'] < 0) {
        $alerts[] = [
            'id' => 'warranty_expired_' . $eq['id'],
            'type' => 'warranty_expired',
            'priority' => 'critical',
            'title' => t('warranty_expired'),
            'message' => $eq['name'] . ' (' . $eq['code'] . ') - ' . t('expired_since') . ' ' . abs($eq['days_left']) . ' ' . t('days'),
            'details' => t('purchase_date') . ' : ' . format_date_us($eq['purchase_date'], false) . '<br>' . t('warranty_end') . ' : ' . format_date_us($eq['warranty_end'], false),
            'url' => '?page=equipment_detail&id=' . $eq['id'],
            'date' => $eq['warranty_end'],
            'days_overdue' => abs($eq['days_left'])
        ];
    } else {
        $alerts[] = [
            'id' => 'warranty_upcoming_' . $eq['id'],
            'type' => 'warranty_upcoming',
            'priority' => 'warning',
            'title' => t('warranty_upcoming'),
            'message' => $eq['name'] . ' (' . $eq['code'] . ') - ' . t('expires_in') . ' ' . $eq['days_left'] . ' ' . t('days'),
            'details' => t('purchase_date') . ' : ' . format_date_us($eq['purchase_date'], false) . '<br>' . t('warranty_end') . ' : ' . format_date_us($eq['warranty_end'], false),
            'url' => '?page=equipment_detail&id=' . $eq['id'],
            'date' => $eq['warranty_end'],
            'days_left' => $eq['days_left']
        ];
    }
}

// 6. Unassigned interventions older than 3 days
$stmt = $pdo->query("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code,
           DATEDIFF(NOW(), i.created_at) as days_old
    FROM interventions i
    JOIN equipment e ON i.equipment_id = e.id
    WHERE i.intervenant_id IS NULL 
    AND i.task_status = 'a_faire'
    AND i.created_at <= DATE_SUB(NOW(), INTERVAL 3 DAY)
    ORDER BY i.created_at ASC
");
$unassigned = $stmt->fetchAll();

foreach($unassigned as $inv) {
    $alerts[] = [
        'id' => 'unassigned_' . $inv['id'],
        'type' => 'unassigned_intervention',
        'priority' => 'warning',
        'title' => t('unassigned_intervention'),
        'message' => $inv['title'] . ' - ' . t('waiting_assignment') . ' ' . $inv['days_old'] . ' ' . t('days'),
        'details' => t('equipment') . ' : ' . $inv['equipment_name'] . '<br>' . t('priority') . ' : ' . $inv['priority'],
        'url' => '?page=interventions&action=assign&id=' . $inv['id'],
        'date' => $inv['created_at']
    ];
}

// Sort alerts by date (newest first)
usort($alerts, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Count alerts by priority
$critical_count = count(array_filter($alerts, function($a) { return $a['priority'] == 'critical'; }));
$warning_count = count(array_filter($alerts, function($a) { return $a['priority'] == 'warning'; }));
$info_count = count(array_filter($alerts, function($a) { return $a['priority'] == 'info'; }));
?>

<style>
    .alert-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
        transition: all 0.3s;
    }
    .alert-card-header {
        padding: 15px 20px;
        color: white;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    .alert-card-header.critical { background: linear-gradient(135deg, #dc3545, #c82333); }
    .alert-card-header.warning { background: linear-gradient(135deg, #fd7e14, #e06a0a); }
    .alert-card-header.info { background: linear-gradient(135deg, #17a2b8, #138496); }
    .alert-card-body {
        padding: 20px;
        display: none;
    }
    .alert-card-body.show {
        display: block;
    }
    .alert-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
        cursor: pointer;
    }
    .alert-item:hover {
        background: #f8f9fa;
    }
    .alert-item:last-child {
        border-bottom: none;
    }
    .alert-item.critical { border-left: 4px solid #dc3545; }
    .alert-item.warning { border-left: 4px solid #fd7e14; }
    .alert-item.info { border-left: 4px solid #17a2b8; }
    .alert-priority-badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    .alert-priority-critical { background: #dc3545; color: white; }
    .alert-priority-warning { background: #fd7e14; color: white; }
    .alert-priority-info { background: #17a2b8; color: white; }
    .alert-dismiss-btn {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        transition: color 0.2s;
    }
    .alert-dismiss-btn:hover {
        color: #dc3545;
    }
    .stat-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-right: 10px;
    }
    .btn-clear-all {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 5px 15px;
        font-size: 12px;
    }
    .btn-clear-all:hover {
        background: #5a6268;
        color: white;
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bell"></i> <?php echo t('alert_center'); ?></h2>
        <div>
            <button class="btn-clear-all me-2" onclick="clearAllAlerts()">
                <i class="fas fa-check-double"></i> <?php echo t('mark_all_read'); ?>
            </button>
        </div>
    </div>
    
    <!-- Alerts summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" onclick="filterAlerts('critical')">
                <div class="stats-number text-danger"><?php echo $critical_count; ?></div>
                <div class="text-muted"><?php echo t('critical_alerts'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterAlerts('warning')">
                <div class="stats-number text-warning"><?php echo $warning_count; ?></div>
                <div class="text-muted"><?php echo t('warning_alerts'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterAlerts('info')">
                <div class="stats-number text-info"><?php echo $info_count; ?></div>
                <div class="text-muted"><?php echo t('info_alerts'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="filterAlerts('all')">
                <div class="stats-number"><?php echo count($alerts); ?></div>
                <div class="text-muted"><?php echo t('total_alerts'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Alerts by category -->
    <?php
    $categories = [
        'critical_intervention' => ['title' => t('critical_interventions_title'), 'icon' => 'fas fa-skull-crosswalk', 'color' => 'critical'],
        'maintenance_overdue' => ['title' => t('maintenance_overdue_title'), 'icon' => 'fas fa-calendar-times', 'color' => 'warning'],
        'maintenance_upcoming' => ['title' => t('maintenance_upcoming_title'), 'icon' => 'fas fa-calendar-week', 'color' => 'info'],
        'stock_critical' => ['title' => t('stock_critical_title'), 'icon' => 'fas fa-boxes', 'color' => 'warning'],
        'warranty_expired' => ['title' => t('warranty_expired_title'), 'icon' => 'fas fa-file-contract', 'color' => 'critical'],
        'warranty_upcoming' => ['title' => t('warranty_upcoming_title'), 'icon' => 'fas fa-file-contract', 'color' => 'info'],
        'unassigned_intervention' => ['title' => t('unassigned_intervention_title'), 'icon' => 'fas fa-user-plus', 'color' => 'warning']
    ];
    
    foreach($categories as $type => $cat):
        $type_alerts = array_filter($alerts, function($a) use ($type) {
            return $a['type'] == $type;
        });
        if(empty($type_alerts)) continue;
    ?>
    <div class="alert-card" data-category="<?php echo $type; ?>">
        <div class="alert-card-header <?php echo $cat['color']; ?>" onclick="toggleCard(this)">
            <span><i class="<?php echo $cat['icon']; ?>"></i> <?php echo $cat['title']; ?> <span class="badge bg-light text-dark ms-2"><?php echo count($type_alerts); ?></span></span>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="alert-card-body">
            <?php foreach($type_alerts as $alert): ?>
            <div class="alert-item <?php echo $alert['priority']; ?>" data-priority="<?php echo $alert['priority']; ?>" data-id="<?php echo $alert['id']; ?>" onclick="goToUrl('<?php echo $alert['url']; ?>')">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-bold"><?php echo $alert['title']; ?></div>
                        <div class="small text-muted mt-1"><?php echo $alert['message']; ?></div>
                        <div class="small text-muted mt-1">
                            <i class="fas fa-calendar-alt"></i> <?php echo format_date_us($alert['date'], true); ?>
                        </div>
                        <?php if(isset($alert['details'])): ?>
                        <div class="small text-muted mt-1">
                            <i class="fas fa-info-circle"></i> <?php echo $alert['details']; ?>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($alert['days_left'])): ?>
                        <div class="small text-warning mt-1">
                            <i class="fas fa-hourglass-half"></i> <?php echo t('expires_in'); ?> <?php echo $alert['days_left']; ?> <?php echo t('days'); ?>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($alert['days_overdue'])): ?>
                        <div class="small text-danger mt-1">
                            <i class="fas fa-exclamation-circle"></i> <?php echo t('overdue_by'); ?> <?php echo round($alert['days_overdue']); ?> <?php echo t('days'); ?>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($alert['percentage'])): ?>
                        <div class="small text-danger mt-1">
                            <i class="fas fa-chart-line"></i> <?php echo t('stock_at'); ?> <?php echo $alert['percentage']; ?>%
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <span class="alert-priority-badge alert-priority-<?php echo $alert['priority']; ?>">
                            <?php 
                            if($alert['priority'] == 'critical') echo t('critical');
                            elseif($alert['priority'] == 'warning') echo t('warning');
                            else echo t('info');
                            ?>
                        </span>
                        <button class="alert-dismiss-btn ms-2" onclick="dismissAlert(event, '<?php echo $alert['id']; ?>')" title="<?php echo t('mark_as_read'); ?>">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if(empty($alerts)): ?>
    <div class="alert-card">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h4><?php echo t('excellent'); ?></h4>
            <p class="text-muted"><?php echo t('no_alerts'); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Store dismissed alerts for session only
let dismissedAlerts = [];

// Update alerts display
function updateDisplayedAlerts() {
    const allAlerts = document.querySelectorAll('.alert-item');
    allAlerts.forEach(alert => {
        const alertId = alert.getAttribute('data-id');
        if (dismissedAlerts.includes(alertId)) {
            alert.style.display = 'none';
        } else {
            alert.style.display = '';
        }
    });
    updateCounters();
}

// Mark an alert as read
function dismissAlert(event, alertId) {
    event.stopPropagation();
    if (!dismissedAlerts.includes(alertId)) {
        dismissedAlerts.push(alertId);
    }
    const alertElement = document.querySelector(`.alert-item[data-id="${alertId}"]`);
    if(alertElement) {
        alertElement.style.display = 'none';
        updateCounters();
    }
}

// Mark all as read
function clearAllAlerts() {
    if(confirm('<?php echo t('mark_all_read_confirm'); ?>')) {
        const allAlerts = document.querySelectorAll('.alert-item');
        allAlerts.forEach(alert => {
            const alertId = alert.getAttribute('data-id');
            if(alertId && !dismissedAlerts.includes(alertId)) {
                dismissedAlerts.push(alertId);
                alert.style.display = 'none';
            }
        });
        updateCounters();
        
        const msgDiv = document.createElement('div');
        msgDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
        msgDiv.innerHTML = '<i class="fas fa-info-circle"></i> <?php echo t('alerts_hidden_session'); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.querySelector('.container-fluid').insertBefore(msgDiv, document.querySelector('.container-fluid').firstChild);
        setTimeout(() => { msgDiv.remove(); }, 5000);
    }
}

// Update counters
function updateCounters() {
    const visibleAlerts = document.querySelectorAll('.alert-item:not([style*="display: none"])');
    const total = visibleAlerts.length;
    
    const badge = document.querySelector('.notification-badge .badge-count');
    if(badge) {
        if(total > 0) {
            badge.textContent = total;
            badge.style.display = 'inline-block';
            document.title = '(' + total + ') GMAO Pro';
        } else {
            badge.style.display = 'none';
            document.title = 'GMAO Pro';
        }
    }
}

// Filter alerts by priority
function filterAlerts(priority) {
    const allAlerts = document.querySelectorAll('.alert-item');
    allAlerts.forEach(alert => {
        if(priority === 'all') {
            if(!dismissedAlerts.includes(alert.getAttribute('data-id'))) {
                alert.style.display = '';
            }
        } else {
            if(alert.getAttribute('data-priority') === priority && !dismissedAlerts.includes(alert.getAttribute('data-id'))) {
                alert.style.display = '';
            } else {
                alert.style.display = 'none';
            }
        }
    });
}

// Open URL
function goToUrl(url) {
    window.location.href = url;
}

// Toggle card
function toggleCard(header) {
    const body = header.nextElementSibling;
    const icon = header.querySelector('.fa-chevron-down, .fa-chevron-up');
    if(body.classList.contains('show')) {
        body.classList.remove('show');
        if(icon) icon.classList.remove('fa-chevron-up');
        if(icon) icon.classList.add('fa-chevron-down');
    } else {
        body.classList.add('show');
        if(icon) icon.classList.remove('fa-chevron-down');
        if(icon) icon.classList.add('fa-chevron-up');
    }
}

// Initialization
document.addEventListener('DOMContentLoaded', function() {
    dismissedAlerts = [];
});
</script>