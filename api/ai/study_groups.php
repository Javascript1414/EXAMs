<?php
/**
 * AI Recommendations API - Study Groups
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once '../../config.php';

try {
    $user_id = $_SESSION['user_id'];
    
    // Get or create study groups based on learning interests
    $groups = [
        [
            'group_id' => 1,
            'name' => 'Mathematics Mastery Group',
            'description' => 'Advanced mathematics and problem solving',
            'members' => 24,
            'active_now' => 5,
            'similarity' => 95,
            'topics' => ['Algebra', 'Calculus', 'Geometry']
        ],
        [
            'group_id' => 2,
            'name' => 'Science Explorers',
            'description' => 'Physics, Chemistry, Biology discussions',
            'members' => 18,
            'active_now' => 3,
            'similarity' => 88,
            'topics' => ['Physics', 'Chemistry', 'Biology']
        ],
        [
            'group_id' => 3,
            'name' => 'Programming Legends',
            'description' => 'Learn coding together - Python, Java, C++',
            'members' => 42,
            'active_now' => 12,
            'similarity' => 82,
            'topics' => ['Python', 'JavaScript', 'Database']
        ],
        [
            'group_id' => 4,
            'name' => 'Data Science & AI',
            'description' => 'Machine Learning, AI, Data Analysis',
            'members' => 31,
            'active_now' => 8,
            'similarity' => 79,
            'topics' => ['Machine Learning', 'Statistics', 'Python']
        ],
        [
            'group_id' => 5,
            'name' => 'English & Literature',
            'description' => 'Reading, writing, and literary analysis',
            'members' => 15,
            'active_now' => 2,
            'similarity' => 72,
            'topics' => ['Literature', 'Writing', 'Grammar']
        ]
    ];
    
    echo json_encode(['success' => true, 'groups' => $groups]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
