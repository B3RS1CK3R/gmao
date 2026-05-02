<?php
// export/export_intervention_pdf.php - Export PDF d'une intervention
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    die('ID intervention manquant');
}

require_once __DIR__ . '/../config/database.php';

// Récupérer l'intervention
$stmt = $pdo->prepare("
    SELECT i.*, e.name as equipment_name, e.code as equipment_code, e.location as equipment_location,
           t.firstname, t.lastname, t.specialty, t.phone,
           u.username as created_by_name
    FROM interventions i 
    JOIN equipment e ON i.equipment_id = e.id 
    LEFT JOIN technicians t ON i.intervenant_id = t.id
    LEFT JOIN users u ON i.reported_by = u.username
    WHERE i.id = ?
");
$stmt->execute([$id]);
$intervention = $stmt->fetch();

if(!$intervention) {
    die('Intervention non trouvée');
}

// Récupérer les pièces utilisées
$stmt = $pdo->prepare("
    SELECT sp.*, sm.quantity 
    FROM stock_movements sm
    JOIN spare_parts sp ON sm.part_id = sp.id
    WHERE sm.intervention_id = ?
");
$stmt->execute([$id]);
$used_parts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport intervention - <?php echo htmlspecialchars($intervention['task_number']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #667eea;
        }
        .header h1 {
            color: #667eea;
            margin: 0;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td, table th {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .info-table td {
            border: none;
            padding: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-size: 11px;
        }
        .status-critical { background: #dc3545; }
        .status-high { background: #fd7e14; }
        .status-medium { background: #ffc107; color: #333; }
        .status-low { background: #28a745; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .signature {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()">🖨️ Imprimer</button>
        <button onclick="window.close()">❌ Fermer</button>
    </div>
    
    <div class="header">
        <h1>Rapport d'intervention</h1>
        <p>GMAO Industrielle - Document technique</p>
    </div>
    
    <!-- Identification -->
    <div class="section">
        <div class="section-title">1. Identification</div>
        <table class="info-table">
            <tr><td width="30%"><strong>N° de tâche</strong></td><td><?php echo htmlspecialchars($intervention['task_number'] ?? 'N/A'); ?></td></tr>
            <tr><td><strong>Titre</strong></td><td><?php echo htmlspecialchars($intervention['title']); ?></td></tr>
            <tr><td><strong>Date de création</strong></td><td><?php echo date('d/m/Y H:i', strtotime($intervention['created_at'])); ?></td></tr>
            <tr><td><strong>Créé par</strong></td><td><?php echo htmlspecialchars($intervention['created_by_name'] ?? $intervention['reported_by']); ?></td></tr>
            <tr><td><strong>Criticité</strong></td><td><span class="status-badge status-<?php echo $intervention['priority']; ?>"><?php echo strtoupper($intervention['priority']); ?></span></td></tr>
            <tr><td><strong>Statut</strong></td><td><?php echo $intervention['task_status']; ?></td></tr>
        </table>
    </div>
    
    <!-- Équipement -->
    <div class="section">
        <div class="section-title">2. Équipement concerné</div>
        <table class="info-table">
            <tr><td width="30%"><strong>Nom</strong></td><td><?php echo htmlspecialchars($intervention['equipment_name']); ?></td></tr>
            <tr><td><strong>Code</strong></td><td><?php echo htmlspecialchars($intervention['equipment_code']); ?></td></tr>
            <tr><td><strong>Localisation</strong></td><td><?php echo htmlspecialchars($intervention['equipment_location'] ?: 'Non spécifiée'); ?></td></tr>
            <tr><td><strong>Zone</strong></td><td><?php echo htmlspecialchars($intervention['zone'] ?: 'Non spécifiée'); ?></td></tr>
            <tr><td><strong>Localisation précise</strong></td><td><?php echo htmlspecialchars($intervention['localisation'] ?: 'Non spécifiée'); ?></td></tr>
        </table>
    </div>
    
    <!-- Description -->
    <div class="section">
        <div class="section-title">3. Description de l'intervention</div>
        <div style="padding: 10px; background: #f9f9f9; border-radius: 5px;">
            <?php echo nl2br(htmlspecialchars($intervention['description'] ?: 'Aucune description')); ?>
        </div>
    </div>
    
    <!-- Planning -->
    <div class="section">
        <div class="section-title">4. Planning</div>
        <table class="info-table">
            <tr><td width="30%"><strong>Type de tâche</strong></td><td><?php echo htmlspecialchars($intervention['task_type'] ?: $intervention['type']); ?></td></tr>
            <tr><td><strong>Date prévue</strong></td><td><?php echo $intervention['intervention_date'] ? date('d/m/Y', strtotime($intervention['intervention_date'])) : 'Non planifiée'; ?></td></tr>
            <tr><td><strong>Durée prévue</strong></td><td><?php echo htmlspecialchars($intervention['planned_duration'] ?: 'Non spécifiée'); ?></td></tr>
            <?php if($intervention['duration_hours']): ?>
            <tr><td><strong>Durée réelle</strong></td><td><?php echo $intervention['duration_hours']; ?> heure(s)</td></tr>
            <?php endif; ?>
            <?php if($intervention['completed_date']): ?>
            <tr><td><strong>Date de réalisation</strong></td><td><?php echo date('d/m/Y H:i', strtotime($intervention['completed_date'])); ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Intervenant -->
    <div class="section">
        <div class="section-title">5. Intervenant(s)</div>
        <?php if($intervention['firstname']): ?>
        <table class="info-table">
            <tr><td width="30%"><strong>Technicien</strong></td><td><?php echo htmlspecialchars($intervention['firstname'] . ' ' . $intervention['lastname']); ?></td></tr>
            <tr><td><strong>Spécialité</strong></td><td><?php echo htmlspecialchars($intervention['specialty']); ?></td></tr>
            <tr><td><strong>Téléphone</strong></td><td><?php echo htmlspecialchars($intervention['phone'] ?: 'Non renseigné'); ?></td></tr>
        </table>
        <?php else: ?>
        <p>Aucun technicien assigné</p>
        <?php endif; ?>
    </div>
    
    <!-- Pièces utilisées -->
    <?php if(!empty($used_parts)): ?>
    <div class="section">
        <div class="section-title">6. Pièces détachées utilisées</div>
        <table>
            <thead>
                <tr><th>Référence</th><th>Nom</th><th>Quantité</th><th>Prix unitaire</th><th>Total</th></tr>
            </thead>
            <tbody>
                <?php 
                $total_cost = 0;
                foreach($used_parts as $part): 
                    $subtotal = $part['unit_price'] * $part['quantity'];
                    $total_cost += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($part['part_number']); ?></td>
                    <td><?php echo htmlspecialchars($part['name']); ?></td>
                    <td><?php echo $part['quantity']; ?></td>
                    <td><?php echo number_format($part['unit_price'], 2); ?> €</td>
                    <td><?php echo number_format($subtotal, 2); ?> €</td>
                </tr>
                <?php endforeach; ?>
                <tr style="background: #f0f0f0; font-weight: bold;">
                    <td colspan="4" style="text-align: right;">Total :</td>
                    <td><?php echo number_format($total_cost, 2); ?> €</td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Rapport -->
    <?php if($intervention['completion_report']): ?>
    <div class="section">
        <div class="section-title">7. Rapport d'intervention</div>
        <div style="padding: 10px; background: #f9f9f9; border-radius: 5px;">
            <?php echo nl2br(htmlspecialchars($intervention['completion_report'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Signatures -->
    <div class="signature">
        <div class="signature-line">
            Signature du technicien
        </div>
        <div class="signature-line">
            Signature du responsable
        </div>
    </div>
    
    <div class="footer">
        Document généré automatiquement par GMAO Industrielle le <?php echo date('d/m/Y H:i'); ?>
    </div>
    
    <script>
        // Impression automatique
        // window.print();
    </script>
</body>
</html>