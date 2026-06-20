<?php
require 'includes/db.php';

try {
    // Make exam_id nullable
    $pdo->exec("ALTER TABLE practical_exams MODIFY exam_id BIGINT UNSIGNED NULL;");
    echo "✅ SUCCESS: practical_exams.exam_id is now nullable\n";
    
    // Verify the change
    $result = $pdo->query("DESCRIBE practical_exams exam_id");
    $info = $result->fetch(PDO::FETCH_ASSOC);
    echo "\nColumn Info:\n";
    echo "  Field: " . $info['Field'] . "\n";
    echo "  Type: " . $info['Type'] . "\n";
    echo "  Null: " . $info['Null'] . "\n";
    echo "  Key: " . $info['Key'] . "\n";
    echo "  Default: " . $info['Default'] . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
