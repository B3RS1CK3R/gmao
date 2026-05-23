<?php
// includes/sidebar.php - Menu latéral complet GMAO
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar p-3 text-white">
    <div class="d-flex flex-column h-100">
        
        <!-- Logo -->
        <div class="d-flex align-items-center mb-4 px-3">
            <i class="fas fa-tools fa-2x text-primary me-3"></i>
            <div>
                <h4 class="mb-0 text-white">GMAO</h4>
                <small class="text-light">Gestion de Maintenance</small>
            </div>
        </div>

        <!-- Menu Complet -->
        <ul class="nav flex-column mb-auto">

            <!-- 1. Dashboard -->
            <li class="nav-item">
                <a href="index.php?page=dashboard" class="nav-link <?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
            </li>

            <!-- 2. Equipment -->
            <li class="nav-item">
                <a href="index.php?page=equipment" class="nav-link <?php echo ($page ?? '') === 'equipment' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs me-2"></i> <?php echo t('equipment'); ?>
                </a>
            </li>

            <!-- 3. Interventions -->
            <li class="nav-item">
                <a href="index.php?page=interventions" class="nav-link <?php echo ($page ?? '') === 'interventions' ? 'active' : ''; ?>">
                    <i class="fas fa-wrench me-2"></i> <?php echo t('interventions'); ?>
                </a>
            </li>

            <!-- 4. Preventive -->
            <li class="nav-item">
                <a href="index.php?page=preventive" class="nav-link <?php echo ($page ?? '') === 'preventive' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check me-2"></i> <?php echo t('preventive_maintenance'); ?>
                </a>
            </li>

            <!-- 5. Technicians -->
            <li class="nav-item">
                <a href="index.php?page=technicians" class="nav-link <?php echo in_array(($page ?? ''), ['technicians', 'technician_detail']) ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog me-2"></i> <?php echo t('technicians'); ?>
                </a>
            </li>

            <!-- 6. Planning -->
            <li class="nav-item">
                <a href="index.php?page=planning" class="nav-link <?php echo ($page ?? '') === 'planning' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt me-2"></i> <?php echo t('planning'); ?>
                </a>
            </li>

            <!-- 7. Stock -->
            <li class="nav-item">
                <a href="index.php?page=stock" class="nav-link <?php echo ($page ?? '') === 'stock' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes me-2"></i> <?php echo t('stock'); ?>
                </a>
            </li>

            <!-- 8. Performance -->
            <li class="nav-item">
                <a href="index.php?page=performance" class="nav-link <?php echo ($page ?? '') === 'performance' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line me-2"></i> <?php echo t('performance_analysis'); ?>
                </a>
            </li>

            <!-- 9. Alerts -->
            <li class="nav-item">
                <a href="index.php?page=alerts" class="nav-link <?php echo ($page ?? '') === 'alerts' ? 'active' : ''; ?>">
                    <i class="fas fa-bell me-2"></i> <?php echo t('alerts'); ?>
                </a>
            </li>

            <!-- 10. Criticality Matrix -->
            <li class="nav-item">
                <a href="index.php?page=criticality" class="nav-link <?php echo ($page ?? '') === 'criticality' ? 'active' : ''; ?>">
                    <i class="fas fa-th me-2"></i> <?php echo t('criticality_matrix'); ?>
                </a>
            </li>

            <hr class="my-2 bg-secondary">

            <!-- 11. Users -->
            <li class="nav-item">
                <a href="index.php?page=users" class="nav-link <?php echo ($page ?? '') === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> <?php echo t('users'); ?>
                </a>
            </li>

            <!-- 12. Profile -->
            <li class="nav-item">
                <a href="index.php?page=profile" class="nav-link <?php echo ($page ?? '') === 'profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?>
                </a>
            </li>

            <!-- 13. Export Center -->
            <li class="nav-item">
                <a href="index.php?page=export" class="nav-link <?php echo ($page ?? '') === 'export' ? 'active' : ''; ?>">
                    <i class="fas fa-file-export me-2"></i> <?php echo t('export_center'); ?>
                </a>
            </li>

            <!-- 14. Mail Setting -->
            <li class="nav-item">
                <a href="index.php?page=email_config" class="nav-link <?php echo ($page ?? '') === 'email_config' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope me-2"></i> <?php echo t('email_config'); ?>
                </a>
            </li>

            <!-- 15. Permissions -->
            <li class="nav-item">
                <a href="index.php?page=permissions" class="nav-link <?php echo ($page ?? '') === 'permissions' ? 'active' : ''; ?>">
                    <i class="fas fa-key me-2"></i> <?php echo t('permissions'); ?>
                </a>
            </li>

            <!-- 16. Admin Migrations -->
            <li class="nav-item">
                <a href="index.php?page=migrations" class="nav-link <?php echo ($page ?? '') === 'migrations' ? 'active' : ''; ?>">
                    <i class="fas fa-database me-2"></i> <?php echo t('admin_migrations'); ?>
                </a>
            </li>

        </ul>

        <!-- Footer -->
        <div class="mt-auto pt-3 border-top border-secondary">
            <small class="text-muted px-3">
                <?php echo t('version'); ?> 1.0<br>
                <span class="text-light"><?php echo date('Y'); ?> © GMAO</span>
            </small>
        </div>
    </div>
</div>

<style>
    .sidebar {
        background: linear-gradient(180deg, #212529 0%, #343a40 100%);
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        min-height: 100vh;
    }
    .nav-link {
        color: #adb5bd;
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 4px;
        transition: all 0.2s;
    }
    .nav-link:hover {
        background: #495057;
        color: white;
        transform: translateX(4px);
    }
    .nav-link.active {
        background: #0d6efd;
        color: white;
        font-weight: 500;
    }
</style>