<?php
// network_info_modal.php - Contenu de la modale
require_once __DIR__ . '/../includes/lang.php';
$ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.x';
$port = $_SERVER['SERVER_PORT'] ?? 80;
$url = "http://{$ip}:{$port}/gmao/index.php?page=mobile_dashboard";
?>

<div class="text-center">
    <div class="mb-3">
        <div id="qrcodeModal" style="display: flex; justify-content: center;"></div>
    </div>
    
    <div class="alert alert-info">
        <strong><i class="fas fa-wifi"></i> <?= t('url_to_scan') ?> :</strong><br>
        <code style="word-break: break-all; font-size: 12px;"><?= $url ?></code>
    </div>
    
    <div class="row g-2">
        <div class="col-6">
            <button class="btn btn-outline-primary btn-sm w-100" onclick="copyToClipboard('<?= $url ?>')">
                <i class="fas fa-copy"></i> <?= t('copy_url') ?>
            </button>
        </div>
        <div class="col-6">
            <button class="btn btn-outline-success btn-sm w-100" onclick="window.open('<?= $url ?>', '_blank')">
                <i class="fas fa-external-link-alt"></i> <?= t('test') ?>
            </button>
        </div>
    </div>
    
    <hr class="my-3">
    
    <div class="small text-muted">
        <i class="fas fa-info-circle"></i> 
        <?= t('wifi_instruction') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById("qrcodeModal"), {
    text: "<?= $url ?>",
    width: 180,
    height: 180
});

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('<?= t('url_copied') ?>');
}
</script>