<?php
// pages/dashboard.php - Version restaurée à partir de ton ancien code

// ====================== FONCTIONS TEMPORAIRES (obligatoires) ======================
if (!function_exists('getDashboardStats')) {
    function getDashboardStats() {
        return [
            'total_equipment'            => 248,
            'active_interventions'       => 17,
            'avg_intervention_duration'  => 4.8,
            'critical_stock'             => 9
        ];
    }
}

if (!function_exists('getAlerts')) {
    function getAlerts() {
        return [
            "3 équipements en maintenance critique",
            "2 interventions en retard ce jour"
        ];
    }
}

if (!function_exists('getRecentInterventions')) {
    function getRecentInterventions($limit = 5) {
        return [
            [
                'id' => 45,
                'equipment_name' => 'Pressoir Hydraulique P-045',
                'title' => 'Remplacement joint hydraulique',
                'priority' => 'high',
                'status' => 'in_progress',
                'created_at' => '2026-05-22'
            ],
            [
                'id' => 44,
                'equipment_name' => 'Convoyeur CV-12',
                'title' => 'Maintenance préventive mensuelle',
                'priority' => 'medium',
                'status' => 'completed',
                'created_at' => '2026-05-21'
            ]
        ];
    }
}
// =====================================================================

$stats = getDashboardStats();
$alerts = getAlerts();
$recentInterventions = getRecentInterventions(5);
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-tachometer-alt"></i> <?php echo t('dashboard'); ?>
        <small class="text-muted"><?php echo t('view_performance'); ?></small>
    </h2>
    
    <?php if(count($alerts) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <strong><i class="fas fa-exclamation-triangle"></i> <?php echo t('alerts'); ?> :</strong>
            <ul class="mb-0">
                <?php foreach($alerts as $alert): ?>
                    <li><?php echo htmlspecialchars($alert); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Cartes KPI -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary stat-card" onclick="window.location.href='?page=equipment'" style="cursor: pointer;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('equipment'); ?></h5>
                    <h2><?php echo $stats['total_equipment']; ?></h2>
                    <p class="mb-0"><?php echo t('active'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning stat-card" onclick="window.location.href='?page=interventions'" style="cursor: pointer;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('interventions'); ?> <?php echo t('in_progress'); ?></h5>
                    <h2><?php echo $stats['active_interventions']; ?></h2>
                    <p class="mb-0"><?php echo t('to_do'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info stat-card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('avg_duration'); ?></h5>
                    <h2><?php echo $stats['avg_intervention_duration']; ?>h</h2>
                    <p class="mb-0"><?php echo t('per_intervention'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-danger stat-card" onclick="window.location.href='?page=stock'" style="cursor: pointer;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('critical_stock'); ?></h5>
                    <h2><?php echo $stats['critical_stock']; ?></h2>
                    <p class="mb-0"><?php echo t('references'); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Interventions récentes -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-clock"></i> <?php echo t('recent_interventions'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('equipment'); ?></th>
                                    <th><?php echo t('title'); ?></th>
                                    <th><?php echo t('priority'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($recentInterventions)): ?>
                                    <tr class="text-center">
                                        <td colspan="5"><?php echo t('no_interventions'); ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($recentInterventions as $interv): ?>
                                    <tr style="cursor: pointer;" onclick="window.location.href='?page=intervention_view&id=<?php echo $interv['id']; ?>'">
                                        <td><?php echo htmlspecialchars($interv['equipment_name']); ?></td>
                                        <td><?php echo htmlspecialchars($interv['title']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $interv['priority'] == 'critical' ? 'danger' : 
                                                    ($interv['priority'] == 'high' ? 'warning' : 'secondary'); ?>">
                                                <?php echo t($interv['priority']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo t($interv['status']); ?></td>
                                        <td><?php echo date('m/d/Y', strtotime($interv['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions rapides et Analyse avancée -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5><i class="fas fa-bolt"></i> <?php echo t('quick_actions'); ?></h5>
                </div>
                <div class="card-body">
                    <a href="?page=intervention_add" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-plus"></i> <?php echo t('new_intervention'); ?>
                    </a>
                    <a href="?page=equipment&action=add" class="btn btn-info w-100 mb-2">
                        <i class="fas fa-microchip"></i> <?php echo t('add_equipment'); ?>
                    </a>
                    <a href="?page=preventive&action=add" class="btn btn-warning w-100">
                        <i class="fas fa-calendar-plus"></i> <?php echo t('plan_maintenance'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5><i class="fas fa-chart-line"></i> <?php echo t('advanced_analysis'); ?></h5>
                </div>
                <div class="card-body text-center">
                    <a href="?page=performance" class="btn btn-outline-primary">
                        <i class="fas fa-chart-bar"></i> <?php echo t('view_performance'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}
</style>