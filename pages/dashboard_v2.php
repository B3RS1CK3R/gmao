<?php
// pages/dashboard_v2.php
$stats = getDashboardStats();
$alerts = getAlerts();

// Récupération des données pour les graphiques
$stmt = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count,
        SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high
    FROM interventions 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$chartData = $stmt->fetchAll();

// Top 5 équipements les plus problématiques
$stmt = $pdo->query("
    SELECT e.name, COUNT(i.id) as nb_interventions
    FROM equipment e
    LEFT JOIN interventions i ON e.id = i.equipment_id
    GROUP BY e.id
    ORDER BY nb_interventions DESC
    LIMIT 5
");
$topIssues = $stmt->fetchAll();

// Répartition des statuts d'interventions
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM interventions 
    GROUP BY status
");
$statusStats = $stmt->fetchAll();
?>

<!-- Intégration de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    .chart-container {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .kpi-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
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
        font-size: 14px;
        opacity: 0.9;
    }
</style>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="btn-group">
            <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-download"></i> Exporter les rapports
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="export/excel.php?type=equipment">
                    <i class="fas fa-microchip"></i> Équipements (CSV)
                </a></li>
                <li><a class="dropdown-item" href="export/excel.php?type=interventions">
                    <i class="fas fa-tools"></i> Interventions (CSV)
                </a></li>
                <li><a class="dropdown-item" href="export/excel.php?type=stock">
                    <i class="fas fa-boxes"></i> Stock (CSV)
                </a></li>
            </ul>
        </div>
    </div>
</div>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-chart-line"></i> Tableau de bord industriel
        <small class="text-muted">Analyse en temps réel</small>
    </h2>
    
    <!-- Alertes -->
    <?php if(count($alerts) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <strong><i class="fas fa-exclamation-triangle"></i> Alertes :</strong>
            <ul>
                <?php foreach($alerts as $alert): ?>
                    <li><?= htmlspecialchars($alert) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="kpi-card">
                <div class="kpi-value"><?= $stats['total_equipment'] ?></div>
                <div class="kpi-label"><i class="fas fa-microchip"></i> Équipements actifs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="kpi-value"><?= $stats['active_interventions'] ?></div>
                <div class="kpi-label"><i class="fas fa-tools"></i> Interventions en cours</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="kpi-value"><?= $stats['completed_interventions'] ?? 0 ?></div>
                <div class="kpi-label"><i class="fas fa-check-circle"></i> Terminées ce mois</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="kpi-value"><?= $stats['avg_intervention_duration'] ?>h</div>
                <div class="kpi-label"><i class="fas fa-clock"></i> Durée moyenne</div>
            </div>
        </div>
    </div>
    
    <!-- Graphiques -->
    <div class="row">
        <div class="col-md-8">
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <div class="col-md-4">
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <h5>Top 5 équipements problématiques</h5>
                <canvas id="topIssuesChart"></canvas>
            </div>
        </div>
        <div class="col-md-6">
            <div class="chart-container">
                <h5>Rapport coût / intervention</h5>
                <canvas id="costChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Graphique d'évolution des interventions
const ctx1 = document.getElementById('trendChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($chartData, 'date')) ?>,
        datasets: [{
            label: 'Total interventions',
            data: <?= json_encode(array_column($chartData, 'count')) ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Critiques',
            data: <?= json_encode(array_column($chartData, 'critical')) ?>,
            borderColor: '#dc3545',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Évolution des interventions (30 jours)'
            }
        }
    }
});

// Graphique des statuts
const ctx2 = document.getElementById('statusChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($statusStats, 'status')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($statusStats, 'count')) ?>,
            backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Répartition des interventions'
            }
        }
    }
});

// Graphique top équipements
const ctx3 = document.getElementById('topIssuesChart').getContext('2d');
new Chart(ctx3, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topIssues, 'name')) ?>,
        datasets: [{
            label: "Nombre d'interventions",
            data: <?= json_encode(array_column($topIssues, 'nb_interventions')) ?>,
            backgroundColor: '#764ba2'
        }]
    },
    options: {
        responsive: true,
        indexAxis: 'y'
    }
});

// Graphique des coûts (simulé)
const ctx4 = document.getElementById('costChart').getContext('2d');
new Chart(ctx4, {
    type: 'line',
    data: {
        labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
        datasets: [{
            label: 'Coût maintenance (€)',
            data: [1250, 1800, 950, 2100, 1450, 1700],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true
        }]
    }
});
</script>