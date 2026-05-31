<?php
// tools/run_migration.php - run SQL migration file using mysqli->multi_query
require_once __DIR__ . '/../config/database.php';

// Accept optional SQL file path as first CLI argument
$fn = $argv[1] ?? __DIR__ . '/../migrations/20260524_01_unify_links.sql';
if (!file_exists($fn)) {
    echo "Migration file not found: $fn\n";
    exit(1);
}
$sql = file_get_contents($fn);
if ($sql === false) {
    echo "Failed to read migration file\n";
    exit(1);
}
// Use mysqli to run multi_query
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    echo "MySQL connect error: " . $mysqli->connect_error . "\n";
    exit(1);
}
$mysqli->set_charset('utf8');
if ($mysqli->multi_query($sql)) {
    do {
        if ($res = $mysqli->store_result()) {
            // optionally fetch results
            $res->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    if ($mysqli->errno) {
        echo "Error during migration: ({$mysqli->errno}) {$mysqli->error}\n";
        $mysqli->close();
        exit(1);
    }
    echo "Migration executed successfully.\n";
    // After running, if the migration mentions external_path, verify the column exists
    if (stripos($fn, 'external_path') !== false || stripos($sql, 'external_path') !== false) {
        $check = $mysqli->query("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='attachments' AND COLUMN_NAME='external_path'");
        if ($check) {
            $r = $check->fetch_assoc();
            echo "external_path column present: " . ($r['c'] > 0 ? 'yes' : 'no') . "\n";
        }
    }
    $mysqli->close();
    exit(0);
} else {
    echo "Migration failed: ({$mysqli->errno}) {$mysqli->error}\n";
    $mysqli->close();
    exit(1);
}
?>