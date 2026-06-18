<?php
/**
 * Admin: Manage Subject Teachers
 * Allows admin to assign and manage teachers for subjects
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/phpmailer_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if user is admin or superadmin
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] !== 'admin' && $_SESSION['role_name'] !== 'superadmin')) {
    http_response_code(403);
    die('Access Denied');
}

// Get all teachers
$teachers_query = "
    SELECT u.id, u.full_name, u.email, t.trade_name
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    WHERE u.role_id = (SELECT id FROM roles WHERE name = 'teacher')
    ORDER BY u.full_name
";
$teachers = $pdo->query($teachers_query)->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects
$subjects_query = "
    SELECT s.id, s.subject_name, t.trade_name
    FROM subjects s
    LEFT JOIN trades t ON s.trade_id = t.id
    ORDER BY t.trade_name, s.subject_name
";
$subjects = $pdo->query($subjects_query)->fetchAll(PDO::FETCH_ASSOC);

// Get all subject-teacher assignments
$assignments_query = "
    SELECT st.id, st.subject_id, st.teacher_id, s.subject_name, u.full_name
    FROM subject_teacher st
    JOIN subjects s ON st.subject_id = s.id
    JOIN users u ON st.teacher_id = u.id
    ORDER BY s.subject_name, u.full_name
";
$assignments = $pdo->query($assignments_query)->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign') {
        $subject_id = intval($_POST['subject_id']);
        $teacher_id = intval($_POST['teacher_id']);
        
        try {
            // Get teacher info
            $teacher_stmt = $pdo->prepare("SELECT u.email, u.full_name FROM users u WHERE u.id = ?");
            $teacher_stmt->execute([$teacher_id]);
            $teacher = $teacher_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get subject info
            $subject_stmt = $pdo->prepare("SELECT s.subject_name FROM subjects s WHERE s.id = ?");
            $subject_stmt->execute([$subject_id]);
            $subject = $subject_stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("
                INSERT INTO subject_teacher (subject_id, teacher_id, created_by)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$subject_id, $teacher_id, $_SESSION['user_id']]);
            
            // Send email notification to teacher
            if ($teacher && $subject && !empty($teacher['email'])) {
                try {
                    $mail = getMailer();
                    
                    if ($mail) {
                        $mail->addAddress($teacher['email'], $teacher['full_name']);
                        $mail->Subject = 'New Subject Assignment - ' . $subject['subject_name'];
                        
                        $mail->isHTML(true);
                        $mail->Body = "
                        <h2>Subject Assignment Notification</h2>
                        <p>Dear {$teacher['full_name']},</p>
                        <p>You have been assigned to teach the following subject:</p>
                        <p><strong>Subject:</strong> {$subject['subject_name']}</p>
                        <p>You can now create and manage exams for this subject. Please log in to the system to get started.</p>
                        <p>Best regards,<br>Exam System</p>
                        ";
                        
                        $mail->send();
                    }
                } catch (Exception $e) {
                    // Log email error but don't fail the assignment
                    error_log('Email send failed for teacher assignment: ' . $e->getMessage());
                }
            }
            
            echo json_encode(['status' => 'success', 'message' => '✅ Teacher assigned successfully and email sent']);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo json_encode(['status' => 'error', 'message' => '❌ This teacher is already assigned to this subject']);
            } else {
                echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
            }
        }
        exit;
    }
    
    if ($action === 'remove') {
        $assignment_id = intval($_POST['assignment_id']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM subject_teacher WHERE id = ?");
            $stmt->execute([$assignment_id]);
            
            echo json_encode(['status' => 'success', 'message' => '✅ Assignment removed successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Subject Teachers</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
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
            padding: 30px;
            text-align: center;
        }
        
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 40px;
            padding: 25px;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 5px solid #667eea;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        select, input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-row .form-group {
            margin-bottom: 0;
        }
        
        button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .btn-danger {
            background: #f44336;
            padding: 6px 12px;
            font-size: 0.9em;
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert.success {
            background: #c8e6c9;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
            display: block;
        }
        
        .alert.error {
            background: #ffcdd2;
            color: #c62828;
            border-left: 4px solid #f44336;
            display: block;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-teacher {
            background: #c8e6c9;
            color: #2e7d32;
        }
        
        .badge-trade {
            background: #bbdefb;
            color: #1565c0;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .empty-state p { font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 Manage Subject Teachers</h1>
            <p>Assign and manage teachers for subjects</p>
        </div>
        
        <div class="content">
            <!-- Alert Messages -->
            <div id="alert" class="alert"></div>
            
            <!-- Assign Teacher Section -->
            <div class="section">
                <h2>➕ Assign Teacher to Subject</h2>
                <form id="assignForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_id">Select Subject *</label>
                            <select id="subject_id" required>
                                <option value="">-- Choose a subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>">
                                        <?= htmlspecialchars($subject['subject_name']) ?> (<?= htmlspecialchars($subject['trade_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="teacher_id">Select Teacher *</label>
                            <select id="teacher_id" required>
                                <option value="">-- Choose a teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>">
                                        <?= htmlspecialchars($teacher['full_name']) ?> (<?= htmlspecialchars($teacher['trade_name'] ?? 'N/A') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit">Assign Teacher</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Current Assignments Section -->
            <div class="section">
                <h2>📋 Current Assignments</h2>
                
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <p>No teachers assigned yet. Assign a teacher to a subject above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-trade"><?= htmlspecialchars($assignment['subject_name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-teacher"><?= htmlspecialchars($assignment['full_name']) ?></span>
                                        </td>
                                        <td>
                                            <button class="btn-danger" onclick="removeAssignment(<?= $assignment['id'] ?>)">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Teachers Summary -->
            <div class="section">
                <h2>👨‍🏫 Teachers Summary</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>Email</th>
                                <th>Trade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?= htmlspecialchars($teacher['full_name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['email']) ?></td>
                                    <td><span class="badge badge-trade"><?= htmlspecialchars($teacher['trade_name'] ?? 'N/A') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Show alert message
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = 'alert ' + type;
            
            setTimeout(() => {
                alert.className = 'alert';
            }, 5000);
        }
        
        // Assign teacher to subject
        document.getElementById('assignForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const subject_id = document.getElementById('subject_id').value;
            const teacher_id = document.getElementById('teacher_id').value;
            
            if (!subject_id || !teacher_id) {
                showAlert('❌ Please select both subject and teacher', 'error');
                return;
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=assign&subject_id=${subject_id}&teacher_id=${teacher_id}`
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    document.getElementById('assignForm').reset();
                    
                    // Reload page after 1 second
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        });
        
        // Remove assignment
        async function removeAssignment(assignmentId) {
            if (!confirm('Are you sure you want to remove this assignment?')) return;
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&assignment_id=${assignmentId}`
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
