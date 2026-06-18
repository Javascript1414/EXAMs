<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!hasRole('superadmin') && !hasRole('admin')) {
    die("Access Denied");
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'set_trade_code') {
        $trade_id = (int)$_POST['trade_id'];
        $trade_code = strtoupper(sanitizeInput($_POST['trade_code']));
        
        $pdo->prepare("UPDATE trades SET trade_code = ? WHERE id = ?")->execute([$trade_code, $trade_id]);
        $_SESSION['success_message'] = "Trade code updated!";
    }
    
    elseif ($action === 'set_enrollment') {
        $user_id = (int)$_POST['user_id'];
        $enrollment_no = sanitizeInput($_POST['enrollment_no']);
        
        $pdo->prepare("UPDATE users SET enrollment_no = ? WHERE id = ?")->execute([$enrollment_no, $user_id]);
        $_SESSION['success_message'] = "Enrollment number updated!";
    }
    
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Get trades without codes
$tradesStmt = $pdo->query("SELECT id, trade_name, trade_code FROM trades ORDER BY trade_name");
$trades = $tradesStmt->fetchAll();

// Get students without enrollment numbers
$studentsStmt = $pdo->query("
    SELECT u.id, u.full_name, u.enrollment_no, t.trade_name 
    FROM users u 
    JOIN trades t ON u.trade_id = t.id 
    WHERE u.role_id = (SELECT id FROM roles WHERE name = 'student') 
    AND (u.enrollment_no IS NULL OR u.enrollment_no = '')
    ORDER BY u.full_name
    LIMIT 50
");
$students = $studentsStmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Configuration - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px 0; }
        .container-fluid { padding: 20px; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { font-weight: 600; }
        code { background-color: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container-fluid">

<div class="container-fluid px-4">
    <h2 class="mb-4">🎓 Certificate ID Configuration</h2>
    
    <?php displayFlashMessages(); ?>
    
    <div class="row">
        <!-- Trade Codes Configuration -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Step 1: Configure Trade Codes</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Set course codes like CITS, COPA, DDVT, ACIT</p>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Trade Name</th>
                                    <th>Code</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trades as $trade): ?>
                                <tr>
                                    <td><?= htmlspecialchars($trade['trade_name']) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="set_trade_code">
                                            <input type="hidden" name="trade_id" value="<?= $trade['id'] ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="trade_code" class="form-control" 
                                                       value="<?= htmlspecialchars($trade['trade_code'] ?? '') ?>" 
                                                       placeholder="e.g., CITS" maxlength="20" required>
                                                <button type="submit" class="btn btn-primary btn-sm">Set</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enrollment Numbers Configuration -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Step 2: Set Enrollment Numbers</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Enter student enrollment/registration numbers</p>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Trade</th>
                                    <th>Enrollment No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['full_name']) ?></td>
                                    <td><?= htmlspecialchars($student['trade_name']) ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="set_enrollment">
                                            <input type="hidden" name="user_id" value="<?= $student['id'] ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="enrollment_no" class="form-control" 
                                                       placeholder="e.g., 1414" maxlength="50" required>
                                                <button type="submit" class="btn btn-success btn-sm">Set</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Certificate ID Format Info -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">📋 Certificate ID Format</h5>
        </div>
        <div class="card-body">
            <p><strong>Format:</strong> <code style="font-size: 18px; background: #f0f0f0; padding: 10px;">CITS/24-25/Y/1414/A1</code></p>
            <ul>
                <li><strong>CITS</strong> = Trade Code (Configure above)</li>
                <li><strong>24-25</strong> = Academic Year (Aug-July system)</li>
                <li><strong>Y</strong> = Year Marker (Static)</li>
                <li><strong>1414</strong> = Student Enrollment No. (Set above)</li>
                <li><strong>A1</strong> = Exam Sequence (Auto: A1 for 1st exam, A2 for 2nd, etc.)</li>
            </ul>
            
            <h5 class="mt-4">Examples:</h5>
            <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
CITS/24-25/Y/1414/A1  ← First exam by student 1414 in 2024-25
CITS/24-25/Y/1414/A2  ← Second exam by same student
COPA/24-25/Y/2020/A1  ← First exam by student 2020 in COPA trade
            </pre>
        </div>
    </div>
    
    <!-- Academic Year Info -->
    <div class="card mt-4">
        <div class="card-header bg-warning">
            <h5 class="mb-0">📅 Academic Year (Aug-July System)</h5>
        </div>
        <div class="card-body">
            <p>The academic year is automatically calculated from the exam date:</p>
            <ul>
                <li><strong>August 2024 - July 2025</strong> = <code>24-25</code></li>
                <li><strong>August 2025 - July 2026</strong> = <code>25-26</code></li>
                <li><strong>August 2023 - July 2024</strong> = <code>23-24</code></li>
            </ul>
            <p class="text-muted">No manual configuration needed - automatically calculated!</p>
        </div>
    </div>
    
    <!-- Setup Steps -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">✅ Setup Checklist</h5>
        </div>
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="step1">
                <label class="form-check-label" for="step1">
                    <strong>Step 1:</strong> Set all Trade Codes (CITS, COPA, etc.)
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="step2">
                <label class="form-check-label" for="step2">
                    <strong>Step 2:</strong> Set enrollment numbers for all students
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="step3">
                <label class="form-check-label" for="step3">
                    <strong>Step 3:</strong> Release/Generate certificates
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="step4">
                <label class="form-check-label" for="step4">
                    <strong>Step 4:</strong> Verify certificates have correct ID format
                </label>
            </div>
        </div>
    </div>
    
    <p class="mt-4">
        <a href="<?= BASE_URL ?>/admin/release_certificates.php" class="btn btn-primary">
            ➜ Go to Release Certificates
        </a>
    </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
