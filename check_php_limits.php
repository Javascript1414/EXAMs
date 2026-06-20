<?php
/**
 * Check and display current PHP upload settings
 */

echo "Current PHP Upload Settings:\n";
echo "============================\n\n";

echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

// Convert to bytes for comparison
function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    return $value;
}

$upload_max = convertToBytes(ini_get('upload_max_filesize'));
$post_max = convertToBytes(ini_get('post_max_size'));
$memory = convertToBytes(ini_get('memory_limit'));

echo "\nIn Bytes:\n";
echo "upload_max_filesize: " . number_format($upload_max) . " bytes (" . round($upload_max / 1024 / 1024) . " MB)\n";
echo "post_max_size: " . number_format($post_max) . " bytes (" . round($post_max / 1024 / 1024) . " MB)\n";
echo "memory_limit: " . number_format($memory) . " bytes (" . round($memory / 1024 / 1024) . " MB)\n";

$needed = 52428800; // 50MB

echo "\nRequired for 50MB uploads: " . number_format($needed) . " bytes (50 MB)\n";

if ($upload_max >= $needed && $post_max >= $needed) {
    echo "\n✓ Server is configured to handle 50MB file uploads\n";
} else {
    echo "\n⚠ Server may need configuration for 50MB uploads\n";
    if ($upload_max < $needed) {
        echo "  - upload_max_filesize needs to be at least 50M\n";
    }
    if ($post_max < $needed) {
        echo "  - post_max_size needs to be at least 50M\n";
    }
}
?>
