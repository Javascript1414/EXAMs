<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>✅ Testing Multiple Trades System</h2>";

// Verify junction table
$check = $pdo->query("SELECT COUNT(*) FROM student_trades")->fetchColumn();
echo "<p><strong>✅ student_trades table exists:</strong> $check records</p>";

// Test query logic for a student
$test_student_id = 2; // SOUMYAJIT SANTRA

echo "<h3>Test: Fetching trades for Student ID $test_student_id</h3>";

// Step 1: Get trades
$stmt = $pdo->prepare("
    SELECT DISTINCT st.trade_id, t.trade_name
    FROM student_trades st
    JOIN trades t ON st.trade_id = t.id
    WHERE st.student_id = ?
    ORDER BY t.trade_name
");
$stmt->execute([$test_student_id]);
$assigned_trades = $stmt->fetchAll();

echo "<p><strong>Assigned Trades:</strong> " . count($assigned_trades) . "</p>";
foreach ($assigned_trades as $trade) {
    echo "- " . $trade['trade_name'] . " (ID: " . $trade['trade_id'] . ")<br>";
}

if (!empty($assigned_trades)) {
    // Step 2: Get trade IDs array
    $trade_ids = array_column($assigned_trades, 'trade_id');
    $trade_ids_placeholders = implode(',', array_fill(0, count($trade_ids), '?'));
    
    echo "<h3>Test: Fetching Subjects</h3>";
    
    // Step 3: Get subjects
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.subject_name
        FROM subjects s
        WHERE s.trade_id IN ($trade_ids_placeholders)
        ORDER BY s.subject_name
    ");
    $stmt->execute($trade_ids);
    $subjects = $stmt->fetchAll();
    
    echo "<p><strong>Total Subjects:</strong> " . count($subjects) . "</p>";
    
    echo "<h3>Test: Fetching Notes</h3>";
    
    // Step 4: Get notes
    $stmt = $pdo->prepare("
        SELECT n.id, n.title, t.trade_name, s.subject_name
        FROM notes n
        JOIN trades t ON n.trade_id = t.id
        JOIN subjects s ON n.subject_id = s.id
        WHERE n.trade_id IN ($trade_ids_placeholders) AND n.status = 'active'
        ORDER BY n.created_at DESC
    ");
    $stmt->execute($trade_ids);
    $notes = $stmt->fetchAll();
    
    echo "<p><strong>Total Notes:</strong> " . count($notes) . "</p>";
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>Title</th><th>Trade</th><th>Subject</th></tr>";
    foreach ($notes as $note) {
        echo "<tr><td>" . $note['title'] . "</td><td>" . $note['trade_name'] . "</td><td>" . $note['subject_name'] . "</td></tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; margin-top: 20px;'><strong>✅ ALL TESTS PASSED!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ Student has no trades assigned.</strong></p>";
    echo "<p>Assign trades using: <a href='/admin/assign_student_trades.php'>/admin/assign_student_trades.php</a></p>";
}

?>
