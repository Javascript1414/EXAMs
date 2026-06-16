// Carousel JavaScript - Continuous Auto-Rotation with Enhanced Features
document.addEventListener('DOMContentLoaded', function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.carousel-slide');
    const totalSlides = slides.length;
    const slidesPerView = 2;
    let autoPlayInterval = null;
    let isAutoPlayPaused = false;

    function updateSlideIndicator() {
        const currentSlideNum = document.getElementById('currentSlide');
        if (currentSlideNum) {
            currentSlideNum.textContent = Math.floor(currentSlide / slidesPerView) + 1;
        }
    }

    function showSlide(n) {
        const track = document.querySelector('.carousel-track');
        if (!track) return;
        
        if (n + slidesPerView > totalSlides) {
            currentSlide = 0; // Loop back to start
        } else if (n < 0) {
            currentSlide = totalSlides - slidesPerView;
        } else {
            currentSlide = n;
        }
        track.style.transform = `translateX(-${currentSlide * 50}%)`;
        updateSlideIndicator();
    }

    function moveCarousel(direction) {
        showSlide(currentSlide + direction);
    }

    function autoPlay() {
        if (!isAutoPlayPaused) {
            showSlide(currentSlide + 1);
        }
    }

    function startAutoPlay() {
        // Clear any existing interval
        if (autoPlayInterval) clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(autoPlay, 3500); // Auto change every 3.5 seconds
    }

    function toggleAutoPlay() {
        const resumeBtn = document.getElementById('resumeBtn');
        if (!resumeBtn) return;
        
        if (isAutoPlayPaused) {
            isAutoPlayPaused = false;
            resumeBtn.textContent = '⏸ Pause';
            resumeBtn.style.background = 'linear-gradient(135deg, #1abc9c 0%, #17a589 100%)';
        } else {
            isAutoPlayPaused = true;
            resumeBtn.textContent = '▶ Resume';
            resumeBtn.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #d63031 100%)';
        }
    }

    // Make functions globally available
    window.moveCarousel = moveCarousel;
    window.toggleAutoPlay = toggleAutoPlay;

    // Start auto-play immediately when page loads
    startAutoPlay();
    updateSlideIndicator();
    
    const resumeBtn = document.getElementById('resumeBtn');
    if (resumeBtn) {
        resumeBtn.textContent = '⏸ Pause';
        resumeBtn.style.background = 'linear-gradient(135deg, #1abc9c 0%, #17a589 100%)';
    }

    // Pause on hover, resume on leave
    const carouselContainer = document.querySelector('.carousel-container');
    if (carouselContainer) {
        carouselContainer.addEventListener('mouseenter', function() {
            if (!isAutoPlayPaused) {
                isAutoPlayPaused = true;
                const btn = document.getElementById('resumeBtn');
                if (btn) {
                    btn.textContent = '▶ Resume';
                    btn.style.background = 'linear-gradient(135deg, #ff6b6b 0%, #d63031 100%)';
                }
            }
        });
        
        carouselContainer.addEventListener('mouseleave', function() {
            const btn = document.getElementById('resumeBtn');
            if (isAutoPlayPaused && btn && btn.textContent.includes('Resume')) {
                isAutoPlayPaused = false;
                btn.textContent = '⏸ Pause';
                btn.style.background = 'linear-gradient(135deg, #1abc9c 0%, #17a589 100%)';
            }
        });
    }
});

