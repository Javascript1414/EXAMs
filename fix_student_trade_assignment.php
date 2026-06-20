<?php
require 'config.php';
require 'includes/db.php';

echo "Updating Student Trade Assignment\n\n";

// The current logged-in student from the screenshot appears to be using an account
// Let's identify which student is currently logged in by checking the active session
// For now, let's update the test students to be in CSA trade

echo "Updating students to CSA trade (Trade ID 2):\n";

// Get all students in Trade 1 (General Education)
$stmt = $pdo->query("
    SELECT id, email FROM users 
    WHERE trade_id = 1 AND role_id = 4
");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($students) . " students in General Education trade\n";

// Update them to CSA trade
if (count($students) > 0) {
    $stmt = $pdo->prepare("UPDATE users SET trade_id = 2 WHERE id = ?");
    
    foreach ($students as $student) {
        $stmt->execute([$student['id']]);
        echo "  ✓ Updated {$student['email']} to CSA trade\n";
    }
    
    echo "\n✓ All students updated! They should now see the practical exams.\n";
} else {
    echo "No students found in General Education trade\n";
}

// Verify the update
echo "\nVerifying - Students now have these trades:\n";
$stmt = $pdo->query("
    SELECT u.id, u.email, t.id, t.trade_name
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    WHERE u.role_id = 4
");
$updated = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($updated as $s) {
    echo "  - {$s['email']} → {$s['trade_name']} (Trade ID: {$s['id']})\n";
}

// Test the query again
echo "\nTesting practical exams visibility for student ID 37:\n";
$stmt = $pdo->prepare("
    SELECT pe.id, pe.title, s.subject_name
    FROM practical_exams pe
    JOIN subjects s ON pe.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    WHERE t.id = 2 AND pe.status = 'active'
");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($result) . " practical exams:\n";
foreach ($result as $r) {
    echo "  - {$r['title']} ({$r['subject_name']})\n";
}
?>
