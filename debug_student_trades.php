<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Debugging Student Trades Issue</h2>";

// Check if student_trades table exists
echo "<h3>Checking for Junction Tables:</h3>";
$result = $pdo->query("SHOW TABLES LIKE '%trade%'")->fetchAll();
echo "<pre>";
print_r($result);
echo "</pre>";

// Check users table structure
echo "<h3>Users Table Structure:</h3>";
$result = $pdo->query("DESCRIBE users")->fetchAll();
echo "<pre>";
foreach ($result as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "</pre>";

// Check if student is assigned to multiple trades
echo "<h3>Checking Current Database Tables:</h3>";
$result = $pdo->query("SHOW TABLES")->fetchAll();
foreach ($result as $row) {
    echo implode(" | ", $row) . "<br>";
}
?>
