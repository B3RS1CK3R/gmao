<?php
// pages/performance.php - Analyse performance (MTBF/MTTR)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Récupérer tous les équipements
$stmt = $pdo->query("SELECT id, name, code, status FROM equipment WHERE status = 'active' ORDER BY name");
$equipments = $stmt->fetchAll();

// Calculer les performances pour chaque équipement
$equipments_perf = [];
$total_mtbf = 0;
$mtbf_count = 0;
$total_mttr = 0;
$mttr_count = 0;
$total_availability = 0;
$availability_count = 0;
$total_failures = 0;

foreach($equipments as $eq) {
    // Calcul du MTBF
    $stmt = $pdo->prepare("
        SELECT created_at FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
        AND task_status = 'termine'
        ORDER BY created_at ASC
    ");
    $stmt->execute([$eq['id']]);
    $failures = $stmt->fetchAll();
    
    $mtbf = 0;
    if(count($failures) >= 2) {
        $total_interval = 0;
        for($i = 1; $i < count($failures); $i++) {
            $date1 = strtotime($failures[$i-1]['created_at']);
            $date2 = strtotime($failures[$i]['created_at']);
            $interval = ($date2 - $date1) / 3600;
            $total_interval += $interval;
        }
        $mtbf = round($total_interval / (count($failures) - 1), 1);
    }
    
    // Calcul du MTTR
    $stmt = $pdo->prepare("
        SELECT duration_hours FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
        AND task_status = 'termine'
        AND duration_hours IS NOT NULL
    ");
    $stmt->execute([$eq['id']]);
    $repairs = $stmt->fetchAll();
    
    $mttr = 0;
    if(count($repairs) > 0) {
        $total_duration = 0;
        foreach($repairs as $repair) {
            $total_duration += $repair['duration_hours'];
        }
        $mttr = round($total_duration / count($repairs), 1);
    }
    
    // Calcul de la disponibilité (sur 30 jours)
    $stmt = $pdo->prepare("
        SELECT SUM(duration_hours) as total_downtime
        FROM interventions 
        WHERE equipment_id = ? 
        AND task_status = 'termine'
        AND completed_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$eq['id']]);
    $downtime = $stmt->fetch()['total_downtime'] ?? 0;
    
    $total_hours = 30 * 24;
    $availability = 100;
    if($downtime > 0) {
        $availability = round((($total_hours - $downtime) / $total_hours) * 100, 1);
    }
    
    // Nombre de pannes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
    ");
    $stmt->execute([$eq['id']]);
    $failure_count = $stmt->fetchColumn();
    
    $equipments_perf[] = [
        'id' => $eq['id'],
        'name' => $eq['name'],
        'code' => $eq['code'],
        'status' => $eq['status'],
        'mtbf' => $mtbf,
        'mttr' => $mttr,
        'availability' => $availability,
        'failure_count' => $failure_count
    ];
    
    if($mtbf > 0) {
        $total_mtbf += $mtbf;
        $mtbf_count++;
    }
    if($mttr > 0) {
        $total_mttr += $mttr;
        $mttr_count++;
    }
    if($availability > 0) {
        $total_availability += $availability;
        $availability_count++;
    }
    $total_failures += $failure_count;
}

// Moyennes globales
$global_mtbf = $mtbf_count > 0 ? round($total_mtbf / $mtbf_count, 1) : 0;
$global_mttr = $mttr_count > 0 ? round($total_mttr / $mttr_count, 1) : 0;
$global_availability = $availability_count > 0 ? round($total_availability / $availability_count, 1) : 0;

// Pannes par type d'équipement
$stmt = $pdo->query("
    SELECT e.type, COUNT(i.id) as failures_count
    FROM equipment e
    LEFT JOIN interventions i ON e.id = i.equipment_id AND i.type = 'corrective'
    WHERE e.type IS NOT NULL AND e.type != ''
    GROUP BY e.type
    ORDER BY failures_count DESC
    LIMIT 10
");
$failures_by_type = $stmt->fetchAll();

// Évolution MTBF sur 6 mois
$trend_months = [];
$trend_mtbf = [];
for($i = 5; $i >= 0; $i--) {
    $trend_months[] = date('M Y', strtotime("-$i months"));
    
    $start_date = date('Y-m-01', strtotime("-$i months"));
    $end_date = date('Y-m-t', strtotime("-$i months"));
    
    // Calculer le MTBF moyen des équipements pour ce mois
    $all_mtbf = [];
    foreach($equipments as $eq) {
        $stmt = $pdo->prepare("
            SELECT created_at FROM interventions 
            WHERE equipment_id = ? 
            AND type = 'corrective'
            AND created_at BETWEEN ? AND ?
            ORDER BY created_at ASC
        ");
        $stmt->execute([$eq['id'], $start_date, $end_date]);
        $failures = $stmt->fetchAll();
        
        if(count($failures) >= 2) {
            $total_interval = 0;
            for($j = 1; $j < count($failures); $j++) {
                $date1 = strtotime($failures[$j-1]['created_at']);
                $date2 = strtotime($failures[$j]['created_at']);
                $interval = ($date2 - $date1) / 3600;
                $total_interval += $interval;
            }
            $all_mtbf[] = $total_interval / (count($failures) - 1);
        }
    }
    
    $trend_mtbf[] = !empty($all_mtbf) ? round(array_sum($all_mtbf) / count($all_mtbf), 1) : 0;
}

// Top 5 équipements problématiques
usort($equipments_perf, function($a, $b) {
    return $b['failure_count'] - $a['failure_count'];
});
$top_problematic = array_slice($equipments_perf, 0, 5);

// Meilleurs performers
usort($equipments_perf, function($a, $b) {
    return $b['availability'] - $a['availability'];
});
$best_performers = array_slice($equipments_perf, 0, 5);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .kpi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
        transition: transform 0.3s;
    }
    .kpi-card:hover {
        transform: translateY(-5px);
    }
    .kpi-value {
        font-size: 32px;
        font-weight: bold;
    }
    .kpi-label {
        font-size: 13px;
        opacity: 0.9;
        margin-top: 5px;
    }
    .kpi-good {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    .kpi-warning {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    .kpi-danger {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .performance-table {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .performance-table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
    }
    .performance-table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    .performance-table tr:hover {
        background: #f8f9fa;
    }
    .mtbf-good { color: #28a745; font-weight: bold; }
    .mtbf-bad { color: #dc3545; font-weight: bold; }
    .mttr-good { color: #28a745; font-weight: bold; }
    .mttr-bad { color: #dc3545; font-weight: bold; }
    .recommendation-card {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-top: 20px;
        border-left: 4px solid #fd7e14;
    }
    .badge-recommendation {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-critical { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: #333; }
</style>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-chart-line"></i> <?php echo t('performance_analysis'); ?>
        <small class="text-muted"><?php echo t('mtbf_mttr_indicators'); ?></small>
    </h2>
    
    <!-- KPIs globaux -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="kpi-card <?php echo $global_mtbf >= 100 ? 'kpi-good' : ($global_mtbf >= 50 ? 'kpi-warning' : 'kpi-danger'); ?>">
                <div class="kpi-value"><?php echo number_format($global_mtbf, 0); ?> h</div>
                <div class="kpi-label"><i class="fas fa-clock"></i> <?php echo t('mtbf'); ?></div>
                <small><?php echo t('mtbf_description'); ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card <?php echo $global_mttr <= 4 ? 'kpi-good' : ($global_mttr <= 8 ? 'kpi-warning' : 'kpi-danger'); ?>">
                <div class="kpi-value"><?php echo number_format($global_mttr, 1); ?> h</div>
                <div class="kpi-label"><i class="fas fa-wrench"></i> <?php echo t('mttr'); ?></div>
                <small><?php echo t('mttr_description'); ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card <?php echo $global_availability >= 95 ? 'kpi-good' : ($global_availability >= 85 ? 'kpi-warning' : 'kpi-danger'); ?>">
                <div class="kpi-value"><?php echo $global_availability; ?>%</div>
                <div class="kpi-label"><i class="fas fa-chart-simple"></i> <?php echo t('availability'); ?></div>
                <small><?php echo t('availability_description'); ?></small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-value"><?php echo $total_failures; ?></div>
                <div class="kpi-label"><i class="fas fa-exclamation-triangle"></i> <?php echo t('total_failures'); ?></div>
                <small><?php echo t('failures_recorded'); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-12">
            <div class="chart-container">
                <h5><i class="fas fa-chart-line"></i> <?php echo t('mtbf_trend'); ?></h5>
                <canvas id="trendMTBFChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie"></i> <?php echo t('failures_by_type'); ?></h5>
                <canvas id="failuresByTypeChart" height="250"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5><i class="fas fa-trophy"></i> <?php echo t('top_problematic'); ?></h5>
                <canvas id="topFailuresChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top et Flop -->
    <div class="row">
        <div class="col-md-6">
            <div class="performance-table">
                <div style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 12px 15px; font-weight: bold;">
                    <i class="fas fa-star"></i> <?php echo t('best_performers'); ?>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo t('equipment'); ?></th>
                                <th><?php echo t('mtbf'); ?></th>
                                <th><?php echo t('availability'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($best_performers as $eq): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($eq['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($eq['code']); ?></small></td>
                                <td class="mtbf-good"><?php echo $eq['mtbf'] > 0 ? number_format($eq['mtbf'], 0) . ' h' : 'N/A'; ?></td>
                                <td><span class="badge bg-success"><?php echo $eq['availability']; ?>%</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="performance-table">
                <div style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 12px 15px; font-weight: bold;">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo t('critical_equipment'); ?>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?php echo t('equipment'); ?></th>
                                <th><?php echo t('mtbf'); ?></th>
                                <th><?php echo t('failures'); ?></th>
                                <th><?php echo t('status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_problematic as $eq): ?>
                                <?php if($eq['mtbf'] < 50 || $eq['failure_count'] > 3): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($eq['name']); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($eq['code']); ?></small></td>
                                    <td class="mtbf-bad"><?php echo $eq['mtbf'] > 0 ? number_format($eq['mtbf'], 0) . ' h' : 'N/A'; ?></td>
                                    <td><span class="badge bg-danger"><?php echo $eq['failure_count']; ?></span></td>
                                    <td><span class="badge bg-danger"><?php echo t('critical'); ?></span></td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tableau complet des performances -->
    <div class="performance-table mt-4">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 15px; font-weight: bold;">
            <i class="fas fa-table"></i> <?php echo t('detailed_performance'); ?>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?php echo t('equipment'); ?></th>
                        <th><?php echo t('code'); ?></th>
                        <th><?php echo t('mtbf'); ?></th>
                        <th><?php echo t('mttr'); ?></th>
                        <th><?php echo t('availability'); ?></th>
                        <th><?php echo t('failures'); ?></th>
                        <th><?php echo t('recommendation'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($equipments_perf as $eq): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($eq['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($eq['code']); ?></td>
                        <td class="<?php echo $eq['mtbf'] > 100 ? 'mtbf-good' : ($eq['mtbf'] > 50 ? '' : 'mtbf-bad'); ?>">
                            <?php echo $eq['mtbf'] > 0 ? number_format($eq['mtbf'], 0) . ' h' : 'N/A'; ?>
                        </td>
                        <td class="<?php echo $eq['mttr'] < 4 ? 'mttr-good' : ($eq['mttr'] < 8 ? '' : 'mttr-bad'); ?>">
                            <?php echo $eq['mttr'] > 0 ? number_format($eq['mttr'], 1) . ' h' : 'N/A'; ?>
                        </td>
                        <td>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-<?php echo $eq['availability'] >= 95 ? 'success' : ($eq['availability'] >= 85 ? 'warning' : 'danger'); ?>" 
                                     style="width: <?php echo $eq['availability']; ?>%">
                                    <?php echo $eq['availability']; ?>%
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-secondary"><?php echo $eq['failure_count']; ?></span></td>
                        <td>
                            <?php if($eq['mtbf'] < 50): ?>
                                <span class="badge-recommendation badge-critical"><?php echo t('urgent_revision'); ?></span>
                            <?php elseif($eq['mtbf'] < 100): ?>
                                <span class="badge-recommendation badge-warning"><?php echo t('monitoring'); ?></span>
                            <?php else: ?>
                                <span class="badge-recommendation badge-critical" style="background: #28a745;"><?php echo t('good'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Recommandations -->
    <div class="recommendation-card">
        <h5><i class="fas fa-lightbulb"></i> <?php echo t('recommendations'); ?></h5>
        <hr>
        <ul class="mb-0">
            <?php
            $recommendations = [];
            foreach($equipments_perf as $eq) {
                if($eq['mtbf'] < 50) {
                    $recommendations[] = "<li><strong>{$eq['name']}</strong> : " . t('major_overhaul') . "</li>";
                }
                if($eq['mttr'] > 8) {
                    $recommendations[] = "<li><strong>{$eq['name']}</strong> : " . t('training_recommendation') . "</li>";
                }
                if($eq['availability'] < 90) {
                    $recommendations[] = "<li><strong>{$eq['name']}</strong> : " . t('reinforced_preventive') . "</li>";
                }
            }
            if($global_mtbf < 100 && $global_mtbf > 0) {
                $recommendations[] = "<li>🏭 " . t('global_mtbf_low') . "</li>";
            }
            if($global_mttr > 6 && $global_mttr > 0) {
                $recommendations[] = "<li>🔧 " . t('global_mttr_high') . "</li>";
            }
            
            if(empty($recommendations)) {
                echo "<li>✅ " . t('perfect_performance') . "</li>";
            } else {
                echo implode('', $recommendations);
            }
            ?>
        </ul>
    </div>
</div>

<script>
// Graphique d'évolution MTBF
const ctx1 = document.getElementById('trendMTBFChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trend_months); ?>,
        datasets: [{
            label: '<?php echo t('mtbf'); ?> (heures)',
            data: <?php echo json_encode($trend_mtbf); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        }
    }
});

// Graphique des pannes par type
const ctx2 = document.getElementById('failuresByTypeChart').getContext('2d');
const failuresData = <?php echo json_encode($failures_by_type); ?>;
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: failuresData.map(item => item.type || '<?php echo t("unclassified"); ?>'),
        datasets: [{
            data: failuresData.map(item => item.failures_count),
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#fee140', '#17a2b8', '#fd7e14', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

// Graphique top équipements problématiques
const topData = <?php echo json_encode($top_problematic); ?>;
const ctx3 = document.getElementById('topFailuresChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: topData.map(e => e.name),
        datasets: [{
            label: '<?php echo t("failures_count"); ?>',
            data: topData.map(e => e.failure_count),
            backgroundColor: '#dc3545',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: '<?php echo t("failures_count"); ?>'
                }
            }
        }
    }
});
</script>