<?php
/**
 * Timeline API - Get Course Progress
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    $query = "
        SELECT 
            c.course_id,
            c.course_name as name,
            COALESCE(uce.progress, 0) as progress,
            (SELECT COUNT(*) FROM course_lessons WHERE course_id = c.course_id) as total_lessons,
            COALESCE((SELECT COUNT(*) FROM course_lessons cl 
                     WHERE cl.course_id = c.course_id 
                     AND cl.lesson_id IN (
                         SELECT lesson_id FROM user_lesson_progress 
                         WHERE user_id = ? AND completed = 1
                     )), 0) as completed_lessons
        FROM courses c
        LEFT JOIN user_course_enrollment uce ON c.course_id = uce.course_id AND uce.user_id = ?
        WHERE uce.user_id = ? OR c.is_public = 1
        ORDER BY uce.progress DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $user_id, $user_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'courses' => $courses
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
