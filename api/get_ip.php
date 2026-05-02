<?php
// api/get_ip.php
header('Content-Type: application/json');

function getLocalIP() {
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
        if($ip != '127.0.0.1' && filter_var($ip, FILTER_VALIDATE_IP) && strpos($ip, '192.168.') === 0) {
            return $ip;
        }
    }
    
    return $ips[0] ?? null;
}

$ip = getLocalIP();
echo json_encode(['ip' => $ip]);
?>