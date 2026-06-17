<?php
require_once __DIR__ . '/../config.php';

/**
 * Sanitizes user input to prevent XSS.
 */
function sanitizeInput(string $data): string {
    return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to a specified path within the application.
 */
function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Generates a CSRF token for form submission.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token from a form submission.
 */
function verifyCsrfToken(?string $token): bool {
    if (!$token || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Checks if a user is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Checks if the logged-in user has a specific role.
 */
function hasRole(string $role_name): bool {
    return isset($_SESSION['role_name']) && $_SESSION['role_name'] === $role_name;
}

/**
 * Forces authentication. Redirects to login if not logged in.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_message'] = "Please log in to access this page.";
        redirect('/login.php');
    }
}

/**
 * Protects a route by requiring a specific role.
 */
function requireRole(string $role_name): void {
    requireLogin();
    if (!hasRole($role_name)) {
        redirectDashboard($_SESSION['role_name'] ?? 'student');
    }
}

/**
 * Routes the user to their correct role-based dashboard.
 */
function redirectDashboard(string $role_name): void {
    match ($role_name) {
        'superadmin', 'admin' => redirect('/admin/index.php'),
        'moderator'           => redirect('/moderator/index.php'),
        'student'             => redirect('/student/index.php'),
        default               => redirect('/login.php')
    };
}

/**
 * Displays flash messages (success or error).
 */
function displayFlashMessages(): void {
    if (isset($_SESSION['error_message'])) {
        echo '<div style="color: #EF4444; background: #FEE2E2; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
        echo sanitizeInput($_SESSION['error_message']);
        echo '</div>';
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        echo '<div style="color: #10B981; background: #D1FAE5; padding: 10px; border-radius: 4px; margin-bottom: 15px;">';
        echo sanitizeInput($_SESSION['success_message']);
        echo '</div>';
        unset($_SESSION['success_message']);
    }
}

/**
 * Returns a relative time string (e.g., "2 hours ago").
 */
function timeElapsedString(string $datetime, bool $full = false): string {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = ['y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
        else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Gets user profile with all details
 */
function getUserProfile($pdo, int $user_id) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.phone, u.status, u.created_at,
               u.approval_status, u.approved_at,
               up.bio, up.profile_photo_path, up.cover_photo_path,
               up.phone_verified, up.aadhaar_number, up.father_name,
               up.mother_name, up.emergency_contact, up.emergency_contact_name,
               up.social_media_links, up.skills, up.certifications,
               up.about_education, up.about_experience, up.website, up.location
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Gets user profile statistics (exams, bookmarks, etc.)
 */
function getUserProfileStats($pdo, int $user_id) {
    // Exam Statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_exams,
               SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as completed_exams,
               AVG(CASE WHEN status = 'submitted' THEN score ELSE NULL END) as avg_score
        FROM exam_attempts
        WHERE student_id = ?
    ");
    $stmt->execute([$user_id]);
    $exam_stats = $stmt->fetch();

    // Material Statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_bookmarks
        FROM material_bookmarks
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $bookmark_stats = $stmt->fetch();

    // Certificate Statistics
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_certificates
        FROM exam_attempts
        WHERE student_id = ? AND status = 'submitted' AND score >= 40
    ");
    $stmt->execute([$user_id]);
    $cert_stats = $stmt->fetch();

    return [
        'exams' => $exam_stats,
        'bookmarks' => $bookmark_stats,
        'certificates' => $cert_stats
    ];
}

/**
 * Creates or updates user profile
 */
function updateUserProfile($pdo, int $user_id, array $data): bool {
    try {
        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $pdo->prepare("
                UPDATE user_profiles SET
                    bio = ?,
                    profile_photo_path = ?,
                    cover_photo_path = ?,
                    phone_verified = ?,
                    aadhaar_number = ?,
                    father_name = ?,
                    mother_name = ?,
                    emergency_contact = ?,
                    emergency_contact_name = ?,
                    social_media_links = ?,
                    skills = ?,
                    certifications = ?,
                    about_education = ?,
                    about_experience = ?,
                    website = ?,
                    location = ?
                WHERE user_id = ?
            ");
            $stmt->execute([
                $data['bio'] ?? null,
                $data['profile_photo_path'] ?? null,
                $data['cover_photo_path'] ?? null,
                $data['phone_verified'] ?? 0,
                $data['aadhaar_number'] ?? null,
                $data['father_name'] ?? null,
                $data['mother_name'] ?? null,
                $data['emergency_contact'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['social_media_links'] ?? null,
                $data['skills'] ?? null,
                $data['certifications'] ?? null,
                $data['about_education'] ?? null,
                $data['about_experience'] ?? null,
                $data['website'] ?? null,
                $data['location'] ?? null,
                $user_id
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO user_profiles 
                (user_id, bio, profile_photo_path, cover_photo_path, phone_verified,
                 aadhaar_number, father_name, mother_name, emergency_contact,
                 emergency_contact_name, social_media_links, skills, certifications,
                 about_education, about_experience, website, location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $data['bio'] ?? null,
                $data['profile_photo_path'] ?? null,
                $data['cover_photo_path'] ?? null,
                $data['phone_verified'] ?? 0,
                $data['aadhaar_number'] ?? null,
                $data['father_name'] ?? null,
                $data['mother_name'] ?? null,
                $data['emergency_contact'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['social_media_links'] ?? null,
                $data['skills'] ?? null,
                $data['certifications'] ?? null,
                $data['about_education'] ?? null,
                $data['about_experience'] ?? null,
                $data['website'] ?? null,
                $data['location'] ?? null
            ]);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Formats profile photo for display
 */
function getProfilePhoto($profile_photo_path, string $full_name = 'User') {
    if (!empty($profile_photo_path)) {
        return '<img src="' . htmlspecialchars($profile_photo_path) . '" alt="Profile" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">';
    }
    return '<div style="width: 50px; height: 50px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 20px;">' . strtoupper(substr($full_name, 0, 1)) . '</div>';
}

/**
 * Gets user approval status badge
 */
function getApprovalBadge(string $status): string {
    $badge_colors = [
        'approved' => '#d4edda',
        'pending' => '#fff3cd',
        'rejected' => '#f8d7da'
    ];
    $text_colors = [
        'approved' => '#155724',
        'pending' => '#856404',
        'rejected' => '#721c24'
    ];
    
    $bg = $badge_colors[$status] ?? '#f0f0f0';
    $text = $text_colors[$status] ?? '#333';
    $icon = $status === 'approved' ? '✓' : ($status === 'pending' ? '⏳' : '✗');
    
    return '<span style="display: inline-block; padding: 6px 12px; border-radius: 20px; background: ' . $bg . '; color: ' . $text . '; font-size: 13px; font-weight: 500;">' . $icon . ' ' . ucfirst($status) . '</span>';
}

/**
 * Validates uploaded image file
 * Checks MIME type, file size, and image dimensions
 */
function validateImageFile(array $file, string $type = 'profile'): array {
    $result = ['valid' => true, 'error' => ''];
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (empty($file['name'])) {
        return $result;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $result['valid'] = false;
        $result['error'] = 'File size must be less than 5MB';
        return $result;
    }
    
    // Check MIME type
    if (!in_array($file['type'], $allowed_types)) {
        $result['valid'] = false;
        $result['error'] = 'Only JPG, PNG and GIF files are allowed';
        return $result;
    }
    
    // Additional validation using getimagesize
    $image_info = @getimagesize($file['tmp_name']);
    if ($image_info === false) {
        $result['valid'] = false;
        $result['error'] = 'Invalid or corrupted image file';
        return $result;
    }
    
    // Check image dimensions
    list($width, $height) = $image_info;
    
    if ($type === 'profile') {
        if ($width < 100 || $height < 100) {
            $result['valid'] = false;
            $result['error'] = 'Profile photo must be at least 100x100 pixels';
            return $result;
        }
    } elseif ($type === 'cover') {
        if ($width < 300 || $height < 100) {
            $result['valid'] = false;
            $result['error'] = 'Cover photo must be at least 300x100 pixels';
            return $result;
        }
    }
    
    return $result;
}

/**
 * Gets organized upload directory path for image type
 */
function getUploadDirectory(string $type = 'profile'): string {
    $base = __DIR__ . '/../uploads/profiles/';
    
    if ($type === 'profile') {
        return $base . 'profile_photos/';
    } elseif ($type === 'cover') {
        return $base . 'cover_photos/';
    }
    
    return $base;
}

/**
 * Generates safe filename for uploaded image
 */
function generateImageFilename(int $user_id, string $type = 'profile', string $original_filename = ''): string {
    $prefix = ($type === 'cover') ? 'cover' : 'profile';
    
    // Extract actual file extension
    $ext = 'jpg'; // Default
    if (!empty($original_filename)) {
        $original_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        if (in_array($original_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $ext = ($original_ext === 'jpeg') ? 'jpg' : $original_ext;
        }
    }
    
    return $prefix . '_' . $user_id . '_' . time() . '.' . $ext;
}

/**
 * Deletes old profile/cover photo if exists
 */
function deleteOldPhoto(string $photo_path = null): bool {
    if (empty($photo_path)) {
        return true;
    }
    
    // Extract filename from URL path
    $parts = parse_url($photo_path);
    if (empty($parts['path'])) {
        return true;
    }
    
    // Convert URL path to file system path
    $file_path = __DIR__ . '/..' . $parts['path'];
    
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    
    return true;
}

/**
 * Uploads image file with full validation
 */
function uploadImageFile(array $file, int $user_id, string $type = 'profile'): array {
    $result = ['success' => false, 'path' => null, 'error' => ''];
    
    // Validate file
    $validation = validateImageFile($file, $type);
    if (!$validation['valid']) {
        $result['error'] = $validation['error'];
        return $result;
    }
    
    // Get upload directory and ensure it exists
    $upload_dir = getUploadDirectory($type);
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $result['error'] = 'Failed to create upload directory: ' . $upload_dir;
            return $result;
        }
    }
    
    // Verify directory is writable
    if (!is_writable($upload_dir)) {
        // Try to chmod
        if (!chmod($upload_dir, 0777)) {
            $result['error'] = 'Upload directory is not writable. Please check folder permissions. Path: ' . $upload_dir;
            return $result;
        }
    }
    
    // Double-check after chmod
    if (!is_writable($upload_dir)) {
        $result['error'] = 'Upload directory permissions could not be set. Contact administrator. Path: ' . $upload_dir;
        return $result;
    }
    
    // Generate safe filename with actual file extension
    $filename = generateImageFilename($user_id, $type, $file['name']);
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        $result['error'] = 'Failed to save image file';
        return $result;
    }
    
    // Set proper permissions
    chmod($file_path, 0644);
    
    // Return URL path
    $url_path = '/uploads/profiles/' . ($type === 'cover' ? 'cover_photos' : 'profile_photos') . '/' . $filename;
    $result['success'] = true;
    $result['path'] = BASE_URL . $url_path;
    
    return $result;
}

?>