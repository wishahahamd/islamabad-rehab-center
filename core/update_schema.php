<?php
// Schema update and Seeding script for new tables
require_once __DIR__ . '/db.php';

try {
    $pdo->exec("USE `irc_db`;");

    echo "Updating schema...\n";

    // 1. Create treatment_plans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `treatment_plans` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `patient_id` INT NOT NULL,
            `created_by` INT NOT NULL,
            `detox_plan` TEXT NULL,
            `therapy_goals` TEXT NULL,
            `aftercare_notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table 'treatment_plans' created or verified.\n";

    // 2. Create patient_daily_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `patient_daily_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `patient_id` INT NOT NULL,
            `log_date` DATE NOT NULL,
            `mood_score` INT NOT NULL, -- 1 to 5
            `notes` TEXT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `patient_date` (`patient_id`, `log_date`),
            FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table 'patient_daily_logs' created or verified.\n";

    // 3. Create support_requests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `support_requests` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `patient_id` INT NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `status` VARCHAR(20) DEFAULT 'Pending', -- Pending, Resolved
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "✓ Table 'support_requests' created or verified.\n";

    // 4. Seed new pages
    // Check and seed 'Treatment Plans'
    $stmt = $pdo->prepare("SELECT id FROM `sys_pages` WHERE `page_url` = 'dashboards/rehab/manage_plans.php'");
    $stmt->execute();
    $planPageId = $stmt->fetchColumn();
    if (!$planPageId) {
        $pdo->exec("
            INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
            VALUES (2, 'Treatment Plans', 'dashboards/rehab/manage_plans.php', 'bi bi-clipboard2-pulse', 3)
        ");
        $planPageId = $pdo->lastInsertId();
        echo "✓ Seeded page 'Treatment Plans' (ID: $planPageId)\n";
    } else {
        echo "✓ Page 'Treatment Plans' already exists (ID: $planPageId)\n";
    }

    // Check and seed 'Support Tickets'
    $stmt = $pdo->prepare("SELECT id FROM `sys_pages` WHERE `page_url` = 'dashboards/rehab/support_requests.php'");
    $stmt->execute();
    $supportPageId = $stmt->fetchColumn();
    if (!$supportPageId) {
        $pdo->exec("
            INSERT INTO `sys_pages` (`parent_id`, `page_name`, `page_url`, `icon_class`, `sort_order`) 
            VALUES (2, 'Support Tickets', 'dashboards/rehab/support_requests.php', 'bi bi-chat-left-text', 4)
        ");
        $supportPageId = $pdo->lastInsertId();
        echo "✓ Seeded page 'Support Tickets' (ID: $supportPageId)\n";
    } else {
        echo "✓ Page 'Support Tickets' already exists (ID: $supportPageId)\n";
    }

    // 5. Seed permissions
    $roles = ['super_admin', 'clinical_director', 'doctor', 'counselor'];
    foreach ($roles as $role) {
        $pdo->exec("INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('$role', $planPageId), ('$role', $supportPageId)");
    }
    // Patients access support requests (supportPageId)
    $pdo->exec("INSERT IGNORE INTO `role_access` (`role_key`, `page_id`) VALUES ('patient', $supportPageId)");
    
    echo "✓ Permissions seeded successfully.\n";

    // 6. Seed a sample treatment plan if none exists
    $plansCount = $pdo->query("SELECT COUNT(*) FROM `treatment_plans`")->fetchColumn();
    if ($plansCount == 0) {
        // Find therapist id (Therapist Bilal is id 3)
        $pdo->exec("
            INSERT INTO `treatment_plans` (`patient_id`, `created_by`, `detox_plan`, `therapy_goals`, `aftercare_notes`) 
            VALUES (1, 3, 'Phase 1: 7-day withdrawal monitoring and hydration. Phase 2: Nutritional recovery.', '1. Develop distress tolerance skills.\n2. Rebuild relationships with family.\n3. Address underlying anxiety through CBT.', 'Scheduled weekly outpatient follow-up.')
        ");
        echo "✓ Sample treatment plan seeded.\n";
    }

    // 7. Seed sample daily logs
    $logsCount = $pdo->query("SELECT COUNT(*) FROM `patient_daily_logs`")->fetchColumn();
    if ($logsCount == 0) {
        $pdo->exec("
            INSERT INTO `patient_daily_logs` (`patient_id`, `log_date`, `mood_score`, `notes`) 
            VALUES 
            (1, '2026-07-04', 3, 'Withdrawal symptoms are fading. Feeling physically exhausted but stable.'),
            (1, '2026-07-05', 4, 'Participated fully in group session today. Rested well.')
        ");
        echo "✓ Sample daily logs seeded.\n";
    }

    // 8. Seed sample support requests
    $requestsCount = $pdo->query("SELECT COUNT(*) FROM `support_requests`")->fetchColumn();
    if ($requestsCount == 0) {
        $pdo->exec("
            INSERT INTO `support_requests` (`patient_id`, `subject`, `message`, `status`) 
            VALUES (1, 'Anxiety spike in evenings', 'I am experiencing mild anxiety around 8 PM. Can I discuss this with Therapist Bilal?', 'Pending')
        ");
        echo "✓ Sample support requests seeded.\n";
    }

    echo "Schema update complete!\n";

} catch (Exception $e) {
    die("Database Schema Update Failed: " . $e->getMessage() . "\n");
}
?>
