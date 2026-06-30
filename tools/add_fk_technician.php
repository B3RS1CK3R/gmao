<?php
// Use central includes for DB and helpers
require_once __DIR__ . '/../includes/functions.php';
ob_start();
$schema = DB_NAME;
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    echo "MySQL connect error: " . $mysqli->connect_error . "\n";
    exit(1);
}
// Check if column exists
$colRes = $mysqli->query("SELECT COUNT(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='". $mysqli->real_escape_string($schema) ."' AND TABLE_NAME='interventions' AND COLUMN_NAME='technician_id'");
$col = $colRes->fetch_assoc();
if ($col['c'] == 0) {
    echo "Column technician_id missing, adding...\n";
    if (!$mysqli->query("ALTER TABLE interventions ADD COLUMN technician_id INT NULL AFTER equipment_id")) {
        echo "Failed to add column: ({$mysqli->errno}) {$mysqli->error}\n";
        exit(1);
    }
    echo "Column added.\n";
}
// Check if foreign key exists
$keyRes = $mysqli->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA='". $mysqli->real_escape_string($schema) ."' AND TABLE_NAME='interventions' AND COLUMN_NAME='technician_id' AND REFERENCED_TABLE_NAME='technicians'");
if ($keyRes->num_rows == 0) {
    echo "Foreign key missing, adding FK interventions(technician_id) -> technicians(id)...\n";
    // Add FK with a generated name
    $fkName = 'fk_interventions_technician_id';
    if (!$mysqli->query("ALTER TABLE interventions ADD CONSTRAINT {$fkName} FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL")) {
        echo "Failed to add foreign key: ({$mysqli->errno}) {$mysqli->error}\n";
        exit(1);
    }
    echo "Foreign key added.\n";
} else {
    echo "Foreign key already present.\n";
}
$mysqli->close();
echo "Done.\n";
?>