<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/student_settings_functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !hasRole('student')) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '❌ Security token expired. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = '❌ All fields are required.';
        } elseif (strlen($new_password) < 8) {
            $error = '❌ New password must be at least 8 characters long.';
        } elseif ($new_password !== $confirm_password) {
            $error = '❌ Passwords do not match.';
        } elseif ($current_password === $new_password) {
            $error = '❌ New password must be different from current password.';
        } else {
            // Get current user
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                $error = '❌ Current password is incorrect.';
            } else {
                // Update password
                $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 10]);
                try {
                    $update_stmt = $pdo->prepare("
                        UPDATE users 
                        SET password = ?, password_last_changed = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    
                    if ($update_stmt->execute([$new_password_hash, $user_id])) {
                        logStudentActivity($user_id, 'password_changed', 'Password changed successfully');
                        $message = '✅ Password changed successfully! Please log in again with your new password.';
                        
                        // Redirect after 3 seconds
                        echo '<script>
                            setTimeout(function() {
                                window.location.href = "' . BASE_URL . '/logout_new.php";
                            }, 3000);
                        </script>';
                    } else {
                        $error = '❌ Failed to update password. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = '❌ Database error. Please try again.';
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    * {
        box-sizing: border-box;
    }

    .change-password-container {
        max-width: 600px;
        margin: 40px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }

    .change-password-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .change-password-header h1 {
        margin: 0;
        font-size: 1.8em;
    }

    .change-password-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .change-password-content {
        padding: 40px;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.3s;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group input[type="password"] {
        font-family: 'Courier New', monospace;
        letter-spacing: 2px;
    }

    .password-strength {
        margin-top: 8px;
        display: flex;
        gap: 5px;
        height: 4px;
    }

    .strength-bar {
        flex: 1;
        background: #ddd;
        border-radius: 2px;
    }

    .strength-bar.weak {
        background: #dc3545;
    }

    .strength-bar.medium {
        background: #ffc107;
    }

    .strength-bar.strong {
        background: #28a745;
    }

    .password-requirements {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .password-requirements h4 {
        margin: 0 0 12px 0;
        font-size: 14px;
        color: #333;
    }

    .requirement {
        display: flex;
        align-items: center;
        font-size: 13px;
        color: #666;
        margin-bottom: 8px;
        padding: 4px 0;
    }

    .requirement:last-child {
        margin-bottom: 0;
    }

    .requirement-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        margin-right: 8px;
        border-radius: 50%;
        background: #e9ecef;
        color: #999;
        font-size: 12px;
    }

    .requirement.met .requirement-icon {
        background: #d4edda;
        color: #28a745;
    }

    .requirement.met .requirement-icon::after {
        content: '✓';
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
        flex: 1;
    }

    .btn-primary:hover {
        background: #764ba2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        flex: 1;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .show-password-toggle {
        position: relative;
        margin-top: -20px;
        margin-bottom: 20px;
    }

    .show-password-toggle input[type="checkbox"] {
        width: auto;
        margin-right: 8px;
        cursor: pointer;
    }

    .show-password-toggle label {
        margin: 0;
        display: inline;
        font-weight: 400;
        cursor: pointer;
        user-select: none;
    }

    @media (max-width: 768px) {
        .change-password-container {
            margin: 20px;
        }

        .change-password-content {
            padding: 20px;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<div class="change-password-container">
    <!-- Header -->
    <div class="change-password-header">
        <h1>🔄 Change Password</h1>
        <p>Update your account password to keep it secure</p>
    </div>

    <!-- Content -->
    <div class="change-password-content">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <!-- Password Requirements -->
            <div class="password-requirements">
                <h4>🔐 Password Requirements</h4>
                <div class="requirement" id="req-length">
                    <span class="requirement-icon"></span>
                    <span>At least 8 characters</span>
                </div>
                <div class="requirement" id="req-uppercase">
                    <span class="requirement-icon"></span>
                    <span>At least one uppercase letter (A-Z)</span>
                </div>
                <div class="requirement" id="req-lowercase">
                    <span class="requirement-icon"></span>
                    <span>At least one lowercase letter (a-z)</span>
                </div>
                <div class="requirement" id="req-number">
                    <span class="requirement-icon"></span>
                    <span>At least one number (0-9)</span>
                </div>
                <div class="requirement" id="req-special">
                    <span class="requirement-icon"></span>
                    <span>At least one special character (!@#$%^&*)</span>
                </div>
            </div>

            <!-- Change Password Form -->
            <form method="POST" id="change-password-form">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

                <!-- Current Password -->
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        placeholder="Enter your current password"
                        required
                    >
                </div>

                <!-- New Password -->
                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        placeholder="Enter your new password"
                        required
                        oninput="checkPasswordStrength(this.value)"
                    >
                    <div class="password-strength" id="password-strength" style="display:none;">
                        <div class="strength-bar"></div>
                        <div class="strength-bar"></div>
                        <div class="strength-bar"></div>
                    </div>
                </div>

                <!-- Show Password Toggle -->
                <div class="show-password-toggle">
                    <input type="checkbox" id="show-password" onchange="togglePasswordVisibility()">
                    <label for="show-password">Show passwords</label>
                </div>

                <!-- Confirm New Password -->
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your new password"
                        required
                    >
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        🔄 Change Password
                    </button>
                    <a href="<?= BASE_URL ?>/student/settings.php" class="btn btn-secondary">
                        ← Back to Settings
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function checkPasswordStrength(password) {
    // Check requirements
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasLowercase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
    
    // Update requirement indicators
    updateRequirement('req-length', hasLength);
    updateRequirement('req-uppercase', hasUppercase);
    updateRequirement('req-lowercase', hasLowercase);
    updateRequirement('req-number', hasNumber);
    updateRequirement('req-special', hasSpecial);
    
    // Calculate strength
    const requirements_met = [hasLength, hasUppercase, hasLowercase, hasNumber, hasSpecial].filter(Boolean).length;
    
    if (password.length === 0) {
        document.getElementById('password-strength').style.display = 'none';
        return;
    }
    
    // Show strength meter
    const strengthBars = document.querySelectorAll('.password-strength .strength-bar');
    strengthBars.forEach((bar, index) => {
        bar.className = 'strength-bar';
        if (index < requirements_met) {
            if (requirements_met <= 2) {
                bar.classList.add('weak');
            } else if (requirements_met <= 3) {
                bar.classList.add('medium');
            } else {
                bar.classList.add('strong');
            }
        }
    });
    
    document.getElementById('password-strength').style.display = 'flex';
    
    // Enable/disable submit button
    const new_password = document.getElementById('new_password').value;
    const confirm_password = document.getElementById('confirm_password').value;
    const submit_btn = document.getElementById('submit-btn');
    
    const all_requirements_met = hasLength && hasUppercase && hasLowercase && hasNumber && hasSpecial;
    const passwords_match = new_password === confirm_password && new_password.length > 0;
    
    submit_btn.disabled = !(all_requirements_met && passwords_match);
}

function updateRequirement(id, met) {
    const element = document.getElementById(id);
    if (met) {
        element.classList.add('met');
    } else {
        element.classList.remove('met');
    }
}

function togglePasswordVisibility() {
    const current = document.getElementById('current_password');
    const new_pass = document.getElementById('new_password');
    const confirm = document.getElementById('confirm_password');
    const show = document.getElementById('show-password').checked;
    
    current.type = show ? 'text' : 'password';
    new_pass.type = show ? 'text' : 'password';
    confirm.type = show ? 'text' : 'password';
}

// Validate passwords match in real-time
document.getElementById('confirm_password').addEventListener('input', function() {
    checkPasswordStrength(document.getElementById('new_password').value);
});

// Prevent form submission if passwords don't match
document.getElementById('change-password-form').addEventListener('submit', function(e) {
    const new_password = document.getElementById('new_password').value;
    const confirm_password = document.getElementById('confirm_password').value;
    
    if (new_password !== confirm_password) {
        e.preventDefault();
        alert('❌ Passwords do not match!');
        return false;
    }
});

// Initialize on page load
window.addEventListener('load', function() {
    const submit_btn = document.getElementById('submit-btn');
    submit_btn.disabled = true;
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
