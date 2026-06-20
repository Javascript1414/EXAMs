<?php
/**
 * Fix exam_id constraint in practical_submissions table
 */

require_once 'config.php';
require_once 'includes/db.php';

echo "Fixing practical_submissions table...\n\n";

try {
    // Step 1: Drop the foreign key constraint if it exists
    try {
        $sql1 = "ALTER TABLE practical_submissions DROP FOREIGN KEY practical_submissions_ibfk_3";
        $pdo->exec($sql1);
        echo "✓ Dropped foreign key constraint\n";
    } catch (Exception $e) {
        echo "○ Foreign key doesn't exist or already dropped\n";
    }
    
    // Step 2: Recreate the column to allow NULL - just make it nullable without FK
    $sql2 = "ALTER TABLE practical_submissions MODIFY COLUMN exam_id INT NULL";
    $pdo->exec($sql2);
    echo "✓ Modified exam_id column to allow NULL\n";
    
    // Don't recreate the foreign key if exams table structure is incompatible
    // The exam_id field is optional anyway - practical exams don't need to reference exams table
    
    // Step 3: Verify the schema
    $stmt = $pdo->query("DESCRIBE practical_submissions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n✓ Table fixed successfully!\n\nColumn Schema:\n";
    foreach ($columns as $col) {
        if ($col['Field'] === 'exam_id') {
            echo "exam_id: Type={$col['Type']}, Null={$col['Null']}, Default={$col['Default']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
