<?php
/**
 * Complete Upload System Troubleshooting Guide
 * Run this script to understand and fix any upload issues
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>📋 Upload System Complete Guide</h1>\n";

echo "<h2>✅ Quick Verification</h2>\n";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse; margin-bottom:20px; width:100%;'>\n";

$checks = [];

// Check 1: Directories
$profile_dir = __DIR__ . '/uploads/profiles/profile_photos';
$cover_dir = __DIR__ . '/uploads/profiles/cover_photos';
$checks[] = ['Directories Exist', is_dir($profile_dir) && is_dir($cover_dir) ? '✓ YES' : '✗ NO'];

// Check 2: Writable
$checks[] = ['Directories Writable', is_writable($profile_dir) && is_writable($cover_dir) ? '✓ YES' : '✗ NO'];

// Check 3: Database
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM user_profiles");
    $cnt = $stmt->fetch()['cnt'];
    $checks[] = ['Database Connected', '✓ YES (' . $cnt . ' profiles)'];
} catch (Exception $e) {
    $checks[] = ['Database Connected', '✗ NO: ' . $e->getMessage()];
}

// Check 4: Functions
$funcs_ok = function_exists('uploadImageFile') && 
            function_exists('validateImageFile') && 
            function_exists('generateImageFilename');
$checks[] = ['Upload Functions', $funcs_ok ? '✓ YES' : '✗ NO'];

foreach ($checks as $check) {
    $status_color = strpos($check[1], '✓') !== false ? 'green' : 'red';
    echo "<tr><td><strong>{$check[0]}</strong></td><td style='color:$status_color;'>{$check[1]}</td></tr>\n";
}
echo "</table>\n";

echo "<h2>📸 Image Requirements</h2>\n";
echo "<ul>\n";
echo "<li><strong>Formats:</strong> JPG, PNG, GIF</li>\n";
echo "<li><strong>Max Size:</strong> 5MB</li>\n";
echo "<li><strong>Profile Photo:</strong> Minimum 100×100 pixels (recommended 500×500+)</li>\n";
echo "<li><strong>Cover Photo:</strong> Minimum 300×100 pixels (recommended 1200×400+)</li>\n";
echo "</ul>\n";

echo "<h2>🔍 Common Issues & Solutions</h2>\n";

$issues = [
    [
        'Problem' => 'Upload button doesn\'t work',
        'Cause' => 'Form might not have enctype=\"multipart/form-data\"',
        'Solution' => 'Check edit_profile.php line ~450 for form tag'
    ],
    [
        'Problem' => 'File uploaded but error message shown',
        'Cause' => 'Image smaller than minimum required size',
        'Solution' => 'Use image editor to resize to at least 500×500 pixels'
    ],
    [
        'Problem' => 'File appears to upload but doesn\'t save',
        'Cause' => 'Directory permissions issue or file too large',
        'Solution' => 'Run PHP command: chmod -R 755 uploads/profiles/'
    ],
    [
        'Problem' => 'Photo shows in edit but not in profile view',
        'Cause' => 'Database not updated or URL path incorrect',
        'Solution' => 'Check database: SELECT profile_photo_path FROM user_profiles'
    ],
    [
        'Problem' => 'See "Invalid or corrupted image file"',
        'Cause' => 'File is not a real image or corrupted',
        'Solution' => 'Try uploading a different image file'
    ]
];

echo "<table border='1' cellpadding='10' style='border-collapse:collapse; margin-bottom:20px; width:100%;'>\n";
echo "<tr><th>Problem</th><th>Cause</th><th>Solution</th></tr>\n";
foreach ($issues as $issue) {
    echo "<tr>\n";
    echo "<td><strong>{$issue['Problem']}</strong></td>\n";
    echo "<td>{$issue['Cause']}</td>\n";
    echo "<td><code>{$issue['Solution']}</code></td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

echo "<h2>🧪 Manual Test</h2>\n";

if (isLoggedIn()) {
    echo "<p>Logged in as User ID: " . $_SESSION['user_id'] . "</p>\n";
    
    echo "<h3>Check Uploaded Files</h3>\n";
    
    $user_id = $_SESSION['user_id'];
    
    // Query database
    $stmt = $pdo->prepare("SELECT profile_photo_path, cover_photo_path FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if ($profile) {
        echo "<p><strong>Profile Photo URL:</strong> " . ($profile['profile_photo_path'] ? htmlspecialchars($profile['profile_photo_path']) : 'None') . "</p>\n";
        echo "<p><strong>Cover Photo URL:</strong> " . ($profile['cover_photo_path'] ? htmlspecialchars($profile['cover_photo_path']) : 'None') . "</p>\n";
        
        // Check if files exist
        if (!empty($profile['profile_photo_path'])) {
            $path_parts = parse_url($profile['profile_photo_path']);
            $file_path = __DIR__ . '/..' . $path_parts['path'];
            echo "<p>Profile Photo File: " . (file_exists($file_path) ? '✓ EXISTS' : '✗ MISSING') . "</p>\n";
        }
    } else {
        echo "<p>No profile record found. <a href='edit_profile.php'>Create one now</a></p>\n";
    }
    
    echo "<h3>Upload Test Form</h3>\n";
    echo "<form method='post' enctype='multipart/form-data' style='border:1px solid #ccc; padding:15px; border-radius:5px;'>\n";
    echo "<p>\n";
    echo "<label>Test Upload Profile Photo:</label><br>\n";
    echo "<input type='file' name='test_image' accept='image/*'><br>\n";
    echo "<small>Min 100×100px, Max 5MB</small>\n";
    echo "</p>\n";
    echo "<p><button type='submit' name='action' value='test'>Test Upload</button></p>\n";
    echo "</form>\n";
    
    // Process test upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'test' && !empty($_FILES['test_image'])) {
        echo "<h3>Test Result</h3>\n";
        
        $result = uploadImageFile($_FILES['test_image'], $user_id, 'profile');
        
        if ($result['success']) {
            echo "<p style='color:green; padding:10px; background:#e8f5e9; border-radius:5px;'>\n";
            echo "✅ Upload Successful!<br>\n";
            echo "URL: " . htmlspecialchars($result['path']) . "<br>\n";
            echo "<img src='" . htmlspecialchars($result['path']) . "' style='max-width:200px; margin-top:10px; border:1px solid #ccc;'>\n";
            echo "</p>\n";
        } else {
            echo "<p style='color:red; padding:10px; background:#ffebee; border-radius:5px;'>\n";
            echo "❌ Upload Failed<br>\n";
            echo "Error: " . htmlspecialchars($result['error']) . "\n";
            echo "</p>\n";
        }
    }
    
} else {
    echo "<p><a href='login.php'>Please log in</a> to test uploads</p>\n";
}

echo "<h2>📚 System Information</h2>\n";
echo "<pre style='background:#f5f5f5; padding:10px; border-radius:5px;'>\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Upload Max Size: " . ini_get('upload_max_filesize') . "\n";
echo "POST Max Size: " . ini_get('post_max_size') . "\n";
echo "Profile Directory: " . getUploadDirectory('profile') . "\n";
echo "Cover Directory: " . getUploadDirectory('cover') . "\n";
echo "</pre>\n";

echo "<h2>🔧 If All Else Fails</h2>\n";
echo "<ol>\n";
echo "<li>Clear browser cache (Ctrl+Shift+Delete)</li>\n";
echo "<li>Try different image file (PNG instead of JPG)</li>\n";
echo "<li>Make image bigger (at least 500×500 pixels)</li>\n";
echo "<li>Check PHP error log: " . ini_get('error_log') . "</li>\n";
echo "<li>Restart XAMPP/server</li>\n";
echo "</ol>\n";

?>
