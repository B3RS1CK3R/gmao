<?php
// pages/equipment_attachments.php - Manage attachments for equipments
if(!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit();
}

// Only admins and supervisors can manage global attachments
if(!in_array($_SESSION['role'], ['admin','supervisor'])) {
    echo "<div class='alert alert-danger'>" . t('access_denied') . "</div>";
    return;
}

$equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;
if($equipment_id > 0) {
    $stmt = $pdo->prepare("SELECT a.*, e.name as equipment_name, e.code as equipment_code FROM attachments a JOIN equipment e ON a.parent_id = e.id WHERE a.parent_type = 'equipment' AND a.parent_id = ? ORDER BY a.created_at DESC");
    $stmt->execute([$equipment_id]);
    $attachments = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT a.*, e.name as equipment_name, e.code as equipment_code FROM attachments a JOIN equipment e ON a.parent_id = e.id WHERE a.parent_type = 'equipment' ORDER BY a.created_at DESC");
    $attachments = $stmt->fetchAll();
}

?>
<?php $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
<style>
    .attach-card { background: white; border-radius:10px; padding:12px; margin-bottom:12px; }
    .attach-thumb { width:80px; height:60px; object-fit:cover; border-radius:6px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-paperclip"></i> <?php echo t('equipment_attachments'); ?></h2>
    <div>
        <?php if($equipment_id > 0): ?>
            <a href="?page=equipment_attachments" class="btn btn-sm btn-secondary"><?php echo t('view_all'); ?></a>
        <?php endif; ?>
        <a href="?page=equipment" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> <?php echo t('back'); ?></a>
    </div>
</div>

<?php if(empty($attachments)): ?>
    <div class="text-muted"><?php echo t('no_documents'); ?></div>
<?php else: ?>
    <div class="row">
        <?php foreach($attachments as $att): ?>
        <div class="col-md-4">
            <div class="attach-card">
                <div class="d-flex gap-2">
                    <div>
                        <?php if(strpos($att['mime'],'image/') === 0): ?>
                            <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" target="_blank"><img src="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" class="attach-thumb" alt=""></a>
                        <?php else: ?>
                            <div style="width:80px;height:60px;background:#f5f5f5;border-radius:6px;display:flex;align-items:center;justify-content:center;"><?php echo strtoupper(pathinfo($att['original_name'], PATHINFO_EXTENSION)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;">
                        <div><strong><?php echo htmlspecialchars($att['original_name']); ?></strong></div>
                        <div class="small text-muted"><?php echo htmlspecialchars($att['equipment_name']) . ' (' . htmlspecialchars($att['equipment_code']) . ')'; ?></div>
                        <div class="small text-muted"><?php echo format_date_us($att['created_at'], true); ?></div>
                            <div class="mt-2">
                                <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" target="_blank" class="btn btn-sm btn-secondary"><?php echo t('view'); ?></a>
                                <a href="<?php echo $baseUrl; ?>/uploads/attachments/equipment/<?php echo $att['parent_id']; ?>/<?php echo htmlspecialchars($att['filename']); ?>" download class="btn btn-sm btn-info"><?php echo t('download'); ?></a>
                                <form action="api/delete_attachment.php" method="post" style="display:inline-block;" onsubmit="return confirm('<?php echo t('delete_confirm'); ?>');">
                                    <input type="hidden" name="id" value="<?php echo $att['id']; ?>">
                                    <?php echo csrf_input(); ?>
                                    <button class="btn btn-sm btn-danger" type="submit"><?php echo t('delete'); ?></button>
                                </form>
                            </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
