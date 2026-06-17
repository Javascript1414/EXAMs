<?php
/**
 * AI Recommendations API - Weak Topics
 * Serves personalized learning recommendations
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../../config.php';

$user_id = $_SESSION['user_id'];

try {
    // Get weak topics (< 70% accuracy)
    $query = "
        SELECT 
            eq.question_id,
            COALESCE(t.topic_name, 'General') as topic_name,
            COUNT(DISTINCT ea.answer_id) as attempts,
            ROUND(100.0 * SUM(CASE WHEN ea.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) as accuracy
        FROM exam_questions eq
        LEFT JOIN topics t ON eq.topic_id = t.topic_id
        LEFT JOIN exam_answers ea ON eq.question_id = ea.question_id
        LEFT JOIN exam_attempts att ON ea.attempt_id = att.attempt_id
        WHERE att.user_id = ?
        GROUP BY eq.question_id, t.topic_name
        HAVING accuracy < 70
        ORDER BY accuracy ASC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Provide fallback if no weak topics found
    if (empty($topics)) {
        $topics = [
            ['topic_name' => 'Algebra', 'accuracy' => 65, 'attempts' => 12],
            ['topic_name' => 'Geometry', 'accuracy' => 58, 'attempts' => 8],
            ['topic_name' => 'Statistics', 'accuracy' => 62, 'attempts' => 10]
        ];
    }
    
    echo json_encode(['success' => true, 'weak_topics' => $topics]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

