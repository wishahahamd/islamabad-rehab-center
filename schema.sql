-- Database Schema for Islamabad Rehab Center (IRC) Universal Skeleton Project
-- Create Database
CREATE DATABASE IF NOT EXISTS `irc_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `irc_db`;

-- 1. System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_key` VARCHAR(50) NOT NULL PRIMARY KEY,
    `setting_value` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('system_name', 'Islamabad Rehab Center'),
('system_logo', 'logo.png'),
('footer_text', '© 2026 Islamabad Rehab Center. All rights reserved.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2. System Roles Table
CREATE TABLE IF NOT EXISTS `sys_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(100) NOT NULL UNIQUE,
    `role_key` VARCHAR(50) NOT NULL UNIQUE,
    `is_system_role` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed System Roles
INSERT INTO `sys_roles` (`id`, `role_name`, `role_key`, `is_system_role`) VALUES
(1, 'Super Admin', 'super_admin', 1),
(2, 'Clinical Director', 'clinical_director', 0),
(3, 'Doctor / Therapist', 'doctor', 0),
(4, 'Case Counselor', 'counselor', 0),
(5, 'Patient', 'patient', 0),
(6, 'Suspended', 'suspended', 1)
ON DUPLICATE KEY UPDATE `role_name` = VALUES(`role_name`), `is_system_role` = VALUES(`is_system_role`);

-- 3. System Pages Table
CREATE TABLE IF NOT EXISTS `sys_pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NULL,
    `page_name` VARCHAR(100) NOT NULL,
    `page_url` VARCHAR(255) NOT NULL,
    `icon_class` VARCHAR(100) NULL,
    `sort_order` INT DEFAULT 0,
    FOREIGN KEY (`parent_id`) REFERENCES `sys_pages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed System Pages
-- 1: Dashboard
-- 2: Clinical Folder (Parent Menu)
-- 3: Patient Intake & Directory (Child)
-- 4: Therapy Session Logs (Child)
-- 5: System Admin Folder (Parent Menu)
-- 6: Manage Pages (Child)
-- 7: Manage Roles (Child)
-- 8: Manage Users (Child)
INSERT INTO `sys_pages` (`id`, `parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) VALUES
(1, NULL, 'Dashboard', 'dashboard.php', 'bi bi-speedometer2', 1),
(2, NULL, 'Clinical Operations', '#', 'bi bi-heart-pulse-fill', 2),
(3, 2, 'Patient Intake', 'dashboards/rehab/manage_patients.php', 'bi bi-person-badge-fill', 1),
(4, 2, 'Therapy Sessions', 'dashboards/rehab/therapy_sessions.php', 'bi bi-calendar-event', 2),
(5, NULL, 'System Management', '#', 'bi bi-gear-fill', 3),
(6, 5, 'Manage Pages', 'dashboards/super_admin/manage_pages.php', 'bi bi-file-earmark-medical', 1),
(7, 5, 'Manage Roles', 'dashboards/super_admin/manage_roles.php', 'bi bi-shield-lock', 2),
(8, 5, 'Manage Users', 'dashboards/super_admin/manage_users.php', 'bi bi-people-fill', 3)
ON DUPLICATE KEY UPDATE 
`parent_id` = VALUES(`parent_id`), 
`page_name` = VALUES(`page_name`), 
`page_url` = VALUES(`page_url`), 
`icon_class` = VALUES(`icon_class`), 
`sort_order` = VALUES(`sort_order`);

-- 4. Role Access / Permissions Link Table
CREATE TABLE IF NOT EXISTS `role_access` (
    `role_key` VARCHAR(50) NOT NULL,
    `page_id` INT NOT NULL,
    PRIMARY KEY (`role_key`, `page_id`),
    FOREIGN KEY (`role_key`) REFERENCES `sys_roles` (`role_key`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`page_id`) REFERENCES `sys_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Permissions
INSERT INTO `role_access` (`role_key`, `page_id`) VALUES
-- Super Admin accesses everything
('super_admin', 1),
('super_admin', 2),
('super_admin', 3),
('super_admin', 4),
('super_admin', 5),
('super_admin', 6),
('super_admin', 7),
('super_admin', 8),
-- Clinical Director accesses clinical operations
('clinical_director', 1),
('clinical_director', 2),
('clinical_director', 3),
('clinical_director', 4),
-- Doctor accesses sessions & patients
('doctor', 1),
('doctor', 2),
('doctor', 3),
('doctor', 4),
-- Counselor accesses patients list
('counselor', 1),
('counselor', 2),
('counselor', 3),
-- Patients only access dashboard
('patient', 1)
ON DUPLICATE KEY UPDATE `role_key` = `role_key`;

-- 5. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `role` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `identity_no` VARCHAR(50) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`role`) REFERENCES `sys_roles` (`role_key`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Users
-- Password is 'admin123' hashed
INSERT INTO `users` (`id`, `name`, `email`, `role`, `password`, `identity_no`, `is_active`) VALUES
(1, 'Super Admin User', 'superadmin@irc.gov.pk', 'super_admin', '$2y$10$ShoBofVHvgH5XWlT//7ECew4ewiYy5orDbgmZwudiVkgYNNpg3RlS', '12345-6789012-3', 1),
(2, 'Dr. Sarah Khan', 'sarah@irc.gov.pk', 'clinical_director', '$2y$10$ShoBofVHvgH5XWlT//7ECew4ewiYy5orDbgmZwudiVkgYNNpg3RlS', '37405-1111111-1', 1),
(3, 'Therapist Bilal', 'bilal@irc.gov.pk', 'doctor', '$2y$10$ShoBofVHvgH5XWlT//7ECew4ewiYy5orDbgmZwudiVkgYNNpg3RlS', '37405-2222222-2', 1),
(4, 'Counselor Yasmin', 'yasmin@irc.gov.pk', 'counselor', '$2y$10$ShoBofVHvgH5XWlT//7ECew4ewiYy5orDbgmZwudiVkgYNNpg3RlS', '37405-3333333-3', 1)
ON DUPLICATE KEY UPDATE 
`name` = VALUES(`name`),
`role` = VALUES(`role`),
`password` = VALUES(`password`),
`identity_no` = VALUES(`identity_no`),
`is_active` = VALUES(`is_active`);

-- 6. Patients Table (Rehab Clinic Specific)
CREATE TABLE IF NOT EXISTS `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `age` INT NOT NULL,
    `gender` VARCHAR(20) NOT NULL,
    `admission_date` DATE NOT NULL,
    `treatment_status` VARCHAR(50) NOT NULL DEFAULT 'Intake', -- Intake, Detox, Rehab, Outpatient, Discharged
    `assigned_therapist_id` INT NULL,
    `medical_history` TEXT NULL,
    FOREIGN KEY (`assigned_therapist_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Patients
INSERT INTO `patients` (`id`, `name`, `age`, `gender`, `admission_date`, `treatment_status`, `assigned_therapist_id`, `medical_history`) VALUES
(1, 'Ali Raza', 28, 'Male', '2026-06-01', 'Detox', 3, 'Alcohol substance rehabilitation and detoxification.'),
(2, 'Zainab Bibi', 32, 'Female', '2026-05-15', 'Rehab', 3, 'Depression therapy and behavioral modification treatment.'),
(3, 'Hamza Mughal', 24, 'Male', '2026-06-10', 'Intake', 2, 'Initial screening completed. Opiate addiction history.')
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`age` = VALUES(`age`),
`gender` = VALUES(`gender`),
`admission_date` = VALUES(`admission_date`),
`treatment_status` = VALUES(`treatment_status`),
`assigned_therapist_id` = VALUES(`assigned_therapist_id`),
`medical_history` = VALUES(`medical_history`);

-- 7. Therapy Sessions Table (Rehab Clinic Specific)
CREATE TABLE IF NOT EXISTS `therapy_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `therapist_id` INT NOT NULL,
    `session_date` DATETIME NOT NULL,
    `session_type` VARCHAR(50) NOT NULL, -- Individual, Group, Family, Physiotherapy
    `notes` TEXT NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Therapy Sessions
INSERT INTO `therapy_sessions` (`id`, `patient_id`, `therapist_id`, `session_date`, `session_type`, `notes`) VALUES
(1, 1, 3, '2026-06-18 10:00:00', 'Individual', 'Detox stage progress is steady. Physical withdrawal symptoms declining.'),
(2, 2, 3, '2026-06-18 11:30:00', 'Group', 'Active participation in peer sharing. Expressed optimistic views on recovery.')
ON DUPLICATE KEY UPDATE
`patient_id` = VALUES(`patient_id`),
`therapist_id` = VALUES(`therapist_id`),
`session_date` = VALUES(`session_date`),
`session_type` = VALUES(`session_type`),
`notes` = VALUES(`notes`);
