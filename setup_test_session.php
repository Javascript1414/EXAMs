<?php
session_start();

// Set up a student session
require 'includes/db.php';

// Get a student (ID 2 was used in the previous test)
$_SESSION['user_id'] = 2;
$_SESSION['role_name'] = 'student';

// Get exam attempt info
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE student_id = 2 AND exam_id = 4 ORDER BY id DESC LIMIT 1");
$stmt->execute();
$attempt = $stmt->fetch();

if ($attempt) {
    echo "Session set! Attempt ID: " . $attempt['id'] . "<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Role: " . $_SESSION['role_name'] . "<br>";
    echo "<a href='student/exam_attempt.php?id=4'>Click to view exam</a>";
} else {
    echo "No attempt found";
}
?>
