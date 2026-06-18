<?php
require_once 'config.php';
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT id, video_name, video_file, course_id, status FROM videos LIMIT 15");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total Videos: " . count($videos) . "\n\n";
    foreach ($videos as $v) {
        echo "ID: " . $v['id'] . " | Name: " . $v['video_name'] . " | File: " . $v['video_file'] . " | Status: " . $v['status'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
