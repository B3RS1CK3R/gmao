-- migration for unifying links and standardizing statuses
-- 1. Ensure technician_id exists and is linked correctly
ALTER TABLE interventions ADD COLUMN IF NOT EXISTS technician_id INT AFTER equipment_id;

-- 2. Migrate data from intervenant_id to technician_id if intervenant_id exists
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'interventions' AND COLUMN_NAME = 'intervenant_id') > 0,
    'UPDATE interventions SET technician_id = intervenant_id WHERE technician_id IS NULL AND intervenant_id IS NOT NULL',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Standardize task_status column
-- First, ensure it's an ENUM with English values
ALTER TABLE interventions MODIFY COLUMN task_status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'closed') DEFAULT 'pending';

-- Map old French values to new English values
UPDATE interventions SET task_status = 'pending' WHERE task_status = 'a_faire' OR task_status IS NULL;
UPDATE interventions SET task_status = 'in_progress' WHERE task_status = 'en_cours';
UPDATE interventions SET task_status = 'completed' WHERE task_status = 'termine';
UPDATE interventions SET task_status = 'closed' WHERE task_status = 'cloturee';

-- 4. Sync the 'status' column with 'task_status' if it exists
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'interventions' AND COLUMN_NAME = 'status') > 0,
    'UPDATE interventions SET status = task_status',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add foreign key if missing
-- We check if the constraint exists first (optional but safer)
-- ALTER TABLE interventions ADD FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL;
