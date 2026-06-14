<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin') && !hasRole('moderator')) {
    redirectDashboard($_SESSION['role_name'] ?? 'student');
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Question_Import_Template.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['trade_name', 'subject_name', 'question_type', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'correct_answer', 'difficulty', 'marks', 'negative_marks', 'explanation']);
fputcsv($output, ['General Education', 'Mathematics', 'mcq', 'What is 2+2?', '3', '4', '5', '6', 'B', 'Easy', '1.00', '0.00', 'Basic arithmetic rule.']);
fclose($output);
exit;