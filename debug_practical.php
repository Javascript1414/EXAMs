<?php
/**
 * Debug script to diagnose practical exams issue
 */
require_once 'config.php';
require_once 'includes/db.php';

// Check subjects
$stmt = $pdo->query('SELECT id, subject_name, trade_id FROM subjects WHERE id = 6');
$subject = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<h3>Subject ID 6:</h3>";
echo "<pre>" . json_encode($subject, JSON_PRETTY_PRINT) . "</pre>";

// Test the actual SQL query from getStudentPracticalExams
$student_id = 28;
$trade_id = 2;
$stmt = $pdo->prepare("
    SELECT pe.id, pe.exam_id, pe.subject_id, pe.title, pe.theory_marks, pe.practical_marks,
           pe.total_marks, pe.practical_pass_marks, pe.submission_deadline, 
           pe.evaluation_instructions, pe.status, s.subject_name, t.trade_name,
           ps.id as submission_id, ps.submitted_at, ps.is_late, ps.status as submission_status,
           pm.marks_obtained, pm.result_status as mark_result_status, pm.feedback
    FROM practical_exams pe
    JOIN subjects s ON pe.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id AND ps.student_id = ?
    LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
    WHERE t.id = ? AND pe.status = 'active'
    ORDER BY pe.submission_deadline DESC
");
$stmt->execute([$student_id, $trade_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Query Result with student_id=$student_id, trade_id=$trade_id:</h3>";
echo "<pre>" . json_encode($results, JSON_PRETTY_PRINT) . "</pre>";

// Let me also test if we query by pe.trade_id directly instead
$stmt = $pdo->query('SELECT id, full_name, trade_id FROM users ORDER BY id LIMIT 10');
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>All Users/Students:</h3>";
echo "<pre>" . json_encode($students, JSON_PRETTY_PRINT) . "</pre>";

// Check session to see who's logged in
echo "<h3>Session Data:</h3>";
echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";

// Check trades
$stmt = $pdo->query('SELECT id, trade_name FROM trades');
$trades = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Available Trades:</h3>";
echo "<pre>" . json_encode($trades, JSON_PRETTY_PRINT) . "</pre>";

// Check users table structure first
$stmt = $pdo->query('SHOW COLUMNS FROM users');
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
echo "<h3>Users table columns:</h3>";
echo "<pre>" . json_encode($cols, JSON_PRETTY_PRINT) . "</pre>";

// Check if test student has a trade_id
$stmt = $pdo->query('SELECT id, trade_id, full_name FROM users LIMIT 1');
$student = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h2>Debugging Practical Exams System</h2>";
echo "<h3>Student Info:</h3>";
echo "<pre>";
echo json_encode($student, JSON_PRETTY_PRINT);
echo "</pre>";

if ($student) {
    $trade_id = $student['trade_id'];
    
    // Check practical exams for that trade
    $stmt = $pdo->prepare('
        SELECT pe.id, pe.title, pe.subject_id, pe.trade_id, s.subject_name, t.trade_name, pe.status
        FROM practical_exams pe
        LEFT JOIN subjects s ON pe.subject_id = s.id
        LEFT JOIN trades t ON pe.trade_id = t.id
        ORDER BY pe.id DESC LIMIT 5
    ');
    $stmt->execute();
    $practicals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Practical Exams (All):</h3>";
    echo "<pre>";
    echo json_encode($practicals, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Check what getStudentPracticalExams returns
    require_once 'includes/practical_exam_functions.php';
    $result = getStudentPracticalExams($student['id'], $trade_id);
    
    echo "<h3>getStudentPracticalExams Result:</h3>";
    echo "<pre>";
    echo json_encode($result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Check practical_submissions table
    $stmt = $pdo->query('SHOW COLUMNS FROM practical_submissions');
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    echo "<h3>practical_submissions columns:</h3>";
    echo "<pre>";
    echo json_encode($columns, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Check if there are submissions
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM practical_submissions');
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h3>Total Submissions: " . $count['count'] . "</h3>";
}
?>
