<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Debugging Subject Filter Issue</h2>";

// Get a student
$student_id = 3; // SOUMO SANTRA

echo "<h3>1. Get Student's Assigned Trades:</h3>";
$stmt = $pdo->prepare("
    SELECT st.trade_id, t.trade_name
    FROM student_trades st
    JOIN trades t ON st.trade_id = t.id
    WHERE st.student_id = ?
");
$stmt->execute([$student_id]);
$trades = $stmt->fetchAll();

echo "<p><strong>Trades:</strong></p>";
foreach ($trades as $trade) {
    echo "- " . $trade['trade_name'] . " (ID: " . $trade['trade_id'] . ")<br>";
}

echo "<h3>2. Get ALL Subjects from Student's Trades:</h3>";
$trade_ids = array_column($trades, 'trade_id');
$placeholders = implode(',', array_fill(0, count($trade_ids), '?'));

$stmt = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name, t.trade_name
    FROM subjects s
    JOIN trades t ON s.trade_id = t.id
    WHERE s.trade_id IN ($placeholders)
    ORDER BY s.subject_name
");
$stmt->execute($trade_ids);
$subjects = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Subject ID</th><th>Subject Name</th><th>Trade</th></tr>";
foreach ($subjects as $subject) {
    echo "<tr><td>" . $subject['id'] . "</td><td>" . $subject['subject_name'] . "</td><td>" . $subject['trade_name'] . "</td></tr>";
}
echo "</table>";

echo "<h3>3. Test Filtering by Subject:</h3>";
if (!empty($subjects)) {
    $test_subject_id = $subjects[0]['id'];
    echo "<p><strong>Testing filter with Subject ID: " . $test_subject_id . " (" . $subjects[0]['subject_name'] . ")</strong></p>";
    
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, n.subject_id, s.subject_name, t.trade_name
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        JOIN trades t ON n.trade_id = t.id
        WHERE n.trade_id IN ($placeholders) AND n.subject_id = ? AND n.status = 'active'
    ");
    $params = $trade_ids;
    $params[] = $test_subject_id;
    $stmt->execute($params);
    $filtered_notes = $stmt->fetchAll();
    
    echo "<p><strong>Notes for this subject:</strong> " . count($filtered_notes) . "</p>";
    
    if (count($filtered_notes) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr style='background: #2196F3; color: white;'><th>Title</th><th>Subject</th><th>Trade</th></tr>";
        foreach ($filtered_notes as $note) {
            echo "<tr><td>" . $note['title'] . "</td><td>" . $note['subject_name'] . "</td><td>" . $note['trade_name'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No notes found for this subject</p>";
    }
}

echo "<h3>4. Check Dropdown HTML Logic:</h3>";
echo "<p>Dropdown should show:</p>";
echo "<ul>";
foreach ($subjects as $subject) {
    echo "<li>&lt;option value='" . $subject['id'] . "'&gt;" . $subject['subject_name'] . "&lt;/option&gt;</li>";
}
echo "</ul>";

?>
