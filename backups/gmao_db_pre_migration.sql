-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: gmao_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attachments`
--

DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_type` enum('equipment','intervention') NOT NULL,
  `parent_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `external_path` varchar(1024) DEFAULT NULL,
  `mime` varchar(100) NOT NULL,
  `size` int(11) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_type`,`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attachments`
--

LOCK TABLES `attachments` WRITE;
/*!40000 ALTER TABLE `attachments` DISABLE KEYS */;
INSERT INTO `attachments` VALUES (1,'equipment',4,'b7b7508578276cd8_1778089451.pdf','DC CF 2025.pdf',NULL,'application/pdf',688658,1,'2026-05-06 19:44:11'),(4,'equipment',1,'72cec6e6ae9aaa91_1778090094.pdf','DC CF 2025.pdf',NULL,'application/pdf',688658,1,'2026-05-06 19:54:54'),(5,'equipment',3,'3e6a329ea0b79920_1778320049.pdf','DC CF 2025.pdf',NULL,'application/pdf',688658,1,'2026-05-09 11:47:29');
/*!40000 ALTER TABLE `attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `calendar_reminders`
--

DROP TABLE IF EXISTS `calendar_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intervention_id` int(11) DEFAULT NULL,
  `reminder_minutes` int(11) DEFAULT 60,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `reminder_type` enum('email','push','both') DEFAULT 'email',
  PRIMARY KEY (`id`),
  KEY `intervention_id` (`intervention_id`),
  CONSTRAINT `calendar_reminders_ibfk_1` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_reminders`
--

LOCK TABLES `calendar_reminders` WRITE;
/*!40000 ALTER TABLE `calendar_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `technical_specs` text DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `status` enum('active','maintenance','broken','retired') DEFAULT 'active',
  `total_operating_hours` int(11) DEFAULT 0,
  `last_failure_date` datetime DEFAULT NULL,
  `total_failures` int(11) DEFAULT 0,
  `total_repair_time` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `probability_score` int(11) DEFAULT 1,
  `severity_score` int(11) DEFAULT 1,
  `zone` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipment`
--

LOCK TABLES `equipment` WRITE;
/*!40000 ALTER TABLE `equipment` DISABLE KEYS */;
INSERT INTO `equipment` VALUES (1,'MAC-001','Machine CNC 5 axes','Fraiseuse','Atelier A','Mazak','2025-01-15','2027-01-15','Power: 15kW, Speed: 12000 RPM',NULL,'active',0,NULL,0,0,'2026-05-01 11:24:20',1,1,NULL),(2,'PRE-002','Presse hydraulique 200T','Presse','Atelier B','Hydram','2022-06-20','2024-06-20','Force: 200 tonnes, Course: 500mm',NULL,'active',0,NULL,0,0,'2026-05-01 11:24:20',1,1,NULL),(3,'CON-003','Convoyeur bande 10m','Convoyeur','Ligne 1','FlexLink','2026-03-10','2029-03-10','Débit: 500 kg/h',NULL,'active',0,NULL,0,0,'2026-05-01 11:24:20',4,4,NULL),(4,'COM-004','Compresseur 75kW','Compresseur','Local technique n°6','Atlas Copco','2021-11-05','2024-11-05','Pression: 8 bars, Débit: 10m³/min\r\nTension: 400V~',NULL,'active',0,NULL,0,0,'2026-05-01 11:24:20',3,2,NULL),(5,'DRI-001','Perceuse 7.5kW','Perceuse à colonne','Atelier n°3','HBM','2026-05-04','2029-05-04','Supply: 400V~',NULL,'active',0,NULL,0,0,'2026-05-08 12:11:45',1,1,NULL);
/*!40000 ALTER TABLE `equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interventions`
--

DROP TABLE IF EXISTS `interventions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `interventions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_number` varchar(20) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `type` enum('corrective','preventive','emergency') DEFAULT 'corrective',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `reported_by` varchar(100) DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `parts_used` text DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `intervenant_id` int(11) DEFAULT NULL,
  `task_status` enum('pending','in_progress','completed','cancelled','closed') DEFAULT 'pending',
  `intervention_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL,
  `task_type` enum('revision','depannage','installation','maintenance_preventive','controle','autre') DEFAULT 'revision',
  `zone` varchar(100) DEFAULT NULL,
  `localisation` varchar(200) DEFAULT NULL,
  `planned_duration` enum('1h','2h','2h30','3h','4h','6h','8h','1j','2j','3j') DEFAULT '4h',
  `completed_date` datetime DEFAULT NULL,
  `completion_report` text DEFAULT NULL,
  `calendar_color` varchar(7) DEFAULT '#3788d8',
  `all_day` tinyint(1) DEFAULT 0,
  `alert_sent` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_number` (`task_number`),
  KEY `equipment_id` (`equipment_id`),
  KEY `intervenant_id` (`intervenant_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_task_number` (`task_number`),
  KEY `idx_scheduled_date` (`intervention_date`),
  KEY `fk_interventions_technician_id` (`technician_id`),
  CONSTRAINT `fk_interventions_technician_id` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interventions_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interventions_ibfk_2` FOREIGN KEY (`intervenant_id`) REFERENCES `technicians` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interventions`
--

LOCK TABLES `interventions` WRITE;
/*!40000 ALTER TABLE `interventions` DISABLE KEYS */;
INSERT INTO `interventions` VALUES (1,'TASK-260032',4,1,'preventive','medium','Révision annuelle du compresseur n°4','Révision globale du compresseur','admin',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'',1,'','2026-05-18',NULL,'revision','Bâtiment C','Salle 7','4h',NULL,NULL,'#3788d8',0,NULL,'2026-05-11 12:53:47');
/*!40000 ALTER TABLE `interventions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (2,'20260506_01_create_attachments.sql','2026-05-06 19:32:50'),(3,'20260506_02_add_external_path.sql','2026-05-06 20:04:17'),(4,'20260524_01_unify_links.sql','2026-05-24 10:03:26');
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_metrics`
--

DROP TABLE IF EXISTS `performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) DEFAULT NULL,
  `date_recorded` date DEFAULT NULL,
  `mtbf` decimal(10,2) DEFAULT NULL,
  `mttr` decimal(10,2) DEFAULT NULL,
  `availability` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `idx_date` (`date_recorded`),
  CONSTRAINT `performance_metrics_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_metrics`
--

LOCK TABLES `performance_metrics` WRITE;
/*!40000 ALTER TABLE `performance_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `preventive_maintenance`
--

DROP TABLE IF EXISTS `preventive_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `preventive_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `last_done` date DEFAULT NULL,
  `next_due` date DEFAULT NULL,
  `instructions` text DEFAULT NULL,
  `assigned_team` varchar(100) DEFAULT NULL,
  `last_alert_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `idx_next_due` (`next_due`),
  CONSTRAINT `preventive_maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `preventive_maintenance`
--

LOCK TABLES `preventive_maintenance` WRITE;
/*!40000 ALTER TABLE `preventive_maintenance` DISABLE KEYS */;
INSERT INTO `preventive_maintenance` VALUES (1,4,90,'2026-05-23','2026-08-21','','',NULL);
/*!40000 ALTER TABLE `preventive_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_log`
--

DROP TABLE IF EXISTS `report_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `report_log` (
  `id` int(11) NOT NULL,
  `last_report_sent` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_log`
--

LOCK TABLES `report_log` WRITE;
/*!40000 ALTER TABLE `report_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(20) NOT NULL,
  `page` varchar(50) NOT NULL,
  `allowed` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role` (`role`,`page`)
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (111,'supervisor','dashboard',1),(112,'supervisor','performance',1),(113,'supervisor','equipment',1),(114,'supervisor','equipment_detail',1),(115,'supervisor','interventions',1),(116,'supervisor','intervention_add',1),(117,'supervisor','intervention_view',1),(118,'supervisor','preventive',1),(119,'supervisor','stock',1),(120,'supervisor','technicians',1),(121,'supervisor','alerts',1),(122,'supervisor','planning',1),(123,'supervisor','profile',1),(124,'supervisor','technician_detail',1),(125,'supervisor','mail_settings',1),(126,'supervisor','users',1),(127,'supervisor','export_center',1),(128,'supervisor','criticality_matrix',1),(129,'supervisor','calendar',1),(130,'supervisor','equipment_attachments',1),(131,'supervisor','equipment_edit',1),(132,'supervisor','intervention_edit',1),(133,'supervisor','preventive_edit',1),(134,'supervisor','technician_edit',1),(135,'supervisor','stock_detail',1),(136,'technician','dashboard',1),(137,'technician','equipment',1),(138,'technician','equipment_detail',1),(139,'technician','interventions',1),(140,'technician','intervention_view',1),(141,'technician','preventive',1),(142,'technician','stock',1),(143,'technician','technicians',1),(144,'technician','alerts',1),(145,'technician','planning',1),(146,'technician','technician_detail',1),(147,'technician','users',1),(148,'technician','calendar',1),(149,'technician','equipment_attachments',1),(150,'technician','stock_detail',1),(151,'viewer','dashboard',1),(152,'viewer','performance',1),(153,'viewer','equipment',1),(154,'viewer','equipment_detail',1),(155,'viewer','interventions',1),(156,'viewer','intervention_view',1),(157,'viewer','preventive',1),(158,'viewer','stock',1),(159,'viewer','technicians',1),(160,'viewer','alerts',1),(161,'viewer','planning',1),(162,'viewer','technician_detail',1),(163,'viewer','users',1),(164,'viewer','criticality_matrix',1),(165,'viewer','calendar',1),(166,'viewer','equipment_attachments',1),(167,'viewer','stock_detail',1);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spare_parts`
--

DROP TABLE IF EXISTS `spare_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spare_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `part_number` varchar(50) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_quantity` int(11) DEFAULT 5,
  `location` varchar(100) DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `last_restock` date DEFAULT NULL,
  `last_alert_sent` datetime DEFAULT NULL,
  `documentation_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `part_number` (`part_number`),
  KEY `idx_quantity` (`quantity`),
  KEY `idx_min_quantity` (`min_quantity`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spare_parts`
--

LOCK TABLES `spare_parts` WRITE;
/*!40000 ALTER TABLE `spare_parts` DISABLE KEYS */;
INSERT INTO `spare_parts` VALUES (1,'COU-008','Courroie n°8',15,5,'Atelier n°3','LaBonneCourroie',9.59,'2026-05-18',NULL,''),(2,'MOT-012','Moteur 12 kW',3,5,'Atelier n°3','SuperMoteurs',1227.59,'2026-05-04',NULL,'');
/*!40000 ALTER TABLE `spare_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `part_id` int(11) DEFAULT NULL,
  `movement_type` enum('in','out') DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `intervention_id` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `movement_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `part_id` (`part_id`),
  KEY `intervention_id` (`intervention_id`),
  KEY `idx_movement_date` (`movement_date`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_sequence`
--

DROP TABLE IF EXISTS `task_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_sequence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_number` int(11) DEFAULT 260031,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_sequence`
--

LOCK TABLES `task_sequence` WRITE;
/*!40000 ALTER TABLE `task_sequence` DISABLE KEYS */;
INSERT INTO `task_sequence` VALUES (1,260032,'2026-05-11 12:53:47');
/*!40000 ALTER TABLE `task_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technician_availability`
--

DROP TABLE IF EXISTS `technician_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technician_availability` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `technician_id` int(11) DEFAULT NULL,
  `available_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_availability` (`technician_id`,`available_date`),
  KEY `idx_date` (`available_date`),
  CONSTRAINT `technician_availability_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technician_availability`
--

LOCK TABLES `technician_availability` WRITE;
/*!40000 ALTER TABLE `technician_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `technician_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technician_skills`
--

DROP TABLE IF EXISTS `technician_skills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technician_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `technician_id` int(11) DEFAULT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `skill_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `certified` tinyint(1) DEFAULT 0,
  `certification_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_skill` (`technician_id`,`equipment_type`),
  CONSTRAINT `technician_skills_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technician_skills`
--

LOCK TABLES `technician_skills` WRITE;
/*!40000 ALTER TABLE `technician_skills` DISABLE KEYS */;
INSERT INTO `technician_skills` VALUES (2,1,'Electrical repairs','expert',0,NULL);
/*!40000 ALTER TABLE `technician_skills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technicians`
--

DROP TABLE IF EXISTS `technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `technicians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `specialty` varchar(200) DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive','on_leave') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `technicians_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technicians`
--

LOCK TABLES `technicians` WRITE;
/*!40000 ALTER TABLE `technicians` DISABLE KEYS */;
INSERT INTO `technicians` VALUES (1,NULL,'001','Bobby','Calagan','06 12 34 56 78','bobby@gmao.com','Electrician, Mecanician',NULL,'2021-01-18','active','2026-05-09 11:54:46');
/*!40000 ALTER TABLE `technicians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_logs`
--

DROP TABLE IF EXISTS `user_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=170 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_logs`
--

LOCK TABLES `user_logs` WRITE;
/*!40000 ALTER TABLE `user_logs` DISABLE KEYS */;
INSERT INTO `user_logs` VALUES (1,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-02 11:08:54'),(2,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-03 09:46:29'),(3,1,'equipment_updated','Equipment ID: 4 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-03 09:48:21'),(4,1,'profile_updated','Profile updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-04 12:33:39'),(5,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-04 14:19:27'),(6,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-04 14:20:09'),(7,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-04 16:46:27'),(8,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-04 16:46:53'),(9,1,'login_success','Login successful','::1',NULL,'2026-05-05 21:49:51'),(10,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-05 21:51:09'),(11,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-05 21:53:51'),(12,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-05 21:54:39'),(13,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-05 21:56:56'),(14,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 09:11:38'),(15,1,'login_success','Login successful','::1',NULL,'2026-05-06 10:12:53'),(16,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 10:18:08'),(17,1,'login_success','Login successful','::1',NULL,'2026-05-06 17:35:33'),(18,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:14:02'),(19,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:18:25'),(20,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:23:55'),(21,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:36:00'),(22,1,'equipment_updated','Equipment ID: 4 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-06 22:37:17'),(23,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:45:57'),(24,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:54:30'),(25,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-06 22:56:23'),(26,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:53:09'),(27,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:54:04'),(28,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:54:30'),(29,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:54:52'),(30,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:57:12'),(31,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:59:21'),(32,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 08:59:49'),(33,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 09:00:25'),(34,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 09:00:57'),(35,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 09:01:23'),(36,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 09:02:03'),(37,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-08 09:16:59'),(38,1,'equipment_created','Equipment created: DRI-001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-08 12:11:45'),(39,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-09 07:47:50'),(40,1,'login_success','Login successful','::1',NULL,'2026-05-09 11:08:30'),(41,1,'technician_created','Technician created: 001','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-09 11:54:46'),(42,1,'technician_updated','Technician ID: 1 modified','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-09 15:48:37'),(43,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-09 19:01:46'),(44,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 09:49:01'),(45,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 10:18:23'),(46,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 10:18:46'),(47,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 10:27:47'),(48,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 10:55:56'),(49,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 11:10:36'),(50,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 11:28:09'),(51,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 11:38:36'),(52,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 15:48:06'),(53,1,'login_success','Login successful','::1',NULL,'2026-05-10 15:48:43'),(54,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-10 15:59:54'),(55,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 09:48:29'),(56,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 12:36:02'),(57,1,'intervention_created','Intervention créée: TASK-260032','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-11 12:53:47'),(58,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:41:35'),(59,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:46:30'),(60,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:47:44'),(61,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:49:27'),(62,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:52:53'),(63,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:56:29'),(64,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 18:56:59'),(65,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-11 19:03:26'),(66,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-11 19:08:32'),(67,1,'equipment_updated','Equipment ID: 5 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-11 19:09:14'),(68,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-11 19:09:53'),(69,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-11 19:12:36'),(70,1,'equipment_updated','[CON-003] Convoyeur bande 10m - Equipment information updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-12 08:47:55'),(71,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 08:48:28'),(72,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:40:36'),(73,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:45:57'),(74,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:46:43'),(75,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:47:22'),(76,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:54:50'),(77,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:58:12'),(78,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 18:58:36'),(79,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:02:01'),(80,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:03:47'),(81,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:04:21'),(82,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:05:43'),(83,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:07:16'),(84,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:13:35'),(85,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:16:56'),(86,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:17:34'),(87,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:20:19'),(88,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:21:39'),(89,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:30:18'),(90,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:34:33'),(91,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:35:03'),(92,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:37:09'),(93,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:41:29'),(94,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:42:00'),(95,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-12 19:42:33'),(96,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 08:32:05'),(97,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 08:32:34'),(98,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 08:34:07'),(99,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 08:35:16'),(100,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 08:36:38'),(101,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:23:30'),(102,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:25:17'),(103,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:30:07'),(104,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:34:29'),(105,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:36:58'),(106,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:40:51'),(107,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:41:20'),(108,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:42:30'),(109,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:43:53'),(110,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:47:35'),(111,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:50:58'),(112,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:54:31'),(113,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 09:58:09'),(114,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:03:22'),(115,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:04:16'),(116,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:05:12'),(117,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:06:26'),(118,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:12:12'),(119,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:14:27'),(120,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:16:43'),(121,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 10:19:07'),(122,1,'technician_updated','[Bobby Calagan] - No changes recorded','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 10:20:45'),(123,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 11:01:23'),(124,1,'technician_updated','[Bobby Calagan] - No changes recorded','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 11:01:32'),(125,1,'equipment_updated','[MAC-001] Machine CNC 5 axes - Equipment information updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 11:45:02'),(126,1,'equipment_updated','[MAC-001] Machine CNC 5 axes - Equipment information updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 11:45:24'),(127,1,'equipment_updated','[MAC-001] Machine CNC 5 axes - Equipment information updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 11:46:40'),(128,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 17:37:06'),(129,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 17:39:48'),(130,1,'equipment_updated','[MAC-001] Machine CNC 5 axes - Equipment information updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 17:40:17'),(131,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 17:54:51'),(132,1,'equipment_updated','[MAC-001] Machine CNC 5 axes - Equipment updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-13 17:55:02'),(133,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 18:44:52'),(134,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 19:08:31'),(135,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-13 19:19:43'),(136,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 09:43:39'),(137,1,'equipment_updated','Equipment ID: 4 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-14 09:44:08'),(138,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 09:49:54'),(139,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0','2026-05-14 09:50:12'),(140,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 09:52:46'),(141,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 10:01:38'),(142,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 10:08:25'),(143,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 10:17:02'),(144,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-14 10:26:37'),(145,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-15 07:46:19'),(146,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-15 07:54:46'),(147,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-15 08:00:55'),(148,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-15 08:05:36'),(149,1,'login_success','Login successful','127.0.0.1',NULL,'2026-05-16 08:55:01'),(150,1,'login_success','Connexion réussie','127.0.0.1',NULL,'2026-05-18 13:29:14'),(151,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:32:25'),(152,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:34:22'),(153,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:36:13'),(154,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:36:57'),(155,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:37:11'),(156,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:38:51'),(157,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-22 01:39:31'),(158,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-23 00:02:53'),(159,1,'stock_created','Part created: COU-008','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-23 15:57:18'),(160,1,'stock_updated','Part ID: 1 modified','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-23 15:58:30'),(161,1,'preventive_created','Preventive maintenance created for equipment ID: 4','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-23 20:16:03'),(162,1,'intervention_updated','Intervention ID: 1 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 12:18:28'),(163,1,'equipment_updated','Equipment ID: 4 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 17:16:01'),(164,1,'equipment_updated','Equipment ID: 5 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 17:17:16'),(165,1,'intervention_updated','Intervention ID: 1 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 17:18:14'),(166,1,'stock_created','Part created: MOT-012','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 22:10:59'),(167,1,'equipment_updated','Equipment ID: 3 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-25 22:22:41'),(168,1,'intervention_updated','Intervention ID: 1 updated','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0','2026-05-26 19:13:46'),(169,1,'login_success','login_success','127.0.0.1',NULL,'2026-05-30 20:44:40');
/*!40000 ALTER TABLE `user_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `last_activity` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `role` enum('admin','supervisor','technician','viewer') DEFAULT 'technician',
  `email` varchar(100) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$GFM/xX5B0UPZEXZnTxsTaOXQ9o3kO1VMo1Oul.6fgVyxl6JrJPXza','Administrator','admin','admin@gmao.com','2026-05-30 20:44:40','127.0.0.1',1,NULL,NULL,'2026-05-01 11:24:20'),(2,'superviseur','$2y$10$r2ZPfh1QwFPavejDYCmGguDDEXtTA7vp64N.JGhXEQhH/n9Gzkone','Superviseur','supervisor','superviseur@gmao.com',NULL,NULL,1,NULL,NULL,'2026-05-01 11:42:04'),(3,'technicien','$2y$10$r2ZPfh1QwFPavejDYCmGguDDEXtTA7vp64N.JGhXEQhH/n9Gzkone','Technicien','technician','technicien@gmao.com',NULL,NULL,1,NULL,NULL,'2026-05-01 11:42:04'),(4,'visiteur','$2y$10$r2ZPfh1QwFPavejDYCmGguDDEXtTA7vp64N.JGhXEQhH/n9Gzkone','Visiteur','viewer','visiteur@gmao.com',NULL,NULL,1,NULL,NULL,'2026-05-01 11:42:04');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `work_schedule`
--

DROP TABLE IF EXISTS `work_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `technician_id` int(11) DEFAULT NULL,
  `intervention_id` int(11) DEFAULT NULL,
  `scheduled_start` datetime DEFAULT NULL,
  `scheduled_end` datetime DEFAULT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `technician_id` (`technician_id`),
  KEY `intervention_id` (`intervention_id`),
  KEY `idx_scheduled_start` (`scheduled_start`),
  CONSTRAINT `work_schedule_ibfk_1` FOREIGN KEY (`technician_id`) REFERENCES `technicians` (`id`) ON DELETE CASCADE,
  CONSTRAINT `work_schedule_ibfk_2` FOREIGN KEY (`intervention_id`) REFERENCES `interventions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_schedule`
--

LOCK TABLES `work_schedule` WRITE;
/*!40000 ALTER TABLE `work_schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_schedule` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-30 23:00:44
