<?php
/**
 * Timeline API - Get Achievements
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get user achievements
    $query = "
        SELECT 
            ua.achievement_id,
            a.achievement_name as title,
            a.description,
            a.icon,
            ua.created_at
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.achievement_id
        WHERE ua.user_id = ?
        ORDER BY ua.created_at DESC
        LIMIT 12
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    $achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no achievements, return default ones
    if (count($achievements) === 0) {
        $achievements = [
            ['title' => 'First Steps', 'description' => 'Took your first exam', 'icon' => '🎯'],
            ['title' => 'Quick Learner', 'description' => 'Completed first course', 'icon' => '⚡'],
            ['title' => 'Bookworm', 'description' => 'Bookmarked 5 materials', 'icon' => '📚'],
            ['title' => 'Ace Performance', 'description' => 'Scored 90%+ on exam', 'icon' => '⭐'],
            ['title' => 'Consistent Learner', 'description' => '7 day learning streak', 'icon' => '🔥'],
            ['title' => 'Social Butterfly', 'description' => 'Joined study group', 'icon' => '🦋']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'achievements' => $achievements
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
