<?php
/**
 * Google Form Exam System Setup Script
 * Runs database migrations and updates sidebar navigation
 */

require_once 'config.php';
require_once 'includes/db.php';

$message = '';
$errors = [];
$success_count = 0;

// Step 1: Run Database Migration
try {
    $migration_file = __DIR__ . '/migrations/phase_22_google_form_exams.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: $migration_file");
    }
    
    $sql = file_get_contents($migration_file);
    
    // Execute migration
    $pdo->exec($sql);
    
    $success_count++;
    $message .= "✅ Database tables created successfully\n";
} catch (Exception $e) {
    $errors[] = "Database Migration Error: " . $e->getMessage();
}

// Step 2: Check and Update Sidebar
try {
    $sidebar_file = __DIR__ . '/includes/sidebar.php';
    
    if (!file_exists($sidebar_file)) {
        throw new Exception("Sidebar file not found: $sidebar_file");
    }
    
    $sidebar_content = file_get_contents($sidebar_file);
    
    // Check if already added
    if (strpos($sidebar_content, 'google_form_exams.php') === false) {
        // Note: This is informational - actual updates need manual review
        $message .= "\n⚠️ Sidebar needs manual updates:\n";
        $message .= "  - Admin: Add Google Form Exams menu\n";
        $message .= "  - Teacher: Add Create/Enter Marks menus\n";
        $message .= "  - Student: Add Google Form Exams menu\n";
        $message .= "  See GOOGLE_FORM_EXAM_IMPLEMENTATION.md for details\n";
    } else {
        $success_count++;
        $message .= "✅ Sidebar already updated\n";
    }
} catch (Exception $e) {
    $errors[] = "Sidebar Check Error: " . $e->getMessage();
}

// Step 3: Verify Tables
try {
    $tables = [
        'google_form_exams',
        'google_form_exam_attempts',
        'google_form_exam_permissions',
        'google_form_exam_stats'
    ];
    
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if (!$result) {
            throw new Exception("Table not created: $table");
        }
    }
    
    $success_count++;
    $message .= "✅ All required database tables verified\n";
} catch (Exception $e) {
    $errors[] = "Table Verification Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Form Exam Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .setup-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }

        .setup-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }

        .setup-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .setup-header p {
            color: #718096;
            margin: 10px 0 0 0;
        }

        .status-section {
            margin: 20px 0;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            margin: 10px 0;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #48bb78;
        }

        .status-item.error {
            border-left-color: #f56565;
            background: rgba(245, 101, 101, 0.1);
        }

        .status-item.warning {
            border-left-color: #ed8936;
            background: rgba(237, 137, 54, 0.1);
        }

        .status-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .status-text {
            flex: 1;
        }

        .status-text strong {
            display: block;
            color: #2d3748;
        }

        .status-text small {
            color: #718096;
            display: block;
            margin-top: 5px;
        }

        .message {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .error-message {
            background: rgba(245, 101, 101, 0.1);
            border-left-color: #f56565;
            color: #c53030;
        }

        .next-steps {
            background: #f0f4ff;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .next-steps h3 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .next-steps ol {
            margin: 0;
            padding-left: 20px;
        }

        .next-steps li {
            margin: 10px 0;
            color: #4a5568;
        }

        .btn-complete {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .table-info {
            font-size: 0.9rem;
            color: #718096;
            margin-top: 15px;
        }

        code {
            background: #edf2f7;
            padding: 2px 6px;
            border-radius: 4px;
            color: #c53030;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="bi bi-google"></i> Google Form Exam Setup</h1>
            <p>Automated setup for the Google Form Exam Management feature</p>
        </div>

        <!-- Status Messages -->
        <div class="status-section">
            <?php if ($success_count > 0): ?>
                <div class="status-item">
                    <div class="status-icon">✅</div>
                    <div class="status-text">
                        <strong><?= $success_count ?> step(s) completed successfully</strong>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($errors) > 0): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="status-item error">
                        <div class="status-icon">❌</div>
                        <div class="status-text">
                            <strong>Error</strong>
                            <small><?= htmlspecialchars($error) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Detailed Messages -->
        <?php if ($message): ?>
            <div class="message">
<?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Important Steps -->
        <div class="next-steps">
            <h3><i class="bi bi-list-check"></i> Next Steps</h3>
            <ol>
                <li>
                    <strong>Update Sidebar Navigation</strong><br>
                    Open <code>/includes/sidebar.php</code> and add menu items as described in 
                    <code>GOOGLE_FORM_EXAM_IMPLEMENTATION.md</code>
                </li>
                <li>
                    <strong>Test Admin Panel</strong><br>
                    Login as admin and visit <code>/admin/google_form_exams.php</code>
                </li>
                <li>
                    <strong>Grant Permissions</strong><br>
                    Assign teachers permission to create exams for their subjects
                </li>
                <li>
                    <strong>Test Teacher Interface</strong><br>
                    Login as teacher and create a test exam
                </li>
                <li>
                    <strong>Test Student Interface</strong><br>
                    Login as student and view exams
                </li>
                <li>
                    <strong>Enter Sample Marks</strong><br>
                    Have teacher enter marks for test exam
                </li>
                <li>
                    <strong>Test Certificate Generation</strong><br>
                    Generate certificates for passing students
                </li>
            </ol>
        </div>

        <!-- Database Tables Info -->
        <div class="table-info">
            <strong>Created Database Tables:</strong><br>
            • <code>google_form_exams</code> - Exam information<br>
            • <code>google_form_exam_attempts</code> - Student attempts and marks<br>
            • <code>google_form_exam_permissions</code> - Teacher permissions<br>
            • <code>google_form_exam_stats</code> - Statistics/reports<br>
            • Updated: <code>certificates</code> table with <code>exam_source</code> column
        </div>

        <button class="btn-complete" onclick="location.href = '<?= BASE_URL ?>/index.php'">
            <i class="bi bi-check-circle"></i> Setup Complete - Go to Dashboard
        </button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
