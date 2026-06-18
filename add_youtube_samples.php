<?php
/**
 * Add Sample YouTube Videos
 * Script to insert sample YouTube videos into the database
 */

require_once __DIR__ . '/includes/db.php';

echo "Adding sample YouTube videos...\n";

try {
    // Sample YouTube videos
    $videos = [
        [
            'name' => 'Khan Academy: Algebra Basics',
            'description' => 'Learn the fundamentals of algebra from Khan Academy - a trusted educational resource',
            'course_id' => 1,
            'instructor_id' => 1,
            'youtube_id' => 'Pd_6XpSRnQQ',
            'duration' => 600
        ],
        [
            'name' => 'Crash Course: Biology Intro',
            'description' => 'An entertaining and educational introduction to biology concepts',
            'course_id' => 4,
            'instructor_id' => 1,
            'youtube_id' => 'QnQSUJKS75w',
            'duration' => 1200
        ],
        [
            'name' => 'TED-Ed: Physics Explained',
            'description' => 'Understanding the principles of physics through animated explanations',
            'course_id' => 2,
            'instructor_id' => 1,
            'youtube_id' => 'N5v1Ur8dQMo',
            'duration' => 900
        ]
    ];

    foreach ($videos as $video) {
        $stmt = $pdo->prepare("
            INSERT INTO videos (video_name, description, course_id, instructor_id, video_file, duration, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
            ON DUPLICATE KEY UPDATE video_name = video_name
        ");
        
        $stmt->execute([
            $video['name'],
            $video['description'],
            $video['course_id'],
            $video['instructor_id'],
            'youtube:' . $video['youtube_id'],
            $video['duration']
        ]);
        
        echo "✓ Added: {$video['name']}\n";
    }

    echo "\n✅ Sample YouTube videos added successfully!\n";
    echo "\nVideo Links:\n";
    echo "1. Algebra Basics: https://youtu.be/Pd_6XpSRnQQ\n";
    echo "2. Biology Intro: https://youtu.be/QnQSUJKS75w\n";
    echo "3. Physics Explained: https://youtu.be/N5v1Ur8dQMo\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
