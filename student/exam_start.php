<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid session. Please try again.";
        redirect('/student/exams.php');
    }

    $exam_id = (int)($_POST['exam_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND status = 'published'");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();

    if (!$exam) redirect('/student/exams.php');

    // Check existing attempt
    $check = $pdo->prepare("SELECT id, status FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
    $check->execute([$exam_id, $_SESSION['user_id']]);
    $attempt = $check->fetch();

    if ($attempt) {
        if ($attempt['status'] === 'in_progress') {
            redirect('/student/exam_attempt.php?id=' . $exam_id);
        } else {
            redirect('/student/exams.php'); // Already submitted
        }
    }

    // Initialize New Attempt
    $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, status) VALUES (?, ?, 'in_progress')")->execute([$exam_id, $_SESSION['user_id']]);
    $attempt_id = $pdo->lastInsertId();

    // Fetch and Shuffle Questions if needed
    $qStmt = $pdo->prepare("SELECT question_id FROM exam_questions WHERE exam_id = ?");
    $qStmt->execute([$exam_id]);
    $questions = $qStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($exam['random_question_order']) { shuffle($questions); }

    // Map into exam_answers to establish state
    $insertAns = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, answer_status) VALUES (?, ?, 'not_visited')");
    foreach ($questions as $qid) {
        $insertAns->execute([$attempt_id, $qid]);
    }
    redirect('/student/exam_attempt.php?id=' . $exam_id);
}