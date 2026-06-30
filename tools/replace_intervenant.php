<?php
// tools/replace_intervenant.php
// Replaces occurrences of 'intervenant_id' -> 'technician_id' in PHP files under project
$root = realpath(__DIR__ . '/..');
$excludeDirs = ['vendor', 'migrations', 'backups', 'tools'];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = [];
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') continue;
    // exclude paths
    foreach ($excludeDirs as $ex) {
        if (stripos($path, DIRECTORY_SEPARATOR . $ex . DIRECTORY_SEPARATOR) !== false) {
            continue 2;
        }
    }
    $content = file_get_contents($path);
    if ($content === false) continue;
    if (strpos($content, 'intervenant_id') !== false) {
        $new = str_replace('intervenant_id', 'technician_id', $content);
        // make backup
        copy($path, $path . '.bak');
        file_put_contents($path, $new);
        $changed[] = $path;
    }
}
if (count($changed) === 0) {
    echo "No files changed.\n";
} else {
    echo "Replaced in " . count($changed) . " files:\n";
    foreach ($changed as $p) echo " - $p\n";
}
?>