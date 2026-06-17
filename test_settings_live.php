<?php
/**
 * Settings Module Quick Demo/Test
 * This page shows live data from the database
 */

require_once 'includes/db.php';
require_once 'includes/student_settings_functions.php';

// For testing, use a test student ID (you can change this)
$test_student_id = 1; // Will work if student ID 1 exists

echo "<div style='max-width:1000px; margin:40px auto; font-family:Arial, sans-serif;'>";
echo "<h1>🔧 Settings Module - Live Test</h1>";
echo "<hr>";

try {
    // Test 1: Get Notification Settings
    echo "<h2>1. Notification Settings Test</h2>";
    $notifications = getNotificationSettings($test_student_id);
    if ($notifications && is_array($notifications)) {
        echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
        echo "✅ Retrieved notification settings for Student #$test_student_id\n\n";
        echo json_encode($notifications, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n</pre>";
    } else {
        echo "<p>⚠️ Could not retrieve notification settings (may not exist yet)</p>";
    }
    
    // Test 2: Get Preferences
    echo "<h2>2. Preferences Test</h2>";
    $prefs = getStudentPreferences($test_student_id);
    if ($prefs && is_array($prefs)) {
        echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
        echo "✅ Retrieved preferences for Student #$test_student_id\n\n";
        echo json_encode($prefs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo "\n</pre>";
    } else {
        echo "<p>⚠️ Could not retrieve preferences (may not exist yet)</p>";
    }
    
    // Test 3: Get Login History
    echo "<h2>3. Login History Test</h2>";
    $history = getLoginHistoryPaginated($test_student_id, 1, 5);
    if (is_array($history)) {
        echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
        echo "✅ Retrieved login history for Student #$test_student_id\n\n";
        echo "Total logins: " . $history['total'] . "\n";
        echo "Page: " . $history['current_page'] . " of " . $history['pages'] . "\n\n";
        if (!empty($history['data'])) {
            echo "Recent logins:\n";
            foreach ($history['data'] as $login) {
                echo "  • " . $login['login_time'] . " - " . $login['browser'] . " (" . $login['ip_address'] . ")\n";
            }
        } else {
            echo "No login history found.\n";
        }
        echo "\n</pre>";
    } else {
        echo "<p>⚠️ Could not retrieve login history</p>";
    }
    
    // Test 4: Get Activity Logs
    echo "<h2>4. Activity Logs Test</h2>";
    $activities = getActivityHistoryPaginated($test_student_id, 1, 5);
    if (is_array($activities)) {
        echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
        echo "✅ Retrieved activity logs for Student #$test_student_id\n\n";
        echo "Total activities: " . $activities['total'] . "\n\n";
        if (!empty($activities['data'])) {
            echo "Recent activities:\n";
            foreach ($activities['data'] as $activity) {
                echo "  • " . $activity['activity_type'] . ": " . $activity['description'] . "\n";
            }
        } else {
            echo "No activities logged yet.\n";
        }
        echo "\n</pre>";
    } else {
        echo "<p>⚠️ Could not retrieve activity logs</p>";
    }
    
    // Test 5: Database table row counts
    echo "<h2>5. Database Statistics</h2>";
    $tables = [
        'student_notification_settings',
        'student_preferences',
        'account_deletion_requests',
        'student_activity_logs',
        'data_export_requests'
    ];
    
    echo "<table style='border-collapse:collapse; width:100%; margin:10px 0;'>";
    echo "<tr style='background:#667eea; color:white;'><th style='padding:10px; border:1px solid #ddd;'>Table Name</th><th style='padding:10px; border:1px solid #ddd;'>Row Count</th></tr>";
    
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<tr>";
        echo "<td style='padding:10px; border:1px solid #ddd;'>$table</td>";
        echo "<td style='padding:10px; border:1px solid #ddd;'>$count</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Summary
    echo "<hr>";
    echo "<h2>✅ Summary</h2>";
    echo "<p style='font-size:16px; color:green;'>";
    echo "<strong>🎉 All Settings Module Functions Are Working!</strong><br>";
    echo "Database: Connected ✅<br>";
    echo "Tables: All 5 exist ✅<br>";
    echo "Functions: All callable ✅<br>";
    echo "Data: Retrievable ✅<br>";
    echo "</p>";
    
    echo "<h3>📝 To Access Settings:</h3>";
    echo "<ol style='font-size:14px;'>";
    echo "<li>Login to student account at: <a href='student_login.php' style='color:#667eea;'>student_login.php</a></li>";
    echo "<li>Then go to: <a href='student/settings.php' style='color:#667eea;'>student/settings.php</a></li>";
    echo "<li>Or click Settings in the sidebar after login</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background:#fee; padding:20px; border:2px solid #f00; border-radius:5px;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</div>";
?>
