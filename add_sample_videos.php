<?php
/**
 * Add Sample Videos to Database
 * Run this once to populate with sample data
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

// Get admin user
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role_id IN (SELECT id FROM roles WHERE role_name = 'admin')");
$stmt->execute(['admin@exam.com']);
$admin = $stmt->fetch();

if (!$admin) {
    // Create default instructor if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['instructor@exam.com']);
    $instructor = $stmt->fetch();
    
    if (!$instructor) {
        die('No instructor found. Please create an instructor account first.');
    }
} else {
    $instructor = $admin;
}

// Get default course
$stmt = $pdo->prepare("SELECT course_id FROM courses LIMIT 1");
$stmt->execute();
$course = $stmt->fetch();

if (!$course) {
    die('No course found. Please create a course first.');
}

// Sample videos with YouTube links
$sampleVideos = [
    [
        'title' => 'Introduction to Web Development',
        'description' => 'Learn the basics of HTML, CSS, and JavaScript. This comprehensive course will teach you how to create beautiful and responsive websites.',
        'video_file' => 'youtube:dQw4w9WgXcQ',
        'duration' => 3600
    ],
    [
        'title' => 'Advanced JavaScript Concepts',
        'description' => 'Master async/await, promises, closures, and event handling in JavaScript. Perfect for intermediate developers.',
        'video_file' => 'youtube:jS4aFq5-91M',
        'duration' => 4200
    ],
    [
        'title' => 'PHP Backend Development',
        'description' => 'Build robust backend applications with PHP. Learn about database connections, APIs, and security best practices.',
        'video_file' => 'youtube:3nHt-A1sBa8',
        'duration' => 3800
    ],
    [
        'title' => 'Database Design Principles',
        'description' => 'Understand database normalization, indexing, and optimization. Essential for all backend developers.',
        'video_file' => 'youtube:fhAp9RgbVg8',
        'duration' => 2800
    ],
    [
        'title' => 'Responsive Web Design',
        'description' => 'Create websites that work perfectly on all devices. Learn mobile-first design principles and CSS media queries.',
        'video_file' => 'youtube:E6S8LYNWlTI',
        'duration' => 3200
    ],
    [
        'title' => 'HTML5 Forms and Validation',
        'description' => 'Master HTML5 form elements and client-side validation. Create user-friendly forms with proper validation.',
        'video_file' => 'youtube:OHUoHYVKHyc',
        'duration' => 2400
    ],
    [
        'title' => 'CSS Grid and Flexbox',
        'description' => 'Learn modern CSS layout techniques. Master CSS Grid and Flexbox to create complex layouts easily.',
        'video_file' => 'youtube:jV8B24rSN5o',
        'duration' => 3100
    ],
    [
        'title' => 'AJAX and Fetch API',
        'description' => 'Make asynchronous HTTP requests from the browser. Learn both traditional AJAX and modern Fetch API.',
        'video_file' => 'youtube:Yjkpxq-2KEw',
        'duration' => 2600
    ],
    [
        'title' => 'Introduction to React',
        'description' => 'Start your journey with React. Learn components, state, props, and lifecycle methods.',
        'video_file' => 'youtube:W6NZfCO5tTE',
        'duration' => 4500
    ]
];

try {
    $stmt = $pdo->prepare("
        INSERT INTO videos (video_name, description, video_file, duration, course_id, instructor_id, views, rating, total_ratings, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, 'active', NOW())
    ");

    $added = 0;
    foreach ($sampleVideos as $video) {
        $stmt->execute([
            $video['title'],
            $video['description'],
            $video['video_file'],
            $video['duration'],
            $course['course_id'],
            $instructor['id']
        ]);
        $added++;
    }

    echo "<h2 style='color: green;'>✓ Successfully added $added sample videos!</h2>";
    echo "<p><a href='student/video_streaming.php'>Go to Video Streaming Platform</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>✗ Error adding videos</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
