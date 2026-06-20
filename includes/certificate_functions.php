<?php
/**
 * Certificate Generation & Email System
 */

// Note: This file is included from other files, paths handled by calling file

/**
 * Generate certificate when all marks are complete
 */
function checkAndIssueCertificate($student_id, $practical_exam_id) {
    global $pdo;
    
    try {
        // Get practical exam details
        $stmt = $pdo->prepare("
            SELECT pe.*, s.subject_id, s.trade_id
            FROM practical_exams pe
            JOIN subjects s ON pe.subject_id = s.id
            WHERE pe.id = ?
        ");
        $stmt->execute([$practical_exam_id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) return false;
        
        // Get student marks
        $stmt = $pdo->prepare("
            SELECT 
                ps.id, ps.student_id,
                COALESCE(pm.marks_obtained, 0) as practical_marks,
                COALESCE(pm.result_status, 'pending') as practical_status
            FROM practical_submissions ps
            LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
            WHERE ps.practical_exam_id = ? AND ps.student_id = ?
        ");
        $stmt->execute([$practical_exam_id, $student_id]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission || $submission['practical_status'] === 'pending') {
            return false; // Marks not complete
        }
        
        // Calculate total marks
        $theory_marks = $exam['theory_marks'] ?? 0;
        $practical_marks = $submission['practical_marks'];
        $total_marks = $theory_marks + $practical_marks;
        $passing_marks = ($exam['practical_pass_marks'] ?? 50); // 50% passing
        $is_passed = $total_marks >= $passing_marks;
        
        // Insert certificate
        $certificate_id = 'CERT-' . strtoupper(uniqid(date('Ym')));
        $stmt = $pdo->prepare("
            INSERT INTO certificates 
            (certificate_id, student_id, practical_exam_id, subject_id, trade_id, 
             theory_marks, practical_marks, total_marks, percentage, is_passed, issued_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                issued_at = NOW(),
                percentage = ?
        ");
        
        $percentage = $total_marks > 0 ? round(($total_marks / ($theory_marks + $exam['practical_marks'])) * 100) : 0;
        
        $stmt->execute([
            $certificate_id, $student_id, $practical_exam_id, $exam['subject_id'], $exam['trade_id'],
            $theory_marks, $practical_marks, $total_marks, $percentage, ($is_passed ? 1 : 0),
            $percentage
        ]);
        
        return [
            'success' => true,
            'certificate_id' => $certificate_id,
            'total_marks' => $total_marks,
            'percentage' => $percentage,
            'is_passed' => $is_passed
        ];
        
    } catch (Exception $e) {
        error_log("Certificate Generation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Send certificate via email
 */
function sendCertificateEmail($student_id, $certificate_id) {
    global $pdo;
    
    try {
        require_once 'includes/PHPMailer/PHPMailerAutoload.php';
        
        // Get certificate details
        $stmt = $pdo->prepare("
            SELECT c.*, u.email, u.full_name, s.subject_name, t.trade_name
            FROM certificates c
            JOIN users u ON c.student_id = u.id
            JOIN subjects s ON c.subject_id = s.id
            JOIN trades t ON c.trade_id = t.id
            WHERE c.certificate_id = ? AND c.student_id = ?
        ");
        $stmt->execute([$certificate_id, $student_id]);
        $cert = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cert) return false;
        
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom('noreply@citslms.com', 'CITS LMS');
        $mail->addAddress($cert['email'], $cert['full_name']);
        $mail->Subject = 'Your Certificate - ' . $cert['subject_name'];
        
        $mail->isHTML(true);
        $mail->Body = "
            <h2>Congratulations!</h2>
            <p>Dear {$cert['full_name']},</p>
            <p>You have successfully completed the practical exam for <strong>{$cert['subject_name']}</strong>.</p>
            <p>
                <strong>Certificate ID:</strong> {$cert['certificate_id']}<br>
                <strong>Trade:</strong> {$cert['trade_name']}<br>
                <strong>Theory Marks:</strong> {$cert['theory_marks']}<br>
                <strong>Practical Marks:</strong> {$cert['practical_marks']}<br>
                <strong>Total Marks:</strong> {$cert['total_marks']}<br>
                <strong>Percentage:</strong> {$cert['percentage']}%<br>
                <strong>Status:</strong> " . ($cert['is_passed'] ? 'PASSED' : 'FAILED') . "
            </p>
            <p>You can download your certificate from your dashboard.</p>
            <p>Best regards,<br>CITS LMS Team</p>
        ";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Email Send Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send marks notification email to student
 */
function sendMarksNotificationEmail($student_id, $practical_exam_id, $marks, $feedback) {
    global $pdo;
    
    try {
        // Get student email
        $stmt = $pdo->prepare("
            SELECT u.email, u.full_name, pe.title, s.subject_name
            FROM users u
            JOIN practical_exams pe ON 1=1
            JOIN subjects s ON pe.subject_id = s.id
            WHERE u.id = ? AND pe.id = ?
        ");
        $stmt->execute([$student_id, $practical_exam_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) return false;
        
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom('noreply@citslms.com', 'CITS LMS');
        $mail->addAddress($data['email'], $data['full_name']);
        $mail->Subject = 'Your Practical Marks - ' . $data['subject_name'];
        
        $mail->isHTML(true);
        $mail->Body = "
            <h2>Marks Assigned</h2>
            <p>Dear {$data['full_name']},</p>
            <p>Your practical exam has been marked and graded.</p>
            <p>
                <strong>Subject:</strong> {$data['subject_name']}<br>
                <strong>Practical:</strong> {$data['title']}<br>
                <strong>Marks Obtained:</strong> {$marks}<br>
                <strong>Feedback:</strong> {$feedback}
            </p>
            <p>Check your dashboard to view your complete results.</p>
            <p>Best regards,<br>CITS LMS Team</p>
        ";
        
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Email Send Error: " . $e->getMessage());
        return false;
    }
}
?>
