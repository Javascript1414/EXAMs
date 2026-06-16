<?php
/**
 * Run Migration Phase 12 - OTP Verification System
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>Running Phase 12 Migration...</h2>";
echo "<hr>";

try {
    // Read migration file
    $migration_file = file_get_contents(__DIR__ . '/phase_12_otp_verification_migration.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $migration_file)),
        function($stmt) { return !empty($stmt) && !preg_match('/^--/', $stmt); }
    );
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            $pdo->exec($statement);
            $count++;
            echo "<p style='color: green;'>✅ Statement " . $count . " executed successfully</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Verifying Tables Created:</h3>";
    
    // Check if tables exist
    $tables = ['otp_verifications', 'email_verification_tokens', 'sms_logs', 'email_logs'];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' NOT FOUND</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Checking Users Table Columns:</h3>";
    
    $columns_to_check = ['email_verified', 'phone_verified', 'otp_attempts', 'otp_locked_until'];
    
    $result = $pdo->query("DESCRIBE users");
    $existing_columns = [];
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($columns_to_check as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✅ Column '$col' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$col' NOT FOUND</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Migration Completed Successfully!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Migration Failed</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
