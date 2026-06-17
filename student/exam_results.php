<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('student');

// Get student's exam results
$results = $pdo->prepare("
    SELECT r.*, e.exam_name, e.total_marks as max_marks
    FROM results r
    JOIN exams e ON r.exam_id = e.id
    WHERE r.student_id = ?
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get pending exams
$pending = $pdo->prepare("
    SELECT e.* 
    FROM exams e
    WHERE e.status = 'published'
    AND e.id NOT IN (SELECT exam_id FROM results WHERE student_id = ?)
    ORDER BY e.id
")->fetchAll(PDO::FETCH_ASSOC);

// Get exam attempts in progress
$in_progress = $pdo->prepare("
    SELECT ea.*, e.exam_name 
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.student_id = ? AND ea.status = 'in_progress'
    ORDER BY ea.started_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exam Results - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .result-card {
            background: white;
            border-left: 4px solid;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .result-card.passed { border-left-color: #4CAF50; }
        .result-card.failed { border-left-color: #f44336; }
        .score { font-size: 32px; font-weight: bold; }
        .percentage { font-size: 24px; font-weight: bold; }
        .progress-bar-animated { background: linear-gradient(90deg, #4CAF50, #45a049); }
        .stat-box { 
            background: white;
            border: 1px solid #e0e0e0;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-number { font-size: 28px; font-weight: bold; color: #1a237e; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .table-header { background: #1a237e; color: white; }
        .badge-pending { background: #FFC107; }
        .badge-in-progress { background: #2196F3; }
        .badge-completed { background: #4CAF50; }
    </style>
</head>
<body>

<div class="container">
    <h1 class="mb-4">📊 My Exam Results</h1>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-number"><?= count($results) ?></div>
                <div class="stat-label">Exams Taken</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-number"><?= count($in_progress) ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-number"><?= count($pending) ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-number"><?= $results ? round(array_sum(array_column($results, 'percentage')) / count($results), 1) : 0 ?>%</div>
                <div class="stat-label">Average Score</div>
            </div>
        </div>
    </div>

    <!-- In Progress Exams -->
    <?php if (count($in_progress) > 0): ?>
    <div class="card">
        <div class="card-header table-header">
            <h5 class="mb-0">🕐 Exams In Progress</h5>
        </div>
        <div class="card-body">
            <?php foreach ($in_progress as $attempt): ?>
            <div class="result-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= htmlspecialchars($attempt['exam_name']) ?></h6>
                        <small class="text-muted">Started: <?= date('d/m/Y H:i', strtotime($attempt['started_at'])) ?></small>
                    </div>
                    <a href="../student/exam_attempt.php?id=<?= $attempt['exam_id'] ?>" class="btn btn-primary btn-sm">Continue Exam</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Results -->
    <?php if (count($results) > 0): ?>
    <div class="card">
        <div class="card-header table-header">
            <h5 class="mb-0">✓ Completed Exams</h5>
        </div>
        <div class="card-body">
            <?php foreach ($results as $result): 
                $passed = $result['percentage'] >= 40;
            ?>
            <div class="result-card <?= $passed ? 'passed' : 'failed' ?>">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h6 class="mb-1"><?= htmlspecialchars($result['exam_name']) ?></h6>
                        <small class="text-muted">Submitted: <?= date('d/m/Y H:i', strtotime($result['created_at'])) ?></small>
                    </div>
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-4 text-end">
                                <div class="score"><?= round($result['obtained_marks'], 2) ?></div>
                                <small class="text-muted">/ <?= $result['max_marks'] ?></small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="percentage" style="color: <?= $passed ? '#4CAF50' : '#f44336' ?>">
                                    <?= round($result['percentage'], 1) ?>%
                                </div>
                                <small class="text-muted"><?= $passed ? 'PASS' : 'FAIL' ?></small>
                            </div>
                            <div class="col-4">
                                <div class="progress" style="height: 20px; border-radius: 10px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= $result['percentage'] ?>%; background: <?= $passed ? '#4CAF50' : '#f44336' ?>" 
                                         aria-valuenow="<?= $result['percentage'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Exams -->
    <?php if (count($pending) > 0): ?>
    <div class="card">
        <div class="card-header table-header">
            <h5 class="mb-0">📝 Available Exams</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Exam Name</th>
                        <th>Duration</th>
                        <th>Total Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $exam): ?>
                    <tr>
                        <td><?= htmlspecialchars($exam['exam_name']) ?></td>
                        <td><?= $exam['duration_minutes'] ?> minutes</td>
                        <td><?= $exam['total_marks'] ?></td>
                        <td>
                            <form action="../student/exam_start.php" method="POST" style="display: inline;">
                                <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-success btn-sm">Start Exam</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($results) === 0 && count($in_progress) === 0 && count($pending) === 0): ?>
    <div class="alert alert-info">
        <h5>No exams available yet.</h5>
        <p>Please check back later for available exams.</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
