<?php
/**
 * Email Notification System for Online Exam Portal
 * Handles automated email notifications for registration and approvals
 * Uses PHPMailer with Gmail SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Require PHPMailer configuration
if (!file_exists(__DIR__ . '/phpmailer_config.php')) {
    error_log('PHPMailer config not found');
    return false;
}

require_once __DIR__ . '/phpmailer_config.php';
require_once __DIR__ . '/db.php';

/**
 * ============================================================================
 * REGISTRATION EMAIL NOTIFICATION
 * ============================================================================
 * 
 * Send email to new student after successful registration
 * Includes: Full Name, User ID, Temporary Password
 * Status: Awaiting Admin Verification
 */
function sendRegistrationNotificationEmail($email, $full_name, $user_id, $password) {
    try {
        // Validate inputs
        if (empty($email) || empty($full_name) || empty($user_id) || empty($password)) {
            error_log('sendRegistrationNotificationEmail: Invalid parameters provided');
            return false;
        }

        // Get mailer instance
        $mail = getMailer();
        if (!$mail) {
            error_log('sendRegistrationNotificationEmail: Failed to initialize mailer');
            return false;
        }

        // Email subject
        $subject = "Account Registration Successful - " . APP_NAME . " Online Exam Portal";

        // Professional HTML email content
        $htmlContent = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #f5f5f5;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                }
                .header h1 {
                    font-size: 28px;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                .header p {
                    font-size: 14px;
                    opacity: 0.9;
                }
                .content {
                    padding: 40px 30px;
                }
                .greeting {
                    font-size: 16px;
                    margin-bottom: 20px;
                    color: #333;
                }
                .credentials-section {
                    background: #f8f9ff;
                    border-left: 4px solid #667eea;
                    padding: 20px;
                    border-radius: 4px;
                    margin: 25px 0;
                }
                .credentials-section h3 {
                    color: #667eea;
                    font-size: 14px;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .credential-item {
                    margin: 12px 0;
                    padding: 10px;
                    background: white;
                    border-radius: 4px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .credential-label {
                    font-weight: 600;
                    color: #555;
                    font-size: 13px;
                }
                .credential-value {
                    font-family: 'Courier New', monospace;
                    color: #667eea;
                    font-weight: 600;
                    font-size: 14px;
                    word-break: break-all;
                }
                .status-box {
                    background: #fff3cd;
                    border: 1px solid #ffc107;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 20px 0;
                    color: #856404;
                    font-size: 13px;
                }
                .status-box strong {
                    display: block;
                    margin-bottom: 8px;
                    color: #333;
                }
                .steps {
                    margin: 25px 0;
                }
                .steps h3 {
                    color: #333;
                    font-size: 14px;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                .step {
                    padding: 12px 15px;
                    margin-bottom: 10px;
                    background: #f9f9f9;
                    border-left: 3px solid #667eea;
                    border-radius: 2px;
                }
                .step-number {
                    display: inline-block;
                    width: 24px;
                    height: 24px;
                    background: #667eea;
                    color: white;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 24px;
                    font-weight: bold;
                    margin-right: 10px;
                    font-size: 12px;
                }
                .step-text {
                    display: inline-block;
                    vertical-align: middle;
                    font-size: 13px;
                }
                .security-notice {
                    background: #f8d7da;
                    border: 1px solid #f5c6cb;
                    color: #721c24;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 20px 0;
                    font-size: 12px;
                }
                .security-notice strong {
                    display: block;
                    margin-bottom: 8px;
                }
                .security-notice ul {
                    margin-left: 20px;
                    margin-top: 8px;
                }
                .security-notice li {
                    margin: 5px 0;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white !important;
                    padding: 12px 30px;
                    border-radius: 4px;
                    text-decoration: none;
                    margin: 20px 0;
                    font-weight: 600;
                    font-size: 14px;
                    text-align: center;
                }
                .footer {
                    background: #f5f5f5;
                    padding: 20px 30px;
                    border-top: 1px solid #eee;
                    text-align: center;
                    font-size: 12px;
                    color: #888;
                }
                .footer p {
                    margin: 5px 0;
                }
                .divider {
                    height: 1px;
                    background: #eee;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <!-- Header -->
                <div class='header'>
                    <h1>Registration Successful! 🎉</h1>
                    <p>" . APP_NAME . " Online Exam Portal</p>
                </div>

                <!-- Content -->
                <div class='content'>
                    <!-- Greeting -->
                    <p class='greeting'>Dear <strong>" . htmlspecialchars($full_name) . "</strong>,</p>

                    <p style='margin-bottom: 20px; font-size: 14px;'>
                        Welcome to " . APP_NAME . "! Your account has been successfully created. Your login credentials are provided below.
                    </p>

                    <!-- Credentials Section -->
                    <div class='credentials-section'>
                        <h3>📋 Your Credentials</h3>
                        <div class='credential-item'>
                            <span class='credential-label'>User ID:</span>
                            <span class='credential-value'>" . htmlspecialchars($user_id) . "</span>
                        </div>
                        <div class='credential-item'>
                            <span class='credential-label'>Email:</span>
                            <span class='credential-value'>" . htmlspecialchars($email) . "</span>
                        </div>
                        <div class='credential-item'>
                            <span class='credential-label'>Password:</span>
                            <span class='credential-value'>" . htmlspecialchars($password) . "</span>
                        </div>
                    </div>

                    <!-- Status Box -->
                    <div class='status-box'>
                        <strong>⏳ Account Status: Under Verification</strong>
                        Your account is currently under admin verification. You will receive another email once your account has been approved. This typically takes 1-2 business days.
                    </div>

                    <!-- Steps -->
                    <div class='steps'>
                        <h3>📝 What's Next?</h3>
                        <div class='step'>
                            <span class='step-number'>1</span>
                            <span class='step-text'>Wait for admin approval (we'll send you an email)</span>
                        </div>
                        <div class='step'>
                            <span class='step-number'>2</span>
                            <span class='step-text'>Once approved, log in with your credentials</span>
                        </div>
                        <div class='step'>
                            <span class='step-number'>3</span>
                            <span class='step-text'>Complete your profile and start your courses</span>
                        </div>
                        <div class='step'>
                            <span class='step-number'>4</span>
                            <span class='step-text'>Participate in exams and track your progress</span>
                        </div>
                    </div>

                    <!-- Security Notice -->
                    <div class='security-notice'>
                        <strong>🔒 Security Notice</strong>
                        <ul>
                            <li>Never share your password with anyone</li>
                            <li>" . APP_NAME . " staff will never ask for your password</li>
                            <li>Always use https:// when accessing the portal</li>
                            <li>Log out after each session</li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <p style='margin-top: 25px; font-size: 13px; color: #666;'>
                        If you have any questions or need support, please contact our admin team through the portal or email us at <strong>" . MAIL_FROM_EMAIL . "</strong>
                    </p>
                </div>

                <!-- Footer -->
                <div class='footer'>
                    <p><strong>" . APP_NAME . "</strong></p>
                    <p>Online Examination & Learning Management System</p>
                    <p style='margin-top: 15px; color: #aaa;'>This is an automated email. Please do not reply to this message.</p>
                    <p style='margin-top: 10px; font-size: 11px;'>© " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version as fallback
        $textContent = "
        WELCOME TO " . APP_NAME . "!

        Dear " . $full_name . ",

        Your account has been successfully created.

        YOUR CREDENTIALS:
        User ID: " . $user_id . "
        Email: " . $email . "
        Password: (provided during registration)

        ACCOUNT STATUS:
        Your account is currently under admin verification. You will receive an approval email within 1-2 business days.

        NEXT STEPS:
        1. Wait for admin approval
        2. Log in with your credentials once approved
        3. Complete your profile
        4. Start learning and taking exams

        SECURITY NOTICE:
        - Never share your password
        - Always use https when accessing the portal
        - Log out after each session

        For support, contact: " . MAIL_FROM_EMAIL . "

        This is an automated email. Please do not reply.
        ";

        // Configure email
        $mail->addAddress($email, $full_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlContent;
        $mail->AltBody = $textContent;

        // Send email
        if ($mail->send()) {
            logEmailNotification($email, 'registration', 'sent', $user_id);
            return true;
        } else {
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            logEmailNotification($email, 'registration', 'failed', $user_id, $mail->ErrorInfo);
            return false;
        }

    } catch (Exception $e) {
        error_log('Registration Email Exception: ' . $e->getMessage());
        logEmailNotification($email ?? 'unknown', 'registration', 'failed', $user_id ?? 0, $e->getMessage());
        return false;
    }
}

/**
 * ============================================================================
 * APPROVAL EMAIL NOTIFICATION
 * ============================================================================
 * 
 * Send email to student when admin approves their account
 * Includes: Full Name, User ID, Login URL
 * Does NOT include password
 */
function sendApprovalNotificationEmail($email, $full_name, $user_id) {
    try {
        // Validate inputs
        if (empty($email) || empty($full_name) || empty($user_id)) {
            error_log('sendApprovalNotificationEmail: Invalid parameters provided');
            return false;
        }

        // Get mailer instance
        $mail = getMailer();
        if (!$mail) {
            error_log('sendApprovalNotificationEmail: Failed to initialize mailer');
            return false;
        }

        // Email subject
        $subject = "Account Approved! ✅ Welcome to " . APP_NAME;

        // Build login URL
        $login_url = BASE_URL . '/login.php';

        // Professional HTML email content
        $htmlContent = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background: #f5f5f5;
                }
                .email-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                }
                .header h1 {
                    font-size: 28px;
                    margin-bottom: 10px;
                    font-weight: 600;
                }
                .header p {
                    font-size: 14px;
                    opacity: 0.9;
                }
                .success-badge {
                    display: inline-block;
                    background: rgba(255, 255, 255, 0.2);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 12px;
                    font-weight: 600;
                    margin-top: 10px;
                }
                .content {
                    padding: 40px 30px;
                }
                .greeting {
                    font-size: 16px;
                    margin-bottom: 20px;
                    color: #333;
                }
                .approval-message {
                    background: #d4edda;
                    border: 1px solid #c3e6cb;
                    color: #155724;
                    padding: 20px;
                    border-radius: 4px;
                    margin: 20px 0;
                    font-size: 14px;
                }
                .approval-message strong {
                    display: block;
                    margin-bottom: 10px;
                    font-size: 16px;
                }
                .user-info {
                    background: #f8f9ff;
                    border-left: 4px solid #28a745;
                    padding: 20px;
                    border-radius: 4px;
                    margin: 25px 0;
                }
                .user-info h3 {
                    color: #28a745;
                    font-size: 14px;
                    margin-bottom: 15px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .info-item {
                    margin: 12px 0;
                    padding: 10px;
                    background: white;
                    border-radius: 4px;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .info-label {
                    font-weight: 600;
                    color: #555;
                    font-size: 13px;
                }
                .info-value {
                    font-family: 'Courier New', monospace;
                    color: #28a745;
                    font-weight: 600;
                    font-size: 14px;
                }
                .cta-section {
                    text-align: center;
                    margin: 30px 0;
                    padding: 20px;
                    background: #f0fdf4;
                    border-radius: 4px;
                }
                .cta-button {
                    display: inline-block;
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white !important;
                    padding: 14px 40px;
                    border-radius: 4px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 15px;
                    margin: 10px 0;
                    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
                }
                .cta-button:hover {
                    background: linear-gradient(135deg, #218838 0%, #1aa179 100%);
                }
                .instructions {
                    margin: 25px 0;
                }
                .instructions h3 {
                    color: #333;
                    font-size: 14px;
                    margin-bottom: 15px;
                    font-weight: 600;
                }
                .instruction-item {
                    padding: 12px 15px;
                    margin-bottom: 10px;
                    background: #f9f9f9;
                    border-left: 3px solid #28a745;
                    border-radius: 2px;
                    font-size: 13px;
                }
                .step-number {
                    display: inline-block;
                    width: 24px;
                    height: 24px;
                    background: #28a745;
                    color: white;
                    border-radius: 50%;
                    text-align: center;
                    line-height: 24px;
                    font-weight: bold;
                    margin-right: 10px;
                    font-size: 12px;
                }
                .security-note {
                    background: #e7f3ff;
                    border: 1px solid #b3d9ff;
                    color: #004085;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 20px 0;
                    font-size: 12px;
                }
                .security-note strong {
                    display: block;
                    margin-bottom: 8px;
                }
                .footer {
                    background: #f5f5f5;
                    padding: 20px 30px;
                    border-top: 1px solid #eee;
                    text-align: center;
                    font-size: 12px;
                    color: #888;
                }
                .footer p {
                    margin: 5px 0;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <!-- Header -->
                <div class='header'>
                    <h1>Account Approved! 🎉</h1>
                    <p>You're ready to get started</p>
                    <div class='success-badge'>✅ Status: Active</div>
                </div>

                <!-- Content -->
                <div class='content'>
                    <!-- Greeting -->
                    <p class='greeting'>Dear <strong>" . htmlspecialchars($full_name) . "</strong>,</p>

                    <!-- Approval Message -->
                    <div class='approval-message'>
                        <strong>Great News!</strong>
                        Your account has been approved by the administrator. You can now log in and start learning!
                    </div>

                    <p style='margin-bottom: 20px; font-size: 14px;'>
                        Your " . APP_NAME . " account is now active and ready to use. You have full access to all courses, exams, and learning materials.
                    </p>

                    <!-- User Info -->
                    <div class='user-info'>
                        <h3>📋 Your Account Information</h3>
                        <div class='info-item'>
                            <span class='info-label'>User ID:</span>
                            <span class='info-value'>" . htmlspecialchars($user_id) . "</span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Email:</span>
                            <span class='info-value'>" . htmlspecialchars($email) . "</span>
                        </div>
                        <div class='info-item'>
                            <span class='info-label'>Status:</span>
                            <span class='info-value'>✅ Active</span>
                        </div>
                    </div>

                    <!-- CTA Section -->
                    <div class='cta-section'>
                        <p style='margin-bottom: 15px; font-size: 14px;'>Click the button below to access the portal:</p>
                        <a href='" . htmlspecialchars($login_url) . "' class='cta-button'>Login to Portal →</a>
                        <p style='margin-top: 15px; font-size: 12px; color: #666;'>Or visit: <span style='font-family: monospace;'>" . htmlspecialchars($login_url) . "</span></p>
                    </div>

                    <!-- Instructions -->
                    <div class='instructions'>
                        <h3>🚀 Getting Started</h3>
                        <div class='instruction-item'>
                            <span class='step-number'>1</span>
                            <span>Go to the login page using the link above</span>
                        </div>
                        <div class='instruction-item'>
                            <span class='step-number'>2</span>
                            <span>Enter your User ID and password</span>
                        </div>
                        <div class='instruction-item'>
                            <span class='step-number'>3</span>
                            <span>Complete your profile in the Dashboard</span>
                        </div>
                        <div class='instruction-item'>
                            <span class='step-number'>4</span>
                            <span>Start exploring courses and take exams</span>
                        </div>
                    </div>

                    <!-- Security Note -->
                    <div class='security-note'>
                        <strong>🔐 Important Security Reminders</strong>
                        Remember to keep your password confidential and never share it with anyone, including support staff.
                    </div>

                    <!-- Support -->
                    <p style='margin-top: 25px; font-size: 13px; color: #666;'>
                        If you experience any issues accessing your account, please contact our support team at <strong>" . MAIL_FROM_EMAIL . "</strong>
                    </p>
                </div>

                <!-- Footer -->
                <div class='footer'>
                    <p><strong>" . APP_NAME . "</strong></p>
                    <p>Online Examination & Learning Management System</p>
                    <p style='margin-top: 15px; color: #aaa;'>This is an automated email. Please do not reply to this message.</p>
                    <p style='margin-top: 10px; font-size: 11px;'>© " . date('Y') . " " . APP_NAME . ". All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Plain text version
        $textContent = "
        ACCOUNT APPROVED!

        Dear " . $full_name . ",

        Great news! Your account has been approved.

        YOUR ACCOUNT INFORMATION:
        User ID: " . $user_id . "
        Email: " . $email . "
        Status: Active ✅

        NEXT STEPS:
        1. Log in to the portal: " . $login_url . "
        2. Complete your profile
        3. Start taking exams and learning

        LOGIN URL: " . $login_url . "

        SECURITY REMINDERS:
        - Keep your password confidential
        - Do not share it with anyone
        - Always use https when accessing

        For support, contact: " . MAIL_FROM_EMAIL . "

        This is an automated email. Please do not reply.
        ";

        // Configure email
        $mail->addAddress($email, $full_name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlContent;
        $mail->AltBody = $textContent;

        // Send email
        if ($mail->send()) {
            logEmailNotification($email, 'approval', 'sent', $user_id);
            return true;
        } else {
            error_log('PHPMailer Error (Approval): ' . $mail->ErrorInfo);
            logEmailNotification($email, 'approval', 'failed', $user_id, $mail->ErrorInfo);
            return false;
        }

    } catch (Exception $e) {
        error_log('Approval Email Exception: ' . $e->getMessage());
        logEmailNotification($email ?? 'unknown', 'approval', 'failed', $user_id ?? 0, $e->getMessage());
        return false;
    }
}

/**
 * ============================================================================
 * EMAIL NOTIFICATION LOGGING
 * ============================================================================
 * 
 * Log all email notifications to database for audit trail
 */
function logEmailNotification($email, $type, $status, $user_id = 0, $error_message = '') {
    try {
        global $pdo;
        
        // Create email_notifications table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                email VARCHAR(255) NOT NULL,
                notification_type ENUM('registration', 'approval', 'rejection', 'reset_password', 'otp') NOT NULL,
                status ENUM('sent', 'failed', 'pending') NOT NULL DEFAULT 'pending',
                error_message LONGTEXT,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                retry_count INT DEFAULT 0,
                INDEX idx_user_id (user_id),
                INDEX idx_email (email),
                INDEX idx_type (notification_type),
                INDEX idx_status (status),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Insert log entry
        $stmt = $pdo->prepare("
            INSERT INTO email_notifications 
            (user_id, email, notification_type, status, error_message) 
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $user_id ?: null,
            $email,
            $type,
            $status,
            $error_message ?: null
        ]);

    } catch (Exception $e) {
        error_log('Email Notification Logging Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * ============================================================================
 * HELPER FUNCTION: Get User Details
 * ============================================================================
 */
function getUserDetails($user_id) {
    try {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, phone, status, approval_status 
            FROM users 
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log('getUserDetails Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * ============================================================================
 * HELPER FUNCTION: Resend Approval Email
 * ============================================================================
 * 
 * Resend approval email to student (useful for retries)
 */
function resendApprovalEmail($user_id) {
    try {
        $user = getUserDetails($user_id);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if ($user['approval_status'] !== 'approved') {
            return ['success' => false, 'message' => 'User account is not approved'];
        }

        $sent = sendApprovalNotificationEmail(
            $user['email'],
            $user['full_name'],
            $user['id']
        );

        if ($sent) {
            return ['success' => true, 'message' => 'Approval email sent successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email'];
        }

    } catch (Exception $e) {
        error_log('resendApprovalEmail Error: ' . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>
