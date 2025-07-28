/**
 * Testimonials Slider Component
 * 
 * Handles testimonial slider functionality for the HPH shortcode system
 */

class TestimonialsSlider {
    constructor(container) {
        this.container = container;
        this.slides = container.querySelectorAll('.testimonial-slide');
        this.prevButton = container.querySelector('.testimonials-prev');
        this.nextButton = container.querySelector('.testimonials-next');
        this.dotsContainer = container.querySelector('.testimonials-dots');
        
        this.currentSlide = 0;
        this.autoplay = container.dataset.autoplay === 'true';
        this.autoplaySpeed = parseInt(container.dataset.autoplaySpeed) || 5000;
        this.autoplayTimer = null;
        
        this.init();
    }
    
    init() {
        if (this.slides.length <= 1) return;
        
        this.createDots();
        this.bindEvents();
        this.showSlide(0);
        
        if (this.autoplay) {
            this.startAutoplay();
        }
    }
    
    createDots() {
        if (!this.dotsContainer) return;
        
        this.dotsContainer.innerHTML = '';
        
        this.slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.className = 'testimonial-dot';
            dot.setAttribute('aria-label', `Go to testimonial ${index + 1}`);
            dot.addEventListener('click', () => this.goToSlide(index));
            this.dotsContainer.appendChild(dot);
        });
        
        this.dots = this.dotsContainer.querySelectorAll('.testimonial-dot');
    }
    
    bindEvents() {
        if (this.prevButton) {
            this.prevButton.addEventListener('click', () => this.previousSlide());
        }
        
        if (this.nextButton) {
            this.nextButton.addEventListener('click', () => this.nextSlide());
        }
        
        // Pause autoplay on hover
        if (this.autoplay) {
            this.container.addEventListener('mouseenter', () => this.pauseAutoplay());
            this.container.addEventListener('mouseleave', () => this.startAutoplay());
        }
        
        // Keyboard navigation
        this.container.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'ArrowLeft':
                    e.preventDefault();
                    this.previousSlide();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    this.nextSlide();
                    break;
            }
        });
        
        // Touch/swipe support
        this.addTouchSupport();
    }
    
    addTouchSupport() {
        let startX = 0;
        let startY = 0;
        let threshold = 50;
        
        this.container.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });
        
        this.container.addEventListener('touchend', (e) => {
            if (!startX || !startY) return;
            
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // Only handle horizontal swipes
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > threshold) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.previousSlide();
                }
            }
            
            startX = 0;
            startY = 0;
        }, { passive: true });
    }
    
    showSlide(index) {
        // Hide all slides
        this.slides.forEach(slide => {
            slide.classList.remove('active');
            slide.style.display = 'none';
        });
        
        // Show current slide
        if (this.slides[index]) {
            this.slides[index].classList.add('active');
            this.slides[index].style.display = 'block';
        }
        
        // Update dots
        if (this.dots) {
            this.dots.forEach(dot => dot.classList.remove('active'));
            if (this.dots[index]) {
                this.dots[index].classList.add('active');
            }
        }
        
        this.currentSlide = index;
    }
    
    nextSlide() {
        const nextIndex = (this.currentSlide + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }
    
    previousSlide() {
        const prevIndex = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }
    
    goToSlide(index) {
        if (index >= 0 && index < this.slides.length && index !== this.currentSlide) {
            this.showSlide(index);
            
            // Reset autoplay
            if (this.autoplay) {
                this.pauseAutoplay();
                this.startAutoplay();
            }
        }
    }
    
    startAutoplay() {
        if (!this.autoplay || this.slides.length <= 1) return;
        
        this.pauseAutoplay();
        this.autoplayTimer = setInterval(() => {
            this.nextSlide();
        }, this.autoplaySpeed);
    }
    
    pauseAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }
    
    destroy() {
        this.pauseAutoplay();
        
        // Remove event listeners
        if (this.prevButton) {
            this.prevButton.removeEventListener('click', this.previousSlide);
        }
        
        if (this.nextButton) {
            this.nextButton.removeEventListener('click', this.nextSlide);
        }
        
        if (this.dots) {
            this.dots.forEach(dot => {
                dot.removeEventListener('click', this.goToSlide);
            });
        }
    }
}

/**
 * Initialize all testimonials sliders on the page
 */
function initTestimonialsSliders() {
    const testimonialSections = document.querySelectorAll('[data-component="testimonials"]');
    
    testimonialSections.forEach(section => {
        if (section.classList.contains('testimonials-slider')) {
            new TestimonialsSlider(section);
        }
    });
}

/**
 * Auto-initialize when DOM is ready
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTestimonialsSliders);
} else {
    initTestimonialsSliders();
}

/**
 * Re-initialize on dynamic content changes
 */
document.addEventListener('shortcodeContentUpdated', initTestimonialsSliders);

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { TestimonialsSlider, initTestimonialsSliders };
}
