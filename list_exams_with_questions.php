<?php
require 'includes/db.php';

// Get exams with questions
$stmt = $pdo->query('SELECT e.id, e.exam_name, COUNT(eq.id) as q_count 
                      FROM exams e 
                      LEFT JOIN exam_questions eq ON e.id = eq.exam_id 
                      GROUP BY e.id 
                      HAVING q_count > 0
                      LIMIT 5');
$exams = $stmt->fetchAll();

if (empty($exams)) {
    echo "No exams with questions found!";
} else {
    foreach ($exams as $exam) {
        echo $exam['id'] . " | " . $exam['exam_name'] . " | " . $exam['q_count'] . " questions<br>";
    }
}
?>
