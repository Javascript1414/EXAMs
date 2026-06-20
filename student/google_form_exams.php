<?php
/**
 * Student: Google Form Exams
 * Students can view and access Google Form exams assigned to them
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

requireRole('student');

$student_id = $_SESSION['user_id'];
$student_trade_id = $_SESSION['trade_id'] ?? 0;

// Get filter parameters
$filter_subject = isset($_GET['subject']) ? (int)$_GET['subject'] : 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get all Google Form exams for student's trade
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

$params = [$student_id, $student_trade_id];

if ($filter_subject > 0) {
    $query .= " AND gfe.subject_id = ?";
    $params[] = $filter_subject;
}

if ($filter_status !== 'all') {
    if ($filter_status === 'completed') {
        $query .= " AND gfea.marks_obtained IS NOT NULL";
    } elseif ($filter_status === 'pending') {
        $query .= " AND gfea.marks_obtained IS NULL AND gfe.exam_date <= NOW()";
    } elseif ($filter_status === 'upcoming') {
        $query .= " AND gfe.exam_date > NOW()";
    }
}

$query .= " ORDER BY gfe.exam_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get subjects for filter
$subjects = $pdo->prepare("
    SELECT DISTINCT s.id, s.subject_name
    FROM subjects s
    WHERE s.trade_id = ?
    ORDER BY s.subject_name
");
$subjects->execute([$student_trade_id]);
$subjects_list = $subjects->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT gfe.id) as total_exams,
        SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN gfea.marks_obtained IS NULL AND gfe.exam_date <= NOW() THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN gfe.exam_date > NOW() THEN 1 ELSE 0 END) as upcoming,
        AVG(CASE WHEN gfea.marks_obtained IS NOT NULL THEN gfea.marks_obtained ELSE NULL END) as avg_marks
    FROM google_form_exams gfe
    LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id AND gfea.student_id = ?
    WHERE gfe.status = 'published' AND gfe.subject_id IN (
        SELECT id FROM subjects WHERE trade_id = ?
    )
");
$stats->execute([$student_id, $student_trade_id]);
$stats = $stats->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Form Exams - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
            --info-color: #4299e1;
        }

        body {
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.completed {
            border-left-color: var(--success-color);
        }

        .stat-card.pending {
            border-left-color: var(--warning-color);
        }

        .stat-card.upcoming {
            border-left-color: var(--info-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-card.completed .stat-value {
            color: var(--success-color);
        }

        .stat-card.pending .stat-value {
            color: var(--warning-color);
        }

        .stat-card.upcoming .stat-value {
            color: var(--info-color);
        }

        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .filters h5 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 15px;
        }

        .filter-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-filter {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-reset {
            background: transparent;
            border: 2px solid #e2e8f0;
            color: #718096;
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset:hover {
            background: #f7fafc;
        }

        .exam-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-top: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .exam-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .exam-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .exam-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .exam-status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-upcoming {
            background: rgba(66, 153, 225, 0.2);
            color: var(--info-color);
        }

        .status-completed {
            background: rgba(72, 187, 120, 0.2);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(237, 137, 54, 0.2);
            color: var(--warning-color);
        }

        .exam-subject {
            color: #718096;
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .exam-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .marks-display {
            background: rgba(72, 187, 120, 0.1);
            padding: 10px 15px;
            border-radius: 8px;
            margin: 5px 0;
        }

        .marks-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .result-pass {
            color: var(--success-color);
            font-weight: 600;
        }

        .result-fail {
            color: var(--danger-color);
            font-weight: 600;
        }

        .instructions {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid var(--primary-color);
        }

        .instructions h6 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .instructions p {
            margin: 0;
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn-start {
            background: linear-gradient(135deg, var(--success-color) 0%, #38a169 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-start:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }

        .btn-view-results {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-view-results:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #cbd5e0;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #718096;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .exam-card-header {
                flex-direction: column;
                gap: 10px;
            }

            .exam-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-start, .btn-view-results {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <i class="bi bi-google" style="font-size: 2rem; color: var(--warning-color);"></i>
            <h1>Google Form Exams</h1>
        </div>

        <!-- Statistics Cards -->
        <?php if ($stats): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['total_exams'] ?? 0 ?></div>
                    <div class="stat-label">Total Exams</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-value"><?= $stats['completed'] ?? 0 ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-value"><?= $stats['pending'] ?? 0 ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card upcoming">
                    <div class="stat-value"><?= $stats['upcoming'] ?? 0 ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <h5><i class="bi bi-funnel"></i> Filters</h5>
            <form method="GET" class="filter-group">
                <div>
                    <select name="subject" class="form-select">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects_list as $subj): ?>
                            <option value="<?= $subj['id'] ?>" <?= $filter_subject == $subj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subj['subject_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <select name="status" class="form-select">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="upcoming" <?= $filter_status === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending (Marks)</option>
                        <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn-filter">
                        <i class="bi bi-search"></i> Apply Filter
                    </button>
                    <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn-reset">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Exams List -->
        <?php if (count($exams) === 0): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>No Google Form Exams Available</h3>
                <p>There are no published Google Form exams available for your course at this time.</p>
            </div>
        <?php else: ?>
            <?php foreach ($exams as $exam): ?>
                <?php
                    $exam_date = new DateTime($exam['exam_date']);
                    $now = new DateTime();
                    $is_upcoming = $exam_date > $now;
                    $is_completed = !is_null($exam['marks_obtained']);
                    
                    if ($is_completed) {
                        $status = 'completed';
                        $status_label = '✓ Completed';
                    } elseif ($is_upcoming) {
                        $status = 'upcoming';
                        $status_label = 'Upcoming';
                    } else {
                        $status = 'pending';
                        $status_label = 'Pending (Marks to be entered)';
                    }
                ?>
                <div class="exam-card">
                    <div class="exam-card-header">
                        <div style="flex: 1;">
                            <h4 class="exam-title"><?= htmlspecialchars($exam['exam_title']) ?></h4>
                            <div class="exam-subject">
                                <i class="bi bi-layers"></i> <?= htmlspecialchars($exam['subject_name']) ?>
                                <span style="color: #cbd5e0;"> • <?= htmlspecialchars($exam['trade_name']) ?></span>
                            </div>
                        </div>
                        <span class="exam-status-badge status-<?= $status ?>">
                            <?= $status_label ?>
                        </span>
                    </div>

                    <div class="exam-details">
                        <div class="detail-item">
                            <span class="detail-label">Exam Date</span>
                            <span class="detail-value"><?= date('M d, Y', strtotime($exam['exam_date'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Marks</span>
                            <span class="detail-value"><?= $exam['total_marks'] ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Pass Marks</span>
                            <span class="detail-value"><?= $exam['pass_marks'] ?></span>
                        </div>
                        <?php if ($is_completed): ?>
                            <div class="detail-item">
                                <span class="detail-label">Your Result</span>
                                <span class="detail-value">
                                    <span class="<?= $exam['result_status'] === 'pass' ? 'result-pass' : 'result-fail' ?>">
                                        <?= strtoupper($exam['result_status']) ?>
                                    </span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($is_completed): ?>
                        <div class="marks-display">
                            <small style="color: #718096;">Your Marks</small>
                            <div class="marks-value"><?= $exam['marks_obtained'] ?>/<?= $exam['total_marks'] ?></div>
                            <small style="color: #a0aec0;">
                                Entered on <?= date('M d, Y', strtotime($exam['marks_entered_at'])) ?>
                            </small>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($exam['instructions'])): ?>
                        <div class="instructions">
                            <h6><i class="bi bi-info-circle"></i> Instructions</h6>
                            <p><?= nl2br(htmlspecialchars($exam['instructions'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <?php if (!$is_completed && $is_upcoming): ?>
                            <a href="<?= htmlspecialchars($exam['google_form_link']) ?>" target="_blank" class="btn-start">
                                <i class="bi bi-box-arrow-up-right"></i> Start Exam
                            </a>
                        <?php elseif (!$is_completed && !$is_upcoming): ?>
                            <a href="<?= htmlspecialchars($exam['google_form_link']) ?>" target="_blank" class="btn-start">
                                <i class="bi bi-box-arrow-up-right"></i> Complete Exam
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($is_completed): ?>
                            <button class="btn-view-results" onclick="viewResults(<?= $exam['id'] ?>)">
                                <i class="bi bi-graph-up"></i> View Results
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewResults(examId) {
            // Redirect to results page
            window.location.href = './exam_results.php?exam_id=' + examId;
        }
    </script>
</body>
</html>
