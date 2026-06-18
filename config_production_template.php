<?php
/**
 * Production Configuration Template
 * Copy this over config.php when deploying to production
 */

// ============================================
// ⚠️  BEFORE GOING LIVE - UPDATE THESE VALUES:
// ============================================

// Start Session Securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);  // ⬅ ENABLE FOR HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Database Credentials - UPDATE FOR PRODUCTION
define('DB_HOST', 'your-production-host.com:3306');  // ⬅ UPDATE
define('DB_NAME', 'exams_lms');
define('DB_USER', 'db_production_user');  // ⬅ UPDATE - Use different user than root
define('DB_PASS', 'strong_password_here');  // ⬅ UPDATE - Use strong password

// Timezone Configuration
date_default_timezone_set('Asia/Kolkata');

// Application Settings - UPDATE FOR PRODUCTION
define('BASE_URL', 'https://yourdomain.com');  // ⬅ UPDATE - Use HTTPS
define('APP_NAME', 'CITS LMS');
define('ENVIRONMENT', 'production');  // ⬅ CHANGE from 'development' to 'production'

// Error Handling - DISABLE ERRORS DISPLAY IN PRODUCTION
if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/var/log/php-errors.log');  // ⬅ Configure error logging
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Email Configuration - UPDATE FOR PRODUCTION
// Option 1: Gmail (Production)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-production-email@gmail.com');  // ⬅ UPDATE
define('SMTP_PASS', 'your-app-specific-password');  // ⬅ UPDATE
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');  // ⬅ UPDATE
define('SMTP_FROM_NAME', 'CITS LMS - Your Organization');  // ⬅ UPDATE

// Option 2: SendGrid or AWS SES (recommended for production)
// define('SMTP_HOST', 'smtp.sendgrid.net');
// define('SMTP_PORT', 587);
// define('SMTP_USER', 'apikey');
// define('SMTP_PASS', 'SG.your_sendgrid_api_key');

// Security Settings
define('SESSION_TIMEOUT', 1800);  // 30 minutes
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('UPLOAD_MAX_SIZE', 10485760);  // 10 MB in bytes

// SSL Certificate Path (if using self-signed in development)
// define('CURL_CERTIFICATE_VERIFY', '/path/to/ca-bundle.crt');

// ============================================
// ✅ DEPLOYMENT CHECKLIST:
// ============================================
/*
 * 1. DATABASE:
 *    ☐ Create production database user (not root)
 *    ☐ Set strong password for database user
 *    ☐ Run all migrations: run_migration_*.php
 *    ☐ Backup database
 *    ☐ Test database connection
 *    ☐ Verify foreign key constraints are enabled
 * 
 * 2. CONFIGURATION:
 *    ☐ Update DB_HOST to production server
 *    ☐ Update DB_USER and DB_PASS
 *    ☐ Update BASE_URL to production domain
 *    ☐ Change ENVIRONMENT to 'production'
 *    ☐ Configure SMTP for email delivery
 *    ☐ Set up error logging
 * 
 * 3. PHP EXTENSIONS:
 *    ☐ Verify GD extension is enabled (images)
 *    ☐ Verify ZIP extension is enabled (certificates)
 *    ☐ Verify PDO_MySQL is available
 *    ☐ Verify CURL is available
 * 
 * 4. FILE PERMISSIONS:
 *    ☐ Create all upload directories
 *    ☐ Set 755 permissions on directories
 *    ☐ Set 644 permissions on files
 *    ☐ Verify php.ini upload_tmp_dir is writable
 *    ☐ Verify max_upload_size is sufficient
 * 
 * 5. SECURITY:
 *    ☐ Enable HTTPS/SSL certificate
 *    ☐ Configure firewall rules
 *    ☐ Set up WAF (Web Application Firewall)
 *    ☐ Enable CORS restrictions if needed
 *    ☐ Configure database backup schedule
 *    ☐ Set up monitoring and alerting
 * 
 * 6. TESTING:
 *    ☐ Test user registration
 *    ☐ Test user login
 *    ☐ Test exam attempt flow
 *    ☐ Test certificate generation
 *    ☐ Test file uploads
 *    ☐ Test email notifications
 *    ☐ Test payment gateway (if applicable)
 *    ☐ Test admin dashboard
 *    ☐ Load testing with multiple concurrent users
 * 
 * 7. MONITORING:
 *    ☐ Set up application logging
 *    ☐ Set up database monitoring
 *    ☐ Set up server monitoring (CPU, RAM, Disk)
 *    ☐ Configure alerting for errors and performance
 *    ☐ Set up daily backup verification
 * 
 * 8. DOCUMENTATION:
 *    ☐ Document all configuration changes
 *    ☐ Document database structure
 *    ☐ Document backup procedures
 *    ☐ Document recovery procedures
 *    ☐ Create runbook for common issues
 */
?>
