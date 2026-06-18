<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Testing Filter Action (Simulating GET Request)</h2>";

$student_id = 3;
$subject_filter = 1; // User clicks on "General Knowledge"

echo "<p><strong>Simulating:</strong> Click subject dropdown with ID " . $subject_filter . "</p>";

// Get student's trades
$stmt = $pdo->prepare("
    SELECT trade_id FROM student_trades WHERE student_id = ?
");
$stmt->execute([$student_id]);
$trades = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($trades)) {
    echo "<p style='color: red;'>❌ No trades assigned!</p>";
    die();
}

$trade_ids_placeholders = implode(',', array_fill(0, count($trades), '?'));

// Main query (same as student/notes.php)
$query = "
    SELECT n.*, s.subject_name, t.trade_name, u.full_name as uploaded_by_name
    FROM notes n
    JOIN trades t ON n.trade_id = t.id
    JOIN subjects s ON n.subject_id = s.id
    JOIN users u ON n.uploaded_by = u.id
    WHERE n.trade_id IN ($trade_ids_placeholders) AND n.status = 'active'
";

$params = $trades;

// Apply subject filter
$query .= " AND n.subject_id = ?";
$params[] = $subject_filter;

$query .= " ORDER BY n.created_at DESC LIMIT 6 OFFSET 0";

echo "<h3>Query Being Executed:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
echo $query;
echo "</pre>";

echo "<h3>Query Parameters:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px;'>";
print_r($params);
echo "</pre>";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();

echo "<h3>Results:</h3>";
echo "<p><strong>Rows returned:</strong> " . count($results) . "</p>";

if (count($results) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Title</th><th>Subject</th><th>Trade</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['subject_name'] . "</td>";
        echo "<td>" . $row['trade_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green;'><strong>✅ Filter is working!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ No notes found with this filter</strong></p>";
    
    // Show all notes without filter
    echo "<h3>All notes (without filter):</h3>";
    $query2 = "
        SELECT n.*, s.subject_name
        FROM notes n
        JOIN subjects s ON n.subject_id = s.id
        WHERE n.trade_id IN ($trade_ids_placeholders) AND n.status = 'active'
    ";
    $stmt2 = $pdo->prepare($query2);
    $stmt2->execute($trades);
    $all_results = $stmt2->fetchAll();
    
    echo "<p>Total notes: " . count($all_results) . "</p>";
    foreach ($all_results as $r) {
        echo "- " . $r['title'] . " (Subject ID: " . $r['subject_id'] . " = " . $r['subject_name'] . ")<br>";
    }
}

echo "<h3>Now test in browser:</h3>";
echo "<p><a href='http://localhost/EXAMs/student/notes.php?subject=1' target='_blank'>Click here to test filter</a></p>";
echo "<p>(You need to be logged in as a student first)</p>";

?>
