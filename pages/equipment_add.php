<?php
// pages/equipment_add.php - Formulaire d'ajout (sans traitement)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}
$error = isset($_GET['error']) ? $_GET['error'] : '';
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
        <h2><i class="fas fa-plus-circle"></i> <?php echo t('add_equipment'); ?></h2>
        <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="form-card">
        <div class="form-card-header"><i class="fas fa-plus-circle"></i> <?php echo t('add_equipment'); ?></div>
        <div class="card-body p-4">
            <form method="POST">
                <!-- Tous les champs comme avant -->
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label><input type="text" name="code" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <!-- ... (copiez tous les champs depuis votre equipment_add.php original) ... -->
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                    <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>