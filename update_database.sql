-- update_database.sql - Ajout des colonnes pour les alertes
ALTER TABLE preventive_maintenance ADD COLUMN IF NOT EXISTS last_alert_sent DATETIME NULL;
ALTER TABLE spare_parts ADD COLUMN IF NOT EXISTS last_alert_sent DATETIME NULL;
ALTER TABLE interventions ADD COLUMN IF NOT EXISTS alert_sent DATETIME NULL;

-- Table pour le suivi des rapports hebdomadaires
CREATE TABLE IF NOT EXISTS report_log (
    id INT PRIMARY KEY,
    last_report_sent DATETIME NULL
);