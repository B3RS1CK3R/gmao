<?php
// pages/preventive_debug.php
if(!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
?>

<div class="container">
    <h2>🔍 Diagnostic Préventive</h2>
    
    <h4>1. Configuration task_format_settings</h4>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM task_format_settings WHERE type = 'preventive'");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>"; print_r($config); echo "</pre>";
    ?>

    <h4>2. Prochain numéro (dryRun)</h4>
    <?php echo "<strong>" . generateTaskNumber($pdo, 'preventive', true) . "</strong>"; ?>

    <h4>3. Test incrémentation réelle</h4>
    <?php 
    $ref = generateTaskNumber($pdo, 'preventive', false); 
    echo "<strong style='color:green'>" . $ref . "</strong>"; 
    ?>

    <hr>
    <a href="?page=preventive" class="btn btn-primary">Retour à la liste</a>
</div>