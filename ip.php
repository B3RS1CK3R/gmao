<?php
// ip.php - Affiche l'adresse IP du serveur
echo "<h1>Informations de connexion GMAO</h1>";
echo "<hr>";
echo "<h2>Adresses accessibles :</h2>";
echo "<ul>";

// IP locale
$local_ip = gethostbyname(gethostname());
echo "<li><strong>IP locale :</strong> $local_ip</li>";

// Toutes les IPs
$ips = [];
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $output = shell_exec('ipconfig');
    preg_match_all('/IPv4 Address[ .]+:\s*([0-9.]+)/', $output, $matches);
    $ips = $matches[1] ?? [];
} else {
    $output = shell_exec('ip addr show');
    preg_match_all('/inet ([0-9.]+)\//', $output, $matches);
    $ips = $matches[1] ?? [];
}

foreach($ips as $ip) {
    if($ip != '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP)) {
        echo "<li><strong>IP réseau :</strong> $ip</li>";
    }
}

echo "</ul>";

echo "<hr>";
echo "<h2>Accès depuis votre smartphone :</h2>";

$ip_address = $ips[0] ?? '192.168.1.x';
echo "<div style='background:#e9ecef; padding:20px; border-radius:10px;'>";
echo "<p><strong>URL pour smartphone :</strong></p>";
echo "<code style='font-size:18px; background:white; padding:10px; display:block; word-break:break-all;'>";
echo "http://$ip_address/gmao/index.php?page=mobile_dashboard";
echo "</code>";
echo "</div>";

echo "<hr>";
echo "<h3>⚠️ Conditions requises :</h3>";
echo "<ul>";
echo "<li>Votre smartphone doit être connecté au MÊME réseau Wi-Fi que l'ordinateur</li>";
echo "<li>Apache doit être démarré dans XAMPP</li>";
echo "<li>Le pare-feu Windows peut bloquer l'accès - autorisez le port 80</li>";
echo "</ul>";

echo "<h3>🔧 Si ça ne fonctionne pas :</h3>";
echo "<ul>";
echo "<li>Vérifiez que XAMPP Apache est bien démarré (bouton vert)</li>";
echo "<li>Désactivez temporairement le pare-feu Windows pour tester</li>";
echo "<li>Vérifiez que le smartphone est sur le même réseau Wi-Fi</li>";
echo "</ul>";
?>