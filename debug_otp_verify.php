<?php
/**
 * Debug OTP Verification Issue
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/otp_helper.php';

echo "<h2>🔍 OTP Verification Debug</h2>";
echo "<hr>";

// Get latest unverified user
$stmt = $pdo->prepare("
    SELECT id, email, status, email_verified, created_at 
    FROM users 
    WHERE status = 'inactive' 
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->execute();
$latest_user = $stmt->fetch();

if (!$latest_user) {
    echo "<p style='color: orange;'>⚠️ No inactive users found</p>";
    exit;
}

$user_id = $latest_user['id'];

echo "<h3>📋 Latest Inactive User:</h3>";
echo "<ul>";
echo "<li>ID: " . $latest_user['id'] . "</li>";
echo "<li>Email: " . $latest_user['email'] . "</li>";
echo "<li>Status: " . $latest_user['status'] . "</li>";
echo "<li>Email Verified: " . ($latest_user['email_verified'] ? 'YES' : 'NO') . "</li>";
echo "<li>Created: " . $latest_user['created_at'] . "</li>";
echo "</ul>";

echo "<hr>";

// Check if OTP exists for this user
$otp_stmt = $pdo->prepare("
    SELECT id, otp_code, purpose, channel, expires_at, is_used, verified_at, created_at 
    FROM otp_verifications 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 1
");
$otp_stmt->execute([$user_id]);
$otp_record = $otp_stmt->fetch();

if (!$otp_record) {
    echo "<p style='color: red;'>❌ No OTP found for this user!</p>";
    exit;
}

echo "<h3>📧 Latest OTP Record:</h3>";
echo "<ul>";
echo "<li>ID: " . $otp_record['id'] . "</li>";
echo "<li>OTP Code: <strong>" . $otp_record['otp_code'] . "</strong></li>";
echo "<li>Purpose: " . $otp_record['purpose'] . "</li>";
echo "<li>Channel: " . $otp_record['channel'] . "</li>";
echo "<li>Expires At: " . $otp_record['expires_at'] . "</li>";
echo "<li>Is Used: " . ($otp_record['is_used'] ? 'YES ✅' : 'NO ❌') . "</li>";
echo "<li>Verified At: " . ($otp_record['verified_at'] ?? 'NOT YET') . "</li>";
echo "<li>Created: " . $otp_record['created_at'] . "</li>";
echo "</ul>";

echo "<hr>";

// Check if OTP is expired
$now = time();
$expires = strtotime($otp_record['expires_at']);

echo "<h3>⏰ Expiry Check:</h3>";
echo "<p>Current Time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Expires At: " . $otp_record['expires_at'] . "</p>";

if ($expires < $now) {
    echo "<p style='color: red;'>❌ OTP EXPIRED!</p>";
} else {
    $remaining = $expires - $now;
    echo "<p style='color: green;'>✅ OTP still valid for " . $remaining . " seconds</p>";
}

echo "<hr>";

// Now test the verify function
if (!$otp_record['is_used'] && $expires >= $now) {
    echo "<h3>🧪 Testing verifyOTP() function:</h3>";
    
    $verify_result = verifyOTP($pdo, $user_id, $otp_record['otp_code'], 'email_verification');
    
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . json_encode($verify_result, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($verify_result['success']) {
        echo "<p style='color: green;'>✅ OTP verified successfully!</p>";
        
        // Check if user is now active
        $check_user = $pdo->prepare("SELECT status, email_verified FROM users WHERE id = ?");
        $check_user->execute([$user_id]);
        $updated_user = $check_user->fetch();
        
        echo "<h3>👤 User Status After Verification:</h3>";
        echo "<ul>";
        echo "<li>Status: " . $updated_user['status'] . "</li>";
        echo "<li>Email Verified: " . ($updated_user['email_verified'] ? 'YES ✅' : 'NO ❌') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ OTP verification failed: " . $verify_result['message'] . "</p>";
    }
}

echo "<hr>";
echo "<h3>✅ Debug Complete</h3>";
?>
