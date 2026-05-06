<?php
// api/check_attachments_columns.php - quick check for external_path column
require_once __DIR__ . '/../config/database.php';

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