<?php
// pages/stock_detail.php - Fiche détaillée d'une pièce détachée
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: index.php?page=stock');
    exit();
}

// Récupération de la pièce
$stmt = $pdo->prepare("SELECT * FROM spare_parts WHERE id = ?");
$stmt->execute([$id]);
$part = $stmt->fetch();

if(!$part) {
    echo "<div class='alert alert-danger'>Pièce détachée non trouvée</div>";
    return;
}

// Récupération de l'historique des mouvements de stock
$stmt = $pdo->prepare("
    SELECT * FROM stock_movements 
    WHERE part_id = ? 
    ORDER BY movement_date DESC 
    LIMIT 20
");
$stmt->execute([$id]);
$movements = $stmt->fetchAll();

// Récupération de l'historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('stock_created', 'stock_updated', 'stock_deleted', 'stock_restored', 'stock_movement')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Calcul des statistiques
$stock_status = '';
$stock_class = '';
$percentage = 0;

if($part['quantity'] < 0) {
    $stock_status = 'Désactivée';
    $stock_class = 'secondary';
} elseif($part['quantity'] <= 0) {
    $stock_status = 'RUPTURE DE STOCK';
    $stock_class = 'danger';
} elseif($part['quantity'] <= $part['min_quantity']) {
    $stock_status = 'Stock critique';
    $stock_class = 'danger';
} elseif($part['quantity'] <= $part['min_quantity'] * 2) {
    $stock_status = 'Stock à surveiller';
    $stock_class = 'warning';
} else {
    $stock_status = 'Stock suffisant';
    $stock_class = 'success';
}

if($part['min_quantity'] > 0 && $part['quantity'] >= 0) {
    $percentage = min(100, round(($part['quantity'] / $part['min_quantity']) * 100));
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
    .info-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .info-card-header.warning {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
    }
    .info-card-header.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    .info-card-header.success {
        background: linear-gradient(135deg, #28a745, #1e7e34);
    }
    .info-card-header.info {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }
    .stat-box {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        margin-bottom: 10px;
    }
    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
    }
    .stock-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }
    .stock-badge-danger { background: #dc3545; color: white; }
    .stock-badge-warning { background: #ffc107; color: #333; }
    .stock-badge-success { background: #28a745; color: white; }
    .stock-badge-secondary { background: #6c757d; color: white; }
    .progress-bar-custom {
        height: 10px;
        border-radius: 5px;
        transition: width 0.5s;
    }
    .history-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-folder {
        background: #17a2b8;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-folder:hover {
        background: #138496;
    }
    .doc-preview {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-box"></i> 
        <?php echo htmlspecialchars($part['name']); ?>
        <small class="text-muted">(<?php echo htmlspecialchars($part['part_number']); ?>)</small>
    </h2>
    <div>
        <a href="?page=stock" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à la liste
        </a>
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
        <a href="?page=stock&action=edit&id=<?php echo $part['id']; ?>" class="btn btn-warning">
            <i class="fas fa-edit"></i> Modifier
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <!-- Colonne gauche - Informations générales -->
    <div class="col-md-4">
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-info-circle"></i> Informations générales
            </div>
            <div class="card-body p-4">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td style="width: 40%;"><strong>Référence</strong></td>
                        <td><?php echo htmlspecialchars($part['part_number']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Nom</strong></td>
                        <td><?php echo htmlspecialchars($part['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Emplacement</strong></td>
                        <td><?php echo htmlspecialchars($part['location'] ?: 'Non spécifié'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fournisseur</strong></td>
                        <td><?php echo htmlspecialchars($part['supplier'] ?: 'Non spécifié'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Prix unitaire</strong></td>
                        <td><?php echo number_format($part['unit_price'], 2); ?> €</td>
                    </tr>
                    <tr>
                        <td><strong>Dernier réapprov.</strong></td>
                        <td><?php echo $part['last_restock'] ? format_date_us($part['last_restock'], false) : 'Non renseigné'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Statut du stock -->
        <div class="info-card">
            <div class="info-card-header <?php echo $stock_class == 'danger' ? 'danger' : ($stock_class == 'warning' ? 'warning' : ($stock_class == 'success' ? 'success' : '')); ?>">
                <i class="fas fa-chart-line"></i> État du stock
            </div>
            <div class="card-body p-4 text-center">
                <div class="stock-badge stock-badge-<?php echo $stock_class; ?> mb-3">
                    <?php echo $stock_status; ?>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-custom bg-<?php echo $stock_class == 'danger' ? 'danger' : ($stock_class == 'warning' ? 'warning' : 'success'); ?>" 
                         style="width: <?php echo $percentage; ?>%">
                        <?php echo $percentage; ?>%
                    </div>
                </div>
                <h2 class="mb-0"><?php echo $part['quantity'] >= 0 ? $part['quantity'] : 'Désactivée'; ?></h2>
                <p class="text-muted">Quantité disponible</p>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Seuil minimum</small>
                        <h4><?php echo $part['min_quantity']; ?></h4>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">Stock recommandé</small>
                        <h4><?php echo $part['min_quantity'] * 2; ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Documentation -->
        <?php if(!empty($part['documentation_path'])): ?>
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-folder-open"></i> Documentation
            </div>
            <div class="card-body p-4">
                <div class="doc-preview">
                    <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                    <p><strong>Fichier / Dossier :</strong><br>
                    <code><?php echo htmlspecialchars($part['documentation_path']); ?></code></p>
                    <button class="btn-folder" onclick="openDocumentation('<?php echo htmlspecialchars($part['documentation_path']); ?>')">
                        <i class="fas fa-folder-open"></i> Ouvrir la documentation
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Colonne droite - Mouvements et historique -->
    <div class="col-md-8">
        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($movements); ?></div>
                    <div class="text-muted">Mouvements total</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number text-success"><?php 
                        $in_count = 0;
                        foreach($movements as $m) {
                            if($m['movement_type'] == 'in') $in_count += $m['quantity'];
                        }
                        echo $in_count;
                    ?></div>
                    <div class="text-muted">Entrées</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <div class="stat-number text-danger"><?php 
                        $out_count = 0;
                        foreach($movements as $m) {
                            if($m['movement_type'] == 'out') $out_count += $m['quantity'];
                        }
                        echo $out_count;
                    ?></div>
                    <div class="text-muted">Sorties</div>
                </div>
            </div>
        </div>
        
        <!-- Mouvements de stock -->
        <div class="info-card">
            <div class="info-card-header info">
                <i class="fas fa-exchange-alt"></i> Mouvements de stock
            </div>
            <div class="card-body p-0">
                <?php if(empty($movements)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>Aucun mouvement de stock enregistré</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Quantité</th>
                                    <th>Raison</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($movements as $mov): ?>
                                <tr>
                                    <td><?php echo format_date_us($mov['movement_date'], true); ?></td>
                                    <td>
                                        <?php if($mov['movement_type'] == 'in'): ?>
                                            <span class="badge bg-success">📥 Entrée</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">📤 Sortie</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $mov['quantity']; ?></td>
                                    <td><small><?php echo htmlspecialchars($mov['reason'] ?: '-'); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Historique des modifications -->
        <?php if(!empty($history)): ?>
        <div class="info-card">
            <div class="info-card-header">
                <i class="fas fa-history"></i> Historique des modifications
            </div>
            <div class="card-body p-3">
                <?php foreach($history as $h): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <span>
                            <?php
                            $action_icons = [
                                'stock_created' => '🟢 Création',
                                'stock_updated' => '✏️ Modification',
                                'stock_deleted' => '🗑️ Désactivation',
                                'stock_restored' => '🔄 Réactivation',
                                'stock_movement' => '📊 Mouvement'
                            ];
                            echo isset($action_icons[$h['action']]) ? $action_icons[$h['action']] : $h['action'];
                            ?>
                        </span>
                        <small class="text-muted"><?php echo format_date_us($h['created_at'], true); ?></small>
                    </div>
                    <small class="text-muted">
                        Par : <?php echo htmlspecialchars($h['username'] ?? 'Inconnu'); ?> 
                        (IP: <?php echo htmlspecialchars($h['ip_address']); ?>)
                    </small>
                    <div class="small text-muted mt-1"><?php echo htmlspecialchars($h['details']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Boutons d'action -->
        <?php if($part['quantity'] >= 0): ?>
        <div class="action-buttons">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#movementInModal">
                <i class="fas fa-plus-circle"></i> Entrée de stock
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#movementOutModal">
                <i class="fas fa-minus-circle"></i> Sortie de stock
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal entrée de stock -->
<div class="modal fade" id="movementInModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Entrée de stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                <div class="modal-body">
                    <p><strong>Pièce :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                    <p><strong>Stock actuel :</strong> <?php echo $part['quantity']; ?></p>
                    <input type="hidden" name="movement_type" value="in">
                    <div class="mb-3">
                        <label class="form-label">Quantité à ajouter</label>
                        <input type="number" name="quantity" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Raison / Bon de commande</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Ex: Commande n°1234, Retour SAV..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Valider l'entrée</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal sortie de stock -->
<div class="modal fade" id="movementOutModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-minus-circle"></i> Sortie de stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=stock&action=movement&id=<?php echo $part['id']; ?>">
                <div class="modal-body">
                    <p><strong>Pièce :</strong> <?php echo htmlspecialchars($part['name']); ?></p>
                    <p><strong>Stock actuel :</strong> <?php echo $part['quantity']; ?></p>
                    <input type="hidden" name="movement_type" value="out">
                    <div class="mb-3">
                        <label class="form-label">Quantité à retirer</label>
                        <input type="number" name="quantity" class="form-control" min="1" max="<?php echo $part['quantity']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Raison / Intervention</label>
                        <textarea name="reason" class="form-control" rows="2" placeholder="Ex: Intervention n°..., Utilisation maintenance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">Valider la sortie</button>
                </div>
            </form>
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