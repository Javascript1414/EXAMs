<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>📤 Upload Directory Debug</h2>\n";

// Test directory structure
$upload_dir = __DIR__ . '/uploads/profiles/profile_photos';
$cover_dir = __DIR__ . '/uploads/profiles/cover_photos';

echo "<h3>Directory Status</h3>\n";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>\n";
echo "<tr><th>Path</th><th>Exists</th><th>Writable</th><th>Permissions</th></tr>\n";

foreach ([$upload_dir => 'Profile Photos', $cover_dir => 'Cover Photos'] as $dir => $name) {
    $exists = is_dir($dir) ? 'Yes ✓' : 'No ✗';
    $writable = is_writable($dir) ? 'Yes ✓' : 'No ✗';
    $perms = is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
    
    echo "<tr><td>$name<br>$dir</td><td>$exists</td><td>$writable</td><td>$perms</td></tr>\n";
}
echo "</table>\n";

// Test file write
echo "<h3>Write Test</h3>\n";

$test_file = $upload_dir . '/test_write_' . time() . '.txt';
$test_content = 'Test write at ' . date('Y-m-d H:i:s');

if (file_put_contents($test_file, $test_content)) {
    echo "<p style='color:green;'>✓ Can write files to profile directory</p>\n";
    unlink($test_file);
} else {
    echo "<p style='color:red;'>✗ Cannot write to profile directory</p>\n";
}

// Test functions
echo "<h3>Function Tests</h3>\n";

$test_user_id = 123;

// Test getUploadDirectory
$profile_dir = getUploadDirectory('profile');
$cover_dir_test = getUploadDirectory('cover');

echo "<p>Profile Directory: $profile_dir</p>\n";
echo "<p>Cover Directory: $cover_dir_test</p>\n";

// Test generateImageFilename
$profile_filename = generateImageFilename($test_user_id, 'profile', 'test.jpg');
$cover_filename = generateImageFilename($test_user_id, 'cover', 'banner.png');

echo "<p>Profile Filename: $profile_filename</p>\n";
echo "<p>Cover Filename: $cover_filename</p>\n";

// Test validateImageFile with mock data
echo "<h3>Validation Test</h3>\n";

// Create a simple test image
$test_image = tempnam(sys_get_temp_dir(), 'img');
$gd = imagecreatetruecolor(200, 200);
imagefill($gd, 0, 0, imagecolorallocate($gd, 255, 255, 255));
imagejpeg($gd, $test_image);
imagedestroy($gd);

$mock_file = [
    'name' => 'test_image.jpg',
    'type' => 'image/jpeg',
    'size' => filesize($test_image),
    'tmp_name' => $test_image,
    'error' => UPLOAD_ERR_OK
];

$validation = validateImageFile($mock_file, 'profile');
echo "<p>Validation Result: " . ($validation['valid'] ? 'Valid ✓' : 'Invalid ✗') . "</p>\n";
if (!$validation['valid']) {
    echo "<p>Error: " . $validation['error'] . "</p>\n";
}

// Test full upload process
echo "<h3>Upload Process Test</h3>\n";

$upload_result = uploadImageFile($mock_file, $test_user_id, 'profile');

if ($upload_result['success']) {
    echo "<p style='color:green;'>✓ Upload successful!</p>\n";
    echo "<p>Path: " . $upload_result['path'] . "</p>\n";
    
    // Check if file exists
    $saved_file = $profile_dir . generateImageFilename($test_user_id, 'profile', 'test_image.jpg');
    if (file_exists($saved_file)) {
        echo "<p>✓ File saved to: $saved_file</p>\n";
        unlink($saved_file);
    } else {
        echo "<p>✗ File not found after upload</p>\n";
    }
} else {
    echo "<p style='color:red;'>✗ Upload failed: " . $upload_result['error'] . "</p>\n";
}

// Clean up test image
unlink($test_image);

echo "<h3>Summary</h3>\n";
echo "<div style='background:#e8f5e9; padding:15px; border-radius:5px;'>\n";
echo "<p><strong>If all tests show ✓:</strong> Upload system is working correctly</p>\n";
echo "<p><strong>If any test shows ✗:</strong> Check directory permissions with:</p>\n";
echo "<pre>chmod -R 755 uploads/profiles/</pre>\n";
echo "</div>\n";

?>
