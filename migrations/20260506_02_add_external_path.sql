-- 20260506_02_add_external_path.sql
-- Add external_path column to attachments to store URLs or local paths
-- Add column only if missing (safer across MySQL versions)
SET @s = (
	SELECT IF(
		(SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attachments' AND COLUMN_NAME = 'external_path') = 0,
		"ALTER TABLE attachments ADD COLUMN external_path VARCHAR(1024) NULL DEFAULT NULL AFTER original_name",
		'SELECT 1'
	)
);
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
