<?php
/**
 * Create practical_exam_invitations table
 * Required for exam link generation feature
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/exam_invitation_functions.php';

try {
    echo "<h3>🔧 Setting up Practical Exam Invitations...</h3>";
    
    // Call the initialization function
    $result = initExamInvitationsTable();
    
    if ($result['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ Table initialization successful!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ " . htmlspecialchars($result['message']) . "</p>";
    }
    
    // Verify table structure
    echo "<h4>📋 Table Structure:</h4>";
    echo "<pre>";
    $columns_stmt = $pdo->query("DESCRIBE practical_exam_invitations");
    $columns = $columns_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        printf("%-25s %-35s %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'] === 'YES' ? '(nullable)' : '(required)'
        );
    }
    echo "</pre>";
    
    echo "<h4>✅ You can now:</h4>";
    echo "<ul>";
    echo "<li>Go to Admin Panel → Practical Exams</li>";
    echo "<li>Click 'Generate Link' button on any exam</li>";
    echo "<li>Share the link with students</li>";
    echo "<li>Students click link to join exam</li>";
    echo "</ul>";
    
    echo "<p><a href='admin/practical_exams.php' class='btn btn-primary'>Go to Admin Panel</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    exit(1);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Practical Exams</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 Practical Exam Management Setup</h1>
    </div>
</body>
</html>
