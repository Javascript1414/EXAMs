<?php
/**
 * Teacher: Practical Exams Management
 * Redirects to admin practical exams page (shared interface)
 */

require_once '../config.php';

// Check if user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied - Teachers Only');
}

// Include the admin practical exams page (shared interface)
require_once '../admin/practical_exams.php';
?>
