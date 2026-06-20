<?php
/**
 * INFINITYFREE DEPLOYMENT CHECKLIST & VERIFICATION
 * 
 * Run this script to verify your deployment is production-ready
 * URL: /deployment-verify.php
 * 
 * Last Updated: 2026-06-20
 */

// Load configuration
require_once __DIR__ . '/config_infinityfree.php';

// Styling
$style = <<<CSS
<style>
    * { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0; padding: 20px; }
    .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    h1 { color: #333; margin-top: 0; text-align: center; }
    .status { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
    .check { padding: 20px; border-radius: 8px; border-left: 4px solid; }
    .check.pass { background: #d5f4e6; border-color: #27ae60; color: #1e7e34; }
    .check.fail { background: #fadbd8; border-color: #e74c3c; color: #a93226; }
    .check.warn { background: #fef5e7; border-color: #f39c12; color: #7d6608; }
    .check-title { font-weight: bold; font-size: 1.1em; margin-bottom: 8px; }
    .check-detail { font-size: 0.9em; line-height: 1.5; }
    .section { margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; }
    .section h2 { color: #667eea; margin-top: 0; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; font-family: 'Courier New'; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #f5f5f5; font-weight: bold; }
    .summary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin-top: 30px; text-align: center; }
    .score { font-size: 2em; font-weight: bold; }
</style>
CSS;

// Verification checks
$checks = [];
$total_checks = 0;
$passed_checks = 0;
$failed_checks = 0;
$warnings = 0;

// ==================== ENVIRONMENT CHECKS ====================
$section = 'Environment';

// 1. Check if running on InfinityFree or production
$is_production = ENVIRONMENT === 'production' || isInfinityFreeEnvironment();
$checks[] = [
    'section' => $section,
    'name' => 'Production Environment',
    'status' => $is_production ? 'pass' : 'warn',
    'message' => $is_production ? 
        'Running in production mode (' . ENVIRONMENT . ')' : 
        'Running in development mode - switch to production before deployment',
    'detail' => 'Current Environment: ' . ENVIRONMENT . ' | Domain: ' . $_SERVER['HTTP_HOST']
];

// 2. Check PHP version
$php_version = phpversion();
$php_ok = version_compare($php_version, '7.4', '>=');
$checks[] = [
    'section' => $section,
    'name' => 'PHP Version',
    'status' => $php_ok ? 'pass' : 'fail',
    'message' => $php_ok ? "PHP {$php_version} is compatible" : "PHP {$php_version} is too old (require >= 7.4)",
    'detail' => 'Current: ' . $php_version
];

// ==================== DATABASE CHECKS ====================
$section = 'Database';

// 3. Check database connection
$db_ok = false;
$db_error = '';
try {
    $pdo->query('SELECT 1');
    $db_ok = true;
    $db_message = 'Database connected successfully';
} catch (Exception $e) {
    $db_error = $e->getMessage();
    $db_message = 'Database connection failed: ' . substr($db_error, 0, 100);
}

$checks[] = [
    'section' => $section,
    'name' => 'Database Connection',
    'status' => $db_ok ? 'pass' : 'fail',
    'message' => $db_message,
    'detail' => 'Host: ' . DB_HOST . ' | Database: ' . DB_NAME
];

// 4. Check database tables
if ($db_ok) {
    try {
        $tables_stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $table_count = $tables_stmt->fetch()['count'];
        $tables_ok = $table_count > 5;
        
        $checks[] = [
            'section' => $section,
            'name' => 'Database Tables',
            'status' => $tables_ok ? 'pass' : 'warn',
            'message' => "Found {$table_count} tables",
            'detail' => $tables_ok ? 'Database properly initialized' : 'Database may not be fully initialized'
        ];
    } catch (Exception $e) {
        $checks[] = [
            'section' => $section,
            'name' => 'Database Tables',
            'status' => 'warn',
            'message' => 'Could not verify tables',
            'detail' => $e->getMessage()
        ];
    }
}

// ==================== FILE & DIRECTORY CHECKS ====================
$section = 'Files & Directories';

// 5. Check critical files exist
$critical_files = [
    'config_infinityfree.php' => 'Configuration file',
    'index_infinityfree.php' => 'Production index file',
    'login.php' => 'Login page',
    'register.php' => 'Registration page',
];

foreach ($critical_files as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $checks[] = [
        'section' => $section,
        'name' => 'File: ' . $file,
        'status' => $exists ? 'pass' : 'fail',
        'message' => $exists ? "{$desc} exists" : "{$desc} missing",
        'detail' => 'Path: /' . $file
    ];
}

// 6. Check directory permissions
$directories = [
    'uploads' => 'Upload directory',
    'includes' => 'Includes directory',
    'assets' => 'Assets directory',
];

foreach ($directories as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    $checks[] = [
        'section' => $section,
        'name' => 'Directory: ' . $dir,
        'status' => $exists ? ($writable ? 'pass' : 'warn') : 'fail',
        'message' => $exists ? ($writable ? "{$desc} exists and is writable" : "{$desc} exists but not writable") : "{$desc} missing",
        'detail' => 'Permissions: ' . substr(sprintf('%o', fileperms($path)), -4)
    ];
}

// ==================== CONFIGURATION CHECKS ====================
$section = 'Configuration';

// 7. Check BASE_URL is configured
$base_url_ok = defined('BASE_URL') && !empty(BASE_URL) && BASE_URL !== 'http://localhost/EXAMs';
$checks[] = [
    'section' => $section,
    'name' => 'BASE_URL Configuration',
    'status' => $base_url_ok ? 'pass' : 'warn',
    'message' => $base_url_ok ? 'BASE_URL properly configured' : 'BASE_URL may need updating',
    'detail' => 'Current: ' . BASE_URL
];

// 8. Check SMTP Configuration
$smtp_ok = defined('SMTP_HOST') && !empty(SMTP_HOST);
$checks[] = [
    'section' => $section,
    'name' => 'Email Configuration',
    'status' => $smtp_ok ? 'pass' : 'warn',
    'message' => $smtp_ok ? 'SMTP configured' : 'SMTP not configured',
    'detail' => 'SMTP Host: ' . (SMTP_HOST ?: 'Not set')
];

// 9. Check security settings
$secure_ok = ENVIRONMENT === 'production' && COOKIE_SECURE;
$checks[] = [
    'section' => $section,
    'name' => 'Security Settings',
    'status' => ENVIRONMENT === 'production' ? 'pass' : 'warn',
    'message' => ENVIRONMENT === 'production' ? 'Production security enabled' : 'Still in development mode',
    'detail' => 'Secure Cookies: ' . (COOKIE_SECURE ? 'Yes' : 'No') . ' | HttpOnly: ' . (COOKIE_HTTPONLY ? 'Yes' : 'No')
];

// ==================== ASSET CHECKS ====================
$section = 'Assets';

// 10. Check asset directories
$asset_dirs = [
    'assets/css' => 'CSS files',
    'assets/js' => 'JavaScript files',
    'assets/images' => 'Image files',
];

foreach ($asset_dirs as $dir => $desc) {
    $path = __DIR__ . '/' . $dir;
    $exists = is_dir($path);
    $file_count = $exists ? count(glob($path . '/*')) : 0;
    
    $checks[] = [
        'section' => $section,
        'name' => 'Directory: ' . $dir,
        'status' => ($exists && $file_count > 0) ? 'pass' : 'warn',
        'message' => $exists ? "{$desc} found ({$file_count} files)" : "{$desc} directory missing",
        'detail' => 'Files: ' . $file_count
    ];
}

// ==================== PROCESS RESULTS ====================
foreach ($checks as $check) {
    $total_checks++;
    if ($check['status'] === 'pass') $passed_checks++;
    elseif ($check['status'] === 'fail') $failed_checks++;
    elseif ($check['status'] === 'warn') $warnings++;
}

// Calculate score
$score = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;
$ready = $failed_checks === 0 && $score >= 80;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Deployment Verification - <?= APP_NAME ?></title>
    <?= $style ?>
</head>
<body>
    <div class="container">
        <h1>🚀 <?= APP_NAME ?> - Deployment Verification</h1>
        <p style="text-align: center; color: #666;">Checking your production deployment configuration...</p>
        
        <!-- STATUS OVERVIEW -->
        <div class="status">
            <div class="check pass">
                <div class="check-title">✅ Passed Checks</div>
                <div class="check-detail"><strong><?= $passed_checks ?></strong> / <?= $total_checks ?></div>
            </div>
            <div class="check <?= $failed_checks > 0 ? 'fail' : 'pass' ?>">
                <div class="check-title">❌ Failed Checks</div>
                <div class="check-detail"><strong><?= $failed_checks ?></strong> issue<?= $failed_checks !== 1 ? 's' : '' ?></div>
            </div>
            <div class="check <?= $warnings > 0 ? 'warn' : 'pass' ?>">
                <div class="check-title">⚠️ Warnings</div>
                <div class="check-detail"><strong><?= $warnings ?></strong> warning<?= $warnings !== 1 ? 's' : '' ?></div>
            </div>
        </div>

        <!-- DETAILED CHECKS BY SECTION -->
        <?php 
        $current_section = '';
        foreach ($checks as $check) {
            if ($check['section'] !== $current_section) {
                if ($current_section !== '') echo '</div>';
                $current_section = $check['section'];
                echo '<div class="section"><h2>' . $current_section . '</h2>';
            }
        ?>
            <div class="check <?= $check['status'] ?>">
                <div class="check-title"><?= $check['name'] ?></div>
                <div class="check-detail">
                    <strong><?= $check['message'] ?></strong><br>
                    <small><?= $check['detail'] ?></small>
                </div>
            </div>
        <?php } echo '</div>'; ?>

        <!-- DEPLOYMENT READINESS -->
        <div class="summary">
            <div class="score"><?= $score ?>%</div>
            <div style="font-size: 1.2em; margin-top: 10px;">
                <?php if ($ready): ?>
                    ✅ <strong>DEPLOYMENT READY</strong>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;">Your system is configured for production deployment!</p>
                <?php elseif ($failed_checks > 0): ?>
                    ❌ <strong>DEPLOYMENT BLOCKED</strong>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;">Please fix the failed checks before deploying to production.</p>
                <?php else: ?>
                    ⚠️ <strong>REVIEW RECOMMENDED</strong>
                    <p style="margin: 10px 0 0 0; font-size: 0.9em;">Review the warnings before deploying to production.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- NEXT STEPS -->
        <div class="section">
            <h2>📋 Next Steps</h2>
            <table>
                <tr>
                    <th>Step</th>
                    <th>Action</th>
                </tr>
                <tr>
                    <td><strong>1. Database Backup</strong></td>
                    <td>Always backup your database before deployment</td>
                </tr>
                <tr>
                    <td><strong>2. SSL Certificate</strong></td>
                    <td>Enable HTTPS in InfinityFree Control Panel → Auto SSL</td>
                </tr>
                <tr>
                    <td><strong>3. Environment Variable</strong></td>
                    <td>Ensure <code>ENVIRONMENT = 'production'</code> in config</td>
                </tr>
                <tr>
                    <td><strong>4. Error Logging</strong></td>
                    <td>Monitor error logs in Control Panel for issues</td>
                </tr>
                <tr>
                    <td><strong>5. Test All Features</strong></td>
                    <td>Login, upload files, send emails, create exams</td>
                </tr>
                <tr>
                    <td><strong>6. Remove Debug Pages</strong></td>
                    <td>Delete all test_*.php and debug_*.php files</td>
                </tr>
            </table>
        </div>

        <!-- ENVIRONMENT INFO -->
        <div class="section">
            <h2>ℹ️ Environment Information</h2>
            <table>
                <tr>
                    <th>Item</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Application</td>
                    <td><?= APP_NAME ?></td>
                </tr>
                <tr>
                    <td>Environment</td>
                    <td><?= ENVIRONMENT ?></td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><?= phpversion() ?></td>
                </tr>
                <tr>
                    <td>Server</td>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                </tr>
                <tr>
                    <td>Domain</td>
                    <td><?= $_SERVER['HTTP_HOST'] ?></td>
                </tr>
                <tr>
                    <td>Base URL</td>
                    <td><?= BASE_URL ?></td>
                </tr>
                <tr>
                    <td>Database</td>
                    <td><?= DB_NAME ?> @ <?= DB_HOST ?></td>
                </tr>
            </table>
        </div>

        <!-- RECOMMENDATIONS -->
        <div class="section" style="background: #fef5e7; border-radius: 8px; padding: 20px;">
            <h2 style="color: #7d6608;">💡 Deployment Recommendations</h2>
            <ul style="line-height: 1.8;">
                <li>✅ Change default admin password immediately after deployment</li>
                <li>✅ Enable automatic backups via InfinityFree Control Panel</li>
                <li>✅ Set up email notifications for errors and alerts</li>
                <li>✅ Monitor disk space and database usage regularly</li>
                <li>✅ Keep your application updated with latest patches</li>
                <li>✅ Use strong, unique passwords for all accounts</li>
                <li>✅ Set up regular database backups (download weekly)</li>
                <li>✅ Test all critical workflows before going live</li>
                <li>✅ Document any customizations you make</li>
                <li>✅ Set up an admin email for notifications</li>
            </ul>
        </div>

    </div>
</body>
</html>
