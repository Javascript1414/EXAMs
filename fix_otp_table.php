<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔧 Fixing OTP Verifications Table</h2>";
echo "<hr>";

try {
    // Check if channel column exists
    $result = $pdo->query("DESCRIBE otp_verifications");
    $columns = [];
    while ($row = $result->fetch()) {
        $columns[] = $row['Field'];
    }
    
    $missing_columns = [];
    
    // Check for missing columns
    if (!in_array('channel', $columns)) {
        $missing_columns[] = 'channel';
    }
    if (!in_array('verified_at', $columns)) {
        $missing_columns[] = 'verified_at';
    }
    
    if (count($missing_columns) > 0) {
        echo "<p>Missing columns: " . implode(", ", $missing_columns) . "</p>";
        
        // Add channel column
        if (in_array('channel', $missing_columns)) {
            $pdo->exec("ALTER TABLE otp_verifications ADD COLUMN channel ENUM('email', 'sms', 'both') NOT NULL DEFAULT 'both' AFTER purpose");
            echo "<p style='color: green;'>✅ Added 'channel' column</p>";
        }
        
        // Add verified_at column
        if (in_array('verified_at', $missing_columns)) {
            $pdo->exec("ALTER TABLE otp_verifications ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL AFTER is_used");
            echo "<p style='color: green;'>✅ Added 'verified_at' column</p>";
        }
        
        echo "<hr>";
        echo "<p style='color: green;'><strong>✅ Table fixed! Now try registration again.</strong></p>";
    } else {
        echo "<p style='color: green;'>✅ All columns present!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
