<?php
// Carousel Photos Configuration - Automatic Loop
$imagePath = '../../assets/images/';
$imageDir = __DIR__ . '/' . $imagePath;

// Get all image files automatically
$carouselPhotos = array();
$allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

if (is_dir($imageDir)) {
    $files = scandir($imageDir);
    
    foreach ($files as $file) {
        // Skip . and ..
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        // Get file extension
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        // Check if it's an image file
        if (in_array($extension, $allowedExtensions)) {
            $carouselPhotos[] = $file;
        }
    }
    
    // Sort photos alphabetically
    sort($carouselPhotos);
}
?>