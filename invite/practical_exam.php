<?php
/**
 * Student: Access Practical Exam via Invitation Link
 * Students can join practical exams using invitation codes
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/practical_exam_functions.php';
require_once '../includes/exam_invitation_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if code is provided
$code = sanitizeInput($_GET['code'] ?? '');

if (!$code) {
    header('Location: ' . BASE_URL . '/student/practical_exams.php');
    exit;
}

// Get exam details from invitation code
$exam = getPracticalExamByInvitation($code);

if (!$exam) {
    $message = 'Invalid or expired invitation link';
    $message_type = 'danger';
} else {
    // Check if student belongs to the correct trade
    $student_trade = $_SESSION['trade_id'] ?? null;
    
    if ($student_trade != $exam['trade_id']) {
        // Update student's trade to match exam's trade
        try {
            $stmt = $pdo->prepare("UPDATE users SET trade_id = ? WHERE id = ?");
            $stmt->execute([$exam['trade_id'], $_SESSION['user_id']]);
            $_SESSION['trade_id'] = $exam['trade_id'];
        } catch (Exception $e) {
            $message = 'Could not assign you to the exam trade. Please contact support.';
            $message_type = 'danger';
        }
    }
    
    if (!isset($message)) {
        $message = 'Successfully joined the practical exam! Redirecting...';
        $message_type = 'success';
        
        // Redirect to practical exams page
        header('Refresh: 2; url=' . BASE_URL . '/student/practical_exams.php');
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical Exam Invitation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .card-body {
            padding: 40px;
            text-align: center;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
        }
        
        .icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        
        .icon.success {
            color: #48bb78;
        }
        
        .icon.danger {
            color: #f56565;
        }
        
        h1 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        p {
            color: #666;
            margin-bottom: 10px;
        }
        
        .exam-details {
            background: #f7fafc;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        
        .exam-details strong {
            color: #667eea;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            flex: 1;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #2d3748;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
            color: #2d3748;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body">
                <?php if ($message_type === 'success'): ?>
                    <div class="icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1>Invitation Accepted!</h1>
                    <p><?= htmlspecialchars($message) ?></p>
                    
                    <?php if ($exam): ?>
                    <div class="exam-details">
                        <div><strong>📚 Exam:</strong> <?= htmlspecialchars($exam['title']) ?></div>
                        <div style="margin-top: 10px;"><strong>📝 Deadline:</strong> <?= date('M d, Y H:i', strtotime($exam['submission_deadline'])) ?></div>
                        <div style="margin-top: 10px;"><strong>⭐ Marks:</strong> Theory: <?= $exam['theory_marks'] ?> | Practical: <?= $exam['practical_marks'] ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/student/practical_exams.php" class="btn btn-primary">
                            <i class="fas fa-arrow-right"></i> Go to Exams
                        </a>
                    </div>
                
                <?php else: ?>
                    <div class="icon danger">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <h1>Invalid Link</h1>
                    <p><?= htmlspecialchars($message) ?></p>
                    <p style="font-size: 0.9rem; color: #999; margin-top: 20px;">The invitation link may have expired or been revoked.</p>
                    
                    <div class="btn-group">
                        <a href="<?= BASE_URL ?>/student/practical_exams.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Exams
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
