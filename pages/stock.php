<?php
// pages/stock.php - Liste des pièces détachées + mouvements de stock
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$action = $_GET['action'] ?? 'list';
if ($action == 'add') {
    header('Location: ?page=stock_add');
    exit();
}
if ($action == 'edit' && isset($_GET['id'])) {
    header('Location: ?page=stock_edit&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'delete' && isset($_GET['id'])) {
    header('Location: ?page=stock_delete&id=' . intval($_GET['id']));
    exit();
}
if ($action == 'restore' && isset($_GET['id']) && $_SESSION['role'] == 'admin') {
    $stmt = $pdo->prepare("UPDATE spare_parts SET quantity = 0, min_quantity = 5, documentation_path = NULL WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    logUserAction($_SESSION['user_id'], 'stock_restored', "Part ID: {$_GET['id']} reactivated");
    header('Location: ?page=stock&msg=' . urlencode(t('save_success')));
    exit();
}

$message = '';
$error = '';

// ========== TRAITEMENT DES MOUVEMENTS ==========
if($action == 'movement' && isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $part_id = $_GET['id'];
    $movement_type = $_POST['movement_type'];
    $quantity = intval($_POST['quantity']);
    $reason = trim($_POST['reason'] ?? '');
    $related = $_POST['related_type'] ?? '';

    // Extraire le type et l'ID
    $related_type = null;
    $related_id = null;
    if ($related && strpos($related, '_') !== false) {
        list($related_type, $related_id) = explode('_', $related);
        $related_id = intval($related_id);
    }

    $stmt = $pdo->prepare("SELECT quantity FROM spare_parts WHERE id = ?");
    $stmt->execute([$part_id]);
    $current = $stmt->fetchColumn();

    if($movement_type == 'in') {
        $new_quantity = $current + $quantity;
        $movement_text = "Stock in";
    } else {
        if($current < $quantity) {
            $error = "❌ " . t('stock_insufficient') . " (disponible: $current)";
        } else {
            $new_quantity = $current - $quantity;
            $movement_text = "Stock out";
        }
    }

    if(!$error) {
        $pdo->prepare("UPDATE spare_parts SET quantity = ?, last_restock = ? WHERE id = ?")
            ->execute([$new_quantity, ($movement_type == 'in' ? date('Y-m-d') : null), $part_id]);

        $stmt = $pdo->prepare("INSERT INTO stock_movements (part_id, movement_type, quantity, reason, related_type, related_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$part_id, $movement_type, $quantity, $reason, $related_type, $related_id]);

        logUserAction($_SESSION['user_id'], 'stock_movement', "$movement_text: $quantity x part ID: $part_id");
        $message = "✅ " . t('save_success');
        echo "<meta http-equiv='refresh' content='1;url=?page=stock_detail&id=$part_id'>";
    }
}

// Récupération des pièces
if($_SESSION['role'] == 'admin') {
    $parts = $pdo->query("SELECT * FROM spare_parts ORDER BY name")->fetchAll();
} else {
    $parts = $pdo->query("SELECT * FROM spare_parts WHERE quantity >= 0 ORDER BY name")->fetchAll();
}

// Historique des modifications
$history = [];
foreach($parts as $part) {
    $stmt = $pdo->prepare("
        SELECT * FROM user_logs 
        WHERE action IN ('stock_created', 'stock_updated', 'stock_deleted', 'stock_restored', 'stock_movement')
        AND details LIKE ?
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $stmt->execute(["%ID: {$part['id']}%"]);
    $history[$part['id']] = $stmt->fetchAll();
}

// Statistiques
$critical_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] <= $p['min_quantity'] && $p['quantity'] >= 0; 
}));
$warning_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] > $p['min_quantity'] && $p['quantity'] <= $p['min_quantity'] * 2 && $p['quantity'] >= 0; 
}));
$ok_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] > $p['min_quantity'] * 2 && $p['quantity'] >= 0; 
}));
$inactive_stock = count(array_filter($parts, function($p) { 
    return $p['quantity'] < 0; 
}));
?>

<style>
    .stock-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .stock-card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; font-weight: bold; }
    .status-critical { background: #dc3545; color: white; }
    .status-warning { background: #ffc107; color: #333; }
    .status-ok { background: #28a745; color: white; }
    .status-inactive { background: #6c757d; color: white; }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .table-row-clickable { cursor: pointer; transition: background 0.2s; }
    .table-row-clickable:hover { background: #f8f9fa; }
    .table-dark th { background: #212529; color: white; padding: 12px 15px; font-weight: 600; vertical-align: middle; }
    .table td { padding: 12px 15px; vertical-align: middle; border-bottom: 1px solid #eee; }
    .action-buttons { display: inline-flex; flex-direction: column; gap: 4px; align-items: center; justify-content: center; }
    .action-buttons-row { display: flex; gap: 4px; justify-content: center; }
    .action-icon-btn { width: 30px !important; height: 30px !important; padding: 0 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; font-size: 12px !important; line-height: 1 !important; border-radius: 6px !important; }
    .stats-card { text-align: center; padding: 15px; background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.2s; cursor: pointer; }
    .stats-card:hover { transform: translateY(-3px); }
    .stats-number { font-size: 28px; font-weight: bold; }
    .history-item { padding: 5px 0; font-size: 10px; border-bottom: 1px solid #eee; }
    .history-item:last-child { border-bottom: none; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-primary:hover { filter: brightness(0.95); }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 6px; color: white; }
    .btn-warning:hover { background: #e06a0a; color: white; }
    .btn-danger { background: #dc3545; border: none; border-radius: 6px; }
    .btn-success { background: #28a745; border: none; border-radius: 6px; }
    .btn-info { background: #17a2b8; border: none; border-radius: 6px; }
    .legend-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; text-align: center; }
    .legend-item { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px; background: #f8f9fa; border-radius: 10px; transition: transform 0.2s; }
    .legend-item:hover { transform: translateY(-2px); background: #e9ecef; }
    .legend-item i { font-size: 20px; }
    .legend-item .status-badge { font-size: 12px; padding: 5px 12px; }
    .legend-item small { font-size: 11px; color: #6c757d; }
    @media (max-width: 768px) { .legend-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
    @media (max-width: 480px) { .legend-grid { grid-template-columns: 1fr; } }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes"></i> <?php echo t('stock'); ?></h2>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=stock_add" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?php echo t('add_part'); ?>
        </a>
        <?php endif; ?>
    </div>
    
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=critical'">
                <div class="stats-number text-danger"><?php echo $critical_stock; ?></div>
                <div class="text-muted"><?php echo t('critical_stock'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=warning'">
                <div class="stats-number text-warning"><?php echo $warning_stock; ?></div>
                <div class="text-muted"><?php echo t('to_monitor'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=ok'">
                <div class="stats-number text-success"><?php echo $ok_stock; ?></div>
                <div class="text-muted"><?php echo t('sufficient'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card" onclick="window.location.href='?page=stock&status=inactive'">
                <div class="stats-number text-secondary"><?php echo $inactive_stock; ?></div>
                <div class="text-muted"><?php echo t('inactive'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Liste -->
    <div class="stock-card">
        <div class="stock-card-header">
            <i class="fas fa-list"></i> <?php echo t('stock_list'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th><?php echo t('part_number'); ?></th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('quantity'); ?></th>
                            <th><?php echo t('stock_level'); ?></th>
                            <th><?php echo t('status'); ?></th>
                            <th><?php echo t('location_stock'); ?></th>
                            <th><?php echo t('unit_price'); ?></th>
                            <th><?php echo t('documentation'); ?></th>
                            <th><?php echo t('last_modifications'); ?></th>
                            <th class="text-center" style="width: 90px;"><?php echo t('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($parts as $part): 
                            $stock_percent = $part['min_quantity'] > 0 ? min(100, ($part['quantity'] / $part['min_quantity']) * 100) : 0;
                            
                            if($part['quantity'] < 0) {
                                $status_class = 'status-inactive';
                                $status_text = '⚫ '. t('inactive');
                                $bg_class = '';
                            } elseif($part['quantity'] <= $part['min_quantity']) {
                                $status_class = 'status-critical';
                                $status_text = '🔴 '. t('critical_stock');
                                $bg_class = 'table-danger';
                            } elseif($part['quantity'] <= $part['min_quantity'] * 2) {
                                $status_class = 'status-warning';
                                $status_text = '🟡 '. t('to_monitor');
                                $bg_class = 'table-warning';
                            } else {
                                $status_class = 'status-ok';
                                $status_text = '🟢 '. t('sufficient');
                                $bg_class = '';
                            }
                        ?>
                        <tr class="table-row-clickable <?php echo $bg_class; ?>" onclick="window.location.href='?page=stock_detail&id=<?php echo $part['id']; ?>'">
                            <td><strong><?php echo htmlspecialchars($part['part_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($part['name']); ?></td>
                            <td><?php echo $part['quantity'] >= 0 ? $part['quantity'] : '-'; ?></td>
                            <td style="width: 100px;">
                                <?php if($part['quantity'] >= 0): ?>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar <?php echo $stock_percent <= 30 ? 'bg-danger' : ($stock_percent <= 60 ? 'bg-warning' : 'bg-success'); ?>" 
                                        style="width: <?php echo $stock_percent; ?>%"></div>
                                </div>
                                <small><?php echo $part['quantity']; ?> / <?php echo $part['min_quantity']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo htmlspecialchars($part['location'] ?: '-'); ?></td>
                            <td><?php echo number_format($part['unit_price'], 2); ?> €</td>
                            <td class="text-center">
                                <?php if(!empty($part['documentation_path'])): ?>
                                    <i class="fas fa-file-alt text-info" title="<?php echo t('documentation'); ?>"></i>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 120px;">
                                <?php if(!empty($history[$part['id']])): ?>
                                    <?php foreach(array_slice($history[$part['id']], 0, 2) as $h): ?>
                                    <div class="history-item">
                                        <?php
                                        $action_icons = [
                                            'stock_created' => '🟢 ' . t('created'),
                                            'stock_updated' => '✏️ ' . t('modified'),
                                            'stock_deleted' => '🗑️ ' . t('deactivated'),
                                            'stock_restored' => '🔄 ' . t('restored'),
                                            'stock_movement' => '📊 ' . t('movement')
                                        ];
                                        echo isset($action_icons[$h['action']]) ? $action_icons[$h['action']] : $h['action'];
                                        ?>
                                        <br><small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center action-buttons" onclick="event.stopPropagation()">
                                <?php if($part['quantity'] >= 0): ?>
                                    <div class="action-buttons">
                                        <div class="action-buttons-row">
                                            <button type="button" class="btn btn-sm btn-success action-icon-btn" title="<?php echo t('stock_in'); ?>" data-bs-toggle="modal" data-bs-target="#movementInModal<?php echo $part['id']; ?>">
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-warning action-icon-btn" title="<?php echo t('stock_out'); ?>" data-bs-toggle="modal" data-bs-target="#movementOutModal<?php echo $part['id']; ?>">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                        </div>
                                        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                                            <div class="action-buttons-row">
                                                <a href="?page=stock_edit&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-primary action-icon-btn" title="<?php echo t('edit'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?page=stock_delete&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-danger action-icon-btn" title="<?php echo t('delete'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                        <a href="?page=stock&action=restore&id=<?php echo $part['id']; ?>" class="btn btn-sm btn-success action-icon-btn" title="<?php echo t('restore'); ?>" onclick="return confirm('<?php echo t('restore_confirm'); ?>')">
                                            <i class="fas fa-undo-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Modale entrée -->
                        <div class="modal fade" id="movementInModal<?php echo $part['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title"><i class="fas fa-plus-circle"></i> <?php echo t('stock_in'); ?></h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                                        <div class="modal-body">
                                            <p><strong><?php echo t('part'); ?> :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                                            <p><strong><?php echo t('current_stock'); ?> :</strong> <?php echo $part['quantity']; ?></p>
                                            <input type="hidden" name="movement_type" value="in">
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('quantity_to_add'); ?></label>
                                                <input type="number" name="quantity" class="form-control" min="1" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('reason'); ?></label>
                                                <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo t('reason_placeholder'); ?>"></textarea>
                                            </div>
                                            <!-- Pas d'association pour les entrées (peut-être utile mais on laisse vide) -->
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                                            <button type="submit" class="btn btn-success"><?php echo t('confirm'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modale sortie (avec association) -->
                        <div class="modal fade" id="movementOutModal<?php echo $part['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-warning text-dark">
                                        <h5 class="modal-title"><i class="fas fa-minus-circle"></i> <?php echo t('stock_out'); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                                        <div class="modal-body">
                                            <p><strong><?php echo t('part'); ?> :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                                            <p><strong><?php echo t('current_stock'); ?> :</strong> <?php echo $part['quantity']; ?></p>
                                            <input type="hidden" name="movement_type" value="out">
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('quantity_to_remove'); ?></label>
                                                <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $part['quantity']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo t('reason'); ?></label>
                                                <textarea name="reason" class="form-control" rows="2" placeholder="<?php echo t('reason_placeholder'); ?>"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Associer à (intervention / maintenance)</label>
                                                <select name="related_type" class="form-select">
                                                    <option value="">-- Aucune --</option>
                                                    <?php
                                                    $invStmt = $pdo->query("SELECT id, task_number, title FROM interventions WHERE task_status NOT IN ('completed', 'closed') ORDER BY task_number");
                                                    $interventions = $invStmt->fetchAll();
                                                    if ($interventions): ?>
                                                        <optgroup label="Interventions">
                                                        <?php foreach ($interventions as $inv): ?>
                                                            <option value="intervention_<?php echo $inv['id']; ?>">
                                                                <?php echo htmlspecialchars($inv['task_number'] . ' - ' . $inv['title']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endif; ?>
                                                    <?php
                                                    $prevStmt = $pdo->query("SELECT id, task_number, title FROM preventive_maintenance WHERE task_status != 'completed' ORDER BY task_number");
                                                    $preventives = $prevStmt->fetchAll();
                                                    if ($preventives): ?>
                                                        <optgroup label="Maintenances préventives">
                                                        <?php foreach ($preventives as $prev): ?>
                                                            <option value="preventive_<?php echo $prev['id']; ?>">
                                                                <?php echo htmlspecialchars($prev['task_number'] . ' - ' . $prev['title']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endif; ?>
                                                </select>
                                                <small class="text-muted">Laissez vide si non lié à une tâche.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                                            <button type="submit" class="btn btn-warning"><?php echo t('confirm'); ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Légende -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="stock-card">
                <div class="stock-card-header">
                    <i class="fas fa-info-circle"></i> <?php echo t('legend'); ?>
                </div>
                <div class="card-body p-3">
                    <div class="legend-grid">
                        <div class="legend-item">
                            <span class="status-badge status-critical">🔴 <?php echo t('critical_stock'); ?></span>
                            <small><?php echo t('critical_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-warning">🟡 <?php echo t('to_monitor'); ?></span>
                            <small><?php echo t('monitor_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-ok">🟢 <?php echo t('sufficient'); ?></span>
                            <small><?php echo t('sufficient_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <span class="status-badge status-inactive">⚫ <?php echo t('inactive'); ?></span>
                            <small><?php echo t('inactive_desc'); ?></small>
                        </div>
                        <div class="legend-item">
                            <i class="fas fa-plus-circle text-success"></i>
                            <small><?php echo t('stock_in'); ?></small>
                        </div>
                        <div class="legend-item">
                            <i class="fas fa-minus-circle text-warning"></i>
                            <small><?php echo t('stock_out'); ?></small>
                        </div>
                        <div class="legend-item">
                            <i class="fas fa-edit text-primary"></i>
                            <small><?php echo t('edit'); ?></small>
                        </div>
                        <div class="legend-item">
                            <i class="fas fa-trash text-danger"></i>
                            <small><?php echo t('delete'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openDocumentation(path) {
    let formattedPath = path.replace(/\\/g, '/');
    if (!formattedPath.startsWith('file:///')) {
        formattedPath = 'file:///' + formattedPath;
    }
    window.open(formattedPath, '_blank');
}
</script>