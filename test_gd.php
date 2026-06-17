<?php
// Test if script runs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if GD is available
if (extension_loaded('gd')) {
    // Create simple test image
    $width = 800;
    $height = 600;
    $img = imagecreatetruecolor($width, $height);
    
    $white = imagecolorallocate($img, 255, 255, 255);
    $black = imagecolorallocate($img, 0, 0, 0);
    $blue = imagecolorallocate($img, 0, 0, 255);
    
    imagefilledrectangle($img, 0, 0, $width, $height, $white);
    
    // Draw test box
    imagerectangle($img, 50, 50, 300, 200, $blue);
    imagestring($img, 5, 60, 60, "ER DIAGRAM - DATABASE", $blue);
    
    // Save file
    $filepath = __DIR__ . '/uploads/ER_Diagram_Test.jpg';
    imagejpeg($img, $filepath, 95);
    imagedestroy($img);
    
    echo "SUCCESS: File created at " . $filepath . "\n";
} else {
    echo "ERROR: GD library not available\n";
}
?>
