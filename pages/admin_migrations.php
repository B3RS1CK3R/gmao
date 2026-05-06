<?php
// pages/admin_migrations.php - Admin UI to view & run DB migrations
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$migrationsDir = __DIR__ . '/../migrations';
if(!is_dir($migrationsDir)) { echo "<div class='alert alert-warning'>No migrations directory.</div>"; return; }

$files = glob($migrationsDir . '/*.sql');
sort($files, SORT_STRING);

// Ensure migrations table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    applied_at DATETIME NOT NULL
)");

$applied = [];
$stmt = $pdo->query("SELECT name, applied_at FROM migrations ORDER BY applied_at DESC");
foreach($stmt->fetchAll() as $r) { $applied[$r['name']] = $r['applied_at']; }

?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-database"></i> <?php echo t('migrations'); ?></h2>
    <form method="post" action="api/run_migrations.php">
        <button class="btn btn-primary" type="submit"><?php echo t('run_migrations'); ?></button>
    </form>
</div>
<div class="card p-3">
    <ul>
    <?php foreach($files as $f): $name = basename($f); ?>
        <li>
            <strong><?php echo htmlspecialchars($name); ?></strong>
            <?php if(isset($applied[$name])): ?>
                — <span class="text-success"><?php echo htmlspecialchars($applied[$name]); ?></span>
            <?php else: ?>
                — <span class="text-muted"><?php echo t('pending'); ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
