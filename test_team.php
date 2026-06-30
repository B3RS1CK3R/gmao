<?php
// test_team.php - Vérifier l'envoi de team_id
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<h1>Données POST reçues</h1>';
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    echo '<p><strong>team_id :</strong> ' . (isset($_POST['team_id']) ? $_POST['team_id'] : 'NON TRANSMIS') . '</p>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Test team_id</title></head>
<body>
<form method="POST">
    <label>Équipe :</label>
    <select name="team_id">
        <option value="">-- Aucune --</option>
        <option value="1">Équipe 1</option>
        <option value="2">Équipe 2</option>
    </select>
    <br><br>
    <label>Autre champ :</label>
    <input type="text" name="autre" value="test">
    <br><br>
    <button type="submit">Envoyer</button>
</form>
</body>
</html>