<?php
require_once __DIR__ . '/includes/db.php';

try {
    $pdo->exec('ALTER TABLE email_logs MODIFY user_id BIGINT UNSIGNED NULL');
    echo "✅ email_logs table updated: user_id now nullable\n";
    
    $pdo->exec('ALTER TABLE sms_logs MODIFY user_id BIGINT UNSIGNED NULL');
    echo "✅ sms_logs table updated: user_id now nullable\n";
    
    echo "✅ Database schema fixed!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
