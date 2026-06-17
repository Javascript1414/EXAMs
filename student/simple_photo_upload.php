<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';
$success = false;

// Get current photos
$stmt = $pdo->prepare("
    SELECT profile_photo_path, cover_photo_path 
    FROM user_profiles 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$current_photos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_photos) {
    $current_photos = ['profile_photo_path' => null, 'cover_photo_path' => null];
}

// PROFILE PHOTO UPLOAD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '❌ Security token expired. Please try again.';
    } elseif (empty($_FILES['upload_profile']['name'])) {
        $error = '❌ Please select a profile photo file.';
    } else {
        try {
            $file = $_FILES['upload_profile'];
            
            // Validate file
            $validation = validateImageFile($file, 'profile');
            if (!$validation['valid']) {
                throw new Exception($validation['error']);
            }
            
            // Upload file
            $result = uploadImageFile($file, $user_id, 'profile');
            if (!$result['success']) {
                throw new Exception($result['error']);
            }
            
            // Delete old photo
            if (!empty($current_photos['profile_photo_path'])) {
                @unlink($current_photos['profile_photo_path']);
            }
            
            // Update database
            $check = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
            $check->execute([$user_id]);
            
            if ($check->fetch()) {
                $upd = $pdo->prepare("UPDATE user_profiles SET profile_photo_path = ? WHERE user_id = ?");
                $upd->execute([$result['path'], $user_id]);
            } else {
                $ins = $pdo->prepare("INSERT INTO user_profiles (user_id, profile_photo_path) VALUES (?, ?)");
                $ins->execute([$user_id, $result['path']]);
            }
            
            $message = '✅ Profile photo uploaded successfully!';
            $success = true;
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT profile_photo_path, cover_photo_path FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $current_photos = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $error = '❌ Upload failed: ' . $e->getMessage();
            error_log('Profile photo upload error: ' . $e->getMessage());
        }
    }
}



include __DIR__ . '/../includes/header.php';
?>

<style>
    body { background: #f5f5f5; }
    
    .upload-container {
        max-width: 700px;
        margin: 40px auto;
        padding: 0 15px;
    }
    
    .upload-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px;
        margin-bottom: 20px;
    }
    
    .upload-card h2 {
        color: #667eea;
        margin-top: 0;
        margin-bottom: 5px;
        font-size: 20px;
    }
    
    .upload-card .subtitle {
        color: #888;
        font-size: 13px;
        margin-bottom: 20px;
    }
    
    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
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
    
    .upload-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .file-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .file-input-group input[type="file"] {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
    }
    
    .file-input-group button {
        padding: 10px 20px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        white-space: nowrap;
    }
    
    .file-input-group button:hover {
        background: #764ba2;
    }
    
    .file-input-group button:active {
        transform: scale(0.98);
    }
    
    .photo-preview {
        margin-top: 15px;
        text-align: center;
    }
    
    .photo-preview img {
        max-width: 200px;
        max-height: 200px;
        border-radius: 6px;
        border: 2px solid #ddd;
    }
    
    .photo-preview p {
        margin: 10px 0 0 0;
        font-size: 12px;
        color: #666;
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #667eea;
        padding: 12px 15px;
        border-radius: 4px;
        font-size: 12px;
        color: #333;
        margin-top: 15px;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 13px;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-primary:hover {
        background: #764ba2;
    }
    
    .btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #5a6268;
    }
</style>

<div class="upload-container">
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- PROFILE PHOTO UPLOAD -->
    <div class="upload-card">
        <h2>👤 Profile Photo</h2>
        <div class="subtitle">Upload your profile picture (100×100 min, 5MB max)</div>
        
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="file-input-group">
                <input type="file" name="upload_profile" accept="image/*" required>
                <button type="submit">Upload</button>
            </div>
            
            <div class="info-box">
                ✓ Formats: JPG, PNG, GIF | Max 5MB | Min 100×100px
            </div>
        </form>
        
        <?php if (!empty($current_photos['profile_photo_path'])): ?>
            <div class="photo-preview">
                <img src="<?php echo htmlspecialchars($current_photos['profile_photo_path']); ?>" alt="Profile">
                <p>Current Profile Photo</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- ACTION BUTTONS -->
    <div class="action-buttons">
        <a href="<?php echo BASE_URL; ?>/student/profile.php" class="btn btn-primary">View Profile</a>
        <a href="<?php echo BASE_URL; ?>/student/edit_profile.php" class="btn btn-secondary">Edit Profile</a>
        <a href="<?php echo BASE_URL; ?>/student/index.php" class="btn btn-secondary">Go Back</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
