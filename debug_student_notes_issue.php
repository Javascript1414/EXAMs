<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Allow anyone to see this debug info
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Student Notes Issue</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #333; color: white; }
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
    </style>
</head>
<body>

<h1>🔍 Student Notes Debug</h1>

<div class="section">
    <h2>📚 All Notes in System</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Trade</th>
            <th>Subject</th>
            <th>Title</th>
            <th>Status</th>
            <th>Uploaded</th>
        </tr>
        <?php
        $stmt = $pdo->query("
            SELECT n.id, n.trade_id, n.subject_id, n.title, n.status, n.created_at,
                   s.subject_name, t.trade_name
            FROM notes n
            LEFT JOIN subjects s ON n.subject_id = s.id
            LEFT JOIN trades t ON n.trade_id = t.id
            ORDER BY n.created_at DESC
        ");
        $all_notes = $stmt->fetchAll();
        
        foreach ($all_notes as $note) {
            $status_class = $note['status'] === 'active' ? 'status-active' : 'status-inactive';
            echo "<tr>
                <td>{$note['id']}</td>
                <td>{$note['trade_name']}</td>
                <td>{$note['subject_name']}</td>
                <td>{$note['title']}</td>
                <td><span class='{$status_class}'>{$note['status']}</span></td>
                <td>" . date('M d, Y', strtotime($note['created_at'])) . "</td>
            </tr>";
        }
        
        if (empty($all_notes)) {
            echo "<tr><td colspan='6' style='text-align: center; color: red;'><strong>❌ NO NOTES FOUND</strong></td></tr>";
        }
        ?>
    </table>
    <p><strong>Total Notes:</strong> <?php echo count($all_notes); ?></p>
</div>

<div class="section">
    <h2>👥 Student Trade Assignments</h2>
    <table>
        <tr>
            <th>Student ID</th>
            <th>Student Name</th>
            <th>Assigned Trades</th>
            <th>Trade Count</th>
        </tr>
        <?php
        $stmt = $pdo->query("
            SELECT DISTINCT st.student_id, u.full_name, u.id,
                   GROUP_CONCAT(t.trade_name SEPARATOR ', ') as trades,
                   COUNT(st.trade_id) as trade_count
            FROM student_trades st
            JOIN users u ON st.student_id = u.id
            JOIN trades t ON st.trade_id = t.id
            GROUP BY st.student_id
            ORDER BY u.full_name
            LIMIT 10
        ");
        $students = $stmt->fetchAll();
        
        foreach ($students as $student) {
            echo "<tr>
                <td>{$student['student_id']}</td>
                <td>{$student['full_name']}</td>
                <td>{$student['trades']}</td>
                <td>{$student['trade_count']}</td>
            </tr>";
        }
        
        if (empty($students)) {
            echo "<tr><td colspan='4' style='text-align: center; color: red;'><strong>❌ NO STUDENT TRADE ASSIGNMENTS FOUND</strong></td></tr>";
        }
        ?>
    </table>
    <p><strong>Students with Trades:</strong> <?php echo count($students); ?></p>
</div>

<div class="section">
    <h2>🎯 Notes Available Per Trade</h2>
    <table>
        <tr>
            <th>Trade</th>
            <th>Active Notes</th>
            <th>Inactive Notes</th>
            <th>Total Notes</th>
        </tr>
        <?php
        $stmt = $pdo->query("
            SELECT t.id, t.trade_name,
                   SUM(CASE WHEN n.status = 'active' THEN 1 ELSE 0 END) as active_count,
                   SUM(CASE WHEN n.status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
                   COUNT(n.id) as total_count
            FROM trades t
            LEFT JOIN notes n ON t.id = n.trade_id
            GROUP BY t.id
            ORDER BY t.trade_name
        ");
        $trades_notes = $stmt->fetchAll();
        
        foreach ($trades_notes as $trade) {
            $active = $trade['active_count'] ?? 0;
            $inactive = $trade['inactive_count'] ?? 0;
            $total = $trade['total_count'] ?? 0;
            $active_class = $active > 0 ? 'status-active' : '';
            
            echo "<tr>
                <td>{$trade['trade_name']}</td>
                <td><span class='{$active_class}'>{$active}</span></td>
                <td><span class='status-inactive'>{$inactive}</span></td>
                <td>{$total}</td>
            </tr>";
        }
        ?>
    </table>
</div>

<div class="section">
    <h2>⚠️ Issues Found</h2>
    <ul>
        <?php
        $issues = [];
        
        // Check if there are any notes
        if (count($all_notes) === 0) {
            $issues[] = "❌ <strong>NO NOTES IN DATABASE</strong> - Admin needs to upload notes first";
        }
        
        // Check if there are any student trade assignments
        if (count($students) === 0) {
            $issues[] = "❌ <strong>NO STUDENT TRADES ASSIGNED</strong> - Admin must assign trades to students in the student management panel";
        }
        
        // Check for inactive notes
        $inactive_notes = array_filter($all_notes, fn($n) => $n['status'] === 'inactive');
        if (count($inactive_notes) > 0) {
            $issues[] = "⚠️ <strong>" . count($inactive_notes) . " NOTES ARE INACTIVE</strong> - Students can't see inactive notes. Admin must toggle them to 'active'";
        }
        
        // Check for subject-trade mismatches
        $stmt = $pdo->query("
            SELECT n.id, n.title
            FROM notes n
            WHERE n.subject_id NOT IN (
                SELECT id FROM subjects WHERE subjects.trade_id = n.trade_id
            )
        ");
        $mismatched = $stmt->fetchAll();
        if (count($mismatched) > 0) {
            $issues[] = "❌ <strong>" . count($mismatched) . " NOTES HAVE SUBJECT-TRADE MISMATCH</strong> - Subject doesn't belong to the selected trade";
        }
        
        if (empty($issues)) {
            echo "<li style='color: green; font-weight: bold;'>✅ No obvious issues found</li>";
        } else {
            foreach ($issues as $issue) {
                echo "<li>$issue</li>";
            }
        }
        ?>
    </ul>
</div>

<div class="section">
    <h2>💡 How to Fix</h2>
    <ol>
        <li><strong>Upload Notes in Admin:</strong> Go to Admin Panel → Notes → Add new notes for each subject</li>
        <li><strong>Make sure notes are ACTIVE:</strong> Check status column - toggle to 'active' if needed</li>
        <li><strong>Assign Trades to Students:</strong> Admin Panel → Student Management → Assign trades to each student</li>
        <li><strong>Verify Subject-Trade Link:</strong> Subject must belong to the same trade as the note</li>
    </ol>
</div>

</body>
</html>
