<?php
/**
 * AI Recommendations API - Learning Statistics
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get learning statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT attempt_id) as total_attempts,
            ROUND(AVG(marks), 2) as avg_score,
            MAX(marks) as best_score,
            MIN(marks) as worst_score
        FROM exam_attempts ea
        LEFT JOIN exam_results er ON ea.attempt_id = er.attempt_id
        WHERE ea.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $learning_score = $stats['avg_score'] ?? 0;
    $total_attempts = $stats['total_attempts'] ?? 0;
    
    // Calculate learning streak
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(created_at)) as days
        FROM exam_attempts
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$user_id]);
    $streak = $stmt->fetch(PDO::FETCH_ASSOC)['days'] ?? 0;
    
    $response = [
        'success' => true,
        'learning_score' => (float)$learning_score,
        'total_attempts' => (int)$total_attempts,
        'best_score' => (float)($stats['best_score'] ?? 0),
        'worst_score' => (float)($stats['worst_score'] ?? 0),
        'learning_streak' => (int)$streak,
        'next_actions' => [
            ['icon' => 'book-open', 'title' => 'Review Weak Topics', 'description' => 'Practice on topics below 70%'],
            ['icon' => 'target', 'title' => 'Take Practice Tests', 'description' => 'Improve your exam readiness'],
            ['icon' => 'users', 'title' => 'Join Study Groups', 'description' => 'Learn with other students'],
            ['icon' => 'award', 'title' => 'Earn Badges', 'description' => 'Complete achievements']
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
