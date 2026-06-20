<?php
/**
 * Video API - Get Videos
 * Filters by student's assigned trade
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

requireLogin();

try {
    // Get student's assigned trade
    $student_stmt = $pdo->prepare("SELECT trade_id FROM users WHERE id = ?");
    $student_stmt->execute([$_SESSION['user_id']]);
    $student = $student_stmt->fetch();
    
    if (!$student || !$student['trade_id']) {
        echo json_encode([
            'success' => false,
            'message' => 'No trade assigned to this student'
        ]);
        exit;
    }
    
    $student_trade_id = $student['trade_id'];
    
    // Get videos from courses that belong to the student's trade
    // First, check if courses table has trade_id column
    $check_trade_col = $pdo->query("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME='courses' AND COLUMN_NAME='trade_id'
    ")->fetch();
    
    if ($check_trade_col) {
        // Courses table has trade_id - use it for filtering
        $stmt = $pdo->prepare("
            SELECT 
                v.id as video_id,
                v.video_name as title,
                v.description,
                v.video_file,
                v.duration,
                v.views,
                v.rating,
                v.total_ratings,
                v.created_at,
                c.course_name as course,
                c.trade_id,
                u.full_name as instructor,
                COALESCE(wh.watch_percentage, 0) as progress
            FROM videos v
            LEFT JOIN courses c ON v.course_id = c.course_id
            LEFT JOIN users u ON v.instructor_id = u.id
            LEFT JOIN video_watch_history wh ON v.id = wh.video_id AND wh.student_id = ?
            WHERE v.status = 'active' AND c.trade_id = ?
            ORDER BY v.created_at DESC
            LIMIT 20
        ");
        
        $stmt->execute([$_SESSION['user_id'], $student_trade_id]);
    } else {
        // Fallback: if courses don't have trade_id, get all videos
        // (This will be used until courses table is updated)
        $stmt = $pdo->prepare("
            SELECT 
                v.id as video_id,
                v.video_name as title,
                v.description,
                v.video_file,
                v.duration,
                v.views,
                v.rating,
                v.total_ratings,
                v.created_at,
                c.course_name as course,
                u.full_name as instructor,
                COALESCE(wh.watch_percentage, 0) as progress
            FROM videos v
            LEFT JOIN courses c ON v.course_id = c.course_id
            LEFT JOIN users u ON v.instructor_id = u.id
            LEFT JOIN video_watch_history wh ON v.id = wh.video_id AND wh.student_id = ?
            WHERE v.status = 'active'
            ORDER BY v.created_at DESC
            LIMIT 20
        ");
        
        $stmt->execute([$_SESSION['user_id']]);
    }
    
    $videos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'videos' => $videos
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch videos: ' . $e->getMessage()
    ]);
}
