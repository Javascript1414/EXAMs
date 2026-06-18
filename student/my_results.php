<?php
/**
 * Student: Exam Results with Teacher Info
 * Shows exam results with teacher details and comments
 */

require_once '../config.php';
require_once '../includes/db.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['role_name'] !== 'student') {
    http_response_code(403);
    die('Access Denied');
}

$student_id = $_SESSION['user_id'];

// Get student info
$student_query = "SELECT full_name, email FROM users WHERE id = ?";
$student_stmt = $pdo->prepare($student_query);
$student_stmt->execute([$student_id]);
$student = $student_stmt->fetch(PDO::FETCH_ASSOC);

// Get exam results with teacher info
$results_query = "
    SELECT 
        r.id,
        e.exam_name,
        s.subject_name,
        t.trade_name,
        u.full_name as teacher_name,
        u.email as teacher_email,
        r.obtained_marks,
        e.total_marks,
        ROUND((r.obtained_marks / e.total_marks * 100), 2) as percentage,
        r.attempt_date,
        r.status,
        r.feedback
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN trades t ON e.trade_id = t.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE r.student_id = ?
    ORDER BY r.attempt_date DESC
";
$results_stmt = $pdo->prepare($results_query);
$results_stmt->execute([$student_id]);
$results = $results_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get overall statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_exams,
        AVG(ROUND((r.obtained_marks / e.total_marks * 100), 2)) as avg_percentage,
        MAX(ROUND((r.obtained_marks / e.total_marks * 100), 2)) as best_percentage,
        MIN(ROUND((r.obtained_marks / e.total_marks * 100), 2)) as worst_percentage
    FROM exam_results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
";
$stats_stmt = $pdo->prepare($stats_query);
$stats_stmt->execute([$student_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Exam Results - Student Dashboard</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 { color: #667eea; font-size: 0.95em; margin-bottom: 10px; }
        .stat-card .value { font-size: 2em; font-weight: bold; color: #333; }
        
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
        
        .result-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .result-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .result-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .result-title h3 { color: #333; font-size: 1.2em; margin-bottom: 5px; }
        .result-title p { color: #666; font-size: 0.9em; }
        
        .marks {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .marks p { color: #666; }
        .marks .score { font-size: 1.3em; font-weight: bold; color: #667eea; }
        
        .percentage {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        
        .percentage .value { font-size: 2em; font-weight: bold; }
        .percentage p { opacity: 0.9; margin-top: 5px; }
        
        .result-teacher {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid #667eea;
        }
        
        .result-teacher h4 { color: #667eea; margin-bottom: 10px; font-size: 0.95em; }
        .teacher-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .teacher-info p { color: #666; font-size: 0.9em; }
        
        .result-feedback {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid #2196f3;
        }
        
        .result-feedback h4 { color: #1565c0; margin-bottom: 8px; }
        .result-feedback p { color: #333; }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-subject { background: #c8e6c9; color: #2e7d32; }
        .badge-trade { background: #bbdefb; color: #1565c0; }
        .badge-pass { background: #c8e6c9; color: #2e7d32; }
        .badge-fail { background: #ffcdd2; color: #c62828; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 My Exam Results</h1>
            <p>Student: <?= htmlspecialchars($student['full_name']) ?> | <?= htmlspecialchars($student['email']) ?></p>
        </div>
        
        <!-- Statistics -->
        <?php if (!empty($stats['total_exams'])): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>📝 Total Exams</h3>
                    <div class="value"><?= $stats['total_exams'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>📈 Average Score</h3>
                    <div class="value"><?= round($stats['avg_percentage'], 1) ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>⭐ Best Score</h3>
                    <div class="value"><?= round($stats['best_percentage'], 1) ?>%</div>
                </div>
                <div class="stat-card">
                    <h3>📉 Lowest Score</h3>
                    <div class="value"><?= round($stats['worst_percentage'], 1) ?>%</div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Results Section -->
        <div class="section">
            <h2>📋 Exam Results</h2>
            
            <?php if (empty($results)): ?>
                <div class="empty-state">
                    <p>No exam results yet. Complete some exams to see your results.</p>
                </div>
            <?php else: ?>
                <?php foreach ($results as $result): ?>
                    <div class="result-card">
                        <div class="result-header">
                            <div class="result-title">
                                <h3><?= htmlspecialchars($result['exam_name']) ?></h3>
                                <p>
                                    <span class="badge badge-subject"><?= htmlspecialchars($result['subject_name'] ?? 'N/A') ?></span>
                                    <span class="badge badge-trade"><?= htmlspecialchars($result['trade_name'] ?? 'N/A') ?></span>
                                </p>
                            </div>
                            
                            <div class="marks">
                                <p>Score Obtained</p>
                                <div class="score"><?= $result['obtained_marks'] ?> / <?= $result['total_marks'] ?></div>
                            </div>
                            
                            <div class="percentage">
                                <div class="value"><?= number_format($result['percentage'], 1) ?>%</div>
                                <p><?= $result['percentage'] >= 50 ? '✅ Passed' : '❌ Failed' ?></p>
                            </div>
                        </div>
                        
                        <!-- Attempt Date -->
                        <p style="color: #666; font-size: 0.9em; margin-bottom: 15px;">
                            📅 Attempted on: <?= date('M d, Y H:i', strtotime($result['attempt_date'])) ?>
                        </p>
                        
                        <!-- Teacher Information -->
                        <?php if ($result['teacher_name']): ?>
                            <div class="result-teacher">
                                <h4>👨‍🏫 Teacher Information</h4>
                                <div class="teacher-info">
                                    <p><strong>Teacher Name:</strong> <?= htmlspecialchars($result['teacher_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($result['teacher_email']) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Feedback from Teacher -->
                        <?php if (!empty($result['feedback'])): ?>
                            <div class="result-feedback">
                                <h4>💬 Teacher Feedback</h4>
                                <p><?= htmlspecialchars($result['feedback']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
