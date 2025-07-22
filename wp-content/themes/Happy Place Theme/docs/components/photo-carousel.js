export class PhotoCarousel {
    constructor(element, options = {}) {
        this.element = element;
        this.images = element.querySelectorAll('.carousel-image');
        this.dots = element.querySelectorAll('.carousel-dot');
        this.currentIndex = 0;
        this.autoPlay = options.autoPlay !== false;
        this.interval = options.interval || 5000;
        
        this.init();
    }

    init() {
        if (this.images.length <= 1) return;
        
        this.bindEvents();
        if (this.autoPlay) {
            this.startAutoPlay();
        }
        
        // Store reference for global access
        this.element.photoCarousel = this;
    }

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
        this.updateDisplay();
    }

    previous() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
        this.updateDisplay();
    }

    goTo(index) {
        this.currentIndex = index;
        this.updateDisplay();
    }

    updateDisplay() {
        // Update images
        this.images.forEach((img, index) => {
            img.classList.toggle('active', index === this.currentIndex);
        });
        
        // Update dots
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === this.currentIndex);
        });
    }

    bindEvents() {
        // Dot navigation
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goTo(index));
        });
        
        // Keyboard navigation
        this.element.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.previous();
            if (e.key === 'ArrowRight') this.next();
        });
        
        // Touch/swipe support
        let touchStartX = null;
        this.element.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
        });
        
        this.element.addEventListener('touchend', (e) => {
            if (!touchStartX) return;
            
            const touchEndX = e.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            
            if (Math.abs(diff) > 50) { // Minimum swipe distance
                if (diff > 0) {
                    this.next();
                } else {
                    this.previous();
                }
            }
            touchStartX = null;
        });
    }

    startAutoPlay() {
        this.autoPlayTimer = setInterval(() => {
            this.next();
        }, this.interval);
        
        // Pause on hover
        this.element.addEventListener('mouseenter', () => {
            clearInterval(this.autoPlayTimer);
        });
        
        this.element.addEventListener('mouseleave', () => {
            this.startAutoPlay();
        });
    }
}