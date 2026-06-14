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
define('DB_HOST', '127.0.0.1:3307');
define('DB_NAME', 'exams_lms');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('BASE_URL', 'http://localhost/EXAMs');
define('APP_NAME', 'CITS LMS');
define('ENVIRONMENT', 'development'); // Set to 'production' to hide errors