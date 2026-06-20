<?php
/**
 * INFINITYFREE DEPLOYMENT CONFIGURATION
 * 
 * This file sets up environment-specific configurations for InfinityFree hosting.
 * Last Updated: 2026-06-20
 */

// Detect current environment
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// InfinityFree domain configuration
$infinityFreeConfig = [
    'domains' => [
        // Replace with your actual domain(s)
        'yourdomain.infinityfree.com',
        'yourdomain.com',
        'www.yourdomain.com',
    ],
    'environment' => 'production',
    'php_version' => '8.0', // Verify with InfinityFree control panel
];

// =========================
// ENVIRONMENT DETECTION
// =========================

// Detect if running on InfinityFree
function isInfinityFreeEnvironment() {
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $server_addr = $_SERVER['SERVER_ADDR'] ?? '';
    
    // Check for common InfinityFree indicators
    if (strpos($server_name, 'infinityfree') !== false ||
        strpos($server_name, 'ifastnet') !== false ||
        strpos($server_name, '.byethost') !== false) {
        return true;
    }
    
    // Check hostname
    $hostname = gethostname();
    if (strpos($hostname, 'infinity') !== false) {
        return true;
    }
    
    return false;
}

// =========================
// DATABASE CONFIGURATION
// =========================

if (isInfinityFreeEnvironment()) {
    // InfinityFree Database Settings
    // UPDATE THESE WITH YOUR INFINITYFREE CREDENTIALS
    define('DB_HOST', 'sql123.infinityfree.com'); // Replace with InfinityFree MySQL host
    define('DB_NAME', 'if0_37654321_exams_lms'); // InfinityFree format: if0_USERID_dbname
    define('DB_USER', 'if0_37654321'); // InfinityFree username (usually if0_USERID)
    define('DB_PASS', 'PASSWORD_HERE'); // Set from InfinityFree cpanel
} else {
    // Local/Development Database Settings
    define('DB_HOST', '127.0.0.1:3307');
    define('DB_NAME', 'exams_lms');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// =========================
// URL & PATH CONFIGURATION
// =========================

// Detect if we're in a subdirectory
$base_path = dirname($_SERVER['SCRIPT_NAME']);
if ($base_path === '/') {
    $base_path = '';
}

// Set BASE_URL based on environment
if (isInfinityFreeEnvironment()) {
    // InfinityFree: Domain root access
    define('BASE_URL', $protocol . $domain);
    define('BASE_PATH', '');
} else {
    // Local development
    define('BASE_URL', 'http://localhost/EXAMs');
    define('BASE_PATH', '/EXAMs');
}

// Application Settings
define('APP_NAME', 'CITS LMS');
define('ENVIRONMENT', isInfinityFreeEnvironment() ? 'production' : 'development');

// Timezone Configuration
date_default_timezone_set('Asia/Kolkata');

// =========================
// FILE PATHS (Absolute)
// =========================

define('APP_ROOT', __DIR__);
define('INCLUDES_DIR', APP_ROOT . '/includes');
define('ASSETS_DIR', APP_ROOT . '/assets');
define('UPLOADS_DIR', APP_ROOT . '/uploads');
define('VENDOR_DIR', APP_ROOT . '/vendor');
define('MIGRATIONS_DIR', APP_ROOT . '/migrations');

// =========================
// ASSET PATHS (Relative URLs)
// =========================

define('CSS_URL', BASE_URL . '/assets/css');
define('JS_URL', BASE_URL . '/assets/js');
define('IMAGES_URL', BASE_URL . '/assets/images');
define('UPLOADS_URL', BASE_URL . '/uploads');

// =========================
// EMAIL CONFIGURATION
// =========================

if (ENVIRONMENT === 'production') {
    // Production Email Settings (InfinityFree)
    define('SMTP_HOST', 'smtp.gmail.com'); // or your email provider
    define('SMTP_PORT', 587);
    define('SMTP_USER', 'your-email@gmail.com');
    define('SMTP_PASS', 'your-app-password');
    define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
    define('SMTP_FROM_NAME', 'CITS LMS');
} else {
    // Development Email Settings
    define('SMTP_HOST', 'smtp.mailtrap.io');
    define('SMTP_PORT', 587);
    define('SMTP_USER', '5644d2f3f1f4c9');
    define('SMTP_PASS', 'b4fbb80cc6c5ba');
    define('SMTP_FROM_EMAIL', 'hello@example.com');
    define('SMTP_FROM_NAME', 'EXAMs System');
}

// =========================
// SECURITY SETTINGS
// =========================

// CORS Settings
define('ALLOWED_ORIGINS', [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
]);

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('COOKIE_SECURE', ENVIRONMENT === 'production'); // Only send over HTTPS in production
define('COOKIE_HTTPONLY', true);
define('COOKIE_SAMESITE', 'Lax');

// =========================
// PERFORMANCE & LIMITS
// =========================

define('MAX_UPLOAD_SIZE', 50 * 1024 * 1024); // 50MB (check InfinityFree limits)
define('ALLOWED_UPLOAD_TYPES', [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'exe', 'pkt'
]);

define('PAGINATION_ITEMS', 10);
define('CACHE_ENABLED', ENVIRONMENT === 'production');
define('CACHE_TTL', 3600);

// =========================
// DEBUG & ERROR HANDLING
// =========================

if (ENVIRONMENT === 'production') {
    // Production - Hide errors
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', APP_ROOT . '/logs/errors.log');
} else {
    // Development - Show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// =========================
// INCLUDE GUARD & VALIDATION
// =========================

if (!function_exists('isValidRequest')) {
    function isValidRequest() {
        return !empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI']);
    }
}

// Verify configuration loaded
if (!defined('BASE_URL')) {
    die('Configuration failed to load.');
}

// InfinityFree Ready
define('INFINITYFREE_READY', true);
?>
