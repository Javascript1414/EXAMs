<!-- PHOTO CAROUSEL SECTION -->
<div class="photo-carousel-section">
<div class="container">
<h3 class="carousel-title">✨ College Moments ✨</h3>
<?php include __DIR__ . '/../config/carousel-photos.php'; ?>
<div class="carousel-wrapper">
    <div class="carousel-container">
        <div class="carousel-track">
            <?php $photoCount = 0; foreach($carouselPhotos as $photo): $photoCount++; ?>
                <div class="carousel-slide">
                    <img src="http://localhost/exams/assets/images/<?php echo htmlspecialchars($photo); ?>" alt="College Photo">
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
