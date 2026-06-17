<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!isset($_FILES['photo'])) {
    echo json_encode(['success' => false, 'error' => 'No file provided']);
    exit;
}

try {
    $file = $_FILES['photo'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large',
            UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file selected',
            UPLOAD_ERR_NO_TMP_DIR => 'Server error',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write file',
            UPLOAD_ERR_EXTENSION => 'Extension blocked'
        ];
        throw new Exception($errors[$file['error']] ?? 'Upload failed');
    }
    
    // Validate image
    $validation = validateImageFile($file, 'profile');
    if (!$validation['valid']) {
        throw new Exception($validation['error']);
    }
    
    // Upload file
    $result = uploadImageFile($file, $user_id, 'profile');
    if (!$result['success']) {
        throw new Exception($result['error']);
    }
    
    // Get old photo and delete
    $stmt = $pdo->prepare("SELECT profile_photo_path FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $old_profile = $stmt->fetch();
    
    if ($old_profile && !empty($old_profile['profile_photo_path'])) {
        @unlink($old_profile['profile_photo_path']);
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
    
    echo json_encode([
        'success' => true,
        'path' => $result['path'],
        'message' => 'Photo uploaded successfully!'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    error_log('Photo upload error: ' . $e->getMessage());
}
?>
