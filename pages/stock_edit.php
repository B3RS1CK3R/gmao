<?php
// pages/stock_edit.php - Éditer une pièce détachée
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=stock');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM spare_parts WHERE id = ?");
$stmt->execute([$id]);
$part = $stmt->fetch();
if (!$part) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } else {
        $sql = "UPDATE spare_parts SET 
                part_number = ?, 
                name = ?, 
                quantity = ?, 
                min_quantity = ?, 
                location = ?, 
                supplier = ?, 
                unit_price = ?, 
                last_restock = ?,
                documentation_path = ?
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $_POST['part_number'],
            $_POST['name'],
            $_POST['quantity'],
            $_POST['min_quantity'],
            $_POST['location'],
            $_POST['supplier'],
            $_POST['unit_price'],
            $_POST['last_restock'],
            $_POST['documentation_path'] ?? null,
            $id
        ]);
        if ($result) {
            logUserAction($_SESSION['user_id'], 'stock_updated', "Part ID: $id modified");
            header('Location: ?page=stock&msg=' . urlencode(t('save_success')));
            exit();
        } else {
            $error = t('save_error');
        }
    }
}
?>
<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #fd7e14, #e06a0a); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-folder { background: #17a2b8; color: white; border: none; border-radius: 8px; padding: 8px 15px; cursor: pointer; }
    .btn-folder:hover { background: #138496; }
    .doc-preview { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; font-size: 12px; }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-edit"></i> <?php echo t('edit_part'); ?> : <?php echo htmlspecialchars($part['part_number']); ?></h2>
        <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-edit"></i> <?php echo t('edit_part'); ?></div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_input() ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('part_number'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="part_number" class="form-control" value="<?php echo htmlspecialchars($part['part_number']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($part['name']); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('quantity'); ?></label>
                        <input type="number" name="quantity" class="form-control" value="<?php echo $part['quantity']; ?>" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('min_quantity'); ?></label>
                        <input type="number" name="min_quantity" class="form-control" value="<?php echo $part['min_quantity']; ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('location_stock'); ?></label>
                        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($part['location']); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('unit_price'); ?> (€)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" value="<?php echo $part['unit_price']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('supplier'); ?></label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($part['supplier']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('last_restock'); ?></label>
                        <input type="date" name="last_restock" class="form-control" value="<?php echo $part['last_restock']; ?>">
                    </div>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><i class="fas fa-folder-open"></i> <?php echo t('documentation'); ?></label>
                        <div class="d-flex gap-2">
                            <input type="text" name="documentation_path" class="form-control" value="<?php echo htmlspecialchars($part['documentation_path'] ?? ''); ?>" placeholder="<?php echo t('doc_placeholder'); ?>">
                            <?php if (!empty($part['documentation_path'])): ?>
                            <button type="button" class="btn-folder" onclick="openDocumentation('<?php echo addslashes($part['documentation_path']); ?>')" title="<?php echo t('open_doc'); ?>">
                                <i class="fas fa-folder-open"></i> <?php echo t('open'); ?>
                            </button>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted"><?php echo t('doc_help'); ?></small>
                    </div>
                    <?php else: ?>
                    <?php if (!empty($part['documentation_path'])): ?>
                    <div class="col-md-12 mb-3">
                        <div class="doc-preview">
                            <i class="fas fa-link"></i> <strong><?php echo t('documentation'); ?> :</strong><br>
                            <a href="#" onclick="openDocumentation('<?php echo addslashes($part['documentation_path']); ?>'); return false;">
                                <i class="fas fa-file-alt"></i> <?php echo basename($part['documentation_path']); ?>
                            </a>
                            <br><small class="text-muted"><?php echo htmlspecialchars($part['documentation_path']); ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                    <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
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