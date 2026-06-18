<?php
/**
 * Certificate ID Generator
 * Format: CITS/24-25/Y/1414/A1
 * CITS = Course Code
 * 24-25 = Academic Year (Aug-July system)
 * Y = Year marker
 * 1414 = Student Registration Number
 * A1 = Exam Sequence (A1, A2, A3, A4...)
 */

function getAcademicYear($date) {
    $dt = new DateTime($date);
    $month = (int)$dt->format('m');
    $year = (int)$dt->format('Y');
    
    // Academic year starts in August (month 8)
    if ($month >= 8) {
        $start_year = $year;
        $end_year = $year + 1;
    } else {
        $start_year = $year - 1;
        $end_year = $year;
    }
    
    $start_yy = substr((string)$start_year, -2);
    $end_yy = substr((string)$end_year, -2);
    
    return "{$start_yy}-{$end_yy}";
}

function getExamSequenceLetter($sequence) {
    $letters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    $letter = $letters[min($sequence - 1, 9)];
    return $letter . $sequence;
}

function generateCertificateID($pdo, $student_id, $exam_id, $result_id, $result_date) {
    try {
        // Get student enrollment number
        $userStmt = $pdo->prepare("SELECT u.enrollment_no, t.trade_code FROM users u JOIN trades t ON u.trade_id = t.id WHERE u.id = ?");
        $userStmt->execute([$student_id]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            error_log("Certificate: User not found with trades join for ID: $student_id");
            return null;
        }
        
        if (!$user['enrollment_no']) {
            error_log("Certificate: Student ID $student_id has no enrollment_no");
            return null;
        }
        
        if (!$user['trade_code']) {
            error_log("Certificate: Student ID $student_id has no trade_code");
            return null;
        }
        
        $course_code = $user['trade_code'];
        $registration = $user['enrollment_no'];
        $academic_year = getAcademicYear($result_date);
        
        // Get exam sequence number for this student
        $seqStmt = $pdo->prepare("
            SELECT COUNT(*) as seq_count 
            FROM certificates 
            WHERE student_id = ? AND exam_sequence IS NOT NULL
        ");
        $seqStmt->execute([$student_id]);
        $seq_result = $seqStmt->fetch(PDO::FETCH_ASSOC);
        $exam_sequence = ($seq_result['seq_count'] ?? 0) + 1;
        
        $sequence_letter = getExamSequenceLetter($exam_sequence);
        
        // Generate certificate ID
        $cert_id = "{$course_code}/{$academic_year}/Y/{$registration}/{$sequence_letter}";
        
        // Store metadata for this certificate
        return [
            'certificate_id' => $cert_id,
            'course_code' => $course_code,
            'academic_year' => $academic_year,
            'student_registration' => $registration,
            'exam_sequence' => $exam_sequence
        ];
        
    } catch (Exception $e) {
        error_log("Certificate ID Generation Error: " . $e->getMessage());
        return null;
    }
}

function insertCertificate($pdo, $student_id, $exam_id, $result_id, $result_data, $generated_by = null) {
    try {
        // Generate certificate ID with metadata
        $cert_data = generateCertificateID($pdo, $student_id, $exam_id, $result_id, $result_data['created_at']);
        
        if (!$cert_data) {
            error_log("Certificate: Failed to generate certificate data for student_id=$student_id, exam_id=$exam_id");
            return ['success' => false, 'error' => 'Failed to generate certificate ID'];
        }
        
        $cert_id = $cert_data['certificate_id'];
        $verify_code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        
        // Calculate grade
        $percentage = (float)$result_data['percentage'];
        if ($percentage >= 90) $grade = 'A+';
        elseif ($percentage >= 80) $grade = 'A';
        elseif ($percentage >= 70) $grade = 'B';
        elseif ($percentage >= 60) $grade = 'C';
        else $grade = 'D';
        
        // Insert certificate with only columns that exist in the table
        $insert = $pdo->prepare("
            INSERT INTO certificates 
            (certificate_id, student_id, exam_id, result_id, score, percentage, 
             verification_code, generated_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $insert->execute([
            $cert_id,
            $student_id,
            $exam_id,
            $result_id,
            $result_data['obtained_marks'],
            $result_data['percentage'],
            $verify_code,
            $generated_by
        ]);
        
        return [
            'success' => true,
            'certificate_id' => $cert_id,
            'verification_code' => $verify_code,
            'grade' => $grade
        ];
        
    } catch (Exception $e) {
        error_log("Certificate Insert Error: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
