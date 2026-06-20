<?php
/**
 * Google Form Exam Utility Functions
 * Include this file in your includes/functions.php or use it separately
 */

/**
 * Check if a teacher has permission to create Google Form exams for a subject
 */
function hasGoogleFormExamPermission($teacher_id, $subject_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id FROM google_form_exam_permissions
        WHERE teacher_id = ? AND subject_id = ? AND can_create_exams = 1
    ");
    $stmt->execute([$teacher_id, $subject_id]);
    return $stmt->fetch() ? true : false;
}

/**
 * Get all subjects a teacher can create Google Form exams for
 */
function getTeacherGoogleFormSubjects($teacher_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.subject_name, t.trade_name
        FROM subjects s
        JOIN trades t ON s.trade_id = t.id
        JOIN google_form_exam_permissions gfep ON s.id = gfep.subject_id
        WHERE gfep.teacher_id = ? AND gfep.can_create_exams = 1
        ORDER BY t.trade_name, s.subject_name
    ");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get teacher's Google Form exams
 */
function getTeacherGoogleFormExams($teacher_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT gfe.id, gfe.exam_title, gfe.subject_id, gfe.total_marks, gfe.pass_marks,
               gfe.exam_date, gfe.status, s.subject_name, t.trade_name,
               COUNT(DISTINCT gfea.student_id) as total_students,
               SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered
        FROM google_form_exams gfe
        LEFT JOIN subjects s ON gfe.subject_id = s.id
        LEFT JOIN trades t ON s.trade_id = t.id
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        WHERE gfe.created_by = ?
        GROUP BY gfe.id
        ORDER BY gfe.exam_date DESC
    ");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get students for a specific Google Form exam
 */
function getExamStudents($exam_id, $teacher_id) {
    global $pdo;
    
    // Verify teacher owns this exam
    $check = $pdo->prepare("
        SELECT id FROM google_form_exams WHERE id = ? AND created_by = ?
    ");
    $check->execute([$exam_id, $teacher_id]);
    if (!$check->fetch()) {
        return [];
    }
    
    $stmt = $pdo->prepare("
        SELECT gfea.id as attempt_id, gfea.student_id, u.full_name, u.email,
               gfea.marks_obtained, gfea.result_status, gfea.marks_entered_at
        FROM google_form_exam_attempts gfea
        JOIN users u ON gfea.student_id = u.id
        WHERE gfea.exam_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$exam_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get Google Form exams for a student
 */
function getStudentGoogleFormExams($student_id, $trade_id, $filter_subject = 0, $filter_status = 'all') {
    global $pdo;
    
    $query = "
        SELECT DISTINCT gfe.id, gfe.exam_title, gfe.subject_id, gfe.total_marks, gfe.pass_marks,
               gfe.exam_date, gfe.exam_time, gfe.status, gfe.instructions, gfe.google_form_link,
               s.subject_name, t.trade_name,
               gfea.attempt_time, gfea.marks_obtained, gfea.result_status, gfea.marks_entered_at
        FROM google_form_exams gfe
        JOIN subjects s ON gfe.subject_id = s.id
        JOIN trades t ON s.trade_id = t.id
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id AND gfea.student_id = ?
        WHERE s.trade_id = ? AND gfe.status = 'published'
    ";
    
    $params = [$student_id, $trade_id];
    
    if ($filter_subject > 0) {
        $query .= " AND gfe.subject_id = ?";
        $params[] = $filter_subject;
    }
    
    if ($filter_status === 'completed') {
        $query .= " AND gfea.marks_obtained IS NOT NULL";
    } elseif ($filter_status === 'pending') {
        $query .= " AND gfea.marks_obtained IS NULL AND gfe.exam_date <= NOW()";
    } elseif ($filter_status === 'upcoming') {
        $query .= " AND gfe.exam_date > NOW()";
    }
    
    $query .= " ORDER BY gfe.exam_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get exam statistics for a student
 */
function getStudentGoogleFormStats($student_id, $trade_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT gfe.id) as total_exams,
            SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN gfea.marks_obtained IS NULL AND gfe.exam_date <= NOW() THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN gfe.exam_date > NOW() THEN 1 ELSE 0 END) as upcoming,
            AVG(CASE WHEN gfea.marks_obtained IS NOT NULL THEN gfea.marks_obtained ELSE NULL END) as avg_marks,
            SUM(CASE WHEN gfea.result_status = 'pass' THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN gfea.result_status = 'fail' THEN 1 ELSE 0 END) as fail_count
        FROM google_form_exams gfe
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id AND gfea.student_id = ?
        WHERE gfe.status = 'published' AND gfe.subject_id IN (
            SELECT id FROM subjects WHERE trade_id = ?
        )
    ");
    $stmt->execute([$student_id, $trade_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Save marks for a student's Google Form exam
 * Returns: ['success' => bool, 'message' => string, 'result_status' => string]
 */
function saveGoogleFormMarks($attempt_id, $marks_obtained, $teacher_id) {
    global $pdo;
    
    try {
        // Get attempt details
        $attempt = $pdo->prepare("
            SELECT gfea.student_id, gfea.exam_id, gfe.total_marks, gfe.pass_marks
            FROM google_form_exam_attempts gfea
            JOIN google_form_exams gfe ON gfea.exam_id = gfe.id
            WHERE gfea.id = ?
        ");
        $attempt->execute([$attempt_id]);
        $attempt_data = $attempt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt_data) {
            return ['success' => false, 'message' => 'Attempt not found'];
        }

        // Validate marks
        if ($marks_obtained < 0 || $marks_obtained > $attempt_data['total_marks']) {
            return ['success' => false, 
                    'message' => 'Marks must be between 0 and ' . $attempt_data['total_marks']];
        }

        // Determine result status
        $result_status = $marks_obtained >= $attempt_data['pass_marks'] ? 'pass' : 'fail';

        // Update marks
        $update = $pdo->prepare("
            UPDATE google_form_exam_attempts
            SET marks_obtained = ?, result_status = ?, marks_entered_by = ?, marks_entered_at = NOW()
            WHERE id = ?
        ");
        $update->execute([$marks_obtained, $result_status, $teacher_id, $attempt_id]);

        return [
            'success' => true, 
            'message' => 'Marks saved successfully',
            'result_status' => $result_status
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate certificates for passing students in a Google Form exam
 */
function generateGoogleFormCertificates($exam_id, $subject_id, $generated_by = null) {
    global $pdo;
    
    try {
        // Get exam details
        $exam_stmt = $pdo->prepare("
            SELECT gfe.id, gfe.exam_title, gfe.total_marks, gfe.pass_marks, gfe.created_at,
                   s.subject_name, t.trade_id
            FROM google_form_exams gfe
            JOIN subjects s ON gfe.subject_id = s.id
            JOIN trades t ON s.trade_id = t.id
            WHERE gfe.id = ? AND gfe.subject_id = ?
        ");
        $exam_stmt->execute([$exam_id, $subject_id]);
        $exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) {
            return ['success' => false, 'message' => 'Exam not found', 'generated' => 0];
        }
        
        // Get passing students with their marks
        $stmt = $pdo->prepare("
            SELECT gfea.id as attempt_id, gfea.student_id, gfea.marks_obtained, 
                   gfea.result_status, u.full_name, u.enrollment_no, gfea.marks_entered_at
            FROM google_form_exam_attempts gfea
            JOIN users u ON gfea.student_id = u.id
            WHERE gfea.exam_id = ? AND gfea.subject_id = ?
            AND gfea.marks_obtained IS NOT NULL
            AND gfea.result_status = 'pass'
        ");
        $stmt->execute([$exam_id, $subject_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $generated = 0;
        
        // Generate certificate for each passing student
        foreach ($students as $student) {
            // Calculate percentage
            $percentage = ($student['marks_obtained'] / $exam['total_marks']) * 100;
            $percentage = round($percentage, 2);
            
            // Generate verification code
            $verify_code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
            
            // Generate certificate ID using attempt_id as reference
            // Format: GFORM/{DATE}/{STUDENT_ID}/{ATTEMPT_ID}
            $cert_ref = date('Y-m-d', strtotime($student['marks_entered_at'] ?: $exam['created_at']));
            $certificate_id = "GFORM/{$cert_ref}/{$student['student_id']}/{$student['attempt_id']}";
            
            // Check if certificate already exists for this attempt
            $checkCert = $pdo->prepare("
                SELECT id FROM certificates 
                WHERE student_id = ? AND exam_id = ? AND result_id = ?
            ");
            $checkCert->execute([$student['student_id'], $exam_id, $student['attempt_id']]);
            $existing = $checkCert->fetch();
            
            if (!$existing) {
                // Insert certificate record
                $insert = $pdo->prepare("
                    INSERT INTO certificates 
                    (certificate_id, student_id, exam_id, result_id, score, percentage, 
                     verification_code, generated_by, status, issued_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                
                $insert->execute([
                    $certificate_id,
                    $student['student_id'],
                    $exam_id,
                    $student['attempt_id'],
                    $student['marks_obtained'],
                    $percentage,
                    $verify_code,
                    $generated_by
                ]);
                
                $generated++;
            }
        }
        
        return ['success' => true, 'generated' => $generated];
    } catch (Exception $e) {
        error_log("Google Form Certificate Generation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage(), 'generated' => 0];
    }
}

/**
 * Get admin statistics for Google Form exams
 */
function getGoogleFormAdminStats() {
    global $pdo;
    
    $stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT gfe.id) as total_exams,
            COUNT(DISTINCT gfea.student_id) as total_students_appeared,
            SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered,
            SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) as certificates_generated
        FROM google_form_exams gfe
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        LEFT JOIN certificates c ON gfea.student_id = c.student_id 
            AND gfea.subject_id = c.subject_id
            AND gfea.exam_title = c.exam_title
            AND c.exam_source = 'Google Form'
    ")->fetch(PDO::FETCH_ASSOC);
    
    return $stats;
}

/**
 * Get exams created by each teacher
 */
function getExamsByTeacher() {
    global $pdo;
    
    $exams = $pdo->query("
        SELECT 
            u.full_name as teacher_name,
            u.id as teacher_id,
            COUNT(gfe.id) as total_exams,
            COUNT(DISTINCT gfea.student_id) as students_appeared,
            SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered
        FROM google_form_exams gfe
        LEFT JOIN users u ON gfe.created_by = u.id
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        GROUP BY gfe.created_by, u.full_name, u.id
        ORDER BY total_exams DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    return $exams;
}

/**
 * Publish a Google Form exam (make it visible to students)
 */
function publishGoogleFormExam($exam_id, $teacher_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE google_form_exams 
            SET status = 'published' 
            WHERE id = ? AND created_by = ?
        ");
        $stmt->execute([$exam_id, $teacher_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Exam not found or access denied'];
        }
        
        return ['success' => true, 'message' => 'Exam published successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Close a Google Form exam (prevent further access)
 */
function closeGoogleFormExam($exam_id, $teacher_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE google_form_exams 
            SET status = 'closed' 
            WHERE id = ? AND created_by = ?
        ");
        $stmt->execute([$exam_id, $teacher_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Exam not found or access denied'];
        }
        
        return ['success' => true, 'message' => 'Exam closed successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get Google Form exam details with related statistics
 */
function getGoogleFormExamDetails($exam_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT gfe.id, gfe.exam_title, gfe.subject_id, gfe.total_marks, gfe.pass_marks,
               gfe.exam_date, gfe.exam_time, gfe.status, gfe.instructions, 
               gfe.google_form_link, gfe.created_by, gfe.created_at,
               s.subject_name, t.trade_name, u.full_name as created_by_name,
               COUNT(DISTINCT gfea.student_id) as total_students,
               SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered,
               SUM(CASE WHEN gfea.result_status = 'pass' THEN 1 ELSE 0 END) as pass_count,
               SUM(CASE WHEN gfea.result_status = 'fail' THEN 1 ELSE 0 END) as fail_count,
               AVG(CASE WHEN gfea.marks_obtained IS NOT NULL THEN gfea.marks_obtained ELSE NULL END) as avg_marks
        FROM google_form_exams gfe
        LEFT JOIN subjects s ON gfe.subject_id = s.id
        LEFT JOIN trades t ON s.trade_id = t.id
        LEFT JOIN users u ON gfe.created_by = u.id
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        WHERE gfe.id = ?
        GROUP BY gfe.id
    ");
    $stmt->execute([$exam_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if student has completed a Google Form exam
 */
function hasStudentCompletedGoogleFormExam($student_id, $exam_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT marks_obtained FROM google_form_exam_attempts
        WHERE student_id = ? AND exam_id = ?
    ");
    $stmt->execute([$student_id, $exam_id]);
    $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($attempt && !is_null($attempt['marks_obtained']));
}

/**
 * Get student's result for a specific exam
 */
function getStudentExamResult($student_id, $exam_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT gfea.marks_obtained, gfea.result_status, gfea.marks_entered_at,
               gfea.attempt_time, gfe.total_marks, gfe.pass_marks
        FROM google_form_exam_attempts gfea
        JOIN google_form_exams gfe ON gfea.exam_id = gfe.id
        WHERE gfea.student_id = ? AND gfea.exam_id = ?
    ");
    $stmt->execute([$student_id, $exam_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
