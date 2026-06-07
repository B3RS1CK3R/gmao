<?php
// pages/equipment_add.php - Formulaire complet d'ajout d'équipement
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
    .info-message { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; }
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
            <div class="info-message">
                <i class="fas fa-info-circle"></i> <?php echo t('add_equipment_info'); ?>
                <small class="d-block mt-1 text-muted"><?php echo t('save_before_adding_documents'); ?></small>
            </div>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('code'); ?> <span class="text-danger">*</span></label><input type="text" name="code" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('name'); ?> <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('type'); ?></label><input type="text" name="type" class="form-control" placeholder="<?php echo t('type_help'); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('location'); ?></label><input type="text" name="location" class="form-control" placeholder="<?php echo t('location_help'); ?>"></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('supplier'); ?></label><input type="text" name="supplier" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('purchase_date'); ?></label><input type="date" name="purchase_date" class="form-control"></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('warranty_end'); ?></label><input type="date" name="warranty_end" class="form-control"></div>
                    <div class="col-md-12 mb-3"><label class="form-label"><?php echo t('technical_specs'); ?></label><textarea name="technical_specs" class="form-control" rows="3" placeholder="<?php echo t('technical_specs_help'); ?>"></textarea></div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('probability_score'); ?></label>
                        <select name="probability_score" class="form-select">
                            <option value="1">1 - <?php echo t('very_low'); ?></option><option value="2">2 - <?php echo t('low'); ?></option>
                            <option value="3">3 - <?php echo t('medium'); ?></option><option value="4">4 - <?php echo t('high'); ?></option>
                            <option value="5">5 - <?php echo t('very_high'); ?></option>
                        </select>
                        <small class="text-muted"><?php echo t('probability_help'); ?></small>
                    </div>
                    <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('severity_score'); ?></label>
                        <select name="severity_score" class="form-select">
                            <option value="1">1 - <?php echo t('negligible'); ?></option><option value="2">2 - <?php echo t('minor'); ?></option>
                            <option value="3">3 - <?php echo t('moderate'); ?></option><option value="4">4 - <?php echo t('serious'); ?></option>
                            <option value="5">5 - <?php echo t('critical'); ?></option>
                        </select>
                        <small class="text-muted"><?php echo t('severity_help'); ?></small>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                    <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Module Documents (après le formulaire principal) -->
<div class="form-card mt-4">
    <div class="form-card-header" style="background: linear-gradient(135deg, #17a2b8, #138496);">
        <i class="fas fa-paperclip"></i> <?php echo t('documents'); ?>
        <span class="badge bg-light text-dark ms-2">0 <?php echo t('files'); ?></span>
    </div>
    <div class="card-body p-4">
        <div class="info-message">
            <i class="fas fa-info-circle"></i> 
            <?php echo t('save_before_adding_documents'); ?>
            <small class="d-block mt-1 text-muted"><?php echo t('doc_path_help'); ?></small>
        </div>
        
        <div class="mt-3">
            <label class="form-label"><?php echo t('document_path'); ?></label>
            <div class="input-group">
                <input type="text" name="document_path" id="document_path" class="form-control" placeholder="C:\dossiers\manuel.pdf ou https://lien.com/doc.pdf">
                <button type="button" class="btn btn-outline-secondary" onclick="browseFile()">
                    <i class="fas fa-folder-open"></i> <?php echo t('browse'); ?>
                </button>
            </div>
            <small class="text-muted"><?php echo t('doc_path_placeholder'); ?></small>
        </div>
        <div class="mt-3 form-check">
            <input type="checkbox" name="add_document" value="1" class="form-check-input" id="add_document_check">
            <label class="form-check-label" for="add_document_check" style="color: red; font-weight: bold;"><?php echo t('add_document_after_creation'); ?></label>
        </div>
        <small class="text-muted d-block mt-2"><?php echo t('add_document_info'); ?></small>
    </div>
</div>

<script>
function browseFile() {
    // Créer un input file invisible
    var input = document.createElement('input');
    input.type = 'file';
    input.onchange = function(e) {
        var file = e.target.files[0];
        if (file) {
            document.getElementById('document_path').value = file.path || file.name;
            // Si le navigateur ne supporte pas file.path, on utilise le nom
        }
    };
    input.click();
}
</script>