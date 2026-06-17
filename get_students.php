<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.full_name, r.name as role
    FROM users u 
    JOIN roles r ON u.role_id = r.id
    WHERE r.name = 'student'
    LIMIT 5
");
$stmt->execute();
$students = $stmt->fetchAll();

echo "<h2>Available Student Accounts</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Email</th><th>Name</th></tr>";
foreach ($students as $s) {
    echo "<tr><td>{$s['id']}</td><td>{$s['email']}</td><td>{$s['full_name']}</td></tr>";
}
echo "</table>";
echo "<p><strong>Test with any of these email addresses. Password is probably a test password.</strong></p>";
echo "<p><a href='student_login.php'>Go back to login</a></p>";
