<?php
// pages/stock_add.php - Ajouter une pièce détachée
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (!in_array($_SESSION['role'] ?? '', ['admin', 'supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
        $error = t('csrf_invalid');
    } else {
        $sql = "INSERT INTO spare_parts (part_number, name, quantity, min_quantity, location, supplier, unit_price, last_restock, documentation_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            $_POST['documentation_path'] ?? null
        ]);
        if ($result) {
            logUserAction($_SESSION['user_id'], 'stock_created', "Part created: {$_POST['part_number']}");
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
    .form-card-header { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .btn-primary { background: linear-gradient(135deg, #28a745, #1e7e34); border: none; border-radius: 8px; padding: 8px 20px; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_part'); ?></h2>
        <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-plus-circle"></i> <?php echo t('add_part'); ?></div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_input() ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('part_number'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="part_number" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('quantity'); ?></label>
                        <input type="number" name="quantity" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('min_quantity'); ?></label>
                        <input type="number" name="min_quantity" class="form-control" value="5">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('location_stock'); ?></label>
                        <input type="text" name="location" class="form-control" placeholder="<?php echo t('location_placeholder'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label"><?php echo t('unit_price'); ?> (€)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('supplier'); ?></label>
                        <input type="text" name="supplier" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?php echo t('last_restock'); ?></label>
                        <input type="date" name="last_restock" class="form-control">
                    </div>
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                    <div class="col-md-12 mb-3">
                        <label class="form-label"><i class="fas fa-folder-open"></i> <?php echo t('documentation'); ?></label>
                        <input type="text" name="documentation_path" class="form-control" placeholder="<?php echo t('doc_placeholder'); ?>">
                        <small class="text-muted"><?php echo t('doc_help'); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                    <a href="?page=stock" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>