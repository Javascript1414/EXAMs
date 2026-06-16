<?php
/**
 * Email Sending Test - Direct Test
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>📧 Email Sending Diagnostic</h2>";
echo "<hr>";

// Test 1: Check PHP mail() configuration
echo "<h3>1️⃣ PHP mail() Function Check:</h3>";

$test_email = 'test_' . time() . '@test.com';
$test_subject = 'EDUCARE - Test Email ' . date('Y-m-d H:i:s');
$test_message = '<html><body>';
$test_message .= '<h1>Test Email</h1>';
$test_message .= '<p>This is a test email to verify mail() function is working.</p>';
$test_message .= '<p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>';
$test_message .= '</body></html>';

$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";

echo "<p>Test Details:</p>";
echo "<ul>";
echo "<li>To: $test_email</li>";
echo "<li>Subject: $test_subject</li>";
echo "<li>From: noreply@" . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li>Content-Type: text/html</li>";
echo "</ul>";

$mail_result = @mail($test_email, $test_subject, $test_message, $headers);

echo "<p><strong>mail() Result:</strong> " . ($mail_result ? "✅ TRUE (Sent to queue)" : "❌ FALSE (Failed)") . "</p>";

echo "<hr>";

// Test 2: Check sendmail_path
echo "<h3>2️⃣ Server Configuration Check:</h3>";

$sendmail_path = ini_get('sendmail_path');
$mail_function = ini_get('disable_functions');

echo "<p>sendmail_path: " . ($sendmail_path ?: '❌ Not set') . "</p>";
echo "<p>mail() function disabled: " . (strpos($mail_function, 'mail') !== false ? "❌ YES (DISABLED!)" : "✅ NO (Enabled)") . "</p>";

echo "<hr>";

// Test 3: Log the email to database
echo "<h3>3️⃣ Logging Email:</h3>";

try {
    $log_stmt = $pdo->prepare("
        INSERT INTO email_logs (user_id, email_address, subject, status, sent_at) 
        VALUES (NULL, ?, ?, ?, NOW())
    ");
    
    $log_result = $log_stmt->execute([$test_email, $test_subject, $mail_result ? 'sent' : 'failed']);
    
    if ($log_result) {
        echo "<p style='color: green;'>✅ Email logged to database</p>";
        
        // Check email_logs table
        $check = $pdo->query("SELECT COUNT(*) as count FROM email_logs");
        $row = $check->fetch();
        echo "<p>Total emails logged: " . $row['count'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database log error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 4: Direct sendOTPEmail() test
echo "<h3>4️⃣ Testing sendOTPEmail() Function:</h3>";

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email_helper.php';

$otp_email = 'otp_test_' . time() . '@test.com';
$otp_code = '123456';
$otp_user = 'Test User';

echo "<p>Sending test OTP email...</p>";

$otp_result = sendOTPEmail($otp_email, $otp_code, $otp_user);

echo "<p><strong>sendOTPEmail() Result:</strong> " . ($otp_result ? "✅ TRUE" : "❌ FALSE") . "</p>";

echo "<hr>";

// Test 5: Check actual email logs
echo "<h3>5️⃣ Recent Email Logs:</h3>";

try {
    $logs = $pdo->query("
        SELECT id, email_address, subject, status, sent_at, created_at 
        FROM email_logs 
        ORDER BY id DESC 
        LIMIT 10
    ");
    
    if ($logs->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr style='background: #667eea; color: white;'>";
        echo "<th>ID</th><th>Email</th><th>Subject</th><th>Status</th><th>Sent At</th>";
        echo "</tr>";
        
        while ($log = $logs->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $log['id'] . "</td>";
            echo "<td>" . substr($log['email_address'], 0, 30) . "</td>";
            echo "<td>" . substr($log['subject'], 0, 40) . "</td>";
            echo "<td><span style='color: " . ($log['status'] === 'sent' ? 'green' : 'red') . ";'>" . $log['status'] . "</span></td>";
            echo "<td>" . ($log['sent_at'] ?? '-') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>❌ No email logs found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error reading logs: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h2 style='color: blue;'>📊 Summary:</h2>";
echo "<p>If <strong>mail() = TRUE</strong> but emails not arriving:</p>";
echo "<ul>";
echo "<li>Check your inbox AND spam folder</li>";
echo "<li>Server may have mail queue (check later)</li>";
echo "<li>Contact hosting provider about mail configuration</li>";
echo "</ul>";

echo "<p>If <strong>mail() = FALSE</strong>:</p>";
echo "<ul>";
echo "<li>✅ Use PHPMailer with Gmail SMTP instead</li>";
<li>Go to: <a href='use_phpmailer_for_registration.php'>use_phpmailer_for_registration.php</a></li>";
echo "</ul>";
?>
