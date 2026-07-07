-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: irc_db
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
-- Table structure for table `patient_daily_logs`
--

DROP TABLE IF EXISTS `patient_daily_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_daily_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `log_date` date NOT NULL,
  `mood_score` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `patient_date` (`patient_id`,`log_date`),
  CONSTRAINT `patient_daily_logs_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patient_daily_logs`
--

LOCK TABLES `patient_daily_logs` WRITE;
/*!40000 ALTER TABLE `patient_daily_logs` DISABLE KEYS */;
INSERT INTO `patient_daily_logs` VALUES (1,1,'2026-07-04',3,'Withdrawal symptoms are fading. Feeling physically exhausted but stable.','2026-07-06 09:14:51'),(2,1,'2026-07-05',4,'Participated fully in group session today. Rested well.','2026-07-06 09:14:51');
/*!40000 ALTER TABLE `patient_daily_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `admission_date` date NOT NULL,
  `treatment_status` varchar(50) NOT NULL DEFAULT 'Intake',
  `assigned_therapist_id` int(11) DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assigned_therapist_id` (`assigned_therapist_id`),
  CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`assigned_therapist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES (1,'Ali Raza',28,'Male','2026-06-01','Detox',3,'Alcohol substance rehabilitation and detoxification.'),(2,'Zainab Bibi',32,'Female','2026-05-15','Rehab',3,'Depression therapy and behavioral modification treatment.'),(3,'Hamza Mughal',24,'Male','2026-06-10','Intake',2,'Initial screening completed. Opiate addiction history.'),(4,'haseeb javed',23,'Male','2026-09-19','Outpatient',3,'anxiety disorder and depression'),(6,'haseeb ahmad',33,'Male','2026-06-20','Discharged',3,'');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_access`
--

DROP TABLE IF EXISTS `role_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_access` (
  `role_key` varchar(50) NOT NULL,
  `page_id` int(11) NOT NULL,
  PRIMARY KEY (`role_key`,`page_id`),
  KEY `page_id` (`page_id`),
  CONSTRAINT `role_access_ibfk_1` FOREIGN KEY (`role_key`) REFERENCES `sys_roles` (`role_key`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `role_access_ibfk_2` FOREIGN KEY (`page_id`) REFERENCES `sys_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_access`
--

LOCK TABLES `role_access` WRITE;
/*!40000 ALTER TABLE `role_access` DISABLE KEYS */;
INSERT INTO `role_access` VALUES ('clinical_director',1),('clinical_director',2),('clinical_director',3),('clinical_director',4),('clinical_director',10),('clinical_director',11),('counselor',1),('counselor',2),('counselor',3),('counselor',10),('counselor',11),('doctor',1),('doctor',2),('doctor',3),('doctor',4),('doctor',10),('doctor',11),('patient',1),('patient',11),('super_admin',1),('super_admin',2),('super_admin',3),('super_admin',4),('super_admin',5),('super_admin',6),('super_admin',7),('super_admin',8),('super_admin',9),('super_admin',10),('super_admin',11);
/*!40000 ALTER TABLE `role_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_requests`
--

DROP TABLE IF EXISTS `support_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolution_notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `support_requests_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_requests`
--

LOCK TABLES `support_requests` WRITE;
/*!40000 ALTER TABLE `support_requests` DISABLE KEYS */;
INSERT INTO `support_requests` VALUES (1,1,'Anxiety spike in evenings','I am experiencing mild anxiety around 8 PM. Can I discuss this with Therapist Bilal?','Pending','2026-07-06 09:14:51',NULL);
/*!40000 ALTER TABLE `support_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_pages`
--

DROP TABLE IF EXISTS `sys_pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `page_name` varchar(100) NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `icon_class` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `sys_pages_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `sys_pages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_pages`
--

LOCK TABLES `sys_pages` WRITE;
/*!40000 ALTER TABLE `sys_pages` DISABLE KEYS */;
INSERT INTO `sys_pages` VALUES (1,NULL,'Dashboard','dashboard.php','bi bi-speedometer2',1),(2,NULL,'Clinical Operations','#','bi bi-heart-pulse-fill',2),(3,2,'Patient Intake','dashboards/rehab/manage_patients.php','bi bi-person-badge-fill',1),(4,2,'Therapy Sessions','dashboards/rehab/therapy_sessions.php','bi bi-calendar-event',2),(5,NULL,'System Management','#','bi bi-gear-fill',3),(6,5,'Manage Pages','dashboards/super_admin/manage_pages.php','bi bi-file-earmark-medical',1),(7,5,'Manage Roles','dashboards/super_admin/manage_roles.php','bi bi-shield-lock',2),(8,5,'Manage Users','dashboards/super_admin/manage_users.php','bi bi-people-fill',3),(9,5,'Thesis Documentation','thesis_documentation.html','bi bi-file-earmark-pdf-fill',4),(10,2,'Treatment Plans','dashboards/rehab/manage_plans.php','bi bi-clipboard2-pulse',3),(11,2,'Support Tickets','dashboards/rehab/support_requests.php','bi bi-chat-left-text',4);
/*!40000 ALTER TABLE `sys_pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sys_roles`
--

DROP TABLE IF EXISTS `sys_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sys_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `role_key` varchar(50) NOT NULL,
  `is_system_role` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sys_roles`
--

LOCK TABLES `sys_roles` WRITE;
/*!40000 ALTER TABLE `sys_roles` DISABLE KEYS */;
INSERT INTO `sys_roles` VALUES (1,'Super Admin','super_admin',1),(2,'Clinical Director','clinical_director',0),(3,'Doctor / Therapist','doctor',0),(4,'Case Counselor','counselor',0),(5,'Patient','patient',0),(6,'Suspended','suspended',1);
/*!40000 ALTER TABLE `sys_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('footer_text','© 2026 Islamabad Rehab Center. All rights reserved.'),('system_logo','logo.png'),('system_name','Islamabad Rehab Center');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `therapy_sessions`
--

DROP TABLE IF EXISTS `therapy_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `therapy_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `therapist_id` int(11) NOT NULL,
  `session_date` datetime NOT NULL,
  `session_type` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `therapist_id` (`therapist_id`),
  CONSTRAINT `therapy_sessions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `therapy_sessions_ibfk_2` FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `therapy_sessions`
--

LOCK TABLES `therapy_sessions` WRITE;
/*!40000 ALTER TABLE `therapy_sessions` DISABLE KEYS */;
INSERT INTO `therapy_sessions` VALUES (1,1,3,'2026-06-18 10:00:00','Individual','Detox stage progress is steady. Physical withdrawal symptoms declining.'),(2,2,3,'2026-06-18 11:30:00','Group','Active participation in peer sharing. Expressed optimistic views on recovery.'),(3,1,3,'2026-06-20 08:16:00','Family','vgyvuvuybuj nl');
/*!40000 ALTER TABLE `therapy_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `treatment_plans`
--

DROP TABLE IF EXISTS `treatment_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `treatment_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `detox_plan` text DEFAULT NULL,
  `therapy_goals` text DEFAULT NULL,
  `aftercare_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `treatment_plans_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `treatment_plans_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `treatment_plans`
--

LOCK TABLES `treatment_plans` WRITE;
/*!40000 ALTER TABLE `treatment_plans` DISABLE KEYS */;
INSERT INTO `treatment_plans` VALUES (1,1,3,'Phase 1: 7-day withdrawal monitoring and hydration. Phase 2: Nutritional recovery.','1. Develop distress tolerance skills.\n2. Rebuild relationships with family.\n3. Address underlying anxiety through CBT.','Scheduled weekly outpatient follow-up.','2026-07-06 09:14:51');
/*!40000 ALTER TABLE `treatment_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `identity_no` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role`) REFERENCES `sys_roles` (`role_key`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Super Admin User','superadmin@irc.gov.pk','super_admin','$2y$10$WaeymUROb/RxW0JqYx06eOs6xDaEJi2vnTF59Rz8fglaJ9txo2fx.','12345-6789012-3',1),(2,'Dr. Sarah Khan','sarah@irc.gov.pk','clinical_director','$2y$10$WaeymUROb/RxW0JqYx06eOs6xDaEJi2vnTF59Rz8fglaJ9txo2fx.','37405-1111111-1',1),(3,'Therapist Bilal','bilal@irc.gov.pk','doctor','$2y$10$WaeymUROb/RxW0JqYx06eOs6xDaEJi2vnTF59Rz8fglaJ9txo2fx.','37405-2222222-2',1),(4,'Counselor Yasmin','yasmin@irc.gov.pk','counselor','$2y$10$WaeymUROb/RxW0JqYx06eOs6xDaEJi2vnTF59Rz8fglaJ9txo2fx.','37405-3333333-3',1),(5,'Ali Raza','patient@irc.gov.pk','patient','$2y$10$WaeymUROb/RxW0JqYx06eOs6xDaEJi2vnTF59Rz8fglaJ9txo2fx.','37405-4444444-4',1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-06 15:44:31
