<?php
/**
 * Global Configuration File
 */

// Start Session Securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Database Credentials
define('DB_HOST', 'localhost:3307');
define('DB_NAME', 'exams_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Timezone Configuration
date_default_timezone_set('Asia/Kolkata'); // Set to your server timezone

// Application Settings
define('BASE_URL', 'http://localhost/EXAMs');
define('APP_NAME', 'CITS LMS');
define('ENVIRONMENT', 'development'); // Set to 'production' to hide errors

// Email Configuration (SMTP)
// Option 1: Gmail (Change to your details)
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'soumyajitsantra699@gmail.com');
// define('SMTP_PASS', 'your-app-password');
// define('SMTP_FROM_EMAIL', 'noreply@exams.local');
// define('SMTP_FROM_NAME', 'EXAMs Learning System');

// Option 2: Mailtrap (For Testing - uncomment to use)
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 587);
define('SMTP_USER', '5644d2f3f1f4c9');
define('SMTP_PASS', 'b4fbb80cc6c5ba');
define('SMTP_FROM_EMAIL', 'hello@example.com');
define('SMTP_FROM_NAME', 'EXAMs System');