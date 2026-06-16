<?php
require_once __DIR__ . '/includes/db.php';

echo "=== DATABASE VERIFICATION ===\n\n";

// Get all tables
$result = $pdo->query("SHOW TABLES FROM exams_lms");
$tables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "Total tables in exams_lms: " . count($tables) . "\n";

// Check OTP related tables
$otp_tables = ['otp_verifications', 'email_verification_tokens', 'sms_logs', 'email_logs'];

echo "\nOTP System Tables:\n";
foreach ($otp_tables as $table) {
    if (in_array($table, $tables)) {
        echo "✅ $table EXISTS\n";
    } else {
        echo "❌ $table MISSING\n";
    }
}

echo "\n=== VERIFYING USERS TABLE ===\n";
// Check users table columns
$result = $pdo->query("DESCRIBE users");
$columns = $result->fetchAll(PDO::FETCH_COLUMN, 0);

$required_cols = ['email_verified', 'phone_verified', 'otp_attempts', 'otp_locked_until'];
foreach ($required_cols as $col) {
    if (in_array($col, $columns)) {
        echo "✅ $col EXISTS\n";
    } else {
        echo "❌ $col MISSING\n";
    }
}

// If otp_verifications exists, describe it
if (in_array('otp_verifications', $tables)) {
    echo "\n=== OTP_VERIFICATIONS TABLE STRUCTURE ===\n";
    $result = $pdo->query("DESCRIBE otp_verifications");
    $columns = $result->fetchAll();
    
    foreach ($columns as $col) {
        echo $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
