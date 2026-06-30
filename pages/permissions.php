<?php
// pages/permissions.php - Gestion des accès par rôle
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>Access denied</div>";
    return;
}

$permissionsFile = __DIR__ . '/../permissions.json';

// Liste des pages du projet
$all_pages = [
    'dashboard', 'performance', 'equipment', 'equipment_detail', 'interventions',
    'intervention_add', 'intervention_view', 'preventive', 'stock', 'technicians',
    'alerts', 'planning', 'profile', 'technician_detail',
    'mail_settings', 'users', 'export_center', 'admin_migrations', 'criticality_matrix',
    'calendar', 'equipment_attachments', 'equipment_edit', 'intervention_edit',
    'preventive_edit', 'technician_edit', 'stock_detail', 'permissions'
];

$roles = ['admin', 'supervisor', 'technician', 'viewer'];

// Charger les permissions depuis le fichier JSON
function loadPermissions($file) {
    if(!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

// Sauvegarder les permissions dans le fichier JSON
function savePermissions($file, $permissions) {
    file_put_contents($file, json_encode($permissions, JSON_PRETTY_PRINT));
}

// Initialiser les permissions par défaut (tout coché pour tous)
function initDefaultPermissions($pages) {
    $default = [];
    foreach(['supervisor', 'technician', 'viewer'] as $role) {
        $default[$role] = $pages;
    }
    return $default;
}

// Charger les permissions actuelles
$permissionsData = loadPermissions($permissionsFile);

// Si le fichier est vide, initialiser avec toutes les pages cochées par défaut
if(empty($permissionsData)) {
    $permissionsData = initDefaultPermissions($all_pages);
    savePermissions($permissionsFile, $permissionsData);
}

// Traitement du formulaire
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPermissions = [];
    foreach(['supervisor', 'technician', 'viewer'] as $role) {
        $newPermissions[$role] = isset($_POST[$role]) ? array_keys($_POST[$role]) : [];
    }
    savePermissions($permissionsFile, $newPermissions);
    $permissionsData = $newPermissions;
    $message = "✅ Permissions updated successfully";
}
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .permissions-table th {
        background: #343a40;
        color: white;
        padding: 12px;
        text-align: center;
    }
    .permissions-table td {
        padding: 12px 8px;
        text-align: center;
        vertical-align: middle;
    }
    .page-name {
        font-weight: bold;
        background: #f8f9fa;
        text-align: left;
        color: #2c3e50;
    }
    .checkbox-admin {
        opacity: 0.7;
    }
</style>

<div class="container-fluid">
    <h2 class="mb-4">
        <i class="fas fa-lock"></i> <?php echo t('role_access_management'); ?>
    </h2>

    <?php if(isset($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        <strong><?php echo t('administrator'); ?></strong> : <?php echo t('admin_desc'); ?>
    </div>

    <form method="POST">
        <div class="info-card">
            <div class="card-header-custom">
                <i class="fas fa-table"></i> <?php echo t('page_access_by_role'); ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 permissions-table">
                        <thead>
                            <tr>
                                <th style="text-align: left;"><?php echo t('page'); ?></th>
                                <th><?php echo t('administrator'); ?></th>
                                <th><?php echo t('supervisor'); ?></th>
                                <th><?php echo t('technician'); ?></th>
                                <th><?php echo t('viewer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_pages as $page): 
                                $translatedName = t($page);
                                // Fallback to formatted name if key equals value (meaning no translation found)
                                if ($translatedName === $page) {
                                    $translatedName = ucwords(str_replace('_', ' ', $page));
                                }
                                
                                $checkedSupervisor = in_array($page, $permissionsData['supervisor'] ?? []) ? 'checked' : '';
                                $checkedTechnician = in_array($page, $permissionsData['technician'] ?? []) ? 'checked' : '';
                                $checkedViewer = in_array($page, $permissionsData['viewer'] ?? []) ? 'checked' : '';
                            ?>
                            <tr>
                                <td class="page-name"><?php echo htmlspecialchars($translatedName); ?></td>
                                <td class="text-center">
                                    <input type="checkbox" checked disabled class="form-check-input checkbox-admin">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="supervisor[<?php echo $page; ?>]" value="1" <?php echo $checkedSupervisor; ?> class="form-check-input">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="technician[<?php echo $page; ?>]" value="1" <?php echo $checkedTechnician; ?> class="form-check-input">
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="viewer[<?php echo $page; ?>]" value="1" <?php echo $checkedViewer; ?> class="form-check-input">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3 mb-5">
            <button type="submit" class="btn btn-primary"> <i class="fas fa-save"></i> <?php echo t('save_changes'); ?> </button>
            <a href="?page=dashboard" class="btn btn-secondary"> <i class="fas fa-times"></i> <?php echo t('cancel'); ?> </a>
        </div>
    </form>
</div>