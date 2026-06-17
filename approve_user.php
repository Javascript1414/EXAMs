<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Approve user 25
$stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', status = 'active' WHERE id = 25");
$stmt->execute();

echo "User 25 approved and activated!<br>";
echo "<p><a href='student_login.php'>Back to login</a></p>";
