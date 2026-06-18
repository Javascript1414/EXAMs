<?php
require_once __DIR__ . '/includes/db.php';

// Get first student's trade
$stmt = $pdo->query("SELECT trade_id, trade_name FROM users u JOIN trades t ON u.trade_id = t.id WHERE u.role = 'student' LIMIT 1");
$student = $stmt->fetch();

if (!$student) {
    die("No student found!");
}

$trade_id = $student['trade_id'];
$trade_name = $student['trade_name'];

echo "<h2>Adding Subjects for: " . htmlspecialchars($trade_name) . " (ID: $trade_id)</h2>";

// Sample subjects to add
$subjects = [
    'General Knowledge',
    'Mathematics', 
    'English',
    'Science',
    'Physics',
    'Chemistry',
    'Biology',
    'History',
    'Geography',
    'Computer Science'
];

$added = 0;
$existed = 0;

foreach ($subjects as $subject_name) {
    // Check if exists
    $check = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? AND subject_name = ?");
    $check->execute([$trade_id, $subject_name]);
    
    if (!$check->fetch()) {
        // Add it
        $insert = $pdo->prepare("INSERT INTO subjects (trade_id, subject_name, created_at) VALUES (?, ?, NOW())");
        $insert->execute([$trade_id, $subject_name]);
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($subject_name) . "</p>";
        $added++;
    } else {
        echo "<p style='color: orange;'>⚠ Already exists: " . htmlspecialchars($subject_name) . "</p>";
        $existed++;
    }
}

echo "<hr>";
echo "<p style='color: blue;'><strong>Added: $added subjects</strong></p>";
echo "<p style='color: blue;'><strong>Already existed: $existed subjects</strong></p>";

// Show all subjects
$all = $pdo->query("SELECT subject_name FROM subjects WHERE trade_id = ? ORDER BY subject_name", [$trade_id])->fetchAll();
echo "<h3>Total Subjects Now: " . count($all) . "</h3>";
echo "<ul>";
foreach ($all as $s) {
    echo "<li>" . htmlspecialchars($s['subject_name']) . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='/student/notes.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>← Go to Study Notes</a></p>";
?>
