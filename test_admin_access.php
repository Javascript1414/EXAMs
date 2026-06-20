<?php
/**
 * Test Admin Access to Practical Exams
 * This script verifies the admin page is accessible with proper role
 */

session_start();
require_once 'config.php';
require_once 'includes/db.php';

// Simulate admin session
$_SESSION['user_id'] = 27;  // Test Admin from database
$_SESSION['role_name'] = 'superadmin';

echo "=== ADMIN ACCESS TEST ===\n\n";
echo "1. Session Data:\n";
echo "   - user_id: " . $_SESSION['user_id'] . "\n";
echo "   - role_name: " . $_SESSION['role_name'] . "\n\n";

// Check access control (same as admin/practical_exams.php)
echo "2. Access Control Check:\n";
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_name'], ['admin', 'superadmin', 'moderator'])) {
    echo "   ❌ DENIED - Access Denied - Admin Only\n";
    echo "   The admin page would return 403 Forbidden\n";
} else {
    echo "   ✅ GRANTED - User has admin access\n";
    echo "   The admin page would load successfully\n\n";
    
    // Now verify the exam_id field was added to the form
    echo "3. Checking if exam_id form field exists:\n";
    
    // Read the admin file to check for exam_id field
    $admin_file = file_get_contents(__DIR__ . '/admin/practical_exams.php');
    
    if (strpos($admin_file, 'name="exam_id"') !== false) {
        echo "   ✅ FOUND - exam_id field is present in the form\n";
        echo "   The form now allows linking practical exams to theory exams\n";
    } else {
        echo "   ❌ NOT FOUND - exam_id field missing\n";
    }
    
    echo "\n4. Form Elements Added:\n";
    if (strpos($admin_file, 'Link to Theory Exam') !== false) {
        echo "   ✅ Theory Exam dropdown exists\n";
    }
    if (strpos($admin_file, 'SELECT id, exam_name FROM exams WHERE status') !== false) {
        echo "   ✅ Query to fetch active exams exists\n";
    }
    if (strpos($admin_file, 'Optional') !== false) {
        echo "   ✅ Optional label exists\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nNext Steps:\n";
echo "1. Log in as admin@example.com with password: password\n";
echo "2. Visit: /admin/practical_exams.php\n";
echo "3. Click 'Create Practical Exam' button\n";
echo "4. You should see the new 'Link to Theory Exam' dropdown\n";
?>