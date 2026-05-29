<?php
// pages/equipment_qr.php - Page QR Code pour équipement
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
    header('Location: index.php?page=equipment');
    exit();
}

// Récupération de l'équipement
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
$stmt->execute([$id]);
$equipment = $stmt->fetch();

if(!$equipment) {
    echo "<div class='alert alert-danger'>" . t('equipment_not_found') . "</div>";
    return;
}

// Génération de l'URL pour le QR code - Correction du chemin
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$url = $protocol . "://" . $host . $script_dir . "/index.php?page=equipment_detail&id=" . $equipment['id'];
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($url);
$local_qr_url = "https://quickchart.io/qr?text=" . urlencode($url) . "&size=250";

// Récupération de l'historique des maintenances
$stmt = $pdo->prepare("
    SELECT * FROM interventions 
    WHERE equipment_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$id]);
$maintenances = $stmt->fetchAll();

// Mapping des statuts d'équipement
$status_badge_map = [
    'active' => 'success',
    'maintenance' => 'warning',
    'broken' => 'danger',
    'retired' => 'secondary'
];

// Mapping des priorités avec les classes CSS du projet
$priority_badge_map = [
    'critical' => 'danger',
    'high' => 'warning',
    'medium' => 'info',
    'low' => 'success'
];

// Mapping des statuts d'intervention (basé sur les valeurs réelles de la BDD)
// Les valeurs possibles : 'planifiee', 'en_cours', 'termine', 'cloturee', 'annulee'
$intervention_status_map = [
    'planifiee' => ['class' => 'status-planifiee', 'icon' => '📅', 'label' => 'planifiee'],
    'en_cours' => ['class' => 'status-en_cours', 'icon' => '⚙️', 'label' => 'en_cours'],
    'termine' => ['class' => 'status-termine', 'icon' => '✅', 'label' => 'termine'],
    'cloturee' => ['class' => 'status-cloturee', 'icon' => '🔒', 'label' => 'cloturee'],
    'annulee' => ['class' => 'status-annulee', 'icon' => '❌', 'label' => 'annulee']
];
?>

<style>
    .qr-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .qr-card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .qr-code-container {
        background: white;
        padding: 30px;
        text-align: center;
        border-radius: 15px;
        margin-bottom: 20px;
    }
    .qr-code {
        background: white;
        padding: 20px;
        display: inline-block;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .qr-code img {
        width: 200px;
        height: 200px;
    }
    .equipment-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        margin-top: 15px;
    }
    .print-area {
        text-align: center;
        padding: 20px;
    }
    .print-area .qr-code {
        box-shadow: none;
    }
    @media print {
        .no-print { display: none !important; }
        .print-area { display: block !important; margin: 0; padding: 0; }
        .print-area .qr-code { box-shadow: none; border: 1px solid #ddd; }
        body { background: white; }
        .container-fluid { padding: 0; margin: 0; }
    }
    .btn-print {
        background: linear-gradient(135deg, #28a745, #1e7e34);
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        color: white;
        font-weight: bold;
    }
    .btn-print:hover {
        filter: brightness(0.95);
        color: white;
    }
    .btn-back {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        color: white;
    }
    .btn-back:hover {
        background: #5a6268;
        color: white;
    }
    
    /* Badges pour les statuts d'intervention */
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-planifiee { background: #17a2b8; color: white; }
    .status-en_cours { background: #ffc107; color: #333; }
    .status-termine { background: #28a745; color: white; }
    .status-cloturee { background: #6c757d; color: white; }
    .status-annulee { background: #dc3545; color: white; }
    
    /* Badges pour les priorités - charte graphique */
    .priority-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .priority-critical { background: #dc3545; color: white; }
    .priority-high { background: #fd7e14; color: white; }
    .priority-medium { background: #ffc107; color: #333; }
    .priority-low { background: #28a745; color: white; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-qrcode"></i> 
            <?php echo t('qr_code_for'); ?> <?php echo htmlspecialchars($equipment['name']); ?>
            <small class="text-muted">(<?php echo htmlspecialchars($equipment['code']); ?>)</small>
        </h2>
        <div class="no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> <?php echo t('print_label'); ?>
            </button>
            <a href="?page=equipment" class="btn-back ms-2">
                <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Zone à imprimer -->
        <div class="print-area" style="display: none;">
            <div class="qr-code">
                <img src="<?php echo $qr_url; ?>" alt="QR Code" onerror="this.src='<?php echo $local_qr_url; ?>'">
                <div style="margin-top: 15px; font-family: monospace; font-size: 14px; font-weight: bold;">
                    <?php echo htmlspecialchars($equipment['code']); ?>
                </div>
                <div style="margin-top: 5px; font-size: 10px;">
                    <?php echo htmlspecialchars($equipment['name']); ?>
                </div>
            </div>
        </div>
        
        <!-- Affichage normal -->
        <div class="col-md-12 no-print">
            <div class="qr-card">
                <div class="qr-card-header">
                    <i class="fas fa-qrcode"></i> <?php echo t('qr_code'); ?>
                </div>
                <div class="card-body text-center p-4">
                    <div class="qr-code-container">
                        <div class="qr-code">
                            <img src="<?php echo $qr_url; ?>" alt="QR Code" onerror="this.src='<?php echo $local_qr_url; ?>'">
                        </div>
                        <div class="equipment-info">
                            <h5><?php echo htmlspecialchars($equipment['name']); ?></h5>
                            <p class="mb-0"><?php echo t('code'); ?> : <strong><?php echo htmlspecialchars($equipment['code']); ?></strong></p>
                            <p class="mb-0"><?php echo t('location'); ?> : <?php echo htmlspecialchars($equipment['location'] ?: t('not_specified')); ?></p>
                            <p class="mb-0"><?php echo t('status'); ?> : 
                                <span class="badge bg-<?php echo $status_badge_map[$equipment['status']] ?? 'secondary'; ?>">
                                    <?php 
                                    $status_labels = [
                                        'active' => '🟢 ' . t('active'),
                                        'maintenance' => '🟡 ' . t('maintenance'),
                                        'broken' => '🔴 ' . t('broken'),
                                        'retired' => '⚫ ' . t('retired')
                                    ];
                                    echo $status_labels[$equipment['status']] ?? $equipment['status'];
                                    ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> 
                        <?php echo t('scan_qr_instruction'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Dernières interventions - Version corrigée -->
            <div class="qr-card">
                <div class="qr-card-header">
                    <i class="fas fa-history"></i> <?php echo t('last_interventions'); ?>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($maintenances)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p><?php echo t('no_interventions'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?php echo t('date'); ?></th>
                                        <th><?php echo t('title'); ?></th>
                                        <th><?php echo t('priority'); ?></th>
                                        <th><?php echo t('status'); ?></th>
                                        <th class="text-center"><?php echo t('actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($maintenances as $m): 
                                        // Gestion de la priorité avec la charte graphique
                                        $priority_class = '';
                                        $priority_icon = '';
                                        $priority_text = '';
                                        
                                        switch($m['priority']) {
                                            case 'critical':
                                                $priority_class = 'priority-critical';
                                                $priority_icon = '🔴';
                                                $priority_text = t('critical');
                                                break;
                                            case 'high':
                                                $priority_class = 'priority-high';
                                                $priority_icon = '🟠';
                                                $priority_text = t('high');
                                                break;
                                            case 'medium':
                                                $priority_class = 'priority-medium';
                                                $priority_icon = '🟡';
                                                $priority_text = t('medium');
                                                break;
                                            case 'low':
                                                $priority_class = 'priority-low';
                                                $priority_icon = '🟢';
                                                $priority_text = t('low');
                                                break;
                                            default:
                                                $priority_class = 'priority-medium';
                                                $priority_icon = '🟡';
                                                $priority_text = $m['priority'];
                                        }
                                        
                                        // Gestion du statut de l'intervention
                                        $status_class = '';
                                        $status_icon = '';
                                        $status_text = '';
                                        
                                        switch($m['task_status']) {
                                            case 'planifiee':
                                                $status_class = 'status-planifiee';
                                                $status_icon = '📅';
                                                $status_text = t('planned');
                                                break;
                                            case 'en_cours':
                                                $status_class = 'status-en_cours';
                                                $status_icon = '⚙️';
                                                $status_text = t('in_progress');
                                                break;
                                            case 'termine':
                                                $status_class = 'status-termine';
                                                $status_icon = '✅';
                                                $status_text = t('completed');
                                                break;
                                            case 'cloturee':
                                                $status_class = 'status-cloturee';
                                                $status_icon = '🔒';
                                                $status_text = t('closed');
                                                break;
                                            case 'annulee':
                                                $status_class = 'status-annulee';
                                                $status_icon = '❌';
                                                $status_text = t('cancelled');
                                                break;
                                            default:
                                                $status_class = 'status-planifiee';
                                                $status_icon = '📅';
                                                $status_text = $m['task_status'];
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo format_date_us($m['created_at'], false); ?></td>
                                        <td><?php echo htmlspecialchars($m['title']); ?></td>
                                        <td>
                                            <span class="priority-badge <?php echo $priority_class; ?>">
                                                <?php echo $priority_icon . ' ' . $priority_text; ?>
                                            </span>
                                        </span>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo $status_icon . ' ' . $status_text; ?>
                                            </span>
                                        </span>
                                        <td class="text-center">
                                            <a href="?page=intervention_view&id=<?php echo $m['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </span>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.onbeforeprint = function() {
    var printArea = document.querySelector('.print-area');
    var normalContent = document.querySelector('.col-md-12');
    if(printArea && normalContent) {
        printArea.style.display = 'block';
        normalContent.style.display = 'none';
    }
};
window.onafterprint = function() {
    var printArea = document.querySelector('.print-area');
    var normalContent = document.querySelector('.col-md-12');
    if(printArea && normalContent) {
        printArea.style.display = 'none';
        normalContent.style.display = 'block';
    }
};

// Vérification du chargement du QR Code
document.addEventListener('DOMContentLoaded', function() {
    var qrImages = document.querySelectorAll('.qr-code img');
    qrImages.forEach(function(img) {
        img.onerror = function() {
            console.log('QR Code API failed, using fallback');
            this.src = '<?php echo $local_qr_url; ?>';
        };
    });
});
</script>