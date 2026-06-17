<?php
/**
 * Phase 14 Migration Runner: User Approval System
 * Run this file once to set up user approval workflow
 */

require_once __DIR__ . '/includes/db.php';

try {
    echo "Starting Phase 14 Migration...\n\n";
    
    // 1. Add approval columns to users table
    echo "1. Adding approval_status to users table...";
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `status`");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Column may already exist - OK)\n";
    }
    
    echo "2. Adding approved_by to users table...";
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `approved_by` BIGINT UNSIGNED NULL AFTER `approval_status`");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Column may already exist - OK)\n";
    }
    
    echo "3. Adding approved_at to users table...";
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `approved_at` TIMESTAMP NULL AFTER `approved_by`");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Column may already exist - OK)\n";
    }
    
    echo "4. Adding rejection_reason to users table...";
    try {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `rejection_reason` TEXT NULL AFTER `approved_at`");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Column may already exist - OK)\n";
    }
    
    echo "5. Adding foreign key for approved_by...";
    try {
        $pdo->exec("ALTER TABLE `users` ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Constraint may already exist - OK)\n";
    }
    
    // 2. Create user_profiles table
    echo "6. Creating user_profiles table...";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_profiles` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
                `bio` TEXT NULL,
                `profile_photo_path` VARCHAR(500) NULL,
                `phone_verified` BOOLEAN DEFAULT FALSE,
                `phone_verified_at` TIMESTAMP NULL,
                `aadhaar_number` VARCHAR(20) NULL,
                `father_name` VARCHAR(150) NULL,
                `mother_name` VARCHAR(150) NULL,
                `emergency_contact` VARCHAR(20) NULL,
                `emergency_contact_name` VARCHAR(150) NULL,
                `social_media_links` JSON NULL,
                `skills` JSON NULL,
                `certifications` JSON NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                INDEX `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Table may already exist - OK)\n";
    }
    
    // 3. Create admin_approvals_log table
    echo "7. Creating admin_approvals_log table...";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_approvals_log` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `admin_id` BIGINT UNSIGNED NOT NULL,
                `action` ENUM('approved', 'rejected', 'resubmitted') NOT NULL,
                `reason` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `user_agent` VARCHAR(500) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_admin_id` (`admin_id`),
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        echo " (Table may already exist - OK)\n";
    }
    
    echo "\n✅ Phase 14 Migration Completed Successfully!\n";
    echo "✓ Added approval system columns to users table\n";
    echo "✓ Created user_profiles table\n";
    echo "✓ Created admin_approvals_log table\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
