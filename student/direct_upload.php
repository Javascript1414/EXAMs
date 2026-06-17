<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user_id = $_SESSION['user_id'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>📸 Upload Photo - Direct</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #667eea; margin-top: 0; }
        .status { 
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        form { display: flex; flex-direction: column; gap: 15px; }
        input[type='file'] { 
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        button {
            padding: 12px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        button:hover { background: #764ba2; }
        .debug { 
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 12px;
            color: #333;
        }
        .debug h3 { margin-top: 0; }
        .debug pre { 
            background: white;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 11px;
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>📸 Photo Upload - Direct</h1>";

// Check directory writable
$profile_dir = __DIR__ . '/../uploads/profiles/profile_photos';
$cover_dir = __DIR__ . '/../uploads/profiles/cover_photos';

echo "<div class='debug'>
    <h3>✓ System Check</h3>
    <pre>";

echo "User ID: " . $user_id . "\n";
echo "Profile Dir: " . $profile_dir . "\n";
echo "Profile Dir Writable: " . (is_writable($profile_dir) ? "YES ✓" : "NO ✗") . "\n";
echo "Cover Dir Writable: " . (is_writable($cover_dir) ? "YES ✓" : "NO ✗") . "\n";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";

echo "</pre></div>";

// Process upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['photo'])) {
        $upload_message = "<div class='status error'>❌ No file received</div>";
    } elseif ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (exceeds php.ini limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (exceeds form limit)',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $upload_message = "<div class='status error'>❌ Upload Error: " . ($errors[$_FILES['photo']['error']] ?? 'Unknown error') . "</div>";
    } else {
        $file = $_FILES['photo'];
        
        echo "<div class='debug'><pre>";
        echo "Name: " . $file['name'] . "\n";
        echo "Type: " . $file['type'] . "\n";
        echo "Size: " . $file['size'] . " bytes\n";
        echo "Tmp Name: " . $file['tmp_name'] . "\n";
        echo "Tmp File Exists: " . (file_exists($file['tmp_name']) ? "YES ✓" : "NO ✗") . "\n";
        echo "</pre></div>";
        
        // Validate
        $validation = validateImageFile($file, 'profile');
        
        echo "<div class='status " . ($validation['valid'] ? 'success' : 'error') . "'>";
        echo ($validation['valid'] ? '✓ Validation: PASSED' : '✗ Validation: FAILED') . "\n";
        echo "Details: " . $validation['error'] . "\n";
        echo "</div>";
        
        if ($validation['valid']) {
            // Try upload
            $result = uploadImageFile($file, $user_id, 'profile');
            
            echo "<div class='status " . ($result['success'] ? 'success' : 'error') . "'>";
            echo ($result['success'] ? '✅ UPLOAD SUCCESSFUL!' : '❌ Upload Failed') . "\n";
            echo "Message: " . $result['error'] . "\n";
            if ($result['success']) {
                echo "Saved to: " . $result['path'] . "\n";
            }
            echo "</div>";
        }
    }
}

echo $upload_message;

echo "<h2>Select Photo to Upload:</h2>
    <form method='POST' enctype='multipart/form-data'>
        <input type='file' name='photo' accept='image/*' required>
        <button type='submit'>📤 Upload Photo</button>
    </form>
    
    <div style='margin-top: 30px; text-align: center;'>
        <a href='simple_photo_upload.php' style='color: #667eea; text-decoration: none; font-weight: bold;'>← Back to Simple Upload</a>
    </div>
</div>
</body>
</html>";
?>
