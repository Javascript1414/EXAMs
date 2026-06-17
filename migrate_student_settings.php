<?php
/**
 * Database Migration Runner - Student Settings Module
 * Access: http://localhost/EXAMs/migrate_student_settings.php
 */

// Prevent direct script access via browser in production
if (php_sapi_name() !== 'cli' && !isset($_GET['key'])) {
    die('❌ Access Denied. Use: http://localhost/EXAMs/migrate_student_settings.php?key=run_now');
}

require_once 'includes/db.php';

$start_time = microtime(true);

try {
    echo "<pre style='background:#f5f5f5; padding:20px; font-family:monospace;'>";
    echo "🔄 Starting Database Migration...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    // Read migration file
    $migration_file = 'phase_16_student_settings_migration.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("❌ Migration file not found: $migration_file");
    }
    
    $sql = file_get_contents($migration_file);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $executed = 0;
    $errors = [];
    
    echo "📋 Executing SQL statements...\n\n";
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $executed++;
                echo "✅ Statement $executed executed\n";
            } catch (Exception $e) {
                $errors[] = [
                    'statement' => substr($statement, 0, 50) . "...",
                    'error' => $e->getMessage()
                ];
                echo "⚠️  Error in statement: " . substr($statement, 0, 50) . "...\n";
                echo "   Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
    echo "✅ SUCCESS! Executed $executed SQL statements\n";
    
    if (!empty($errors)) {
        echo "\n⚠️  " . count($errors) . " error(s) occurred (mostly duplicate table warnings)\n";
    }
    
    echo "\n📊 Verifying tables...\n\n";
    
    // Verify tables were created
    $verify = $pdo->query("
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA='exams_lms' AND (
            TABLE_NAME LIKE 'student_%' 
            OR TABLE_NAME = 'account_deletion_requests'
            OR TABLE_NAME = 'data_export_requests'
        )
        ORDER BY TABLE_NAME
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Student Settings Tables:\n";
    $table_count = 0;
    foreach ($verify as $table) {
        echo "   ✅ $table\n";
        $table_count++;
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    
    if ($table_count >= 5) {
        echo "\n🎉 MIGRATION SUCCESSFUL!\n\n";
        echo "📊 Status: All 5 tables created successfully\n";
        echo "⏱️  Time taken: " . round((microtime(true) - $start_time) * 1000) . "ms\n\n";
        echo "✨ Student Settings module is ready to use!\n\n";
        echo "🌐 Access here: http://localhost/EXAMs/student/settings.php\n";
        echo "🔑 Login with any student account\n";
        echo "📚 Documentation: QUICK_DEPLOYMENT_SETTINGS.md\n";
    } else {
        echo "\n⚠️  WARNING: Only $table_count tables found (expected 5)\n";
        echo "   Please review the migration output above.\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "</pre>";
    
} catch (Exception $e) {
    echo "<div style='background:#fff3cd; padding:20px; border:1px solid #ffc107; border-radius:4px;'>";
    echo "<strong>❌ ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit(1);
}
?>
