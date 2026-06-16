<?php
/**
 * Migration Runner for Phase 10e - Notifications Table Enhancement
 * Run this file once to apply the migration
 */

require_once __DIR__ . '/includes/db.php';

try {
    echo "Starting Phase 10e Migration...\n";
    
    $queries = [
        // Add columns to notifications table
        "ALTER TABLE `notifications` ADD COLUMN `notification_type` VARCHAR(50) DEFAULT 'general' AFTER `message`",
        "ALTER TABLE `notifications` ADD COLUMN `target_type` VARCHAR(50) DEFAULT 'all' AFTER `notification_type`",
        "ALTER TABLE `notifications` ADD COLUMN `target_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `target_type`",
        "ALTER TABLE `notifications` ADD COLUMN `action_url` VARCHAR(255) NULL DEFAULT NULL AFTER `target_id`",
        "ALTER TABLE `notifications` ADD COLUMN `icon` VARCHAR(50) DEFAULT 'bell' AFTER `action_url`",
        "ALTER TABLE `notifications` ADD COLUMN `created_by` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `icon`",
        "ALTER TABLE `notifications` ADD COLUMN `status` VARCHAR(50) DEFAULT 'sent' AFTER `created_by`",
        
        // Create notification_recipients table
        "CREATE TABLE IF NOT EXISTS `notification_recipients` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `notification_id` BIGINT UNSIGNED NOT NULL,
            `user_id` BIGINT UNSIGNED NOT NULL,
            `is_read` BOOLEAN DEFAULT FALSE,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`notification_id`) REFERENCES `notifications`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            INDEX `idx_notification_user` (`notification_id`, `user_id`),
            INDEX `idx_user_read` (`user_id`, `is_read`),
            UNIQUE KEY `unique_notification_user` (`notification_id`, `user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    $executedCount = 0;
    $skippedCount = 0;
    
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                $executedCount++;
                echo "✓ Query executed\n";
            } catch (PDOException $e) {
                $errorMsg = $e->getMessage();
                // Skip if column already exists or table already exists
                if (strpos($errorMsg, 'Duplicate column') !== false ||
                    strpos($errorMsg, 'already exists') !== false ||
                    strpos($errorMsg, '1060') !== false ||
                    strpos($errorMsg, '1061') !== false) {
                    $skippedCount++;
                    echo "⚠ Skipped (already exists)\n";
                } else {
                    throw $e;
                }
            }
        }
    }
    
    // Add foreign key if it doesn't exist
    try {
        $result = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME='notifications' AND CONSTRAINT_NAME='fk_notifications_created_by'")->fetch();
        if (!$result) {
            $pdo->exec("ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL");
            $executedCount++;
            echo "✓ Foreign key added\n";
        } else {
            echo "⚠ Foreign key already exists\n";
            $skippedCount++;
        }
    } catch (Exception $e) {
        echo "⚠ Foreign key setup skipped: " . substr($e->getMessage(), 0, 60) . "...\n";
        $skippedCount++;
    }
    
    echo "\n✅ Phase 10e Migration completed! ({$executedCount} executed, {$skippedCount} skipped)\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
