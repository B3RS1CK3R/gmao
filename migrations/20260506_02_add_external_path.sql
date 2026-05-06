-- 20260506_02_add_external_path.sql
-- Add external_path column to attachments to store URLs or local paths
ALTER TABLE attachments
ADD COLUMN external_path VARCHAR(1024) NULL DEFAULT NULL AFTER original_name;
