<?php
// pages/technician_edit.php - Formulaire d'édition (sans traitement POST)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'supervisor') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=technicians');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
$stmt->execute([$id]);
$tech = $stmt->fetch();
if (!$tech) {
    echo "<div class='alert alert-danger'>" . t('save_error') . "</div>";
    return;
}

// Compétences existantes
$stmtSkills = $pdo->prepare("SELECT * FROM technician_skills WHERE technician_id = ?");
$stmtSkills->execute([$id]);
$existingSkills = $stmtSkills->fetchAll();

$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<style>
    .form-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .form-card-header { background: linear-gradient(135deg, #fd7e14, #e06a0a); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #ddd; padding: 10px 12px; }
    .skill-row { background: #f8f9fa; padding: 10px; border-radius: 8px; margin-bottom: 10px; }
    .btn-add-skill { background: #28a745; color: white; border: none; padding: 5px 15px; border-radius: 5px; font-size: 12px; }
    .btn-remove-skill { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 5px; font-size: 12px; }
    .btn-warning { background: #fd7e14; border: none; border-radius: 8px; padding: 8px 20px; color: white; }
    .btn-secondary { background: #6c757d; border: none; border-radius: 8px; padding: 8px 20px; }
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-card">
                <div class="form-card-header"><i class="fas fa-user-edit"></i> <?php echo t('edit_technician'); ?> : <?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" id="editTechnicianForm">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('employee_id'); ?> <span class="text-danger">*</span></label><input type="text" name="employee_id" class="form-control" value="<?php echo htmlspecialchars($tech['employee_id']); ?>" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('firstname'); ?> <span class="text-danger">*</span></label><input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($tech['firstname']); ?>" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('lastname'); ?> <span class="text-danger">*</span></label><input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($tech['lastname']); ?>" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('phone'); ?></label><input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($tech['phone']); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('email'); ?></label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($tech['email']); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('specialty'); ?></label><input type="text" name="specialty" class="form-control" value="<?php echo htmlspecialchars($tech['specialty']); ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('hire_date'); ?></label><input type="date" name="hire_date" class="form-control" value="<?php echo $tech['hire_date']; ?>"></div>
                            <div class="col-md-6 mb-3"><label class="form-label"><?php echo t('status'); ?></label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php if($tech['status'] == 'active') echo 'selected'; ?>>🟢 <?php echo t('active'); ?></option>
                                    <option value="inactive" <?php if($tech['status'] == 'inactive') echo 'selected'; ?>>🔴 <?php echo t('inactive'); ?></option>
                                    <option value="on_leave" <?php if($tech['status'] == 'on_leave') echo 'selected'; ?>>🟡 <?php echo t('on_leave'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="form-label"><i class="fas fa-tools"></i> <?php echo t('skills'); ?></label>
                            <div id="skills-container">
                                <?php if(empty($existingSkills)): ?>
                                    <div class="skill-row" data-skill-index="0">
                                        <div class="row align-items-center">
                                            <div class="col-md-5"><input type="text" name="skills[0][equipment_type]" class="form-control" placeholder="Equipment type (ex: Pump, Motor)"></div>
                                            <div class="col-md-4"><select name="skills[0][skill_level]" class="form-select"><option value="beginner">🌱 Beginner</option><option value="intermediate">📌 Intermediate</option><option value="advanced">📈 Advanced</option><option value="expert">🏆 Expert</option></select></div>
                                            <div class="col-md-2"><label class="form-check-label"><input type="checkbox" name="skills[0][certified]" value="1"> Certified</label></div>
                                            <div class="col-md-1"><button type="button" class="btn-remove-skill" onclick="removeSkillRow(this)">✕</button></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($existingSkills as $idx => $skill): ?>
                                    <div class="skill-row" data-skill-index="<?php echo $idx; ?>">
                                        <div class="row align-items-center">
                                            <div class="col-md-5"><input type="text" name="skills[<?php echo $idx; ?>][equipment_type]" class="form-control" value="<?php echo htmlspecialchars($skill['equipment_type']); ?>"></div>
                                            <div class="col-md-4"><select name="skills[<?php echo $idx; ?>][skill_level]" class="form-select"><option value="beginner" <?php echo $skill['skill_level'] == 'beginner' ? 'selected' : ''; ?>>🌱 Beginner</option><option value="intermediate" <?php echo $skill['skill_level'] == 'intermediate' ? 'selected' : ''; ?>>📌 Intermediate</option><option value="advanced" <?php echo $skill['skill_level'] == 'advanced' ? 'selected' : ''; ?>>📈 Advanced</option><option value="expert" <?php echo $skill['skill_level'] == 'expert' ? 'selected' : ''; ?>>🏆 Expert</option></select></div>
                                            <div class="col-md-2"><label class="form-check-label"><input type="checkbox" name="skills[<?php echo $idx; ?>][certified]" value="1" <?php echo $skill['certified'] ? 'checked' : ''; ?>> Certified</label></div>
                                            <div class="col-md-1"><button type="button" class="btn-remove-skill" onclick="removeSkillRow(this)">✕</button></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="btn-add-skill mt-2" onclick="addSkillRow()"><i class="fas fa-plus"></i> Add Skill</button>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> <?php echo t('update'); ?></button>
                            <a href="?page=technicians" class="btn btn-secondary"><i class="fas fa-times"></i> <?php echo t('cancel'); ?></a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let skillCounter = <?php echo count($existingSkills); ?>;
function addSkillRow() {
    const container = document.getElementById('skills-container');
    const newRow = document.createElement('div');
    newRow.className = 'skill-row';
    newRow.setAttribute('data-skill-index', skillCounter);
    newRow.innerHTML = `<div class="row align-items-center"><div class="col-md-5"><input type="text" name="skills[${skillCounter}][equipment_type]" class="form-control" placeholder="Equipment type (ex: Pump, Motor)"></div><div class="col-md-4"><select name="skills[${skillCounter}][skill_level]" class="form-select"><option value="beginner">🌱 Beginner</option><option value="intermediate">📌 Intermediate</option><option value="advanced">📈 Advanced</option><option value="expert">🏆 Expert</option></select></div><div class="col-md-2"><label class="form-check-label"><input type="checkbox" name="skills[${skillCounter}][certified]" value="1"> Certified</label></div><div class="col-md-1"><button type="button" class="btn-remove-skill" onclick="removeSkillRow(this)">✕</button></div></div>`;
    container.appendChild(newRow);
    skillCounter++;
}
function removeSkillRow(button) { button.closest('.skill-row').remove(); }
</script>