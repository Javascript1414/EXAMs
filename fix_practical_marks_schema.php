<?php
/**
 * Fix practical exam schema - Allow NULL exam_id
 */

require_once 'config.php';
require_once 'includes/db.php';

try {
    // Make exam_id nullable in practical_submissions
    $pdo->exec("ALTER TABLE practical_submissions MODIFY COLUMN exam_id BIGINT UNSIGNED NULL;");
    echo "✓ practical_submissions updated - exam_id is now nullable<br>";
    
    // Make exam_id nullable in practical_marks
    $pdo->exec("ALTER TABLE practical_marks MODIFY COLUMN exam_id BIGINT UNSIGNED NULL;");
    echo "✓ practical_marks updated - exam_id is now nullable<br>";
    
    echo "<br><strong>Schema updates completed successfully!</strong>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
