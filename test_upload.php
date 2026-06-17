<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<h2>🧪 Test Image Upload</h2>\n";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Upload Result</h3>\n";
    
    if (!isset($_FILES['test_image'])) {
        echo "<p style='color:red;'>No file selected</p>\n";
    } else {
        $file = $_FILES['test_image'];
        echo "<h4>File Details:</h4>\n";
        echo "<pre>\n";
        echo "Name: " . htmlspecialchars($file['name']) . "\n";
        echo "Type: " . htmlspecialchars($file['type']) . "\n";
        echo "Size: " . $file['size'] . " bytes\n";
        echo "Tmp: " . htmlspecialchars($file['tmp_name']) . "\n";
        echo "Error: " . $file['error'] . "\n";
        echo "</pre>\n";
        
        // Test validation
        echo "<h4>Validation:</h4>\n";
        $validation = validateImageFile($file, 'profile');
        echo "<p>Valid: " . ($validation['valid'] ? 'Yes ✓' : 'No ✗') . "</p>\n";
        if (!$validation['valid']) {
            echo "<p style='color:red;'>Error: " . $validation['error'] . "</p>\n";
        }
        
        // Test upload for logged-in user
        if (isLoggedIn()) {
            echo "<h4>Upload Simulation:</h4>\n";
            $user_id = $_SESSION['user_id'];
            
            $upload_result = uploadImageFile($file, $user_id, 'profile');
            
            echo "<p>Success: " . ($upload_result['success'] ? 'Yes ✓' : 'No ✗') . "</p>\n";
            if ($upload_result['success']) {
                echo "<p style='color:green;'>✓ Upload successful!</p>\n";
                echo "<p>Saved path: " . htmlspecialchars($upload_result['path']) . "</p>\n";
                echo "<p><img src='" . htmlspecialchars($upload_result['path']) . "' style='max-width:200px; border:1px solid #ccc;'></p>\n";
            } else {
                echo "<p style='color:red;'>✗ Upload failed: " . htmlspecialchars($upload_result['error']) . "</p>\n";
            }
        } else {
            echo "<p style='color:orange;'>Please log in to test upload</p>\n";
        }
    }
} else {
    echo "<h3>Upload a Test Image</h3>\n";
    
    if (!isLoggedIn()) {
        echo "<p style='color:red;'>Please <a href='/EXAMs/login.php'>log in</a> first</p>\n";
    } else {
        echo "<p>User ID: " . htmlspecialchars($_SESSION['user_id']) . "</p>\n";
        echo "<form method='post' enctype='multipart/form-data'>\n";
        echo "<p>\n";
        echo "<label>Select Image:</label><br>\n";
        echo "<input type='file' name='test_image' accept='image/*' required>\n";
        echo "</p>\n";
        echo "<p>\n";
        echo "<button type='submit'>Upload Test</button>\n";
        echo "</p>\n";
        echo "</form>\n";
        
        // Show directory status
        echo "<h3>Directory Status</h3>\n";
        $profile_dir = __DIR__ . '/uploads/profiles/profile_photos/';
        echo "<p>Profile Photos Directory: $profile_dir</p>\n";
        echo "<p>Exists: " . (is_dir($profile_dir) ? 'Yes ✓' : 'No ✗') . "</p>\n";
        echo "<p>Writable: " . (is_writable($profile_dir) ? 'Yes ✓' : 'No ✗') . "</p>\n";
        echo "<p>Permissions: " . substr(sprintf('%o', fileperms($profile_dir)), -4) . "</p>\n";
    }
}

?>
