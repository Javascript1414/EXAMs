<?php
require 'config.php';
require 'includes/db.php';

echo "Checking Database Schema and Student Access:\n\n";

// Check roles table structure
$stmt = $pdo->query("DESCRIBE roles");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Roles table columns: " . implode(", ", array_column($cols, 'Field')) . "\n\n";

// Check what role ID is for student
$stmt = $pdo->query("SELECT * FROM roles LIMIT 5");
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Available roles:\n";
$student_role_id = null;
foreach ($roles as $role) {
    echo "  " . json_encode($role) . "\n";
    if (stripos(json_encode($role), 'student') !== false) {
        $student_role_id = $role['id'] ?? null;
    }
}

// Check users table
$stmt = $pdo->query("DESCRIBE users");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\n\nUsers table columns: " . implode(", ", array_column($cols, 'Field')) . "\n";

// Get all students
echo "\n\nAll Students:\n";
$stmt = $pdo->query("
    SELECT id, email, role_id, trade_id 
    FROM users 
    WHERE role_id IN (SELECT id FROM roles) 
    LIMIT 10
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total students found: " . count($students) . "\n";
foreach ($students as $s) {
    echo "  - ID: {$s['id']}, Email: {$s['email']}, Role: {$s['role_id']}, Trade: {$s['trade_id']}\n";
}

// Check practical exams and their trade IDs
echo "\n\nPractical Exams by Trade:\n";
$stmt = $pdo->query("
    SELECT pe.id, pe.title, pe.trade_id, pe.subject_id, pe.status,
           t.trade_name, s.subject_name
    FROM practical_exams pe
    LEFT JOIN trades t ON pe.trade_id = t.id
    LEFT JOIN subjects s ON pe.subject_id = s.id
");
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($exams as $exam) {
    echo "  - {$exam['title']} | Trade: {$exam['trade_id']} ({$exam['trade_name']}) | Subject: {$exam['subject_name']} | Status: {$exam['status']}\n";
}

// Check trades
echo "\n\nAll Trades:\n";
$stmt = $pdo->query("SELECT id, trade_name FROM trades");
$trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($trades as $t) {
    echo "  - ID: {$t['id']}, Name: {$t['trade_name']}\n";
}

// Test query logic
if (count($students) > 0 && count($exams) > 0) {
    $test_student = $students[0];
    $trade_id = $test_student['trade_id'];
    
    echo "\n\nTesting query for Student ID {$test_student['id']} with Trade ID {$trade_id}:\n";
    
    $stmt = $pdo->prepare("
        SELECT pe.id, pe.title, s.subject_name
        FROM practical_exams pe
        JOIN subjects s ON pe.subject_id = s.id
        JOIN trades t ON s.trade_id = t.id
        WHERE t.id = ? AND pe.status = 'active'
    ");
    $stmt->execute([$trade_id]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Result: " . count($result) . " exams found\n";
    foreach ($result as $r) {
        echo "  - {$r['title']} ({$r['subject_name']})\n";
    }
}
?>
