<?php
require 'config.php';
require 'includes/db.php';

try {
    // Check practical exams
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM practical_exams");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Practical Exams: " . $result['count'] . "\n";
    
    // Check if student's trade has any exams
    $stmt = $pdo->query("
        SELECT pe.id, pe.title, pe.subject_id, pe.trade_id, s.subject_name, t.trade_name, pe.submission_deadline
        FROM practical_exams pe
        JOIN subjects s ON pe.subject_id = s.id
        JOIN trades t ON pe.trade_id = t.id
        LIMIT 10
    ");
    $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nExisting Practical Exams:\n";
    if (count($exams) > 0) {
        foreach ($exams as $exam) {
            echo "- {$exam['title']} ({$exam['subject_name']} - {$exam['trade_name']}) - Deadline: {$exam['submission_deadline']}\n";
        }
    } else {
        echo "No practical exams found. Teachers need to create them.\n";
    }
    
    // Check available subjects
    echo "\n\nAvailable Subjects:\n";
    $stmt = $pdo->query("
        SELECT s.id, s.subject_name, t.trade_name, t.id as trade_id
        FROM subjects s
        JOIN trades t ON s.trade_id = t.id
        LIMIT 10
    ");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjects as $subj) {
        echo "- {$subj['subject_name']} ({$subj['trade_name']})\n";
    }
    
    // Check if logged-in student has a trade
    echo "\n\nStudent Trade Info:\n";
    $stmt = $pdo->query("
        SELECT id, username, trade_id FROM users WHERE role_id = 3 LIMIT 5
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($students as $student) {
        echo "- Student: {$student['username']} - Trade ID: {$student['trade_id']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
