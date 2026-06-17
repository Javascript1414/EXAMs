<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Get the latest OTP for user 25
$stmt = $pdo->prepare("
    SELECT * FROM otp_verifications 
    WHERE user_id = 25 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute();
$otp = $stmt->fetch();

if ($otp) {
    echo "<h2>OTP for User 25</h2>";
    echo "OTP Code: <strong>" . htmlspecialchars($otp['otp_code']) . "</strong><br>";
    echo "Expires: " . htmlspecialchars($otp['expires_at']) . "<br>";
    echo "Type: " . htmlspecialchars($otp['verification_type']) . "<br>";
    echo "<p><a href='verify_otp.php?purpose=email_verification&user_id=25'>Back to verification</a></p>";
} else {
    echo "No OTP found for user 25";
}
