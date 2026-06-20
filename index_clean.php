<?php
/**
 * Main Entry Point Router
 * Directs users to appropriate page based on login status
 * 
 * - Logged in users → Redirects to their dashboard
 * - Non-logged in users → Redirects to /index/ (which serves /index/index.php)
 */

session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_name'])) {
    header("Location: /" . $_SESSION['role_name'] . "/index.php");
    exit;
}

// If not logged in, redirect to main landing page (/index/index.php)
// Using 301 permanent redirect for SEO - tells search engines this is the canonical landing page
header("Location: /index/", true, 301);
exit;
