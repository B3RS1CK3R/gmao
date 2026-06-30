<?php
// test_preventive.php - Script de test direct (sans passer par index.php)
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simuler un utilisateur admin (à des fins de test)
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h2>DEBUG POST</h2>';
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    
    $team_id = $_POST['team_id'] ?? null;
    echo '<p>team_id = ' . ($team_id ?: 'NULL') . '</p>';
    
    // Insertion de test (ne pas vraiment insérer, juste pour voir)
    echo '<p>Simulation insertion terminée.</p>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test preventive - insertion</title>
</head>
<body>
    <h2>Formulaire de test pour preventive</h2>
    <form method="POST">
        <label>Équipement :</label>
        <select name="equipment_id" required>
            <?php
            $eqs = $pdo->query("SELECT id, code, name FROM equipment LIMIT 5")->fetchAll();
            foreach ($eqs as $eq) echo "<option value='{$eq['id']}'>{$eq['code']} - {$eq['name']}</option>";
            ?>
        </select><br><br>
        <label>Fréquence (jours) :</label>
        <input type="number" name="frequency_days" value="30" required><br><br>
        <label>Technicien :</label>
        <select name="technician_id"><option value="">Aucun</option></select><br><br>
        <label>Équipe :</label>
        <select name="team_id">
            <option value="">Aucune</option>
            <?php
            $teams = $pdo->query("SELECT id, name FROM teams")->fetchAll();
            foreach ($teams as $team) echo "<option value='{$team['id']}'>{$team['name']}</option>";
            ?>
        </select><br><br>
        <button type="submit">Tester l'envoi</button>
    </form>
</body>
</html>