<?php
/**
 * Phase 15 Migration Runner: User Deletion Archive System
 * Creates deleted_users_archive table and related functionality
 * Run this file once to set up user deletion archiving
 */

require_once __DIR__ . '/includes/db.php';

try {
    echo "Starting Phase 15 Migration: User Deletion Archive System...\n\n";
    
    // 1. Create deleted_users_archive table
    echo "1. Creating deleted_users_archive table...";
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `deleted_users_archive` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `original_user_id` BIGINT UNSIGNED NOT NULL,
                `full_name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(255) NOT NULL,
                `phone` VARCHAR(20) NOT NULL,
                `role_name` VARCHAR(100) NOT NULL,
                `trade_name` VARCHAR(255) NULL,
                `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL,
                `account_status` ENUM('active', 'inactive', 'suspended') NOT NULL,
                `registration_date` TIMESTAMP NULL,
                `last_login` TIMESTAMP NULL,
                `deleted_by_admin_id` BIGINT UNSIGNED NOT NULL,
                `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `deletion_reason` TEXT NULL,
                `original_user_data` JSON NOT NULL,
                `restored_at` TIMESTAMP NULL,
                `restored_by_admin_id` BIGINT UNSIGNED NULL,
                FOREIGN KEY (`deleted_by_admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
                FOREIGN KEY (`restored_by_admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
                INDEX `idx_original_user_id` (`original_user_id`),
                INDEX `idx_email` (`email`),
                INDEX `idx_deleted_at` (`deleted_at`),
                INDEX `idx_restored_at` (`restored_at`),
                INDEX `idx_role_name` (`role_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo " ✓\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo " (Table already exists - OK)\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ Phase 15 Migration Completed Successfully!\n";
    echo "✓ Created deleted_users_archive table for user deletion archiving\n";
    echo "✓ Archives store complete user records before permanent deletion\n";
    echo "✓ Supports restoration by superadmin\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
