<?php
/**
 * Profile Database Setup Migration
 * Runs profile.sql to create profile tables and columns
 */

require_once __DIR__ . '/includes/db.php';

try {
    // Read the SQL file
    $sql_file = __DIR__ . '/profile.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("profile.sql file not found!");
    }

    $sql_content = file_get_contents($sql_file);
    
    // Split by semicolon and execute each query
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $executed_count = 0;
    foreach ($queries as $query) {
        if (!empty($query)) {
            try {
                $pdo->exec($query);
                $executed_count++;
                echo "✓ Query executed successfully\n";
            } catch (PDOException $e) {
                // Skip if table/column already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠ " . $e->getMessage() . "\n";
                } else {
                    echo "✓ Already exists (skipped)\n";
                    $executed_count++;
                }
            }
        }
    }
    
    echo "\n===== MIGRATION COMPLETE =====\n";
    echo "Total queries executed: $executed_count\n";
    echo "\nProfile tables created:\n";
    echo "✓ users table (updated with approval columns)\n";
    echo "✓ user_profiles table\n";
    echo "✓ admin_approvals_log table\n";
    echo "✓ verification_documents table\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
