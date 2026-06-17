<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🔍 Photo Upload Debug - Step by Step</h2>\n";

// Check 1: PHP File Upload Configuration
echo "<h3>1️⃣ PHP Configuration</h3>\n";
$php_config = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_tmp_dir' => ini_get('upload_tmp_dir'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'memory_limit' => ini_get('memory_limit')
];

echo "<pre style='background:#f0f0f0; padding:10px; border-radius:5px;'>\n";
foreach ($php_config as $key => $val) {
    echo str_pad($key . ':', 25) . " $val\n";
}
echo "</pre>\n";

// Check 2: Directory Structure
echo "<h3>2️⃣ Directory Structure</h3>\n";

$dirs_to_check = [
    'Base Uploads' => __DIR__ . '/uploads',
    'Profiles' => __DIR__ . '/uploads/profiles',
    'Profile Photos' => __DIR__ . '/uploads/profiles/profile_photos',
    'Cover Photos' => __DIR__ . '/uploads/profiles/cover_photos'
];

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%;'>\n";
echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Perms</th></tr>\n";

foreach ($dirs_to_check as $name => $dir) {
    $exists = is_dir($dir) ? '✓' : '✗';
    $writable = is_writable($dir) ? '✓' : '✗';
    $perms = is_dir($dir) ? decoct(fileperms($dir) & 0777) : 'N/A';
    
    $row_color = (is_dir($dir) && is_writable($dir)) ? 'white' : '#ffcccc';
    echo "<tr style='background:$row_color;'>\n";
    echo "<td>$name<br><small>$dir</small></td>\n";
    echo "<td>$exists</td>\n";
    echo "<td>$writable</td>\n";
    echo "<td>$perms</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// Check 3: Security Files
echo "<h3>3️⃣ Security Files</h3>\n";

$security_files = [
    __DIR__ . '/uploads/profiles/.htaccess',
    __DIR__ . '/uploads/profiles/index.php',
    __DIR__ . '/uploads/profiles/profile_photos/.htaccess',
    __DIR__ . '/uploads/profiles/profile_photos/index.php',
    __DIR__ . '/uploads/profiles/cover_photos/.htaccess',
    __DIR__ . '/uploads/profiles/cover_photos/index.php'
];

foreach ($security_files as $file) {
    $status = file_exists($file) ? '✓ Exists' : '✗ Missing';
    echo "<p>$file: <strong>$status</strong></p>\n";
}

// Check 4: Test File Write
echo "<h3>4️⃣ Write Test</h3>\n";

$test_file = __DIR__ . '/uploads/profiles/profile_photos/test_write_' . time() . '.txt';
$write_test = file_put_contents($test_file, 'test');

if ($write_test !== false) {
    echo "<p style='color:green;'>✓ Can write to upload directory</p>\n";
    unlink($test_file);
} else {
    echo "<p style='color:red;'>✗ Cannot write to upload directory!</p>\n";
    echo "<p>Try running: <code>chmod -R 777 uploads/profiles/</code></p>\n";
}

// Check 5: Form Upload Test
echo "<h3>5️⃣ Upload Test Form</h3>\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo'])) {
    echo "<h4>Upload Result:</h4>\n";
    
    $file = $_FILES['test_photo'];
    
    echo "<div style='background:#f0f0f0; padding:15px; border-radius:5px; margin:10px 0;'>\n";
    echo "<strong>File Details:</strong><br>\n";
    echo "Name: " . htmlspecialchars($file['name']) . "<br>\n";
    echo "Type: " . htmlspecialchars($file['type']) . "<br>\n";
    echo "Size: " . $file['size'] . " bytes<br>\n";
    echo "Error Code: " . $file['error'] . "<br>\n";
    echo "Tmp Name: " . htmlspecialchars($file['tmp_name']) . "<br>\n";
    echo "</div>\n";
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'PHP extension blocked upload'
        ];
        
        $error_msg = $error_messages[$file['error']] ?? 'Unknown error';
        echo "<p style='color:red;'>❌ Upload Error: $error_msg (Code: " . $file['error'] . ")</p>\n";
    } else {
        // File uploaded successfully, test validation
        echo "<h4>Testing Validation:</h4>\n";
        
        $validation = validateImageFile($file, 'profile');
        
        if ($validation['valid']) {
            echo "<p style='color:green;'>✓ File validation passed</p>\n";
            
            // Try upload
            $user_id = isLoggedIn() ? $_SESSION['user_id'] : 9999;
            $result = uploadImageFile($file, $user_id, 'profile');
            
            if ($result['success']) {
                echo "<p style='color:green;'>✓ Upload successful!</p>\n";
                echo "<p>Path: " . htmlspecialchars($result['path']) . "</p>\n";
                
                // Try to display
                echo "<p><img src='" . htmlspecialchars($result['path']) . "' style='max-width:200px; border:1px solid #ccc; margin:10px 0;'></p>\n";
                
                // Check if file exists
                $saved_file = getUploadDirectory('profile') . generateImageFilename($user_id, 'profile', $file['name']);
                echo "<p>File exists in filesystem: " . (file_exists($saved_file) ? '✓ YES' : '✗ NO') . "</p>\n";
            } else {
                echo "<p style='color:red;'>✗ Upload failed: " . htmlspecialchars($result['error']) . "</p>\n";
            }
        } else {
            echo "<p style='color:red;'>✗ Validation failed: " . htmlspecialchars($validation['error']) . "</p>\n";
        }
    }
    
    echo "<hr>\n";
}

// Show upload form
echo "<form method='post' enctype='multipart/form-data' style='border:2px solid #667eea; padding:20px; border-radius:5px; margin:20px 0;'>\n";
echo "<h4>Test Upload Photo</h4>\n";
echo "<p>\n";
echo "<label>Select Photo (JPG, PNG, GIF - Min 100×100px, Max 5MB):</label><br><br>\n";
echo "<input type='file' name='test_photo' accept='image/*' required>\n";
echo "</p>\n";
echo "<p><button type='submit' style='padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer; font-size:16px;'>Test Upload</button></p>\n";
echo "</form>\n";

// Check 6: Database
echo "<h3>6️⃣ Database Status</h3>\n";

try {
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT profile_photo_path, cover_photo_path FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            echo "<p>Profile Photo URL: " . ($profile['profile_photo_path'] ? htmlspecialchars($profile['profile_photo_path']) : 'None') . "</p>\n";
            echo "<p>Cover Photo URL: " . ($profile['cover_photo_path'] ? htmlspecialchars($profile['cover_photo_path']) : 'None') . "</p>\n";
        } else {
            echo "<p>No profile record for this user</p>\n";
        }
    } else {
        echo "<p><a href='/EXAMs/login.php'>Log in</a> to see database records</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Check 7: PHP Extensions
echo "<h3>7️⃣ PHP Extensions</h3>\n";

$extensions = ['gd', 'exif', 'fileinfo'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext) ? '✓ Loaded' : '✗ Not loaded';
    echo "<p>$ext: <strong>$loaded</strong></p>\n";
}

// Check 8: Log File
echo "<h3>8️⃣ Error Log</h3>\n";

$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $log_lines = array_slice(file($error_log), -10);
    echo "<pre style='background:#f0f0f0; padding:10px; border-radius:5px; max-height:300px; overflow-y:auto;'>\n";
    foreach ($log_lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>\n";
} else {
    echo "<p>Error log not found: " . htmlspecialchars($error_log) . "</p>\n";
}

echo "<h3>✅ Recommendation</h3>\n";
echo "<ol>\n";
echo "<li>If directory shows ✗ Writable: Run <code>chmod -R 777 uploads/profiles/</code></li>\n";
echo "<li>Use the form above to test upload</li>\n";
echo "<li>Check that your photo is at least 100×100 pixels</li>\n";
echo "<li>Try different image format (JPG instead of PNG)</li>\n";
echo "</ol>\n";

?>
