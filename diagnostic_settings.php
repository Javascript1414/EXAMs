<?php
/**
 * Settings Module Diagnostic - Check what's working and what's not
 */

require_once 'includes/db.php';
require_once 'includes/student_settings_functions.php';

$diagnostics = [];
$errors = [];

echo "<div style='background:#f5f5f5; padding:20px; font-family:monospace; line-height:1.8;'>";
echo "<h2>🔍 Student Settings Module - Diagnostic Report</h2>";
echo "<hr>";

// 1. Check database connection
echo "<h3>1️⃣  Database Connection</h3>";
try {
    $test = $pdo->query("SELECT 1");
    echo "✅ PDO Connection: OK\n";
    $diagnostics['pdo'] = true;
} catch (Exception $e) {
    echo "❌ PDO Connection: FAILED - " . $e->getMessage() . "\n";
    $diagnostics['pdo'] = false;
    $errors[] = $e->getMessage();
}

// 2. Check tables exist
echo "\n<h3>2️⃣  Database Tables</h3>";
$required_tables = [
    'student_notification_settings',
    'student_preferences',
    'account_deletion_requests',
    'student_activity_logs',
    'data_export_requests'
];

foreach ($required_tables as $table) {
    try {
        $result = $pdo->query("SELECT COUNT(*) FROM `$table` LIMIT 1");
        echo "✅ Table `$table`: EXISTS\n";
        $diagnostics[$table] = true;
    } catch (Exception $e) {
        echo "❌ Table `$table`: MISSING\n";
        $diagnostics[$table] = false;
        $errors[] = "Table $table not found";
    }
}

// 3. Check functions are callable
echo "\n<h3>3️⃣  Backend Functions</h3>";
$required_functions = [
    'getNotificationSettings',
    'updateNotificationSettings',
    'getStudentPreferences',
    'updateStudentPreferences',
    'getLoginHistoryPaginated',
    'logStudentActivity',
    'getActivityHistoryPaginated',
    'requestAccountDeletion',
    'getDeletionRequestStatus',
    'requestDataExport'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "✅ Function `$func()`: AVAILABLE\n";
        $diagnostics[$func] = true;
    } else {
        echo "❌ Function `$func()`: MISSING\n";
        $diagnostics[$func] = false;
        $errors[] = "Function $func not found";
    }
}

// 4. Check file accessibility
echo "\n<h3>4️⃣  File Accessibility</h3>";
$required_files = [
    'student/settings.php',
    'student/settings_ajax.php',
    'student/change_password.php',
    'includes/student_settings_functions.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ File `$file`: EXISTS\n";
        $diagnostics[$file] = true;
    } else {
        echo "❌ File `$file`: MISSING\n";
        $diagnostics[$file] = false;
        $errors[] = "File $file not found";
    }
}

// 5. Test sample data insertion
echo "\n<h3>5️⃣  Data Operations Test</h3>";
try {
    // Try to insert test record (won't actually insert, just test query)
    $test_query = $pdo->prepare("
        SELECT COUNT(*) as count FROM student_notification_settings
        WHERE student_id = ? LIMIT 1
    ");
    $test_query->execute([999]); // Non-existent student
    echo "✅ Query Execution: OK\n";
    $diagnostics['query_test'] = true;
} catch (Exception $e) {
    echo "❌ Query Execution: FAILED - " . $e->getMessage() . "\n";
    $diagnostics['query_test'] = false;
    $errors[] = $e->getMessage();
}

// 6. Check PHP version and extensions
echo "\n<h3>6️⃣  PHP Environment</h3>";
echo "✅ PHP Version: " . phpversion() . "\n";
echo "✅ PDO Extension: " . (extension_loaded('pdo') ? 'YES' : 'NO') . "\n";
echo "✅ PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'YES' : 'NO') . "\n";
echo "✅ JSON Extension: " . (extension_loaded('json') ? 'YES' : 'NO') . "\n";
echo "✅ Bcrypt Available: " . (function_exists('password_hash') ? 'YES' : 'NO') . "\n";

// Summary
echo "\n<hr>";
echo "<h3>📋 Summary</h3>";
$passed = count(array_filter($diagnostics));
$total = count($diagnostics);
echo "Tests Passed: $passed / $total\n";

if (empty($errors)) {
    echo "\n🎉 All systems operational! Settings module should work fine.\n";
} else {
    echo "\n⚠️  Issues Found:\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

echo "\n<h3>🔗 Next Steps</h3>";
echo "1. Login to student account\n";
echo "2. Visit: <a href='" . BASE_URL . "/student/settings.php' target='_blank'>student/settings.php</a>\n";
echo "3. If still not working, check browser console (F12) for errors\n";
echo "4. Check Apache error log: c:\\xampp\\apache\\logs\\error.log\n";

echo "</div>";
?>
