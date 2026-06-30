<?php
// api/check_attachments_columns.php - quick check for external_path column
// Use core includes for consistent DB access and helpers
require_once __DIR__ . '/../includes/functions.php';
ob_start();

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM attachments LIKE 'external_path'");
    $rows = $stmt->fetchAll();
    if(empty($rows)) {
        echo "MISSING\n";
        exit(0);
    }
    foreach($rows as $r) {
        echo $r['Field'] . ' ' . $r['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

?>