<?php
/**
 * Dummy Exam Creation & Certificate Generation Script
 * Creates 32-question exam, submits correct answers, generates & emails certificate
 */

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/certificate_generator.php';

// ============ CONFIGURATION ============
$email_to = 'soumyajitsantra699@gmail.com';
$student_name = 'Test Student';
$student_email = $email_to;
$trade_id = 1; // Default CITS trade
$exam_name = 'Dummy Exam - ' . date('Y-m-d H:i');
$num_questions = 32;

echo "🎯 Starting Dummy Exam & Certificate Generation...\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    // ========== STEP 1: Create or Get Student ==========
    echo "📝 Step 1: Creating test student...\n";
    
    // Check if test student exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role_id = (SELECT id FROM roles WHERE name = 'student')");
    $stmt->execute(['dummy_student@test.local']);
    $student = $stmt->fetch();
    
    if (!$student) {
        // Create test student
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, full_name, role_id, trade_id, status, approval_status, email_verified, enrollment_no, created_at)
            VALUES (?, ?, ?, (SELECT id FROM roles WHERE name = 'student'), ?, 'active', 'approved', 1, ?, NOW())
        ");
        
        $stmt->execute([
            'dummy_student@test.local',
            password_hash('test123', PASSWORD_DEFAULT),
            $student_name,
            $trade_id,
            '1414' // Default enrollment number
        ]);
        
        $student_id = $pdo->lastInsertId();
        echo "   ✅ Student created: ID=$student_id\n";
    } else {
        $student_id = $student['id'];
        echo "   ✅ Using existing student: ID=$student_id\n";
    }
    
    // ========== STEP 2: Create Exam ==========
    echo "\n📚 Step 2: Creating exam with 32 questions...\n";
    
    // First get or create a subject
    $subjectStmt = $pdo->prepare("SELECT id FROM subjects WHERE trade_id = ? LIMIT 1");
    $subjectStmt->execute([$trade_id]);
    $subject = $subjectStmt->fetch();
    
    if (!$subject) {
        $pdo->prepare("INSERT INTO subjects (trade_id, subject_name, created_at) VALUES (?, ?, NOW())")->execute([$trade_id, 'Dummy Subject']);
        $subject_id = $pdo->lastInsertId();
    } else {
        $subject_id = $subject['id'];
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO exams (exam_name, trade_id, subject_id, exam_type, duration_minutes, total_marks, passing_marks, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $exam_name,
        $trade_id,
        $subject_id,
        'Practice Test',
        120, // 2 hours
        32,  // total marks (1 per question)
        13,  // passing marks (40% of 32)
        'published',
        1    // Admin user
    ]);
    
    $exam_id = $pdo->lastInsertId();
    echo "   ✅ Exam created: ID=$exam_id, Name=$exam_name\n";
    
    // ========== STEP 3: Create 32 Questions ==========
    echo "\n❓ Step 3: Creating 32 questions...\n";
    
    $questions = [];
    $total_marks = 32; // 1 mark per question
    
    for ($i = 1; $i <= $num_questions; $i++) {
        $stmt = $pdo->prepare("
            INSERT INTO questions (trade_id, subject_id, question_type, question_text, option_a, option_b, option_c, option_d, correct_answer, marks, status, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $question_text = "Question $i: What is the correct answer to question $i?";
        $stmt->execute([
            $trade_id,
            $subject_id,
            'mcq',
            $question_text,
            'Option A - Incorrect',
            'Option B - Correct Answer ✓',
            'Option C - Incorrect',
            'Option D - Incorrect',
            'B', // correct answer
            1, // marks
            'active',
            1  // created by admin
        ]);
        
        $question_id = $pdo->lastInsertId();
        $questions[] = $question_id;
        
        // Link question to exam
        $linkStmt = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
        $linkStmt->execute([$exam_id, $question_id]);
        
        if ($i % 8 === 0) echo "   ✅ Questions 1-$i created\n";
    }
    
    echo "   ✅ All 32 questions created successfully\n";
    
    // ========== STEP 4: Create Exam Attempt ==========
    echo "\n🚀 Step 4: Creating exam attempt...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO exam_attempts (exam_id, student_id, started_at, status)
        VALUES (?, ?, NOW(), 'in_progress')
    ");
    
    $stmt->execute([$exam_id, $student_id]);
    $attempt_id = $pdo->lastInsertId();
    echo "   ✅ Exam attempt created: ID=$attempt_id\n";
    
    // ========== STEP 5: Submit All Correct Answers ==========
    echo "\n✍️ Step 5: Submitting correct answers for all 32 questions...\n";
    
    $total_obtained = 0;
    
    foreach ($questions as $idx => $question_id) {
        // Get the question marks
        $stmt = $pdo->prepare("SELECT marks, correct_answer FROM questions WHERE id = ?");
        $stmt->execute([$question_id]);
        $q = $stmt->fetch();
        
        $marks = $q['marks'];
        $total_obtained += $marks;
        
        // Submit answer to exam_answers
        $stmt = $pdo->prepare("
            INSERT INTO exam_answers (attempt_id, question_id, selected_answer, is_correct, answer_status, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $attempt_id,
            $question_id,
            $q['correct_answer'], // Answer: B
            1, // is_correct
            'answered'
        ]);
    }
    
    echo "   ✅ All 32 answers submitted - Obtained: $total_obtained/$total_marks marks\n";
    
    // ========== STEP 6: Mark Attempt as Completed ==========
    echo "\n✔️ Step 6: Completing exam attempt...\n";
    
    $percentage = ($total_obtained / $total_marks) * 100;
    $is_passed = $percentage >= 40;
    $time_taken_seconds = 1800; // 30 minutes
    
    $stmt = $pdo->prepare("
        UPDATE exam_attempts 
        SET status = 'submitted', submitted_at = NOW(), score = ?, percentage = ?, time_taken_seconds = ?
        WHERE id = ?
    ");
    $stmt->execute([$total_obtained, $percentage, $time_taken_seconds, $attempt_id]);
    
    echo "   ✅ Exam completed\n";
    echo "   📊 Marks: $total_obtained/$total_marks\n";
    echo "   📈 Percentage: " . round($percentage, 2) . "%\n";
    echo "   ✔️ Status: " . ($is_passed ? 'PASSED ✅' : 'FAILED ❌') . "\n";
    
    // ========== STEP 7: Generate Certificate ==========
    echo "\n🎓 Step 7: Generating certificate...\n";
    
    if ($is_passed) {
        try {
            // Create result record first (needed for certificate)
            // Check if results table exists and what columns it has
            $result_data = [
                'obtained_marks' => $total_obtained,
                'total_marks' => $total_marks,
                'percentage' => round($percentage, 2),
                'student_id' => $student_id,
                'exam_id' => $exam_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // First check student setup
            $checkStmt = $pdo->prepare("SELECT u.enrollment_no, t.trade_code FROM users u LEFT JOIN trades t ON u.trade_id = t.id WHERE u.id = ?");
            $checkStmt->execute([$student_id]);
            $studentCheck = $checkStmt->fetch();
            echo "   📌 Student Enrollment: " . ($studentCheck['enrollment_no'] ?? 'NOT SET') . "\n";
            echo "   📌 Trade Code: " . ($studentCheck['trade_code'] ?? 'NOT SET') . "\n";
            
            // Try to generate certificate with custom ID
            $cert_result = insertCertificate(
                $pdo,
                $student_id,
                $exam_id,
                $attempt_id,
                $result_data,
                1 // generated_by (admin)
            );
            
            if (is_array($cert_result) && isset($cert_result['success']) && $cert_result['success']) {
                $cert_id = $cert_result['certificate_id'];
                echo "   ✅ Certificate generated\n";
                echo "   📋 Certificate ID: $cert_id\n";
            } else if ($cert_result === false) {
                echo "   ⚠️ Certificate generation failed - check enrollment_no and trade_code in admin config\n";
                $cert_id = null;
            } else {
                echo "   ✅ Certificate generated\n";
                $cert_id = $cert_result['certificate_id'] ?? 'GENERATED';
                echo "   📋 Certificate ID: $cert_id\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️ Certificate error: " . $e->getMessage() . "\n";
            $cert_id = null;
        }
    } else {
        echo "   ⚠️ Certificate not generated (exam not passed)\n";
        $cert_id = null;
    }
    
    // ========== STEP 8: Email Certificate ==========
    echo "\n📧 Step 8: Emailing certificate...\n";
    
    if ($cert_id && $is_passed) {
        try {
            // Load PHPMailer
            require_once 'vendor/autoload.php';
            
            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = getenv('SMTP_USER') ?: 'your-email@gmail.com';
            $mail->Password = getenv('SMTP_PASS') ?: 'your-app-password';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            $mail->setFrom('noreply@exams.local', 'EXAMs Learning System');
            $mail->addAddress($email_to, $student_name);
            $mail->isHTML(true);
            
            $mail->Subject = "🎓 Your Certificate is Ready! - $cert_id";
            
            $cert_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/EXAMs/student/certificate_view.php?id=' . $attempt_id;
            $download_url = $cert_url . '&download=1';
            
            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2>🎉 Congratulations!</h2>
                <p>Dear $student_name,</p>
                <p>You have successfully completed the exam <strong>$exam_name</strong> and earned your certificate!</p>
                
                <div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <strong>📋 Certificate Details:</strong><br/>
                    Certificate ID: <strong>$cert_id</strong><br/>
                    Marks Obtained: <strong>$total_obtained/$total_marks</strong><br/>
                    Percentage: <strong>" . round($percentage, 2) . "%</strong><br/>
                </div>
                
                <p>
                    <a href='$cert_url' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>
                        View Certificate
                    </a>
                    <a href='$download_url' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>
                        Download PDF
                    </a>
                </p>
                
                <p>Thank you for using EXAMs Learning System!</p>
                <hr>
                <p style='color: #666; font-size: 12px;'>© 2026 EXAMs Learning System. All rights reserved.</p>
            </body>
            </html>
            ";
            
            $mail->send();
            echo "   ✅ Certificate emailed to: $email_to\n";
            
        } catch (Exception $e) {
            echo "   ⚠️ Email error: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()) . "\n";
            echo "   💡 Make sure SMTP credentials are configured\n";
        }
    }
    
    // ========== SUMMARY ==========
    echo "\n" . "=" . str_repeat("=", 60) . "\n";
    echo "✅ COMPLETED SUCCESSFULLY!\n";
    echo "=" . str_repeat("=", 60) . "\n\n";
    
    echo "📊 Summary:\n";
    echo "   • Student ID: $student_id\n";
    echo "   • Student Email: dummy_student@test.local\n";
    echo "   • Exam ID: $exam_id\n";
    echo "   • Exam Name: $exam_name\n";
    echo "   • Questions Created: $num_questions\n";
    echo "   • Attempt ID: $attempt_id\n";
    echo "   • Marks: $total_obtained/$total_marks\n";
    echo "   • Percentage: " . round($percentage, 2) . "%\n";
    echo "   • Status: " . ($is_passed ? 'PASSED ✅' : 'FAILED ❌') . "\n";
    if ($cert_id) {
        echo "   • Certificate ID: $cert_id\n";
        echo "   • Certificate Status: Generated & Emailed ✅\n";
    }
    
    echo "\n🔗 Links:\n";
    echo "   • View Results: http://localhost/EXAMs/student/results.php\n";
    echo "   • View Certificate: http://localhost/EXAMs/student/certificates.php\n";
    echo "   • View Exam: http://localhost/EXAMs/admin/exams.php?id=$exam_id\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

?>
