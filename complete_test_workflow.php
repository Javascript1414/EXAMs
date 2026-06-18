<?php
/**
 * COMPLETE END-TO-END TEST
 * Create exam → Complete it → Generate certificate → Test student access
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/certificate_generator.php';

echo "=" . str_repeat("=", 90) . "\n";
echo "🧪 COMPLETE END-TO-END SYSTEM TEST\n";
echo "=" . str_repeat("=", 90) . "\n\n";

try {
    // ========================================
    // STEP 1: CREATE NEW TEST EXAM
    // ========================================
    echo "STEP 1️⃣ : नया Exam बनाते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $exam_name = "COMPLETE TEST EXAM - " . date('Y-m-d H:i:s');
    $trade_id = 1; // CITS
    
    // Get a valid subject_id
    $subject = $pdo->query("SELECT id FROM subjects LIMIT 1")->fetch();
    $subject_id = $subject ? $subject['id'] : 1;
    
    $stmt = $pdo->prepare("INSERT INTO exams (exam_name, trade_id, subject_id, exam_type, total_marks, passing_marks, created_at) 
                          VALUES (?, ?, ?, 'MCQ', 10, 6, NOW())");
    $stmt->execute([$exam_name, $trade_id, $subject_id]);
    $exam_id = $pdo->lastInsertId();
    
    echo "   ✅ Exam बन गया\n";
    echo "      Exam ID: $exam_id\n";
    echo "      Name: $exam_name\n";
    echo "      Total Marks: 10\n";
    echo "      Pass Marks: 6 (60%)\n\n";

    // ========================================
    // STEP 2: CREATE TEST QUESTIONS
    // ========================================
    echo "STEP 2️⃣ : 10 Questions बनाते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $questions_data = [
        ['option_a' => 'Option A1', 'option_b' => 'Option B1', 'option_c' => 'Correct C1', 'option_d' => 'Option D1', 'correct' => 'C'],
        ['option_a' => 'Option A2', 'option_b' => 'Option B2', 'option_c' => 'Option C2', 'option_d' => 'Correct D2', 'correct' => 'D'],
        ['option_a' => 'Correct A3', 'option_b' => 'Option B3', 'option_c' => 'Option C3', 'option_d' => 'Option D3', 'correct' => 'A'],
        ['option_a' => 'Option A4', 'option_b' => 'Correct B4', 'option_c' => 'Option C4', 'option_d' => 'Option D4', 'correct' => 'B'],
        ['option_a' => 'Option A5', 'option_b' => 'Option B5', 'option_c' => 'Correct C5', 'option_d' => 'Option D5', 'correct' => 'C'],
        ['option_a' => 'Option A6', 'option_b' => 'Option B6', 'option_c' => 'Option C6', 'option_d' => 'Correct D6', 'correct' => 'D'],
        ['option_a' => 'Correct A7', 'option_b' => 'Option B7', 'option_c' => 'Option C7', 'option_d' => 'Option D7', 'correct' => 'A'],
        ['option_a' => 'Option A8', 'option_b' => 'Correct B8', 'option_c' => 'Option C8', 'option_d' => 'Option D8', 'correct' => 'B'],
        ['option_a' => 'Option A9', 'option_b' => 'Option B9', 'option_c' => 'Correct C9', 'option_d' => 'Option D9', 'correct' => 'C'],
        ['option_a' => 'Option A10', 'option_b' => 'Option B10', 'option_c' => 'Option C10', 'option_d' => 'Correct D10', 'correct' => 'D'],
    ];
    
    $question_ids = [];
    
    foreach ($questions_data as $idx => $q) {
        $stmt = $pdo->prepare("INSERT INTO questions (trade_id, subject_id, question_text, option_a, option_b, option_c, option_d, correct_answer, question_type) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'MCQ')");
        $stmt->execute([$trade_id, $subject_id, "Test Question " . ($idx+1), $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['correct']]);
        $question_ids[] = $pdo->lastInsertId();
    }
    
    echo "   ✅ 10 Questions बन गए\n";
    echo "      Question IDs: " . implode(", ", $question_ids) . "\n\n";

    // ========================================
    // STEP 3: LINK QUESTIONS TO EXAM
    // ========================================
    echo "STEP 3️⃣ : Questions को Exam से link करते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    foreach ($question_ids as $q_id) {
        $stmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        $stmt->execute([$exam_id, $q_id]);
    }
    
    echo "   ✅ सभी 10 Questions Exam से link हो गए\n\n";

    // ========================================
    // STEP 4: CREATE TEST STUDENT (or use existing)
    // ========================================
    echo "STEP 4️⃣ : Test Student के साथ attempt करते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $student_id = 29; // Use existing test student
    $student = $pdo->query("SELECT * FROM users WHERE id = $student_id")->fetch();
    
    echo "   ✅ Student मिल गया\n";
    echo "      ID: {$student['id']}\n";
    echo "      Name: {$student['full_name']}\n";
    echo "      Email: {$student['email']}\n\n";

    // ========================================
    // STEP 5: CREATE EXAM ATTEMPT
    // ========================================
    echo "STEP 5️⃣ : Exam attempt बनाते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, student_id, status, started_at) 
                          VALUES (?, ?, 'in_progress', NOW())");
    $stmt->execute([$exam_id, $student_id]);
    $attempt_id = $pdo->lastInsertId();
    
    echo "   ✅ Exam attempt शुरू हो गया\n";
    echo "      Attempt ID: $attempt_id\n\n";

    // ========================================
    // STEP 6: SUBMIT ANSWERS (8/10 = 80% = PASS)
    // ========================================
    echo "STEP 6️⃣ : 8 सही और 2 गलत answers submit करते हैं (80%)\n";
    echo str_repeat("-", 90) . "\n";
    
    $correct_answers = ['C', 'D', 'A', 'B', 'C', 'D', 'A', 'B']; // 8 correct
    $wrong_answers = ['A', 'A']; // 2 wrong (different from correct)
    
    // Submit correct answers
    for ($i = 0; $i < 8; $i++) {
        $stmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, selected_answer, is_correct) 
                              VALUES (?, ?, ?, 1)");
        $stmt->execute([$attempt_id, $question_ids[$i], $correct_answers[$i]]);
    }
    
    // Submit wrong answers
    for ($i = 0; $i < 2; $i++) {
        $stmt = $pdo->prepare("INSERT INTO exam_answers (attempt_id, question_id, selected_answer, is_correct) 
                              VALUES (?, ?, ?, 0)");
        $stmt->execute([$attempt_id, $question_ids[8 + $i], $wrong_answers[$i]]);
    }
    
    echo "   ✅ 8 सही answers (80%)\n";
    echo "   ✅ 2 गलत answers (20%)\n";
    echo "   ✅ Total: 8/10 = 80% = PASSING ✅\n\n";

    // ========================================
    // STEP 7: MARK ATTEMPT AS COMPLETED
    // ========================================
    echo "STEP 7️⃣ : Exam attempt को complete करते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $time_taken = 1800; // 30 minutes
    $percentage = 80;
    
    $stmt = $pdo->prepare("UPDATE exam_attempts 
                          SET status = 'completed', time_taken_seconds = ? 
                          WHERE id = ?");
    $stmt->execute([$time_taken, $attempt_id]);
    
    echo "   ✅ Exam attempt complete हो गया\n";
    echo "      Status: COMPLETED\n";
    echo "      Percentage: 80%\n";
    echo "      Time: 30 minutes\n\n";

    // ========================================
    // STEP 8: CREATE RESULT
    // ========================================
    echo "STEP 8️⃣ : Results record बनाते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO results (attempt_id, student_id, exam_id, obtained_marks, total_marks, percentage, is_passed, created_at) 
                          VALUES (?, ?, ?, 8, 10, 80, 1, NOW())");
    $stmt->execute([$attempt_id, $student_id, $exam_id]);
    $result_id = $pdo->lastInsertId();
    
    echo "   ✅ Result record बना दिया\n";
    echo "      Result ID: $result_id\n";
    echo "      Marks: 8/10\n";
    echo "      Percentage: 80%\n";
    echo "      Status: PASSED ✅\n\n";

    // ========================================
    // STEP 9: GENERATE CERTIFICATE
    // ========================================
    echo "STEP 9️⃣ : Certificate generate करते हैं\n";
    echo str_repeat("-", 90) . "\n";
    
    $result_data = [
        'obtained_marks' => 8,
        'total_marks' => 10,
        'percentage' => 80,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    try {
        $cert_result = insertCertificate($pdo, $student_id, $exam_id, $result_id, $result_data, 1);
        
        if ($cert_result && $cert_result['success']) {
            echo "   ✅ Certificate बना दिया\n";
            echo "      Certificate ID: " . $cert_result['certificate_id'] . "\n";
            echo "      Verification Code: " . $cert_result['verification_code'] . "\n";
            echo "      Grade: " . $cert_result['grade'] . "\n\n";
            
            $cert_id = $cert_result['certificate_id'];
            $verify_code = $cert_result['verification_code'];
        } else {
            echo "   ❌ Certificate बनाने में error आई\n";
            if (is_array($cert_result) && isset($cert_result['error'])) {
                echo "      Error: " . $cert_result['error'] . "\n";
            } else {
                echo "      Result: " . json_encode($cert_result) . "\n";
            }
            echo "\n";
            exit;
        }
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n\n";
        exit;
    }

    // ========================================
    // FINAL REPORT
    // ========================================
    echo "=" . str_repeat("=", 90) . "\n";
    echo "✅ COMPLETE TEST SUCCESSFUL\n";
    echo "=" . str_repeat("=", 90) . "\n\n";

    echo "📊 TEST RESULTS:\n";
    echo str_repeat("-", 90) . "\n";
    echo "   Exam ID:           $exam_id\n";
    echo "   Exam Name:         $exam_name\n";
    echo "   Total Questions:   10\n";
    echo "   Student:           {$student['full_name']}\n";
    echo "   Marks Obtained:    8/10\n";
    echo "   Percentage:        80%\n";
    echo "   Status:            PASSED ✅\n";
    echo "   Grade:             A (80%)\n";
    echo "   Certificate ID:    $cert_id\n";
    echo "   Verification Code: $verify_code\n\n";

    echo "🔗 IMPORTANT LINKS FOR TESTING:\n";
    echo str_repeat("-", 90) . "\n";
    echo "1️⃣  Student Certificate Page:\n";
    echo "   http://localhost/EXAMs/student/certificate_view.php?id=$attempt_id\n\n";
    
    echo "2️⃣  Download Certificate PDF:\n";
    echo "   http://localhost/EXAMs/student/certificate_view.php?id=$attempt_id&download=1\n\n";
    
    echo "3️⃣  Verify Certificate (Public):\n";
    echo "   http://localhost/EXAMs/verify.php?code=$verify_code\n\n";
    
    echo "4️⃣  Database Record:\n";
    echo "   Attempt ID: $attempt_id\n";
    echo "   Result ID: $result_id\n";
    echo "   Exam ID: $exam_id\n\n";

    echo "=" . str_repeat("=", 90) . "\n";
    echo "🎯 NOW TEST:\n";
    echo "=" . str_repeat("=", 90) . "\n\n";
    
    echo "✅ Student को यह link दो (Certificate देखने के लिए):\n";
    echo "   👉 http://localhost/EXAMs/student/certificate_view.php?id=$attempt_id\n\n";
    
    echo "✅ Verify link को public में share कर सकते हो:\n";
    echo "   👉 http://localhost/EXAMs/verify.php?code=$verify_code\n\n";
    
    echo "✅ Certificate को email भेज सकते हो:\n";
    echo "   👉 https://myaccount.google.com/security (Gmail App Password)\n";
    echo "   👉 http://localhost/EXAMs/gmail_setup.php (Paste here)\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

?>
