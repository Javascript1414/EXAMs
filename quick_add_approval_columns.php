<?php
require_once __DIR__ . '/includes/db.php';

try {
    echo "Adding approval columns to users table...\n";
    
    // Check if column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'approval_status'")->fetch();
    
    if (!$result) {
        // Add approval_status column
        $pdo->exec("ALTER TABLE users ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER status");
        echo "✓ Added approval_status column\n";
    } else {
        echo "✓ approval_status column already exists\n";
    }
    
    // Check if approved_by column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved_by'")->fetch();
    
    if (!$result) {
        // Add approved_by column
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER approval_status");
        echo "✓ Added approved_by column\n";
    } else {
        echo "✓ approved_by column already exists\n";
    }
    
    // Check if approved_at column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'approved_at'")->fetch();
    
    if (!$result) {
        // Add approved_at column
        $pdo->exec("ALTER TABLE users ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by");
        echo "✓ Added approved_at column\n";
    } else {
        echo "✓ approved_at column already exists\n";
    }
    
    // Check if rejection_reason column exists
    $result = $pdo->query("SHOW COLUMNS FROM users LIKE 'rejection_reason'")->fetch();
    
    if (!$result) {
        // Add rejection_reason column
        $pdo->exec("ALTER TABLE users ADD COLUMN rejection_reason TEXT NULL AFTER approved_at");
        echo "✓ Added rejection_reason column\n";
    } else {
        echo "✓ rejection_reason column already exists\n";
    }
    
    // Create admin_approvals_log table if it doesn't exist
    $result = $pdo->query("SHOW TABLES LIKE 'admin_approvals_log'")->fetch();
    
    if (!$result) {
        $pdo->exec("
            CREATE TABLE `admin_approvals_log` (
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
        echo "✓ Created admin_approvals_log table\n";
    } else {
        echo "✓ admin_approvals_log table already exists\n";
    }
    
    echo "\n✓ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    die(1);
}
?>
