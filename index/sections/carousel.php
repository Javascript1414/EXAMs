<?php
// Load config if not already loaded
if (!defined('BASE_URL')) {
    $config_file = dirname(__DIR__) . '/../config_infinityfree.php';
    if (!file_exists($config_file)) {
        $config_file = dirname(__DIR__) . '/../config.php';
    }
    require_once $config_file;
}
?>
<!-- PHOTO CAROUSEL SECTION - Production Ready -->
<div class="photo-carousel-section">
<div class="container">
<h3 class="carousel-title">✨ College Moments ✨</h3>
<?php 
// Load carousel photos configuration
$carousel_config = dirname(__DIR__) . '/config/carousel-photos.php';
if (file_exists($carousel_config)) {
    include $carousel_config;
} else {
    // Default empty array if config not found
    $carouselPhotos = [];
}
?>
<div class="carousel-wrapper">
    <div class="carousel-container">
        <div class="carousel-track">
            <?php $photoCount = 0; foreach($carouselPhotos as $photo): $photoCount++; ?>
                <div class="carousel-slide">
                    <img src="<?= BASE_URL ?>/assets/images/<?php echo htmlspecialchars($photo); ?>" alt="College Photo <?= $photoCount ?>">
                    <div class="slide-overlay">
                        <span class="photo-number">Photo <?php echo $photoCount; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Carousel Controls - Bottom -->
    <div class="carousel-controls">
        <button class="carousel-control-btn carousel-btn-prev-bottom" onclick="moveCarousel(-1)">❮ Previous</button>
        <button class="carousel-control-btn carousel-btn-resume" onclick="toggleAutoPlay()" id="resumeBtn">⏸ Pause</button>
        <button class="carousel-control-btn carousel-btn-next-bottom" onclick="moveCarousel(1)">Next ❯</button>
    </div>
</div>
</div>
</div>
