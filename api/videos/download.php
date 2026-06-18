<?php
/**
 * API - Download Video
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

$videoId = intval($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$videoId) {
    http_response_code(404);
    die('Video not found');
}

try {
    // Get video details
    $stmt = $pdo->prepare("
        SELECT v.id, v.video_name, v.video_file, v.status
        FROM videos v
        WHERE v.id = ? AND v.status = 'active'
    ");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch();
    
    if (!$video) {
        http_response_code(404);
        die('Video not found');
    }
    
    // Check if user has permission (student role)
    if ($_SESSION['role'] !== 'student') {
        http_response_code(403);
        die('Access denied');
    }
    
    // Get file path
    $filePath = __DIR__ . '/../../uploads/videos/' . $video['video_file'];
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        die('Video file not found');
    }
    
    // Log download
    $logStmt = $pdo->prepare("
        INSERT INTO video_downloads (video_id, student_id, downloaded_at)
        VALUES (?, ?, NOW())
    ");
    $logStmt->execute([$videoId, $userId]);
    
    // Set headers for download
    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . basename($video['video_name']) . '.mp4"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Stream file in chunks
    $chunkSize = 1024 * 256; // 256KB chunks
    $file = fopen($filePath, 'rb');
    
    while (!feof($file)) {
        echo fread($file, $chunkSize);
        flush();
        
        if (connection_aborted()) {
            break;
        }
    }
    
    fclose($file);
    exit;
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Error downloading video');
}
