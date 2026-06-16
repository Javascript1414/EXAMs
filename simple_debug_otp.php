<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';

try {
    // Test 1: Table exists?
    $result = $pdo->query("SHOW TABLES LIKE 'otp_verifications'");
    $table_exists = $result->rowCount() > 0;
    
    $output = [];
    $output[] = "Table otp_verifications exists: " . ($table_exists ? "YES ✅" : "NO ❌");
    
    if ($table_exists) {
        // Test 2: Try to insert an OTP record
        require_once __DIR__ . '/includes/otp_helper.php';
        
        $test_user_id = 1; // assuming user 1 exists
        $otp = generateOTP();
        $expires_at = date('Y-m-d H:i:s', strtotime("+10 minutes"));
        
        $output[] = "Generated OTP: $otp";
        $output[] = "Expires at: $expires_at";
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO otp_verifications (user_id, otp_code, purpose, channel, expires_at, is_used) 
                VALUES (?, ?, ?, ?, ?, FALSE)
            ");
            
            $result = $stmt->execute([$test_user_id, $otp, 'email_verification', 'both']);
            
            if ($result) {
                $output[] = "Insert successful: YES ✅";
            } else {
                $output[] = "Insert returned false: NO ❌";
                $output[] = "Error info: " . json_encode($stmt->errorInfo());
            }
        } catch (Exception $e) {
            $output[] = "Insert exception: " . $e->getMessage();
        }
    }
    
    file_put_contents(__DIR__ . '/otp_debug.txt', implode("\n", $output));
    echo "Debug output saved to otp_debug.txt\n";
    foreach ($output as $line) {
        echo $line . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
