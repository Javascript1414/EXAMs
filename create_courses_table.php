<?php
/**
 * Create Courses Table Migration
 */

require_once __DIR__ . '/includes/db.php';

echo "Creating courses table...\n";

try {
    // Create courses table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            course_id INT PRIMARY KEY AUTO_INCREMENT,
            course_name VARCHAR(255) NOT NULL,
            course_code VARCHAR(50) UNIQUE,
            description TEXT,
            instructor_id BIGINT UNSIGNED,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    echo "✅ Courses table created successfully!\n";
    
    // Insert sample courses
    $courses = [
        ['course_name' => 'Mathematics', 'course_code' => 'MATH101'],
        ['course_name' => 'Science', 'course_code' => 'SCI101'],
        ['course_name' => 'English', 'course_code' => 'ENG101'],
        ['course_name' => 'History', 'course_code' => 'HIST101'],
        ['course_name' => 'Chemistry', 'course_code' => 'CHEM101'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO courses (course_name, course_code, description)
        VALUES (?, ?, 'Sample course for testing')
    ");
    
    foreach ($courses as $course) {
        $stmt->execute([$course['course_name'], $course['course_code']]);
        echo "✓ Added: {$course['course_name']}\n";
    }
    
    echo "\n✅ Sample courses added successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
