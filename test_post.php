<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>';
    print_r($_POST);
    echo '</pre>';
    exit;
}
?>
<form method="POST">
    <select name="team_id">
        <option value="1">Equipe 1</option>
    </select>
    <button type="submit">Envoyer</button>
</form>