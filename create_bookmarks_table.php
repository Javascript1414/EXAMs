<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Direct SQL execution without complex parsing
    $sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS `material_bookmarks` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `material_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`material_id`) REFERENCES `study_materials`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_material_bookmark` (`user_id`, `material_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_material_id` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $pdo->exec($sql);
    echo "✓ material_bookmarks table created successfully!\n";
    
    // Verify it exists
    $result = $pdo->query("SHOW TABLES LIKE 'material_bookmarks'")->fetch();
    if ($result) {
        echo "✓ Table verified - it exists in the database!\n";
    }
    
} catch (PDOException $e) {
    die("✗ Error: " . $e->getMessage() . "\n");
}
?>
