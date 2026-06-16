<?php
/**
 * Public Email Test Script (No Login Required)
 * For testing PHPMailer and email configuration
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/phpmailer_config.php';
require_once __DIR__ . '/includes/phpmailer_email_helper.php';
require_once __DIR__ . '/includes/email_helper.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - EDUCARE LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        .test-card h1 {
            color: #667eea;
            margin-bottom: 30px;
            font-weight: bold;
        }
        .form-control, .btn {
            border-radius: 8px;
            padding: 12px;
            font-size: 16px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-weight: bold;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .result-box {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }
        .result-box.success {
            background-color: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            display: block;
        }
        .result-box.error {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            display: block;
        }
        .info-box {
            background-color: #d1ecf1;
            border: 2px solid #0c5460;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .method-info {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h1>📧 Email System Test</h1>
        
        <div class="info-box">
            <strong>ℹ️ Note:</strong> This tool tests if your email system is working correctly.
            Check your inbox or spam folder after sending.
        </div>

        <form method="POST">
            <div class="mb-3">
                <label for="test_email" class="form-label">Test Email Address:</label>
                <input type="email" class="form-control" id="test_email" name="test_email" 
                       placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>" required>
            </div>

            <div class="mb-3">
                <label for="test_method" class="form-label">Testing Method:</label>
                <select class="form-control" id="test_method" name="test_method">
                    <option value="phpmailer">PHPMailer (Gmail SMTP)</option>
                    <option value="mail">PHP mail() Function</option>
                </select>
                <div class="method-info">
                    <strong>PHPMailer:</strong> Uses Gmail SMTP (soumosantra588@gmail.com)<br>
                    <strong>mail():</strong> Uses server's mail configuration
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100">🚀 Send Test Email</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $test_email = sanitizeInput($_POST['test_email'] ?? '');
            $test_method = sanitizeInput($_POST['test_method'] ?? 'phpmailer');
            
            if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
                echo '<div class="result-box error">❌ <strong>Invalid Email:</strong> Please enter a valid email address</div>';
            } else {
                try {
                    if ($test_method === 'phpmailer') {
                        // Test with PHPMailer
                        require_once 'vendor/autoload.php';
                        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                        
                        try {
                            $mail->isSMTP();
                            $mail->Host = MAIL_HOST;
                            $mail->SMTPAuth = true;
                            $mail->Username = MAIL_USERNAME;
                            $mail->Password = MAIL_PASSWORD;
                            $mail->SMTPSecure = MAIL_ENCRYPTION;
                            $mail->Port = MAIL_PORT;
                            $mail->SMTPDebug = 0;

                            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
                            $mail->addAddress($test_email);
                            $mail->isHTML(true);
                            $mail->Subject = 'EDUCARE LMS - Test Email';
                            
                            $html_body = '
                            <html>
                            <head>
                                <style>
                                    body { font-family: Arial, sans-serif; }
                                    .container { max-width: 600px; margin: 0 auto; }
                                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                                    .content { background: #f5f5f5; padding: 20px; }
                                    .footer { background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
                                </style>
                            </head>
                            <body>
                                <div class="container">
                                    <div class="header">
                                        <h1>✅ Email System Working!</h1>
                                    </div>
                                    <div class="content">
                                        <p>Hello,</p>
                                        <p>This is a test email from your <strong>EDUCARE LMS</strong> system.</p>
                                        <p><strong>Method Used:</strong> PHPMailer (Gmail SMTP)</p>
                                        <p><strong>Status:</strong> ✅ Email sent successfully</p>
                                        <p>If you received this email, your email system is working correctly!</p>
                                        <hr>
                                        <p style="color: #666; font-size: 12px;">
                                            Timestamp: ' . date('Y-m-d H:i:s') . '<br>
                                            Server: ' . $_SERVER['SERVER_NAME'] . '
                                        </p>
                                    </div>
                                    <div class="footer">
                                        <p>&copy; 2024 EDUCARE LMS. All rights reserved.</p>
                                    </div>
                                </div>
                            </body>
                            </html>
                            ';
                            
                            $mail->Body = $html_body;

                            if ($mail->send()) {
                                echo '<div class="result-box success">
                                    <h5>✅ Success!</h5>
                                    <p>Email sent successfully using PHPMailer (Gmail SMTP)</p>
                                    <p><strong>Recipient:</strong> ' . htmlspecialchars($test_email) . '</p>
                                    <p>Check your inbox or spam folder for the test email.</p>
                                </div>';
                            }
                        } catch (\PHPMailer\PHPMailer\Exception $e) {
                            echo '<div class="result-box error">
                                <h5>❌ Error</h5>
                                <p><strong>PHPMailer Error:</strong></p>
                                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                            </div>';
                        }
                    } else {
                        // Test with mail() function
                        $subject = 'EDUCARE LMS - Test Email';
                        $headers = "MIME-Version: 1.0\r\n";
                        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM_EMAIL . ">\r\n";
                        
                        $html_body = '
                        <html>
                        <body style="font-family: Arial, sans-serif;">
                            <div style="max-width: 600px; margin: 0 auto;">
                                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;">
                                    <h1>✅ Email System Working!</h1>
                                </div>
                                <div style="background: #f5f5f5; padding: 20px;">
                                    <p>Hello,</p>
                                    <p>This is a test email from your <strong>EDUCARE LMS</strong> system.</p>
                                    <p><strong>Method Used:</strong> PHP mail() Function</p>
                                    <p><strong>Status:</strong> ✅ Email sent successfully</p>
                                    <p>If you received this email, your email system is working correctly!</p>
                                    <hr>
                                    <p style="color: #666; font-size: 12px;">
                                        Timestamp: ' . date('Y-m-d H:i:s') . '<br>
                                        Server: ' . $_SERVER['SERVER_NAME'] . '
                                    </p>
                                </div>
                                <div style="background: #333; color: white; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px;">
                                    <p>&copy; 2024 EDUCARE LMS. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ';
                        
                        $mail_result = mail($test_email, $subject, $html_body, $headers);
                        
                        if ($mail_result) {
                            echo '<div class="result-box success">
                                <h5>✅ Success!</h5>
                                <p>Email sent successfully using PHP mail() function</p>
                                <p><strong>Recipient:</strong> ' . htmlspecialchars($test_email) . '</p>
                                <p>Check your inbox or spam folder for the test email.</p>
                            </div>';
                        } else {
                            echo '<div class="result-box error">
                                <h5>❌ Error</h5>
                                <p><strong>mail() function returned FALSE</strong></p>
                                <p>The mail() function is not properly configured on your server.</p>
                            </div>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="result-box error">
                        <h5>❌ Error</h5>
                        <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    </div>';
                }
            }
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
