<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get student details
$stmt = $pdo->prepare("SELECT id, full_name, trade_id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

$trade_id = $student['trade_id'];

// Get all subjects for this trade
$stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE trade_id = ? ORDER BY subject_name");
$stmt->execute([$trade_id]);
$subjects = $stmt->fetchAll();

echo "<h2>Subject Count Check</h2>";
echo "<p><strong>Student:</strong> " . htmlspecialchars($student['full_name']) . "</p>";
echo "<p><strong>Trade ID:</strong> " . $trade_id . "</p>";
echo "<hr>";
echo "<h3>Total Subjects: " . count($subjects) . "</h3>";

if (count($subjects) > 0) {
    echo "<table border='1' cellpadding='10' style='width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Subject ID</th><th>Subject Name</th></tr>";
    foreach ($subjects as $s) {
        echo "<tr><td>" . $s['id'] . "</td><td>" . htmlspecialchars($s['subject_name']) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No subjects found!</strong></p>";
}

echo "<hr>";
echo "<p><a href='/EXAMs/student/notes.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Go to Study Notes</a></p>";
?>
