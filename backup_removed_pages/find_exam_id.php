<?php
/**
 * Find Exam ID After Creating & Assigning Questions
 * Shows the exam with its ID for linking with practical
 */

require_once 'config.php';
require_once 'includes/db.php';

// Get all exams with question counts, ordered by most recent
$exams_query = $pdo->query("
    SELECT 
        e.id as exam_id,
        e.exam_name,
        s.subject_name,
        t.trade_name,
        e.total_marks,
        e.exam_type,
        u.full_name as created_by,
        e.created_at,
        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN trades t ON e.trade_id = t.id
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.exam_type = 'theory'
    ORDER BY e.created_at DESC
");

$all_exams = $exams_query->fetchAll(PDO::FETCH_ASSOC);

// Get the most recent exam
$latest_exam = !empty($all_exams) ? $all_exams[0] : null;

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Exam ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #48bb78;
            --danger: #f56565;
            --warning: #ed8936;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 20px;
        }

        .container-main {
            max-width: 1100px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
            padding: 25px;
        }

        .card-title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .card-body {
            padding: 30px;
        }

        .latest-exam-box {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            border: 3px solid var(--primary);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .latest-exam-box strong {
            color: var(--primary);
            display: block;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .exam-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
        }

        .exam-detail:last-child {
            border-bottom: none;
        }

        .exam-detail-label {
            color: #718096;
            font-weight: 600;
        }

        .exam-detail-value {
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
        }

        .exam-id-display {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin: 20px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .exam-id-display:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .exam-id-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 10px;
        }

        .exam-id-value {
            font-size: 3rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .copy-icon {
            font-size: 1.5rem;
            opacity: 0.8;
        }

        .copy-hint {
            font-size: 0.8rem;
            margin-top: 10px;
            opacity: 0.8;
        }

        .next-step {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 5px solid var(--success);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .next-step strong {
            color: var(--success);
            display: block;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .next-step p {
            margin: 0;
            color: #166534;
            line-height: 1.6;
        }

        .next-step ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #166534;
        }

        .next-step li {
            margin: 6px 0;
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

        .exam-row-highlight {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
            font-weight: 600;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-questions {
            background: #e0e7ff;
            color: var(--primary);
        }

        .badge-marks {
            background: #d1fae5;
            color: #047857;
        }

        .id-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .id-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 700;
            border: none;
            transition: all 0.3s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
            color: white;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #37a76e;
            transform: translateY(-3px);
            color: white;
        }

        .no-exams {
            text-align: center;
            padding: 40px;
        }

        .no-exams i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 15px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-id-badge"></i> Find Your Exam ID
                </h1>
            </div>
            <div class="card-body">
                <?php if ($latest_exam): ?>
                    <!-- Latest Exam Section -->
                    <div class="latest-exam-box">
                        <strong><i class="fas fa-star" style="color: #f59e0b;"></i> Your Latest Exam (After Assigning Questions)</strong>
                        
                        <div class="exam-detail">
                            <span class="exam-detail-label">Exam Name:</span>
                            <span class="exam-detail-value"><?= htmlspecialchars($latest_exam['exam_name']) ?></span>
                        </div>

                        <div class="exam-detail">
                            <span class="exam-detail-label">Subject:</span>
                            <span class="exam-detail-value"><?= htmlspecialchars($latest_exam['subject_name'] ?? 'N/A') ?></span>
                        </div>

                        <div class="exam-detail">
                            <span class="exam-detail-label">Trade:</span>
                            <span class="exam-detail-value"><?= htmlspecialchars($latest_exam['trade_name'] ?? 'N/A') ?></span>
                        </div>

                        <div class="exam-detail">
                            <span class="exam-detail-label">Total Marks:</span>
                            <span class="exam-detail-value"><?= $latest_exam['total_marks'] ?></span>
                        </div>

                        <div class="exam-detail">
                            <span class="exam-detail-label">Questions Assigned:</span>
                            <span class="exam-detail-value">
                                <span class="badge badge-questions">
                                    <i class="fas fa-question-circle"></i> <?= $latest_exam['question_count'] ?> Questions
                                </span>
                            </span>
                        </div>

                        <div class="exam-detail">
                            <span class="exam-detail-label">Created On:</span>
                            <span class="exam-detail-value"><?= date('d-M-Y H:i', strtotime($latest_exam['created_at'])) ?></span>
                        </div>
                    </div>

                    <!-- BIG ID Display -->
                    <div class="exam-id-display" onclick="copyToClipboard('<?= $latest_exam['exam_id'] ?>', '<?= htmlspecialchars($latest_exam['exam_name']) ?>')">
                        <div class="exam-id-label">
                            <i class="fas fa-copy"></i> Click to Copy Your Exam ID
                        </div>
                        <div class="exam-id-value">
                            <span><?= $latest_exam['exam_id'] ?></span>
                        </div>
                        <div class="copy-hint">
                            <i class="fas fa-mouse-pointer"></i> Click the ID above to copy it
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="next-step">
                        <strong><i class="fas fa-arrow-right"></i> Next: Create Practical Exam with This ID</strong>
                        <p>Now that you have the ID, you can:</p>
                        <ul>
                            <li>Go to <strong>"Create Practical Exam"</strong></li>
                            <li>Select <strong>"Link Theory Exam"</strong> dropdown</li>
                            <li>Choose: <strong><?= htmlspecialchars($latest_exam['exam_name']) ?></strong></li>
                            <li>This will link your practical exam with this theory exam</li>
                            <li>When you assign marks, everything will work perfectly! ✅</li>
                        </ul>
                    </div>

                    <!-- All Exams Table -->
                    <div style="margin-top: 40px;">
                        <h2 style="color: var(--primary); font-weight: 700; margin-bottom: 20px;">
                            <i class="fas fa-list"></i> All Your Theory Exams
                        </h2>

                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 60px;">ID</th>
                                    <th>Exam Name</th>
                                    <th>Subject</th>
                                    <th style="width: 100px;">Questions</th>
                                    <th style="width: 80px;">Marks</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_exams as $exam): ?>
                                    <tr class="<?= $exam['exam_id'] == $latest_exam['exam_id'] ? 'exam-row-highlight' : '' ?>">
                                        <td>
                                            <span class="id-badge" onclick="copyToClipboard('<?= $exam['exam_id'] ?>', '<?= htmlspecialchars($exam['exam_name']) ?>')">
                                                <?= $exam['exam_id'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($exam['exam_name']) ?></strong>
                                            <?php if ($exam['exam_id'] == $latest_exam['exam_id']): ?>
                                                <br><small style="color: var(--success); font-weight: 600;">← Latest</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-marks"><?= htmlspecialchars($exam['subject_name'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-questions"><?= $exam['question_count'] ?></span>
                                        </td>
                                        <td>
                                            <strong><?= $exam['total_marks'] ?></strong>
                                        </td>
                                        <td>
                                            <small><?= date('d-M-Y', strtotime($exam['created_at'])) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/admin/practical_exams.php" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Create Practical Exam Now
                        </a>
                        <a href="<?= BASE_URL ?>/link_theory_practical.php" class="btn btn-primary">
                            <i class="fas fa-question-circle"></i> Need Help? View Guide
                        </a>
                    </div>

                <?php else: ?>
                    <div class="no-exams">
                        <i class="fas fa-inbox"></i>
                        <h3>No Exams Found</h3>
                        <p>Create a theory exam and assign questions to it first.</p>
                        <a href="<?= BASE_URL ?>/admin/exams.php" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Create Exam
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(examId, examName) {
            navigator.clipboard.writeText(examId).then(function() {
                // Show success message
                const message = document.createElement('div');
                message.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
                    color: white;
                    padding: 16px 24px;
                    border-radius: 10px;
                    font-weight: 700;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    z-index: 9999;
                    animation: slideIn 0.3s ease;
                `;
                message.innerHTML = `<i class="fas fa-check-circle"></i> ID Copied: <strong>${examId}</strong>`;
                document.body.appendChild(message);
                
                setTimeout(() => {
                    message.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => message.remove(), 300);
                }, 2000);
            });
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
