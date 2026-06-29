<?php
// pages/equipment_edit.php - Formulaire d'édition (sans traitement POST)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=equipment');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$eq = $stmt->fetch();
if (!$eq) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';

// Récupération des pièces détachées (actives)
$parts = $pdo->query("SELECT id, part_number, name, location, quantity FROM spare_parts WHERE quantity >= 0 ORDER BY name")->fetchAll();

// Récupération des prestataires (fournisseurs) actifs
$contractors = $pdo->query("SELECT id, company_name, specialty FROM contractors WHERE status = 'active' ORDER BY company_name")->fetchAll();

// Récupération des pièces déjà associées à cet équipement
$associatedParts = [];
try {
    $stmt = $pdo->prepare("SELECT part_id, quantity_needed FROM equipment_parts WHERE equipment_id = ?");
    $stmt->execute([$id]);
    $associatedParts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // Table equipment_parts peut ne pas exister encore
}
?>

<style>
    .form-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .form-card-header {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .form-label {
        font-weight: 500;
        margin-bottom: 5px;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 12px;
    }
    .btn-warning {
        background: #fd7e14;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        color: white;
    }
    .btn-warning:hover {
        background: #e06a0a;
        color: white;
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
    }
    .btn-secondary:hover {
        background: #5a6268;
    }
    .btn-danger-sm {
        background: #dc3545;
        border: none;
        border-radius: 6px;
        padding: 5px 10px;
        color: white;
        font-size: 12px;
    }
    .btn-danger-sm:hover {
        background: #c82333;
    }
    .btn-success-sm {
        background: #28a745;
        border: none;
        border-radius: 6px;
        padding: 5px 10px;
        color: white;
        font-size: 12px;
    }
    .btn-success-sm:hover {
        background: #218838;
    }
    .info-message {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .part-row {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
        margin-bottom: 8px;
        border: 1px solid #e9ecef;
    }
    .part-row .row {
        align-items: center;
    }
    .text-muted-sm {
        font-size: 13px;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_equipment'); ?> : <?php echo htmlspecialchars($eq['code']); ?></h2>
        <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_equipment'); ?></div>
        <div class="card-body p-4">
            <form method="POST" action="?page=equipment_edit_action">
                <?= csrf_input() ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($eq['code']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($eq['name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('type'); ?></label>
                        <input type="text" name="type" class="form-control" value="<?php echo htmlspecialchars($eq['type']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('location'); ?></label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($eq['location']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('supplier'); ?></label>
                        <select name="supplier" class="form-select">
                            <option value="">-- <?php echo t('select_contractor'); ?> --</option>
                            <?php foreach ($contractors as $contractor): ?>
                                <option value="<?php echo htmlspecialchars($contractor['company_name']); ?>" 
                                    <?php if ($eq['supplier'] == $contractor['company_name']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($contractor['company_name']); ?>
                                    <?php if (!empty($contractor['specialty'])): ?>
                                        (<?php echo htmlspecialchars($contractor['specialty']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted"><?php echo t('select_supplier_from_contractors'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('status'); ?></label>
                        <select name="status" class="form-select">
                            <option value="active" <?php if($eq['status'] == 'active') echo 'selected'; ?>><?php echo t('active'); ?></option>
                            <option value="maintenance" <?php if($eq['status'] == 'maintenance') echo 'selected'; ?>><?php echo t('maintenance'); ?></option>
                            <option value="broken" <?php if($eq['status'] == 'broken') echo 'selected'; ?>><?php echo t('broken'); ?></option>
                            <?php if($_SESSION['role'] == 'admin'): ?>
                            <option value="retired" <?php if($eq['status'] == 'retired') echo 'selected'; ?>><?php echo t('retired'); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('purchase_date'); ?></label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo $eq['purchase_date']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('warranty_end'); ?></label>
                        <input type="date" name="warranty_end" class="form-control" value="<?php echo $eq['warranty_end']; ?>">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><?php echo t('technical_specs'); ?></label>
                        <textarea name="technical_specs" class="form-control" rows="3"><?php echo htmlspecialchars($eq['technical_specs']); ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('probability_score'); ?></label>
                        <select name="probability_score" class="form-select">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php if(($eq['probability_score'] ?? 1) == $i) echo 'selected'; ?>><?php echo $i; ?> - <?php echo t(['very_low','low','medium','high','very_high'][$i-1]); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('severity_score'); ?></label>
                        <select name="severity_score" class="form-select">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <option value="<?php echo $i; ?>" <?php if(($eq['severity_score'] ?? 1) == $i) echo 'selected'; ?>><?php echo $i; ?> - <?php echo t(['negligible','minor','moderate','serious','critical'][$i-1]); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <!-- MODULE : PIÈCES DÉTACHÉES -->
                <div class="form-card mt-4" style="box-shadow: none; border: 1px solid #dee2e6;">
                    <div class="form-card-header" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                        <i class="fas fa-tools"></i> <?php echo t('spare_parts_required'); ?>
                        <button type="button" class="btn btn-success-sm float-end" onclick="addPartRow()">
                            <i class="fas fa-plus"></i> <?php echo t('add_part'); ?>
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="info-message">
                            <i class="fas fa-info-circle"></i> 
                            <?php echo t('spare_parts_help'); ?>
                            <small class="d-block mt-1 text-muted"><?php echo t('spare_parts_help_detail'); ?></small>
                        </div>
                        
                        <!-- En-têtes des colonnes -->
                        <div class="row mb-2" style="font-weight: 600; font-size: 13px; color: #495057; padding: 0 10px;">
                            <div class="col-md-4"><?php echo t('part_name_reference'); ?></div>
                            <div class="col-md-2"><?php echo t('quantity_needed'); ?></div>
                            <div class="col-md-2 text-muted"><?php echo t('location_stock'); ?></div>
                            <div class="col-md-2 text-muted"><?php echo t('stock_available'); ?></div>
                            <div class="col-md-2 text-end"><?php echo t('actions'); ?></div>
                        </div>
                        
                        <div id="partsContainer">
                            <?php 
                            // Afficher les lignes pour les pièces déjà associées
                            $rowIndex = 0;
                            if (!empty($associatedParts)) {
                                foreach ($associatedParts as $partId => $quantityNeeded) {
                                    $partInfo = null;
                                    foreach ($parts as $p) {
                                        if ($p['id'] == $partId) {
                                            $partInfo = $p;
                                            break;
                                        }
                                    }
                                    if ($partInfo) {
                                        $location = htmlspecialchars($partInfo['location'] ?? '');
                                        $stock = $partInfo['quantity'];
                                        ?>
                                        <div class="part-row" id="partRow_<?php echo $rowIndex; ?>">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <select name="part_id[]" class="form-select part-select" onchange="updatePartInfo(this, <?php echo $rowIndex; ?>)">
                                                        <option value="">-- <?php echo t('select_part'); ?> --</option>
                                                        <?php foreach ($parts as $part): ?>
                                                            <option value="<?php echo $part['id']; ?>" 
                                                                    data-location="<?php echo htmlspecialchars($part['location'] ?? ''); ?>"
                                                                    data-stock="<?php echo $part['quantity']; ?>"
                                                                    <?php if ($part['id'] == $partId) echo 'selected'; ?>>
                                                                <?php echo htmlspecialchars($part['name'] . ' (' . $part['part_number'] . ')'); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <input type="number" name="quantity[]" class="form-control" min="1" value="<?php echo $quantityNeeded; ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <span class="text-muted-sm part-location" id="partLocation_<?php echo $rowIndex; ?>"><?php echo $location ?: '-'; ?></span>
                                                </div>
                                                <div class="col-md-2">
                                                    <span class="text-muted-sm part-stock" id="partStock_<?php echo $rowIndex; ?>"><?php echo $stock; ?></span>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-danger-sm" onclick="removePartRow(<?php echo $rowIndex; ?>)">
                                                        <i class="fas fa-minus-circle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        $rowIndex++;
                                    }
                                }
                            }
                            // Si aucune pièce associée, afficher une ligne vide par défaut
                            if (empty($associatedParts)) {
                            ?>
                            <div class="part-row" id="partRow_0">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select name="part_id[]" class="form-select part-select" onchange="updatePartInfo(this, 0)">
                                            <option value="">-- <?php echo t('select_part'); ?> --</option>
                                            <?php foreach ($parts as $part): ?>
                                                <option value="<?php echo $part['id']; ?>" 
                                                        data-location="<?php echo htmlspecialchars($part['location'] ?? ''); ?>"
                                                        data-stock="<?php echo $part['quantity']; ?>">
                                                    <?php echo htmlspecialchars($part['name'] . ' (' . $part['part_number'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="quantity[]" class="form-control" min="1" value="1">
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted-sm part-location" id="partLocation_0">-</span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="text-muted-sm part-stock" id="partStock_0">-</span>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button type="button" class="btn btn-danger-sm" onclick="removePartRow(0)" style="display: none;">
                                            <i class="fas fa-minus-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php 
                            $rowIndex = 1;
                            }
                            ?>
                        </div>
                        
                        <input type="hidden" name="part_count" id="partCount" value="<?php echo $rowIndex; ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let rowCounter = <?php echo !empty($associatedParts) ? count($associatedParts) : 1; ?>;

function addPartRow() {
    const container = document.getElementById('partsContainer');
    const rowId = rowCounter++;
    
    const row = document.createElement('div');
    row.className = 'part-row';
    row.id = 'partRow_' + rowId;
    row.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <label class="form-label" style="font-size: 13px;"><?php echo t('part_name_reference'); ?></label>
                <select name="part_id[]" class="form-select part-select" onchange="updatePartInfo(this, ${rowId})">
                    <option value="">-- <?php echo t('select_part'); ?> --</option>
                    <?php foreach ($parts as $part): ?>
                        <option value="<?php echo $part['id']; ?>" 
                                data-location="<?php echo htmlspecialchars($part['location'] ?? ''); ?>"
                                data-stock="<?php echo $part['quantity']; ?>">
                            <?php echo htmlspecialchars($part['name'] . ' (' . $part['part_number'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size: 13px;"><?php echo t('quantity_needed'); ?></label>
                <input type="number" name="quantity[]" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size: 13px;"><?php echo t('location_stock'); ?></label>
                <span class="text-muted-sm part-location" id="partLocation_${rowId}">-</span>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size: 13px;"><?php echo t('stock_available'); ?></label>
                <span class="text-muted-sm part-stock" id="partStock_${rowId}">-</span>
            </div>
            <div class="col-md-2 text-end">
                <label class="form-label" style="font-size: 13px;">&nbsp;</label>
                <button type="button" class="btn btn-danger-sm" onclick="removePartRow(${rowId})">
                    <i class="fas fa-minus-circle"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(row);
    document.getElementById('partCount').value = rowCounter;
    checkRemoveButtons();
}

function removePartRow(id) {
    const row = document.getElementById('partRow_' + id);
    if (row) {
        row.remove();
        const count = document.getElementById('partCount');
        count.value = parseInt(count.value) - 1;
        checkRemoveButtons();
    }
}

function updatePartInfo(select, rowId) {
    const selectedOption = select.options[select.selectedIndex];
    const locationSpan = document.getElementById('partLocation_' + rowId);
    const stockSpan = document.getElementById('partStock_' + rowId);
    
    if (selectedOption && selectedOption.value) {
        const location = selectedOption.dataset.location || '-';
        const stock = selectedOption.dataset.stock || '-';
        locationSpan.textContent = location;
        stockSpan.textContent = stock;
    } else {
        locationSpan.textContent = '-';
        stockSpan.textContent = '-';
    }
}

function checkRemoveButtons() {
    const rows = document.querySelectorAll('.part-row');
    rows.forEach((row, index) => {
        const btn = row.querySelector('.btn-danger-sm');
        if (btn) {
            btn.style.display = (index === 0 && rows.length === 1) ? 'none' : 'inline-block';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    checkRemoveButtons();
});
</script>