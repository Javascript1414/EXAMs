<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Get student info
$student = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();
$student_trade = $student['trade_id'];

echo "<h2>Debug: Why Student Can't See All Notes</h2>";
echo "<p><strong>Student:</strong> " . htmlspecialchars($student['full_name']) . "</p>";
echo "<p><strong>Student Trade ID:</strong> " . $student_trade . "</p>";

// Show ALL notes in system
echo "<h3>ALL Notes in System:</h3>";
$all_notes = $pdo->query("
    SELECT n.*, t.trade_name, s.subject_name, u.full_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.uploaded_by = u.id
    ORDER BY n.created_at DESC
")->fetchAll();

echo "<table border='1' cellpadding='10' style='width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Title</th><th>Trade</th><th>Trade ID</th><th>Subject</th><th>Status</th><th>Visible?</th>";
echo "</tr>";

foreach ($all_notes as $note) {
    $visible = ($note['trade_id'] == $student_trade && $note['status'] == 'active') ? "✓ YES" : "✗ NO";
    $visible_color = ($note['trade_id'] == $student_trade && $note['status'] == 'active') ? "green" : "red";
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($note['title']) . "</td>";
    echo "<td>" . htmlspecialchars($note['trade_name']) . "</td>";
    echo "<td>" . $note['trade_id'] . "</td>";
    echo "<td>" . htmlspecialchars($note['subject_name']) . "</td>";
    echo "<td>" . ucfirst($note['status']) . "</td>";
    echo "<td style='color: $visible_color; font-weight: bold;'>$visible</td>";
    echo "</tr>";
}
echo "</table>";

// Show notes student should see
echo "<h3>Notes Student SHOULD See:</h3>";
$visible_notes = $pdo->query("
    SELECT n.*, t.trade_name, s.subject_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    WHERE n.trade_id = $student_trade AND n.status = 'active'
")->fetchAll();

echo "<p><strong>Count: " . count($visible_notes) . "</strong></p>";
if (count($visible_notes) > 0) {
    echo "<ul>";
    foreach ($visible_notes as $note) {
        echo "<li>" . htmlspecialchars($note['title']) . " (" . htmlspecialchars($note['subject_name']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>No notes found for student's trade!</p>";
}
?>
