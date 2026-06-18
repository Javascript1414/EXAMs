<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>🔍 Checking Role System</h2>";

// Check if roles table has 'student' role
echo "<h3>Available Roles:</h3>";
$roles = $pdo->query("SELECT id, role_name FROM roles")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #2196F3; color: white;'><th>ID</th><th>Role</th></tr>";
foreach ($roles as $role) {
    echo "<tr><td>" . $role['id'] . "</td><td>" . $role['role_name'] . "</td></tr>";
}
echo "</table>";

// Check what role_id students have
echo "<h3>Students and Their Roles:</h3>";
$students = $pdo->query("
    SELECT u.id, u.full_name, u.role_id 
    FROM users u 
    WHERE u.role_id = 3 OR u.role_id = 4
    LIMIT 5
")->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr style='background: #4CAF50; color: white;'><th>ID</th><th>Name</th><th>Role ID</th></tr>";
foreach ($students as $student) {
    echo "<tr><td>" . $student['id'] . "</td><td>" . $student['full_name'] . "</td><td>" . $student['role_id'] . "</td></tr>";
}
echo "</table>";

// Test hasRole function
echo "<h3>Testing hasRole() function:</h3>";
$_SESSION['user_id'] = 3;
$_SESSION['role_name'] = 'student';

require_once __DIR__ . '/includes/functions.php';

echo "<p>Session role_name: " . $_SESSION['role_name'] . "</p>";
echo "<p>hasRole('student'): " . (hasRole('student') ? 'TRUE ✅' : 'FALSE ❌') . "</p>";
echo "<p>hasRole('admin'): " . (hasRole('admin') ? 'TRUE' : 'FALSE') . "</p>";

?>
