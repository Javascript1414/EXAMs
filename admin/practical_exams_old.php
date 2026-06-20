<?php
/**
 * Admin: Practical Exam Management Dashboard
 * Admins can review, approve, and release certificates
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';
require_once '../includes/exam_invitation_functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin', 'moderator'])) {
    http_response_code(403);
    die('Access Denied - Admin Only');
}

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token';
        $message_type = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        $cert_id = (int)($_POST['cert_id'] ?? 0);

        if ($action === 'release_certificate' && $cert_id > 0) {
            // Release certificate (make it visible to student)
            $stmt = $pdo->prepare("UPDATE certificates SET status = 'released' WHERE id = ?");
            $stmt->execute([$cert_id]);
            $message = 'Certificate released successfully';
            $message_type = 'success';
        } elseif ($action === 'revoke_certificate' && $cert_id > 0) {
            // Revoke certificate
            $stmt = $pdo->prepare("UPDATE certificates SET status = 'revoked' WHERE id = ?");
            $stmt->execute([$cert_id]);
            $message = 'Certificate revoked';
            $message_type = 'success';
        } elseif ($action === 'create_practical_exam') {
            // Create practical exam (Admin feature)
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $subject_id = (int)($_POST['subject_id'] ?? 0);
            $exam_id = !empty($_POST['exam_id']) ? (int)$_POST['exam_id'] : null;
            $theory_marks = (int)($_POST['theory_marks'] ?? 80);
            $practical_marks = (int)($_POST['practical_marks'] ?? 20);
            $practical_pass_marks = (int)($_POST['practical_pass_marks'] ?? 10);
            $submission_deadline = $_POST['submission_deadline'] ?? '';
            $evaluation_instructions = sanitizeInput($_POST['evaluation_instructions'] ?? '');
            
            // Validation
            if (!$title) {
                $message = 'Title is required';
                $message_type = 'danger';
            } elseif (!$subject_id) {
                $message = 'Subject is required';
                $message_type = 'danger';
            } elseif ($theory_marks < 1 || $practical_marks < 1) {
                $message = 'Theory and practical marks must be at least 1';
                $message_type = 'danger';
            } elseif ($practical_pass_marks > $practical_marks) {
                $message = 'Pass marks cannot exceed practical marks';
                $message_type = 'danger';
            } elseif (!$submission_deadline) {
                $message = 'Submission deadline is required';
                $message_type = 'danger';
            } else {
                // Get trade_id from subject
                $subj_stmt = $pdo->prepare("SELECT trade_id FROM subjects WHERE id = ?");
                $subj_stmt->execute([$subject_id]);
                $subj = $subj_stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$subj) {
                    $message = 'Invalid subject selected';
                    $message_type = 'danger';
                } else {
                    // Create practical exam
                    $result = createPracticalExam([
                        'exam_id' => $exam_id,
                        'subject_id' => $subject_id,
                        'trade_id' => $subj['trade_id'],
                        'title' => $title,
                        'description' => $description,
                        'theory_marks' => $theory_marks,
                        'practical_marks' => $practical_marks,
                        'practical_pass_marks' => $practical_pass_marks,
                        'submission_deadline' => $submission_deadline,
                        'evaluation_instructions' => $evaluation_instructions,
                        'created_by' => $_SESSION['user_id']
                    ]);
                    
                    if ($result['success']) {
                        $message = 'Practical exam created successfully!';
                        $message_type = 'success';
                    } else {
                        $message = $result['message'];
                        $message_type = 'danger';
                    }
                }
            }
        } elseif ($action === 'delete_practical_exam') {
            // Delete practical exam
            $practical_exam_id = (int)($_POST['practical_exam_id'] ?? 0);
            
            if (!$practical_exam_id) {
                $message = 'Invalid practical exam';
                $message_type = 'danger';
            } else {
                try {
                    // Check if exam has submissions
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM practical_submissions WHERE practical_exam_id = ?");
                    $check_stmt->execute([$practical_exam_id]);
                    $submissions_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($submissions_count > 0) {
                        $message = "Cannot delete! This exam has $submissions_count student submission(s). Delete all submissions first.";
                        $message_type = 'danger';
                    } else {
                        // Delete the practical exam
                        $del_stmt = $pdo->prepare("DELETE FROM practical_exams WHERE id = ?");
                        $del_stmt->execute([$practical_exam_id]);
                        $message = 'Practical exam deleted successfully!';
                        $message_type = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Error deleting exam: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        } elseif ($action === 'edit_practical_exam') {
            // Edit practical exam
            $practical_exam_id = (int)($_POST['practical_exam_id'] ?? 0);
            $title = sanitizeInput($_POST['title'] ?? '');
            $description = sanitizeInput($_POST['description'] ?? '');
            $theory_marks = (int)($_POST['theory_marks'] ?? 80);
            $practical_marks = (int)($_POST['practical_marks'] ?? 20);
            $practical_pass_marks = (int)($_POST['practical_pass_marks'] ?? 10);
            $submission_deadline = $_POST['submission_deadline'] ?? '';
            $evaluation_instructions = sanitizeInput($_POST['evaluation_instructions'] ?? '');
            
            if (!$practical_exam_id) {
                $message = 'Invalid practical exam';
                $message_type = 'danger';
            } elseif (!$title) {
                $message = 'Title is required';
                $message_type = 'danger';
            } elseif ($theory_marks < 1 || $practical_marks < 1) {
                $message = 'Theory and practical marks must be at least 1';
                $message_type = 'danger';
            } elseif ($practical_pass_marks > $practical_marks) {
                $message = 'Pass marks cannot exceed practical marks';
                $message_type = 'danger';
            } elseif (!$submission_deadline) {
                $message = 'Submission deadline is required';
                $message_type = 'danger';
            } else {
                try {
                    $update_stmt = $pdo->prepare("
                        UPDATE practical_exams 
                        SET title = ?, description = ?, theory_marks = ?, practical_marks = ?,
                            total_marks = ?, practical_pass_marks = ?, submission_deadline = ?,
                            evaluation_instructions = ?
                        WHERE id = ?
                    ");
                    $update_stmt->execute([
                        $title,
                        $description,
                        $theory_marks,
                        $practical_marks,
                        $theory_marks + $practical_marks,
                        $practical_pass_marks,
                        $submission_deadline,
                        $evaluation_instructions,
                        $practical_exam_id
                    ]);
                    $message = 'Practical exam updated successfully!';
                    $message_type = 'success';
                } catch (Exception $e) {
                    $message = 'Error updating exam: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        } elseif ($action === 'generate_invitation') {
            // Generate invitation link
            $practical_exam_id = (int)($_POST['practical_exam_id'] ?? 0);
            $expires_days = (int)($_POST['expires_days'] ?? 30);
            
            if (!$practical_exam_id) {
                $message = 'Invalid practical exam';
                $message_type = 'danger';
            } else {
                $result = generateExamInvitation($practical_exam_id, $_SESSION['user_id'], $expires_days);
                
                if ($result['success']) {
                    $message = '✓ Invitation link generated! <br><strong>Share this link:</strong> <code style="background:#f0f0f0; padding:8px; border-radius:4px; display:inline-block; margin-top:5px;">' . htmlspecialchars($result['url']) . '</code><br><small style="color:#666;">Code: ' . htmlspecialchars($result['code']) . '</small>';
                    $message_type = 'success';
                } else {
                    $message = $result['message'];
                    $message_type = 'danger';
                }
            }
        } elseif ($action === 'revoke_invitation') {
            // Revoke invitation
            $invitation_id = (int)($_POST['invitation_id'] ?? 0);
            
            if ($invitation_id) {
                $result = revokeExamInvitation($invitation_id);
                $message = $result['message'];
                $message_type = $result['success'] ? 'success' : 'danger';
            }
        } elseif ($action === 'assign_marks') {
            // Assign marks to submission
            $submission_id = (int)($_POST['submission_id'] ?? 0);
            $marks_obtained = (float)($_POST['marks_obtained'] ?? 0);
            $feedback = sanitizeInput($_POST['feedback'] ?? '');
            
            if (!$submission_id || $marks_obtained < 0) {
                $message = 'Invalid submission or marks';
                $message_type = 'danger';
            } else {
                try {
                    // Get submission details
                    $sub_stmt = $pdo->prepare("
                        SELECT ps.id, ps.practical_exam_id, ps.student_id, ps.exam_id, ps.submission_file, ps.submitted_at, ps.status,
                               pe.practical_marks, pe.practical_pass_marks, pe.theory_marks
                        FROM practical_submissions ps
                        JOIN practical_exams pe ON ps.practical_exam_id = pe.id
                        WHERE ps.id = ?
                    ");
                    $sub_stmt->execute([$submission_id]);
                    $submission = $sub_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$submission) {
                        $message = 'Submission not found';
                        $message_type = 'danger';
                    } elseif ($marks_obtained > $submission['practical_marks']) {
                        $message = 'Marks cannot exceed maximum marks (' . $submission['practical_marks'] . ')';
                        $message_type = 'danger';
                    } else {
                        // Insert or update marks
                        $check_stmt = $pdo->prepare("SELECT id FROM practical_marks WHERE submission_id = ?");
                        $check_stmt->execute([$submission_id]);
                        $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existing) {
                            // Update existing marks
                            $update_stmt = $pdo->prepare("
                                UPDATE practical_marks 
                                SET marks_obtained = ?, feedback = ?, marked_by = ?, marked_at = NOW()
                                WHERE submission_id = ?
                            ");
                            $update_stmt->execute([$marks_obtained, $feedback, $_SESSION['user_id'], $submission_id]);
                        } else {
                            // Insert new marks (include all required foreign keys)
                            $insert_stmt = $pdo->prepare("
                                INSERT INTO practical_marks 
                                (submission_id, practical_exam_id, student_id, exam_id, marks_obtained, feedback, marked_by, marked_at, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                            ");
                            $insert_stmt->execute([
                                $submission_id, 
                                $submission['practical_exam_id'],
                                $submission['student_id'],
                                $submission['exam_id'],
                                $marks_obtained, 
                                $feedback, 
                                $_SESSION['user_id']
                            ]);
                        }
                        
                        // Auto-generate certificate if marks meet pass criteria
                        if ($marks_obtained >= $submission['practical_pass_marks']) {
                            // Calculate combined marks if linked to theory exam
                            if ($submission['exam_id']) {
                                $result_stmt = $pdo->prepare("
                                    SELECT id FROM combined_exam_results 
                                    WHERE student_id = ? AND exam_id = ?
                                ");
                                $result_stmt->execute([$submission['student_id'], $submission['exam_id']]);
                                
                                if (!$result_stmt->fetch()) {
                                    // Get theory marks from exam_results
                                    $theory_stmt = $pdo->prepare("
                                        SELECT total_marks_obtained FROM exam_results 
                                        WHERE student_id = ? AND exam_id = ?
                                        ORDER BY attempt_date DESC LIMIT 1
                                    ");
                                    $theory_stmt->execute([$submission['student_id'], $submission['exam_id']]);
                                    $theory_result = $theory_stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($theory_result) {
                                        $total_marks = $submission['theory_marks'] + $submission['practical_marks'];
                                        $total_obtained = $theory_result['total_marks_obtained'] + $marks_obtained;
                                        $percentage = ($total_obtained / $total_marks) * 100;
                                        
                                        // Insert combined result
                                        $combined_stmt = $pdo->prepare("
                                            INSERT INTO combined_exam_results 
                                            (student_id, exam_id, theory_marks_obtained, practical_marks_obtained, total_marks_obtained, total_percentage, result_status, created_at)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                                        ");
                                        $combined_stmt->execute([
                                            $submission['student_id'],
                                            $submission['exam_id'],
                                            $theory_result['total_marks_obtained'],
                                            $marks_obtained,
                                            $total_obtained,
                                            $percentage,
                                            ($percentage >= 50) ? 'pass' : 'fail'
                                        ]);
                                    }
                                }
                            }
                        }
                        
                        $message = 'Marks assigned successfully!';
                        $message_type = 'success';
                    }
                } catch (Exception $e) {
                    $message = 'Error: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Get statistics
$stats_stmt = $pdo->query("
    SELECT 
        COUNT(DISTINCT pe.id) as total_practical_exams,
        COUNT(DISTINCT ps.student_id) as total_students_participated,
        COUNT(DISTINCT CASE WHEN pm.marks_obtained IS NOT NULL THEN ps.id END) as submissions_marked,
        COUNT(DISTINCT CASE WHEN cer.id IS NOT NULL THEN cer.id END) as certificates_generated,
        AVG(CASE WHEN cer.id IS NOT NULL THEN cer.total_percentage ELSE NULL END) as avg_percentage
    FROM practical_exams pe
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id
    LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
    LEFT JOIN combined_exam_results cer ON ps.student_id = cer.student_id AND ps.exam_id = cer.exam_id AND cer.result_status = 'pass'
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Get all subjects for create form dropdown
$subjects_stmt = $pdo->query("
    SELECT s.id, s.subject_name, t.id as trade_id, t.trade_name
    FROM subjects s
    JOIN trades t ON s.trade_id = t.id
    ORDER BY t.trade_name, s.subject_name
");
$all_subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all trades for dropdown
$trades_stmt = $pdo->query("
    SELECT id, trade_name FROM trades ORDER BY trade_name
");
$all_trades = $trades_stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== PAGINATION FOR PRACTICAL EXAMS ==========
$items_per_page = 6;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count of practical exams
$count_stmt = $pdo->query("
    SELECT COUNT(DISTINCT pe.id) as total 
    FROM practical_exams pe
");
$total_exams = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_exams / $items_per_page);

// Ensure current page is valid
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $items_per_page;
}

// Get practical exams with details (paginated)
$exams_stmt = $pdo->query("
    SELECT pe.id, pe.title, pe.subject_id, pe.theory_marks, pe.practical_marks,
           pe.submission_deadline, pe.status, s.subject_name, u.full_name,
           COUNT(DISTINCT ps.student_id) as total_submissions,
           SUM(CASE WHEN pm.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marked_count,
           COUNT(DISTINCT CASE WHEN cer.id IS NOT NULL THEN cer.id END) as certificates_generated
    FROM practical_exams pe
    LEFT JOIN subjects s ON pe.subject_id = s.id
    LEFT JOIN users u ON pe.created_by = u.id
    LEFT JOIN practical_submissions ps ON pe.id = ps.practical_exam_id
    LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
    LEFT JOIN combined_exam_results cer ON ps.student_id = cer.student_id AND ps.exam_id = cer.exam_id
    GROUP BY pe.id
    ORDER BY pe.submission_deadline DESC
    LIMIT " . (int)$items_per_page . " OFFSET " . (int)$offset . "
");
$practical_exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get certificates pending release
$pending_certs = $pdo->query("
    SELECT c.id, c.certificate_id, c.student_id, c.exam_id, c.score, c.percentage,
           u.full_name, u.email, e.exam_name, c.status
    FROM certificates c
    JOIN users u ON c.student_id = u.id
    JOIN exams e ON c.exam_id = e.id
    WHERE (c.status = 'active' OR c.status = 'pending')
    ORDER BY c.issued_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// Get submissions for a specific exam if requested (for modal)
$exam_submissions = [];
$selected_exam_id = (int)($_GET['view_exam'] ?? 0);
if ($selected_exam_id) {
    $submissions_stmt = $pdo->prepare("
        SELECT ps.id, ps.student_id, ps.submission_file, ps.submitted_at, ps.status,
               u.full_name, u.email,
               pm.marks_obtained, pm.feedback, pm.marked_at,
               mu.full_name as marked_by_name
        FROM practical_submissions ps
        JOIN users u ON ps.student_id = u.id
        LEFT JOIN practical_marks pm ON ps.id = pm.submission_id
        LEFT JOIN users mu ON pm.marked_by = mu.id
        WHERE ps.practical_exam_id = ?
        ORDER BY ps.submitted_at DESC
    ");
    $submissions_stmt->execute([$selected_exam_id]);
    $exam_submissions = $submissions_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical Exam Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
            --info: #4a90e2;
        }

        * { transition: all 0.3s ease; }
        body {
            background: linear-gradient(135deg, #f7fafc 0%, #f0f4ff 100%);
            padding: 30px 20px;
            min-height: 100vh;
        }

        .container-main {
            max-width: 1400px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .page-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
            pointer-events: none;
        }
        .stat-card.primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        .stat-value {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 12px 0;
        }
        .stat-label {
            font-size: 0.9rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.9;
        }
        .card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 28px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 25px 30px;
            position: relative;
        }
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 30px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }
        .table thead th {
            background: linear-gradient(135deg, #f7fafc 0%, #f0f4ff 100%);
            color: var(--primary);
            font-weight: 700;
            border: none;
            padding: 16px 14px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.03);
        }
        .table tbody td {
            padding: 16px 14px;
            vertical-align: middle;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(72, 187, 120, 0.15);
            color: var(--success);
        }
        .status-pending {
            background: rgba(237, 137, 54, 0.15);
            color: var(--warning);
        }
        .status-released {
            background: rgba(74, 144, 226, 0.15);
            color: var(--info);
        }
        .status-revoked {
            background: rgba(245, 101, 101, 0.15);
            color: var(--danger);
        }
        .btn {
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-danger {
            background: var(--danger);
            color: white;
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.3);
        }
        .btn-danger:hover {
            background: #d93030;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 101, 101, 0.4);
            color: white;
        }
        .btn-warning {
            background: var(--warning);
            color: white;
            box-shadow: 0 4px 12px rgba(237, 137, 54, 0.3);
        }
        .btn-warning:hover {
            background: #d67e2e;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(237, 137, 54, 0.4);
            color: white;
        }
        .btn-info {
            background: var(--info);
            color: white;
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
        }
        .btn-info:hover {
            background: #2d6dbe;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 144, 226, 0.4);
            color: white;
        }
        .btn-outline-danger {
            border: 2px solid var(--danger);
            color: var(--danger);
        }
        .btn-outline-danger:hover {
            background-color: rgba(245, 101, 101, 0.1);
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .alert {
            border: none;
            border-radius: 10px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .alert-info {
            background: rgba(74, 144, 226, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
        }
        .percentage-bar {
            background: #e2e8f0;
            border-radius: 6px;
            height: 24px;
            overflow: hidden;
            position: relative;
        }
        .percentage-fill {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            height: 100%;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            border-bottom: 3px solid #e2e8f0;
            flex-wrap: wrap;
        }
        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            color: #718096;
            font-weight: 700;
            cursor: pointer;
            border-bottom: 4px solid transparent;
            margin-bottom: -3px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tab-button:hover {
            color: var(--primary);
        }
        .tab-button.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; } to { opacity: 1; }
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .form-label {
            color: #2d3748;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        .modal-content {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }

        /* ===== BUTTON STYLES - ENSURE CLICKABILITY ===== */
        #createExamBtn {
            pointer-events: auto !important;
            cursor: pointer !important;
            position: relative;
            z-index: 10;
            font-weight: 600;
            padding: 10px 20px;
            border: none;
            transition: all 0.3s ease;
        }

        #createExamBtn:not(:disabled) {
            cursor: pointer !important;
        }

        #createExamBtn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        #createExamBtn:active {
            transform: translateY(0);
        }

        /* ===== MODAL STYLES ===== */
        .modal-dialog {
            pointer-events: auto !important;
        }

        .modal.fade .modal-dialog {
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .modal.show .modal-dialog {
            transform: none;
            opacity: 1;
        }

        .modal-backdrop {
            pointer-events: auto;
        }

        /* ===== ENSURE HEADER BUTTON AREA IS CLICKABLE ===== */
        .card-header {
            pointer-events: auto;
        }

        .card-header::before {
            pointer-events: none !important;
        }

        .card-header h2 {
            pointer-events: auto;
        }

        /* ========== PAGINATION STYLES ========== */
        .pagination {
            gap: 5px;
        }

        .pagination .page-link {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: var(--primary);
            font-weight: 600;
            padding: 10px 12px;
            min-width: 42px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover:not(.disabled .page-link) {
            background-color: rgba(102, 126, 234, 0.1);
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-color: var(--primary);
            color: white;
            font-weight: 700;
        }

        .pagination .page-item.disabled .page-link {
            color: #cbd5e0;
            border-color: #e2e8f0;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .pagination .page-item:not(.disabled) .page-link {
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.9rem;
            }

            #createExamBtn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }

            .pagination {
                flex-wrap: wrap;
            }

            .pagination .page-link {
                min-width: 38px;
                padding: 8px 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="page-header">
            <h1><i class="fas fa-gauge"></i> Practical Exam Management Dashboard</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?>" role="alert">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-label"><i class="fas fa-book"></i> Total Practical Exams</div>
                <div class="stat-value"><?= $stats['total_practical_exams'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-users"></i> Students Participated</div>
                <div class="stat-value" style="color: var(--primary);"><?= $stats['total_students_participated'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-check-double"></i> Submissions Marked</div>
                <div class="stat-value" style="color: var(--success);"><?= $stats['submissions_marked'] ?? 0 ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="fas fa-certificate"></i> Certificates Generated</div>
                <div class="stat-value" style="color: var(--info);"><?= $stats['certificates_generated'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab-button" type="button">
                <i class="fas fa-award"></i> Certificates (<?= count($pending_certs) ?>)
            </button>
            <button class="tab-button active" type="button">
                <i class="fas fa-list-check"></i> All Practical Exams
            </button>
        </div>

        <!-- Certificates Tab -->
        <div id="certificates" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-certificate"></i> Certificates Ready for Release</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($pending_certs)): ?>
                        <div class="alert alert-info">No certificates pending release</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Certificate ID</th>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_certs as $cert): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($cert['certificate_id']) ?></code></td>
                                        <td>
                                            <div><strong><?= htmlspecialchars($cert['full_name']) ?></strong></div>
                                            <small><?= htmlspecialchars($cert['email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($cert['exam_name']) ?></td>
                                        <td><strong><?= $cert['score'] ?></strong></td>
                                        <td>
                                            <div class="percentage-bar">
                                                <div class="percentage-fill" style="width: <?= min($cert['percentage'], 100) ?>%;">
                                                    <?= round($cert['percentage'], 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($cert['status']) ?>">
                                                <?= ucfirst($cert['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($cert['status'] === 'active' || $cert['status'] === 'pending'): ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="release_certificate">
                                                    <input type="hidden" name="cert_id" value="<?= $cert['id'] ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm" title="Release certificate to student">
                                                        <i class="fas fa-check"></i> Release
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="revoke_certificate">
                                                    <input type="hidden" name="cert_id" value="<?= $cert['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Revoke certificate">
                                                        <i class="fas fa-times"></i> Revoke
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Exams Tab -->
        <div id="exams" class="tab-content active">
            <div class="card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0;"><i class="fas fa-book-open"></i> All Practical Exams</h2>
                    <button type="button" class="btn btn-primary" id="createExamBtn">
                        <i class="fas fa-plus"></i> Create Practical Exam
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($practical_exams)): ?>
                        <div class="alert alert-info">No practical exams created yet</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Practical Title</th>
                                        <th>Subject</th>
                                        <th>Teacher</th>
                                        <th>Marks</th>
                                        <th>Submissions</th>
                                        <th>Marked</th>
                                        <th>Certificates</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($practical_exams as $exam): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($exam['title']) ?></strong></td>
                                        <td><?= htmlspecialchars($exam['subject_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($exam['full_name'] ?? '-') ?></td>
                                        <td>
                                            <small>Theory: <?= $exam['theory_marks'] ?> | Practical: <?= $exam['practical_marks'] ?></small>
                                        </td>
                                        <td><?= $exam['total_submissions'] ?></td>
                                        <td><?= $exam['marked_count'] ?? 0 ?></td>
                                        <td><?= $exam['certificates_generated'] ?? 0 ?></td>
                                        <td>
                                            <span class="status-badge status-<?= strtolower($exam['status']) ?>">
                                                <?= ucfirst($exam['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <a href="?view_exam=<?= $exam['id'] ?>" class="btn btn-info btn-sm" title="View student submissions">
                                                    <i class="fas fa-folder-open"></i> View
                                                </a>
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editExamModal<?= $exam['id'] ?>" title="Edit practical exam">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="generate_invitation">
                                                    <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                                                    <input type="hidden" name="expires_days" value="30">
                                                    <button type="submit" class="btn btn-primary btn-sm" title="Generate shareable invitation link">
                                                        <i class="fas fa-link"></i> Link
                                                    </button>
                                                </form>
                                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this practical exam? This cannot be undone.');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                    <input type="hidden" name="action" value="delete_practical_exam">
                                                    <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete practical exam">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Edit Modals for Each Exam -->
                            <?php foreach ($practical_exams as $exam): 
                                $exam_stmt = $pdo->prepare("SELECT * FROM practical_exams WHERE id = ?");
                                $exam_stmt->execute([$exam['id']]);
                                $exam_details = $exam_stmt->fetch(PDO::FETCH_ASSOC);
                            ?>
                            <div class="modal fade" id="editExamModal<?= $exam['id'] ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header bg-warning text-white">
                                            <h5 class="modal-title">
                                                <i class="fas fa-edit"></i> Edit Practical Exam
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="POST" action="">
                                            <div class="modal-body">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="action" value="edit_practical_exam">
                                                <input type="hidden" name="practical_exam_id" value="<?= $exam['id'] ?>">

                                                <div class="mb-3">
                                                    <label for="edit_title_<?= $exam['id'] ?>" class="form-label"><strong>Exam Title</strong></label>
                                                    <input type="text" class="form-control" id="edit_title_<?= $exam['id'] ?>" name="title" value="<?= htmlspecialchars($exam_details['title'] ?? '') ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="edit_description_<?= $exam['id'] ?>" class="form-label"><strong>Description</strong></label>
                                                    <textarea class="form-control" id="edit_description_<?= $exam['id'] ?>" name="description" rows="3"><?= htmlspecialchars($exam_details['description'] ?? '') ?></textarea>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="edit_theory_<?= $exam['id'] ?>" class="form-label"><strong>Theory Marks</strong></label>
                                                            <input type="number" class="form-control" id="edit_theory_<?= $exam['id'] ?>" name="theory_marks" min="1" max="100" value="<?= $exam_details['theory_marks'] ?? 80 ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="edit_practical_<?= $exam['id'] ?>" class="form-label"><strong>Practical Marks</strong></label>
                                                            <input type="number" class="form-control" id="edit_practical_<?= $exam['id'] ?>" name="practical_marks" min="1" max="100" value="<?= $exam_details['practical_marks'] ?? 20 ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="edit_pass_<?= $exam['id'] ?>" class="form-label"><strong>Pass Marks</strong></label>
                                                            <input type="number" class="form-control" id="edit_pass_<?= $exam['id'] ?>" name="practical_pass_marks" min="1" value="<?= $exam_details['practical_pass_marks'] ?? 10 ?>" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="edit_deadline_<?= $exam['id'] ?>" class="form-label"><strong>Submission Deadline</strong></label>
                                                    <input type="datetime-local" class="form-control" id="edit_deadline_<?= $exam['id'] ?>" name="submission_deadline" value="<?= date('Y-m-d\TH:i', strtotime($exam_details['submission_deadline'] ?? '')) ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="edit_instructions_<?= $exam['id'] ?>" class="form-label"><strong>Evaluation Instructions</strong></label>
                                                    <textarea class="form-control" id="edit_instructions_<?= $exam['id'] ?>" name="evaluation_instructions" rows="3"><?= htmlspecialchars($exam_details['evaluation_instructions'] ?? '') ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update Exam</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <!-- PAGINATION CONTROLS -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Pagination Navigation" style="margin-top: 25px;">
                                <ul class="pagination justify-content-center" style="margin-bottom: 0;">
                                    <!-- Previous Button -->
                                    <li class="page-item <?= $current_page === 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= max(1, $current_page - 1) ?>" style="<?= $current_page === 1 ? 'cursor: not-allowed; opacity: 0.5;' : '' ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    <?php 
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=1">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif;
                                    endif;
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>" style="<?= $i === $current_page ? 'background-color: var(--primary); border-color: var(--primary); color: white;' : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor;
                                    
                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Next Button -->
                                    <li class="page-item <?= $current_page === $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= min($total_pages, $current_page + 1) ?>" style="<?= $current_page === $total_pages ? 'cursor: not-allowed; opacity: 0.5;' : '' ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>

                            <!-- Pagination Info -->
                            <div style="text-align: center; margin-top: 15px; color: #666; font-size: 0.9rem;">
                                Showing <?= count($practical_exams) > 0 ? (($current_page - 1) * $items_per_page + 1) : 0 ?> to <?= min($current_page * $items_per_page, $total_exams) ?> of <?= $total_exams ?> exams (Page <?= $current_page ?> of <?= $total_pages ?>)
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Student Submissions Viewer Section -->
        <?php if ($selected_exam_id): 
            $exam_stmt = $pdo->prepare("SELECT * FROM practical_exams WHERE id = ?");
            $exam_stmt->execute([$selected_exam_id]);
            $current_exam = $exam_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_exam):
        ?>
        <div class="card" style="margin-top: 30px; border: 3px solid #667eea;">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="margin: 0;"><i class="fas fa-file-upload"></i> Student Submissions for: <?= htmlspecialchars($current_exam['title']) ?></h2>
                <a href="practical_exams.php" class="btn btn-light btn-sm"><i class="fas fa-times"></i> Close</a>
            </div>
            <div class="card-body">
                <h5 style="margin-bottom: 20px;"><strong><?= htmlspecialchars($current_exam['title']) ?></strong></h5>
                <p style="color: #666; font-size: 0.9rem;"><strong>Exam ID:</strong> <?= $selected_exam_id ?> | <strong>Total Expected Submissions:</strong> <span style="color: #667eea; font-weight: bold;">Loading...</span></p>
                
                <?php if (empty($exam_submissions)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>No submissions yet</strong> - Students haven't submitted their practical exams for this assessment.
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Submitted On</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Marks</th>
                                <th>Feedback</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exam_submissions as $sub): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($sub['full_name']) ?></strong></td>
                                <td><small><?= htmlspecialchars($sub['email']) ?></small></td>
                                <td><?= date('d-M-Y H:i', strtotime($sub['submitted_at'])) ?></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $sub['marks_obtained'] !== null ? '#28a745' : '#ffc107' ?>;">
                                        <?= $sub['marks_obtained'] !== null ? '✓ Marked' : '⏳ Pending' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $submission_file_path = '../uploads/practical_submissions/' . $sub['submission_file'];
                                        if ($sub['submission_file'] && file_exists($submission_file_path)): 
                                    ?>
                                        <a href="download_submission.php?file=<?= urlencode($sub['submission_file']) ?>" class="btn btn-sm btn-outline-primary" title="Download submission file">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    <?php else: ?>
                                        <small class="text-danger">File missing</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['marks_obtained'] !== null): ?>
                                        <strong style="color: #28a745;"><?= $sub['marks_obtained'] ?>/<?= $current_exam['practical_marks'] ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sub['feedback']): ?>
                                        <small><?= htmlspecialchars(substr($sub['feedback'], 0, 50)) ?><?= strlen($sub['feedback']) > 50 ? '...' : '' ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" onclick="openMarkingForm(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['full_name']) ?>', <?= $current_exam['practical_marks'] ?>, '<?= $sub['marks_obtained'] ?? 0 ?>', '<?= htmlspecialchars($sub['feedback'] ?? '') ?>')">
                                        <i class="fas fa-pen"></i> Mark
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Marking Form Modal -->
    <div class="modal fade" id="markingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-star"></i> Assign Marks</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="action" value="assign_marks">
                        <input type="hidden" name="submission_id" id="markSubmissionId">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Student Name</strong></label>
                            <p id="markStudentName" style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 0;"></p>
                        </div>

                        <div class="mb-3">
                            <label for="marksObtained" class="form-label"><strong>Marks Obtained</strong></label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" class="form-control" id="marksObtained" name="marks_obtained" step="0.5" min="0" required>
                                <span>out of <strong id="maxMarks">20</strong></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="markFeedback" class="form-label"><strong>Feedback (Optional)</strong></label>
                            <textarea class="form-control" id="markFeedback" name="feedback" rows="4" placeholder="Enter your feedback..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Save Marks</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="createPracticalModal" tabindex="-1" role="dialog" aria-labelledby="createPracticalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createPracticalLabel">
                        <i class="fas fa-plus-circle"></i> Create New Practical Exam
                    </h5>
                    <button type="button" class="btn-close btn-close-white" id="closeModalBtn" onclick="closeCreateModal()" aria-label="Close"></button>
                </div>

                <!-- Modal Body with Form -->
                <form id="createPracticalForm" method="POST" action="" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <!-- Hidden Fields -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="create_practical_exam">

                        <!-- Alert Messages -->
                        <div id="formAlert"></div>

                        <!-- Form Fields -->
                        <div class="mb-3">
                            <label for="title" class="form-label"><strong>Exam Title <span class="text-danger">*</span></strong></label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Database Design Practical">
                            <div class="invalid-feedback">Exam title is required.</div>
                        </div>

                        <!-- TRADE DROPDOWN -->
                        <div class="mb-3">
                            <label for="trade_id" class="form-label"><strong>Trade <span class="text-danger">*</span></strong></label>
                            <select class="form-control" id="trade_id" name="trade_id" required>
                                <option value="">-- Select Trade --</option>
                                <?php foreach ($all_trades as $trade): ?>
                                    <option value="<?= $trade['id'] ?>"><?= htmlspecialchars($trade['trade_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a trade.</div>
                        </div>

                        <!-- SUBJECT DROPDOWN (Filtered by Trade) -->
                        <div class="mb-3">
                            <label for="subject_id" class="form-label"><strong>Subject <span class="text-danger">*</span></strong></label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <!-- Options will be populated by JavaScript based on selected trade -->
                            </select>
                            <div class="invalid-feedback">Please select a subject.</div>
                        </div>

                        <!-- THEORY EXAM DROPDOWN (Optional) -->
                        <div class="mb-3">
                            <label for="exam_id" class="form-label"><strong>Link to Theory Exam <span class="text-muted">(Optional)</span></strong></label>
                            <select class="form-control" id="exam_id" name="exam_id">
                                <option value="">-- No Theory Exam --</option>
                                <?php 
                                // Get all active exams
                                $exams_stmt = $pdo->query("SELECT id, exam_name FROM exams WHERE status = 'active' ORDER BY exam_name");
                                $all_exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($all_exams as $exam): ?>
                                    <option value="<?= $exam['id'] ?>"><?= htmlspecialchars($exam['exam_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Connect this practical to a theory exam for combined scoring</small>
                        </div>

                        <!-- Hidden JSON data for subjects -->
                        <script type="application/json" id="subjectsData">
                        <?php 
                            // Create a JSON structure for subjects grouped by trade
                            $subjects_by_trade = [];
                            foreach ($all_subjects as $subject) {
                                $trade_id = $subject['trade_id'];
                                if (!isset($subjects_by_trade[$trade_id])) {
                                    $subjects_by_trade[$trade_id] = [];
                                }
                                $subjects_by_trade[$trade_id][] = [
                                    'id' => $subject['id'],
                                    'name' => $subject['subject_name']
                                ];
                            }
                            echo json_encode($subjects_by_trade);
                        ?>
                        </script>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="theory_marks" class="form-label"><strong>Theory Marks</strong></label>
                                    <input type="number" class="form-control" id="theory_marks" name="theory_marks" value="80" min="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="practical_marks" class="form-label"><strong>Practical Marks <span class="text-danger">*</span></strong></label>
                                    <input type="number" class="form-control" id="practical_marks" name="practical_marks" value="20" min="1" required>
                                    <div class="invalid-feedback">Practical marks must be at least 1.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="practical_pass_marks" class="form-label"><strong>Pass Marks <span class="text-danger">*</span></strong></label>
                            <input type="number" class="form-control" id="practical_pass_marks" name="practical_pass_marks" value="10" min="1" required>
                            <small class="text-muted">Minimum marks to pass</small>
                            <div class="invalid-feedback">Pass marks is required.</div>
                        </div>

                        <div class="mb-3">
                            <label for="submission_deadline" class="form-label"><strong>Submission Deadline <span class="text-danger">*</span></strong></label>
                            <input type="datetime-local" class="form-control" id="submission_deadline" name="submission_deadline" required>
                            <div class="invalid-feedback">Submission deadline is required.</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label"><strong>Description</strong></label>
                            <textarea class="form-control" id="description" name="description" rows="2" placeholder="Exam instructions..."></textarea>
                        </div>

                        <div class="mb-0">
                            <label for="evaluation_instructions" class="form-label"><strong>Evaluation Instructions</strong></label>
                            <textarea class="form-control" id="evaluation_instructions" name="evaluation_instructions" rows="2" placeholder="How to evaluate..."></textarea>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-check"></i> Create Exam
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ========== MODAL MANAGEMENT - ROBUST VERSION WITH MULTIPLE FALLBACKS ==========
        let createModalInstance = null;
        let bootstrapReady = false;

        // Function to safely initialize modal
        function initializeModal() {
            console.log('🔷 [INIT] Attempting to initialize modal...');
            
            // Check if Bootstrap is available
            if (typeof bootstrap === 'undefined') {
                console.warn('⚠️  [INIT] Bootstrap not yet loaded, retrying in 100ms...');
                setTimeout(initializeModal, 100);
                return;
            }

            bootstrapReady = true;
            console.log('✅ [INIT] Bootstrap is available');
            
            // Get modal element
            const modalElement = document.getElementById('createPracticalModal');
            if (!modalElement) {
                console.error('❌ [INIT] Modal element NOT found (ID: createPracticalModal)');
                return;
            }
            console.log('✅ [INIT] Modal element found:', modalElement.id);

            try {
                // Initialize Bootstrap Modal
                createModalInstance = new bootstrap.Modal(modalElement, {
                    backdrop: 'static',
                    keyboard: false
                });
                console.log('✅ [INIT] Bootstrap Modal instance created successfully');
            } catch (error) {
                console.error('❌ [INIT] Failed to create Modal instance:', error);
                return;
            }

            // Setup form submission
            const form = document.getElementById('createPracticalForm');
            if (form) {
                console.log('✅ [INIT] Form found - attaching submit handler');
                form.addEventListener('submit', handleFormSubmit);
            } else {
                console.error('❌ [INIT] Form NOT found (ID: createPracticalForm)');
            }

            // Attach button click handler
            const createBtn = document.getElementById('createExamBtn');
            if (createBtn) {
                console.log('✅ [INIT] Button found - attaching click handler');
                createBtn.addEventListener('click', function(e) {
                    console.log('🔷 [CLICK] Create button clicked');
                    e.preventDefault();
                    e.stopPropagation();
                    openCreateModal();
                });
            } else {
                console.error('❌ [INIT] Button NOT found (ID: createExamBtn)');
            }

            // Also attach to close button
            const closeBtn = document.getElementById('closeModalBtn');
            if (closeBtn) {
                console.log('✅ [INIT] Close button found');
                closeBtn.addEventListener('click', closeCreateModal);
            }

            // Setup trade dropdown to filter subjects
            const tradeSelect = document.getElementById('trade_id');
            const subjectSelect = document.getElementById('subject_id');
            const subjectsDataEl = document.getElementById('subjectsData');
            
            if (tradeSelect && subjectSelect && subjectsDataEl) {
                console.log('✅ [INIT] Trade filter setup - parsing subjects data');
                
                // Parse subjects data
                let subjectsByTrade = {};
                try {
                    subjectsByTrade = JSON.parse(subjectsDataEl.textContent);
                    console.log('✅ [INIT] Subjects data loaded:', Object.keys(subjectsByTrade).length, 'trades');
                } catch (error) {
                    console.error('❌ [INIT] Error parsing subjects data:', error);
                }
                
                // Function to update subject options based on selected trade
                function updateSubjects() {
                    const selectedTradeId = tradeSelect.value;
                    console.log('🔷 [TRADE] Trade selected:', selectedTradeId);
                    
                    // Clear current options (except first)
                    while (subjectSelect.options.length > 1) {
                        subjectSelect.remove(1);
                    }
                    
                    // Reset to default
                    subjectSelect.value = '';
                    
                    if (selectedTradeId && subjectsByTrade[selectedTradeId]) {
                        const subjects = subjectsByTrade[selectedTradeId];
                        console.log('📊 [TRADE] Found', subjects.length, 'subjects for trade', selectedTradeId);
                        
                        // Add subjects for selected trade
                        subjects.forEach(subject => {
                            const option = document.createElement('option');
                            option.value = subject.id;
                            option.textContent = subject.name;
                            subjectSelect.appendChild(option);
                        });
                        
                        console.log('✅ [TRADE] Subject dropdown updated');
                    } else if (selectedTradeId) {
                        console.warn('⚠️  [TRADE] No subjects found for trade ID:', selectedTradeId);
                    }
                }
                
                // Attach change listener to trade dropdown
                tradeSelect.addEventListener('change', updateSubjects);
                console.log('✅ [INIT] Trade change listener attached');
            } else {
                console.warn('⚠️  [INIT] Trade/Subject filtering not available - elements missing');
            }

            console.log('✅ [INIT] Modal initialization complete!');
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            console.log('🔷 [READY] DOM still loading, waiting for DOMContentLoaded...');
            document.addEventListener('DOMContentLoaded', initializeModal);
        } else {
            console.log('🔷 [READY] DOM already loaded, initializing immediately...');
            initializeModal();
        }

        // Open Modal Function
        function openCreateModal() {
            console.log('🔷 [OPEN] openCreateModal() called');
            
            // If bootstrap isn't ready, try again
            if (!bootstrapReady) {
                console.warn('⚠️  [OPEN] Bootstrap not ready yet, waiting...');
                setTimeout(openCreateModal, 100);
                return;
            }

            try {
                if (!createModalInstance) {
                    console.error('❌ [OPEN] Modal instance is null, reinitializing...');
                    initializeModal();
                    return;
                }
                
                createModalInstance.show();
                console.log('✅ [OPEN] Modal displayed successfully');
                
                // Log modal visibility
                const modal = document.getElementById('createPracticalModal');
                if (modal) {
                    console.log('📊 [OPEN] Modal display style:', window.getComputedStyle(modal).display);
                    console.log('📊 [OPEN] Modal visibility:', window.getComputedStyle(modal).visibility);
                }
            } catch (error) {
                console.error('❌ [OPEN] Error opening modal:', error);
                console.error('   Stack trace:', error.stack);
                alert('Error opening form: ' + error.message);
            }
        }

        // Close Modal Function
        function closeCreateModal() {
            console.log('🔷 [CLOSE] closeCreateModal() called');
            try {
                if (createModalInstance) {
                    createModalInstance.hide();
                    console.log('✅ [CLOSE] Modal hidden successfully');
                } else {
                    console.warn('⚠️  [CLOSE] Modal instance not initialized');
                }
            } catch (error) {
                console.error('❌ [CLOSE] Error closing modal:', error);
            }
        }

        // Form Submit Handler
        function handleFormSubmit(e) {
            console.log('🔷 [SUBMIT] Form submit event triggered');
            console.log('📝 [SUBMIT] Event phase:', e.eventPhase);
            
            const form = document.getElementById('createPracticalForm');
            
            if (!form) {
                console.error('❌ [SUBMIT] Form element not found');
                e.preventDefault();
                return;
            }

            // Check validation
            const isValid = form.checkValidity();
            console.log('📋 [SUBMIT] Form validity:', isValid);
            
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
                console.log('❌ [SUBMIT] Form validation failed - preventing submit');
            } else {
                console.log('✅ [SUBMIT] Form validation passed');
                console.log('📝 [SUBMIT] Form data:');
                console.log('  - Title:', document.getElementById('title').value);
                console.log('  - Subject ID:', document.getElementById('subject_id').value);
                console.log('  - Practical Marks:', document.getElementById('practical_marks').value);
                console.log('  - Pass Marks:', document.getElementById('practical_pass_marks').value);
                console.log('  - Deadline:', document.getElementById('submission_deadline').value);
                console.log('✅ [SUBMIT] Form will submit normally');
            }
            
            form.classList.add('was-validated');
        }

        // Global debug helper
        window.debugModal = {
            show: () => { console.log('DEBUG: Calling openCreateModal()'); openCreateModal(); },
            hide: () => { console.log('DEBUG: Calling closeCreateModal()'); closeCreateModal(); },
            status: () => {
                console.log('=== MODAL DEBUG STATUS ===');
                console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
                console.log('Bootstrap ready flag:', bootstrapReady);
                console.log('Modal instance exists:', createModalInstance !== null);
                console.log('Modal element:', document.getElementById('createPracticalModal'));
                console.log('Button element:', document.getElementById('createExamBtn'));
                console.log('Form element:', document.getElementById('createPracticalForm'));
            }
        };

        // Log when script is executed
        console.log('✅ [SCRIPT] Modal management script loaded and ready');
        console.log('💡 [TIP] Open browser console and type: debugModal.status() to check modal status');
        console.log('💡 [TIP] Or type: debugModal.show() to manually open the modal');

        // ========== MARKING FORM FUNCTIONS ==========
        let markingModalInstance = null;

        function openMarkingForm(submissionId, studentName, maxMarks, marks, feedback) {
            document.getElementById('markSubmissionId').value = submissionId;
            document.getElementById('markStudentName').textContent = studentName;
            document.getElementById('marksObtained').value = marks || '';
            document.getElementById('markFeedback').value = feedback || '';
            document.getElementById('maxMarks').textContent = maxMarks;
            document.getElementById('marksObtained').max = maxMarks;

            if (!markingModalInstance) {
                markingModalInstance = new bootstrap.Modal(document.getElementById('markingModal'));
            }
            markingModalInstance.show();
        }

        // ========== TAB SWITCHING ==========
        function switchTab(event, tabName) {
            console.log('🔵 Switching to tab:', tabName);
            
            // Prevent default behavior
            if (event) {
                event.preventDefault();
            }
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('active');
            });

            // Show selected tab
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
                console.log('✅ Tab activated:', tabName);
            }
            
            // Set the clicked button as active
            if (event && event.target) {
                event.target.classList.add('active');
            }
        }
        
        // Initialize tab buttons on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ Initializing tab buttons');
            const tabButtons = document.querySelectorAll('.tab-button');
            
            tabButtons.forEach((button, index) => {
                console.log('Tab button ' + index + ':', button.textContent.trim());
                
                // Remove existing onclick if any
                button.onclick = null;
                
                // Add click event listener
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    
                    // Determine which tab to switch to
                    const tabName = button.textContent.includes('Certificates') ? 'certificates' : 'exams';
                    console.log('🔵 Tab button clicked:', tabName);
                    switchTab(event, tabName);
                });
            });
            
            console.log('✅ Tab buttons initialized');
        });
    </script>
</body>
</html>
