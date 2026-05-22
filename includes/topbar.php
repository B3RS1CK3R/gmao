<!-- includes/topbar.php -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold">
            <i class="fas fa-tools text-primary"></i> GMAO
        </span>
        
        <div class="ms-auto d-flex align-items-center gap-3">
            
            <!-- Sélecteur de Langue -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-globe"></i> 
                    <?php echo getCurrentLanguage() === 'fr' ? '🇫🇷 Français' : '🇬🇧 English'; ?>
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
                    <?php echo htmlspecialchars($_SESSION['username'] ?? t('unknown')); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="?page=profile"><?php echo t('profile'); ?></a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="?page=logout"><?php echo t('logout'); ?></a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>