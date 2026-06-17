<?php
/**
 * Timeline API - Get Timeline Events
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get timeline events (courses started, completed, exams taken, materials reviewed)
    $query = "
        SELECT 
            'course' as event_type,
            c.course_name as title,
            CONCAT('Started ', c.course_name) as description,
            c.created_at as date,
            'in-progress' as status,
            NULL as score
        FROM user_course_enrollment uce
        JOIN courses c ON uce.course_id = c.course_id
        WHERE uce.user_id = ?
        
        UNION
        
        SELECT 
            'exam' as event_type,
            e.exam_name as title,
            CONCAT('Completed ', e.exam_name, ' exam') as description,
            MAX(ea.created_at) as date,
            'completed' as status,
            ROUND(AVG(er.marks), 0) as score
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.exam_id
        JOIN exam_results er ON ea.attempt_id = er.attempt_id
        WHERE ea.user_id = ?
        GROUP BY e.exam_id
        
        ORDER BY date DESC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id, $user_id]);
    $timeline = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'timeline' => $timeline
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
