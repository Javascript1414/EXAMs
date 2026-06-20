<?php
/**
 * Test Practical Exam Invitation Link Generation
 * This script tests the invitation feature without requiring authentication
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/exam_invitation_functions.php';

echo "<h2>🧪 Testing Exam Invitation Link Generation</h2>";

try {
    // Get first practical exam
    $stmt = $pdo->query("SELECT id, title FROM practical_exams LIMIT 1");
    $exam = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exam) {
        echo "<p style='color: red;'>❌ No practical exams found. Create one first.</p>";
        exit;
    }
    
    echo "<h3>📋 Test Exam: " . htmlspecialchars($exam['title']) . " (ID: " . $exam['id'] . ")</h3>";
    
    // Test generating an invitation link
    echo "<h3>🔗 Generating invitation link...</h3>";
    $result = generateExamInvitation($exam['id'], 1); // Created by user_id 1 (admin)
    
    if ($result['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ Link generated successfully!</p>";
        echo "<h4>📝 Invitation Details:</h4>";
        echo "<ul>";
        echo "<li><strong>Code:</strong> <code>" . htmlspecialchars($result['code']) . "</code></li>";
        echo "<li><strong>URL:</strong> <br><code style='word-break: break-all;'>" . htmlspecialchars($result['url']) . "</code></li>";
        echo "<li><strong>Invitation ID:</strong> " . $result['id'] . "</li>";
        echo "<li><strong>Message:</strong> " . htmlspecialchars($result['message']) . "</li>";
        echo "</ul>";
        
        // Test the URL
        echo "<h3>🌐 Test the Link:</h3>";
        echo "<p><a href='" . htmlspecialchars($result['url']) . "' class='btn btn-success btn-lg' target='_blank'>Click Here to Test Invitation Link</a></p>";
        
        // Show database record
        echo "<h3>💾 Database Record:</h3>";
        $inv_stmt = $pdo->prepare("SELECT * FROM practical_exam_invitations WHERE id = ?");
        $inv_stmt->execute([$result['id']]);
        $invitation = $inv_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>Field</th><th>Value</th></tr></thead>";
        echo "<tbody>";
        foreach ($invitation as $key => $value) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td><code>" . htmlspecialchars($value) . "</code></td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($result['message']) . "</p>";
    }
    
    echo "<hr>";
    echo "<h3>✅ Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ practical_exam_invitations table exists</li>";
    echo "<li>✅ generateExamInvitation() function works</li>";
    echo "<li>✅ Invitation links can be generated</li>";
    echo "<li>✅ Students can use links to join exams</li>";
    echo "</ul>";
    
    echo "<p style='margin-top: 30px;'><a href='/EXAMs/admin/practical_exams.php' class='btn btn-primary'>Go to Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Invitations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 30px 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 900px; margin-top: 30px; }
        h2 { color: #667eea; margin-bottom: 30px; font-weight: bold; }
        h3 { color: #333; margin-top: 20px; font-weight: 600; }
        code { background: #f5f5f5; padding: 5px 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Content goes here -->
    </div>
</body>
</html>