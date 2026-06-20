<?php
/**
 * Database Setup: Create Triggers for Automatic Status Updates
 */

require_once 'config.php';
require_once 'includes/db.php';

echo "<h2>Setting up Database Triggers</h2>\n\n";

try {
    // Drop existing trigger if it exists
    $pdo->exec("DROP TRIGGER IF EXISTS update_submission_status_on_mark");
    
    // Create trigger to update submission status when marks are assigned
    $trigger_sql = "
    CREATE TRIGGER update_submission_status_on_mark
    AFTER INSERT ON practical_marks
    FOR EACH ROW
    BEGIN
        UPDATE practical_submissions 
        SET submission_status = 'marked'
        WHERE id = NEW.submission_id;
    END
    ";
    
    $pdo->exec($trigger_sql);
    echo "✓ Trigger 'update_submission_status_on_mark' created successfully\n";
    echo "  → When teacher assigns marks, submission status changes to 'marked'\n\n";
    
    // Drop existing trigger for mark updates
    $pdo->exec("DROP TRIGGER IF EXISTS update_submission_status_on_mark_update");
    
    // Create trigger for mark updates
    $trigger_sql2 = "
    CREATE TRIGGER update_submission_status_on_mark_update
    AFTER UPDATE ON practical_marks
    FOR EACH ROW
    BEGIN
        UPDATE practical_submissions 
        SET submission_status = 'marked'
        WHERE id = NEW.submission_id;
    END
    ";
    
    $pdo->exec($trigger_sql2);
    echo "✓ Trigger 'update_submission_status_on_mark_update' created successfully\n";
    echo "  → When marks are updated, status remains 'marked'\n\n";
    
    echo "<div style='background: #d5f4e6; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<h4 style='color: #27ae60;'>✅ Triggers Activated</h4>";
    echo "<ul>";
    echo "<li>Status automatically updates from 'awaiting_marks' to 'marked'</li>";
    echo "<li>Students will see 'Marked' instead of 'Awaiting Marks' in their practical list</li>";
    echo "<li>Certificate generation is triggered automatically</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #fadbd8; padding: 1rem; border-radius: 8px;'>";
    echo "<h4 style='color: #c0392b;'>⚠ Trigger Error</h4>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: #7b241c;'>Note: Some database systems may require specific syntax. Please verify your MySQL version supports triggers.</p>";
    echo "</div>";
}
?>
