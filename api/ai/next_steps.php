<?php
/**
 * AI Recommendations API - Next Steps
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get completed courses
    $stmt = $pdo->prepare("
        SELECT DISTINCT course_id FROM user_course_enrollment 
        WHERE user_id = ? AND progress = 100
    ");
    $stmt->execute([$user_id]);
    $completed = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Recommend next courses
    $recommendations = [
        ['step' => 1, 'title' => 'Master Advanced Mathematics', 'difficulty' => 'Advanced', 'duration' => '12 weeks', 'progress' => 0],
        ['step' => 2, 'title' => 'Physics Fundamentals', 'difficulty' => 'Intermediate', 'duration' => '8 weeks', 'progress' => 0],
        ['step' => 3, 'title' => 'Data Science Basics', 'difficulty' => 'Advanced', 'duration' => '10 weeks', 'progress' => 0],
        ['step' => 4, 'title' => 'Chemistry Lab Techniques', 'difficulty' => 'Intermediate', 'duration' => '6 weeks', 'progress' => 0],
        ['step' => 5, 'title' => 'Advanced Programming', 'difficulty' => 'Expert', 'duration' => '14 weeks', 'progress' => 0]
    ];
    
    echo json_encode(['success' => true, 'next_steps' => $recommendations]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
