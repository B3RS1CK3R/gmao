-- 20260506_01_create_attachments.sql
-- Table to store attachments linked to equipments or interventions
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `parent_type` ENUM('equipment','intervention') NOT NULL,
  `parent_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(100) NOT NULL,
  `size` INT NOT NULL,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_parent` (`parent_type`,`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
