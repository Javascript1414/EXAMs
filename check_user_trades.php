<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Checking User Roles and Trades</h2>";

// Check user 2
echo "<h3>User ID 2 (SOUMYAJIT SANTRA):</h3>";
$result = $pdo->query("SELECT id, full_name, trade_id, role_id FROM users WHERE id = 2")->fetch();
echo "<pre>";
print_r($result);
echo "</pre>";

// Check all users with their roles
echo "<h3>All Users and Their Roles:</h3>";
$result = $pdo->query("
    SELECT u.id, u.full_name, u.trade_id, u.role_id
    FROM users u
    LIMIT 10
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Name</th><th>Trade ID</th><th>Role ID</th></tr>";
foreach ($result as $row) {
    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['full_name'] . "</td><td>" . $row['trade_id'] . "</td><td>" . $row['role_id'] . "</td></tr>";
}
echo "</table>";

// Check student_trades table
echo "<h3>All Existing Student-Trade Assignments:</h3>";
$result = $pdo->query("
    SELECT st.*, u.full_name, t.trade_name
    FROM student_trades st
    JOIN users u ON st.student_id = u.id
    JOIN trades t ON st.trade_id = t.id
")->fetchAll();

if (empty($result)) {
    echo "<p style='color: red;'><strong>No records found!</strong></p>";
} else {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr style='background: #2196F3; color: white;'><th>Student</th><th>Trade</th></tr>";
    foreach ($result as $row) {
        echo "<tr><td>" . $row['full_name'] . "</td><td>" . $row['trade_name'] . "</td></tr>";
    }
    echo "</table>";
}
?>
