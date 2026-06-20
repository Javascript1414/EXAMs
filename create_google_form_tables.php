<?php
/**
 * Create Google Form related tables
 * This script creates all necessary tables for Google Form exam management
 */

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] !== 'admin' && $_SESSION['role_name'] !== 'superadmin')) {
    http_response_code(403);
    die('Access Denied');
}

$tables_created = [];
$errors = [];

try {
    // 1. Create google_form_exams table
    $sql1 = "CREATE TABLE IF NOT EXISTS google_form_exams (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        exam_title VARCHAR(255) NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        trade_id INT UNSIGNED NOT NULL,
        google_form_link TEXT NOT NULL,
        total_marks INT NOT NULL DEFAULT 100,
        pass_marks INT NOT NULL DEFAULT 40,
        exam_date DATE NOT NULL,
        exam_time TIME NULL,
        instructions LONGTEXT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
        show_results BOOLEAN DEFAULT TRUE,
        show_answers BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subject (subject_id),
        INDEX idx_trade (trade_id),
        INDEX idx_created_by (created_by),
        INDEX idx_status (status),
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (trade_id) REFERENCES trades(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql1);
    $tables_created[] = "✅ google_form_exams";
} catch (PDOException $e) {
    $errors[] = "❌ google_form_exams: " . $e->getMessage();
}

try {
    // 2. Create google_form_exam_attempts table
    $sql2 = "CREATE TABLE IF NOT EXISTS google_form_exam_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id BIGINT UNSIGNED NOT NULL,
        exam_id INT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        exam_title VARCHAR(255) NOT NULL,
        exam_source VARCHAR(50) DEFAULT 'Google Form',
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        marks_obtained INT NULL,
        marks_entered_by BIGINT UNSIGNED NULL,
        marks_entered_at TIMESTAMP NULL,
        result_status ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_attempt (student_id, exam_id),
        INDEX idx_student (student_id),
        INDEX idx_exam (exam_id),
        INDEX idx_subject (subject_id),
        INDEX idx_status (result_status),
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (exam_id) REFERENCES google_form_exams(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (marks_entered_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    $tables_created[] = "✅ google_form_exam_attempts";
} catch (PDOException $e) {
    $errors[] = "❌ google_form_exam_attempts: " . $e->getMessage();
}

try {
    // 3. Create google_form_exam_permissions table
    $sql3 = "CREATE TABLE IF NOT EXISTS google_form_exam_permissions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        teacher_id BIGINT UNSIGNED NOT NULL,
        subject_id INT UNSIGNED NOT NULL,
        can_create_exams BOOLEAN DEFAULT TRUE,
        can_enter_marks BOOLEAN DEFAULT TRUE,
        granted_by BIGINT UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_permission (teacher_id, subject_id),
        INDEX idx_teacher (teacher_id),
        INDEX idx_subject (subject_id),
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
        FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    $tables_created[] = "✅ google_form_exam_permissions";
} catch (PDOException $e) {
    $errors[] = "❌ google_form_exam_permissions: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Form Tables Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🌐 Google Form Tables Setup</h4>
            </div>
            <div class="card-body">
                <?php if (!empty($tables_created)): ?>
                    <div class="alert alert-success">
                        <h5>Tables Created Successfully:</h5>
                        <ul class="mb-0">
                            <?php foreach ($tables_created as $msg): ?>
                                <li><?= $msg ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-warning">
                        <h5>Status:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $msg): ?>
                                <li><?= $msg ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h5>📋 Tables Created:</h5>
                    <ul class="mb-0">
                        <li><strong>google_form_exams</strong> - Store Google Form exam details</li>
                        <li><strong>google_form_exam_attempts</strong> - Track student attempts and marks</li>
                        <li><strong>google_form_exam_permissions</strong> - Manage teacher permissions</li>
                    </ul>
                </div>
                
                <a href="admin/google_form_exams.php" class="btn btn-primary">Go to Google Form Exams</a>
                <a href="admin/index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>
