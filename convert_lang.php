<?php
// convert_lang.php - Aide à la traduction
session_start();
require_once 'includes/lang.php';

$files = [
    'pages/dashboard.php',
    'pages/equipment.php',
    'pages/interventions.php',
    'pages/stock.php',
    'pages/technicians.php',
    'pages/planning.php',
    'pages/alerts.php',
    'pages/performance.php',
    'pages/profile.php',
    'pages/users.php'
];

echo "<h1>Vérification des pages non traduites</h1>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Fichier</th><th>Textes en dur trouvés</th></tr>";

foreach($files as $file) {
    if(file_exists($file)) {
        $content = file_get_contents($file);
        // Chercher les textes français courants
        $french_patterns = [
            '/[>=\s]Ajouter[<\s]/', '/[>=\s]Modifier[<\s]/', '/[>=\s]Supprimer[<\s]/',
            '/[>=\s]Code[<\s]/', '/[>=\s]Nom[<\s]/', '/[>=\s]Type[<\s]/',
            '/[>=\s]Statut[<\s]/', '/[>=\s]Actions[<\s]/', '/[>=\s]Interventions[<\s]/',
            '/[>=\s]Équipements[<\s]/', '/[>=\s]Techniciens[<\s]/', '/[>=\s]Stock[<\s]/'
        ];
        
        $found = [];
        foreach($french_patterns as $pattern) {
            if(preg_match($pattern, $content)) {
                $found[] = $pattern;
            }
        }
        
        echo "<tr>";
        echo "<td>$file</td>";
        echo "<td>" . (count($found) > 0 ? implode(', ', $found) : '✅ OK') . "</td>";
        echo "</tr>";
    }
}
echo "</table>";
?>