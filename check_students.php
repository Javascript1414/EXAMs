<?php
require 'includes/db.php';
$stmt = $pdo->query('SELECT id, email, full_name FROM users LIMIT 5');
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($students as $s) {
    echo $s['id'] . " | " . $s['email'] . " | " . $s['full_name'] . "\n";
}
?>
