/**
 * Hero Carousel Component - ES6 Module
 * Handles image carousel, favorites, and CTA interactions
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

export default class HeroCarousel {
    constructor(element) {
        this.hero = element;
        this.slides = element.querySelectorAll('.hph-hero__slide');
        this.dots = element.querySelectorAll('.hph-hero__dot');
        this.currentSlide = 0;
        this.isPlaying = true;
        this.interval = null;
        this.intervalDuration = parseInt(element.dataset.interval) || 6000;
        
        // Elements
        this.photoCounter = element.querySelector('.hph-hero__current-photo');
        this.progressBar = element.querySelector('.hph-hero__progress-bar');
        this.playPauseBtn = element.querySelector('[data-action="pause"]');
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        
        if (this.slides.length > 1) {
            this.startAutoplay();
            this.updateProgress();
        }
        
        // Initialize ARIA attributes
        this.updateAccessibility();
        
        // Initialize property stats enhancements
        this.initPropertyStats();
        
        // Store instance on element
        this.hero.heroCarousel = this;
    }
    
    bindEvents() {
        // Navigation buttons
        const prevBtn = this.hero.querySelector('[data-action="prev"]');
        const nextBtn = this.hero.querySelector('[data-action="next"]');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousSlide());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextSlide());
        }
        
        // Play/pause button
        if (this.playPauseBtn) {
            this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        }
        
        // Dot navigation
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(index));
        });
        
        // Keyboard navigation
        this.hero.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Pause on hover (accessibility)
        this.hero.addEventListener('mouseenter', () => this.pauseAutoplay());
        this.hero.addEventListener('mouseleave', () => {
            if (this.isPlaying) {
                this.startAutoplay();
            }
        });
        
        // Touch/swipe support
        this.addTouchSupport();
        
        // CTA buttons
        this.bindCTAEvents();
        
        // Favorites
        this.bindFavoriteEvents();
    }
    
    nextSlide() {
        this.goToSlide((this.currentSlide + 1) % this.slides.length);
    }
    
    previousSlide() {
        this.goToSlide((this.currentSlide - 1 + this.slides.length) % this.slides.length);
    }
    
    goToSlide(index) {
        if (index === this.currentSlide) return;
        
        // Remove active class from current slide and dot
        if (this.slides[this.currentSlide]) {
            this.slides[this.currentSlide].classList.remove('hph-hero__slide--active');
        }
        if (this.dots[this.currentSlide]) {
            this.dots[this.currentSlide].classList.remove('hph-hero__dot--active');
        }
        
        // Set new current slide
        this.currentSlide = index;
        
        // Add active class to new slide and dot
        if (this.slides[this.currentSlide]) {
            this.slides[this.currentSlide].classList.add('hph-hero__slide--active');
        }
        if (this.dots[this.currentSlide]) {
            this.dots[this.currentSlide].classList.add('hph-hero__dot--active');
        }
        
        // Update UI elements
        this.updatePhotoCounter();
        this.updateAccessibility();
        this.resetProgress();
        
        // Announce to screen readers
        this.announceSlideChange();
    }
    
    startAutoplay() {
        if (this.slides.length <= 1) return;
        
        this.pauseAutoplay(); // Clear any existing interval
        this.interval = setInterval(() => {
            this.nextSlide();
        }, this.intervalDuration);
        
        this.updatePlayPauseButton(true);
    }
    
    pauseAutoplay() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
    
    togglePlayPause() {
        this.isPlaying = !this.isPlaying;
        
        if (this.isPlaying) {
            this.startAutoplay();
        } else {
            this.pauseAutoplay();
        }
        
        this.updatePlayPauseButton(this.isPlaying);
    }
    
    updatePlayPauseButton(playing) {
        if (!this.playPauseBtn) return;
        
        const icon = this.playPauseBtn.querySelector('i');
        const text = playing ? 'Pause slideshow' : 'Play slideshow';
        
        this.playPauseBtn.dataset.playing = playing;
        this.playPauseBtn.setAttribute('aria-label', text);
        
        if (icon) {
            icon.className = playing ? 'fas fa-pause hph-icon' : 'fas fa-play hph-icon';
        }
    }
    
    updatePhotoCounter() {
        if (this.photoCounter) {
            this.photoCounter.textContent = this.currentSlide + 1;
        }
    }
    
    updateProgress() {
        if (!this.progressBar || this.slides.length <= 1) return;
        
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += (100 / (this.intervalDuration / 100));
            
            if (progress >= 100 || !this.isPlaying) {
                progress = 100;
                clearInterval(progressInterval);
            }
            
            this.progressBar.style.width = progress + '%';
            this.progressBar.dataset.progress = Math.round(progress);
        }, 100);
    }
    
    resetProgress() {
        if (this.progressBar) {
            this.progressBar.style.width = '0%';
            this.progressBar.dataset.progress = '0';
        }
        this.updateProgress();
    }
    
    updateAccessibility() {
        // Update slide accessibility
        this.slides.forEach((slide, index) => {
            const isActive = index === this.currentSlide;
            slide.setAttribute('aria-hidden', !isActive);
            slide.setAttribute('tabindex', isActive ? '0' : '-1');
        });
        
        // Update dot accessibility
        this.dots.forEach((dot, index) => {
            const isActive = index === this.currentSlide;
            dot.setAttribute('aria-selected', isActive);
            dot.setAttribute('tabindex', isActive ? '0' : '-1');
        });
    }
    
    announceSlideChange() {
        // Create or update live region for screen readers
        let liveRegion = document.getElementById('hero-live-region');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'hero-live-region';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.style.position = 'absolute';
            liveRegion.style.left = '-10000px';
            liveRegion.style.width = '1px';
            liveRegion.style.height = '1px';
            liveRegion.style.overflow = 'hidden';
            document.body.appendChild(liveRegion);
        }
        
        liveRegion.textContent = `Photo ${this.currentSlide + 1} of ${this.slides.length}`;
    }
    
    handleKeydown(event) {
        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                this.previousSlide();
                break;
            case 'ArrowRight':
                event.preventDefault();
                this.nextSlide();
                break;
            case ' ':
                event.preventDefault();
                this.togglePlayPause();
                break;
            case 'Home':
                event.preventDefault();
                this.goToSlide(0);
                break;
            case 'End':
                event.preventDefault();
                this.goToSlide(this.slides.length - 1);
                break;
        }
    }
    
    addTouchSupport() {
        let startX = 0;
        let startY = 0;
        let distX = 0;
        let distY = 0;
        let threshold = 100; // Minimum distance for swipe
        let restraint = 100; // Maximum distance perpendicular to swipe direction
        
        this.hero.addEventListener('touchstart', (e) => {
            const touchobj = e.changedTouches[0];
            startX = touchobj.pageX;
            startY = touchobj.pageY;
        }, { passive: true });
        
        this.hero.addEventListener('touchend', (e) => {
            const touchobj = e.changedTouches[0];
            distX = touchobj.pageX - startX;
            distY = touchobj.pageY - startY;
            
            if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
                if (distX > 0) {
                    this.previousSlide();
                } else {
                    this.nextSlide();
                }
            }
        }, { passive: true });
    }
    
    bindCTAEvents() {
        // Schedule Tour button
        const scheduleTourBtn = this.hero.querySelector('[data-action="schedule-tour"]');
        if (scheduleTourBtn) {
            scheduleTourBtn.addEventListener('click', (e) => {
                this.handleScheduleTour(e);
            });
        }
        
        // View Gallery button
        const viewGalleryBtn = this.hero.querySelector('[data-action="view-gallery"]');
        if (viewGalleryBtn) {
            viewGalleryBtn.addEventListener('click', (e) => {
                this.handleViewGallery(e);
            });
        }
        
        // Share button
        const shareBtn = this.hero.querySelector('[data-action="share-listing"]');
        if (shareBtn) {
            shareBtn.addEventListener('click', (e) => {
                this.handleShareListing(e);
            });
        }
    }
    
    bindFavoriteEvents() {
        const favoriteBtn = this.hero.querySelector('[data-action="toggle-favorite"]');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', (e) => {
                this.handleToggleFavorite(e);
            });
        }
    }
    
    handleScheduleTour(event) {
        const button = event.currentTarget;
        const listingId = button.dataset.listingId;
        
        // Trigger custom event for tour scheduling
        const tourEvent = new CustomEvent('hph:schedule-tour', {
            detail: { listingId, button },
            bubbles: true
        });
        
        this.hero.dispatchEvent(tourEvent);
        
        // Default behavior - could open modal, redirect, etc.
        console.log('Schedule tour for listing:', listingId);
    }
    
    handleViewGallery(event) {
        const button = event.currentTarget;
        const photoCount = button.dataset.photos;
        
        // Trigger custom event for gallery viewing
        const galleryEvent = new CustomEvent('hph:view-gallery', {
            detail: { photoCount, currentSlide: this.currentSlide, button },
            bubbles: true
        });
        
        this.hero.dispatchEvent(galleryEvent);
        
        // Default behavior - could open lightbox, navigate to gallery page, etc.
        console.log('View gallery with', photoCount, 'photos, starting at slide', this.currentSlide);
    }
    
    handleShareListing(event) {
        const button = event.currentTarget;
        const listingId = button.dataset.listingId;
        const listingUrl = window.location.href;
        const listingTitle = document.title;
        
        // Check if the Web Share API is supported
        if (navigator.share) {
            navigator.share({
                title: listingTitle,
                text: 'Check out this amazing property!',
                url: listingUrl,
            }).then(() => {
                console.log('Listing shared successfully');
                this.showToast('Listing shared successfully!');
            }).catch((error) => {
                console.log('Error sharing listing:', error);
                this.fallbackShare(listingUrl, listingTitle);
            });
        } else {
            // Fallback to clipboard copy
            this.fallbackShare(listingUrl, listingTitle);
        }
        
        // Trigger custom event for share tracking
        const shareEvent = new CustomEvent('hph:share-listing', {
            detail: { listingId, url: listingUrl, title: listingTitle, button },
            bubbles: true
        });
        
        this.hero.dispatchEvent(shareEvent);
    }
    
    fallbackShare(url, title) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(() => {
                this.showToast('Link copied to clipboard!');
            }).catch(() => {
                this.legacyShare(url, title);
            });
        } else {
            this.legacyShare(url, title);
        }
    }
    
    legacyShare(url, title) {
        // Create a temporary input element to copy the URL
        const tempInput = document.createElement('input');
        tempInput.value = url;
        document.body.appendChild(tempInput);
        tempInput.select();
        tempInput.setSelectionRange(0, 99999); // For mobile devices
        
        try {
            document.execCommand('copy');
            this.showToast('Link copied to clipboard!');
        } catch (err) {
            // Last resort - show a modal with the URL
            this.showShareModal(url, title);
        }
        
        document.body.removeChild(tempInput);
    }
    
    showShareModal(url, title) {
        const modal = document.createElement('div');
        modal.className = 'hph-share-modal';
        modal.innerHTML = `
            <div class="hph-share-modal__backdrop"></div>
            <div class="hph-share-modal__content">
                <h3>Share this listing</h3>
                <p>Copy this link to share:</p>
                <input type="text" value="${url}" readonly>
                <div class="hph-share-modal__actions">
                    <button class="hph-btn hph-btn--primary" onclick="this.closest('.hph-share-modal').remove()">Close</button>
                </div>
            </div>
        `;
        
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `;
        
        const backdrop = modal.querySelector('.hph-share-modal__backdrop');
        backdrop.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        `;
        
        const content = modal.querySelector('.hph-share-modal__content');
        content.style.cssText = `
            background: white;
            padding: 24px;
            border-radius: 8px;
            max-width: 400px;
            width: 90%;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        `;
        
        const input = modal.querySelector('input');
        input.style.cssText = `
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 12px 0;
            font-family: monospace;
        `;
        
        // Close on backdrop click
        backdrop.addEventListener('click', () => modal.remove());
        
        // Select URL on input click
        input.addEventListener('click', () => input.select());
        
        document.body.appendChild(modal);
        input.focus();
        input.select();
    }
    
    async handleToggleFavorite(event) {
        const button = event.currentTarget;
        const listingId = button.dataset.listingId;
        const icon = button.querySelector('i');
        const text = button.querySelector('.sr-only');
        
        // Prevent double-clicks
        if (button.disabled) return;
        button.disabled = true;
        
        // Optimistic UI update
        const wasFavorite = button.classList.contains('is-favorite');
        button.classList.toggle('is-favorite');
        
        if (icon) {
            icon.className = wasFavorite ? 'far fa-heart hph-icon' : 'fas fa-heart hph-icon';
        }
        
        if (text) {
            text.textContent = wasFavorite ? 'Save' : 'Saved';
        }
        
        button.setAttribute('aria-label', wasFavorite ? 'Add to favorites' : 'Remove from favorites');
        
        try {
            // Use the global hphHero variable that's now properly localized
            const response = await fetch(window.hphHero?.ajaxUrl || window.hphAjax?.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hph_toggle_favorite',
                    listing_id: listingId,
                    nonce: window.hphHero?.nonce || window.hphAjax?.nonce
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                // Revert optimistic update on failure
                this.revertFavoriteUI(button, icon, text, wasFavorite);
                console.error('Failed to toggle favorite:', data.data);
            } else {
                // Show success message
                const message = wasFavorite 
                    ? (window.hphHero?.strings?.favoriteRemoved || 'Removed from favorites')
                    : (window.hphHero?.strings?.favoriteAdded || 'Added to favorites');
                this.showToast(message);
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            // Revert optimistic update on error
            this.revertFavoriteUI(button, icon, text, wasFavorite);
        }
        
        button.disabled = false;
    }
    
    revertFavoriteUI(button, icon, text, wasFavorite) {
        button.classList.toggle('is-favorite');
        if (icon) {
            icon.className = wasFavorite ? 'fas fa-heart hph-icon' : 'far fa-heart hph-icon';
        }
        if (text) {
            text.textContent = wasFavorite ? 'Saved' : 'Save';
        }
        button.setAttribute('aria-label', wasFavorite ? 'Remove from favorites' : 'Add to favorites');
    }
    
    showToast(message, type = 'success') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `hph-toast hph-toast--${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}" aria-hidden="true"></i>
            <span>${message}</span>
        `;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--hph-color-${type === 'success' ? 'success' : 'danger'});
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        });
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Initialize property stats enhancements
     * Adds interactive features for lot size and other property data
     */
    initPropertyStats() {
        const stats = this.hero.querySelectorAll('.hph-hero__stat, .hph-quick-fact');
        
        stats.forEach(stat => {
            // Add hover effects for better UX
            stat.addEventListener('mouseenter', () => {
                stat.style.transform = 'scale(1.05)';
                stat.style.transition = 'transform 0.2s ease';
            });
            
            stat.addEventListener('mouseleave', () => {
                stat.style.transform = 'scale(1)';
            });
            
            // Enhanced accessibility for lot size
            const lotSizeStat = stat.querySelector('[data-stat="lot_size"]') || 
                              (stat.dataset.stat === 'lot_size' ? stat : null);
            
            if (lotSizeStat) {
                const value = lotSizeStat.querySelector('.hph-hero__stat-value, .hph-quick-fact__value');
                if (value && value.textContent.includes('acres')) {
                    // Add tooltip for acres to square feet conversion
                    const acres = parseFloat(value.textContent);
                    if (!isNaN(acres)) {
                        const sqft = Math.round(acres * 43560);
                        lotSizeStat.title = `${acres} acres (approximately ${sqft.toLocaleString()} sq ft)`;
                    }
                }
            }
        });
    }
    
    destroy() {
        this.pauseAutoplay();
        // Remove event listeners if needed
    }
}

// Auto-initialize hero carousels
export function initHeroCarousels() {
    const heroes = document.querySelectorAll('.hph-hero[data-component="hero"]');
    
    heroes.forEach(hero => {
        if (!hero.heroCarousel) {
            new HeroCarousel(hero);
        }
    });
}

// Support for dynamically added heroes
export function observeHeroCarousels() {
    const heroObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    const heroes = node.matches?.('.hph-hero[data-component="hero"]') 
                        ? [node] 
                        : node.querySelectorAll?.('.hph-hero[data-component="hero"]') || [];
                    
                    heroes.forEach(hero => {
                        if (!hero.heroCarousel) {
                            new HeroCarousel(hero);
                        }
                    });
                }
            });
        });
    });

    heroObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    return heroObserver;
}

// Make HeroCarousel available globally for debugging and manual initialization
if (typeof window !== 'undefined') {
    window.HeroCarousel = HeroCarousel;
}
