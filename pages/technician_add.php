<?php
// pages/technician_add.php - Formulaire d'ajout de technicien (sans traitement POST)
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-card">
                <div class="form-card-header"><i class="fas fa-user-plus"></i> <?php echo t('add_technician'); ?></div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('employee_id'); ?> <span class="text-danger">*</span></label><input type="text" name="employee_id" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('firstname'); ?> <span class="text-danger">*</span></label><input type="text" name="firstname" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('lastname'); ?> <span class="text-danger">*</span></label><input type="text" name="lastname" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('phone'); ?></label><input type="tel" name="phone" class="form-control" placeholder="06 12 34 56 78"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('email'); ?></label><input type="email" name="email" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('specialty'); ?></label><input type="text" name="specialty" class="form-control" placeholder="<?php echo t('specialty_placeholder'); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('hire_date'); ?></label><input type="date" name="hire_date" class="form-control"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('status'); ?></label>
                                <select name="status" class="form-select">
                                    <option value="active">🟢 <?php echo t('active'); ?></option>
                                    <option value="inactive">🔴 <?php echo t('inactive'); ?></option>
                                    <option value="on_leave">🟡 <?php echo t('on_leave'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo t('create'); ?></button>
                            <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>