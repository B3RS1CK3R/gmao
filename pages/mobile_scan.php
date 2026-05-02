<?php
// pages/mobile_scan.php - Scanner QR code
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

require_once __DIR__ . '/../includes/lang.php';

// Récupérer les équipements récents pour suggestion
$stmt = $pdo->query("SELECT id, code, name FROM equipment WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");
$recent_equipment = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <title><?php echo t('scan_qr_code'); ?> - GMAO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.4/html5-qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #000; margin: 0; padding: 0; height: 100vh; display: flex; flex-direction: column; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .scanner-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; position: relative; }
        .back-btn { position: absolute; left: 15px; top: 20px; color: white; font-size: 24px; text-decoration: none; }
        .back-btn:hover { color: white; }
        .scanner-title { font-size: 18px; font-weight: bold; margin: 0; }
        .scanner-subtitle { font-size: 12px; opacity: 0.8; margin-top: 5px; }
        #reader { width: 100%; flex: 1; background: #000; min-height: 300px; }
        .scan-result { position: fixed; bottom: 0; left: 0; right: 0; background: white; padding: 20px; border-radius: 20px 20px 0 0; transform: translateY(100%); transition: transform 0.3s ease; z-index: 1000; max-height: 80vh; overflow-y: auto; }
        .scan-result.show { transform: translateY(0); }
        .scan-result-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
        .scan-result-title { font-weight: bold; margin: 0; }
        .close-result { background: none; border: none; font-size: 20px; cursor: pointer; color: #999; }
        .manual-input { padding: 20px; background: white; border-top: 1px solid #eee; }
        .instruction { text-align: center; color: white; padding: 15px; background: rgba(0,0,0,0.5); font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .recent-equipment { padding: 15px; background: white; border-top: 1px solid #eee; }
        .recent-title { font-size: 12px; color: #666; margin-bottom: 10px; }
        .recent-item { display: inline-block; background: #f0f0f0; padding: 6px 12px; border-radius: 20px; font-size: 12px; margin: 3px; text-decoration: none; color: #333; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: white; display: flex; justify-content: space-around; padding: 10px 0; box-shadow: 0 -2px 10px rgba(0,0,0,0.1); z-index: 100; }
        .nav-item { text-align: center; padding: 8px 0; color: #888; text-decoration: none; flex: 1; transition: color 0.2s; }
        .nav-item.active { color: #667eea; }
        .nav-item i { font-size: 22px; display: block; }
        .nav-label { font-size: 10px; margin-top: 4px; }
        .equipment-detail { margin-top: 10px; }
        .equipment-detail table { width: 100%; font-size: 13px; }
        .equipment-detail td { padding: 5px 0; }
        .btn-action { margin-top: 15px; display: flex; gap: 10px; }
        .btn-action .btn { flex: 1; }
        .torch-button { position: fixed; bottom: 100px; right: 20px; background: rgba(0,0,0,0.5); width: 50px; height: 50px; border-radius: 25px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; cursor: pointer; z-index: 1000; backdrop-filter: blur(5px); }
    </style>
</head>
<body>
    <div class="scanner-header">
        <a href="?page=mobile_dashboard" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="scanner-title"><?php echo t('scan_qr_code'); ?></div>
        <div class="scanner-subtitle"><?php echo t('scan_qr_instruction'); ?></div>
    </div>
    
    <div id="reader"></div>
    
    <div class="instruction">
        <i class="fas fa-qrcode fa-lg"></i>
        <span><?php echo t('scan_qr_position'); ?></span>
    </div>
    
    <div class="manual-input">
        <p class="small text-muted mb-2"><?php echo t('or_enter_manually'); ?></p>
        <div class="input-group">
            <input type="text" id="manualCode" class="form-control" placeholder="<?php echo t('equipment_code_or_id'); ?>">
            <button class="btn btn-primary" onclick="searchByCode()">
                <i class="fas fa-search"></i> <?php echo t('search'); ?>
            </button>
        </div>
    </div>
    
    <div class="recent-equipment">
        <div class="recent-title"><i class="fas fa-history"></i> <?php echo t('recent_equipment'); ?></div>
        <?php foreach($recent_equipment as $eq): ?>
            <a href="#" class="recent-item" onclick="searchByCodeDirect('<?php echo htmlspecialchars($eq['code']); ?>'); return false;">
                <?php echo htmlspecialchars($eq['code']); ?> - <?php echo htmlspecialchars($eq['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div id="scanResult" class="scan-result">
        <div class="scan-result-header">
            <h6 class="scan-result-title"><i class="fas fa-microchip"></i> <?php echo t('equipment_found'); ?></h6>
            <button class="close-result" onclick="closeResult()">&times;</button>
        </div>
        <div id="resultContent"></div>
    </div>
    
    <div class="torch-button" id="torchButton" onclick="toggleTorch()" style="display: none;">
        <i class="fas fa-lightbulb"></i>
    </div>
    
    <div class="bottom-nav">
        <a href="?page=mobile_dashboard" class="nav-item">
            <i class="fas fa-home"></i>
            <span class="nav-label"><?php echo t('home'); ?></span>
        </a>
        <a href="?page=mobile_interventions" class="nav-item">
            <i class="fas fa-tools"></i>
            <span class="nav-label"><?php echo t('interventions'); ?></span>
        </a>
        <a href="?page=mobile_equipment" class="nav-item">
            <i class="fas fa-microchip"></i>
            <span class="nav-label"><?php echo t('equipment'); ?></span>
        </a>
        <a href="?page=mobile_scan" class="nav-item active">
            <i class="fas fa-qrcode"></i>
            <span class="nav-label"><?php echo t('scan'); ?></span>
        </a>
        <a href="?page=mobile_profile" class="nav-item">
            <i class="fas fa-user"></i>
            <span class="nav-label"><?php echo t('profile'); ?></span>
        </a>
    </div>
    
    <script>
        let html5QrCode;
        let torchEnabled = false;
        let currentCameraId = null;
        
        async function startScanner() {
            try {
                const devices = await Html5Qrcode.getCameras();
                if (devices && devices.length) {
                    const backCamera = devices.find(device => device.label.toLowerCase().includes('back') || device.label.toLowerCase().includes('arriÃ¨re'));
                    currentCameraId = backCamera ? backCamera.id : devices[0].id;
                    
                    html5QrCode = new Html5Qrcode("reader");
                    const config = { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 };
                    
                    await html5QrCode.start(currentCameraId, config, onScanSuccess, onScanError);
                    document.getElementById('torchButton').style.display = 'flex';
                }
            } catch(err) {
                console.error('Erreur camÃ©ra:', err);
                document.getElementById('reader').innerHTML = '<div class="alert alert-danger m-3"><i class="fas fa-exclamation-triangle"></i> <?php echo t("camera_error"); ?></div>';
            }
        }
        
        async function toggleTorch() {
            if (html5QrCode && html5QrCode.isScanning) {
                torchEnabled = !torchEnabled;
                await html5QrCode.applyVideoConstraints({ advanced: [{ torch: torchEnabled }] });
                const torchBtn = document.getElementById('torchButton');
                if (torchEnabled) {
                    torchBtn.style.background = "rgba(255,255,255,0.8)";
                    torchBtn.style.color = "#333";
                } else {
                    torchBtn.style.background = "rgba(0,0,0,0.5)";
                    torchBtn.style.color = "white";
                }
            }
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            let equipmentId = null;
            if (decodedText.includes('equipment_detail&id=')) {
                equipmentId = decodedText.split('equipment_detail&id=')[1];
            } else if (decodedText.includes('id=')) {
                equipmentId = decodedText.split('id=')[1];
            } else if (decodedText.includes('code=')) {
                equipmentId = decodedText.split('code=')[1];
            } else {
                equipmentId = decodedText;
            }
            if (equipmentId) {
                getEquipmentInfo(equipmentId);
                stopScanner();
            }
        }
        
        function onScanError(error) {}
        
        async function stopScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                await html5QrCode.stop();
                document.getElementById('torchButton').style.display = 'none';
            }
        }
        
        function getEquipmentInfo(id) {
            fetch(`/gmao/api/get_equipment.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showResult(data.equipment);
                    } else {
                        showResult(null, '<?php echo t("equipment_not_found"); ?>');
                    }
                })
                .catch(error => {
                    showResult(null, '<?php echo t("connection_error"); ?>');
                });
        }
        
        function showResult(equipment, error = null) {
            const resultDiv = document.getElementById('scanResult');
            const contentDiv = document.getElementById('resultContent');
            
            if (error) {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${error}
                    </div>
                    <button class="btn btn-secondary w-100" onclick="restartScanner()">
                        <i class="fas fa-camera"></i> <?php echo t('scan_again'); ?>
                    </button>
                `;
            } else if (equipment) {
                const statusBadge = equipment.status == 'active' 
                    ? '<span class="badge bg-success"><?php echo t("active"); ?></span>' 
                    : '<span class="badge bg-danger"><?php echo t("inactive"); ?></span>';
                
                contentDiv.innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-microchip fa-3x text-primary mb-2"></i>
                        <h5>${escapeHtml(equipment.name)}</h5>
                        <p class="text-muted small"><?php echo t('code'); ?>: ${escapeHtml(equipment.code)}</p>
                    </div>
                    <div class="equipment-detail">
                        <table class="table table-sm">
                            <tr><td style="width: 40%;"><strong><?php echo t('type'); ?>:</strong></td><td>${escapeHtml(equipment.type || '<?php echo t("not_specified"); ?>')}</td></tr>
                            <tr><td><strong><?php echo t('location'); ?>:</strong></td><td>${escapeHtml(equipment.location || '<?php echo t("not_specified"); ?>')}</td></tr>
                            <tr><td><strong><?php echo t('status'); ?>:</strong></td><td>${statusBadge}</td></tr>
                            ${equipment.supplier ? `<tr><td><strong><?php echo t('supplier'); ?>:</strong></td><td>${escapeHtml(equipment.supplier)}</td></tr>` : ''}
                        </table>
                    </div>
                    <div class="btn-action">
                        <a href="?page=mobile_intervention_add&equipment_id=${equipment.id}" class="btn btn-primary">
                            <i class="fas fa-tools"></i> <?php echo t('report_problem'); ?>
                        </a>
                        <a href="?page=mobile_equipment_detail&id=${equipment.id}" class="btn btn-outline-secondary">
                            <i class="fas fa-info-circle"></i> <?php echo t('details'); ?>
                        </a>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-link btn-sm" onclick="restartScanner()">
                            <i class="fas fa-camera"></i> <?php echo t('scan_another'); ?>
                        </button>
                    </div>
                `;
            }
            resultDiv.classList.add('show');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function closeResult() {
            document.getElementById('scanResult').classList.remove('show');
            restartScanner();
        }
        
        function restartScanner() {
            closeResult();
            startScanner();
        }
        
        function searchByCode() {
            const code = document.getElementById('manualCode').value;
            if (code) {
                stopScanner();
                getEquipmentInfo(code);
            } else {
                alert('<?php echo t("enter_code_warning"); ?>');
            }
        }
        
        function searchByCodeDirect(code) {
            document.getElementById('manualCode').value = code;
            stopScanner();
            getEquipmentInfo(code);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            startScanner();
        });
        
        window.addEventListener('beforeunload', function() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
        });
    </script>
</body>
</html>