<?php
/**
 * PDF Viewer/Server Endpoint
 * Serves PDF files with proper headers and MIME type for inline viewing
 * 
 * CRITICAL: Called by iframes in JavaScript, needs to handle:
 * - Long running connections (don't timeout)
 * - Large file sizes (proper streaming)
 * - No JavaScript execution in PDFs
 * - Browser cache headers
 */

// Disable error display to prevent output after headers sent
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Don't limit script execution time for PDF serving
set_time_limit(0);

try {
    // SECURITY: Require user to be logged in (return error without redirecting for API)
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get file path from request
    $file_path = isset($_GET['file']) ? (string)$_GET['file'] : '';
    
    if (empty($file_path)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No file specified']);
        exit;
    }
    
    // Sanitize path to prevent directory traversal
    // Remove ../ sequences that could escape the uploads directory
    $file_path = str_replace('\\', '/', $file_path); // Normalize slashes
    $file_path = preg_replace('/\.\.\//', '', $file_path); // Remove ../
    $file_path = preg_replace('/\.\.\\\\/', '', $file_path); // Remove ..\
    
    // Build full path
    $full_path = __DIR__ . '/../' . $file_path;
    
    // Resolve real path (eliminates symlinks and ../ tricks)
    $real_path = @realpath($full_path);
    if ($real_path === false) {
        // realpath fails if file doesn't exist - try without it
        if (!file_exists($full_path)) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'File not found']);
            exit;
        }
        $real_path = $full_path;
    }
    
    // Security check: ensure file is within uploads directory
    $uploads_base = realpath(__DIR__ . '/../uploads');
    if (!$uploads_base) {
        throw new Exception('Uploads directory not found');
    }
    
    // Normalize paths for comparison (Windows & Unix compatible)
    $real_path_normalized = str_replace('\\', '/', $real_path);
    $uploads_base_normalized = str_replace('\\', '/', $uploads_base);
    
    if (strpos($real_path_normalized, $uploads_base_normalized) !== 0) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Access denied - file outside allowed directory']);
        exit;
    }
    
    // Check if file exists and is readable
    if (!file_exists($real_path)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    if (!is_file($real_path)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Path is not a file']);
        exit;
    }
    
    if (!is_readable($real_path)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File is not readable']);
        exit;
    }
    
    // Get file info - use multiple methods to ensure we get correct size
    $file_size = false;
    
    // Method 1: filesize()
    $file_size = @filesize($real_path);
    
    // Method 2: fstat() if filesize() failed
    if ($file_size === false) {
        $fp = @fopen($real_path, 'rb');
        if ($fp) {
            $stat = @fstat($fp);
            if ($stat && isset($stat['size'])) {
                $file_size = $stat['size'];
            }
            @fclose($fp);
        }
    }
    
    // Method 3: stat() if fstat() failed  
    if ($file_size === false) {
        $stat = @stat($real_path);
        if ($stat && isset($stat['size'])) {
            $file_size = $stat['size'];
        }
    }
    
    // Log the file size result
    if ($file_size !== false && $file_size > 0) {
        error_log("[PDF_SERVE] File size: $file_size bytes");
    } else {
        error_log("[PDF_SERVE_WARNING] Could not determine file size, will use chunked transfer");
        $file_size = false;
    }
    
    $file_name = basename($real_path);
    
    // Detect MIME type
    $mime_type = 'application/pdf'; // Default to PDF
    
    if (function_exists('mime_content_type')) {
        $detected_mime = @mime_content_type($real_path);
        if ($detected_mime && $detected_mime !== 'application/octet-stream') {
            $mime_type = $detected_mime;
        }
    } else if (function_exists('finfo_file')) {
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected_mime = @finfo_file($finfo, $real_path);
            if ($detected_mime && $detected_mime !== 'application/octet-stream') {
                $mime_type = $detected_mime;
            }
            @finfo_close($finfo);
        }
    }
    
    // Ensure PDF MIME type for PDF files
    $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        $mime_type = 'application/pdf';
    }
    
    // Log the access for debugging
    error_log("[PDF_SERVE] File: $file_path | Real: $real_path | Size: $file_size | MIME: $mime_type");
    
    // Clear any buffered output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for inline viewing (not download)
    header('Content-Type: ' . $mime_type, true);
    
    // Only send Content-Length if we successfully determined the file size
    // Some browsers can have issues if Content-Length is wrong
    if ($file_size !== false && $file_size > 0) {
        header('Content-Length: ' . $file_size, true);
    } else {
        // Don't send Content-Length - let browser handle chunked transfer
        error_log("[PDF_SERVE] Skipping Content-Length header (will use chunked encoding)");
    }
    
    header('Content-Disposition: inline; filename="' . addslashes($file_name) . '"', true);
    header('Accept-Ranges: bytes', true);
    
    // Cache control
    header('Cache-Control: private, must-revalidate, max-age=3600', true);
    header('Pragma: private', true);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT', true);
    
    // CORS headers to allow loading from iframes
    header('X-Content-Type-Options: nosniff', true);
    // X-Frame-Options removed to allow PDF display in iframes
    
    // Disable any caching at the browser level for safety
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true);
    
    // Send the file using readfile() - most efficient for large files
    $bytes_sent = @readfile($real_path);
    
    if ($bytes_sent === false) {
        error_log("[PDF_SERVE_ERROR] Failed to read file: $real_path");
    } else {
        error_log("[PDF_SERVE_SUCCESS] Sent $bytes_sent bytes");
    }
    
    exit;
    
} catch (Exception $e) {
    // Log the error
    error_log("[PDF_SERVE_EXCEPTION] " . $e->getMessage());
    
    // Send error response only if headers not sent
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}
