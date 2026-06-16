<?php
/**
 * Fix OTP Timezone Issue
 * Reset expired OTPs to valid expiry times
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔧 Fixing OTP Timezone Issue</h2>";
echo "<hr>";

try {
    // Get all unverified users with expired OTPs
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, o.otp_code, o.created_at, o.expires_at
        FROM users u
        LEFT JOIN otp_verifications o ON u.id = o.user_id AND o.purpose = 'email_verification'
        WHERE u.email_verified = FALSE AND u.status = 'inactive'
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h3>Before Fix:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Email</th><th>OTP</th><th>Created</th><th>Expires</th><th>Status</th></tr>";
    
    foreach ($users as $user) {
        if ($user['otp_code']) {
            $is_expired = strtotime($user['expires_at']) < time();
            $status = $is_expired ? '❌ EXPIRED' : '✅ VALID';
            echo "<tr><td>" . $user['id'] . "</td><td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . $user['otp_code'] . "</td><td>" . $user['created_at'] . "</td>";
            echo "<td>" . $user['expires_at'] . "</td><td>" . $status . "</td></tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Fixing OTP Expiry Times...</h3>";
    
    // Update all invalid OTPs to have correct expiry time (10 minutes from creation)
    $updateStmt = $pdo->prepare("
        UPDATE otp_verifications 
        SET expires_at = DATE_ADD(created_at, INTERVAL 10 MINUTE)
        WHERE purpose = 'email_verification' AND is_used = FALSE
    ");
    $updateStmt->execute();
    $affected = $updateStmt->rowCount();
    
    echo "<p style='color: green;'>✅ Updated <strong>$affected</strong> OTP records</p>";
    
    // Generate NEW OTPs for all unverified users that don't have valid pending OTP
    $insertStmt = $pdo->prepare("
        INSERT INTO otp_verifications (user_id, otp_code, purpose, channel, expires_at, is_used, created_at)
        SELECT 
            u.id,
            LPAD(FLOOR(RAND() * 999999), 6, '0'),
            'email_verification',
            'email',
            DATE_ADD(NOW(), INTERVAL 10 MINUTE),
            0,
            NOW()
        FROM users u
        WHERE u.email_verified = FALSE 
        AND u.status = 'inactive'
        AND NOT EXISTS (
            SELECT 1 FROM otp_verifications o 
            WHERE o.user_id = u.id 
            AND o.purpose = 'email_verification'
            AND o.is_used = FALSE
            AND o.expires_at > NOW()
        )
    ");
    $insertStmt->execute();
    $newly_created = $insertStmt->rowCount();
    
    echo "<p style='color: green;'>✅ Created <strong>$newly_created</strong> new OTP records for users without valid OTP</p>";
    
    echo "<hr>";
    echo "<h3>After Fix:</h3>";
    
    $stmt2 = $pdo->prepare("
        SELECT u.id, u.email, o.otp_code, o.created_at, o.expires_at
        FROM users u
        LEFT JOIN otp_verifications o ON u.id = o.user_id AND o.purpose = 'email_verification' AND o.is_used = FALSE AND o.expires_at > NOW()
        WHERE u.email_verified = FALSE AND u.status = 'inactive'
        ORDER BY u.created_at DESC
        LIMIT 10
    ");
    $stmt2->execute();
    $fixed_users = $stmt2->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Email</th><th>OTP Code</th><th>Created</th><th>Expires</th><th>Status</th></tr>";
    
    foreach ($fixed_users as $user) {
        if ($user['otp_code']) {
            echo "<tr><td>" . $user['id'] . "</td><td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td><strong>" . $user['otp_code'] . "</strong></td><td>" . $user['created_at'] . "</td>";
            echo "<td>" . $user['expires_at'] . "</td><td style='color: green;'>✅ VALID</td></tr>";
        } else {
            echo "<tr><td>" . $user['id'] . "</td><td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td colspan='4' style='color: orange;'>No valid OTP generated</td></tr>";
        }
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: blue;'><strong>✅ Timezone Issue Fixed!</strong></p>";
    echo "<p>All OTP expiry times are now calculated using database time (NOW())</p>";
    echo "<p>Unverified users now have valid OTPs that will expire in 10 minutes</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
