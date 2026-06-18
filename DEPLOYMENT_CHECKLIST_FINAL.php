<?php
/**
 * COMPLETE DEPLOYMENT CHECKLIST & FINAL REPORT
 * Master summary of all issues and their fixes
 */

$host = '127.0.0.1:3307';
$db = 'exams_lms';
$user = 'root';
$pass = '';

$critical_fixes = [];
$recommended_fixes = [];
$completed = [];

try {
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check PHP Extensions
    if (!extension_loaded('gd')) {
        $critical_fixes[] = [
            'issue' => 'GD Extension Not Loaded',
            'impact' => 'Image processing, resizing, certificates will fail',
            'fix' => 'Enable in php.ini: uncomment extension=gd and restart Apache',
            'file' => 'c:\xampp\php\php.ini',
            'command' => 'Run FIX_EXTENSIONS.bat'
        ];
    } else {
        $completed[] = 'GD extension is loaded ✅';
    }
    
    if (!extension_loaded('zip')) {
        $critical_fixes[] = [
            'issue' => 'ZIP Extension Not Loaded',
            'impact' => 'Certificate generation and file compression will fail',
            'fix' => 'Enable in php.ini: uncomment extension=zip and restart Apache',
            'file' => 'c:\xampp\php\php.ini',
            'command' => 'Run FIX_EXTENSIONS.bat'
        ];
    } else {
        $completed[] = 'ZIP extension is loaded ✅';
    }
    
    // Check Upload Directories
    $upload_dirs = [
        'uploads/profile_photos',
        'uploads/cover_photos',
        'uploads/study_materials',
        'uploads/exam_materials',
        'uploads/certificates'
    ];
    
    $missing_dirs = [];
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            $missing_dirs[] = $dir;
        }
    }
    
    if (!empty($missing_dirs)) {
        $recommended_fixes[] = [
            'issue' => 'Missing Upload Directories',
            'impact' => 'File uploads will fail',
            'fix' => 'Create directories or run FIX_EXTENSIONS.bat',
            'directories' => $missing_dirs
        ];
    } else {
        $completed[] = 'All upload directories exist ✅';
    }
    
    // Check Config
    $config_content = @file_get_contents('config.php');
    if (strpos($config_content, "'development'") !== false) {
        $recommended_fixes[] = [
            'issue' => 'ENVIRONMENT set to development',
            'impact' => 'Errors will be visible to users in production',
            'fix' => 'Change ENVIRONMENT to "production" in config.php',
            'line' => 'define(\'ENVIRONMENT\', \'production\');'
        ];
    } else if (strpos($config_content, "'production'") !== false) {
        $completed[] = 'ENVIRONMENT set to production ✅';
    }
    
    if (strpos($config_content, 'localhost') !== false) {
        $recommended_fixes[] = [
            'issue' => 'BASE_URL set to localhost',
            'impact' => 'Links will not work in production',
            'fix' => 'Update BASE_URL to production domain in config.php',
            'line' => "define('BASE_URL', 'https://yourdomain.com');"
        ];
    } else {
        $completed[] = 'BASE_URL is configured ✅';
    }
    
    // Check Foreign Keys
    $fk_status = $pdo->query('SELECT @@FOREIGN_KEY_CHECKS as status')->fetch(PDO::FETCH_ASSOC);
    if ($fk_status['status']) {
        $completed[] = 'Foreign Key Checks: ENABLED ✅';
    } else {
        $critical_fixes[] = [
            'issue' => 'Foreign Key Checks Disabled',
            'impact' => 'Data integrity could be compromised',
            'fix' => 'Execute: SET GLOBAL FOREIGN_KEY_CHECKS=1;',
            'sql' => 'SET GLOBAL FOREIGN_KEY_CHECKS=1;'
        ];
    }
    
    // Check Tables
    $tables = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$db."'")->fetch()['count'];
    $completed[] = "Database tables: $tables tables found ✅";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Deployment Checklist</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border-radius: 8px;
            border-left: 5px solid #ccc;
        }
        .critical {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .recommended {
            background: #fff3e0;
            border-left-color: #ff9800;
        }
        .completed {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
        }
        .critical h2 { color: #f44336; }
        .recommended h2 { color: #ff9800; }
        .completed h2 { color: #4caf50; }
        .item {
            margin: 15px 0;
            padding: 15px;
            background: rgba(255,255,255,0.5);
            border-radius: 4px;
        }
        .item-title { font-weight: bold; font-size: 1.1em; }
        .item-impact { color: #d32f2f; margin: 8px 0; }
        .item-fix { color: #1976d2; margin: 8px 0; font-family: monospace; }
        .item-command { background: #f5f5f5; padding: 10px; border-radius: 4px; margin: 8px 0; font-family: monospace; }
        .checklist {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .checklist-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #4caf50;
        }
        .footer {
            background: #f5f5f5;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 10px 5px;
            font-size: 0.9em;
        }
        .status-critical { background: #ffcdd2; color: #c62828; }
        .status-warning { background: #ffe0b2; color: #e65100; }
        .status-ok { background: #c8e6c9; color: #2e7d32; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Complete Deployment Checklist</h1>
            <p>Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
        
        <div class="content">
            <!-- STATUS SUMMARY -->
            <div style="text-align: center; margin-bottom: 40px;">
                <h2>Summary Status</h2>
                <div>
                    <span class="status-badge status-critical">🔴 <?= count($critical_fixes) ?> Critical Issues</span>
                    <span class="status-badge status-warning">🟡 <?= count($recommended_fixes) ?> Recommended Fixes</span>
                    <span class="status-badge status-ok">🟢 <?= count($completed) ?> Checks Passed</span>
                </div>
                
                <?php if (count($critical_fixes) > 0): ?>
                    <h3 style="color: #f44336; margin-top: 20px;">⚠️ CANNOT GO LIVE - CRITICAL ISSUES MUST BE FIXED FIRST</h3>
                <?php else: ?>
                    <h3 style="color: #4caf50; margin-top: 20px;">✅ NO CRITICAL ISSUES - READY TO PROCEED</h3>
                <?php endif; ?>
            </div>
            
            <!-- CRITICAL ISSUES -->
            <?php if (count($critical_fixes) > 0): ?>
            <div class="section critical">
                <h2>🔴 CRITICAL ISSUES (Must Fix Before Deployment)</h2>
                
                <?php foreach ($critical_fixes as $fix): ?>
                <div class="item">
                    <div class="item-title"><?= $fix['issue'] ?></div>
                    <div class="item-impact"><strong>Impact:</strong> <?= $fix['impact'] ?></div>
                    <div class="item-fix"><strong>Fix:</strong> <?= $fix['fix'] ?></div>
                    
                    <?php if (isset($fix['command'])): ?>
                        <div class="item-command">💻 <?= $fix['command'] ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($fix['sql'])): ?>
                        <div class="item-command">📊 <?= $fix['sql'] ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($fix['file'])): ?>
                        <div style="color: #666; margin-top: 8px;"><strong>File:</strong> <?= $fix['file'] ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- RECOMMENDED FIXES -->
            <?php if (count($recommended_fixes) > 0): ?>
            <div class="section recommended">
                <h2>🟡 RECOMMENDED FIXES (Strongly Suggested Before Going Live)</h2>
                
                <?php foreach ($recommended_fixes as $fix): ?>
                <div class="item">
                    <div class="item-title"><?= $fix['issue'] ?></div>
                    <div class="item-impact"><strong>Impact:</strong> <?= $fix['impact'] ?></div>
                    <div class="item-fix"><strong>Fix:</strong> <?= $fix['fix'] ?></div>
                    
                    <?php if (isset($fix['line'])): ?>
                        <div class="item-command"><?= htmlspecialchars($fix['line']) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($fix['directories'])): ?>
                        <div style="margin-top: 8px;">
                            <strong>Directories to create:</strong>
                            <ul>
                                <?php foreach ($fix['directories'] as $dir): ?>
                                    <li><code><?= $dir ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- COMPLETED CHECKS -->
            <div class="section completed">
                <h2>✅ CHECKS PASSED</h2>
                <div class="checklist">
                    <?php foreach ($completed as $item): ?>
                    <div class="checklist-item"><?= $item ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- DEPLOYMENT STEPS -->
            <div class="section" style="border-left-color: #2196f3; background: #e3f2fd;">
                <h2 style="color: #1565c0;">📋 Deployment Steps</h2>
                
                <h3>1️⃣ Immediate Actions (Do Now):</h3>
                <ul>
                    <li>Fix all critical issues listed above</li>
                    <li>Run: <code>php run_database_fixes.php</code></li>
                    <li>Apply recommended fixes</li>
                    <li>Restart Apache after PHP extension changes</li>
                </ul>
                
                <h3>2️⃣ Configuration Changes:</h3>
                <ul>
                    <li>Update database credentials in config.php</li>
                    <li>Change BASE_URL to production domain</li>
                    <li>Set ENVIRONMENT to 'production'</li>
                    <li>Configure email settings (SMTP)</li>
                    <li>Enable HTTPS/SSL certificate</li>
                </ul>
                
                <h3>3️⃣ Testing:</h3>
                <ul>
                    <li>Test user registration</li>
                    <li>Test login flow</li>
                    <li>Test exam attempt</li>
                    <li>Test certificate generation</li>
                    <li>Test file uploads</li>
                    <li>Run full audit report</li>
                </ul>
                
                <h3>4️⃣ Deployment:</h3>
                <ul>
                    <li>Backup production database</li>
                    <li>Deploy code to production</li>
                    <li>Run migrations if any</li>
                    <li>Verify all systems are working</li>
                    <li>Monitor for errors</li>
                </ul>
            </div>
            
            <!-- QUICK REFERENCE -->
            <div class="section" style="border-left-color: #9c27b0; background: #f3e5f5;">
                <h2 style="color: #6a1b9a;">⚡ Quick Reference URLs</h2>
                <table>
                    <tr>
                        <th>Report</th>
                        <th>URL</th>
                    </tr>
                    <tr>
                        <td>Full Audit Report</td>
                        <td><code>/pre_live_audit_report.php</code></td>
                    </tr>
                    <tr>
                        <td>Critical Fixes Guide</td>
                        <td><code>/critical_fixes_guide.php</code></td>
                    </tr>
                    <tr>
                        <td>Schema Details</td>
                        <td><code>/check_schema_detailed.php</code></td>
                    </tr>
                    <tr>
                        <td>Foreign Keys Check</td>
                        <td><code>/check_foreign_keys.php</code></td>
                    </tr>
                    <tr>
                        <td>Database Fixes</td>
                        <td><code>/run_database_fixes.php</code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="footer">
            <h3>Next Steps</h3>
            <p>
                <?php if (count($critical_fixes) > 0): ?>
                    Fix the <?= count($critical_fixes) ?> critical issue(s) above, then refresh this page to verify.<br>
                <?php else: ?>
                    ✅ All critical issues resolved. Proceed with deployment checklist.<br>
                <?php endif; ?>
            </p>
            <p style="color: #666; font-size: 0.9em;">
                Report Generated: <?= date('Y-m-d H:i:s') ?><br>
                Database: <?= $db ?> | Host: <?= $host ?>
            </p>
        </div>
    </div>
</body>
</html>
