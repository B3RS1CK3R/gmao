<?php
// includes/sidebar.php - Menu latéral complet GMAO

// Définition des groupes de pages (maître -> liste des pages dépendantes)
$page_groups = [
    'equipment' => [
        'equipment', 'equipment_add', 'equipment_edit', 'equipment_delete', 
        'equipment_detail', 'equipment_qr', 'equipment_attachments', 'equipment_restore'
    ],
    'interventions' => [
        'interventions', 'intervention_add', 'intervention_edit', 'intervention_view',
        'interventions_assign', 'interventions_complete', 'interventions_delete'
    ],
    'technicians' => [
        'technicians', 'technician_add', 'technician_edit', 'technician_detail',
        'technician_delete', 'technicians_restore', 'team_add', 'team_detail', 'team_delete'
    ],
    'preventive' => [
        'preventive', 'preventive_add', 'preventive_edit', 'preventive_delete', 
        'preventive_complete', 'preventive_assign', 'preventive_view'
    ],
    'contractors' => [
        'contractors', 'contractor_add', 'contractor_edit', 'contractor_delete',
        'contractor_detail', 'contractors_restore'
    ],
    'planning' => [
        'planning'
    ],
    'stock' => [
        'stock', 'stock_detail', 'stock_add', 'stock_edit', 'stock_delete'
    ],
    'users' => [
        'users'
    ],
    'profile' => [
        'profile'
    ],
    'settings' => [
        'settings'
    ],
    'alerts' => [
        'alerts'
    ],
];

// Déterminer la page maître active et si c'est une sous-page
$current_page = $page ?? 'dashboard';
$active_group = null;
$is_child = false;

foreach ($page_groups as $master => $pages) {
    if (in_array($current_page, $pages)) {
        $active_group = $master;
        $is_child = ($current_page !== $master);
        break;
    }
}
?>

<!-- Sidebar -->
<div class="sidebar-fixed" id="sidebarWrapper">
    <div class="sidebar-content">
        
        <!-- Logo -->
        <div class="d-flex align-items-center mb-4 px-3 pt-3">
            <i class="fas fa-tools fa-2x text-primary me-3"></i>
            <div>
                <h4 class="mb-0 text-white"><?php echo t('gmao'); ?></h4>
                <small class="text-light"><?php echo t('gmao_desc'); ?></small>
            </div>
        </div>

        <!-- Menu Complet avec ascenseur -->
        <div class="sidebar-menu-container">
            <ul class="nav flex-column">
                <!-- 1. Dashboard -->
                <li class="nav-item">
                    <a href="index.php?page=dashboard" class="nav-link <?php echo ($page ?? '') === 'dashboard' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt me-2"></i> <?php echo t('dashboard'); ?>
                    </a>
                </li>

                <!-- 2. Equipment -->
                <li class="nav-item">
                    <a href="index.php?page=equipment" class="nav-link <?php echo ($active_group === 'equipment') ? 'active' : ''; ?> <?php echo ($active_group === 'equipment' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-cogs me-2"></i> <?php echo t('equipment'); ?>
                    </a>
                </li>

                <!-- 3. Interventions -->
                <li class="nav-item">
                    <a href="index.php?page=interventions" class="nav-link <?php echo ($active_group === 'interventions') ? 'active' : ''; ?> <?php echo ($active_group === 'interventions' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-wrench me-2"></i> <?php echo t('interventions'); ?>
                    </a>
                </li>

                <!-- 4. Preventive -->
                <li class="nav-item">
                    <a href="index.php?page=preventive" class="nav-link <?php echo ($active_group === 'preventive') ? 'active' : ''; ?> <?php echo ($active_group === 'preventive' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-calendar-check me-2"></i> <?php echo t('preventive_maintenance'); ?>
                    </a>
                </li>

                <!-- 5. Technicians -->
                <li class="nav-item">
                    <a href="index.php?page=technicians" class="nav-link <?php echo ($active_group === 'technicians') ? 'active' : ''; ?> <?php echo ($active_group === 'technicians' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-user-cog me-2"></i> <?php echo t('technicians'); ?>
                    </a>
                </li>

                <!-- 6. Planning -->
                <li class="nav-item">
                    <a href="index.php?page=planning" class="nav-link <?php echo ($active_group === 'planning') ? 'active' : ''; ?> <?php echo ($active_group === 'planning' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-calendar-alt me-2"></i> <?php echo t('planning'); ?>
                    </a>
                </li>

                <!-- 7. Stock -->
                <li class="nav-item">
                    <a href="index.php?page=stock" class="nav-link <?php echo ($active_group === 'stock') ? 'active' : ''; ?> <?php echo ($active_group === 'stock' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-boxes me-2"></i> <?php echo t('stock'); ?>
                    </a>
                </li>

                <!-- 8. Prestataires -->
                <li class="nav-item">
                    <a href="index.php?page=contractors" class="nav-link <?php echo ($active_group === 'contractors') ? 'active' : ''; ?> <?php echo ($active_group === 'contractors' && $is_child) ? 'active-child' : ''; ?>">
                        <i class="fas fa-building me-2"></i> <?php echo t('contractors'); ?>
                    </a>
                </li>

                <!-- 9. Performance -->
                <li class="nav-item">
                    <a href="index.php?page=performance" class="nav-link <?php echo ($page ?? '') === 'performance' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line me-2"></i> <?php echo t('performance_analysis'); ?>
                    </a>
                </li>

                <!-- 10. Alerts -->
                <li class="nav-item">
                    <a href="index.php?page=alerts" class="nav-link notification-badge <?php echo ($page ?? '') === 'alerts' ? 'active' : ''; ?>">
                        <i class="fas fa-bell me-2"></i> <?php echo t('alerts'); ?>
                        <span class="badge-count" id="alertBadgeCount" style="display: none;">0</span>
                    </a>
                </li>

                <!-- 11. Criticality Matrix -->
                <li class="nav-item">
                    <a href="index.php?page=criticality" class="nav-link <?php echo ($page ?? '') === 'criticality' ? 'active' : ''; ?>">
                        <i class="fas fa-th me-2"></i> <?php echo t('criticality_matrix'); ?>
                    </a>
                </li>

                <hr class="my-2 bg-secondary">

                <!-- 12. Users -->
                <li class="nav-item">
                    <a href="index.php?page=users" class="nav-link <?php echo ($page ?? '') === 'users' ? 'active' : ''; ?>">
                        <i class="fas fa-users me-2"></i> <?php echo t('users'); ?>
                    </a>
                </li>

                <!-- 13. Profile -->
                <li class="nav-item">
                    <a href="index.php?page=profile" class="nav-link <?php echo ($page ?? '') === 'profile' ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?>
                    </a>
                </li>

                <!-- 14. Export Center -->
                <li class="nav-item">
                    <a href="index.php?page=export" class="nav-link <?php echo ($page ?? '') === 'export' ? 'active' : ''; ?>">
                        <i class="fas fa-file-export me-2"></i> <?php echo t('export_center'); ?>
                    </a>
                </li>

                <!-- 15. Mail Setting -->
                <li class="nav-item">
                    <a href="index.php?page=email_config" class="nav-link <?php echo ($page ?? '') === 'email_config' ? 'active' : ''; ?>">
                        <i class="fas fa-envelope me-2"></i> <?php echo t('email_config'); ?>
                    </a>
                </li>

                <!-- 16. Permissions -->
                <li class="nav-item">
                    <a href="index.php?page=permissions" class="nav-link <?php echo ($page ?? '') === 'permissions' ? 'active' : ''; ?>">
                        <i class="fas fa-key me-2"></i> <?php echo t('permissions'); ?>
                    </a>
                </li>

                <!-- 17. Settings -->
                <li class="nav-item">
                    <a href="index.php?page=settings" class="nav-link <?php echo ($page ?? '') === 'settings' ? 'active' : ''; ?>">
                        <i class="fas fa-cog me-2"></i> <?php echo t('settings'); ?>
                    </a>
                </li>

                <!-- 18. Admin Migrations -->
                <li class="nav-item">
                    <a href="index.php?page=migrations" class="nav-link <?php echo ($page ?? '') === 'migrations' ? 'active' : ''; ?>">
                        <i class="fas fa-database me-2"></i> <?php echo t('admin_migrations'); ?>
                    </a>
                </li>

            </ul>
        </div>

        <!-- Footer -->
        <div class="sidebar-footer pt-3 pb-3 px-3 border-top border-secondary">
            <small class="text-muted">
                <span class="text-info"><?php echo t('version'); ?> <?php echo t('version_number'); ?></span><br>
                <span class="text-light"><?php echo date('Y'); ?> © GMAO</span>
            </small>
        </div>
    </div>
</div>

<!-- Overlay pour mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
    /* ===== SIDEBAR FIXE ===== */
    .sidebar-fixed {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 250px;
        z-index: 1040;
        background: linear-gradient(180deg, #212529 0%, #343a40 100%);
        color: white;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .sidebar-fixed .sidebar-content {
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    
    .sidebar-fixed .sidebar-menu-container {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px 0;
    }
    
    .sidebar-fixed .sidebar-footer {
        flex-shrink: 0;
        padding: 12px 15px;
        border-top: 1px solid #495057;
        text-align: center;
        font-size: 0.75rem;
        color: #adb5bd;
        background: rgba(0,0,0,0.2);
    }
    
    /* Scrollbar */
    .sidebar-fixed .sidebar-menu-container::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar-fixed .sidebar-menu-container::-webkit-scrollbar-track {
        background: #2c3034;
        border-radius: 3px;
    }
    .sidebar-fixed .sidebar-menu-container::-webkit-scrollbar-thumb {
        background: #6c757d;
        border-radius: 3px;
    }
    .sidebar-fixed .sidebar-menu-container::-webkit-scrollbar-thumb:hover {
        background: #adb5bd;
    }
    .sidebar-fixed .sidebar-menu-container {
        scrollbar-width: thin;
        scrollbar-color: #6c757d #2c3034;
    }
    
    /* Liens */
    .sidebar-fixed .nav-link {
        color: #adb5bd;
        padding: 10px 16px;
        border-radius: 6px;
        margin: 2px 8px;
        transition: all 0.2s;
        font-size: 14px;
        display: block;
        text-decoration: none;
    }
    
    .sidebar-fixed .nav-link:hover {
        background: #495057;
        color: white;
        transform: translateX(4px);
    }
    
    .sidebar-fixed .nav-link.active {
        background: #0d6efd;
        color: white;
        font-weight: 500;
    }
    
    .sidebar-fixed .nav-link.active-child {
        background: rgba(13, 110, 253, 0.4);
        color: white !important;
        font-weight: 500;
    }
    
    .sidebar-fixed .nav-link i {
        width: 20px;
        text-align: center;
        margin-right: 8px;
    }
    
    /* ===== BADGE D'ALERTES ===== */
    .sidebar-fixed .badge-count {
        background: #dc3545;
        color: white;
        border-radius: 50%;
        padding: 2px 8px;
        font-size: 11px;
        margin-left: auto;
        float: right;
        font-weight: 600;
        min-width: 20px;
        text-align: center;
        animation: pulse-badge 2s infinite;
    }
    
    @keyframes pulse-badge {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    .sidebar-fixed .nav-link .badge-count {
        margin-top: 2px;
    }
    
    .sidebar-fixed hr.bg-secondary {
        margin: 8px 12px;
        border-color: #495057;
    }
    
    /* ===== OVERLAY MOBILE ===== */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1039;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .sidebar-fixed {
            transform: translateX(-100%);
        }
        
        .sidebar-fixed.open {
            transform: translateX(0);
        }
        
        .sidebar-overlay.active {
            display: block;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== TOGGLE SIDEBAR MOBILE =====
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrapper = document.getElementById('sidebarWrapper');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebarWrapper.classList.toggle('open');
            sidebarOverlay.classList.toggle('active');
            document.body.style.overflow = sidebarWrapper.classList.contains('open') ? 'hidden' : '';
        });
    }
    
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebarWrapper.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // ===== FERMER LA SIDEBAR SUR CLIC LIEN (MOBILE) =====
    const navLinks = document.querySelectorAll('.sidebar-fixed .nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                sidebarWrapper.classList.remove('open');
                sidebarOverlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });
    
    // ===== RÉINITIALISER SUR REDIMENSIONNEMENT =====
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebarWrapper.classList.remove('open');
            sidebarOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // ===== MISE À JOUR DU BADGE D'ALERTES =====
    function updateAlertBadge() {
        const badge = document.getElementById('alertBadgeCount');
        if (!badge) return;
        
        fetch('api/count_alerts.php')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Erreur chargement alertes:', err);
                badge.style.display = 'none';
            });
    }
    
    // Exécuter au chargement
    updateAlertBadge();
    // Puis toutes les 30 secondes
    setInterval(updateAlertBadge, 30000);
});
</script>