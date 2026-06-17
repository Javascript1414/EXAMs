<?php
/**
 * Profile Tables Migration Runner
 * Executes profile.sql to set up user profile and approval system
 */

require_once __DIR__ . '/includes/db.php';

try {
    echo "Starting Profile Tables Migration...\n\n";
    
    // 1. Create user_profiles table
    echo "1. Creating user_profiles table...";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_profiles` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NOT NULL UNIQUE,
                `bio` TEXT NULL,
                `profile_photo_path` VARCHAR(500) NULL,
                `cover_photo_path` VARCHAR(500) NULL,
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
                `about_education` TEXT NULL,
                `about_experience` TEXT NULL,
                `website` VARCHAR(255) NULL,
                `location` VARCHAR(255) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                INDEX `idx_user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo " (already exists)\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Create admin_approvals_log table
    echo "2. Creating admin_approvals_log table...";
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
                INDEX `idx_created_at` (`created_at`),
                INDEX `idx_action` (`action`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo " (already exists)\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Create verification_documents table
    echo "3. Creating verification_documents table...";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `verification_documents` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `document_type` ENUM('aadhaar', 'pan', 'voter_id', 'license', 'passport', 'other') NOT NULL,
                `document_path` VARCHAR(500) NOT NULL,
                `verification_status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                `verified_by` BIGINT UNSIGNED NULL,
                `verified_at` TIMESTAMP NULL,
                `rejection_reason` TEXT NULL,
                `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`verified_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_document_type` (`document_type`),
                INDEX `idx_verification_status` (`verification_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo " (already exists)\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Add approval columns to users table
    echo "4. Adding approval columns to users table...";
    
    // Check if columns exist
    $result = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'approval_status'");
    if ($result->rowCount() == 0) {
        try {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER `status`");
            echo " ✓ (approval_status added)";
        } catch (Exception $e) {
            echo " (Failed to add approval_status)";
        }
    } else {
        echo " (approval_status already exists)";
    }
    
    $result = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'approved_by'");
    if ($result->rowCount() == 0) {
        try {
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `approved_by` BIGINT UNSIGNED NULL AFTER `approval_status`");
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `approved_at` TIMESTAMP NULL AFTER `approved_by`");
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `rejection_reason` TEXT NULL AFTER `approved_at`");
            echo ", ✓ (approved_by, approved_at, rejection_reason added)\n";
        } catch (Exception $e) {
            echo ", (Failed)\n";
        }
    } else {
        echo ", (already exist)\n";
    }
    
    echo "\n✅ Profile Migration Completed!\n";
    echo "✓ Created user_profiles table\n";
    echo "✓ Created admin_approvals_log table\n";
    echo "✓ Created verification_documents table\n";
    echo "✓ Added approval columns to users table\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
