<?php
// includes/sidebar.php - Menu latéral GMAO (Bilingue)
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 sidebar p-3 text-white">
    <div class="d-flex flex-column h-100">
        
        <!-- Logo / Titre -->
        <div class="d-flex align-items-center mb-4 px-3">
            <i class="fas fa-tools fa-2x text-primary me-3"></i>
            <div>
                <h4 class="mb-0 text-white">GMAO</h4>
                <small class="text-light"><?php echo t('maintenance_management'); ?></small>
            </div>
        </div>

        <!-- Menu -->
        <ul class="nav flex-column mb-auto">
            
            <li class="nav-item">
                <a href="index.php?page=dashboard" class="nav-link <?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=equipment" class="nav-link <?php echo ($page ?? '') === 'equipment' ? 'active' : ''; ?>">
                    <i class="fas fa-cogs me-2"></i> <?php echo t('equipment'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=interventions" class="nav-link <?php echo ($page ?? '') === 'interventions' ? 'active' : ''; ?>">
                    <i class="fas fa-wrench me-2"></i> <?php echo t('interventions'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=technicians" class="nav-link <?php echo in_array(($page ?? ''), ['technicians', 'technician_detail']) ? 'active' : ''; ?>">
                    <i class="fas fa-user-cog me-2"></i> <?php echo t('technicians'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=planning" class="nav-link <?php echo ($page ?? '') === 'planning' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt me-2"></i> <?php echo t('planning'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=stock" class="nav-link <?php echo ($page ?? '') === 'stock' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes me-2"></i> <?php echo t('stock'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=reports" class="nav-link <?php echo ($page ?? '') === 'reports' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo t('reports'); ?>
                </a>
            </li>

            <hr class="my-2 bg-secondary">

            <li class="nav-item">
                <a href="index.php?page=users" class="nav-link <?php echo ($page ?? '') === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users me-2"></i> <?php echo t('users'); ?>
                </a>
            </li>

            <li class="nav-item">
                <a href="index.php?page=settings" class="nav-link <?php echo ($page ?? '') === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog me-2"></i> <?php echo t('settings'); ?>
                </a>
            </li>
        </ul>

        <!-- Footer Sidebar -->
        <div class="mt-auto pt-3 border-top border-secondary">
            <small class="text-muted px-3">
                <span class="text-light"><?php echo t('version'); ?> 1.0<br>
                <span class="text-light"><?php echo date('Y'); ?> © GMAO</span>
            </small>
        </div>
    </div>
</div>

<style>
    .sidebar {
        background: linear-gradient(180deg, #212529 0%, #343a40 100%);
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
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
    .nav-link i {
        width: 20px;
    }
</style>