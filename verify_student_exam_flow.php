<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>📋 STUDENT EXAM WORKFLOW VERIFICATION</h2>";
echo "<hr>";

// 1. Check Published Exams
echo "<h3>1️⃣ Published Exams with Linked Questions:</h3>";
$exams = $pdo->query("
    SELECT e.id, e.exam_name, e.status, e.trade_id,
           COUNT(DISTINCT eq.question_id) as linked_questions,
           COUNT(DISTINCT ea.id) as total_attempts
    FROM exams e
    LEFT JOIN exam_questions eq ON e.id = eq.exam_id
    LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
    WHERE e.status = 'published'
    GROUP BY e.id
")->fetchAll();

if (empty($exams)) {
    echo "<p style='color:red;'>❌ NO PUBLISHED EXAMS FOUND!</p>";
} else {
    foreach ($exams as $exam) {
        echo "<div style='border:1px solid #ddd;padding:10px;margin:5px 0;'>";
        echo "<strong>Exam ID {$exam['id']}: {$exam['exam_name']}</strong><br>";
        echo "Trade ID: {$exam['trade_id']}<br>";
        echo "Linked Questions: <span style='color:" . ($exam['linked_questions'] > 0 ? "green" : "red") . ";font-weight:bold;'>{$exam['linked_questions']}</span><br>";
        echo "Attempts: {$exam['total_attempts']}<br>";
        echo "</div>";
    }
}

// 2. Check Student Account
echo "<h3>2️⃣ Student Accounts Available:</h3>";
$students = $pdo->query("
    SELECT u.id, u.username, u.email, u.trade_id, u.status,
           COUNT(DISTINCT ea.id) as attempts
    FROM users u
    LEFT JOIN exam_attempts ea ON u.id = ea.student_id
    WHERE u.role = 'student'
    LIMIT 5
")->fetchAll();

if (empty($students)) {
    echo "<p style='color:red;'>❌ NO STUDENT ACCOUNTS FOUND!</p>";
} else {
    foreach ($students as $st) {
        echo "<div style='border:1px solid #ddd;padding:10px;margin:5px 0;'>";
        echo "<strong>📚 {$st['username']}</strong> (ID: {$st['id']})<br>";
        echo "Email: {$st['email']}<br>";
        echo "Trade ID: {$st['trade_id']}<br>";
        echo "Status: <span style='color:" . ($st['status'] === 'active' ? "green" : "red") . ";'>{$st['status']}</span><br>";
        echo "Attempts: {$st['attempts']}<br>";
        echo "</div>";
    }
}

// 3. Check Questions in Database
echo "<h3>3️⃣ Questions Available in Database:</h3>";
$questions = $pdo->query("
    SELECT COUNT(*) as total FROM questions
")->fetch();
echo "<p>Total Questions: <strong>{$questions['total']}</strong></p>";

// 4. Latest Exam Attempts
echo "<h3>4️⃣ Recent Exam Attempts (Last 10):</h3>";
$attempts = $pdo->query("
    SELECT ea.id, ea.exam_id, ea.student_id, e.exam_name, u.username, ea.status, ea.started_at,
           COUNT(DISTINCT ans.id) as answers_recorded
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    JOIN users u ON ea.student_id = u.id
    LEFT JOIN exam_answers ans ON ea.id = ans.attempt_id
    GROUP BY ea.id
    ORDER BY ea.started_at DESC
    LIMIT 10
")->fetchAll();

if (empty($attempts)) {
    echo "<p>No attempts yet.</p>";
} else {
    echo "<table border='1' style='width:100%;border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Attempt ID</th><th>Student</th><th>Exam</th><th>Status</th><th>Started At</th><th>Answers</th></tr>";
    foreach ($attempts as $att) {
        echo "<tr>";
        echo "<td>{$att['id']}</td>";
        echo "<td>{$att['username']}</td>";
        echo "<td>{$att['exam_name']}</td>";
        echo "<td><span style='color:" . ($att['status'] === 'in_progress' ? "orange" : "green") . ";'>{$att['status']}</span></td>";
        echo "<td>{$att['started_at']}</td>";
        echo "<td>{$att['answers_recorded']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<h3>✅ ACTION PLAN:</h3>";
echo "<ol>";
echo "<li><strong>Login as Student:</strong> Use any student account (username: student, etc.)</li>";
echo "<li><strong>View Available Exams:</strong> Go to My Exams / Exams page</li>";
echo "<li><strong>Click Start Exam:</strong> On a published exam with linked questions</li>";
echo "<li><strong>Read Instructions:</strong> Check the checkbox and click 'Start Examination'</li>";
echo "<li><strong>Take Exam:</strong> Questions should display in exam_attempt.php</li>";
echo "</ol>";

echo "<p style='margin-top:20px;padding:15px;background:#e8f5e9;border:2px solid #4CAF50;'>";
echo "✅ <strong>Complete End-to-End Testing:</strong> If all sections above show green/positive data, the exam flow is working!";
echo "</p>";
?>
