<?php
/**
 * Create subject_teacher table
 * This allows admins to assign teachers to specific subjects
 */

require_once 'config.php';
require_once 'includes/db.php';

try {
    // SQL to create subject_teacher table
    $sql = "
    CREATE TABLE IF NOT EXISTS `subject_teacher` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `subject_id` INT UNSIGNED NOT NULL,
        `teacher_id` BIGINT UNSIGNED NOT NULL,
        `created_by` BIGINT UNSIGNED NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        -- Foreign Keys
        FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
        
        -- Indexes for performance
        INDEX `idx_subject_id` (`subject_id`),
        INDEX `idx_teacher_id` (`teacher_id`),
        INDEX `idx_teacher_subject` (`teacher_id`, `subject_id`),
        
        -- Unique constraint to prevent duplicate assignments
        UNIQUE KEY `unique_teacher_subject` (`subject_id`, `teacher_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    
    echo json_encode([
        'status' => 'success',
        'message' => '✅ subject_teacher table created successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo json_encode([
            'status' => 'success',
            'message' => '✅ subject_teacher table already exists',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => '❌ Error: ' . $e->getMessage()
        ]);
    }
}
?>
