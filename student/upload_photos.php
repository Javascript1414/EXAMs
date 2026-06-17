<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current profile
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email,
           up.profile_photo_path, up.cover_photo_path
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('/student/index.php');
}

// Handle form submission - PHOTOS ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $profile_photo_path = $profile['profile_photo_path'];
        $cover_photo_path = $profile['cover_photo_path'];

        // Profile Photo Upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $upload_result = uploadImageFile($_FILES['profile_photo'], $user_id, 'profile');
            if (!$upload_result['success']) {
                throw new Exception($upload_result['error']);
            }
            deleteOldPhoto($profile['profile_photo_path']);
            $profile_photo_path = $upload_result['path'];
        }



        // Save to database - photos only
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile_exists = $stmt->fetch();

        if ($profile_exists) {
            $stmt = $pdo->prepare("
                UPDATE user_profiles SET
                    profile_photo_path = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $profile_photo_path,
                $user_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles 
                (user_id, profile_photo_path)
                VALUES (?, ?)
            ");
            $stmt->execute([
                $user_id,
                $profile_photo_path
            ]);
        }

        $message = '✅ Photos updated successfully!';
        
        // Refresh
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.email,
                   up.profile_photo_path, up.cover_photo_path
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

    } catch (Exception $e) {
        $error = '❌ Error: ' . $e->getMessage();
        error_log('Photo upload error for user ' . $user_id . ': ' . $e->getMessage());
    }
}

include __DIR__ . '/../includes/header.php';
?>

<style>
    .photo-upload-container {
        max-width: 600px;
        margin: 30px auto;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 30px;
    }

    .photo-upload-container h1 {
        color: #333;
        margin-top: 0;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-weight: 500;
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

    .photo-section {
        margin-bottom: 30px;
        padding-bottom: 30px;
        border-bottom: 1px solid #eee;
    }

    .photo-section h3 {
        color: #667eea;
        margin-top: 0;
        margin-bottom: 15px;
    }

    .photo-section:last-child {
        border-bottom: none;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #333;
        font-weight: 500;
        font-size: 14px;
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
    }

    .file-input-wrapper input[type="file"] {
        display: none;
    }

    .file-input-label {
        display: block;
        background: #f0f0f0;
        border: 2px dashed #ddd;
        padding: 30px;
        border-radius: 6px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 14px;
        color: #666;
    }

    .file-input-label:hover {
        border-color: #667eea;
        background: #f9f9ff;
        color: #333;
    }

    .photo-preview {
        margin-top: 15px;
        max-width: 300px;
    }

    .photo-preview img {
        width: 100%;
        border-radius: 6px;
        border: 1px solid #ddd;
    }

    .photo-preview-label {
        font-size: 12px;
        color: #666;
        margin-top: 10px;
    }

    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #764ba2;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .info-box {
        background: #f0f9ff;
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #333;
    }

    .info-box strong {
        color: #667eea;
    }
</style>

<div class="photo-upload-container">
    <h1>📸 Upload Photos</h1>

    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="info-box">
        <strong>📝 Simple Upload:</strong> Upload your profile and cover photos below. After uploading, you can edit other profile details.
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <!-- Profile Photo -->
        <div class="photo-section">
            <h3>👤 Profile Photo</h3>
            <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Minimum: 100×100 pixels | Recommended: 500×500 pixels or larger<br>Max: 5MB | Formats: JPG, PNG, GIF</p>
            
            <div class="form-group">
                <div class="file-input-wrapper">
                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*">
                    <label for="profile_photo" class="file-input-label">
                        👆 Click to upload or drag & drop<br>
                        <small style="font-size: 12px;">JPG, PNG, GIF - Max 5MB</small>
                    </label>
                </div>
                <?php if (!empty($profile['profile_photo_path'])): ?>
                    <div class="photo-preview">
                        <img src="<?php echo htmlspecialchars($profile['profile_photo_path']); ?>" alt="Profile Photo">
                        <div class="photo-preview-label">✓ Current Photo</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


    <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 6px;">
        <h3 style="margin-top: 0; color: #333;">💡 Tips</h3>
        <ul style="font-size: 14px; color: #666; margin: 0;">
            <li>Profile photo will be shown as a circular 160px image</li>
            <li>Cover photo is displayed as a banner at the top of your profile</li>
            <li>Photos are automatically optimized and secure</li>
            <li>Old photos are automatically removed when you upload new ones</li>
            <li>After uploading, go to "Edit Full Profile" to add more details</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
