<?php
/**
 * Apply Phase 24 Migration - Exam Workflow Redesign
 * Adds theory_exam_id linking and publishing workflow
 */

require_once 'config.php';
require_once 'includes/db.php';

try {
    echo "<h3>Applying Phase 24 Migration - Exam Workflow Redesign</h3>";
    
    // Check if columns already exist
    $result = $pdo->query("SHOW COLUMNS FROM practical_exams WHERE Field = 'theory_exam_id'");
    
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Columns already exist. Migration skipped.</p>";
    } else {
        // Disable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // Add columns to practical_exams
        $pdo->exec("ALTER TABLE practical_exams 
            ADD COLUMN theory_exam_id BIGINT UNSIGNED NULL COMMENT 'Foreign key to exams table for theory exam' AFTER exam_id");
        echo "<p style='color: green;'>✓ Added theory_exam_id column to practical_exams</p>";
        
        $pdo->exec("ALTER TABLE practical_exams 
            ADD COLUMN published BOOLEAN DEFAULT FALSE COMMENT 'Whether exam is published for students' AFTER status");
        echo "<p style='color: green;'>✓ Added published column to practical_exams</p>";
        
        $pdo->exec("ALTER TABLE practical_exams 
            ADD COLUMN published_at TIMESTAMP NULL COMMENT 'When exam was published' AFTER published");
        echo "<p style='color: green;'>✓ Added published_at column to practical_exams</p>";
        
        // Add unique constraint for one-to-one relationship
        $pdo->exec("ALTER TABLE practical_exams 
            ADD UNIQUE KEY `unique_theory_exam_id` (`theory_exam_id`)");
        echo "<p style='color: green;'>✓ Added unique constraint on theory_exam_id</p>";
        
        // Create index and foreign key
        $pdo->exec("ALTER TABLE practical_exams 
            ADD INDEX `idx_theory_exam_id` (`theory_exam_id`)");
        echo "<p style='color: green;'>✓ Added index on theory_exam_id</p>";
        
        $pdo->exec("ALTER TABLE practical_exams 
            ADD FOREIGN KEY (`theory_exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE");
        echo "<p style='color: green;'>✓ Added foreign key constraint on theory_exam_id</p>";
        
        // Add published column to exams if not exists
        $result = $pdo->query("SHOW COLUMNS FROM exams WHERE Field = 'published'");
        if ($result->rowCount() === 0) {
            $pdo->exec("ALTER TABLE exams ADD COLUMN published BOOLEAN DEFAULT FALSE COMMENT 'Whether exam is published for students' AFTER status");
            echo "<p style='color: green;'>✓ Added published column to exams</p>";
        }
        
        // Re-enable foreign key checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        echo "<p style='color: green; font-weight: bold;'>✓✓✓ Migration completed successfully! ✓✓✓</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Migration Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Migration - Phase 24</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        h3 { color: #333; }
        p { font-size: 14px; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Phase 24 Migration Status</h2>
    <p><a href="/">← Back to Home</a></p>
</body>
</html>
