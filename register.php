<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectDashboard($_SESSION['role_name']);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Required
        $full_name  = sanitizeInput($_POST['full_name'] ?? '');
        $email      = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $phone      = sanitizeInput($_POST['phone'] ?? '');
        $trade_id   = (int)($_POST['trade_id'] ?? 0);
        $password   = $_POST['password'] ?? '';
        $confirm_pw = $_POST['confirm_password'] ?? '';

        // Optional
        $gender         = sanitizeInput($_POST['gender'] ?? '');
        $date_of_birth  = sanitizeInput($_POST['date_of_birth'] ?? '');
        $address        = sanitizeInput($_POST['address'] ?? '');
        $batch          = sanitizeInput($_POST['batch'] ?? '');
        $institute_name = sanitizeInput($_POST['institute_name'] ?? '');
        $enrollment_no  = sanitizeInput($_POST['enrollment_no'] ?? '');

        if (empty($full_name) || empty($email) || empty($phone) || empty($password) || empty($trade_id)) {
            $errors[] = "All required fields must be filled out.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        } elseif ($password !== $confirm_pw) {
            $errors[] = "Passwords do not match.";
        } else {
            // Check if email or phone already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR phone = :phone");
            $stmt->execute(['email' => $email, 'phone' => $phone]);
            if ($stmt->fetch()) {
                $errors[] = "Email or Phone is already registered.";
            } else {
                // Get Student Role ID
                $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'student' LIMIT 1");
                $roleStmt->execute();
                $role = $roleStmt->fetch();
                
                if (!$role) {
                    $errors[] = "Critical Error: Student role not found in database.";
                } else {
                    // Hash password and insert
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insertStmt = $pdo->prepare("INSERT INTO users 
                        (role_id, full_name, email, phone, password, trade_id, gender, date_of_birth, address, batch, institute_name, enrollment_no) 
                        VALUES 
                        (:role_id, :full_name, :email, :phone, :password, :trade_id, :gender, :date_of_birth, :address, :batch, :institute_name, :enrollment_no)");
                    
                    $success = $insertStmt->execute([
                        'role_id'        => $role['id'],
                        'full_name'      => $full_name,
                        'email'          => $email,
                        'phone'          => $phone,
                        'password'       => $hashed_password,
                        'trade_id'       => $trade_id,
                        'gender'         => $gender ?: null,
                        'date_of_birth'  => $date_of_birth ?: null,
                        'address'        => $address ?: null,
                        'batch'          => $batch ?: null,
                        'institute_name' => $institute_name ?: null,
                        'enrollment_no'  => $enrollment_no ?: null
                    ]);

                    if ($success) {
                        $_SESSION['success_message'] = "Registration successful! You can now log in.";
                        redirect('/login.php');
                    } else {
                        $errors[] = "Registration failed. Please try again.";
                    }
                }
            }
        }
    }
}

// Fetch trades for the dropdown
$tradesStmt = $pdo->query("SELECT id, trade_name FROM trades ORDER BY trade_name ASC");
$trades = $tradesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/register.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
 <body style="font-family: Arial, sans-serif; background-color: #F3F4F6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px;"> 
    <body>
<!-- <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 600px;"> -->
       <div class="registration-container">
    <!-- <h2 style="color: #0056D2; margin-top: 0;">Student Registration</h2> -->
         <h2 class="registration-title">
    Student Registration
</h2>

<p class="registration-subtitle">
    Create your account and start your learning journey
</p>
        <?php if (!empty($errors)): ?>
<div class="error-box">
    <?= implode('<br>', $errors) ?>
</div>
<?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <!-- <h4 style="margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Required Information</h4> -->
             <h4 class="section-heading">Required Information</h4>
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
            
            <div class="grid-2">
                <div class="form-group"><label>Email Address *</label><input type="email" name="email" required></div>
                <div class="form-group"><label>Phone Number *</label><input type="text" name="phone" required></div>
            </div>

            <div class="form-group">
                <label>Trade / Course *</label>
                <select name="trade_id" required>
                    <option value="">-- Select a Trade --</option>
                    
                    
                    <?php foreach ($trades as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trade_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="grid-2">
                <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
                <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
            </div>

            <h4 style="margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Optional Information</h4>
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">-- Select --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                        <option value="Prefer not to say">Prefer not to say</option>
                    </select>
                </div>
                <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
            </div>

            <div class="form-group"><label>Address</label><textarea name="address" rows="2"></textarea></div>

            <div class="grid-2">
                <div class="form-group"><label>Batch</label><input type="text" name="batch"></div>
                <div class="form-group"><label>Institute Name</label><input type="text" name="institute_name"></div>
            </div>
            
            <div class="form-group"><label>Enrollment Number</label><input type="text" name="enrollment_no"></div>

            <!-- <button type="submit" style="width: 100%; padding: 12px; background: #0056D2; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 10px;">Register Account</button> -->
             <button type="submit" class="btn-register">Register Account</button>
        </form>
        <!-- <p style="text-align: center; margin-top: 15px; font-size: 14px;">Already have an account? <a href="login.php" style="color: #0056D2;">Log In</a></p> -->
<p class="login-link">Already have an account? <a href="login.php" style="color: #0056D2;">Log In</a></p>
    </div>
</body>
</html>