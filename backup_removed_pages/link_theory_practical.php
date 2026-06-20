<?php
/**
 * Link Theory Exam with Practical Exam - Step by Step Guide
 */

require_once 'config.php';
require_once 'includes/db.php';

// Get the most recent theory exam
$latest_exam = $pdo->query("
    SELECT 
        e.id as exam_id,
        e.exam_name,
        s.subject_name,
        t.trade_name,
        e.total_marks,
        (SELECT COUNT(*) FROM exam_questions WHERE exam_id = e.id) as question_count,
        e.created_at
    FROM exams e
    LEFT JOIN subjects s ON e.subject_id = s.id
    LEFT JOIN trades t ON e.trade_id = t.id
    WHERE e.exam_type = 'theory'
    ORDER BY e.created_at DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
require_once 'includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Theory & Practical Exams</title>
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
            max-width: 1000px;
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
            font-size: 1.5rem;
            font-weight: 700;
        }

        .card-body {
            padding: 25px;
            background: white;
        }

        .step {
            padding: 20px;
            margin-bottom: 20px;
            background: #f7fafc;
            border-left: 5px solid var(--primary);
            border-radius: 8px;
            display: flex;
            gap: 20px;
        }

        .step-number {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .step-content h3 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .step-content p {
            margin: 0;
            color: #4a5568;
            line-height: 1.6;
        }

        .step-content ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
            color: #4a5568;
        }

        .step-content li {
            margin: 6px 0;
        }

        .exam-info {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8ecff 100%);
            border: 2px solid var(--primary);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .exam-info strong {
            color: var(--primary);
            display: block;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .exam-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
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
            font-size: 1.1rem;
        }

        .exam-id-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.3rem;
            display: inline-block;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .exam-id-badge:hover {
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

        .checklist {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            margin-top: 20px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .checklist-item:last-child {
            border-bottom: none;
        }

        .checklist-checkbox {
            width: 24px;
            height: 24px;
            cursor: pointer;
        }

        .checklist-text {
            flex: 1;
            color: #4a5568;
        }

        .highlight {
            background: #fef3c7;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            color: #92400e;
        }

        .no-exam {
            text-align: center;
            padding: 40px;
        }

        .no-exam i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 15px;
            display: block;
        }

        @media (max-width: 768px) {
            .step {
                flex-direction: column;
                align-items: flex-start;
            }

            .step-number {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container container-main">
        <?php if ($latest_exam): ?>
            <!-- Latest Exam Card -->
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title"><i class="fas fa-link"></i> Link Theory with Practical Exam</h1>
                </div>
                <div class="card-body">
                    <div class="exam-info">
                        <strong><i class="fas fa-check-circle" style="color: var(--success);"></i> Latest Theory Exam Found!</strong>
                        
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
                            <span class="exam-detail-label">Questions:</span>
                            <span class="exam-detail-value"><?= $latest_exam['question_count'] ?></span>
                        </div>

                        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid rgba(102, 126, 234, 0.2);">
                            <strong style="display: block; margin-bottom: 8px;">📌 Your Exam ID:</strong>
                            <div class="exam-id-badge" onclick="copyToClipboard('<?= $latest_exam['exam_id'] ?>')" title="Click to copy">
                                ID: <?= $latest_exam['exam_id'] ?>
                            </div>
                            <small style="display: block; margin-top: 8px; color: #718096;">Click the ID above to copy it</small>
                        </div>
                    </div>

                    <!-- Step by Step Guide -->
                    <div style="margin-top: 40px;">
                        <h2 style="color: var(--primary); font-weight: 700; margin-bottom: 25px;">
                            <i class="fas fa-tasks"></i> How to Link with Practical Exam
                        </h2>

                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Go to Create Practical Exam</h3>
                                <p>Click the button below or go to <span class="highlight">Practical Exams → Create Practical</span></p>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Fill in Practical Details</h3>
                                <ul>
                                    <li>Enter practical title (e.g., "Welding Practical")</li>
                                    <li>Select subject: <span class="highlight"><?= htmlspecialchars($latest_exam['subject_name'] ?? 'Same as theory') ?></span></li>
                                    <li>Write description</li>
                                    <li>Set marks (Theory + Practical = Total)</li>
                                </ul>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Select Theory Exam (IMPORTANT!)</h3>
                                <p>In the <span class="highlight">"Link Theory Exam"</span> dropdown, select:</p>
                                <p style="margin-top: 10px; font-weight: 700; color: var(--success);">
                                    <?= htmlspecialchars($latest_exam['exam_name']) ?>
                                </p>
                                <p style="margin-top: 8px; font-size: 0.9rem; color: #718096;">This will link it with ID: <span class="highlight"><?= $latest_exam['exam_id'] ?></span></p>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h3>Set Submission Deadline & Save</h3>
                                <ul>
                                    <li>Set submission deadline date and time</li>
                                    <li>Add evaluation instructions (optional)</li>
                                    <li>Click <span class="highlight">Create Practical Exam</span></li>
                                </ul>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-content">
                                <h3>Ready to Mark Practical Work</h3>
                                <p>Now you can:</p>
                                <ul>
                                    <li>Students submit practical work</li>
                                    <li>Go to <span class="highlight">Mark Practical</span></li>
                                    <li>Assign marks (no errors! ✅)</li>
                                    <li>Certificates generate automatically</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div class="checklist">
                            <h3 style="color: var(--primary); font-weight: 700; margin-bottom: 15px;">
                                <i class="fas fa-tasks"></i> Quick Checklist
                            </h3>
                            <div class="checklist-item">
                                <input type="checkbox" class="checklist-checkbox">
                                <span class="checklist-text">✅ Theory Exam Created: <strong><?= htmlspecialchars($latest_exam['exam_name']) ?></strong></span>
                            </div>
                            <div class="checklist-item">
                                <input type="checkbox" class="checklist-checkbox">
                                <span class="checklist-text">📌 Exam ID Noted: <strong><?= $latest_exam['exam_id'] ?></strong></span>
                            </div>
                            <div class="checklist-item">
                                <input type="checkbox" class="checklist-checkbox">
                                <span class="checklist-text">🔗 Ready to Create Practical Exam</span>
                            </div>
                            <div class="checklist-item">
                                <input type="checkbox" class="checklist-checkbox">
                                <span class="checklist-text">📋 Will Link Theory & Practical Together</span>
                            </div>
                            <div class="checklist-item">
                                <input type="checkbox" class="checklist-checkbox">
                                <span class="checklist-text">✨ Ready to Assign Marks (No Errors!)</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="btn-group">
                            <a href="<?= BASE_URL ?>/admin/practical_exams.php" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> Create Practical Exam
                            </a>
                            <a href="<?= BASE_URL ?>/view_exam_ids.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> View All Exam IDs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="no-exam">
                        <i class="fas fa-inbox"></i>
                        <h3>No Theory Exam Found</h3>
                        <p>Create a theory exam first, then come back here to link it with a practical exam.</p>
                        <a href="<?= BASE_URL ?>/admin/exams.php" class="btn btn-primary" style="margin-top: 15px;">
                            <i class="fas fa-plus"></i> Create Theory Exam
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Exam ID copied: ' + text);
            });
        }

        // Make checklists interactive
        document.querySelectorAll('.checklist-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.closest('.checklist-item').style.opacity = this.checked ? '0.6' : '1';
            });
        });
    </script>
</body>
</html>
