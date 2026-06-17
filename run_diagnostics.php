<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "=== Upload System Diagnostics ===\n\n";

// Test 1: Check directories
echo "1. Checking Directories:\n";
$dirs = [
    'Profile Photos' => __DIR__ . '/uploads/profiles/profile_photos',
    'Cover Photos' => __DIR__ . '/uploads/profiles/cover_photos'
];

foreach ($dirs as $name => $dir) {
    echo "   - $name: ";
    if (is_dir($dir)) {
        echo "EXISTS ";
        echo (is_writable($dir) ? "[WRITABLE]" : "[NOT WRITABLE]");
    } else {
        echo "MISSING";
    }
    echo "\n";
}

// Test 2: Check functions
echo "\n2. Checking Functions:\n";
$functions = [
    'validateImageFile',
    'uploadImageFile',
    'deleteOldPhoto',
    'getUploadDirectory',
    'generateImageFilename'
];

foreach ($functions as $func) {
    echo "   - $func: " . (function_exists($func) ? "OK" : "MISSING") . "\n";
}

// Test 3: Check paths
echo "\n3. Testing Paths:\n";
$profile_dir = getUploadDirectory('profile');
$cover_dir = getUploadDirectory('cover');

echo "   - Profile Dir: $profile_dir\n";
echo "   - Cover Dir: $cover_dir\n";

// Test 4: Test filename generation
echo "\n4. Testing Filename Generation:\n";
$test_user = 123;
$profile_filename = generateImageFilename($test_user, 'profile', 'photo.jpg');
$cover_filename = generateImageFilename($test_user, 'cover', 'banner.png');

echo "   - Profile: $profile_filename\n";
echo "   - Cover: $cover_filename\n";

// Test 5: Create a mock test file
echo "\n5. Creating Mock Test File:\n";

$test_dir = sys_get_temp_dir();
$test_file = $test_dir . '/test_img_' . time() . '.jpg';

// Create a real minimal JPEG file
$jpeg_data = hex2bin('FFD8FFE000104A46494600010100000100010000FFDB004300080606070605080707070909080A0C140D0C0B0B0C1912130F141D1A1F1E1D1A1C1C20242E2720222C231C1C28372029222C2323C001090909090C0B0C0C0C1411130F1411141414202B201E202B292020202020202020202020202B20202B2B2B2B2B2B2B2B2B2B2B2B2B2B2B2B2BFFC0000B080001000101011100FFC4001F000001050101010101010000000000000000010203040506070809FFFDA00008010100003F00F4CF142D4B14D000FFD9');

file_put_contents($test_file, $jpeg_data);

$mock_upload = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'size' => filesize($test_file),
    'tmp_name' => $test_file,
    'error' => UPLOAD_ERR_OK
];

echo "   - Test file created: $test_file\n";
echo "   - File size: " . filesize($test_file) . " bytes\n";

// Test 6: Validate
echo "\n6. Testing Validation:\n";
$validation = validateImageFile($mock_upload, 'profile');
echo "   - Valid: " . ($validation['valid'] ? "YES" : "NO") . "\n";
if (!$validation['valid']) {
    echo "   - Error: " . $validation['error'] . "\n";
}

// Test 7: Upload with mock user ID
echo "\n7. Testing Upload Function:\n";
$test_user_id = 9999;
$upload_result = uploadImageFile($mock_upload, $test_user_id, 'profile');

if ($upload_result['success']) {
    echo "   - Upload: SUCCESS ✓\n";
    echo "   - URL: " . $upload_result['path'] . "\n";
    
    // Check if file exists
    $saved_file = getUploadDirectory('profile') . generateImageFilename($test_user_id, 'profile', 'test.jpg');
    echo "   - File exists: " . (file_exists($saved_file) ? "YES" : "NO") . "\n";
    
    // Try to delete it
    if (file_exists($saved_file)) {
        unlink($saved_file);
        echo "   - Cleanup: Deleted\n";
    }
} else {
    echo "   - Upload: FAILED ✗\n";
    echo "   - Error: " . $upload_result['error'] . "\n";
}

// Clean up test file
unlink($test_file);

echo "\n=== End Diagnostics ===\n";

?>
