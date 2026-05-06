<?php
// includes/functions.php - FULL VERSION
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/lang.php';

// Ensure session is active for CSRF helpers
if(session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Simple CSRF helpers
function csrf_token() {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    // Also set a cookie fallback to help forms when session cookie path issues occur
    if(!headers_sent()) {
        setcookie('csrf_token', $_SESSION['csrf_token'], time() + 3600, '/');
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    $token = htmlspecialchars(csrf_token());
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function validate_csrf($token) {
    if(empty($token)) return false;
    if(empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// More tolerant validation: allow cookie fallback if form token missing
function validate_csrf_fallback($token) {
    if(!empty($token) && !empty($_SESSION['csrf_token'])) {
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    // fallback to cookie
    if(!empty($_COOKIE['csrf_token']) && !empty($_SESSION['csrf_token'])) {
        return hash_equals($_SESSION['csrf_token'], $_COOKIE['csrf_token']);
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php?page=login');
        exit();
    }
}

function getEquipment($id = null) {
    global $pdo;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } else {
        $stmt = $pdo->query("SELECT e.*, 
                             (SELECT COUNT(*) FROM interventions WHERE equipment_id = e.id AND status = 'pending') as pending_interventions
                             FROM equipment e ORDER BY e.name");
        return $stmt->fetchAll();
    }
}

function addEquipment($data) {
    global $pdo;
    $sql = "INSERT INTO equipment (code, name, type, location, supplier, purchase_date, warranty_end, technical_specs) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['code'],
        $data['name'],
        $data['type'],
        $data['location'],
        $data['supplier'],
        $data['purchase_date'],
        $data['warranty_end'],
        $data['technical_specs']
    ]);
}

function addIntervention($data) {
    global $pdo;
    $sql = "INSERT INTO interventions (equipment_id, type, priority, title, description, reported_by) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['equipment_id'],
        $data['type'],
        $data['priority'],
        $data['title'],
        $data['description'],
        $_SESSION['username'] ?? 'system'
    ]);
}

function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM equipment");
    $stats['total_equipment'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM interventions WHERE status IN ('pending', 'in_progress')");
    $stats['active_interventions'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM interventions 
                         WHERE status = 'completed' 
                         AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                         AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['completed_interventions'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, start_date, end_date)) as avg_duration 
                         FROM interventions WHERE status = 'completed' AND end_date IS NOT NULL");
    $stats['avg_intervention_duration'] = round($stmt->fetch()['avg_duration'] ?? 0, 1);
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM spare_parts WHERE quantity <= min_quantity");
    $stats['critical_stock'] = $stmt->fetch()['total'];
    
    return $stats;
}

function updatePreventiveSchedule() {
    global $pdo;
    $stmt = $pdo->query("SELECT pm.*, e.name as equipment_name 
                         FROM preventive_maintenance pm 
                         JOIN equipment e ON pm.equipment_id = e.id 
                         WHERE pm.next_due <= CURDATE() AND e.status = 'active'");
    return $stmt->fetchAll();
}

function getAlerts() {
    $alerts = [];
    
    $overdue = updatePreventiveSchedule();
    foreach($overdue as $task) {
        $alerts[] = "⚠️ " . t('maintenance_overdue') . " : " . htmlspecialchars($task['equipment_name']);
    }
    
    global $pdo;
    $stmt = $pdo->query("SELECT name, quantity, min_quantity FROM spare_parts WHERE quantity <= min_quantity");
    $lowStock = $stmt->fetchAll();
    foreach($lowStock as $part) {
        $alerts[] = "📦 " . t('low_stock_title') . " : {$part['name']} ({$part['quantity']} " . t('remaining') . ", min: {$part['min_quantity']})";
    }
    
    $stmt = $pdo->query("SELECT name, warranty_end FROM equipment 
                         WHERE warranty_end <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                         AND warranty_end IS NOT NULL");
    $warranty = $stmt->fetchAll();
    foreach($warranty as $eq) {
        $days = ceil((strtotime($eq['warranty_end']) - time()) / 86400);
        if($days < 0) {
            $alerts[] = "⚠️ " . t('warranty_expired') . " : " . htmlspecialchars($eq['name']);
        } elseif($days <= 30) {
            $alerts[] = "📅 " . t('warranty_upcoming') . " ($days " . t('days_left') . ") : " . htmlspecialchars($eq['name']);
        }
    }
    
    return $alerts;
}

function getRecentInterventions($limit = 5) {
    global $pdo;
    $limit = intval($limit);
    $stmt = $pdo->query("
        SELECT i.*, e.name as equipment_name 
        FROM interventions i 
        JOIN equipment e ON i.equipment_id = e.id 
        ORDER BY i.created_at DESC 
        LIMIT $limit
    ");
    return $stmt->fetchAll();
}

// ========== QR CODE FUNCTIONS ==========
function generateQRCode($equipment_id, $code) {
    $url = "http://" . $_SERVER['HTTP_HOST'] . "/gmao_GEMINI/index.php?page=equipment_detail&id=" . $equipment_id;
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    return $qr_url;
}

function getMaintenanceHistory($equipment_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT * FROM interventions 
        WHERE equipment_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$equipment_id]);
    return $stmt->fetchAll();
}

function getEquipmentDetails($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Format date in US style (m/d/Y) with optional time
function format_date_us($datetime, $withTime = true) {
    if(empty($datetime) || in_array($datetime, ['0000-00-00', '0000-00-00 00:00:00'])) return t('not_specified');
    $ts = strtotime($datetime);
    if($ts === false) return htmlspecialchars($datetime);
    return $withTime ? date('m/d/Y H:i', $ts) : date('m/d/Y', $ts);
}

// ========== EMAIL FUNCTIONS ==========
function sendEmail($to, $subject, $message, $isHTML = true) {
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: " . ($isHTML ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8");
    $headers[] = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">";
    $headers[] = "Reply-To: " . FROM_EMAIL;
    
    $headersString = implode("\r\n", $headers);
    
    if(is_array($to)) {
        $success = true;
        foreach($to as $email) {
            if(!mail($email, $subject, $message, $headersString)) {
                $success = false;
            }
        }
        return $success;
    } else {
        return mail($to, $subject, $message, $headersString);
    }
}

function sendPreventiveAlert($maintenance) {
    $subject = t('preventive_alert_subject') . " - {$maintenance['equipment_name']}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .alert-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
            .equipment { font-weight: bold; color: #856404; }
            .info { margin: 10px 0; }
        </style>
    </head>
    <body>
        <h2>" . t('preventive_maintenance_required') . "</h2>
        <div class='alert-box'>
            <p class='equipment'>🔧 " . t('equipment') . ": {$maintenance['equipment_name']}</p>
            <p class='info'>📅 " . t('due_date') . ": " . date('m/d/Y', strtotime($maintenance['next_due'])) . "</p>
            <p class='info'>📝 " . t('instructions') . ": {$maintenance['instructions']}</p>
            <p class='info'>👥 " . t('assigned_team') . ": {$maintenance['assigned_team']}</p>
        </div>
        <p>
            <a href='http://{$_SERVER['HTTP_HOST']}/gmao_GEMINI/index.php?page=preventive' 
               style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
               " . t('view_in_gmao') . "
            </a>
        </p>
        <hr>
        <small>" . t('automatic_message') . "</small>
    </body>
    </html>
    ";
    
    $to = ALERT_EMAILS;
    return sendEmail($to, $subject, $message, true);
}

function sendStockAlert($part) {
    $subject = t('stock_alert_subject') . ": {$part['name']}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            table { border-collapse: collapse; width: 100%; }
            td, th { padding: 8px; text-align: left; }
        </style>
    </head>
    <body>
        <h2>" . t('critical_stock_alert') . "</h2>
        <p>" . t('part_reached_threshold') . ":</p>
        <table border='0' cellpadding='10' style='background:#f8f9fa; border-radius:5px; width:100%;'>
            <tr><td style='background:#e9ecef'><strong>" . t('part_number') . ":</strong></td><td>{$part['part_number']}</td></tr>
            <tr><td style='background:#e9ecef'><strong>" . t('name') . ":</strong></td><td>{$part['name']}</td></tr>
            <tr><td style='background:#e9ecef'><strong>" . t('remaining_quantity') . ":</strong></td><td style='color:red'><strong>{$part['quantity']}</strong></td></tr>
            <tr><td style='background:#e9ecef'><strong>" . t('minimum_threshold') . ":</strong></td><td>{$part['min_quantity']}</td></tr>
            <tr><td style='background:#e9ecef'><strong>" . t('location') . ":</strong></td><td>{$part['location']}</td></tr>
            <tr><td style='background:#e9ecef'><strong>" . t('supplier') . ":</strong></td><td>{$part['supplier']}</td></tr>
        </table>
        <p style='margin-top:20px;'><a href='http://{$_SERVER['HTTP_HOST']}/gmao_GEMINI/index.php?page=stock' 
              style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
              " . t('view_stock') . "
           </a></p>
        <hr>
        <small>" . t('automatic_message') . "</small>
    </body>
    </html>
    ";
    
    $to = ALERT_EMAILS;
    return sendEmail($to, $subject, $message, true);
}

function sendCriticalInterventionAlert($intervention, $equipment) {
    $subject = t('critical_intervention_subject') . ": {$intervention['title']}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .urgent-box { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h2 style='color:#dc3545'>" . t('new_critical_intervention') . "</h2>
        <div class='urgent-box'>
            <p><strong>🔧 " . t('equipment') . ":</strong> {$equipment['name']}</p>
            <p><strong>📝 " . t('title') . ":</strong> {$intervention['title']}</p>
            <p><strong>📄 " . t('description') . ":</strong> {$intervention['description']}</p>
            <p><strong>👤 " . t('created_by') . ":</strong> {$intervention['reported_by']}</p>
            <p><strong>📅 " . t('date') . ":</strong> " . date('m/d/Y H:i', strtotime($intervention['created_at'])) . "</p>
        </div>
        <p>
            <a href='http://{$_SERVER['HTTP_HOST']}/gmao_GEMINI/index.php?page=interventions' 
               style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
               " . t('view_intervention') . "
            </a>
        </p>
        <hr>
        <small>" . t('immediate_action_required') . "</small>
    </body>
    </html>
    ";
    
    $to = ALERT_EMAILS;
    return sendEmail($to, $subject, $message, true);
}

function sendWeeklyReport() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_interventions,
            SUM(CASE WHEN task_status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN priority = 'critical' THEN 1 ELSE 0 END) as critical
        FROM interventions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $stats = $stmt->fetch();
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as new_equipment
        FROM equipment 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $newEq = $stmt->fetch();
    
    $subject = t('weekly_report_subject') . " " . date('W');
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .stats { background: #e9ecef; padding: 10px; border-radius: 5px; }
        </style>
    </head>
    <body>
        <h2>" . t('weekly_activity_report') . "</h2>
        <p>" . t('period') . " : " . date('m/d/Y', strtotime('-7 days')) . " to " . date('m/d/Y') . "</p>
        
        <div class='stats'>
            <h3>📈 " . t('activity_interventions') . "</h3>
            <ul>
                <li>" . t('total') . " : {$stats['total_interventions']}</li>
                <li>" . t('completed') . " : {$stats['completed']}</li>
                <li>" . t('critical') . " : {$stats['critical']}</li>
            </ul>
            
            <h3>🆕 " . t('equipment') . "</h3>
            <ul>
                <li>" . t('new_equipment_count') . " : {$newEq['new_equipment']}</li>
            </ul>
        </div>
        
        <p style='margin-top:20px;'>
            <a href='http://{$_SERVER['HTTP_HOST']}/gmao_GEMINI/index.php?page=dashboard' 
               style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
               " . t('access_dashboard') . "
            </a>
        </p>
        <hr>
        <small>" . t('automatic_message') . "</small>
    </body>
    </html>
    ";
    
    foreach(ALERT_EMAILS as $email) {
        sendEmail($email, $subject, $message, true);
    }
}

// ========== TECHNICIAN MANAGEMENT FUNCTIONS ==========

function getAllTechnicians($status = null) {
    global $pdo;
    if($status) {
        $stmt = $pdo->prepare("SELECT * FROM technicians WHERE status = ? ORDER BY lastname ASC");
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query("SELECT * FROM technicians ORDER BY lastname ASC");
    }
    return $stmt->fetchAll();
}

function getTechnician($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM technicians WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function addTechnician($data) {
    global $pdo;
    $sql = "INSERT INTO technicians (employee_id, firstname, lastname, phone, email, specialty, hire_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['employee_id'],
        $data['firstname'],
        $data['lastname'],
        $data['phone'],
        $data['email'],
        $data['specialty'],
        $data['hire_date'],
        $data['status']
    ]);
}

function updateTechnician($id, $data) {
    global $pdo;
    $sql = "UPDATE technicians SET employee_id=?, firstname=?, lastname=?, phone=?, email=?, specialty=?, status=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $data['employee_id'],
        $data['firstname'],
        $data['lastname'],
        $data['phone'],
        $data['email'],
        $data['specialty'],
        $data['status'],
        $id
    ]);
}

function deleteTechnician($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM technicians WHERE id = ?");
    return $stmt->execute([$id]);
}

function assignInterventionToTechnician($intervention_id, $technician_id, $scheduled_date, $scheduled_time) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // Update intervention
        $stmt = $pdo->prepare("UPDATE interventions SET technician_id = ?, scheduled_date = ?, scheduled_time = ? WHERE id = ?");
        $stmt->execute([$technician_id, $scheduled_date, $scheduled_time, $intervention_id]);
        
        // Add to schedule
        $stmt = $pdo->prepare("
            INSERT INTO work_schedule (technician_id, intervention_id, scheduled_start) 
            VALUES (?, ?, ?)
        ");
        $scheduled_start = $scheduled_date . ' ' . $scheduled_time . ':00';
        $stmt->execute([$technician_id, $intervention_id, $scheduled_start]);
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function getTechnicianInterventions($technician_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT i.*, e.name as equipment_name 
        FROM interventions i
        JOIN equipment e ON i.equipment_id = e.id
        WHERE i.technician_id = ?
        ORDER BY i.scheduled_date DESC
        LIMIT ?
    ");
    $stmt->execute([$technician_id, $limit]);
    return $stmt->fetchAll();
}

function getAvailableTechnicians($date, $equipment_type = null) {
    global $pdo;
    $sql = "
        SELECT t.* FROM technicians t
        WHERE t.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM work_schedule ws
            WHERE ws.technician_id = t.id
            AND DATE(ws.scheduled_start) = ?
            AND ws.status IN ('scheduled', 'in_progress')
        )
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    return $stmt->fetchAll();
}

function getTechnicianWorkload($technician_id, $start_date, $end_date) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM work_schedule 
        WHERE technician_id = ? 
        AND DATE(scheduled_start) BETWEEN ? AND ?
        AND status != 'cancelled'
    ");
    $stmt->execute([$technician_id, $start_date, $end_date]);
    return $stmt->fetch()['count'];
}

// ========== MTBF / MTTR FUNCTIONS ==========

function calculateMTBF($equipment_id) {
    global $pdo;
    
    // Get failure dates
    $stmt = $pdo->prepare("
        SELECT created_at FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
        AND status = 'completed'
        ORDER BY created_at ASC
    ");
    $stmt->execute([$equipment_id]);
    $failures = $stmt->fetchAll();
    
    if(count($failures) < 2) {
        return 0; // Not enough data
    }
    
    // Calculate mean time between failures
    $total_interval = 0;
    for($i = 1; $i < count($failures); $i++) {
        $date1 = strtotime($failures[$i-1]['created_at']);
        $date2 = strtotime($failures[$i]['created_at']);
        $interval = ($date2 - $date1) / 3600; // in hours
        $total_interval += $interval;
    }
    
    return round($total_interval / (count($failures) - 1), 1);
}

function calculateMTTR($equipment_id) {
    global $pdo;
    
    // Get repair durations
    $stmt = $pdo->prepare("
        SELECT duration_hours FROM interventions 
        WHERE equipment_id = ? 
        AND type = 'corrective'
        AND status = 'completed'
        AND duration_hours IS NOT NULL
    ");
    $stmt->execute([$equipment_id]);
    $repairs = $stmt->fetchAll();
    
    if(count($repairs) == 0) {
        return 0;
    }
    
    $total_duration = 0;
    foreach($repairs as $repair) {
        $total_duration += $repair['duration_hours'];
    }
    
    return round($total_duration / count($repairs), 1);
}

function calculateAvailability($equipment_id, $days = 30) {
    global $pdo;
    
    // Get downtime
    $stmt = $pdo->prepare("
        SELECT SUM(duration_hours) as total_downtime
        FROM interventions 
        WHERE equipment_id = ? 
        AND status = 'completed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$equipment_id, $days]);
    $downtime = $stmt->fetch()['total_downtime'] ?? 0;
    
    $total_hours = $days * 24;
    if($total_hours == 0) return 100;
    
    $availability = (($total_hours - $downtime) / $total_hours) * 100;
    return round($availability, 1);
}

function getEquipmentPerformance($equipment_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            e.*,
            (SELECT COUNT(*) FROM interventions WHERE equipment_id = e.id AND type = 'corrective') as failure_count,
            (SELECT AVG(duration_hours) FROM interventions WHERE equipment_id = e.id AND duration_hours IS NOT NULL) as avg_repair_time
        FROM equipment e
        WHERE e.id = ?
    ");
    $stmt->execute([$equipment_id]);
    $data = $stmt->fetch();
    
    if($data) {
        $data['mtbf'] = calculateMTBF($equipment_id);
        $data['mttr'] = calculateMTTR($equipment_id);
        $data['availability'] = calculateAvailability($equipment_id);
    }
    
    return $data;
}

function getAllEquipmentPerformance() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT id, name, code, status FROM equipment");
    $equipments = $stmt->fetchAll();
    
    $results = [];
    foreach($equipments as $eq) {
        $mtbf = calculateMTBF($eq['id']);
        $mttr = calculateMTTR($eq['id']);
        $availability = calculateAvailability($eq['id']);
        
        // Count failures
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM interventions WHERE equipment_id = ? AND type = 'corrective'");
        $stmt2->execute([$eq['id']]);
        $failure_count = $stmt2->fetchColumn();
        
        $results[] = [
            'id' => $eq['id'],
            'name' => $eq['name'],
            'code' => $eq['code'],
            'status' => $eq['status'],
            'mtbf' => $mtbf,
            'mttr' => $mttr,
            'availability' => $availability,
            'failure_count' => $failure_count
        ];
    }
    
    return $results;
}

function getPerformanceTrend($equipment_id, $months = 6) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as failures_count,
            AVG(duration_hours) as avg_repair_time
        FROM interventions
        WHERE equipment_id = ?
        AND type = 'corrective'
        AND status = 'completed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute([$equipment_id, $months]);
    return $stmt->fetchAll();
}

function getGlobalPerformanceIndicators() {
    global $pdo;
    
    $indicators = [];
    
    // Get all active equipment
    $stmt = $pdo->query("SELECT id FROM equipment WHERE status = 'active'");
    $equipments = $stmt->fetchAll();
    
    // Global MTBF calculation
    $total_mtbf = 0;
    $mtbf_count = 0;
    foreach($equipments as $eq) {
        $mtbf = calculateMTBF($eq['id']);
        if($mtbf > 0) {
            $total_mtbf += $mtbf;
            $mtbf_count++;
        }
    }
    $indicators['global_mtbf'] = $mtbf_count > 0 ? round($total_mtbf / $mtbf_count, 1) : 0;
    
    // Global MTTR calculation
    $stmt = $pdo->query("
        SELECT AVG(duration_hours) as global_mttr 
        FROM interventions 
        WHERE type = 'corrective' AND duration_hours IS NOT NULL AND duration_hours > 0
    ");
    $result = $stmt->fetch();
    $indicators['global_mttr'] = round($result['global_mttr'] ?? 0, 1);
    
    // Failure rate by equipment type
    $stmt = $pdo->query("
        SELECT e.type, COUNT(i.id) as failures_count
        FROM equipment e
        LEFT JOIN interventions i ON e.id = i.equipment_id AND i.type = 'corrective'
        GROUP BY e.type
        ORDER BY failures_count DESC
    ");
    $indicators['failures_by_type'] = $stmt->fetchAll();
    
    // Top 5 most problematic equipment
    $stmt = $pdo->query("
        SELECT e.name, COUNT(i.id) as failures
        FROM equipment e
        JOIN interventions i ON e.id = i.equipment_id
        WHERE i.type = 'corrective'
        GROUP BY e.id
        ORDER BY failures DESC
        LIMIT 5
    ");
    $indicators['top_problematic'] = $stmt->fetchAll();
    
    return $indicators;
}

// ========== ADVANCED USER MANAGEMENT FUNCTIONS ==========

function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, username, fullname, role, email, is_active, last_login, created_at FROM users ORDER BY role, username");
    return $stmt->fetchAll();
}

function getUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function createUser($username, $password, $fullname, $role, $email) {
    global $pdo;
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Create user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role, email, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $result = $stmt->execute([$username, $hashed, $fullname, $role, $email]);
        
        if($result && $role == 'technician') {
            // 2. If technician role, also create a technician entry
            $user_id = $pdo->lastInsertId();
            
            // Split full name into first and last name
            $name_parts = explode(' ', trim($fullname), 2);
            $firstname = $name_parts[0];
            $lastname = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Generate unique employee ID
            $employee_id = 'TECH-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            
            $stmt2 = $pdo->prepare("
                INSERT INTO technicians (user_id, employee_id, firstname, lastname, email, status) 
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt2->execute([$user_id, $employee_id, $firstname, $lastname, $email]);
        }
        
        $pdo->commit();
        return true;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateUser($id, $fullname, $role, $email, $is_active) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, role = ?, email = ?, is_active = ? WHERE id = ?");
    return $stmt->execute([$fullname, $role, $email, $is_active, $id]);
}

function updateUserPassword($id, $new_password) {
    global $pdo;
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed, $id]);
}

function deleteUser($id) {
    global $pdo;
    // Do not delete main admin
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    if($user && $user['username'] == 'admin') {
        return false;
    }
    $stmt2 = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt2->execute([$id]);
}

// Log user actions
function logUserAction($user_id, $action, $details = null) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $pdo->prepare("INSERT INTO user_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action, $details, $ip, $user_agent]);
}

function getUserLogs($user_id = null, $limit = 100) {
    global $pdo;
    $limit = (int)$limit;
    
    $sql = "
        SELECT l.*, u.username 
        FROM user_logs l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT $limit
    ";
    
    $stmt = $pdo->query($sql);
    $logs = $stmt->fetchAll();
    
    if($user_id) {
        $filtered = [];
        foreach($logs as $log) {
            if($log['user_id'] == $user_id) {
                $filtered[] = $log;
            }
        }
        return $filtered;
    }
    
    return $logs;
}

function updateLastLogin($user_id) {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
    return $stmt->execute([$ip, $user_id]);
}

function generateResetToken($email) {
    global $pdo;
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
    if($stmt->execute([$token, $expires, $email])) {
        return $token;
    }
    return false;
}

function verifyResetToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1");
    $stmt->execute([$token]);
    return $stmt->fetch();
}

function resetPassword($token, $new_password) {
    global $pdo;
    $user = verifyResetToken($token);
    if($user) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        return $stmt->execute([$hashed, $user['id']]);
    }
    return false;
}

function hasPermission($required_role) {
    if(!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    $role_levels = [
        'admin' => 4,
        'supervisor' => 3,
        'technician' => 2,
        'viewer' => 1
    ];
    
    $user_level = $role_levels[$_SESSION['role']] ?? 0;
    $required_level = $role_levels[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

function requireRole($role) {
    if(!hasPermission($role)) {
        header('Location: index.php?page=dashboard&error=unauthorized');
        exit();
    }
}

// Generate unique task number
function generateTaskNumber() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get and increment last number
        $stmt = $pdo->query("SELECT last_number FROM task_sequence FOR UPDATE");
        $last_number = $stmt->fetchColumn();
        
        if(!$last_number) {
            $last_number = 260031;
        }
        
        $new_number = $last_number + 1;
        
        $update = $pdo->prepare("UPDATE task_sequence SET last_number = ?");
        $update->execute([$new_number]);
        
        $pdo->commit();
        
        return "TASK-" . $new_number;
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        // Fallback to timestamp-based number
        return "TASK-" . date('YmdHis');
    }
}

// Get next task number (preview)
function getNextTaskNumber() {
    global $pdo;
    $stmt = $pdo->query("SELECT last_number + 1 as next FROM task_sequence");
    $next = $stmt->fetchColumn();
    return "TASK-" . ($next ?: 260032);
}

// Get all intervenants (technicians) for select
function getAllIntervenants() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, firstname, lastname, specialty FROM technicians WHERE status = 'active' ORDER BY lastname ASC");
    return $stmt->fetchAll();
}

// Update intervention status
function updateInterventionStatus($id, $status) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE interventions SET task_status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// Complete intervention with report
function completeIntervention($id, $completion_report, $duration_hours = null) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE interventions 
        SET task_status = 'completed', 
            completed_date = NOW(), 
            completion_report = ?,
            duration_hours = COALESCE(?, duration_hours)
        WHERE id = ?
    ");
    return $stmt->execute([$completion_report, $duration_hours, $id]);
}
?>