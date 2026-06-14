<?php
require_once __DIR__ . '/../config.php';

/**
 * Sanitizes user input to prevent XSS.
 */
function sanitizeInput(string $data): string {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to a specified path within the application.
 */
function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Generates a CSRF token for form submission.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token from a form submission.
 */
function verifyCsrfToken(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Checks if a user is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 */
function hasRole(string $role_name): bool {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

/**
 * Forces authentication. Redirects to login if not logged in.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        redirect('/login.php');
    }
}

/**
 * Protects a route by requiring a specific role.
 */
function requireRole(string $role_name): void {
    requireLogin();
    if (!hasRole($role_name)) {
        redirectDashboard($_SESSION['role_name'] ?? 'student');
    }
}

/**
 * Routes the user to their correct role-based dashboard.
 */
function redirectDashboard(string $role_name): void {
    match ($role_name) {
        'superadmin', 'admin' => redirect('/admin/index.php'),
        'moderator'           => redirect('/moderator/index.php'),
        'student'             => redirect('/student/index.php'),
        default               => redirect('/login.php')
    };
}

/**
 * Displays flash messages (success or error).
 */
function displayFlashMessages(): void {
    if (isset($_SESSION['error_message'])) {
        echo '<div style="color: #EF4444; background: #FEE2E2; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
        echo sanitizeInput($_SESSION['error_message']);
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        echo '<div style="color: #10B981; background: #D1FAE5; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
        echo sanitizeInput($_SESSION['success_message']);
        echo '</div>';
        unset($_SESSION['success_message']);
    }
}

/**
 * Returns a relative time string (e.g., "2 hours ago").
 */
function timeElapsedString(string $datetime, bool $full = false): string {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
        else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>