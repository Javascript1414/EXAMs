<?php
/**
 * Admin: Google Form Exams Management
 * Manage Google Form exam creation permissions and view reports
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is admin or superadmin
if (!isset($_SESSION['user_id']) || ($_SESSION['role_name'] !== 'admin' && $_SESSION['role_name'] !== 'superadmin')) {
    http_response_code(403);
    die('Access Denied');
}

// Handle AJAX requests for permission management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'grant_permission') {
        $teacher_id = (int)$_POST['teacher_id'];
        $subject_id = (int)$_POST['subject_id'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO google_form_exam_permissions 
                (teacher_id, subject_id, can_create_exams, can_enter_marks, granted_by)
                VALUES (?, ?, 1, 1, ?)
                ON DUPLICATE KEY UPDATE 
                can_create_exams = 1, can_enter_marks = 1, updated_at = NOW()
            ");
            $stmt->execute([$teacher_id, $subject_id, $_SESSION['user_id']]);
            
            echo json_encode(['status' => 'success', 'message' => '✅ Permission granted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($action === 'revoke_permission') {
        $permission_id = (int)$_POST['permission_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM google_form_exam_permissions WHERE id = ?");
            $stmt->execute([$permission_id]);
            
            echo json_encode(['status' => 'success', 'message' => '✅ Permission revoked successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'create_exam') {
        $exam_title = $_POST['exam_title'] ?? '';
        $subject_id = (int)($_POST['subject_id'] ?? 0);
        $google_form_link = $_POST['google_form_link'] ?? '';
        $exam_date = $_POST['exam_date'] ?? '';
        $exam_time = $_POST['exam_time'] ?? null;
        $total_marks = (int)($_POST['total_marks'] ?? 100);
        $pass_marks = (int)($_POST['pass_marks'] ?? 40);
        $instructions = $_POST['instructions'] ?? '';

        if (empty($exam_title) || $subject_id <= 0 || empty($google_form_link) || empty($exam_date)) {
            echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled']);
            exit;
        }

        try {
            // Get trade_id from subject
            $subject_stmt = $pdo->prepare("SELECT trade_id FROM subjects WHERE id = ?");
            $subject_stmt->execute([$subject_id]);
            $subject_data = $subject_stmt->fetch();
            
            if (!$subject_data) {
                echo json_encode(['status' => 'error', 'message' => 'Subject not found']);
                exit;
            }
            
            $trade_id = $subject_data['trade_id'];

            $stmt = $pdo->prepare("
                INSERT INTO google_form_exams 
                (exam_title, subject_id, trade_id, google_form_link, exam_date, exam_time, 
                 total_marks, pass_marks, instructions, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ");
            $stmt->execute([
                $exam_title, $subject_id, $trade_id, $google_form_link, $exam_date, $exam_time,
                $total_marks, $pass_marks, $instructions, $_SESSION['user_id']
            ]);
            
            echo json_encode(['status' => 'success', 'message' => '✅ Google Form exam created successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'delete_exam') {
        $exam_id = (int)($_POST['exam_id'] ?? 0);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM google_form_exams WHERE id = ?");
            $stmt->execute([$exam_id]);
            
            echo json_encode(['status' => 'success', 'message' => '✅ Exam deleted successfully']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => '❌ Error: ' . $e->getMessage()]);
        }
        exit;
    }
}

// Include header after AJAX handling (so JSON responses work properly)
require_once '../includes/header.php';

// Get all teachers
$teachers = $pdo->query("
    SELECT u.id, u.full_name, u.email, t.trade_name
    FROM users u
    LEFT JOIN trades t ON u.trade_id = t.id
    WHERE u.role_id = (SELECT id FROM roles WHERE name = 'teacher')
    ORDER BY u.full_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get all subjects
$subjects = $pdo->query("
    SELECT s.id, s.subject_name, t.trade_name
    FROM subjects s
    LEFT JOIN trades t ON s.trade_id = t.id
    ORDER BY t.trade_name, s.subject_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get all permissions
$permissions = [];
try {
    $permissions = $pdo->query("
        SELECT gfep.id, gfep.teacher_id, gfep.subject_id, gfep.can_create_exams, 
               gfep.can_enter_marks, gfep.created_at,
               u.full_name as teacher_name, u.email as teacher_email,
               s.subject_name, t.trade_name
        FROM google_form_exam_permissions gfep
        JOIN users u ON gfep.teacher_id = u.id
        JOIN subjects s ON gfep.subject_id = s.id
        JOIN trades t ON s.trade_id = t.id
        ORDER BY u.full_name, s.subject_name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $permissions = [];
    $table_error = true;
}

// Get Google Form Exams Statistics
$stats = [
    'total_exams' => 0,
    'total_students_appeared' => 0,
    'marks_entered' => 0,
    'certificates_generated' => 0
];
try {
    $stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT gfe.id) as total_exams,
            COUNT(DISTINCT gfea.student_id) as total_students_appeared,
            SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered,
            SUM(CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END) as certificates_generated
        FROM google_form_exams gfe
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        LEFT JOIN certificates c ON gfea.student_id = c.student_id 
            AND gfea.subject_id = c.subject_id
            AND gfea.exam_title = c.exam_title
            AND c.exam_source = 'Google Form'
    ")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $table_error = true;
}

// Get exams by teacher
$exams_by_teacher = [];
try {
    $exams_by_teacher = $pdo->query("
        SELECT 
            u.full_name as teacher_name,
            COUNT(gfe.id) as total_exams,
            COUNT(DISTINCT gfea.student_id) as students_appeared,
            SUM(CASE WHEN gfea.marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marks_entered
        FROM google_form_exams gfe
        LEFT JOIN users u ON gfe.created_by = u.id
        LEFT JOIN google_form_exam_attempts gfea ON gfe.id = gfea.exam_id
        GROUP BY gfe.created_by, u.full_name
        ORDER BY total_exams DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $table_error = true;
}

// Get all created exams
$all_exams = [];
try {
    $all_exams = $pdo->query("
        SELECT gfe.*, s.subject_name, t.trade_name, u.full_name as created_by_name
        FROM google_form_exams gfe
        JOIN subjects s ON gfe.subject_id = s.id
        JOIN trades t ON gfe.trade_id = t.id
        JOIN users u ON gfe.created_by = u.id
        ORDER BY gfe.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $table_error = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Form Exams - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
            --info-color: #4299e1;
        }

        body {
            background-color: #f7fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .page-header .icon {
            font-size: 2.5rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 5px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .stat-card.success {
            border-left-color: var(--success-color);
        }

        .stat-card.warning {
            border-left-color: var(--warning-color);
        }

        .stat-card.info {
            border-left-color: var(--info-color);
        }

        .stat-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background-color: rgba(102, 126, 234, 0.1);
        }

        .stat-card.success .stat-icon {
            background-color: rgba(72, 187, 120, 0.1);
            color: var(--success-color);
        }

        .stat-card.warning .stat-icon {
            background-color: rgba(237, 137, 54, 0.1);
            color: var(--warning-color);
        }

        .stat-card.info .stat-icon {
            background-color: rgba(66, 153, 225, 0.1);
            color: var(--info-color);
        }

        .stat-content h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-card.success .stat-content h3 {
            color: var(--success-color);
        }

        .stat-card.warning .stat-content h3 {
            color: var(--warning-color);
        }

        .stat-card.info .stat-content h3 {
            color: var(--info-color);
        }

        .stat-content p {
            margin: 5px 0 0 0;
            color: #718096;
            font-size: 0.95rem;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0;
            padding: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background-color: var(--danger-color);
            border: none;
        }

        .btn-danger:hover {
            background-color: #e53e3e;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .table thead th {
            background-color: #f7fafc;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 15px;
        }

        .table tbody td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background-color: #f7fafc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .badge-success {
            background-color: rgba(72, 187, 120, 0.2);
            color: var(--success-color);
        }

        .tab-content {
            margin-top: 20px;
        }

        .nav-tabs {
            border-bottom: 2px solid #e2e8f0;
        }

        .nav-tabs .nav-link {
            color: #718096;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border-bottom-color: var(--primary-color);
        }

        .alert {
            border: none;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: var(--success-color);
            background-color: rgba(72, 187, 120, 0.1);
            color: #22543d;
        }

        .alert-danger {
            border-left-color: var(--danger-color);
            background-color: rgba(245, 101, 101, 0.1);
            color: #742a2a;
        }

        .alert-info {
            border-left-color: var(--info-color);
            background-color: rgba(66, 153, 225, 0.1);
            color: #2c5282;
        }

        .permission-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #f9fafb;
        }

        .permission-info {
            flex: 1;
        }

        .permission-info h5 {
            margin: 0 0 5px 0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .permission-info p {
            margin: 0;
            font-size: 0.9rem;
            color: #718096;
        }

        .permission-actions {
            display: flex;
            gap: 10px;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: invert(1);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e0;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: #718096;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
            }

            .permission-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .permission-actions {
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php require_once '../includes/sidebar.php'; ?>
    
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <i class="bi bi-google text-warning"></i>
            <h1>Google Form Exams Management</h1>
        </div>

        <?php if (isset($table_error) && $table_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">⚠️ Database Setup Required</h4>
            <p>Google Form tables are not created yet. Please run the setup script to create the necessary database tables.</p>
            <a href="../setup_db.php" class="btn btn-danger btn-sm">Create Tables Now</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Tabs for different sections -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" 
                        data-bs-target="#overview" type="button" role="tab">
                    <i class="bi bi-graph-up"></i> Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" 
                        data-bs-target="#permissions" type="button" role="tab">
                    <i class="bi bi-shield-check"></i> Permissions
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" 
                        data-bs-target="#reports" type="button" role="tab">
                    <i class="bi bi-file-earmark-text"></i> Reports
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Overview Tab -->
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <!-- Statistics -->
                <div class="stats-container mt-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['total_exams'] ?? 0 ?></h3>
                            <p>Total Google Form Exams</p>
                        </div>
                    </div>

                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['total_students_appeared'] ?? 0 ?></h3>
                            <p>Students Appeared</p>
                        </div>
                    </div>

                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['marks_entered'] ?? 0 ?></h3>
                            <p>Marks Entered</p>
                        </div>
                    </div>

                    <div class="stat-card info">
                        <div class="stat-icon">
                            <i class="bi bi-award"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?= $stats['certificates_generated'] ?? 0 ?></h3>
                            <p>Certificates Generated</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle"></i>
                    <strong>System Overview:</strong> This feature allows teachers to create and manage Google Form-based exams. 
                    Teachers can manually enter marks from Google Form responses, and the system automatically generates certificates.
                </div>

                <!-- Create New Exam Button -->
                <div class="mt-4">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createExamModal">
                        <i class="bi bi-plus-circle"></i> Create New Google Form Exam
                    </button>
                </div>

                <!-- Recently Created Exams -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h2>Recently Created Exams</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($all_exams) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Exam Title</th>
                                            <th>Subject</th>
                                            <th>Google Form Link</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_exams as $exam): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($exam['exam_title']) ?></strong></td>
                                                <td><?= htmlspecialchars($exam['subject_name']) ?></td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($exam['google_form_link']) ?>" target="_blank" class="btn btn-link btn-sm">
                                                        <i class="bi bi-box-arrow-up-right"></i> Open Form
                                                    </a>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($exam['exam_date'])) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $exam['status'] === 'published' ? 'success' : ($exam['status'] === 'closed' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($exam['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteExam(<?= $exam['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h3>No Exams Created Yet</h3>
                                <p>Click the "Create New Google Form Exam" button above to get started.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Info -->
                    <strong>System Overview:</strong> This feature allows teachers to create and manage Google Form-based exams. 
                    Teachers can manually enter marks from Google Form responses, and the system automatically generates certificates.
                </div>
            </div>

            <!-- Permissions Tab -->
            <div class="tab-pane fade" id="permissions" role="tabpanel">
                <div class="card mt-4">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h2>Grant Google Form Exam Permissions</h2>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#grantPermissionModal">
                                <i class="bi bi-plus-circle"></i> Grant Permission
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (count($permissions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Teacher Name</th>
                                            <th>Subject</th>
                                            <th>Trade</th>
                                            <th>Email</th>
                                            <th>Permissions</th>
                                            <th>Granted At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($permissions as $perm): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($perm['teacher_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($perm['subject_name']) ?></td>
                                                <td><?= htmlspecialchars($perm['trade_name']) ?></td>
                                                <td><?= htmlspecialchars($perm['teacher_email']) ?></td>
                                                <td>
                                                    <span class="badge badge-success">
                                                        <i class="bi bi-check-circle"></i>
                                                        Create Exams & Enter Marks
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($perm['created_at'])) ?></td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm" onclick="revokePermission(<?= $perm['id'] ?>)">
                                                        <i class="bi bi-trash"></i> Revoke
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-shield-exclamation"></i>
                                <h3>No Permissions Granted</h3>
                                <p>Click "Grant Permission" to assign Google Form exam rights to teachers.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="card mt-4">
                    <div class="card-header">
                        <h2>Exams Created by Teachers</h2>
                    </div>
                    <div class="card-body">
                        <?php if (count($exams_by_teacher) > 0): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Teacher Name</th>
                                            <th>Total Exams Created</th>
                                            <th>Students Appeared</th>
                                            <th>Marks Entered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($exams_by_teacher as $teacher_exam): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($teacher_exam['teacher_name']) ?></strong></td>
                                                <td><?= $teacher_exam['total_exams'] ?></td>
                                                <td><?= $teacher_exam['students_appeared'] ?? 0 ?></td>
                                                <td><?= $teacher_exam['marks_entered'] ?? 0 ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h3>No Exams Created</h3>
                                <p>Teachers haven't created any Google Form exams yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grant Permission Modal -->
    <div class="modal fade" id="grantPermissionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Grant Google Form Exam Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="grantPermissionForm">
                        <div class="mb-3">
                            <label class="form-label">Select Teacher</label>
                            <select class="form-select" id="teacherSelect" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?= $teacher['id'] ?>">
                                        <?= htmlspecialchars($teacher['full_name']) ?> (<?= htmlspecialchars($teacher['trade_name'] ?? 'N/A') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Subject</label>
                            <select class="form-select" id="subjectSelect" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>">
                                        <?= htmlspecialchars($subject['subject_name']) ?> (<?= htmlspecialchars($subject['trade_name']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            This permission will allow the teacher to:
                            <ul class="mb-0 mt-2" style="font-size: 0.9rem;">
                                <li>Create Google Form exams for this subject</li>
                                <li>View exam attempts</li>
                                <li>Manually enter student marks</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="grantPermission()">Grant Permission</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Google Form Exam Modal -->
    <div class="modal fade" id="createExamModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Google Form Exam</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="createExamForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Exam Title *</label>
                                <input type="text" class="form-control" id="examTitle" placeholder="e.g., Midterm Exam" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject *</label>
                                <select class="form-select" id="examSubject" required>
                                    <option value="">-- Select Subject --</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?= $subject['id'] ?>">
                                            <?= htmlspecialchars($subject['subject_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Google Form Link *</label>
                            <input type="url" class="form-control" id="googleFormLink" 
                                   placeholder="https://forms.google.com/u/0/forms/d/..." 
                                   required>
                            <small class="text-muted">Paste your Google Form sharing link here</small>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Exam Date *</label>
                                <input type="date" class="form-control" id="examDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Exam Time</label>
                                <input type="time" class="form-control" id="examTime">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Total Marks *</label>
                                <input type="number" class="form-control" id="totalMarks" value="100" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Passing Marks *</label>
                                <input type="number" class="form-control" id="passingMarks" value="40" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Instructions</label>
                            <textarea class="form-control" id="examInstructions" rows="3" placeholder="Enter exam instructions (optional)"></textarea>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Note:</strong> After creating the exam, you can publish it for students to take.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createExam()">Create Exam</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function grantPermission() {
            const teacherId = document.getElementById('teacherSelect').value;
            const subjectId = document.getElementById('subjectSelect').value;

            if (!teacherId || !subjectId) {
                alert('Please select both teacher and subject');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'grant_permission');
            formData.append('teacher_id', teacherId);
            formData.append('subject_id', subjectId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    location.reload();
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }

        function revokePermission(permissionId) {
            if (!confirm('Are you sure you want to revoke this permission?')) return;

            const formData = new FormData();
            formData.append('action', 'revoke_permission');
            formData.append('permission_id', permissionId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    location.reload();
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }

        function createExam() {
            const examTitle = document.getElementById('examTitle').value;
            const subjectId = document.getElementById('examSubject').value;
            const googleFormLink = document.getElementById('googleFormLink').value;
            const examDate = document.getElementById('examDate').value;
            const examTime = document.getElementById('examTime').value || null;
            const totalMarks = document.getElementById('totalMarks').value;
            const passingMarks = document.getElementById('passingMarks').value;
            const examInstructions = document.getElementById('examInstructions').value;

            if (!examTitle || !subjectId || !googleFormLink || !examDate) {
                alert('Please fill all required fields (marked with *)');
                return;
            }

            if (!googleFormLink.includes('forms.google.com')) {
                alert('Please enter a valid Google Form link');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'create_exam');
            formData.append('exam_title', examTitle);
            formData.append('subject_id', subjectId);
            formData.append('google_form_link', googleFormLink);
            formData.append('exam_date', examDate);
            formData.append('exam_time', examTime);
            formData.append('total_marks', totalMarks);
            formData.append('pass_marks', passingMarks);
            formData.append('instructions', examInstructions);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message);
                if (data.status === 'success') {
                    document.getElementById('createExamForm').reset();
                    bootstrap.Modal.getInstance(document.getElementById('createExamModal')).hide();
                    setTimeout(() => location.reload(), 1000);
                }
            })
            .catch(err => alert('Error: ' + err.message));
        }

        function deleteExam(examId) {
            if (confirm('Are you sure you want to delete this exam?')) {
                const formData = new FormData();
                formData.append('action', 'delete_exam');
                formData.append('exam_id', examId);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        location.reload();
                    }
                })
                .catch(err => alert('Error: ' + err.message));
            }
        }
    </script>
</body>
</html>
