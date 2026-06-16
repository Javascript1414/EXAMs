<?php
/**
 * Test OTP Issue - Debug Script
 * Check why OTP is not being sent during registration
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/otp_helper.php';

echo "<h2>🔍 OTP Email Issue Debug</h2>";
echo "<hr>";

// 1. Check unverified users in database
echo "<h3>1️⃣ Unverified Users in Database:</h3>";
$stmt = $pdo->prepare("SELECT id, full_name, email, email_verified, status, created_at FROM users WHERE email_verified = FALSE ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$unverified = $stmt->fetchAll();

if (empty($unverified)) {
    echo "<p style='color: orange;'>⚠️ No unverified users found</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Created</th></tr>";
    foreach ($unverified as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";

// 2. Check OTPs in database
echo "<h3>2️⃣ OTP Records in Database:</h3>";
$stmt = $pdo->prepare("SELECT id, user_id, otp_code, purpose, is_used, created_at, expires_at FROM otp_verifications ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$otps = $stmt->fetchAll();

if (empty($otps)) {
    echo "<p style='color: orange;'>⚠️ No OTP records found</p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>User ID</th><th>OTP Code</th><th>Purpose</th><th>Used</th><th>Created</th><th>Expires</th></tr>";
    foreach ($otps as $otp) {
        $is_expired = strtotime($otp['expires_at']) < time();
        $status_color = ($otp['is_used']) ? 'gray' : ($is_expired ? 'red' : 'green');
        $status_text = ($otp['is_used']) ? 'USED' : ($is_expired ? 'EXPIRED' : 'VALID');
        
        echo "<tr>";
        echo "<td>" . $otp['id'] . "</td>";
        echo "<td>" . $otp['user_id'] . "</td>";
        echo "<td><strong>" . $otp['otp_code'] . "</strong></td>";
        echo "<td>" . $otp['purpose'] . "</td>";
        echo "<td style='color: " . $status_color . "; font-weight: bold;'>" . $status_text . "</td>";
        echo "<td>" . $otp['created_at'] . "</td>";
        echo "<td>" . $otp['expires_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";

// 3. Check PHPMailer Configuration
echo "<h3>3️⃣ PHPMailer Configuration Check:</h3>";
require_once __DIR__ . '/includes/phpmailer_config.php';

echo "<ul>";
echo "<li><strong>Host:</strong> " . MAIL_HOST . "</li>";
echo "<li><strong>Port:</strong> " . MAIL_PORT . "</li>";
echo "<li><strong>Encryption:</strong> " . MAIL_ENCRYPTION . "</li>";
echo "<li><strong>Username:</strong> " . MAIL_USERNAME . "</li>";
echo "<li><strong>Password:</strong> " . (MAIL_PASSWORD ? "✅ Configured" : "❌ Not set") . "</li>";
echo "<li><strong>From Email:</strong> " . MAIL_FROM_EMAIL . "</li>";
echo "<li><strong>From Name:</strong> " . MAIL_FROM_NAME . "</li>";
echo "<li><strong>SMTP Enabled:</strong> " . (MAIL_USE_SMTP ? "✅ Yes" : "❌ No") . "</li>";
echo "</ul>";

echo "<hr>";

// 4. Try to send test OTP email
echo "<h3>4️⃣ Test Email Send Attempt:</h3>";

require_once __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port = MAIL_PORT;
    $mail->SMTPDebug = 0;
    
    $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $mail->addAddress('test@example.com', 'Test User');
    $mail->isHTML(true);
    $mail->Subject = 'EDUCARE - Test OTP Email';
    $mail->Body = '<h1>Test OTP: 123456</h1>';
    
    if ($mail->send()) {
        echo "<p style='color: green;'>✅ <strong>Email sending works!</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ Email sending failed</p>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception occurred:</p>";
    echo "<pre style='background: #fee; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}

echo "<hr>";

// 5. Check if email logs exist
echo "<h3>5️⃣ Email Sending History:</h3>";
$stmt = $pdo->prepare("SELECT id, recipient_email, subject, status, sent_at FROM email_logs ORDER BY sent_at DESC LIMIT 5");
try {
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    if (empty($logs)) {
        echo "<p style='color: orange;'>⚠️ No email logs found</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Email</th><th>Subject</th><th>Status</th><th>Sent At</th></tr>";
        foreach ($logs as $log) {
            $status_color = ($log['status'] == 'sent') ? 'green' : 'red';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['recipient_email']) . "</td>";
            echo "<td>" . htmlspecialchars($log['subject']) . "</td>";
            echo "<td style='color: " . $status_color . "; font-weight: bold;'>" . $log['status'] . "</td>";
            echo "<td>" . $log['sent_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "<p style='color: orange;'>⚠️ Email logs table doesn't exist</p>";
}

?>
