<?php
/**
 * Admin: Add New Teacher
 * Allows admin to create and add new teacher accounts
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

// Get all trades for dropdown
$trades_query = "SELECT id, trade_name FROM trades ORDER BY trade_name";
$trades = $pdo->query($trades_query)->fetchAll(PDO::FETCH_ASSOC);

// Get teacher role ID
$teacher_role_query = "SELECT id FROM roles WHERE name = 'teacher' LIMIT 1";
$teacher_role = $pdo->query($teacher_role_query)->fetch(PDO::FETCH_ASSOC);
$teacher_role_id = $teacher_role['id'] ?? null;

if (!$teacher_role_id) {
    die('❌ Error: Teacher role not found in database');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_teacher') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $trade_id = intval($_POST['trade_id'] ?? 0);
        $batch = trim($_POST['batch'] ?? '');
        $institute_name = trim($_POST['institute_name'] ?? '');
        
        // Validation
        $errors = [];
        if (empty($full_name)) $errors[] = 'Full name is required';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if (empty($phone)) $errors[] = 'Phone number is required';
        if (empty($password) || strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
        if ($trade_id <= 0) $errors[] = 'Trade selection is required';
        
        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
            exit;
        }
        
        try {
            // Check if email already exists
            $existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $existing->execute([$email]);
            if ($existing->fetch()) {
                echo json_encode(['status' => 'error', 'message' => '❌ Email already registered']);
                exit;
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert teacher
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name, email, phone, password, trade_id, role_id,
                    batch, institute_name, email_verified, status, approval_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $full_name,
                $email,
                $phone,
                $hashed_password,
                $trade_id,
                $teacher_role_id,
                $batch,
                $institute_name,
                1, // email_verified = true (admin created)
                'active',
                'approved' // auto-approve admin-created accounts
            ]);
            
            $teacher_id = $pdo->lastInsertId();
            
            // Send welcome email to teacher
            try {
                $mail = getMailer();
                
                if ($mail) {
                    $mail->addAddress($email, $full_name);
                    $mail->Subject = 'Welcome to ' . APP_NAME . ' - Teacher Account Created';
                    
                    $mail->isHTML(true);
                    $mail->Body = "
                    <h2>Welcome to " . APP_NAME . "!</h2>
                    <p>Dear {$full_name},</p>
                    <p>Your teacher account has been successfully created by the administrator.</p>
                    
                    <h3>🔐 Login Credentials:</h3>
                    <div style='background: #f0f4ff; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; margin: 15px 0;'>
                        <p><strong>Email:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>{$email}</code></p>
                        <p><strong>Password:</strong> <code style='background: white; padding: 5px 10px; border-radius: 3px;'>{$password}</code></p>
                    </div>
                    
                    <h3>📍 Login URL:</h3>
                    <p><a href='" . BASE_URL . "/staff_login.php' style='color: #667eea; text-decoration: none;'>" . BASE_URL . "/staff_login.php</a></p>
                    
                    <h3>✅ Next Steps:</h3>
                    <ol>
                        <li>Log in to your account using the credentials above</li>
                        <li>Check your assigned subjects in the dashboard</li>
                        <li>Change your password (optional but recommended)</li>
                        <li>Create exams and manage course materials</li>
                        <li>View student results and provide feedback</li>
                    </ol>
                    
                    <div style='background: #fff3cd; padding: 12px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ff9800;'>
                        <p><strong>⚠️ Security Note:</strong></p>
                        <p>• Keep your password safe and secure</p>
                        <p>• Never share your login credentials with anyone</p>
                        <p>• If you forget your password, use the \"Forgot Password\" option on the login page</p>
                    </div>
                    
                    <p>If you have any questions or need assistance, please contact the administrator.</p>
                    
                    <p>Best regards,<br><strong>" . APP_NAME . " Admin Team</strong></p>
                    ";
                    
                    $mail->AltBody = "Welcome to " . APP_NAME . "!\n\nEmail: {$email}\nPassword: {$password}\n\nLogin at: " . BASE_URL . "/staff_login.php";
                    
                    $mail->send();
                }
            } catch (Exception $e) {
                // Log email error but don't fail teacher creation
                error_log('Teacher welcome email failed: ' . $e->getMessage());
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => '✅ Teacher added successfully! Welcome email sent to ' . $email,
                'teacher_id' => $teacher_id,
                'teacher_name' => $full_name,
                'teacher_email' => $email
            ]);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Get all teachers
$teachers_query = "
    SELECT u.id, u.full_name, u.email, u.phone, t.trade_name, u.created_at
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    WHERE u.role_id = ?
    ORDER BY u.created_at DESC
";
$teachers_stmt = $pdo->prepare($teachers_query);
$teachers_stmt->execute([$teacher_role_id]);
$all_teachers = $teachers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Teacher - Admin Panel</title>
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
        
        .content { padding: 30px; }
        
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
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-row.three {
            grid-template-columns: repeat(3, 1fr);
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
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
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
        
        .instructions {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #2196f3;
            margin-bottom: 20px;
        }
        
        .instructions h3 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #333;
        }
        
        .instructions li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👨‍🏫 Add New Teacher</h1>
            <p>Create new teacher accounts in the system</p>
        </div>
        
        <div class="content">
            <!-- Alert Messages -->
            <div id="alert" class="alert"></div>
            
            <!-- Instructions -->
            <div class="instructions">
                <h3>📝 Complete Workflow:</h3>
                <ol>
                    <li><strong>Add Teacher:</strong> Create new teacher account below</li>
                    <li><strong>Assign Subject:</strong> Go to "Manage Subject Teachers" to assign subjects</li>
                    <li><strong>Create Exam:</strong> Teacher creates exam for their subject</li>
                    <li><strong>View Results:</strong> Students see results with teacher info</li>
                </ol>
            </div>
            
            <!-- Add Teacher Form -->
            <div class="section">
                <h2>➕ Create New Teacher Account</h2>
                <form id="addTeacherForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required placeholder="e.g., John Smith">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required placeholder="e.g., john@school.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required placeholder="e.g., 03001234567">
                        </div>
                        
                        <div class="form-group">
                            <label for="trade_id">Trade/Department *</label>
                            <select id="trade_id" name="trade_id" required>
                                <option value="">-- Select Trade --</option>
                                <?php foreach ($trades as $trade): ?>
                                    <option value="<?= $trade['id'] ?>">
                                        <?= htmlspecialchars($trade['trade_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" required placeholder="Min 6 characters">
                        </div>
                        
                        <div class="form-group">
                            <label for="batch">Batch (Optional)</label>
                            <input type="text" id="batch" name="batch" placeholder="e.g., 2024-2025">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="institute_name">Institute Name (Optional)</label>
                        <input type="text" id="institute_name" name="institute_name" placeholder="e.g., ABC Institute">
                    </div>
                    
                    <button type="submit">➕ Add Teacher</button>
                </form>
            </div>
            
            <!-- All Teachers -->
            <div class="section">
                <h2>👥 All Teachers</h2>
                
                <?php if (empty($all_teachers)): ?>
                    <div class="empty-state">
                        <p>No teachers created yet. Add your first teacher above.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Teacher Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Trade</th>
                                    <th>Created On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_teachers as $teacher): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($teacher['full_name']) ?></strong></td>
                                        <td><?= htmlspecialchars($teacher['email']) ?></td>
                                        <td><?= htmlspecialchars($teacher['phone']) ?></td>
                                        <td>
                                            <span class="badge badge-trade">
                                                <?= htmlspecialchars($teacher['trade_name'] ?? 'N/A') ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($teacher['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <p style="margin-top: 15px; color: #666;">
                        Total Teachers: <strong><?= count($all_teachers) ?></strong>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Next Steps -->
            <div class="section" style="background: linear-gradient(135deg, #c8e6c9 0%, #a5d6a7 100%); border-left-color: #4caf50;">
                <h2 style="color: #2e7d32;">✅ Next Steps</h2>
                <ol style="margin-left: 20px; color: #333; line-height: 1.8;">
                    <li>✅ Created teacher account</li>
                    <li>👉 Go to <strong>"Manage Subject Teachers"</strong> to assign subjects</li>
                    <li>Teacher will be able to create exams for assigned subjects</li>
                    <li>Students will see teacher name and email in exam results</li>
                </ol>
            </div>
        </div>
    </div>
    
    <script>
        // Show alert
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = 'alert ' + type;
            
            setTimeout(() => {
                alert.className = 'alert';
            }, 5000);
        }
        
        // Add teacher form submit
        document.getElementById('addTeacherForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('addTeacherForm'));
            formData.append('action', 'add_teacher');
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    showAlert(data.message, 'success');
                    document.getElementById('addTeacherForm').reset();
                    
                    // Reload page after 2 seconds
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showAlert(data.message, 'error');
                }
            } catch (error) {
                showAlert('❌ Error: ' + error.message, 'error');
            }
        });
    </script>
</body>
</html>
