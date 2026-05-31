<?php
$root = realpath(__DIR__ . '/..');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$results = [];
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    if ($f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    // skip vendor, backups, tools, node_modules
    $skipDirs = [DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR];
    $skip = false;
    foreach ($skipDirs as $sd) {
        if (strpos($path, $sd) !== false) { $skip = true; break; }
    }
    if ($skip) continue;
    $cmd = escapeshellcmd('C:\\xampp\\php\\php.exe') . ' -l ' . escapeshellarg($path);
    exec($cmd, $out, $rc);
    $outStr = implode("\n", $out);
    if ($rc !== 0) {
        $results[] = "FILE: $path\n$outStr\n";
    }
}
$target = $root . DIRECTORY_SEPARATOR . 'lint_results.txt';
if (count($results) === 0) {
    file_put_contents($target, "No syntax errors\n");
    echo "No syntax errors\n";
    exit(0);
}
file_put_contents($target, implode("\n", $results));
echo "Wrote " . count($results) . " failure(s) to lint_results.txt\n";
exit(0);
?>