<?php
// pages/dashboard_advanced.php - Dashboard avec MTBF/MTTR
$stats = getDashboardStats();
$alerts = getAlerts();
$global_indicators = getGlobalPerformanceIndicators();
$equipments_perf = getAllEquipmentPerformance();

// Récupérer les 5 derniers mois pour le graphique de tendance
$trend_months = [];
$trend_mtbf = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $trend_months[] = date('M Y', strtotime("-$i months"));
    
    $stmt = $pdo->prepare("
        SELECT AVG(mtbf) as avg_mtbf 
        FROM performance_metrics 
        WHERE DATE_FORMAT(date_recorded, '%Y-%m') = ?
    ");
    $stmt->execute([$month]);
    $val = $stmt->fetch()['avg_mtbf'];
    $trend_mtbf[] = $val ? round($val, 1) : 0;
}
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
        font-size: 36px;
        font-weight: bold;
    }
    .kpi-label {
        font-size: 14px;
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
    .table-performance {
        font-size: 14px;
    }
    .table-performance th {
        background: #f8f9fa;
    }
    .mtbf-good { color: #28a745; font-weight: bold; }
    .mtbf-bad { color: #dc3545; font-weight: bold; }
    .mttr-good { color: #28a745; font-weight: bold; }
    .mttr-bad { color: #dc3545; font-weight: bold; }
</style>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-chart-line"></i> Tableau de bord avancé
        <small class="text-muted">Indicateurs de performance MTBF / MTTR</small>
    </h2>
    
    <!-- Alertes -->
    <?php if(count($alerts) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <strong><i class="fas fa-exclamation-triangle"></i> Alertes :</strong>
            <ul class="mb-0">
                <?php foreach($alerts as $alert): ?>
                    <li><?= htmlspecialchars($alert) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- KPIs principaux -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-value"><?= $stats['total_equipment'] ?></div>
                <div class="kpi-label"><i class="fas fa-microchip"></i> Équipements actifs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card <?= $global_indicators['global_mtbf'] > 100 ? 'kpi-good' : 'kpi-warning' ?>">
                <div class="kpi-value"><?= number_format($global_indicators['global_mtbf'], 0) ?> h</div>
                <div class="kpi-label"><i class="fas fa-clock"></i> MTBF Global</div>
                <small>Temps moyen entre pannes</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card <?= $global_indicators['global_mttr'] < 4 ? 'kpi-good' : 'kpi-warning' ?>">
                <div class="kpi-value"><?= number_format($global_indicators['global_mttr'], 1) ?> h</div>
                <div class="kpi-label"><i class="fas fa-wrench"></i> MTTR Global</div>
                <small>Temps moyen de réparation</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card kpi-danger">
                <div class="kpi-value"><?= $stats['active_interventions'] ?></div>
                <div class="kpi-label"><i class="fas fa-tools"></i> Interventions en cours</div>
            </div>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-8">
            <div class="chart-container">
                <h5><i class="fas fa-chart-line"></i> Évolution du MTBF (6 derniers mois)</h5>
                <canvas id="trendMTBFChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie"></i> Pannes par type d'équipement</h5>
                <canvas id="failuresByTypeChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Classement des équipements -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-trophy"></i> Performance des équipements</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-performance mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Équipement</th>
                                    <th>Code</th>
                                    <th>MTBF (heures)</th>
                                    <th>MTTR (heures)</th>
                                    <th>Disponibilité</th>
                                    <th>Nb pannes</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($equipments_perf as $eq): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($eq['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($eq['code']) ?></td>
                                    <td class="<?= $eq['mtbf'] > 100 ? 'mtbf-good' : 'mtbf-bad' ?>">
                                        <?= $eq['mtbf'] > 0 ? number_format($eq['mtbf'], 0) . ' h' : 'N/A' ?>
                                    </td>
                                    <td class="<?= $eq['mttr'] < 4 ? 'mttr-good' : 'mttr-bad' ?>">
                                        <?= $eq['mttr'] > 0 ? number_format($eq['mttr'], 1) . ' h' : 'N/A' ?>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?= $eq['availability'] >= 95 ? 'success' : ($eq['availability'] >= 85 ? 'warning' : 'danger') ?>" 
                                                 style="width: <?= $eq['availability'] ?>%">
                                                <?= $eq['availability'] ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $eq['failure_count'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $eq['mtbf'] > 100 ? 'success' : ($eq['mtbf'] > 50 ? 'warning' : 'danger') ?>">
                                            <?= $eq['mtbf'] > 100 ? 'Bon' : ($eq['mtbf'] > 50 ? 'Moyen' : 'Critique') ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section recommandations -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-lightbulb"></i> Recommandations</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php
                        $recommendations = [];
                        foreach($equipments_perf as $eq) {
                            if($eq['mtbf'] < 50) {
                                $recommendations[] = "<li>⚠️ <strong>{$eq['name']}</strong> : MTBF très bas ({$eq['mtbf']}h). Prévoyez un remplacement ou une révision majeure.</li>";
                            }
                            if($eq['mttr'] > 8) {
                                $recommendations[] = "<li>🔧 <strong>{$eq['name']}</strong> : Temps de réparation long ({$eq['mttr']}h). Formez davantage les techniciens ou préparez des pièces de rechange.</li>";
                            }
                            if($eq['availability'] < 90) {
                                $recommendations[] = "<li>📉 <strong>{$eq['name']}</strong> : Disponibilité faible ({$eq['availability']}%). Programmez une maintenance préventive renforcée.</li>";
                            }
                        }
                        if(empty($recommendations)) {
                            echo "<li>✅ Tous les équipements ont de bonnes performances. Continuez ainsi !</li>";
                        } else {
                            echo implode('', $recommendations);
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Graphique d'évolution MTBF
const ctx1 = document.getElementById('trendMTBFChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_months) ?>,
        datasets: [{
            label: 'MTBF (heures)',
            data: <?= json_encode($trend_mtbf) ?>,
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
const failuresData = <?= json_encode($global_indicators['failures_by_type']) ?>;
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: failuresData.map(item => item.type || 'Non classé'),
        datasets: [{
            data: failuresData.map(item => item.failures_count),
            backgroundColor: ['#667eea', '#764ba2', '#f093fb', '#4facfe', '#43e97b', '#fa709a']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>