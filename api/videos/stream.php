<?php
/**
 * Video Streaming Server - HTTP Range Request Support (Like YouTube)
 * Handles smooth streaming with buffering optimization
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require login
requireLogin();

// Get video ID and quality from request
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$quality = isset($_GET['quality']) ? sanitize($_GET['quality']) : 'auto';

if (!$video_id) {
    http_response_code(404);
    die('Video not found');
}

try {
    // Verify user has access to this video (student role check)
    $stmt = $pdo->prepare("
        SELECT v.id, v.video_file, v.duration
        FROM videos v
        WHERE v.id = ? AND v.status = 'active'
    ");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        die('Video not found');
    }
    
    // Get video file path
    $videoFile = __DIR__ . '/../../uploads/videos/' . $video['video_file'];
    
    // Check if file exists
    if (!file_exists($videoFile) || !is_readable($videoFile)) {
        http_response_code(404);
        die('Video file not found');
    }
    
    // Get file size
    $fileSize = filesize($videoFile);
    
    // Set response headers for streaming
    header('Content-Type: video/mp4');
    header('Accept-Ranges: bytes');
    header('Cache-Control: max-age=86400, public'); // 24 hour cache
    header('Last-Modified: ' . gmdate('r', filemtime($videoFile)));
    header('ETag: "' . md5_file($videoFile) . '"');
    
    // Handle HTTP Range Requests (crucial for seeking)
    $start = 0;
    $end = $fileSize - 1;
    $statusCode = 200;
    
    if (isset($_SERVER['HTTP_RANGE'])) {
        if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches)) {
            $start = intval($matches[1]);
            $end = $matches[2] !== '' ? intval($matches[2]) : $fileSize - 1;
            
            // Validate range
            if ($start > $end || $end >= $fileSize || $start < 0) {
                http_response_code(416);
                header('Content-Range: bytes */' . $fileSize);
                die();
            }
            
            $statusCode = 206; // Partial Content
        }
    }
    
    // Send range headers
    header('HTTP/1.1 ' . $statusCode . ' ' . ($statusCode == 206 ? 'Partial Content' : 'OK'));
    
    if ($statusCode == 206) {
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
    }
    
    // Calculate content length
    $length = $end - $start + 1;
    header('Content-Length: ' . $length);
    
    // Disable buffering for better streaming
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', 1);
    }
    
    ini_set('zlib.output_compression', 0);
    
    // Output video chunk with buffer optimization
    $bufferSize = 1024 * 256; // 256KB buffer chunks
    
    $file = fopen($videoFile, 'rb');
    fseek($file, $start);
    
    $bytesRead = 0;
    while ($bytesRead < $length && !feof($file)) {
        $chunk = min($bufferSize, $length - $bytesRead);
        echo fread($file, $chunk);
        $bytesRead += $chunk;
        
        // Allow connection abort checking
        if (connection_aborted()) {
            break;
        }
        
        // Flush output periodically (every 1MB)
        if ($bytesRead % (1024 * 1024) == 0) {
            flush();
        }
    }
    
    fclose($file);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Error: ' . $e->getMessage());
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input));
}
