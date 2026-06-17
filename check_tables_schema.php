<?php
$pdo = new PDO('mysql:host=127.0.0.1:3307;dbname=exams_lms;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "=== TABLE SCHEMA CHECK ===\n\n";

// Check exam_questions columns
echo "1. exam_questions COLUMNS:\n";
$result = $pdo->query("DESCRIBE exam_questions");
$cols = $result->fetchAll();
foreach ($cols as $col) {
    echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
}

// Check exam_answers columns
echo "\n2. exam_answers COLUMNS:\n";
$result = $pdo->query("DESCRIBE exam_answers");
$cols = $result->fetchAll();
foreach ($cols as $col) {
    echo "   - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
}

// Check data in exam_questions
echo "\n3. exam_questions DATA for exam_id = 2:\n";
$result = $pdo->query("SELECT * FROM exam_questions WHERE exam_id = 2");
$data = $result->fetchAll(PDO::FETCH_ASSOC);
echo "   Found: " . count($data) . " records\n";
if (count($data) > 0) {
    foreach ($data as $row) {
        print_r($row);
    }
}

// Check exam_answers data
echo "\n4. exam_answers DATA for attempts on exam_id = 2:\n";
$result = $pdo->query("SELECT ea.* FROM exam_answers ea 
                       JOIN exam_attempts e ON ea.attempt_id = e.id 
                       WHERE e.exam_id = 2");
$data = $result->fetchAll(PDO::FETCH_ASSOC);
echo "   Found: " . count($data) . " records\n";
if (count($data) > 0) {
    foreach ($data as $row) {
        print_r($row);
    }
}
?>
