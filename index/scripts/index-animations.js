// ==========================================
// STATISTICS COUNTER ANIMATION
// ==========================================
function animateNumbers() {
    const numberElements = document.querySelectorAll('.stat-number');
    
    numberElements.forEach(element => {
        const target = parseInt(element.getAttribute('data-target'));
        const increment = target / 50; // 50 steps of animation
        let current = 0;
        
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                element.textContent = target;
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current);
            }
        }, 30);
    });
}

// ==========================================
// TESTIMONIALS CAROUSEL
// ==========================================
let testimonialIndex = 0;

function slideTestimonials(direction) {
    const testimonials = document.querySelectorAll('.testimonial-card');
    const container = document.querySelector('.testimonials-carousel');
    
    testimonialIndex += direction;
    
    if (testimonialIndex >= testimonials.length) {
        testimonialIndex = 0;
    } else if (testimonialIndex < 0) {
        testimonialIndex = testimonials.length - 1;
    }
    
    // Update carousel position
    const scrollAmount = testimonialIndex * (testimonials[0].offsetWidth + 30);
    container.style.transform = `translateX(-${scrollAmount}px)`;
}

// Auto-slide testimonials every 5 seconds
setInterval(() => {
    slideTestimonials(1);
}, 5000);

// ==========================================
// SCROLL ANIMATIONS
// ==========================================
function handleScrollAnimations() {
    const elements = document.querySelectorAll('[class*="section"]');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Trigger number animation when statistics section is visible
                if (entry.target.classList.contains('statistics-section')) {
                    animateNumbers();
                }
                
                entry.target.style.opacity = '1';
            }
        });
    }, {
        threshold: 0.1
    });
    
    elements.forEach(element => observer.observe(element));
}

// ==========================================
// SMOOTH SCROLL BEHAVIOR
// ==========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ==========================================
// PARALLAX EFFECT (Optional)
// ==========================================
window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallaxElements = document.querySelectorAll('.statistics-section, .why-choose-section');
    
    parallaxElements.forEach(element => {
        element.style.backgroundPosition = `0 ${scrolled * 0.5}px`;
    });
});

// ==========================================
// INITIALIZE ON DOM READY
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    // Add scroll animations
    handleScrollAnimations();
    
    // Add fade-in effect to sections
    const sections = document.querySelectorAll('[class*="section"]');
    sections.forEach((section, index) => {
        section.style.opacity = '0';
        section.style.transition = 'opacity 0.6s ease';
        
        // Stagger the fade-in
        setTimeout(() => {
            section.style.opacity = '1';
        }, index * 100);
    });
    
    // Button hover effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});

// ==========================================
// RESPONSIVE TESTIMONIALS (Mobile)
// ==========================================
if (window.innerWidth < 768) {
    document.querySelector('.testimonials-carousel').style.overflowX = 'auto';
    document.querySelector('.testimonials-carousel').style.scrollSnapType = 'x mandatory';
    
    document.querySelectorAll('.testimonial-card').forEach(card => {
        card.style.scrollSnapAlign = 'start';
    });
}

// ==========================================
// FEATURE: CLICK OUTSIDE TO CLOSE
// ==========================================
document.addEventListener('click', (e) => {
    // You can add click-outside handlers here if needed
});
