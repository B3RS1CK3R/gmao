<?php
// pages/contractors.php - Liste des prestataires extérieurs
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$action = $_GET['action'] ?? 'list';
$active_filter = isset($_GET['filter']) ? $_GET['filter'] : 'active';

// Redirections
if ($action == 'add') {
    header('Location: ?page=contractor_add');
    exit();
}
if ($action == 'edit' && isset($_GET['id'])) {
    header('Location: ?page=contractor_edit&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'delete' && isset($_GET['id'])) {
    header('Location: ?page=contractor_delete&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'restore' && isset($_GET['id'])) {
    header('Location: ?page=contractors_restore&id=' . intval($_GET['id']));
    exit();
}

// Récupération de tous les prestataires
$all_contractors = $pdo->query("SELECT * FROM contractors ORDER BY company_name")->fetchAll();

// Statistiques
$stats = [
    'total' => count($all_contractors),
    'active' => 0,
    'inactive' => 0,
    'suspended' => 0
];
foreach ($all_contractors as $c) {
    if (isset($stats[$c['status']])) {
        $stats[$c['status']]++;
    }
}

// Filtrer
$contractors = array_filter($all_contractors, function($c) use ($active_filter) {
    if ($active_filter == 'all') return true;
    return $c['status'] == $active_filter;
});

// Filtres
$filters = [
    'all' => ['label' => t('all'), 'icon' => ''],
    'active' => ['label' => t('active'), 'icon' => '🟢'],
    'inactive' => ['label' => t('inactive'), 'icon' => '⚫'],
    'suspended' => ['label' => t('suspended'), 'icon' => '🟠']
];

// Historique des modifications
$history = [];
foreach ($all_contractors as $c) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('contractor_created', 'contractor_updated', 'contractor_deleted', 'contractor_restored')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$c['id']}%"]);
    $history[$c['id']] = $stmt->fetchAll();
}
?>

<style>
    .card-contractor {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #6f42c1, #5a32a3);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .card-header-custom i {
        margin-right: 8px;
    }
    .stats-card {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
        margin-bottom: 15px;
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: bold;
    }
    .filter-bar {
        background: white;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .filter-btn {
        margin-right: 5px;
        transition: all 0.2s;
        border-radius: 6px;
        padding: 6px 14px;
        font-size: 13px;
    }
    .filter-btn.active {
        background: #6f42c1 !important;
        color: white !important;
        border-color: #6f42c1 !important;
    }
    .filter-btn.active:hover {
        background: #5a32a3 !important;
        color: white !important;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-active {
        background: #28a745;
        color: white;
    }
    .status-inactive {
        background: #6c757d;
        color: white;
    }
    .status-suspended {
        background: #fd7e14;
        color: white;
    }
    .action-buttons .btn {
        padding: 4px 8px;
        margin: 0 2px;
        border-radius: 6px;
    }
    .table-row-clickable {
        cursor: pointer;
        transition: background 0.2s;
    }
    .table-row-clickable:hover {
        background: #f8f9fa;
    }
    .table-dark th {
        background: #212529;
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        vertical-align: middle;
    }
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
        border-bottom: 1px solid #eee;
    }
    .history-item {
        padding: 5px 0;
        font-size: 11px;
        border-bottom: 1px solid #eee;
    }
    .legend-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        text-align: center;
    }
    .legend-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 10px;
    }
    @media (max-width: 768px) {
        .legend-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 480px) {
        .legend-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-building"></i> <?php echo t('contractors'); ?></h2>
        <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=contractor_add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('add_contractor'); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo htmlspecialchars($_GET['msg']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if (isset($_GET['err'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?php echo htmlspecialchars($_GET['err']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="row mb-4">
        <?php foreach ($stats as $key => $value): 
            $colors = [
                'total' => '#6f42c1',
                'active' => '#28a745',
                'inactive' => '#6c757d',
                'suspended' => '#fd7e14'
            ];
        ?>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=contractors&filter=<?php echo $key; ?>'">
                <div class="stats-number" style="color: <?php echo $colors[$key] ?? '#6f42c1'; ?>;"><?php echo $value; ?></div>
                <div class="text-muted"><?php echo $filters[$key]['label'] ?? $key; ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtres rapides -->
    <div class="filter-bar">
        <div class="row align-items-center">
            <div class="col-md-10">
                <div class="btn-group" role="group">
                    <?php foreach ($filters as $key => $filter): ?>
                    <button class="btn btn-outline-secondary btn-sm filter-btn <?php echo ($active_filter == $key) ? 'active' : ''; ?>" 
                            onclick="window.location.href='?page=contractors&filter=<?php echo $key; ?>'">
                        <?php echo $filter['icon']; ?> <?php echo $filter['label']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-2 text-end">
                <small class="text-muted"><i class="fas fa-chart-simple"></i> <?php echo t('total'); ?>: <?php echo $stats['total']; ?></small>
            </div>
        </div>
    </div>

    <!-- Liste -->
    <div class="card-contractor">
        <div class="card-header-custom"><i class="fas fa-list"></i> <?php echo t('contractors_list'); ?></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('company_name'); ?></th>
                            <th><?php echo t('siret'); ?></th>
                            <th><?php echo t('phone'); ?></th>
                            <th><?php echo t('email'); ?></th>
                            <th><?php echo t('specialty'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractors as $c): ?>
                        <tr class="table-row-clickable" onclick="window.location.href='?page=contractor_detail&id=<?php echo $c['id']; ?>'">
                            <td><strong><?php echo htmlspecialchars($c['company_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['siret'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($c['phone'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($c['email'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($c['specialty'] ?: '-'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $c['status']; ?>">
                                    <?php echo $filters[$c['status']]['icon'] ?? ''; ?> <?php echo $filters[$c['status']]['label'] ?? $c['status']; ?>
                                </span>
                            </td>
                            <td style="max-width: 150px;">
                                <?php if (!empty($history[$c['id']])): ?>
                                    <div class="history-item"><small class="text-muted"><?php echo format_date_local($history[$c['id']][0]['created_at'], 'long', true); ?></small></div>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center action-buttons" onclick="event.stopPropagation()">
                                <a href="?page=contractor_detail&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-info" title="<?php echo t('view'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($c['status'] != 'inactive'): ?>
                                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                    <a href="?page=contractor_edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-warning" title="<?php echo t('edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=contractor_delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger" title="<?php echo t('delete'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <a href="?page=contractors_restore&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-success" title="<?php echo t('restore'); ?>">
                                        <i class="fas fa-undo-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($contractors) == 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                <?php echo t('no_contractors_found'); ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-contractor">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #6c757d, #495057);">
                    <i class="fas fa-info-circle"></i> <?php echo t('legend'); ?>
                </div>
                <div class="card-body p-3">
                    <div class="legend-grid">
                        <div class="legend-item">
                            <span class="status-badge status-active">🟢 <?php echo t('active'); ?></span>
                            <small><?php echo t('contractor_active_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-inactive">⚫ <?php echo t('inactive'); ?></span>
                            <small><?php echo t('contractor_inactive_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-suspended">🟠 <?php echo t('suspended'); ?></span>
                            <small><?php echo t('contractor_suspended_desc'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>