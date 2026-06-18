<?php
/**
 * PDF File Verification Endpoint
 * Used to check if PDF file exists and is accessible before trying to load in iframe
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    // For API endpoints, check if user is logged in but return JSON errors instead of redirecting
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Unauthorized - please log in'
        ]);
        exit;
    }
    
    // Get file path from request
    $file_path = $_GET['file'] ?? '';
    
    if (empty($file_path)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No file path provided'
        ]);
        exit;
    }
    
    // Sanitize path to prevent directory traversal
    $file_path = preg_replace('/\.\.\//', '', $file_path); // Remove ../
    $file_path = preg_replace('/\.\.\\\\/', '', $file_path); // Also remove ..\ for Windows
    
    $full_path = __DIR__ . '/../' . $file_path;
    $full_path = realpath($full_path); // Get real path
    
    // Security check: ensure file is within uploads directory
    // Normalize both paths for consistent comparison (handle Windows path separators)
    $uploads_base = realpath(__DIR__ . '/../uploads');
    
    if ($full_path === false || $uploads_base === false) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied - invalid path',
            'file_path' => $file_path
        ]);
        exit;
    }
    
    // Normalize paths: convert backslashes to forward slashes for consistent comparison
    $full_path_normalized = str_replace('\\', '/', $full_path);
    $uploads_base_normalized = str_replace('\\', '/', $uploads_base);
    
    // Ensure uploads_base ends with / for proper prefix matching
    if (substr($uploads_base_normalized, -1) !== '/') {
        $uploads_base_normalized .= '/';
    }
    
    // Check if file is within uploads directory
    if (strpos($full_path_normalized, $uploads_base_normalized) !== 0) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied - file outside allowed directory',
            'file_path' => $file_path
        ]);
        exit;
    }
    
    // Check if file exists
    if (!file_exists($full_path)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'PDF file not found',
            'file_path' => $file_path,
            'full_path' => $full_path
        ]);
        exit;
    }
    
    // Check if file is readable
    if (!is_readable($full_path)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'PDF file is not readable',
            'file_path' => $file_path,
            'full_path' => $full_path,
            'permissions' => substr(sprintf('%o', fileperms($full_path)), -4)
        ]);
        exit;
    }
    
    // Check if it's a PDF file
    $mime = mime_content_type($full_path);
    if ($mime !== 'application/pdf' && pathinfo($full_path, PATHINFO_EXTENSION) !== 'pdf') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'File is not a valid PDF',
            'mime_type' => $mime,
            'extension' => pathinfo($full_path, PATHINFO_EXTENSION)
        ]);
        exit;
    }
    
    // File is valid - get file size using multiple methods
    $file_size = false;
    
    // Method 1: filesize()
    $file_size = @filesize($full_path);
    
    // Method 2: fstat()
    if ($file_size === false || $file_size <= 0) {
        $fp = @fopen($full_path, 'rb');
        if ($fp) {
            $stat = @fstat($fp);
            if ($stat && isset($stat['size']) && $stat['size'] > 0) {
                $file_size = $stat['size'];
            }
            @fclose($fp);
        }
    }
    
    // Method 3: stat()
    if ($file_size === false || $file_size <= 0) {
        $stat = @stat($full_path);
        if ($stat && isset($stat['size']) && $stat['size'] > 0) {
            $file_size = $stat['size'];
        }
    }
    
    // Fallback
    if ($file_size === false || $file_size <= 0) {
        $file_size = 0;
    }
    
    // File is valid
    echo json_encode([
        'success' => true,
        'file_path' => $file_path,
        'full_path' => $full_path,
        'size' => $file_size,
        'mime_type' => $mime,
        'readable' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
