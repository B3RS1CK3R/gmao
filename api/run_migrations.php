<?php
// api/run_migrations.php - Run SQL migrations in /migrations
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if(PHP_SAPI !== 'cli') {
    if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo "Access denied";
        exit();
    }
}

$migrationsDir = __DIR__ . '/../migrations';
if(!is_dir($migrationsDir)) {
    echo json_encode(['error' => 'no_migrations_dir']);
    exit();
}

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
)");

$files = glob($migrationsDir . '/*.sql');
sort($files, SORT_STRING);
$applied = [];
$stmt = $pdo->query("SELECT name FROM migrations");
foreach($stmt->fetchAll() as $r) { $applied[$r['name']] = true; }

$results = [];
foreach($files as $file) {
    $name = basename($file);
    if(isset($applied[$name])) {
        $results[$name] = 'skipped';
        continue;
    }

    $sql = file_get_contents($file);
    if($sql === false) {
        $results[$name] = 'read_error';
        continue;
    }

    // Split statements on semicolon followed by newline to avoid basic multi-statement issues
    $stmts = preg_split('/;\s*\n/', $sql);

    // If the migration contains DDL statements (ALTER/CREATE/DROP/etc), don't wrap in transaction
    $containsDDL = preg_match('/\b(ALTER|CREATE|DROP|RENAME|TRUNCATE|GRANT|REVOKE)\b/i', $sql);

    try {
        if(!$containsDDL) {
            $pdo->beginTransaction();
        }

        foreach($stmts as $s) {
            $s = trim($s);
            if($s === '') continue;
            $pdo->exec($s);
        }

        $ins = $pdo->prepare("INSERT INTO migrations (name, applied_at) VALUES (?, NOW())");
        $ins->execute([$name]);

        if(!$containsDDL) {
            try {
                $pdo->commit();
            } catch (PDOException $commitEx) {
                // Some DB engines (or DDL executed inside a transaction) may implicitly commit/close
                // the transaction. If commit reports there is no active transaction, treat as applied.
                if(stripos($commitEx->getMessage(), 'no active transaction') !== false) {
                    // ignore - consider applied
                } else {
                    throw $commitEx;
                }
            }
        }

        $results[$name] = 'applied';
    } catch (PDOException $e) {
        if($pdo->inTransaction()) {
            try { $pdo->rollBack(); } catch (Exception $ex) { /* ignore rollback errors */ }
        }
        $results[$name] = 'error: ' . $e->getMessage();
    }
}

// Output results
if(PHP_SAPI === 'cli') {
    foreach($results as $k=>$v) echo "$k -> $v\n";
} else {
    echo "<h3>Migrations results</h3><ul>";
    foreach($results as $k=>$v) echo "<li>".htmlspecialchars($k)." — " . htmlspecialchars($v) . "</li>";
    echo "</ul><p><a href='?page=equipment'>" . t('back') . "</a></p>";
}

exit();
