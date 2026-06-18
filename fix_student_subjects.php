<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

echo "<h2>Fix: Add All Subjects to Student's Trade</h2>";

// Get current student's trade
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT trade_id, full_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$trade_id = $user['trade_id'];
echo "<p><strong>Student Name:</strong> " . htmlspecialchars($user['full_name']) . "</p>";
echo "<p><strong>Student Trade ID:</strong> " . $trade_id . "</p>";

// List of all subjects to ensure exist
$subjects_to_add = [
    'General Knowledge',
    'Mathematics',
    'English',
    'Science',
    'Physics',
    'Chemistry',
    'Biology',
    'History',
    'Geography',
    'Computer Science',
    'Economics',
    'Social Studies',
    'Hindi',
    'Sanskrit',
    'Civics'
];

echo "<h3>Adding Subjects...</h3>";
$added = 0;
$existed = 0;

foreach ($subjects_to_add as $subject_name) {
    $check = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? AND LOWER(subject_name) = LOWER(?)");
    $check->execute([$trade_id, $subject_name]);
    
    if (!$check->fetch()) {
        $insert = $pdo->prepare("INSERT INTO subjects (trade_id, subject_name, created_at) VALUES (?, ?, NOW())");
        if ($insert->execute([$trade_id, $subject_name])) {
            echo "<p style='color: green;'>✓ " . htmlspecialchars($subject_name) . "</p>";
            $added++;
        }
    } else {
        $existed++;
    }
}

echo "<hr>";
echo "<p style='color: blue;'><strong>Added: $added subjects</strong></p>";
echo "<p style='color: blue;'><strong>Already existed: $existed subjects</strong></p>";

// Show all subjects now
$all = $pdo->query("SELECT subject_name FROM subjects WHERE trade_id = $trade_id ORDER BY subject_name")->fetchAll();
echo "<h3>All Subjects Now (" . count($all) . ")</h3>";
echo "<table border='1' cellpadding='10' style='width: 100%;'>";
foreach ($all as $s) {
    echo "<tr><td>" . htmlspecialchars($s['subject_name']) . "</td></tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='/student/notes.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Go to Study Notes (Refresh)</a></p>";
?>
