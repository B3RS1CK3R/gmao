<?php
// pages/preventive_view.php - Détails d'une maintenance préventive (version sécurisée)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    header('Location: ?page=preventive');
    exit();
}

// Récupération de la tâche préventive avec l'équipement
$stmt = $pdo->prepare("
    SELECT pm.*, 
            e.name as equipment_name, 
            e.code as equipment_code,
            e.location as equipment_location,
            t.id as tech_id, 
            t.firstname, 
            t.lastname,
            team.id as team_id, 
            team.name as team_name
    FROM preventive_maintenance pm
    JOIN equipment e ON pm.equipment_id = e.id
    LEFT JOIN technicians t ON pm.technician_id = t.id
    LEFT JOIN teams team ON pm.team_id = team.id
    WHERE pm.id = ?
");
$stmt->execute([$id]);
$pm = $stmt->fetch();

if (!$pm) {
    echo "<div class='alert alert-danger'>" . t('not_found') . "</div>";
    return;
}

// Historique des modifications
$stmt = $pdo->prepare("
    SELECT * FROM user_logs 
    WHERE action IN ('preventive_created', 'preventive_updated', 'preventive_assigned', 'preventive_completed')
    AND details LIKE ?
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->execute(["%ID: {$id}%"]);
$history = $stmt->fetchAll();

// Interventions liées (optionnel)
$interventions = [];
try {
    $check = $pdo->query("SHOW COLUMNS FROM interventions LIKE 'preventive_id'");
    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM interventions WHERE preventive_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$id]);
        $interventions = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Ignorer si la colonne n'existe pas
}

// Déterminer le statut
$status = $pm['task_status'] ?? 'pending';
$status_labels = [
    'pending' => '🟡 ' . t('pending'),
    'in_progress' => '🔵 ' . t('in_progress'),
    'completed' => '🟢 ' . t('completed'),
    'cancelled' => '⚫ ' . t('cancelled')
];
$status_display = $status_labels[$status] ?? $status;

// Numéro de tâche (task_number ou reference)
$task_number = $pm['task_number'] ?? $pm['reference'] ?? '#' . $pm['id'];
?>

<style>
    .detail-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .detail-header {
        background: linear-gradient(135deg, #fd7e14, #e06a0a);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
    }
    .detail-header .badge {
        font-size: 14px;
        padding: 8px 15px;
    }
    .info-row {
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .label {
        font-weight: 600;
        width: 150px;
        display: inline-block;
    }
    .action-buttons .btn {
        margin-right: 8px;
    }
    .history-item {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .team-box {
        background: #e3f2fd;
        border: 2px solid #0d6efd;
        border-radius: 10px;
        padding: 8px 15px;
        margin-top: 5px;
        display: inline-block;
        font-weight: 600;
        color: #0d6efd;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-calendar-check"></i> 
            <?php echo t('preventive_maintenance'); ?> : 
            <?php echo htmlspecialchars($task_number); ?>
        </h2>
        <div>
            <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'supervisor'): ?>
                <a href="?page=preventive_edit&id=<?php echo $pm['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> <?php echo t('edit'); ?>
                </a>
                <a href="?page=preventive_assign&id=<?php echo $pm['id']; ?>" class="btn btn-info">
                    <i class="fas fa-user-plus"></i> <?php echo t('assign'); ?>
                </a>
                <?php if ($status != 'completed'): ?>
                    <a href="?page=preventive_complete&id=<?php echo $pm['id']; ?>" class="btn btn-success">
                        <i class="fas fa-check"></i> <?php echo t('mark_completed'); ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="?page=preventive" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> <?php echo t('back'); ?>
            </a>
        </div>
    </div>

    <!-- Informations principales -->
    <div class="row">
        <div class="col-md-8">
            <div class="detail-card">
                <div class="detail-header">
                    <i class="fas fa-info-circle"></i> <?php echo t('task_details'); ?>
                    <span class="badge bg-light text-dark float-end"><?php echo $status_display; ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="info-row">
                        <span class="label"><?php echo t('task_number'); ?> :</span>
                        <?php echo htmlspecialchars($task_number); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('title'); ?> :</span>
                        <?php echo htmlspecialchars($pm['title'] ?? ''); ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('equipment'); ?> :</span>
                        <a href="?page=equipment_detail&id=<?php echo $pm['equipment_id']; ?>">
                            <?php echo htmlspecialchars(($pm['equipment_code'] ?? '') . ' - ' . ($pm['equipment_name'] ?? '')); ?>
                        </a>
                        <?php if (!empty($pm['equipment_location'])): ?>
                            <span class="text-muted">(<?php echo htmlspecialchars($pm['equipment_location']); ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('frequency_days'); ?> :</span>
                        <?php echo isset($pm['frequency_days']) ? $pm['frequency_days'] . ' ' . t('days_s') : '-'; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('last_done'); ?> :</span>
                        <?php echo !empty($pm['last_done']) ? format_date_local($pm['last_done'], 'short') : '-'; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('next_due'); ?> :</span>
                        <?php if (!empty($pm['next_due'])): ?>
                            <strong><?php echo format_date_local($pm['next_due'], 'short'); ?></strong>
                            <?php
                            $today = new DateTime();
                            $due = new DateTime($pm['next_due']);
                            if ($due < $today) {
                                echo ' <span class="badge bg-danger">' . t('overdue') . '</span>';
                            } elseif ($due <= (new DateTime())->modify('+7 days')) {
                                echo ' <span class="badge bg-warning text-dark">' . t('soon') . '</span>';
                            }
                            ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </div>
                    <div class="info-row">
                        <span class="label"><?php echo t('instructions'); ?> :</span>
                        <div class="mt-1"><?php echo nl2br(htmlspecialchars($pm['instructions'] ?? '')); ?></div>
                    </div>
                    <?php if (!empty($pm['duration_hours'])): ?>
                    <div class="info-row">
                        <span class="label"><?php echo t('duration_hours'); ?> :</span>
                        <?php echo $pm['duration_hours'] . ' h'; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($pm['completion_report'])): ?>
                    <div class="info-row">
                        <span class="label"><?php echo t('completion_report'); ?> :</span>
                        <div class="mt-1"><?php echo nl2br(htmlspecialchars($pm['completion_report'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assignation et informations -->
        <div class="col-md-4">
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #17a2b8, #0c6b7e);">
                    <i class="fas fa-user-cog"></i> <?php echo t('assignment'); ?>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($pm['technician_id']) || !empty($pm['team_id'])): ?>
                        <?php if (!empty($pm['technician_id'])): ?>
                            <div class="info-row">
                                <span class="label"><?php echo t('technician'); ?> :</span>
                                <?php echo htmlspecialchars(($pm['firstname'] ?? '') . ' ' . ($pm['lastname'] ?? '')); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($pm['team_id'])): ?>
                            <div class="info-row">
                                <span class="label"><?php echo t('team'); ?> :</span>
                                <span class="team-box">
                                    <i class="fas fa-users"></i><?php echo htmlspecialchars($pm['team_name'] ?? ''); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($pm['assigned_at'])): ?>
                            <div class="info-row">
                                <span class="label"><?php echo t('assigned_at'); ?> :</span>
                                <?php echo format_date_local($pm['assigned_at'], 'long', true); ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($pm['assign_comments'])): ?>
                            <div class="info-row">
                                <span class="label"><?php echo t('comments'); ?> :</span>
                                <?php echo nl2br(htmlspecialchars($pm['assign_comments'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> <?php echo t('not_assigned_yet'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique -->
            <?php if (!empty($history)): ?>
                <div class="detail-card">
                    <div class="detail-header" style="background: linear-gradient(135deg, #6c757d, #495057);">
                        <i class="fas fa-history"></i> <?php echo t('modification_history'); ?>
                    </div>
                    <div class="card-body p-3">
                        <?php foreach ($history as $h): ?>
                            <div class="history-item">
                                <div class="d-flex justify-content-between">
                                    <span>
                                        <?php
                                        $action_icons = [
                                            'preventive_created' => '🟢 ' . t('created'),
                                            'preventive_updated' => '✏️ ' . t('updated'),
                                            'preventive_assigned' => '👤 ' . t('assigned'),
                                            'preventive_completed' => '✅ ' . t('completed')
                                        ];
                                        echo $action_icons[$h['action']] ?? $h['action'];
                                        ?>
                                    </span>
                                    <small class="text-muted"><?php echo format_date_local($h['created_at'], 'long', true); ?></small>
                                </div>
                                <small class="text-muted">
                                    <?php echo t('by') . ' : ' . htmlspecialchars($h['username'] ?? 'Inconnu'); ?>
                                    <?php if (!empty($h['details'])): ?>
                                        <br><?php echo htmlspecialchars($h['details']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Interventions liées (si existent) -->
    <?php if (!empty($interventions)): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="detail-card">
                <div class="detail-header" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                    <i class="fas fa-tasks"></i> <?php echo t('related_interventions'); ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo t('task_number'); ?></th>
                                    <th><?php echo t('title'); ?></th>
                                    <th><?php echo t('status'); ?></th>
                                    <th><?php echo t('created_at'); ?></th>
                                    <th><?php echo t('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventions as $inv): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($inv['task_number'] ?? '#' . $inv['id']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['title'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($inv['task_status'] ?? 'pending') == 'completed' ? 'success' : (($inv['task_status'] ?? '') == 'in_progress' ? 'primary' : 'warning'); ?>">
                                            <?php echo t($inv['task_status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($inv['created_at']) ? format_date_local($inv['created_at'], 'short', true) : '-'; ?></td>
                                    <td>
                                        <a href="?page=intervention_view&id=<?php echo $inv['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>