<?php
// pages/criticality_matrix.php - Matrice de criticité (probabilité vs sévérité)
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

if($_SESSION['role'] != 'admin') {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

// Récupération des équipements
$stmt = $pdo->query("
    SELECT id, code, name, 
           COALESCE(probability_score, 1) as probability,
           COALESCE(severity_score, 1) as severity
    FROM equipment
");
$equipments = $stmt->fetchAll();

// Construction de la matrice 5x5 (probabilité = ligne, sévérité = colonne)
$matrix = array_fill(1, 5, array_fill(1, 5, 0));
$details = [];

foreach ($equipments as $eq) {
    $p = (int)$eq['probability'];
    $s = (int)$eq['severity'];
    $matrix[$p][$s]++;
    $details[$p][$s][] = $eq['code'] . ' - ' . $eq['name'];
}

/**
 * Détermine la couleur de la cellule en fonction du score de criticité (p×s)
 * selon les plages définies dans la légende.
 */
function getCriticalityColorClass($score) {
    if ($score >= 16) return 'danger';   // rouge
    if ($score >= 10) return 'orange';   // orange
    if ($score >= 5)  return 'warning';  // jaune
    return 'success';                    // vert
}

// Labels pour les axes
$probLabels = [
    5 => t('very_high'),
    4 => t('high'),
    3 => t('medium'),
    2 => t('low'),
    1 => t('very_low')
];

$sevLabels = [
    1 => t('negligible'),
    2 => t('minor'),
    3 => t('moderate'),
    4 => t('serious'),
    5 => t('critical')
];
?>

<style>
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .card-header-custom {
        background: #6c757d;
        color: white;
        padding: 12px 20px;
        font-weight: bold;
    }
    .card-header-custom.primary {
        background: #667eea;
    }
    .matrix-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
        font-size: 14px;
    }
    .matrix-table th, .matrix-table td {
        border: 1px solid #ddd;
        padding: 10px;
        vertical-align: middle;
    }
    .matrix-table th {
        background: #f8f9fa;
        font-weight: bold;
    }
    .matrix-table td {
        cursor: pointer;
        transition: transform 0.1s;
    }
    .matrix-table td:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 1;
    }
    .cell-count {
        font-size: 18px;
        font-weight: bold;
    }
    .tooltip-list {
        display: none;
        position: absolute;
        background: white;
        border: 1px solid #ccc;
        padding: 8px;
        border-radius: 6px;
        box-shadow: 2px 2px 8px rgba(0,0,0,0.2);
        z-index: 1000;
        font-size: 12px;
        max-width: 250px;
    }
    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 4px;
        margin-right: 5px;
        vertical-align: middle;
    }
    /* Custom badge for orange */
    .badge.bg-orange {
        background-color: #fd7e14 !important;
        color: white;
    }
    /* Background colors for matrix cells (based on criticality score) */
    .bg-critical-success { background-color: #d4edda; }  /* vert */
    .bg-critical-warning { background-color: #fff3cd; }  /* jaune */
    .bg-critical-orange  { background-color: #ffe0b3; }  /* orange */
    .bg-critical-danger   { background-color: #f8d7da; }  /* rouge */
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> <?php echo t('criticality_matrix'); ?></h2>
    </div>

    <div class="info-card">
        <div class="card-header-custom primary">
            <i class="fas fa-th"></i> <?php echo t('criticality_heatmap'); ?>
        </div>
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="matrix-table">
                    <thead>
                        <tr>
                            <th><?php echo t('probability'); ?> ↓ / <?php echo t('severity'); ?> →</th>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <th><?php echo $sevLabels[$s]; ?><br><small>(<?php echo $s; ?>)</small></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($p = 5; $p >= 1; $p--): ?>
                        <tr>
                            <th><?php echo $probLabels[$p]; ?><br><small>(<?php echo $p; ?>)</small></th>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <?php
                                $count = $matrix[$p][$s];
                                $score = $p * $s;
                                $colorClass = 'bg-critical-' . getCriticalityColorClass($score);
                                $tooltipContent = '';
                                if ($count > 0) {
                                    $tooltipContent = implode('<br>', $details[$p][$s]);
                                } else {
                                    $tooltipContent = t('no_equipment');
                                }
                                ?>
                                <td class="<?php echo $colorClass; ?>" style="position: relative;"
                                    onmouseenter="showTooltip(event, '<?php echo addslashes($tooltipContent); ?>')"
                                    onmouseleave="hideTooltip()">
                                    <div class="cell-count"><?php echo $count; ?></div>
                                    <small>(<?php echo $score; ?>)</small>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-info-circle"></i> <?php echo t('legend'); ?>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong><?php echo t('probability_scale'); ?></strong>
                    <ul>
                        <li><?php echo t('very_high'); ?> (5) – >10 failures/year</li>
                        <li><?php echo t('high'); ?> (4) – 7-10 failures/year</li>
                        <li><?php echo t('medium'); ?> (3) – 4-6 failures/year</li>
                        <li><?php echo t('low'); ?> (2) – 2-3 failures/year</li>
                        <li><?php echo t('very_low'); ?> (1) – ≤1 failure/year</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <strong><?php echo t('severity_scale'); ?></strong>
                    <ul>
                        <li><?php echo t('critical'); ?> (5) – cost > €10000, downtime >24h</li>
                        <li><?php echo t('serious'); ?> (4) – €2000-10000, downtime 4-24h</li>
                        <li><?php echo t('moderate'); ?> (3) – €500-2000, downtime 1-4h</li>
                        <li><?php echo t('minor'); ?> (2) – €100-500, downtime <1h</li>
                        <li><?php echo t('negligible'); ?> (1) – cost < €100, no downtime</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3">
                <strong><?php echo t('criticality_levels'); ?></strong><br>
                <span class="legend-color" style="background-color:#d4edda;"></span> <?php echo t('low_criticality'); ?> (score 1-4)<br>
                <span class="legend-color" style="background-color:#fff3cd;"></span> <?php echo t('medium_criticality'); ?> (score 5-9)<br>
                <span class="legend-color" style="background-color:#ffe0b3;"></span> <?php echo t('high_criticality'); ?> (score 10-15)<br>
                <span class="legend-color" style="background-color:#f8d7da;"></span> <?php echo t('very_high_criticality'); ?> (score 16-25)<br>
            </div>
            <div class="mt-2 small text-muted">
                <?php echo t('tooltip_instruction'); ?>
            </div>
        </div>
    </div>

    <!-- Liste des équipements avec scores -->
    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-list"></i> <?php echo t('equipment_list_with_scores'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><?php echo t('code'); ?></th>
                            <th><?php echo t('name'); ?></th>
                            <th><?php echo t('probability'); ?></th>
                            <th><?php echo t('severity'); ?></th>
                            <th><?php echo t('criticality'); ?> (P×S)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($equipments as $eq): 
                            $crit = $eq['probability'] * $eq['severity'];
                            $critClass = getCriticalityColorClass($crit);
                            // Déterminer la classe de fond de ligne
                            switch($critClass) {
                                case 'success': $rowClass = 'bg-critical-success'; break;
                                case 'warning': $rowClass = 'bg-critical-warning'; break;
                                case 'orange':  $rowClass = 'bg-critical-orange'; break;
                                case 'danger':  $rowClass = 'bg-critical-danger'; break;
                                default: $rowClass = '';
                            }
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo htmlspecialchars($eq['code']); ?></td>
                            <td><?php echo htmlspecialchars($eq['name']); ?></td>
                            <td><?php echo $eq['probability']; ?></td>
                            <td><?php echo $eq['severity']; ?></td>
                            <td><span class="badge bg-<?php echo $critClass; ?>"><?php echo $crit; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let tooltipDiv = null;

function showTooltip(event, content) {
    if (!tooltipDiv) {
        tooltipDiv = document.createElement('div');
        tooltipDiv.className = 'tooltip-list';
        document.body.appendChild(tooltipDiv);
    }
    tooltipDiv.innerHTML = content;
    tooltipDiv.style.display = 'block';
    let x = event.clientX + 10;
    let y = event.clientY - 30;
    tooltipDiv.style.left = x + 'px';
    tooltipDiv.style.top = y + 'px';
}

function hideTooltip() {
    if (tooltipDiv) {
        tooltipDiv.style.display = 'none';
    }
}
</script>