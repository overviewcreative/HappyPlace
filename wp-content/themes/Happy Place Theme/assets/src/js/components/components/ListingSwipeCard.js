/**
 * Listing Swipe Card Component
 * 
 * Handles interactive functionality for the flagship listing card component
 * including image carousel, favorite functionality, and swipe gestures.
 */

import { ComponentManager } from '../../utils/ComponentManager';
import { TouchHandler } from '../../utils/TouchHandler';
import { AnalyticsTracker } from '../../utils/AnalyticsTracker';

export class ListingSwipeCard {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            autoplay: false,
            autoplayDelay: 5000,
            enableSwipe: true,
            enableKeyboard: true,
            enableAnalytics: true,
            animationDuration: 300,
            ...options
        };

        this.currentIndex = 0;
        this.images = [];
        this.isAnimating = false;
        this.autoplayTimer = null;
        this.favoriteState = false;

        this.init();
    }

    init() {
        this.setupElements();
        this.setupEventListeners();
        this.setupCarousel();
        this.setupFavorites();
        this.setupAnalytics();
        
        if (this.options.autoplay) {
            this.startAutoplay();
        }

        // Mark as initialized
        this.element.setAttribute('data-initialized', 'true');
    }

    setupElements() {
        // Get all interactive elements
        this.imageContainer = this.element.querySelector('.card-image-carousel');
        this.images = Array.from(this.element.querySelectorAll('.card-image'));
        this.prevBtn = this.element.querySelector('.carousel-prev');
        this.nextBtn = this.element.querySelector('.carousel-next');
        this.indicators = Array.from(this.element.querySelectorAll('.indicator'));
        this.favoriteBtn = this.element.querySelector('.favorite-btn');
        this.actionBtns = Array.from(this.element.querySelectorAll('.action-btn'));

        // Initialize image states
        this.images.forEach((img, index) => {
            img.classList.toggle('active', index === 0);
            img.setAttribute('aria-hidden', index !== 0);
        });

        // Set up ARIA attributes
        this.element.setAttribute('role', 'region');
        this.element.setAttribute('aria-label', 'Property listing card');
        
        if (this.imageContainer) {
            this.imageContainer.setAttribute('role', 'img');
            this.imageContainer.setAttribute('aria-live', 'polite');
        }
    }

    setupEventListeners() {
        // Carousel controls
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prev());
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.next());
        }

        // Indicators
        this.indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => this.goTo(index));
        });

        // Keyboard navigation
        if (this.options.enableKeyboard) {
            this.element.addEventListener('keydown', this.handleKeydown.bind(this));
        }

        // Touch/swipe gestures
        if (this.options.enableSwipe && this.imageContainer) {
            this.touchHandler = new TouchHandler(this.imageContainer, {
                onSwipeLeft: () => this.next(),
                onSwipeRight: () => this.prev(),
                threshold: 50
            });
        }

        // Pause autoplay on hover/focus
        this.element.addEventListener('mouseenter', () => this.pauseAutoplay());
        this.element.addEventListener('mouseleave', () => this.resumeAutoplay());
        this.element.addEventListener('focusin', () => this.pauseAutoplay());
        this.element.addEventListener('focusout', () => this.resumeAutoplay());

        // Lazy loading for images
        this.setupLazyLoading();
    }

    setupCarousel() {
        if (this.images.length <= 1) {
            // Hide controls if only one image
            if (this.prevBtn) this.prevBtn.style.display = 'none';
            if (this.nextBtn) this.nextBtn.style.display = 'none';
            this.indicators.forEach(indicator => indicator.style.display = 'none');
            return;
        }

        this.updateIndicators();
        this.updateAriaLabels();
    }

    setupFavorites() {
        if (!this.favoriteBtn) return;

        // Check initial favorite state
        this.favoriteState = this.favoriteBtn.classList.contains('favorited');
        
        this.favoriteBtn.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleFavorite();
        });

        // Set up ARIA attributes
        this.favoriteBtn.setAttribute('aria-pressed', this.favoriteState);
        this.favoriteBtn.setAttribute('aria-label', 
            this.favoriteState ? 'Remove from favorites' : 'Add to favorites'
        );
    }

    setupAnalytics() {
        if (!this.options.enableAnalytics) return;

        // Track component initialization
        AnalyticsTracker.trackEvent('component_init', {
            component: 'ListingSwipeCard',
            listing_id: this.element.dataset.listingId,
            has_carousel: this.images.length > 1
        });

        // Track action button clicks
        this.actionBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action || btn.textContent.trim();
                AnalyticsTracker.trackEvent('listing_action', {
                    action,
                    listing_id: this.element.dataset.listingId,
                    component: 'ListingSwipeCard'
                });
            });
        });
    }

    setupLazyLoading() {
        this.images.forEach((img, index) => {
            if (index === 0) return; // First image should load immediately

            const src = img.dataset.src || img.src;
            if (src && img.dataset.src) {
                img.src = '';
                img.classList.add('lazy');
            }
        });
    }

    prev() {
        if (this.isAnimating || this.images.length <= 1) return;
        
        const newIndex = this.currentIndex === 0 ? this.images.length - 1 : this.currentIndex - 1;
        this.goTo(newIndex);
    }

    next() {
        if (this.isAnimating || this.images.length <= 1) return;
        
        const newIndex = (this.currentIndex + 1) % this.images.length;
        this.goTo(newIndex);
    }

    goTo(index) {
        if (this.isAnimating || index === this.currentIndex || index < 0 || index >= this.images.length) {
            return;
        }

        this.isAnimating = true;
        const previousIndex = this.currentIndex;
        this.currentIndex = index;

        // Load image if lazy
        this.loadImage(index);

        // Update active states
        this.images[previousIndex].classList.remove('active');
        this.images[this.currentIndex].classList.add('active');

        // Update ARIA attributes
        this.images[previousIndex].setAttribute('aria-hidden', 'true');
        this.images[this.currentIndex].setAttribute('aria-hidden', 'false');

        // Update indicators
        this.updateIndicators();
        this.updateAriaLabels();

        // Track analytics
        if (this.options.enableAnalytics) {
            AnalyticsTracker.trackEvent('carousel_navigate', {
                from_index: previousIndex,
                to_index: this.currentIndex,
                listing_id: this.element.dataset.listingId
            });
        }

        // Reset animation flag
        setTimeout(() => {
            this.isAnimating = false;
        }, this.options.animationDuration);
    }

    loadImage(index) {
        const img = this.images[index];
        if (img && img.classList.contains('lazy') && img.dataset.src) {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            img.addEventListener('load', () => {
                img.classList.add('loaded');
            }, { once: true });
        }
    }

    updateIndicators() {
        this.indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === this.currentIndex);
            indicator.setAttribute('aria-pressed', index === this.currentIndex);
        });
    }

    updateAriaLabels() {
        if (this.imageContainer) {
            this.imageContainer.setAttribute('aria-label', 
                `Image ${this.currentIndex + 1} of ${this.images.length}`
            );
        }

        if (this.prevBtn) {
            this.prevBtn.setAttribute('aria-label', 'Previous image');
        }

        if (this.nextBtn) {
            this.nextBtn.setAttribute('aria-label', 'Next image');
        }
    }

    toggleFavorite() {
        this.favoriteState = !this.favoriteState;
        
        this.favoriteBtn.classList.toggle('favorited', this.favoriteState);
        this.favoriteBtn.setAttribute('aria-pressed', this.favoriteState);
        this.favoriteBtn.setAttribute('aria-label', 
            this.favoriteState ? 'Remove from favorites' : 'Add to favorites'
        );

        // Add heart animation
        const heartIcon = this.favoriteBtn.querySelector('.heart-icon');
        if (heartIcon) {
            heartIcon.style.animation = 'heartBeat 0.3s ease-out';
            setTimeout(() => {
                heartIcon.style.animation = '';
            }, 300);
        }

        // Track analytics
        if (this.options.enableAnalytics) {
            AnalyticsTracker.trackEvent('favorite_toggle', {
                favorited: this.favoriteState,
                listing_id: this.element.dataset.listingId
            });
        }

        // Trigger custom event
        this.element.dispatchEvent(new CustomEvent('favoriteChanged', {
            detail: { 
                favorited: this.favoriteState,
                listingId: this.element.dataset.listingId
            }
        }));
    }

    handleKeydown(event) {
        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                this.prev();
                break;
            case 'ArrowRight':
                event.preventDefault();
                this.next();
                break;
            case 'Home':
                event.preventDefault();
                this.goTo(0);
                break;
            case 'End':
                event.preventDefault();
                this.goTo(this.images.length - 1);
                break;
        }
    }

    startAutoplay() {
        if (this.images.length <= 1) return;
        
        this.autoplayTimer = setInterval(() => {
            this.next();
        }, this.options.autoplayDelay);
    }

    pauseAutoplay() {
        if (this.autoplayTimer) {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    resumeAutoplay() {
        if (this.options.autoplay && !this.autoplayTimer) {
            this.startAutoplay();
        }
    }

    destroy() {
        this.pauseAutoplay();
        
        if (this.touchHandler) {
            this.touchHandler.destroy();
        }

        // Remove event listeners would go here
        // (In a real implementation, we'd store references to bound functions)
        
        this.element.removeAttribute('data-initialized');
    }

    // Static method for auto-initialization
    static init(container = document) {
        const cards = container.querySelectorAll('.listing-swipe-card:not([data-initialized])');
        
        cards.forEach(card => {
            new ListingSwipeCard(card);
        });

        return cards.length;
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ListingSwipeCard.init());
} else {
    ListingSwipeCard.init();
}

// Register with ComponentManager if available
if (typeof ComponentManager !== 'undefined') {
    ComponentManager.register('ListingSwipeCard', ListingSwipeCard);
}
