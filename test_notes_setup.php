<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Testing Student Notes Page Setup</h2>";

// Get a student from DB
$student = $pdo->query("SELECT * FROM users WHERE role_id = 4 LIMIT 1")->fetch();

if (!$student) {
    echo "<p style='color: red;'>❌ No student found in database!</p>";
    die();
}

echo "<p style='color: green;'>✅ Found student: " . $student['full_name'] . " (ID: " . $student['id'] . ")</p>";

// Check assigned trades
$trades = $pdo->query("
    SELECT * FROM student_trades WHERE student_id = " . $student['id']
)->fetchAll();

echo "<p><strong>Trades assigned:</strong> " . count($trades) . "</p>";

if (empty($trades)) {
    echo "<p style='color: red;'>⚠️ No trades assigned! Adding first one...</p>";
    
    // Assign default trade
    $stmt = $pdo->prepare("INSERT INTO student_trades (student_id, trade_id) VALUES (?, ?)");
    $stmt->execute([$student['id'], 1]);
    echo "<p style='color: green;'>✅ Assigned Trade 1 to student</p>";
    
    $trades = $pdo->query("
        SELECT * FROM student_trades WHERE student_id = " . $student['id']
    )->fetchAll();
}

// Now test the SQL query that student/notes.php uses
echo "<h3>Testing Student Notes Query:</h3>";

$trade_ids = array_column($trades, 'trade_id');
$placeholders = implode(',', array_fill(0, count($trade_ids), '?'));

echo "<p><strong>Trade IDs:</strong> " . implode(', ', $trade_ids) . "</p>";

$query = "
    SELECT n.*, s.subject_name, t.trade_name, u.full_name as uploaded_by_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.uploaded_by = u.id
    WHERE n.trade_id IN ($placeholders) AND n.status = 'active'
";

$stmt = $pdo->prepare($query);
$stmt->execute($trade_ids);
$notes = $stmt->fetchAll();

echo "<p><strong>Notes found:</strong> " . count($notes) . "</p>";

if (count($notes) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Title</th><th>Trade</th><th>Subject</th><th>Status</th></tr>";
    foreach ($notes as $note) {
        echo "<tr>";
        echo "<td>" . $note['id'] . "</td>";
        echo "<td>" . $note['title'] . "</td>";
        echo "<td>" . $note['trade_name'] . "</td>";
        echo "<td>" . $note['subject_name'] . "</td>";
        echo "<td>" . $note['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'><strong>✅ Page should work!</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ No notes found. Check if notes exist and status='active'</p>";
    
    // Check all notes
    $all_notes = $pdo->query("SELECT id, title, trade_id, status FROM notes LIMIT 5")->fetchAll();
    echo "<p><strong>Sample notes in database:</strong></p>";
    echo "<pre>";
    print_r($all_notes);
    echo "</pre>";
}

echo "<h3>Next Step:</h3>";
echo "<p>Try accessing: <a href='/student/notes.php' target='_blank'>/student/notes.php</a></p>";
echo "<p>(You need to be logged in as a student)</p>";
?>
