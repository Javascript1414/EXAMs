<?php
/**
 * CITS LMS - Homepage/Landing Page
 * Production Ready for InfinityFree Hosting
 * 
 * This file loads the main homepage with all sections.
 * Properly configured for both local development and production hosting.
 * 
 * Last Updated: 2026-06-20
 */

// Load configuration - Use parent directory config
$config_file = dirname(__DIR__) . '/config_infinityfree.php';
if (!file_exists($config_file)) {
    $config_file = dirname(__DIR__) . '/config.php';
}
require_once $config_file;

// Define constants for easy reference
if (!defined('ASSETS_CSS')) define('ASSETS_CSS', ASSETS_DIR . '/css');
if (!defined('ASSETS_JS')) define('ASSETS_JS', ASSETS_DIR . '/js');
if (!defined('INDEX_JS')) define('INDEX_JS', __DIR__ . '/js');
if (!defined('INDEX_SECTIONS')) define('INDEX_SECTIONS', __DIR__ . '/sections');

// Determine protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CITS LMS - Online Exam and Learning Management System">
    <meta name="keywords" content="LMS, Exam, Learning, Online Test">
    <meta name="author" content="CITS">
    
    <title><?= APP_NAME ?> - NSTI Howrah</title>

    <!-- Bootstrap CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - Production Safe Paths -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main_index.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/index-sections.css?v=<?= time() ?>">
    
    <!-- Security Headers -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#667eea">
</head>

<body>

<?php
// Load all page sections
$sections = [
    'navbar',
    'hero',
    'carousel',
    'features',
    'statistics',
    'why-choose',
    'featured-courses',
    'testimonials',
    'cta-section',
    'main-content',
    'footer'
];

foreach ($sections as $section) {
    $section_file = INDEX_SECTIONS . '/' . $section . '.php';
    if (file_exists($section_file)) {
        include $section_file;
    } else {
        // Log missing section but don't break the page
        error_log("Warning: Section file not found: {$section_file}");
    }
}
?>

<!-- ==================== SCRIPTS ==================== -->

<!-- Bootstrap JS from CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (optional, if needed) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Popper.js (included in Bootstrap bundle) -->

<!-- Custom Scripts - Production Safe Paths -->
<script src="<?= BASE_URL ?>/index/js/script.js?v=<?= time() ?>"></script>

<!-- Index-specific scripts -->
<?php
$index_scripts = ['carousel.js', 'index-animations.js'];
foreach ($index_scripts as $script) {
    $script_path = INDEX_JS . '/' . $script;
    if (file_exists($script_path)) {
        echo '<script src="' . BASE_URL . '/index/js/' . $script . '?v=' . time() . '"></script>';
    }
}
?>

<!-- AOS (Animate on Scroll) - Optional, if used -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 800,
        once: true
    });
</script>

<!-- Global error handler for CDN resources -->
<script>
    // Fallback if CDN resources fail
    window.addEventListener('error', function(e) {
        if (e.filename && (e.filename.includes('cdn.jsdelivr.net') || e.filename.includes('cdnjs.cloudflare.com'))) {
            console.warn('CDN resource failed to load:', e.filename);
            // Optionally, load from local fallback or notify user
        }
    });
</script>

</body>
</html>
