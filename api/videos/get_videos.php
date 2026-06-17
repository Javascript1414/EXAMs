<?php
/**
 * Video API - Get Videos
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $videos = [
        ['video_id' => 1, 'title' => 'Introduction to Calculus', 'duration' => 1200, 'views' => 2340, 'rating' => 4.8, 'instructor' => 'Dr. Johnson', 'course' => 'Mathematics', 'thumbnail' => '/assets/images/video1.jpg', 'progress' => 65],
        ['video_id' => 2, 'title' => 'Photosynthesis Explained', 'duration' => 900, 'views' => 1890, 'rating' => 4.6, 'instructor' => 'Prof. Smith', 'course' => 'Biology', 'thumbnail' => '/assets/images/video2.jpg', 'progress' => 0],
        ['video_id' => 3, 'title' => 'English Grammar Basics', 'duration' => 1500, 'views' => 3240, 'rating' => 4.9, 'instructor' => 'Ms. Brown', 'course' => 'English', 'thumbnail' => '/assets/images/video3.jpg', 'progress' => 100],
        ['video_id' => 4, 'title' => 'Python Programming 101', 'duration' => 2400, 'views' => 5120, 'rating' => 4.7, 'instructor' => 'Mr. Davis', 'course' => 'Computer Science', 'thumbnail' => '/assets/images/video4.jpg', 'progress' => 45],
        ['video_id' => 5, 'title' => 'World History: Medieval Period', 'duration' => 1800, 'views' => 1560, 'rating' => 4.5, 'instructor' => 'Prof. Wilson', 'course' => 'History', 'thumbnail' => '/assets/images/video5.jpg', 'progress' => 0]
    ];
    
    echo json_encode(['success' => true, 'videos' => $videos]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
