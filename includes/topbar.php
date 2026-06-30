<!-- includes/topbar.php - Topbar fixe -->
<nav class="topbar-fixed" id="mainTopbar">
    <div class="d-flex align-items-center justify-content-between w-100">
        <div class="d-flex align-items-center">
            <!-- Bouton toggle mobile -->
            <button class="sidebar-toggle-btn" id="sidebarToggle" type="button">
                <i class="fas fa-bars"></i>
            </button>
            <span class="navbar-brand">
                <i class="fas fa-tools text-primary"></i> GMAO
            </span>
        </div>
        
        <!-- Date -->
        <div class="topbar-date text-muted">
            <?php echo format_date_local(date('Y-m-d'), 'full', false); ?>
        </div>
        
        <!-- Menu utilisateur -->
        <div class="d-flex align-items-center gap-2 gap-md-3">
            
            <!-- Sélecteur de Langue -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-globe"></i> 
                    <span class="d-none d-sm-inline">
                        <?php echo getCurrentLanguage() === 'fr' ? '🇫🇷' : '🇬🇧'; ?>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item <?php echo getCurrentLanguage()==='fr' ? 'active fw-bold' : ''; ?>" 
                           href="?setlang=fr">
                            🇫🇷 Français
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item <?php echo getCurrentLanguage()==='en' ? 'active fw-bold' : ''; ?>" 
                           href="?setlang=en">
                            🇬🇧 English
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Utilisateur -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> 
                    <span class="d-none d-sm-inline">
                        <?php echo htmlspecialchars($_SESSION['username'] ?? t('unknown')); ?>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?page=profile"><?php echo t('profile'); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="?page=logout" onclick="AlertSystem.clearDismissedAlerts()"><?php echo t('logout'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
    /* ===== TOPBAR FIXE ===== */
    .topbar-fixed {
        position: fixed;
        top: 0;
        right: 0;
        left: 250px; /* Largeur de la sidebar */
        height: 60px;
        z-index: 1030;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        padding: 0 20px;
        transition: box-shadow 0.3s ease, left 0.3s ease;
    }
    
    .topbar-fixed.scrolled {
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    
    .topbar-fixed .navbar-brand {
        font-weight: bold;
        font-size: 1.2rem;
        color: #212529;
    }
    
    .topbar-fixed .navbar-brand i {
        color: #0d6efd;
    }
    
    .topbar-fixed .topbar-date {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .topbar-fixed .btn-outline-secondary {
        border-color: #dee2e6;
        font-size: 0.85rem;
    }
    
    .topbar-fixed .btn-outline-secondary:hover {
        background: #f8f9fa;
        border-color: #ced4da;
    }
    
    /* Bouton toggle mobile */
    .sidebar-toggle-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #212529;
        padding: 0 10px;
        margin-right: 10px;
    }
    
    .sidebar-toggle-btn:hover {
        color: #0d6efd;
    }
    
    /* ===== RESPONSIVE ===== */
    @media (max-width: 992px) {
        .topbar-fixed {
            left: 0;
        }
        
        .sidebar-toggle-btn {
            display: inline-block;
        }
    }
    
    @media (max-width: 768px) {
        .topbar-fixed {
            height: 56px;
            padding: 0 10px;
        }
        
        .topbar-fixed .topbar-date {
            font-size: 0.7rem;
            display: none;
        }
        
        .topbar-fixed .navbar-brand {
            font-size: 1rem;
        }
        
        .topbar-fixed .btn-outline-secondary {
            font-size: 0.75rem;
            padding: 4px 8px;
        }
    }
    
    @media (max-width: 576px) {
        .topbar-fixed .topbar-date {
            display: none;
        }
        
        .topbar-fixed .btn-outline-secondary span {
            display: none;
        }
        
        .topbar-fixed .btn-outline-secondary .fa-globe,
        .topbar-fixed .btn-outline-secondary .fa-user-circle {
            font-size: 1.1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== EFFET SCROLL SUR TOPBAR =====
    const topbar = document.getElementById('mainTopbar');
    const mainContent = document.getElementById('mainContent');
    
    if (topbar && mainContent) {
        mainContent.addEventListener('scroll', function() {
            if (this.scrollTop > 10) {
                topbar.classList.add('scrolled');
            } else {
                topbar.classList.remove('scrolled');
            }
        });
    }
});
</script>