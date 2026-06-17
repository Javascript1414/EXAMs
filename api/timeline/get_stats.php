<?php
/**
 * Timeline API - Get Statistics
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Total courses
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT course_id) as count FROM user_course_enrollment WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Completed courses (100% progress)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT course_id) as count FROM user_course_enrollment WHERE user_id = ? AND progress = 100");
    $stmt->execute([$user_id]);
    $completed_courses = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // In progress courses
    $in_progress = $total_courses - $completed_courses;
    
    // Learning streak (days of consecutive learning activity)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(created_at)) as streak
        FROM exam_attempts
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$user_id]);
    $learning_streak = $stmt->fetch(PDO::FETCH_ASSOC)['streak'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'total_courses' => (int)$total_courses,
        'completed_courses' => (int)$completed_courses,
        'in_progress_courses' => (int)$in_progress,
        'learning_streak' => (int)$learning_streak
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
