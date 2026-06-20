<?php
/**
 * Run Practical Exam Migration
 */

require_once __DIR__ . '/includes/db.php';

try {
    // Read migration file
    $migrationFile = __DIR__ . '/migrations/phase_23_practical_exams.sql';
    
    if (!file_exists($migrationFile)) {
        die("❌ Migration file not found: $migrationFile");
    }
    
    // Read SQL content
    $sql = file_get_contents($migrationFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h2>Running Practical Exams Migration...</h2>";
    echo "<pre>";
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $count++;
                echo "✅ Statement $count executed\n";
            } catch (PDOException $e) {
                echo "⚠️  Statement $count: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "</pre>";
    echo "<h3 style='color: green;'>✅ Migration completed! ($count statements executed)</h3>";
    echo "<p><a href='admin/practical_exams.php'>Go to Practical Exams Dashboard →</a></p>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
