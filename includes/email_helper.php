<?php
/**
 * Email Helper Functions
 * Handles all email sending operations
 */

/**
 * Send OTP via Email
 * 
 * @param string $email User's email address
 * @param string $otp_code OTP code to send
 * @param string $user_name User's name (optional)
 * @return bool Success status
 */
function sendOTPEmail($email, $otp_code, $user_name = 'User') {
    try {
        $subject = "Your OTP Verification Code - " . APP_NAME;
        
        $message = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .otp-box { background: #f0f4ff; border: 2px solid #667eea; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 4px; color: #667eea; font-family: 'Courier New', monospace; }
                .otp-timer { color: #666; font-size: 14px; margin-top: 10px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px; }
                .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; color: #856404; font-size: 13px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>" . APP_NAME . "</h1>
                    <p>Email Verification</p>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$user_name}</strong>,</p>
                    
                    <p>You have requested to verify your email address or reset your password. Please use the OTP code below:</p>
                    
                    <div class='otp-box'>
                        <div class='otp-code'>{$otp_code}</div>
                        <div class='otp-timer'>Valid for 10 minutes</div>
                    </div>
                    
                    <p>Enter this code on the verification page to proceed.</p>
                    
                    <div class='warning'>
                        <strong>⚠️ Security Notice:</strong><br>
                        • Never share this OTP with anyone<br>
                        • " . APP_NAME . " will never ask for your OTP via email or phone<br>
                        • If you didn't request this, please ignore this email
                    </div>
                    
                    <p>If you didn't request this verification, you can safely ignore this email.</p>
                    
                    <div class='footer'>
                        <p>" . APP_NAME . " | Secure Authentication System</p>
                        <p style='margin-top: 10px; font-size: 11px; color: #999;'>This is an automated email. Please do not reply to this message.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Set headers for HTML email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Send email
        $result = mail($email, $subject, $message, $headers);
        
        if ($result) {
            // Log email
            logEmail($email, $subject, 'sent');
            return true;
        } else {
            logEmail($email, $subject, 'failed');
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Email Send Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send Welcome Email
 * 
 * @param string $email User's email address
 * @param string $user_name User's name
 * @return bool Success status
 */
function sendWelcomeEmail($email, $user_name = 'User') {
    try {
        $subject = "Welcome to " . APP_NAME . "!";
        
        $message = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .features { list-style: none; padding: 0; }
                .features li { padding: 8px 0; padding-left: 25px; position: relative; }
                .features li:before { content: '✓'; position: absolute; left: 0; color: #667eea; font-weight: bold; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 15px; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to " . APP_NAME . "!</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$user_name}</strong>,</p>
                    
                    <p>Your account has been successfully created! We're excited to have you on board.</p>
                    
                    <p><strong>You can now:</strong></p>
                    <ul class='features'>
                        <li>Access our learning platform</li>
                        <li>Enroll in courses and exams</li>
                        <li>Download study materials</li>
                        <li>Track your progress</li>
                        <li>Earn certificates</li>
                    </ul>
                    
                    <p>If you have any questions or need assistance, feel free to reach out to our support team.</p>
                    
                    <div class='footer'>
                        <p>" . APP_NAME . " | Your Learning Partner</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        
        $result = mail($email, $subject, $message, $headers);
        
        if ($result) {
            logEmail($email, $subject, 'sent');
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Welcome Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log email for auditing
 * 
 * @param string $email Email address
 * @param string $subject Email subject
 * @param string $status Email status (pending, sent, failed)
 */
function logEmail($email, $subject, $status = 'sent') {
    global $pdo;
    
    try {
        // Don't require user_id for logging
        $stmt = $pdo->prepare("
            INSERT INTO email_logs (user_id, email_address, subject, status, sent_at) 
            VALUES (NULL, ?, ?, ?, ?)
        ");
        
        $sent_at = ($status === 'sent') ? date('Y-m-d H:i:s') : null;
        $stmt->execute([$email, $subject, $status, $sent_at]);
        
    } catch (PDOException $e) {
        error_log("Email Log Error: " . $e->getMessage());
    }
}
?>
