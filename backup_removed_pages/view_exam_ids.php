<?php
/**
 * View Theory Exam IDs
 * Shows all theory exams with their IDs for easy reference
 */

require_once 'config.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get all theory exams
$query = "
    SELECT 
        e.id as exam_id,
        e.exam_name,
        s.subject_name,
        t.trade_name,
        u.full_name as created_by,
        e.total_marks,
        e.exam_type,
        e.created_at,
        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN trades t ON e.trade_id = t.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.exam_type = 'theory'
    ORDER BY e.created_at DESC
";

$exams = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theory Exam IDs Reference</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #48bb78;
            --danger: #f56565;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container-main {
            max-width: 1000px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 25px;
            position: relative;
        }

        .card-title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .card-body {
            padding: 30px;
        }

        table {
            width: 100%;
            margin-top: 20px;
        }

        th {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            color: var(--primary);
            font-weight: 700;
            padding: 15px;
            text-align: left;
            border: none;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        tr:hover {
            background: #f7fafc;
        }

        .exam-id-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .exam-id-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .copy-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .copy-btn:hover {
            background: #37a76e;
            transform: translateY(-2px);
        }

        .info-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            border-left: 5px solid var(--primary);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .info-box strong {
            color: var(--primary);
        }

        .no-exams {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .no-exams i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 15px;
            display: block;
        }

        .quick-copy {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }

        .quick-copy label {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: block;
        }

        .exam-list-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            padding: 8px;
            background: white;
            border-radius: 6px;
        }

        .exam-list-row strong {
            color: var(--primary);
            min-width: 80px;
        }

        .badge-info {
            display: inline-block;
            background: #e8ecff;
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-list-check"></i> Theory Exam IDs Reference
                </h1>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <strong>💡 How to use:</strong> When creating a practical exam, select the exam ID from the "Link Theory Exam" dropdown. 
                    Or copy the exam ID from this list and paste it in your practical exam form.
                </div>

                <?php if (empty($exams)): ?>
                    <div class="no-exams">
                        <i class="fas fa-inbox"></i>
                        <p><strong>No theory exams created yet</strong></p>
                        <p style="margin-top: 10px;">Create a theory exam first, then come back here to see its ID.</p>
                        <a href="admin/exam_add.php" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Create Theory Exam
                        </a>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 80px;"><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-book"></i> Exam Name</th>
                                <th><i class="fas fa-tag"></i> Subject</th>
                                <th><i class="fas fa-layer-group"></i> Trade</th>
                                <th><i class="fas fa-star"></i> Marks</th>
                                <th><i class="fas fa-question-circle"></i> Questions</th>
                                <th style="width: 120px;"><i class="fas fa-copy"></i> Copy</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr>
                                    <td>
                                        <span class="exam-id-badge"><?= $exam['exam_id'] ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($exam['exam_name']) ?></strong>
                                        <br>
                                        <small style="color: #718096;">Created: <?= date('d-m-Y H:i', strtotime($exam['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-info"><?= htmlspecialchars($exam['subject_name'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <span class="badge-info"><?= htmlspecialchars($exam['trade_name'] ?? 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--success); font-size: 1.1rem;"><?= $exam['total_marks'] ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-white"><?= $exam['question_count'] ?> Qs</span>
                                    </td>
                                    <td>
                                        <button class="copy-btn" onclick="copyToClipboard('<?= $exam['exam_id'] ?>', '<?= htmlspecialchars($exam['exam_name']) ?>')">
                                            <i class="fas fa-copy"></i> Copy ID
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Quick Copy Section -->
                    <div class="quick-copy">
                        <label><i class="fas fa-clipboard"></i> Quick Reference - All Exam IDs:</label>
                        <div>
                            <?php foreach ($exams as $exam): ?>
                                <div class="exam-list-row">
                                    <strong>[<?= $exam['exam_id'] ?>]</strong>
                                    <span><?= htmlspecialchars($exam['exam_name']) ?></span>
                                    <span style="color: #a0aec0; font-size: 0.9rem;">- <?= htmlspecialchars($exam['subject_name']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 25px; text-align: center;">
                    <a href="teacher/practical_create_exam.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Create Practical Exam
                    </a>
                    <a href="javascript:window.history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Go Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text, name) {
            navigator.clipboard.writeText(text).then(function() {
                const btn = event.target.closest('.copy-btn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                btn.style.background = 'var(--success)';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = 'var(--success)';
                }, 2000);
            });
        }
    </script>
</body>
</html>
