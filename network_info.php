<?php
// network_info.php - Diagnostic réseau complet
$ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.x';
$port = $_SERVER['SERVER_PORT'] ?? 80;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Configuration réseau GMAO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .qr-container {
            background: white;
            padding: 20px;
            text-align: center;
            border-radius: 15px;
        }
        .url-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            word-break: break-all;
            font-family: monospace;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">📡 Configuration réseau GMAO Mobile</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Votre smartphone doit être connecté au <strong>même réseau Wi-Fi</strong> que cet ordinateur
                </div>
                
                <h6>📱 URL à scanner ou à taper sur votre smartphone :</h6>
                <div class="url-box mb-3">
                    http://<?= $ip ?>:<?= $port ?>/gmao/index.php?page=mobile_dashboard
                </div>
                
                <div class="qr-container text-center">
                    <div id="qrcode"></div>
                    <p class="text-muted small mt-2">Scannez ce QR code avec votre smartphone</p>
                </div>
                
                <hr>
                
                <h6>🔧 Informations réseau :</h6>
                <table class="table table-sm">
                    <tr><th>IP Serveur :</th><td><?= $ip ?></td></tr>
                    <tr><th>Port :</th><td><?= $port ?></td></tr>
                    <tr><th>Nom hôte :</th><td><?= gethostname() ?></td></tr>
                    <tr><th>URL complète :</th><td><code>http://<?= $ip ?>:<?= $port ?>/gmao/</code></td></tr>
                </table>
                
                <div class="alert alert-warning">
                    <strong>⚠️ Si le smartphone ne se connecte pas :</strong>
                    <ol class="mb-0 mt-2">
                        <li>Vérifiez que XAMPP Apache est démarré (bouton vert)</li>
                        <li>Désactivez temporairement le pare-feu Windows</li>
                        <li>Redémarrez Apache</li>
                        <li>Vérifiez que le smartphone est sur le même Wi-Fi</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "http://<?= $ip ?>:<?= $port ?>/gmao/index.php?page=mobile_dashboard",
            width: 200,
            height: 200
        });
    </script>
</body>
</html>