<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>📊 Current System Status</h2>";

// Show trades
echo "<h3>Trades in System:</h3>";
$stmt = $pdo->query("SELECT id, trade_name FROM trades ORDER BY id");
$trades = $stmt->fetchAll();

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #4CAF50; color: white;'><th>Trade ID</th><th>Trade Name</th><th>Students</th><th>Notes</th></tr>";

foreach ($trades as $trade) {
    $studentCount = $pdo->query("SELECT COUNT(*) FROM users WHERE trade_id = " . $trade['id'])->fetchColumn();
    $noteCount = $pdo->query("SELECT COUNT(*) FROM notes WHERE trade_id = " . $trade['id'])->fetchColumn();
    
    echo "<tr>";
    echo "<td>" . $trade['id'] . "</td>";
    echo "<td><strong>" . $trade['trade_name'] . "</strong></td>";
    echo "<td>" . $studentCount . "</td>";
    echo "<td>" . $noteCount . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>Students by Trade:</h3>";
foreach ($trades as $trade) {
    $students = $pdo->query("SELECT id, full_name FROM users WHERE trade_id = " . $trade['id'] . " LIMIT 3");
    $all = $students->fetchAll();
    
    echo "<p><strong>" . $trade['trade_name'] . ":</strong> " . count($all) . " users</p>";
}
?>
