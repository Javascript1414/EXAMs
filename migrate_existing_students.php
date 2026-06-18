<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔄 Adding Existing Students to student_trades Table</h2>";

// Find all students (role_id = 4) who don't have entries in student_trades
$result = $pdo->query("
    SELECT u.id, u.full_name, u.trade_id
    FROM users u
    WHERE u.role_id = 4 AND u.trade_id IS NOT NULL
    AND NOT EXISTS (
        SELECT 1 FROM student_trades st WHERE st.student_id = u.id
    )
")->fetchAll();

if (empty($result)) {
    echo "<p style='color: green;'><strong>✅ All students already in student_trades table!</strong></p>";
} else {
    echo "<p><strong>Found " . count($result) . " students to migrate:</strong></p>";
    
    foreach ($result as $student) {
        $stmt = $pdo->prepare("INSERT INTO student_trades (student_id, trade_id) VALUES (?, ?)");
        $stmt->execute([$student['id'], $student['trade_id']]);
        echo "✅ " . $student['full_name'] . " → Trade ID " . $student['trade_id'] . "<br>";
    }
    
    echo "<p style='color: green; margin-top: 20px;'><strong>✅ Migration Complete!</strong></p>";
}

// Verify
echo "<h3>Final Status:</h3>";
$count = $pdo->query("SELECT COUNT(DISTINCT student_id) FROM student_trades")->fetchColumn();
echo "<p><strong>Total students in student_trades: $count</strong></p>";

// Show all students
$result = $pdo->query("
    SELECT u.id, u.full_name, COUNT(st.trade_id) as trade_count
    FROM users u
    LEFT JOIN student_trades st ON u.id = st.student_id
    WHERE u.role_id = 4
    GROUP BY u.id
    ORDER BY u.full_name
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Student</th><th>Trades Assigned</th></tr>";
foreach ($result as $row) {
    $color = $row['trade_count'] > 0 ? 'green' : 'orange';
    echo "<tr><td>" . $row['full_name'] . "</td><td style='color: $color;'><strong>" . $row['trade_count'] . "</strong></td></tr>";
}
echo "</table>";
?>
