<?php
/**
 * PHPMailer Diagnostic Check
 */

require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 PHPMailer Diagnostic Check</h2>";
echo "<hr>";

// Check 1: PHPMailer installed?
echo "<h3>1️⃣ Checking PHPMailer Installation:</h3>";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color: green;'>✅ PHPMailer vendor autoload found</p>";
    
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<p style='color: green;'>✅ PHPMailer class exists</p>";
    } else {
        echo "<p style='color: red;'>❌ PHPMailer class NOT found</p>";
    }
} else {
    echo "<p style='color: red;'>❌ vendor/autoload.php NOT found</p>";
    echo "<p style='color: orange;'><strong>⚠️ PHPMailer NOT installed!</strong></p>";
    echo "<p>To install: <code>composer require phpmailer/phpmailer</code></p>";
}

echo "<hr>";

// Check 2: PHPMailer config file exists?
echo "<h3>2️⃣ Checking PHPMailer Config:</h3>";

if (file_exists(__DIR__ . '/includes/phpmailer_config.php')) {
    echo "<p style='color: green;'>✅ phpmailer_config.php exists</p>";
    
    require_once __DIR__ . '/includes/phpmailer_config.php';
    
    echo "<p><strong>Configuration:</strong></p>";
    echo "<ul>";
    echo "<li>MAIL_HOST: " . (defined('MAIL_HOST') ? MAIL_HOST : '❌ NOT DEFINED') . "</li>";
    echo "<li>MAIL_PORT: " . (defined('MAIL_PORT') ? MAIL_PORT : '❌ NOT DEFINED') . "</li>";
    echo "<li>MAIL_USERNAME: " . (defined('MAIL_USERNAME') ? substr(MAIL_USERNAME, 0, 5) . "***" : '❌ NOT DEFINED') . "</li>";
    echo "<li>MAIL_ENCRYPTION: " . (defined('MAIL_ENCRYPTION') ? MAIL_ENCRYPTION : '❌ NOT DEFINED') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>❌ phpmailer_config.php NOT found</p>";
}

echo "<hr>";

// Check 3: Email helper files exist?
echo "<h3>3️⃣ Checking Email Helper Files:</h3>";

$files = [
    'includes/email_helper.php' => 'PHP mail() function version',
    'includes/phpmailer_email_helper.php' => 'PHPMailer SMTP version'
];

foreach ($files as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✅ $file ($desc)</p>";
    } else {
        echo "<p style='color: red;'>❌ $file NOT found</p>";
    }
}

echo "<hr>";

// Check 4: Test email logs table
echo "<h3>4️⃣ Checking Email Logs Table:</h3>";

try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM email_logs");
    $row = $result->fetch();
    echo "<p style='color: green;'>✅ email_logs table exists</p>";
    echo "<p>Total logs: " . $row['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ email_logs table error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check 5: Test PHP mail() function
echo "<h3>5️⃣ Testing PHP mail() Function:</h3>";

$test_email = 'test@example.com';
$test_subject = 'Test Email from EDUCARE';
$test_message = '<h1>Test</h1><p>This is a test email from your EDUCARE LMS system.</p>';
$test_headers = "MIME-Version: 1.0" . "\r\n";
$test_headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";

$mail_result = @mail($test_email, $test_subject, $test_message, $test_headers);

if ($mail_result) {
    echo "<p style='color: green;'>✅ mail() function returned TRUE</p>";
    echo "<p style='color: orange;'><strong>Note:</strong> This doesn't guarantee email was sent, check spam folder</p>";
} else {
    echo "<p style='color: red;'>❌ mail() function returned FALSE</p>";
}

echo "<hr>";

// Check 6: PHPMailer connection test (if installed)
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<h3>6️⃣ Testing PHPMailer SMTP Connection:</h3>";
    
    try {
        $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        $mailer->isSMTP();
        $mailer->Host = MAIL_HOST;
        $mailer->Port = MAIL_PORT;
        $mailer->SMTPAuth = true;
        $mailer->Username = MAIL_USERNAME;
        $mailer->Password = MAIL_PASSWORD;
        $mailer->SMTPSecure = MAIL_ENCRYPTION;
        $mailer->SMTPDebug = 0;
        
        if ($mailer->smtpConnect()) {
            echo "<p style='color: green;'>✅ PHPMailer SMTP connection SUCCESSFUL</p>";
            $mailer->smtpClose();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ PHPMailer SMTP Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";

// Summary
echo "<h2 style='color: blue;'>📋 Summary:</h2>";
echo "<p>If you see ✅ green marks, system is ready for email sending.</p>";
echo "<p>If you see ❌ red marks, fix those issues first.</p>";
echo "<p><strong>Next Step:</strong> <a href='test_mail.php'>Click here to test sending email</a></p>";
?>
