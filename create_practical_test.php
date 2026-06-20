<?php
/**
 * Create Practical Exam via API
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/practical_exam_functions.php';

// Set teacher session
$_SESSION['user_id'] = 1;  // Assuming teacher user ID
$_SESSION['role_name'] = 'teacher';

// First, verify teacher and subjects exist
$teacherStmt = $pdo->prepare("SELECT id, trade_id FROM users WHERE email = ?");
$teacherStmt->execute(['teacher@example.com']);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    echo "❌ Teacher not found\n";
    exit;
}

echo "✓ Teacher found - ID: " . $teacher['id'] . ", Trade: " . $teacher['trade_id'] . "\n";

// Get a subject for the teacher
$subjStmt = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? LIMIT 1");
$subjStmt->execute([$teacher['trade_id']]);
$subject = $subjStmt->fetch();

if (!$subject) {
    echo "❌ No subjects found for trade " . $teacher['trade_id'] . "\n";
    exit;
}

echo "✓ Subject found - ID: " . $subject['id'] . "\n";

// Create practical exam
$result = createPracticalExam([
    'exam_id' => 0,
    'subject_id' => $subject['id'],
    'trade_id' => $teacher['trade_id'],
    'title' => 'Web Development Practical Project',
    'description' => 'Students will create a responsive website using HTML, CSS, and Bootstrap.',
    'theory_marks' => 80,
    'practical_marks' => 20,
    'practical_pass_marks' => 10,
    'submission_deadline' => date('Y-m-d H:i', strtotime('+7 days')),
    'evaluation_instructions' => 'Check for: Responsiveness, Code quality, CSS styling, User experience',
    'created_by' => $teacher['id']
]);

if ($result['success']) {
    echo "✅ Practical exam created successfully!\n";
    echo "Practical ID: " . $result['practical_exam_id'] . "\n";
    echo "Subject ID: " . $subject['id'] . "\n";
} else {
    echo "❌ Error: " . $result['message'] . "\n";
}

?>
