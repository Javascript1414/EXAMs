<?php
/**
 * CRITICAL ISSUES FIX GUIDE
 * Step-by-step solutions for all issues before going live
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get actual column names
    $tables = ['users', 'exams', 'exam_questions', 'results', 'certificates'];
    $actual_columns = [];
    
    foreach ($tables as $table) {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."'")->fetchAll(PDO::FETCH_COLUMN);
        $actual_columns[$table] = $cols;
    }
    
?>
<!DOCTYPE html>
<html>
<head>
    <title>Critical Issues Fix Guide</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1200px; margin: 0 auto; background: #252526; padding: 20px; border-radius: 8px; }
        h1, h2, h3 { color: #4ec9b0; }
        .error-box { background: #3d2828; border-left: 4px solid #f48771; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .fix-box { background: #1e3a1f; border-left: 4px solid #6a9955; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .code { background: #1e1e1e; border: 1px solid #555; padding: 10px; margin: 10px 0; border-radius: 4px; overflow-x: auto; }
        .warning { color: #ce9178; }
        .success { color: #6a9955; }
        .info { color: #9cdcfe; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #555; }
        th { background: #3e3e42; color: #4ec9b0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 CRITICAL ISSUES FIX GUIDE</h1>
        <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        
        <!-- ISSUE 1 -->
        <div class="error-box">
            <h2>❌ ISSUE 1: Table 'study_material_sections' MISSING</h2>
            <p><strong>Status:</strong> <span class="warning">CRITICAL</span></p>
            
            <h3>Diagnosis:</h3>
            <p>The database is missing the 'study_material_sections' table which is referenced by foreign key constraints.</p>
            
            <h3>Fix Solution:</h3>
            <p>This table might not be needed if it's not actively used. Check if any code references it:</p>
            <div class="code">
grep -r "study_material_sections" /xampp/htdocs/EXAMs/ --include="*.php"
            </div>
            
            <h3>Option A: Create the table (if needed)</h3>
            <div class="code">
CREATE TABLE study_material_sections (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    study_material_id BIGINT NOT NULL,
    section_number INT NOT NULL,
    section_title VARCHAR(255) NOT NULL,
    section_content LONGTEXT,
    duration_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (study_material_id) REFERENCES study_materials(id) ON DELETE CASCADE
);
            </div>
            
            <h3>Option B: Remove unused references (if not needed)</h3>
            <p>If the table is not used, you can safely ignore this if no PHP code references it.</p>
        </div>
        
        <!-- ISSUE 2 -->
        <div class="error-box">
            <h2>❌ ISSUE 2: PHP Extension 'gd' NOT LOADED</h2>
            <p><strong>Status:</strong> <span class="warning">CRITICAL</span></p>
            
            <h3>Purpose:</h3>
            <p>GD library is required for image processing, resizing, and thumbnail generation.</p>
            
            <h3>Fix Solution for XAMPP:</h3>
            <ol>
                <li>Open: <code>c:\xampp\php\php.ini</code></li>
                <li>Find the line: <code>;extension=gd</code></li>
                <li>Remove the semicolon: <code>extension=gd</code></li>
                <li>Save the file</li>
                <li>Restart Apache from XAMPP Control Panel</li>
                <li>Verify with: <code>php -m | findstr gd</code></li>
            </ol>
            
            <p>Or directly uncomment:</p>
            <div class="code">
; Search for this line in php.ini and uncomment it:
extension=gd

; Then restart Apache
            </div>
        </div>
        
        <!-- ISSUE 3 -->
        <div class="error-box">
            <h2>❌ ISSUE 3: PHP Extension 'zip' NOT LOADED</h2>
            <p><strong>Status:</strong> <span class="warning">CRITICAL</span></p>
            
            <h3>Purpose:</h3>
            <p>ZIP extension is required for certificate generation, file compression, and batch downloads.</p>
            
            <h3>Fix Solution for XAMPP:</h3>
            <ol>
                <li>Open: <code>c:\xampp\php\php.ini</code></li>
                <li>Find the line: <code>;extension=zip</code></li>
                <li>Remove the semicolon: <code>extension=zip</code></li>
                <li>Save the file</li>
                <li>Restart Apache from XAMPP Control Panel</li>
                <li>Verify with: <code>php -m | findstr zip</code></li>
            </ol>
        </div>
        
        <!-- DATABASE COLUMN ISSUES -->
        <div class="fix-box">
            <h2>📋 ACTUAL DATABASE COLUMNS</h2>
            <p>These are the actual columns in your database (warnings about column names might be false positives):</p>
            
            <?php foreach ($actual_columns as $table => $cols): ?>
            <h3>Table: <span class="info"><?= $table ?></span></h3>
            <div class="code">
                <?php foreach ($cols as $col): ?>
                    <?= htmlspecialchars($col) ?><br>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- UPLOAD DIRECTORIES -->
        <div class="fix-box">
            <h2>📁 CREATE MISSING UPLOAD DIRECTORIES</h2>
            
            <p>Run this PowerShell command to create all upload directories:</p>
            <div class="code">
$dirs = @(
    "c:\xampp\htdocs\EXAMs\uploads\profile_photos",
    "c:\xampp\htdocs\EXAMs\uploads\cover_photos",
    "c:\xampp\htdocs\EXAMs\uploads\study_materials",
    "c:\xampp\htdocs\EXAMs\uploads\exam_materials",
    "c:\xampp\htdocs\EXAMs\uploads\certificates"
)

foreach ($dir in $dirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force
        Write-Host "Created: $dir"
    }
}
            </div>
        </div>
        
        <!-- CONFIGURATION -->
        <div class="fix-box">
            <h2>⚙️ PRODUCTION CONFIGURATION CHANGES</h2>
            
            <h3>In config.php, change:</h3>
            <table>
                <tr>
                    <th>Variable</th>
                    <th>Development</th>
                    <th>Production</th>
                </tr>
                <tr>
                    <td>ENVIRONMENT</td>
                    <td><code>'development'</code></td>
                    <td><code>'production'</code></td>
                </tr>
                <tr>
                    <td>BASE_URL</td>
                    <td><code>http://localhost/EXAMs</code></td>
                    <td><code>https://yourdomain.com</code></td>
                </tr>
                <tr>
                    <td>DB_HOST</td>
                    <td><code>127.0.0.1:3307</code></td>
                    <td><code>localhost:3306 (or remote)</code></td>
                </tr>
                <tr>
                    <td>SMTP settings</td>
                    <td>Mailtrap (testing)</td>
                    <td>Gmail or production server</td>
                </tr>
            </table>
        </div>
        
        <!-- FINAL CHECKLIST -->
        <div class="fix-box">
            <h2>✅ PRE-DEPLOYMENT CHECKLIST</h2>
            
            <h3>Critical Fixes (Do Now):</h3>
            <ul>
                <li>☐ Enable GD extension in php.ini</li>
                <li>☐ Enable ZIP extension in php.ini</li>
                <li>☐ Restart Apache server</li>
                <li>☐ Create missing upload directories</li>
                <li>☐ Create or verify study_material_sections table</li>
            </ul>
            
            <h3>Configuration (Before Going Live):</h3>
            <ul>
                <li>☐ Update ENVIRONMENT to 'production' in config.php</li>
                <li>☐ Update BASE_URL to production domain</li>
                <li>☐ Update email configuration</li>
                <li>☐ Set up HTTPS/SSL certificate</li>
                <li>☐ Create database backups</li>
            </ul>
            
            <h3>Testing (Before Going Live):</h3>
            <ul>
                <li>☐ Test user registration flow</li>
                <li>☐ Test exam attempt flow</li>
                <li>☐ Test certificate generation</li>
                <li>☐ Test file uploads (profile photo, materials)</li>
                <li>☐ Test email notifications</li>
                <li>☐ Run database integrity checks</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #555; color: #888;">
            <p>For more details, check the full audit report at: /pre_live_audit_report.php</p>
        </div>
    </div>
</body>
</html>
<?php
} catch (Exception $e) {
    echo "<h3 style='color: #f48771;'>Error: " . $e->getMessage() . "</h3>";
}
?>
