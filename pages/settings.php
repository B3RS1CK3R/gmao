<?php
// pages/settings.php - Configuration du système (admin only)
if ($_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$message = '';
$error = '';

$stmt = $pdo->query("SELECT * FROM task_format_settings");
$formats = [];
while ($row = $stmt->fetch()) {
    $formats[$row['type']] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_interv'])) {
        $interv_prefix = trim($_POST['interv_prefix']);
        $interv_year = isset($_POST['interv_use_year']) ? 1 : 0;
        $interv_month = isset($_POST['interv_use_month']) ? 1 : 0;
        $interv_digits = (int)$_POST['interv_digits'];
        $reset_year = isset($_POST['interv_reset_year']) ? 1 : 0;
        $reset_month = isset($_POST['interv_reset_month']) ? 1 : 0;
        $pdo->prepare("UPDATE task_format_settings SET prefix = ?, use_year = ?, use_month = ?, digits = ?, reset_on_year_change = ?, reset_on_month_change = ? WHERE type = 'intervention'")
            ->execute([$interv_prefix, $interv_year, $interv_month, $interv_digits, $reset_year, $reset_month]);
        $message = t('save_success');
    }
    if (isset($_POST['save_prev'])) {
        $prev_prefix = trim($_POST['prev_prefix']);
        $prev_year = isset($_POST['prev_use_year']) ? 1 : 0;
        $prev_month = isset($_POST['prev_use_month']) ? 1 : 0;
        $prev_digits = (int)$_POST['prev_digits'];
        $reset_year = isset($_POST['prev_reset_year']) ? 1 : 0;
        $reset_month = isset($_POST['prev_reset_month']) ? 1 : 0;
        $pdo->prepare("UPDATE task_format_settings SET prefix = ?, use_year = ?, use_month = ?, digits = ?, reset_on_year_change = ?, reset_on_month_change = ? WHERE type = 'preventive'")
            ->execute([$prev_prefix, $prev_year, $prev_month, $prev_digits, $reset_year, $reset_month]);
        $message = t('save_success');
    }
    // Recharger les données
    $stmt = $pdo->query("SELECT * FROM task_format_settings");
    $formats = [];
    while ($row = $stmt->fetch()) $formats[$row['type']] = $row;
}
?>

<style>
    .config-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
    .config-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; font-weight: bold; }
    .form-label { font-weight: 500; margin-bottom: 5px; }
    .form-check-input { margin-right: 8px; }
    .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 8px; padding: 8px 20px; }
    .preview-code { background: #f8f9fa; padding: 8px 12px; border-radius: 8px; font-family: monospace; font-size: 14px; margin-top: 10px; }
    .info-text { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 8px 12px; border-radius: 8px; margin: 10px 0; font-size: 13px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cog"></i> <?php echo t('configuration'); ?></h2>
    </div>
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <div class="row">
        <!-- Interventions -->
        <div class="col-md-6">
            <div class="config-card">
                <div class="config-header"><i class="fas fa-tasks"></i> <?php echo t('task_numbers_interventions'); ?></div>
                <div class="card-body p-4">
                    <form method="POST" id="form_interv">
                        <div class="mb-3"><label class="form-label"><?php echo t('prefix'); ?> (<?php echo t('max_4_chars'); ?>)</label><input type="text" name="interv_prefix" class="form-control" maxlength="4" value="<?php echo htmlspecialchars($formats['intervention']['prefix']); ?>" required></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_use_year" class="form-check-input" id="interv_year" <?php echo $formats['intervention']['use_year'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_year'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_use_month" class="form-check-input" id="interv_month" <?php echo $formats['intervention']['use_month'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_month'); ?></label></div>
                        <div class="mb-3"><label class="form-label"><?php echo t('digits_counter'); ?></label><select name="interv_digits" class="form-select">
                            <option value="4" <?php echo $formats['intervention']['digits'] == 4 ? 'selected' : ''; ?>>4 <?php echo t('digits'); ?> (0001)</option>
                            <option value="5" <?php echo $formats['intervention']['digits'] == 5 ? 'selected' : ''; ?>>5 <?php echo t('digits'); ?> (00001)</option>
                            <option value="6" <?php echo $formats['intervention']['digits'] == 6 ? 'selected' : ''; ?>>6 <?php echo t('digits'); ?> (000001)</option>
                        </select></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_reset_year" class="form-check-input" id="interv_reset_year" <?php echo $formats['intervention']['reset_on_year_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_year_change'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="interv_reset_month" class="form-check-input" id="interv_reset_month" <?php echo $formats['intervention']['reset_on_month_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_month_change'); ?></label></div>
                        <div class="info-text"><i class="fas fa-info-circle"></i> <?php echo t('reset_info'); ?></div>
                        <div class="mt-3">
                            <button type="submit" name="save_interv" class="btn btn-primary"><?php echo t('save'); ?></button>
                            <button type="button" class="btn btn-danger ms-2" onclick="resetCounter('intervention')"><?php echo t('reset_counter'); ?></button>
                        </div>
                    </form>
                    <div class="mt-3"><strong><?php echo t('preview_next'); ?> :</strong><div class="preview-code" id="preview_interv"><?php echo htmlspecialchars(generateTaskNumber($pdo, 'intervention', true)); ?></div></div>
                </div>
            </div>
        </div>
        <!-- Préventives -->
        <div class="col-md-6">
            <div class="config-card">
                <div class="config-header"><i class="fas fa-calendar-check"></i> <?php echo t('task_numbers_preventive'); ?></div>
                <div class="card-body p-4">
                    <form method="POST" id="form_prev">
                        <div class="mb-3"><label class="form-label"><?php echo t('prefix'); ?> (<?php echo t('max_4_chars'); ?>)</label><input type="text" name="prev_prefix" class="form-control" maxlength="4" value="<?php echo htmlspecialchars($formats['preventive']['prefix']); ?>" required></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_use_year" class="form-check-input" id="prev_year" <?php echo $formats['preventive']['use_year'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_year'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_use_month" class="form-check-input" id="prev_month" <?php echo $formats['preventive']['use_month'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('include_month'); ?></label></div>
                        <div class="mb-3"><label class="form-label"><?php echo t('digits_counter'); ?></label><select name="prev_digits" class="form-select">
                            <option value="4" <?php echo $formats['preventive']['digits'] == 4 ? 'selected' : ''; ?>>4 <?php echo t('digits'); ?> (0001)</option>
                            <option value="5" <?php echo $formats['preventive']['digits'] == 5 ? 'selected' : ''; ?>>5 <?php echo t('digits'); ?> (00001)</option>
                            <option value="6" <?php echo $formats['preventive']['digits'] == 6 ? 'selected' : ''; ?>>6 <?php echo t('digits'); ?> (000001)</option>
                        </select></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_reset_year" class="form-check-input" id="prev_reset_year" <?php echo $formats['preventive']['reset_on_year_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_year_change'); ?></label></div>
                        <div class="mb-3 form-check"><input type="checkbox" name="prev_reset_month" class="form-check-input" id="prev_reset_month" <?php echo $formats['preventive']['reset_on_month_change'] ? 'checked' : ''; ?>><label class="form-check-label"><?php echo t('reset_counter_on_month_change'); ?></label></div>
                        <div class="info-text"><i class="fas fa-info-circle"></i> <?php echo t('reset_info'); ?></div>
                        <div class="mt-3">
                            <button type="submit" name="save_prev" class="btn btn-primary"><?php echo t('save'); ?></button>
                            <button type="button" class="btn btn-danger ms-2" onclick="resetCounter('preventive')"><?php echo t('reset_counter'); ?></button>
                        </div>
                    </form>
                    <div class="mt-3"><strong><?php echo t('preview_next'); ?> :</strong><div class="preview-code" id="preview_prev"><?php echo htmlspecialchars(generateTaskNumber($pdo, 'preventive', true)); ?></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour réinitialisation -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-key"></i> <?php echo t('reset_counter_confirm'); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?php echo t('reset_counter_warning'); ?></p>
                <div class="mb-3">
                    <label class="form-label"><?php echo t('confirm_password'); ?></label>
                    <input type="password" id="resetPassword" class="form-control" required autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('cancel'); ?></button>
                <button type="button" class="btn btn-danger" id="confirmResetBtn"><?php echo t('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
let currentResetType = '';

function resetCounter(type) {
    currentResetType = type;
    document.getElementById('resetPassword').value = '';
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}

document.getElementById('confirmResetBtn').addEventListener('click', function() {
    const password = document.getElementById('resetPassword').value;
    if (!password) {
        alert("<?php echo t('password_required'); ?>");
        return;
    }
    fetch('api/reset_counter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ type: currentResetType, password: password })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erreur : ' + data.error);
        }
    })
    .catch(err => { alert('Erreur réseau'); });
});

// Aperçu dynamique
document.addEventListener('DOMContentLoaded', function() {
    function getBasePath() {
        let path = window.location.pathname;
        let match = path.match(/^\/[^\/]*/);
        return match ? match[0] : '';
    }
    const basePath = getBasePath();

    function updatePreview(type) {
        let prefix, useYear, useMonth, digits, previewDiv;
        if (type === 'intervention') {
            prefix = document.querySelector('#form_interv input[name="interv_prefix"]').value;
            useYear = document.querySelector('#form_interv input[name="interv_use_year"]').checked ? 1 : 0;
            useMonth = document.querySelector('#form_interv input[name="interv_use_month"]').checked ? 1 : 0;
            digits = document.querySelector('#form_interv select[name="interv_digits"]').value;
            previewDiv = document.getElementById('preview_interv');
        } else {
            prefix = document.querySelector('#form_prev input[name="prev_prefix"]').value;
            useYear = document.querySelector('#form_prev input[name="prev_use_year"]').checked ? 1 : 0;
            useMonth = document.querySelector('#form_prev input[name="prev_use_month"]').checked ? 1 : 0;
            digits = document.querySelector('#form_prev select[name="prev_digits"]').value;
            previewDiv = document.getElementById('preview_prev');
        }
        if (!previewDiv) return;

        if (useMonth && !useYear) {
            alert("<?php echo t('month_requires_year'); ?>");
            if (type === 'intervention') document.querySelector('#form_interv input[name="interv_use_month"]').checked = false;
            else document.querySelector('#form_prev input[name="prev_use_month"]').checked = false;
            updatePreview(type);
            return;
        }

        fetch(basePath + '/api/preview_task_number.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                type: type,
                prefix: prefix,
                use_year: useYear,
                use_month: useMonth,
                digits: digits
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.preview) previewDiv.innerHTML = data.preview;
            else if (data.error) previewDiv.innerHTML = 'Erreur : ' + data.error;
        })
        .catch(err => { previewDiv.innerHTML = 'Erreur AJAX'; console.error(err); });
    }

    const intervFields = document.querySelectorAll('#form_interv input, #form_interv select');
    intervFields.forEach(field => field.addEventListener('change', () => updatePreview('intervention')));
    const prevFields = document.querySelectorAll('#form_prev input, #form_prev select');
    prevFields.forEach(field => field.addEventListener('change', () => updatePreview('preventive')));

    updatePreview('intervention');
    updatePreview('preventive');
});
</script>