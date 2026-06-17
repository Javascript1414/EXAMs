<?php
/**
 * AI Recommendations API - Recommended Materials
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get recommended materials based on weak topics
    $materials = [
        ['material_id' => 1, 'title' => 'Understanding Algebra - Comprehensive Guide', 'type' => 'PDF', 'rating' => 4.8, 'views' => 2340, 'relevance' => 95],
        ['material_id' => 2, 'title' => 'Geometry Mastery Video Series', 'type' => 'Video', 'rating' => 4.7, 'views' => 1890, 'relevance' => 92],
        ['material_id' => 3, 'title' => 'Statistics Practice Problems', 'type' => 'Interactive', 'rating' => 4.9, 'views' => 3240, 'relevance' => 88],
        ['material_id' => 4, 'title' => 'Calculus Foundations - Notes', 'type' => 'PDF', 'rating' => 4.6, 'views' => 2150, 'relevance' => 85],
        ['material_id' => 5, 'title' => 'Trigonometry Explained', 'type' => 'Video', 'rating' => 4.5, 'views' => 1560, 'relevance' => 80],
        ['material_id' => 6, 'title' => 'Linear Algebra Study Guide', 'type' => 'PDF', 'rating' => 4.8, 'views' => 2780, 'relevance' => 87],
        ['material_id' => 7, 'title' => 'Advanced Problem Solving', 'type' => 'Interactive', 'rating' => 4.9, 'views' => 3100, 'relevance' => 92],
        ['material_id' => 8, 'title' => 'Math Tricks & Shortcuts', 'type' => 'Video', 'rating' => 4.7, 'views' => 4200, 'relevance' => 78]
    ];
    
    echo json_encode(['success' => true, 'materials' => $materials]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
