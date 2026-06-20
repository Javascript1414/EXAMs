<?php
/**
 * Practical Exam Management Functions
 * Handles creation, submission, marking, and certificate generation
 * for theory + practical exams
 */

/**
 * Create a new practical exam
 * Returns: ['success' => bool, 'id' => int, 'message' => string]
 */
function createPracticalExam($data) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO practical_exams 
            (theory_exam_id, subject_id, trade_id, title, description, theory_marks, practical_marks,
             total_marks, practical_pass_marks, submission_deadline, evaluation_instructions, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $stmt->execute([
            $data['theory_exam_id'] ?? null,  // Link to theory exam
            $data['subject_id'],
            $data['trade_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['theory_marks'] ?? 80,
            $data['practical_marks'] ?? 20,
            ($data['theory_marks'] ?? 80) + ($data['practical_marks'] ?? 20),
            $data['practical_pass_marks'] ?? 10,
            $data['submission_deadline'],
            $data['evaluation_instructions'] ?? null,
            $data['created_by']
        ]);
        
        $practical_id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'id' => $practical_id,
            'message' => 'Practical exam created successfully (ready to link and publish)'
        ];
    } catch (Exception $e) {
        error_log("Create Practical Exam Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Link practical exam to theory exam
 * Ensures one-to-one relationship
 */
function linkPracticalToTheoryExam($practical_exam_id, $theory_exam_id, $teacher_id = null) {
    global $pdo;
    
    try {
        // Verify practical exam exists and belongs to user
        if ($teacher_id) {
            $verify = $pdo->prepare("SELECT id FROM practical_exams WHERE id = ? AND created_by = ?");
            $verify->execute([$practical_exam_id, $teacher_id]);
        } else {
            $verify = $pdo->prepare("SELECT id FROM practical_exams WHERE id = ?");
            $verify->execute([$practical_exam_id]);
        }
        
        if (!$verify->fetch()) {
            return ['success' => false, 'message' => 'Practical exam not found'];
        }
        
        // Check if theory exam exists
        $exam_check = $pdo->prepare("SELECT id, exam_name, subject_id, total_marks FROM exams WHERE id = ?");
        $exam_check->execute([$theory_exam_id]);
        $theory_exam = $exam_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$theory_exam) {
            return ['success' => false, 'message' => 'Theory exam not found'];
        }
        
        // Check if another practical exam is already linked to this theory exam
        $existing = $pdo->prepare("SELECT id FROM practical_exams WHERE theory_exam_id = ? AND id != ?");
        $existing->execute([$theory_exam_id, $practical_exam_id]);
        
        if ($existing->fetch()) {
            return ['success' => false, 'message' => 'Another practical exam is already linked to this theory exam'];
        }
        
        // Update practical exam with theory exam link
        $stmt = $pdo->prepare("UPDATE practical_exams SET theory_exam_id = ? WHERE id = ?");
        $stmt->execute([$theory_exam_id, $practical_exam_id]);
        
        return [
            'success' => true,
            'message' => 'Practical exam linked to theory exam successfully',
            'theory_exam_name' => $theory_exam['exam_name'],
            'theory_exam_marks' => $theory_exam['total_marks']
        ];
    } catch (Exception $e) {
        error_log("Link Exam Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Publish practical exam (make available to students)
 * Requires theory exam to be linked first
 */
function publishPracticalExam($practical_exam_id, $teacher_id = null) {
    global $pdo;
    
    try {
        // Verify practical exam exists and belongs to user
        if ($teacher_id) {
            $verify = $pdo->prepare("SELECT theory_exam_id, status FROM practical_exams WHERE id = ? AND created_by = ?");
            $verify->execute([$practical_exam_id, $teacher_id]);
        } else {
            $verify = $pdo->prepare("SELECT theory_exam_id, status FROM practical_exams WHERE id = ?");
            $verify->execute([$practical_exam_id]);
        }
        
        $practical = $verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$practical) {
            return ['success' => false, 'message' => 'Practical exam not found'];
        }
        
        if (!$practical['theory_exam_id']) {
            return ['success' => false, 'message' => 'Cannot publish: Practical exam must be linked to a theory exam first'];
        }
        
        // Publish the practical exam
        $stmt = $pdo->prepare("UPDATE practical_exams SET status = 'active', published = TRUE, published_at = NOW() WHERE id = ?");
        $stmt->execute([$practical_exam_id]);
        
        // Publish the linked theory exam
        $publish_theory = $pdo->prepare("UPDATE exams SET published = TRUE WHERE id = ?");
        $publish_theory->execute([$practical['theory_exam_id']]);
        
        return [
            'success' => true,
            'message' => 'Practical exam published successfully! Students can now submit.'
        ];
    } catch (Exception $e) {
        error_log("Publish Exam Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Unpublish practical exam
 */
function unpublishPracticalExam($practical_exam_id, $teacher_id = null) {
    global $pdo;
    
    try {
        if ($teacher_id) {
            $verify = $pdo->prepare("SELECT theory_exam_id FROM practical_exams WHERE id = ? AND created_by = ?");
            $verify->execute([$practical_exam_id, $teacher_id]);
        } else {
            $verify = $pdo->prepare("SELECT theory_exam_id FROM practical_exams WHERE id = ?");
            $verify->execute([$practical_exam_id]);
        }
        
        $practical = $verify->fetch(PDO::FETCH_ASSOC);
        
        if (!$practical) {
            return ['success' => false, 'message' => 'Practical exam not found'];
        }
        
        $stmt = $pdo->prepare("UPDATE practical_exams SET status = 'draft', published = FALSE, published_at = NULL WHERE id = ?");
        $stmt->execute([$practical_exam_id]);
        
        return [
            'success' => true,
            'message' => 'Practical exam unpublished'
        ];
    } catch (Exception $e) {
        error_log("Unpublish Exam Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get teacher's practical exams
 */
function getTeacherPracticalExams($teacher_id, $subject_id = 0) {
    global $pdo;
    
    $query = "
        SELECT pe.id, pe.exam_id, pe.subject_id, pe.title, pe.theory_marks, pe.practical_marks,
               pe.submission_deadline, pe.status, s.subject_name, t.trade_name,
               COUNT(DISTINCT ps.student_id) as total_students,
               SUM(CASE WHEN ps.status = 'submitted' THEN 1 ELSE 0 END) as submissions_received,
               SUM(CASE WHEN pm.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marked_count
        FROM practical_exams pe
        LEFT JOIN subjects s ON pe.subject_id = s.id
        LEFT JOIN trades t ON pe.trade_id = t.id
        LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id
        LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
        WHERE pe.created_by = ?
    ";
    
    $params = [$teacher_id];
    
    if ($subject_id > 0) {
        $query .= " AND pe.subject_id = ?";
        $params[] = $subject_id;
    }
    
    $query .= " GROUP BY pe.id ORDER BY pe.submission_deadline DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get practical exams for a student
 */
function getStudentPracticalExams($student_id, $trade_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT pe.id, pe.exam_id, pe.subject_id, pe.title, pe.theory_marks, pe.practical_marks,
               pe.total_marks, pe.practical_pass_marks, pe.submission_deadline, 
               pe.evaluation_instructions, pe.status, s.subject_name, t.trade_name,
               ps.id as submission_id, ps.submitted_at, ps.is_late, ps.status as submission_status,
               pm.marks_obtained, pm.result_status as mark_result_status, pm.feedback
        FROM practical_exams pe
        JOIN subjects s ON pe.subject_id = s.id
        JOIN trades t ON s.trade_id = t.id
        LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id AND ps.student_id = ?
        LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
        WHERE t.id = ? AND pe.status = 'active'
        ORDER BY pe.submission_deadline DESC
    ");
    
    $stmt->execute([$student_id, $trade_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Submit practical work
 * Returns: ['success' => bool, 'submission_id' => int, 'message' => string]
 */
function submitPractical($practical_exam_id, $student_id, $exam_id, $file_path, $submission_link = null, $notes = null) {
    global $pdo;
    
    try {
        // Get practical exam details
        $exam_stmt = $pdo->prepare("SELECT submission_deadline, practical_marks FROM practical_exams WHERE id = ?");
        $exam_stmt->execute([$practical_exam_id]);
        $practical = $exam_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$practical) {
            return ['success' => false, 'message' => 'Practical exam not found'];
        }
        
        // Check if late
        $is_late = strtotime(date('Y-m-d H:i:s')) > strtotime($practical['submission_deadline']) ? 1 : 0;
        
        // Convert 0 exam_id to NULL
        $exam_id_param = $exam_id > 0 ? $exam_id : null;
        
        // Insert submission
        $stmt = $pdo->prepare("
            INSERT INTO practical_submissions 
            (practical_exam_id, student_id, exam_id, submission_file, submission_link, submission_notes, is_late, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'submitted')
            ON DUPLICATE KEY UPDATE
                submission_file = VALUES(submission_file),
                submission_link = VALUES(submission_link),
                submission_notes = VALUES(submission_notes),
                submitted_at = NOW(),
                status = 'submitted'
        ");
        
        $stmt->execute([$practical_exam_id, $student_id, $exam_id_param, $file_path, $submission_link, $notes, $is_late]);
        
        $submission_id = $pdo->lastInsertId();
        
        return [
            'success' => true,
            'submission_id' => $submission_id,
            'message' => 'Practical submitted successfully' . ($is_late ? ' (LATE SUBMISSION)' : '')
        ];
    } catch (Exception $e) {
        error_log("Submit Practical Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get submissions for marking
 */
function getPracticalSubmissionsForMarking($practical_exam_id, $teacher_id) {
    global $pdo;
    
    // Verify teacher owns this practical exam
    $verify = $pdo->prepare("SELECT id FROM practical_exams WHERE id = ? AND created_by = ?");
    $verify->execute([$practical_exam_id, $teacher_id]);
    if (!$verify->fetch()) {
        return [];
    }
    
    $stmt = $pdo->prepare("
        SELECT ps.id as submission_id, ps.student_id, ps.submitted_at, ps.is_late, ps.status,
               ps.submission_file, ps.submission_link, ps.submission_notes,
               u.full_name, u.email, u.enrollment_no,
               pm.marks_obtained, pm.result_status, pm.feedback
        FROM practical_submissions ps
        JOIN users u ON ps.student_id = u.id
        LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
        WHERE ps.practical_exam_id = ?
        ORDER BY ps.submitted_at DESC
    ");
    
    $stmt->execute([$practical_exam_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Mark a practical submission
 * Returns: ['success' => bool, 'message' => string, 'result_status' => string]
 */
function markPracticalSubmission($submission_id, $practical_exam_id, $marks_obtained, $feedback, $teacher_id) {
    global $pdo;
    
    try {
        // Get submission and exam details
        $sub_stmt = $pdo->prepare("
            SELECT ps.student_id, ps.exam_id, pe.practical_pass_marks, pe.practical_marks
            FROM practical_submissions ps
            JOIN practical_exams pe ON ps.practical_exam_id = pe.id
            WHERE ps.id = ? AND pe.created_by = ?
        ");
        $sub_stmt->execute([$submission_id, $teacher_id]);
        $submission = $sub_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$submission) {
            return ['success' => false, 'message' => 'Submission not found'];
        }
        
        // Validate marks
        if ($marks_obtained < 0 || $marks_obtained > 100) {
            return ['success' => false, 'message' => 'Marks must be between 0 and 100'];
        }
        
        // Determine result status
        $result_status = $marks_obtained >= $submission['practical_pass_marks'] ? 'pass' : 'fail';
        
        // Insert or update marks
        $stmt = $pdo->prepare("
            INSERT INTO practical_marks 
            (submission_id, practical_exam_id, student_id, exam_id, marks_obtained, 
             result_status, feedback, marked_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                marks_obtained = VALUES(marks_obtained),
                result_status = VALUES(result_status),
                feedback = VALUES(feedback),
                marked_by = VALUES(marked_by),
                marked_at = NOW()
        ");
        
        $stmt->execute([
            $submission_id,
            $practical_exam_id,
            $submission['student_id'],
            $submission['exam_id'],
            $marks_obtained,
            $result_status,
            $feedback,
            $teacher_id
        ]);
        
        // Update submission status
        $update = $pdo->prepare("UPDATE practical_submissions SET status = 'marked' WHERE id = ?");
        $update->execute([$submission_id]);
        
        return [
            'success' => true,
            'message' => 'Marks saved successfully',
            'result_status' => $result_status
        ];
    } catch (Exception $e) {
        error_log("Mark Practical Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get combined marks (theory + practical) for a student
 */
function getCombinedExamMarks($student_id, $exam_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT r.id, r.exam_id, r.student_id, r.theory_marks, r.theory_percentage,
               r.practical_marks, r.practical_percentage, r.total_marks, r.total_percentage,
               r.result_status, r.certificate_generated, e.exam_name, s.subject_name
        FROM combined_exam_results r
        JOIN exams e ON r.exam_id = e.id
        LEFT JOIN subjects s ON e.subject_id = s.id
        WHERE r.student_id = ? AND r.exam_id = ?
    ");
    
    $stmt->execute([$student_id, $exam_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Combine theory + practical marks and update result
 */
function combineCombinedExamMarks($student_id, $exam_id) {
    global $pdo;
    
    try {
        // Get theory marks from exam_results
        $theory = $pdo->prepare("
            SELECT obtained_marks, total_marks, percentage, result_status
            FROM exam_results
            WHERE student_id = ? AND exam_id = ?
        ");
        $theory->execute([$student_id, $exam_id]);
        $theory_result = $theory->fetch(PDO::FETCH_ASSOC);
        
        // Get practical marks
        $practical = $pdo->prepare("
            SELECT SUM(pm.marks_obtained) as practical_marks, pe.practical_marks,
                   pm.result_status, pe.id as practical_exam_id
            FROM practical_marks pm
            JOIN practical_exams pe ON pm.practical_exam_id = pe.id
            WHERE pm.student_id = ? AND pm.exam_id = ?
            LIMIT 1
        ");
        $practical->execute([$student_id, $exam_id]);
        $practical_result = $practical->fetch(PDO::FETCH_ASSOC);
        
        // If we have both marks, combine them
        if ($theory_result && $practical_result && $practical_result['practical_marks'] !== null) {
            $theory_marks = $theory_result['obtained_marks'] ?? 0;
            $practical_marks = $practical_result['practical_marks'] ?? 0;
            $total_marks = $theory_marks + $practical_marks;
            
            // Get total exam marks
            $exam_info = $pdo->prepare("SELECT total_marks FROM exams WHERE id = ?");
            $exam_info->execute([$exam_id]);
            $exam = $exam_info->fetch(PDO::FETCH_ASSOC);
            
            $total_percentage = $exam['total_marks'] > 0 ? ($total_marks / $exam['total_marks']) * 100 : 0;
            
            // Determine pass/fail (need minimum in both)
            $result_status = ($theory_result['result_status'] === 'pass' && $practical_result['result_status'] === 'pass') 
                           ? 'pass' : 'fail';
            
            // Update or insert combined result
            $stmt = $pdo->prepare("
                INSERT INTO combined_exam_results 
                (student_id, exam_id, practical_exam_id, theory_marks, theory_percentage,
                 practical_marks, practical_percentage, total_marks, total_percentage, result_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    theory_marks = VALUES(theory_marks),
                    theory_percentage = VALUES(theory_percentage),
                    practical_marks = VALUES(practical_marks),
                    practical_percentage = VALUES(practical_percentage),
                    total_marks = VALUES(total_marks),
                    total_percentage = VALUES(total_percentage),
                    result_status = VALUES(result_status),
                    updated_at = NOW()
            ");
            
            $stmt->execute([
                $student_id,
                $exam_id,
                $practical_result['practical_exam_id'],
                $theory_marks,
                $theory_result['percentage'],
                $practical_marks,
                ($practical_marks / $practical_result['practical_marks']) * 100,
                $total_marks,
                $total_percentage,
                $result_status
            ]);
            
            return [
                'success' => true,
                'theory_marks' => $theory_marks,
                'practical_marks' => $practical_marks,
                'total_marks' => $total_marks,
                'result_status' => $result_status
            ];
        }
        
        return ['success' => false, 'message' => 'Both theory and practical marks required'];
    } catch (Exception $e) {
        error_log("Combine Marks Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate certificate from combined exam result
 */
function generateCombinedExamCertificate($combined_result_id, $generated_by = null) {
    global $pdo;
    
    try {
        // Get combined result
        $result = $pdo->prepare("
            SELECT cer.id, cer.student_id, cer.exam_id, cer.total_marks, cer.total_percentage,
                   cer.result_status, e.exam_name, u.full_name, u.enrollment_no
            FROM combined_exam_results cer
            JOIN exams e ON cer.exam_id = e.id
            JOIN users u ON cer.student_id = u.id
            WHERE cer.id = ?
        ");
        $result->execute([$combined_result_id]);
        $combined = $result->fetch(PDO::FETCH_ASSOC);
        
        if (!$combined || $combined['result_status'] !== 'pass') {
            return ['success' => false, 'message' => 'Student has not passed the exam'];
        }
        
        // Generate certificate ID
        $certificate_id = "COMB/" . date('Y-m-d') . "/" . $combined['student_id'] . "/" . $combined['exam_id'];
        $verify_code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        
        // Check if certificate already exists
        $check = $pdo->prepare("SELECT id FROM certificates WHERE combined_result_id = ?");
        $check->execute([$combined_result_id]);
        $existing = $check->fetch();
        
        if ($existing) {
            return ['success' => false, 'message' => 'Certificate already generated'];
        }
        
        // Insert certificate
        $insert = $pdo->prepare("
            INSERT INTO certificates 
            (certificate_id, student_id, exam_id, result_id, score, percentage,
             verification_code, generated_by, status, is_combined_exam, combined_result_id, issued_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1, ?, NOW())
        ");
        
        $insert->execute([
            $certificate_id,
            $combined['student_id'],
            $combined['exam_id'],
            $combined['id'],
            $combined['total_marks'],
            $combined['total_percentage'],
            $verify_code,
            $generated_by,
            $combined_result_id
        ]);
        
        // Mark as generated
        $update = $pdo->prepare("UPDATE combined_exam_results SET certificate_generated = 1, generated_at = NOW() WHERE id = ?");
        $update->execute([$combined_result_id]);
        
        return [
            'success' => true,
            'certificate_id' => $certificate_id,
            'message' => 'Certificate generated successfully'
        ];
    } catch (Exception $e) {
        error_log("Generate Combined Certificate Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get practical exam statistics
 */
function getPracticalExamStats($practical_exam_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT ps.student_id) as total_students,
            SUM(CASE WHEN ps.status = 'submitted' THEN 1 ELSE 0 END) as submissions_received,
            SUM(CASE WHEN ps.status != 'submitted' AND ps.status != 'rejected' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN pm.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marked_submissions,
            SUM(CASE WHEN pm.result_status = 'pass' THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN pm.result_status = 'fail' THEN 1 ELSE 0 END) as fail_count,
            AVG(CASE WHEN pm.marks_obtained IS NOT NULL THEN pm.marks_obtained ELSE NULL END) as avg_marks
        FROM practical_submissions ps
        LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
        WHERE ps.practical_exam_id = ?
    ");
    
    $stmt->execute([$practical_exam_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
