<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$attempt_id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

// Fetch attempt
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ? AND student_id = ? AND status = 'in_progress'");
$stmt->execute([$attempt_id, $_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    redirect('/student/exams.php');
}

// Fetch Exam Config
$exam = $pdo->prepare("SELECT total_marks, passing_marks, negative_marking_enabled FROM exams WHERE id = ?");
$exam->execute([$attempt['exam_id']]);
$examConfig = $exam->fetch();

// Calculate Results
$aStmt = $pdo->prepare("SELECT ea.id, ea.selected_answer, q.correct_answer, q.marks, q.negative_marks 
                        FROM exam_answers ea JOIN questions q ON ea.question_id = q.id 
                        WHERE ea.attempt_id = ?");
$aStmt->execute([$attempt_id]);
$answers = $aStmt->fetchAll();

$obtained = 0.00;
$updateAnswer = $pdo->prepare("UPDATE exam_answers SET is_correct = ? WHERE id = ?");

foreach ($answers as $ans) {
    $awarded = 0.00;
    $is_correct = 0;
    if ($ans['selected_answer'] !== null) {
        if ($ans['selected_answer'] === $ans['correct_answer']) {
            $awarded = (float)$ans['marks'];
            $is_correct = 1;
        } else if ($examConfig['negative_marking_enabled']) {
            $awarded = -abs((float)$ans['negative_marks']);
        }
    }
    $obtained += $awarded;
    $updateAnswer->execute([$is_correct, $ans['id']]);
}

$obtained = max(0, $obtained); // Floor at 0
$percentage = ($obtained / $examConfig['total_marks']) * 100;
$isPassed = ($obtained >= $examConfig['passing_marks']) ? 1 : 0;
$timeTaken = time() - strtotime($attempt['started_at']);

// Close attempt
$pdo->prepare("UPDATE exam_attempts SET status = 'submitted', submitted_at = CURRENT_TIMESTAMP, time_taken_seconds = ?, score = ?, percentage = ? WHERE id = ?")->execute([$timeTaken, $obtained, $percentage, $attempt_id]);

// Create Result Record
$resStmt = $pdo->prepare("INSERT INTO results (attempt_id, student_id, exam_id, total_marks, obtained_marks, percentage, is_passed) VALUES (?, ?, ?, ?, ?, ?, ?)");
$resStmt->execute([$attempt_id, $_SESSION['user_id'], $attempt['exam_id'], $examConfig['total_marks'], $obtained, $percentage, $isPassed]);

// Redirect to result page (Implementation in next phase, redirecting to index for now)
$_SESSION['success_message'] = "Exam submitted successfully! " . ($isPassed ? 'Congratulations, you passed!' : 'Unfortunately, you did not pass.');
redirect('/student/index.php');
?>