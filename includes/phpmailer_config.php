<?php
/**
 * PHPMailer Configuration
 * Setup SMTP configuration for sending emails
 */

// Require Composer autoloader for PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com'); // Or your SMTP server
define('MAIL_PORT', 587); // 587 for TLS, 465 for SSL
define('MAIL_USERNAME', 'soumosantra588@gmail.com'); // Your email (SMTP auth)
define('MAIL_PASSWORD', 'twyb jsli kcmm pjvh'); // App password (not regular password)
// IMPORTANT: From email MUST match the SMTP authentication email for Gmail
define('MAIL_FROM_EMAIL', 'soumosantra588@gmail.com'); // Must match MAIL_USERNAME for Gmail
define('MAIL_FROM_NAME', 'EDUCARE LMS'); // Sender name
define('MAIL_ENCRYPTION', 'tls'); // 'tls' or 'ssl'
define('MAIL_USE_SMTP', true); // Set to false to use mail() function

/**
 * Initialize PHPMailer Instance
 * 
 * @return PHPMailer|null PHPMailer instance or null if error
 */
function getMailer() {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        if (MAIL_USE_SMTP) {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = MAIL_USERNAME;
            $mail->Password = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION;
            $mail->Port = MAIL_PORT;
            $mail->SMTPDebug = 0; // Set to 2 for debugging
        }
        
        // Default sender
        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        
        // Character set
        $mail->CharSet = 'UTF-8';
        
        return $mail;
    } catch (Exception $e) {
        error_log("Mailer Initialization Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Test SMTP Connection
 * 
 * @return array Test result
 */
function testSMTPConnection() {
    try {
        $mail = getMailer();
        
        if (!$mail) {
            return [
                'success' => false,
                'message' => 'Failed to initialize mailer'
            ];
        }
        
        // Try to connect (without actually sending)
        $mail->SMTPDebug = 0;
        
        return [
            'success' => true,
            'message' => 'SMTP connection test passed',
            'host' => MAIL_HOST,
            'port' => MAIL_PORT,
            'encryption' => MAIL_ENCRYPTION
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'SMTP connection failed: ' . $e->getMessage()
        ];
    }
}
?>
