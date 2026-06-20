<?php
/**
 * CITS LMS - InfinityFree Production Entry Point
 * 
 * This file routes requests to the appropriate module.
 * Handles: Login, Registration, Admin Panel, Student Area, Teacher Area, etc.
 * 
 * Deployment: InfinityFree (Domain Root)
 * Last Updated: 2026-06-20
 */

// ====================
// INITIALIZATION
// ====================

// Error handling - Before any output
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile:$errline");
    if (strpos($errfile, 'includes') === false) {
        header('HTTP/1.1 500 Internal Server Error');
        echo "An error occurred. Please try again later.";
    }
    return true;
});

// Load configuration
require_once __DIR__ . '/config_infinityfree.php';

// Ensure database connection is available
require_once INCLUDES_DIR . '/db.php';

// Load helper functions
require_once INCLUDES_DIR . '/functions.php';

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', COOKIE_HTTPONLY);
    ini_set('session.cookie_secure', COOKIE_SECURE);
    ini_set('session.cookie_samesite', COOKIE_SAMESITE);
    session_start();
}

// ====================
// ROUTING LOGIC
// ====================

// Get requested URL/page
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove base path if exists
if (BASE_PATH !== '') {
    $request_path = str_replace(BASE_PATH, '', $request_path);
}

// Normalize path
$request_path = trim($request_path, '/');
$request_array = explode('/', $request_path);
$page = $request_array[0] ?? '';

// Handle query string for index.php?url=xxx format (fallback)
if (empty($page) && isset($_GET['url'])) {
    $page = trim($_GET['url'], '/');
}

// ====================
// ROUTE DISPATCHER
// ====================

// Remove leading slash if present
$page = ltrim($page, '/');

// Default to index if empty
if (empty($page)) {
    $page = 'index';
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role_name'] ?? null;

// ====================
// PUBLIC PAGES (No login required)
// ====================

$public_pages = ['index', 'login', 'register', 'forgot_password', 'verify_otp', 'reset_password', 'staff_login'];

if (in_array($page, $public_pages)) {
    if ($page === 'index' && $is_logged_in) {
        // Redirect logged-in users to their dashboard
        redirectDashboard($user_role);
    }
    
    // Load public pages
    $file = __DIR__ . '/' . $page . '.php';
    if (file_exists($file)) {
        require_once $file;
        exit;
    }
}

// ====================
// PROTECTED PAGES (Login required)
// ====================

if (!$is_logged_in) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "/login.php");
    exit;
}

// ====================
// ROLE-BASED ROUTING
// ====================

switch ($page) {
    // ===== STUDENT ROUTES =====
    case 'student':
    case str_starts_with($page, 'student/') ? $page : null:
        if ($user_role !== 'student') {
            header("Location: " . BASE_URL . "/");
            exit;
        }
        $file = __DIR__ . '/student/index.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== TEACHER ROUTES =====
    case 'teacher':
    case str_starts_with($page, 'teacher/') ? $page : null:
        if (!in_array($user_role, ['teacher', 'moderator', 'admin', 'superadmin'])) {
            header("Location: " . BASE_URL . "/");
            exit;
        }
        $file = __DIR__ . '/teacher/index.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== ADMIN ROUTES =====
    case 'admin':
    case str_starts_with($page, 'admin/') ? $page : null:
        if (!in_array($user_role, ['admin', 'superadmin', 'moderator'])) {
            header("Location: " . BASE_URL . "/");
            exit;
        }
        $file = __DIR__ . '/admin/index.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== MODERATOR ROUTES =====
    case 'moderator':
    case str_starts_with($page, 'moderator/') ? $page : null:
        if (!in_array($user_role, ['moderator', 'admin', 'superadmin'])) {
            header("Location: " . BASE_URL . "/");
            exit;
        }
        $file = __DIR__ . '/moderator/index.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== COMMUNITY ROUTES =====
    case 'community':
    case str_starts_with($page, 'community/') ? $page : null:
        $file = __DIR__ . '/community/index.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== API ROUTES =====
    case 'api':
    case str_starts_with($page, 'api/') ? $page : null:
        // API endpoints
        $api_route = isset($request_array[1]) ? $request_array[1] : 'index';
        $api_file = __DIR__ . '/api/' . $api_route . '.php';
        if (file_exists($api_file)) {
            header('Content-Type: application/json');
            require_once $api_file;
        } else {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'API endpoint not found']);
        }
        exit;
    
    // ===== PROFILE & SETTINGS =====
    case 'profile':
    case 'settings':
    case 'logout':
        $file = __DIR__ . '/' . $page . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
        break;
    
    // ===== DEFAULT: ROLE-BASED DASHBOARD =====
    default:
        redirectDashboard($user_role);
        break;
}

// ====================
// 404 HANDLER
// ====================

http_response_code(404);
?>
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= BOOTSTRAP_CDN ?>" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist.</p>
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Go Home</a>
    </div>
</body>
</html>
