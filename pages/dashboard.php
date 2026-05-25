<?php
// pages/dashboard.php - Version dynamique finale

// Chemin absolu fiable
require_once __DIR__ . '/../includes/functions.php';

$stats = getDashboardStats();
$alerts = getAlerts();
$recentInterventions = getRecentInterventions(5);
?>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-tachometer-alt"></i> <?php echo t('dashboard'); ?>
        <small class="text-muted"><?php echo t('view_performance'); ?></small>
    </h2>
    
    <!-- Alertes dynamiques -->
    <?php if(!empty($alerts)): ?>
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
                    <h5 class="card-title"><?php echo t('equipments'); ?></h5>
                    <h2><?php echo $stats['total_equipment'] ?? 0; ?></h2>
                    <p class="mb-0"><?php echo t('active'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning stat-card" onclick="window.location.href='?page=interventions'" style="cursor: pointer;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('interventions'); ?> <?php echo t('in_progress'); ?></h5>
                    <h2><?php echo $stats['active_interventions'] ?? 0; ?></h2>
                    <p class="mb-0"><?php echo t('to_do'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info stat-card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('avg_duration'); ?></h5>
                    <h2><?php echo $stats['avg_intervention_duration'] ?? 0; ?>h</h2>
                    <p class="mb-0"><?php echo t('per_intervention'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-danger stat-card" onclick="window.location.href='?page=stock'" style="cursor: pointer;">
                <div class="card-body">
                    <h5 class="card-title"><?php echo t('critical_stock'); ?></h5>
                    <h2><?php echo $stats['critical_stock'] ?? 0; ?></h2>
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
                                        <td><?php echo htmlspecialchars($interv['equipment_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($interv['title'] ?? 'Sans titre'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($interv['priority'] ?? 'medium') == 'critical' ? 'danger' : 'warning'; ?>">
                                                <?php echo t($interv['priority'] ?? 'medium'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo t($interv['task_status'] ?? $interv['status'] ?? 'pending'); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($interv['created_at'] ?? 'now')); ?></td>
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
</div>

<style>
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}
</style>