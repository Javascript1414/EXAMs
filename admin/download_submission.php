<?php
/**
 * Download Student Practical Submission File
 * Admins and teachers can download submitted files
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check access
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access Denied');
}

$role = $_SESSION['role_name'] ?? '';
if (!in_array($role, ['admin', 'superadmin', 'teacher', 'moderator'])) {
    http_response_code(403);
    die('Access Denied - Admin/Teacher Only');
}

// Get filename from request (extract just the filename to prevent directory traversal)
$file_param = $_GET['file'] ?? '';

if (!$file_param) {
    http_response_code(400);
    die('No file specified');
}

// Extract only the filename (remove any path components)
$filename_only = basename($file_param);

// Construct the full file path from script location
$file_path = __DIR__ . '/../uploads/practical_submissions/' . $filename_only;

// Normalize the path
$file_path = realpath($file_path);
$allowed_dir = realpath(__DIR__ . '/../uploads/practical_submissions');

// Validate the file
if (!$file_path || !file_exists($file_path) || !$allowed_dir || strpos($file_path, $allowed_dir) !== 0) {
    http_response_code(404);
    error_log("Download failed - File: {$file_param}, Resolved: {$file_path}, Allowed: {$allowed_dir}");
    die('File not found or access denied');
}

// Get file info
$filename = basename($file_path);
$filesize = filesize($file_path);

// Set headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Read and output file
readfile($file_path);
exit;
