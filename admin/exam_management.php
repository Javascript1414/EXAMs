<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_name']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin', 'moderator'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get all exams
$exams = $pdo->query("SELECT e.id, e.exam_name, e.status, e.duration_minutes, 
                           COUNT(eq.id) as q_count,
                           COUNT(DISTINCT ea.attempt_id) as attempt_count
                    FROM exams e
                    LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                    LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
                    GROUP BY e.id
                    ORDER BY e.id DESC")->fetchAll();

// Handle actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['exam_id'])) {
        $exam_id = (int)$_POST['exam_id'];
        
        if ($_POST['action'] === 'publish') {
            $pdo->prepare("UPDATE exams SET status = 'published' WHERE id = ?")->execute([$exam_id]);
            $message = "✅ Exam published successfully!";
        } elseif ($_POST['action'] === 'draft') {
            $pdo->prepare("UPDATE exams SET status = 'draft' WHERE id = ?")->execute([$exam_id]);
            $message = "✅ Exam moved to draft!";
        } elseif ($_POST['action'] === 'link_questions') {
            // Link last 25 questions to this exam
            $questions = $pdo->query("SELECT id FROM questions ORDER BY id DESC LIMIT 25")->fetchAll(PDO::FETCH_COLUMN);
            $pdo->prepare("DELETE FROM exam_questions WHERE exam_id = ?")->execute([$exam_id]);
            
            $insert = $pdo->prepare("INSERT INTO exam_questions (exam_id, question_id) VALUES (?, ?)");
            foreach ($questions as $qid) {
                $insert->execute([$exam_id, $qid]);
            }
            $message = "✅ " . count($questions) . " questions linked to exam!";
        }
        
        // Refresh exams list
        $exams = $pdo->query("SELECT e.id, e.exam_name, e.status, e.duration_minutes, 
                                   COUNT(eq.id) as q_count,
                                   COUNT(DISTINCT ea.attempt_id) as attempt_count
                            FROM exams e
                            LEFT JOIN exam_questions eq ON e.id = eq.exam_id
                            LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
                            GROUP BY e.id
                            ORDER BY e.id DESC")->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .badge { padding: 8px 12px; font-size: 12px; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .status-published { background: #4CAF50; }
        .status-draft { background: #FFC107; }
        .table-header { background: #1a237e; color: white; }
        .stat-box { 
            background: white;
            border-left: 4px solid #1a237e;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .stat-number { font-size: 24px; font-weight: bold; color: #1a237e; }
        .stat-label { font-size: 12px; color: #666; }
    </style>
</head>
<body>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="mb-4">📊 Exam Management Dashboard</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number"><?= count($exams) ?></div>
                        <div class="stat-label">Total Exams</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number"><?= count(array_filter($exams, fn($e) => $e['status'] === 'published')) ?></div>
                        <div class="stat-label">Published</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number"><?= array_sum(array_column($exams, 'attempt_count')) ?></div>
                        <div class="stat-label">Total Attempts</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-box">
                        <div class="stat-number"><?= $pdo->query("SELECT COUNT(*) as cnt FROM questions")->fetch()['cnt'] ?></div>
                        <div class="stat-label">Questions</div>
                    </div>
                </div>
            </div>

            <!-- Exams Table -->
            <div class="card">
                <div class="card-header table-header">
                    <h5 class="mb-0">All Exams</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Exam ID</th>
                                <th>Exam Name</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Questions</th>
                                <th>Attempts</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><strong><?= $exam['id'] ?></strong></td>
                                <td><?= htmlspecialchars($exam['exam_name']) ?></td>
                                <td>
                                    <span class="badge status-<?= $exam['status'] ?>">
                                        <?= ucfirst($exam['status']) ?>
                                    </span>
                                </td>
                                <td><?= $exam['duration_minutes'] ?> min</td>
                                <td><span class="badge bg-info"><?= $exam['q_count'] ?></span></td>
                                <td><span class="badge bg-secondary"><?= $exam['attempt_count'] ?></span></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>">
                                        
                                        <?php if ($exam['status'] === 'draft'): ?>
                                            <button type="submit" name="action" value="publish" class="btn btn-success btn-sm">Publish</button>
                                        <?php else: ?>
                                            <button type="submit" name="action" value="draft" class="btn btn-warning btn-sm">Draft</button>
                                        <?php endif; ?>
                                        
                                        <button type="submit" name="action" value="link_questions" class="btn btn-info btn-sm">Link Questions</button>
                                        <a href="exam_assign_questions.php?exam_id=<?= $exam['id'] ?>" class="btn btn-primary btn-sm">Manage Questions</a>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header table-header">
                    <h5 class="mb-0">Exam Details</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($exams as $exam): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <h6>Exam #<?= $exam['id'] ?>: <?= htmlspecialchars($exam['exam_name']) ?></h6>
                        <small class="text-muted">
                            Status: <?= $exam['status'] ?> | 
                            Duration: <?= $exam['duration_minutes'] ?> min | 
                            Questions: <?= $exam['q_count'] ?> | 
                            Attempts: <?= $exam['attempt_count'] ?>
                        </small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
