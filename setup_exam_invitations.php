<?php
require 'config.php';
require 'includes/db.php';

// Check if practical_exam_invitations table exists
try {
    $stmt = $pdo->query("DESCRIBE practical_exam_invitations LIMIT 1");
    echo "✓ Table exists\n";
    $exists = true;
} catch (Exception $e) {
    echo "✗ Table doesn't exist - need to create it\n";
    $exists = false;
}

// If doesn't exist, create it
if (!$exists) {
    echo "Creating practical_exam_invitations table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `practical_exam_invitations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `practical_exam_id` INT NOT NULL,
        `invitation_code` VARCHAR(64) UNIQUE NOT NULL,
        `invitation_url` VARCHAR(255),
        `created_by` INT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `expires_at` DATETIME,
        `max_uses` INT DEFAULT NULL,
        `used_count` INT DEFAULT 0,
        `status` ENUM('active', 'inactive', 'expired') DEFAULT 'active',
        FOREIGN KEY (`practical_exam_id`) REFERENCES `practical_exams`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Table created successfully\n";
}

echo "\n✓ Setup complete!\n";
?>
