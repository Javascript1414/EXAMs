<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Log the logout activity
if (isLoggedIn()) {
    try {
        $userId = $_SESSION['user_id'];
        
        // Log the logout event
        $stmt = $pdo->prepare("INSERT INTO login_logs (user_id, action, ip_address, user_agent, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $userId,
            'logout',
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        // Log error silently
        error_log("Logout logging failed: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

redirect('/login.php');
?>
