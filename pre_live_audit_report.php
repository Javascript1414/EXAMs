<?php
/**
 * COMPREHENSIVE PRE-LIVE AUDIT REPORT
 * Complete system check for all potential issues
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$warnings = [];
$success = [];

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ============================================
    // 1. CHECK DATABASE CONFIGURATION
    // ============================================
    
    // Check Foreign Keys
    $fk_status = $pdo->query('SELECT @@FOREIGN_KEY_CHECKS as status')->fetch(PDO::FETCH_ASSOC);
    if ($fk_status['status']) {
        $success[] = "✅ Foreign Key Checks: ENABLED";
    } else {
        $errors[] = "❌ Foreign Key Checks: DISABLED - Enable with: SET GLOBAL FOREIGN_KEY_CHECKS=1;";
    }
    
    // Check Tables exist
    $tables_sql = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db."'";
    $table_count = $pdo->query($tables_sql)->fetch(PDO::FETCH_ASSOC)['count'];
    $success[] = "✅ Database Tables: " . $table_count . " tables found";
    
    // Get all tables
    $all_tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db."' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
    
    // ============================================
    // 2. CHECK CRITICAL TABLES
    // ============================================
    
    $critical_tables = ['users', 'exams', 'exam_questions', 'exam_attempts', 'results', 'study_materials', 'study_material_sections', 'certificates'];
    
    foreach ($critical_tables as $table) {
        if (in_array($table, $all_tables)) {
            $col_count = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."'")->fetch(PDO::FETCH_ASSOC)['count'];
            $success[] = "✅ Table '$table': Exists with " . $col_count . " columns";
        } else {
            $errors[] = "❌ Table '$table': MISSING - This is critical!";
        }
    }
    
    // ============================================
    // 3. CHECK COLUMN DATA TYPES & CONSTRAINTS
    // ============================================
    
    $columns_to_check = [
        'users' => ['id', 'email', 'password_hash', 'phone', 'role_name'],
        'exams' => ['id', 'exam_code', 'name', 'description', 'duration_minutes'],
        'exam_questions' => ['id', 'exam_id', 'question_text', 'question_type', 'order_in_exam'],
        'results' => ['id', 'exam_id', 'user_id', 'score', 'percentage', 'status'],
        'certificates' => ['id', 'student_id', 'exam_id', 'certificate_number', 'issued_date']
    ];
    
    foreach ($columns_to_check as $table => $columns) {
        if (in_array($table, $all_tables)) {
            foreach ($columns as $col) {
                $col_info = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_KEY FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."' AND COLUMN_NAME = '".$col."'")->fetch(PDO::FETCH_ASSOC);
                
                if ($col_info) {
                    $success[] = "✅ Column '$table'.'$col': " . $col_info['DATA_TYPE'] . (($col_info['IS_NULLABLE'] === 'NO') ? ' (NOT NULL)' : '');
                } else {
                    $warnings[] = "⚠️  Column '$table'.'$col': MISSING";
                }
            }
        }
    }
    
    // ============================================
    // 4. CHECK FOREIGN KEY CONSTRAINTS
    // ============================================
    
    $fks_sql = "SELECT 
                    kcu.CONSTRAINT_NAME,
                    kcu.TABLE_NAME,
                    kcu.COLUMN_NAME,
                    kcu.REFERENCED_TABLE_NAME,
                    kcu.REFERENCED_COLUMN_NAME,
                    rc.UPDATE_RULE,
                    rc.DELETE_RULE
                 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                 LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                    ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                    AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                 WHERE kcu.TABLE_SCHEMA = '".$db."' 
                 AND kcu.REFERENCED_TABLE_NAME IS NOT NULL";
    
    $all_fks = $pdo->query($fks_sql)->fetchAll(PDO::FETCH_ASSOC);
    $success[] = "✅ Foreign Key Constraints: " . count($all_fks) . " constraints found and active";
    
    // Check for weak constraints
    foreach ($all_fks as $fk) {
        if ($fk['UPDATE_RULE'] === 'SET NULL' || $fk['DELETE_RULE'] === 'SET NULL') {
            if ($fk['COLUMN_NAME'] !== 'reviewed_by' && $fk['COLUMN_NAME'] !== 'generated_by') {
                $warnings[] = "⚠️  FK: " . $fk['TABLE_NAME'] . "." . $fk['COLUMN_NAME'] . " uses SET NULL (may cause data loss)";
            }
        }
    }
    
    // ============================================
    // 5. CHECK FILE UPLOADS & PERMISSIONS
    // ============================================
    
    $upload_dirs = [
        'uploads/profile_photos' => 'Profile photos',
        'uploads/cover_photos' => 'Cover photos',
        'uploads/study_materials' => 'Study materials',
        'uploads/exam_materials' => 'Exam materials',
        'uploads/certificates' => 'Certificates'
    ];
    
    foreach ($upload_dirs as $dir => $desc) {
        $full_path = 'c:\xampp\htdocs\EXAMs\\' . str_replace('/', '\\', $dir);
        if (is_dir($full_path)) {
            if (is_writable($full_path)) {
                $success[] = "✅ Upload Dir '$desc': Exists and writable";
            } else {
                $errors[] = "❌ Upload Dir '$desc': NOT WRITABLE - Fix permissions!";
            }
        } else {
            $warnings[] = "⚠️  Upload Dir '$desc': Doesn't exist";
        }
    }
    
    // ============================================
    // 6. CHECK CONFIG FILES
    // ============================================
    
    $config_file = 'c:\xampp\htdocs\EXAMs\config.php';
    if (file_exists($config_file)) {
        $success[] = "✅ Config File: Found";
        
        $config_content = file_get_contents($config_file);
        
        if (strpos($config_content, 'ENVIRONMENT') !== false) {
            if (strpos($config_content, "'development'") !== false) {
                $warnings[] = "⚠️  ENVIRONMENT MODE: Set to 'development' - Change to 'production' for live!";
            }
        }
        
        if (strpos($config_content, 'BASE_URL') !== false) {
            if (strpos($config_content, 'localhost') !== false) {
                $warnings[] = "⚠️  BASE_URL: Set to 'localhost' - Update to production domain!";
            }
        }
    } else {
        $errors[] = "❌ Config File: NOT FOUND";
    }
    
    // ============================================
    // 7. CHECK SESSION CONFIGURATION
    // ============================================
    
    $session_config = [
        'session.cookie_httponly' => '1',
        'session.use_only_cookies' => '1',
        'session.cookie_secure' => '1',
        'session.cookie_samesite' => 'Lax'
    ];
    
    foreach ($session_config as $setting => $expected) {
        $current = ini_get($setting);
        $success[] = "ℹ️  PHP Setting '$setting': " . ($current ?: 'not set');
    }
    
    // ============================================
    // 8. CHECK PHP EXTENSIONS
    // ============================================
    
    $required_extensions = ['pdo', 'pdo_mysql', 'curl', 'gd', 'json', 'mbstring', 'zip'];
    
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $success[] = "✅ PHP Extension '$ext': Loaded";
        } else {
            $errors[] = "❌ PHP Extension '$ext': NOT LOADED - Install required!";
        }
    }
    
    // ============================================
    // 9. CHECK DATA INTEGRITY
    // ============================================
    
    // Check for orphaned records (foreign key violations)
    $integrity_checks = [
        "SELECT COUNT(*) as count FROM exam_attempts WHERE user_id NOT IN (SELECT id FROM users)" => "Orphaned exam_attempts",
        "SELECT COUNT(*) as count FROM exam_questions WHERE exam_id NOT IN (SELECT id FROM exams)" => "Orphaned exam_questions",
        "SELECT COUNT(*) as count FROM certificates WHERE student_id NOT IN (SELECT id FROM users)" => "Orphaned certificates",
        "SELECT COUNT(*) as count FROM results WHERE exam_id NOT IN (SELECT id FROM exams)" => "Orphaned results"
    ];
    
    foreach ($integrity_checks as $query => $desc) {
        try {
            $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                $errors[] = "❌ Data Integrity: Found " . $result['count'] . " " . $desc;
            } else {
                $success[] = "✅ Data Integrity: No " . $desc;
            }
        } catch (Exception $e) {
            $warnings[] = "⚠️  Data Integrity Check failed: " . $e->getMessage();
        }
    }
    
    // ============================================
    // 10. CHECK INDEXES
    // ============================================
    
    $important_indexes = [
        'users' => ['email', 'phone'],
        'exams' => ['exam_code'],
        'study_materials' => ['subject_id'],
        'exam_attempts' => ['user_id', 'exam_id'],
        'results' => ['user_id', 'exam_id']
    ];
    
    foreach ($important_indexes as $table => $columns) {
        foreach ($columns as $col) {
            $index_check = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."' AND COLUMN_NAME = '".$col."' LIMIT 1")->fetch();
            if ($index_check) {
                $success[] = "✅ Index on '$table'.'$col': Found";
            } else {
                $warnings[] = "⚠️  Index on '$table'.'$col': Missing (may slow queries)";
            }
        }
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Database Connection Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Pre-Live Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-left: 4px solid #007bff; padding-left: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .error { color: #d32f2f; padding: 8px; margin: 5px 0; background: #ffebee; border-left: 4px solid #d32f2f; }
        .warning { color: #f57c00; padding: 8px; margin: 5px 0; background: #fff3e0; border-left: 4px solid #f57c00; }
        .success { color: #388e3c; padding: 8px; margin: 5px 0; background: #e8f5e9; border-left: 4px solid #388e3c; }
        .summary { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .status { font-weight: bold; font-size: 18px; }
        .error-count { color: #d32f2f; }
        .warning-count { color: #f57c00; }
        .success-count { color: #388e3c; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 COMPREHENSIVE PRE-LIVE AUDIT REPORT</h1>
        <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        
        <div class="summary">
            <h3>SUMMARY STATUS</h3>
            <p>
                <span class="error-count">❌ Errors: <?= count($errors) ?></span> | 
                <span class="warning-count">⚠️  Warnings: <?= count($warnings) ?></span> | 
                <span class="success-count">✅ Passed: <?= count($success) ?></span>
            </p>
            <?php if (count($errors) === 0): ?>
                <p style="color: #388e3c; font-size: 18px;">✅ <strong>NO CRITICAL ERRORS - SAFE TO DEPLOY</strong></p>
            <?php else: ?>
                <p style="color: #d32f2f; font-size: 18px;">❌ <strong>CRITICAL ERRORS FOUND - FIX BEFORE DEPLOYMENT</strong></p>
            <?php endif; ?>
        </div>
        
        <?php if (count($errors) > 0): ?>
        <div class="section">
            <h2>❌ CRITICAL ERRORS (Must Fix)</h2>
            <?php foreach ($errors as $error): ?>
                <div class="error"><?= $error ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($warnings) > 0): ?>
        <div class="section">
            <h2>⚠️  WARNINGS (Recommended to Fix)</h2>
            <?php foreach ($warnings as $warning): ?>
                <div class="warning"><?= $warning ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2>✅ CHECKS PASSED</h2>
            <?php foreach ($success as $item): ?>
                <div class="success"><?= $item ?></div>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p><strong>Next Steps:</strong></p>
            <ul>
                <?php if (count($errors) > 0): ?>
                    <li>Fix all critical errors immediately</li>
                <?php endif; ?>
                <?php if (count($warnings) > 0): ?>
                    <li>Address warnings before going live</li>
                    <li>Update config.php for production environment</li>
                <?php endif; ?>
                <li>Run database backups</li>
                <li>Test all critical user flows</li>
                <li>Enable HTTPS/SSL certificate</li>
                <li>Configure firewall rules</li>
                <li>Set up monitoring and logging</li>
            </ul>
        </div>
    </div>
</body>
</html>
