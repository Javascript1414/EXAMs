<?php
/**
 * Fix: Make exam_id nullable in practical_marks table
 * Practical exams can exist independently without being linked to theory exams
 */

require_once 'config.php';
require_once 'includes/db.php';

try {
    echo "<h2>Fixing practical_marks Schema</h2>";
    
    // Check current schema
    $result = $pdo->query("DESCRIBE practical_marks")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    foreach ($result as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    echo "</pre>";
    
    // Fix: Remove NOT NULL constraint from exam_id in practical_marks
    $pdo->exec("ALTER TABLE practical_marks MODIFY COLUMN exam_id BIGINT UNSIGNED NULL;");
    echo "<p style='color: green;'>✓ practical_marks.exam_id is now NULLABLE</p>";
    
    // Remove the NOT NULL constraint from unique key if it's causing issues
    // The unique key 'unique_practical_mark' allows NULL values for exam_id
    
    echo "<p style='color: blue;'>✓ Schema updated successfully! You can now assign marks for standalone practical exams.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
