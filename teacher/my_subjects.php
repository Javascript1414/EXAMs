<?php
/**
 * Teacher: My Subjects Dashboard
 * Shows subjects assigned to teacher and allows exam creation
 */

require_once '../config.php';
require_once '../includes/db.php';

// Check if user is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'teacher') {
    http_response_code(403);
    die('Access Denied');
}

$teacher_id = $_SESSION['user_id'];

// Get teacher info
$teacher_query = "SELECT full_name, email FROM users WHERE id = ?";
$teacher_stmt = $pdo->prepare($teacher_query);
$teacher_stmt->execute([$teacher_id]);
$teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);

// Get assigned subjects
$subjects_query = "
    SELECT DISTINCT s.id, s.subject_name, t.trade_name, t.id as trade_id
    FROM subject_teacher st
    JOIN subjects s ON st.subject_id = s.id
    JOIN trades t ON s.trade_id = t.id
    WHERE st.teacher_id = ?
    ORDER BY t.trade_name, s.subject_name
";
$subjects_stmt = $pdo->prepare($subjects_query);
$subjects_stmt->execute([$teacher_id]);
$subjects = $subjects_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get exams created by this teacher
$exams_query = "
    SELECT e.id, e.exam_name, s.subject_name, t.trade_name, 
           e.total_questions, e.exam_date, e.status
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN trades t ON e.trade_id = t.id
    WHERE e.created_by = ?
    ORDER BY e.exam_date DESC
    LIMIT 10
";
$exams_stmt = $pdo->prepare($exams_query);
$exams_stmt->execute([$teacher_id]);
$exams = $exams_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Subjects - Teacher Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 { color: #333; margin-bottom: 5px; }
        .header p { color: #666; }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.4em;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .subject-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .subject-card h3 { font-size: 1.3em; margin-bottom: 10px; }
        .subject-card p { opacity: 0.9; margin-bottom: 15px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
        }
        
        .btn:hover {
            background: #f0f0f0;
            transform: translateX(2px);
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-subject {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .badge-trade {
            background: #bbdefb;
            color: #1565c0;
        }
        
        .badge-status {
            background: #fff3e0;
            color: #e65100;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state p { font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>👋 Welcome, <?= htmlspecialchars($teacher['full_name']) ?>!</h1>
            <p>📧 <?= htmlspecialchars($teacher['email']) ?></p>
        </div>
        
        <!-- My Subjects Section -->
        <div class="section">
            <h2>📚 My Assigned Subjects</h2>
            
            <?php if (empty($subjects)): ?>
                <div class="empty-state">
                    <p>No subjects assigned yet. Please contact admin.</p>
                </div>
            <?php else: ?>
                <div class="subjects-grid">
                    <?php foreach ($subjects as $subject): ?>
                        <div class="subject-card">
                            <h3><?= htmlspecialchars($subject['subject_name']) ?></h3>
                            <p>🏢 <?= htmlspecialchars($subject['trade_name']) ?></p>
                            <a href="create_exam.php?subject_id=<?= $subject['id'] ?>" class="btn">
                                ➕ Create Exam
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Exams Section -->
        <div class="section">
            <h2>📝 My Recent Exams</h2>
            
            <?php if (empty($exams)): ?>
                <div class="empty-state">
                    <p>No exams created yet.</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Exam Name</th>
                                <th>Subject</th>
                                <th>Trade</th>
                                <th>Questions</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($exam['exam_name']) ?></strong></td>
                                    <td><span class="badge badge-subject"><?= htmlspecialchars($exam['subject_name'] ?? 'N/A') ?></span></td>
                                    <td><span class="badge badge-trade"><?= htmlspecialchars($exam['trade_name'] ?? 'N/A') ?></span></td>
                                    <td><strong><?= $exam['total_questions'] ?? 0 ?></strong></td>
                                    <td><?= $exam['exam_date'] ? date('M d, Y', strtotime($exam['exam_date'])) : 'Not set' ?></td>
                                    <td><span class="badge badge-status"><?= htmlspecialchars($exam['status'] ?? 'draft') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
