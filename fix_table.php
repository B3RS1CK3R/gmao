<?php
// fix_table.php - Correction de la table performance_metrics
require_once 'config/database.php';

echo "<h2>Correction de la base de données</h2>";

try {
    // Supprimer l'ancienne table si elle existe
    $pdo->exec("DROP TABLE IF EXISTS performance_metrics");
    echo "<p style='color:orange'>✓ Ancienne table supprimée</p>";
    
    // Créer la nouvelle table
    $sql = "
    CREATE TABLE performance_metrics (
        id INT PRIMARY KEY AUTO_INCREMENT,
        equipment_id INT,
        date_recorded DATE,
        mtbf DECIMAL(10,2),
        mttr DECIMAL(10,2),
        availability DECIMAL(5,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
        INDEX idx_date (date_recorded)
    )";
    
    $pdo->exec($sql);
    echo "<p style='color:green'>✓ Table performance_metrics recréée avec succès</p>";
    
    // Insérer des données initiales pour chaque équipement
    $stmt = $pdo->query("SELECT id FROM equipment");
    $equipments = $stmt->fetchAll();
    
    $insert = $pdo->prepare("
        INSERT INTO performance_metrics (equipment_id, date_recorded, mtbf, mttr, availability)
        VALUES (?, CURDATE(), ?, ?, ?)
    ");
    
    foreach($equipments as $eq) {
        $mtbf = calculateMTBF($eq['id']);
        $mttr = calculateMTTR($eq['id']);
        $availability = calculateAvailability($eq['id']);
        $insert->execute([$eq['id'], $mtbf, $mttr, $availability]);
    }
    
    echo "<p style='color:green'>✓ Données initiales insérées</p>";
    echo "<hr>";
    echo "<p style='color:green; font-weight:bold;'>✅ Correction terminée !</p>";
    echo "<a href='index.php?page=dashboard_advanced' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Accéder au Dashboard avancé</a>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Erreur : " . $e->getMessage() . "</p>";
}
?>