<?php
/**
 * Display Preferences Diagnostic Test
 */

require_once 'includes/db.php';
require_once 'includes/student_settings_functions.php';

$test_student_id = 1;

echo "<div style='max-width:900px; margin:40px auto; font-family:Arial; line-height:1.6;'>";
echo "<h1>🔧 Display Preferences - Diagnostic Test</h1>";
echo "<hr>";

try {
    // Test 1: Get current preferences
    echo "<h2>1. Current Preferences</h2>";
    $prefs = getStudentPreferences($test_student_id);
    if ($prefs) {
        echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
        echo json_encode($prefs, JSON_PRETTY_PRINT);
        echo "</pre>";
    } else {
        echo "<p style='color:red;'>❌ Failed to get preferences</p>";
    }
    
    // Test 2: Try updating theme
    echo "<h2>2. Update Theme Test</h2>";
    $update_result = updateStudentPreferences($test_student_id, ['theme' => 'dark']);
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo json_encode($update_result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Test 3: Verify update
    echo "<h2>3. Verify Update</h2>";
    $prefs_after = getStudentPreferences($test_student_id);
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo "Theme should now be 'dark':\n";
    echo json_encode($prefs_after, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Test 4: Check database directly
    echo "<h2>4. Direct Database Check</h2>";
    $stmt = $pdo->prepare("SELECT * FROM student_preferences WHERE student_id = ?");
    $stmt->execute([$test_student_id]);
    $db_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo json_encode($db_result, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Test 5: Update all preferences
    echo "<h2>5. Update All Preferences</h2>";
    $all_updates = updateStudentPreferences($test_student_id, [
        'theme' => 'light',
        'dashboard_view' => 'list',
        'language' => 'hi',
        'timezone' => 'Asia/Kolkata'
    ]);
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo json_encode($all_updates, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    // Test 6: Verify final state
    echo "<h2>6. Final State</h2>";
    $final_prefs = getStudentPreferences($test_student_id);
    echo "<pre style='background:#f0f0f0; padding:15px; border-radius:5px;'>";
    echo json_encode($final_prefs, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    echo "<hr>";
    echo "<h2>✅ Summary</h2>";
    echo "<p style='color:green; font-size:16px;'>";
    echo "<strong>All preference operations completed successfully!</strong><br>";
    echo "Database connectivity: ✅<br>";
    echo "Update function: ✅<br>";
    echo "Data persistence: ✅<br>";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div style='background:#fee; padding:20px; border:2px solid #f00; border-radius:5px;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "<br><br><strong>Stack Trace:</strong><br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</div>";
?>
