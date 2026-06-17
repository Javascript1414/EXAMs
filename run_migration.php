<?php
require 'includes/db.php';

try {
    // Read migration file
    $sql = file_get_contents('phase_16_student_settings_migration.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $count = 0;
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            $count++;
        }
    }
    
    echo "\n✅ SUCCESS! Executed $count SQL statements\n";
    echo "📊 Database tables created successfully!\n\n";
    
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
    
    echo "📋 Tables Created:\n";
    foreach ($verify as $table) {
        echo "   ✅ $table\n";
    }
    
    echo "\n🎉 Database setup complete! Student Settings module is ready.\n";
    echo "   Access: http://localhost/EXAMs/student/settings.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
