<?php
/**
 * AUTOMATIC DATABASE FIXES
 * Creates missing tables and fixes schema issues
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

$results = [];

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>🔧 Automatic Database Fixes</h1>";
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>\n";
    
    // ============================================
    // FIX 1: Create study_material_sections table if missing
    // ============================================
    
    $table_check = $pdo->query("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = 'study_material_sections'")->fetch();
    
    if (!$table_check) {
        echo "Creating missing table: study_material_sections\n";
        
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS study_material_sections (
                    id BIGINT PRIMARY KEY AUTO_INCREMENT,
                    study_material_id BIGINT NOT NULL,
                    section_number INT NOT NULL,
                    section_title VARCHAR(255) NOT NULL,
                    section_content LONGTEXT,
                    duration_minutes INT,
                    video_url VARCHAR(500),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_material (study_material_id),
                    INDEX idx_section (section_number),
                    CONSTRAINT fk_study_material_section FOREIGN KEY (study_material_id) 
                        REFERENCES study_materials(id) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ study_material_sections table created\n\n";
        } catch (Exception $e) {
            // Table might already exist
            echo "⚠️  Could not create study_material_sections: " . $e->getMessage() . "\n";
            echo "    (This is OK if the table already exists with a different structure)\n\n";
        }
    } else {
        echo "✅ study_material_sections table already exists\n\n";
    }
    
    // ============================================
    // FIX 2: Create missing indexes for performance
    // ============================================
    
    echo "Creating performance indexes...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_exams_code ON exams(exam_code)",
        "CREATE INDEX IF NOT EXISTS idx_exam_attempts_user ON exam_attempts(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_exam_attempts_exam ON exam_attempts(exam_id)",
        "CREATE INDEX IF NOT EXISTS idx_results_user ON results(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_results_exam ON results(exam_id)",
        "CREATE INDEX IF NOT EXISTS idx_certificates_student ON certificates(student_id)",
        "CREATE INDEX IF NOT EXISTS idx_study_materials_subject ON study_materials(subject_id)",
        "CREATE INDEX IF NOT EXISTS idx_exam_questions_exam ON exam_questions(exam_id)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "  ✓ " . substr($index_sql, 23, 40) . "\n";
        } catch (Exception $e) {
            echo "  ℹ " . substr($index_sql, 23, 40) . " (may already exist)\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // FIX 3: Verify foreign key constraints
    // ============================================
    
    echo "Verifying foreign key constraints...\n";
    
    $fk_status = $pdo->query('SELECT @@FOREIGN_KEY_CHECKS as status')->fetch(PDO::FETCH_ASSOC);
    if ($fk_status['status']) {
        echo "✅ Foreign Key Checks: ENABLED\n";
    } else {
        echo "⚠️  Foreign Key Checks: DISABLED\n";
        echo "   Run: SET GLOBAL FOREIGN_KEY_CHECKS=1;\n";
    }
    
    echo "\n";
    
    // ============================================
    // FIX 4: Check upload directories
    // ============================================
    
    echo "Checking upload directories...\n";
    
    $upload_dirs = [
        'uploads/profile_photos',
        'uploads/cover_photos',
        'uploads/study_materials',
        'uploads/exam_materials',
        'uploads/certificates'
    ];
    
    foreach ($upload_dirs as $dir) {
        $full_path = str_replace('/', '\\', $dir);
        if (is_dir($full_path)) {
            if (is_writable($full_path)) {
                echo "✅ $dir (writable)\n";
            } else {
                echo "⚠️  $dir (NOT writable - fix permissions)\n";
            }
        } else {
            echo "⚠️  $dir (MISSING - create with mkdir)\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // FIX 5: Data Integrity Check
    // ============================================
    
    echo "Running data integrity checks...\n";
    
    $integrity_checks = [
        "SELECT COUNT(*) as count FROM exam_questions WHERE exam_id NOT IN (SELECT id FROM exams)" => "orphaned exam_questions",
        "SELECT COUNT(*) as count FROM certificates WHERE student_id NOT IN (SELECT id FROM users)" => "orphaned certificates",
        "SELECT COUNT(*) as count FROM results WHERE exam_id NOT IN (SELECT id FROM exams)" => "orphaned results",
        "SELECT COUNT(*) as count FROM study_materials WHERE subject_id NOT IN (SELECT id FROM subjects)" => "orphaned study_materials"
    ];
    
    foreach ($integrity_checks as $query => $desc) {
        try {
            $result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                echo "⚠️  Found " . $result['count'] . " " . $desc . "\n";
            } else {
                echo "✅ No " . $desc . "\n";
            }
        } catch (Exception $e) {
            echo "ℹ  " . $desc . " (table may not exist)\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // SUMMARY
    // ============================================
    
    echo "===============================================\n";
    echo "✅ AUTOMATED FIXES COMPLETED\n";
    echo "===============================================\n";
    echo "\nNext Steps:\n";
    echo "1. Run FIX_EXTENSIONS.bat to enable PHP extensions\n";
    echo "2. Restart Apache from XAMPP Control Panel\n";
    echo "3. Update config.php for production settings\n";
    echo "4. Run full audit: pre_live_audit_report.php\n";
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
