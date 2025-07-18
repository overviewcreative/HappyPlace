/**
 * Happy Place Swipe Card JavaScript
 * 
 * @package HappyPlace
 */

class HPHSwipeCard {
    constructor(cardElement) {
        this.card = cardElement;
        this.currentSection = 0;
        this.totalSections = parseInt(this.card.dataset.totalSections) || 1;
        this.listingId = this.card.dataset.listingId;
        
        // Get elements
        this.indicators = this.card.querySelectorAll('.hph-indicator');
        this.infoSections = this.card.querySelectorAll('.hph-info-section');
        this.images = this.card.querySelectorAll('.hph-card-image');
        this.navBtns = {
            prev: this.card.querySelector('.hph-nav-btn--prev'),
            next: this.card.querySelector('.hph-nav-btn--next')
        };
        this.touchAreas = {
            left: this.card.querySelector('.hph-touch-area--left'),
            right: this.card.querySelector('.hph-touch-area--right')
        };
        
        // Touch/swipe variables
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.touchStartY = 0;
        this.touchEndY = 0;
        this.isAnimating = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateDisplay();
        
        // Auto-advance functionality
        this.setupAutoAdvance();
        
        // Initialize favorite state
        this.initializeFavoriteState();
    }
    
    bindEvents() {
        // Navigation buttons
        if (this.navBtns.prev) {
            this.navBtns.prev.addEventListener('click', (e) => {
                e.preventDefault();
                this.previousSection();
            });
        }
        
        if (this.navBtns.next) {
            this.navBtns.next.addEventListener('click', (e) => {
                e.preventDefault();
                this.nextSection();
            });
        }
        
        // Touch areas for mobile
        if (this.touchAreas.left) {
            this.touchAreas.left.addEventListener('click', (e) => {
                e.preventDefault();
                this.previousSection();
            });
        }
        
        if (this.touchAreas.right) {
            this.touchAreas.right.addEventListener('click', (e) => {
                e.preventDefault();
                this.nextSection();
            });
        }
        
        // Swipe gestures
        this.card.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.card.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        
        // Mouse drag for desktop
        this.card.addEventListener('mousedown', this.handleMouseDown.bind(this));
        
        // Keyboard navigation
        this.card.addEventListener('keydown', this.handleKeyDown.bind(this));
        
        // Action buttons
        this.bindActionButtons();
        
        // Quick action buttons
        this.bindQuickActionButtons();
        
        // Prevent context menu on long press
        this.card.addEventListener('contextmenu', (e) => {
            if (this.isAnimating) {
                e.preventDefault();
            }
        });
        
        // Reset auto-advance on user interaction
        ['click', 'touchstart', 'keydown'].forEach(event => {
            this.card.addEventListener(event, this.resetAutoAdvance.bind(this));
        });
    }
    
    bindActionButtons() {
        // Favorite button
        const favoriteBtn = this.card.querySelector('.hph-favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleFavorite();
            });
        }
        
        // Share button
        const shareBtn = this.card.querySelector('.hph-share-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.shareProperty();
            });
        }
    }
    
    bindQuickActionButtons() {
        // Save/favorite buttons
        const saveBtns = this.card.querySelectorAll('.hph-save-btn');
        saveBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggleFavorite();
            });
        });
        
        // RSVP buttons
        const rsvpBtns = this.card.querySelectorAll('.hph-openhouse-rsvp-btn');
        rsvpBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleOpenHouseRSVP(btn.dataset.openhouseId);
            });
        });
        
        // Other interactive buttons
        const interactiveBtns = this.card.querySelectorAll('.hph-quick-action-btn:not([href]), .hph-contact-btn:not([href])');
        interactiveBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.handleButtonClick(btn);
            });
        });
    }
    
    handleTouchStart(e) {
        this.touchStartX = e.changedTouches[0].screenX;
        this.touchStartY = e.changedTouches[0].screenY;
    }
    
    handleTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].screenX;
        this.touchEndY = e.changedTouches[0].screenY;
        this.handleSwipe();
    }
    
    handleMouseDown(e) {
        if (e.button !== 0) return; // Only left mouse button
        
        this.touchStartX = e.clientX;
        this.touchStartY = e.clientY;
        
        const handleMouseMove = (e) => {
            this.touchEndX = e.clientX;
            this.touchEndY = e.clientY;
        };
        
        const handleMouseUp = () => {
            this.handleSwipe();
            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        };
        
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    }
    
    handleSwipe() {
        const swipeThreshold = 50;
        const swipeDistance = this.touchEndX - this.touchStartX;
        const verticalDistance = Math.abs(this.touchEndY - this.touchStartY);
        
        // Only register horizontal swipes
        if (Math.abs(swipeDistance) > swipeThreshold && verticalDistance < 100) {
            if (swipeDistance > 0) {
                this.previousSection();
            } else {
                this.nextSection();
            }
        }
    }
    
    handleKeyDown(e) {
        if (e.target !== this.card && !this.card.contains(e.target)) return;
        
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                this.previousSection();
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.nextSection();
                break;
            case 'Home':
                e.preventDefault();
                this.goToSection(0);
                break;
            case 'End':
                e.preventDefault();
                this.goToSection(this.totalSections - 1);
                break;
        }
    }
    
    nextSection() {
        if (this.isAnimating) return;
        
        const nextSection = (this.currentSection + 1) % this.totalSections;
        this.goToSection(nextSection);
        this.addClickAnimation();
    }
    
    previousSection() {
        if (this.isAnimating) return;
        
        const prevSection = (this.currentSection - 1 + this.totalSections) % this.totalSections;
        this.goToSection(prevSection);
        this.addClickAnimation();
    }
    
    goToSection(sectionIndex) {
        if (this.isAnimating || sectionIndex === this.currentSection) return;
        
        this.isAnimating = true;
        this.currentSection = sectionIndex;
        this.updateDisplay();
        
        // Reset animation flag after transition
        setTimeout(() => {
            this.isAnimating = false;
        }, 300);
    }
    
    updateDisplay() {
        // Update indicators
        this.indicators.forEach((indicator, index) => {
            indicator.classList.toggle('hph-indicator--active', index === this.currentSection);
        });
        
        // Update info sections
        this.infoSections.forEach((section, index) => {
            section.classList.toggle('hph-info-section--active', index === this.currentSection);
        });
        
        // Update images (if multiple images per section)
        if (this.images.length > 1) {
            this.images.forEach((image, index) => {
                image.classList.toggle('hph-card-image--active', index === this.currentSection);
            });
        }
        
        // Update navigation button states
        this.updateNavigationState();
        
        // Trigger custom event
        this.card.dispatchEvent(new CustomEvent('hph:sectionChanged', {
            detail: {
                currentSection: this.currentSection,
                totalSections: this.totalSections,
                listingId: this.listingId
            }
        }));
    }
    
    updateNavigationState() {
        // Enable/disable navigation buttons based on current position
        if (this.navBtns.prev) {
            this.navBtns.prev.style.opacity = this.currentSection === 0 ? '0.5' : '1';
        }
        
        if (this.navBtns.next) {
            this.navBtns.next.style.opacity = this.currentSection === this.totalSections - 1 ? '0.5' : '1';
        }
    }
    
    addClickAnimation() {
        this.card.style.transform = 'scale(0.99)';
        setTimeout(() => {
            this.card.style.transform = '';
        }, 100);
    }
    
    // Auto-advance functionality
    setupAutoAdvance() {
        this.autoAdvanceInterval = null;
        this.autoAdvanceDelay = 5000; // 20 seconds
        this.inactivityTimer = null;
        
        this.startAutoAdvanceTimer();
    }
    
    startAutoAdvanceTimer() {
        this.stopAutoAdvance();
        
        this.inactivityTimer = setTimeout(() => {
            this.startAutoAdvance();
        }, 3000);
    }
    
    startAutoAdvance() {
        this.autoAdvanceInterval = setInterval(() => {
            this.nextSection();
        }, this.autoAdvanceDelay);
    }
    
    stopAutoAdvance() {
        if (this.autoAdvanceInterval) {
            clearInterval(this.autoAdvanceInterval);
            this.autoAdvanceInterval = null;
        }
    }
    
    resetAutoAdvance() {
        this.stopAutoAdvance();
        clearTimeout(this.inactivityTimer);
        this.startAutoAdvanceTimer();
    }
    
    // Favorite functionality
    initializeFavoriteState() {
        const favoriteBtn = this.card.querySelector('.hph-favorite-btn');
        if (!favoriteBtn) return;
        
        // Check if property is already favorited
        const favorites = this.getFavorites();
        const isFavorited = favorites.includes(this.listingId);
        
        favoriteBtn.classList.toggle('hph-favorite-btn--active', isFavorited);
        
        // Update save buttons as well
        const saveBtns = this.card.querySelectorAll('.hph-save-btn');
        saveBtns.forEach(btn => {
            btn.classList.toggle('hph-save-btn--active', isFavorited);
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = isFavorited ? 'fas fa-heart' : 'far fa-heart';
            }
        });
    }
    
    toggleFavorite() {
        const favorites = this.getFavorites();
        const isFavorited = favorites.includes(this.listingId);
        
        if (isFavorited) {
            this.removeFavorite();
        } else {
            this.addFavorite();
        }
        
        this.initializeFavoriteState();
    }
    
    addFavorite() {
        const favorites = this.getFavorites();
        if (!favorites.includes(this.listingId)) {
            favorites.push(this.listingId);
            this.saveFavorites(favorites);
            this.showMessage('Added to favorites!');
            
            // Send to server if logged in
            this.syncFavoriteToServer('add');
        }
    }
    
    removeFavorite() {
        const favorites = this.getFavorites();
        const index = favorites.indexOf(this.listingId);
        if (index > -1) {
            favorites.splice(index, 1);
            this.saveFavorites(favorites);
            this.showMessage('Removed from favorites');
            
            // Send to server if logged in
            this.syncFavoriteToServer('remove');
        }
    }
    
    getFavorites() {
        const stored = localStorage.getItem('hph_favorites');
        return stored ? JSON.parse(stored) : [];
    }
    
    saveFavorites(favorites) {
        localStorage.setItem('hph_favorites', JSON.stringify(favorites));
    }
    
    syncFavoriteToServer(action) {
        // Only sync if user is logged in and we have WordPress AJAX
        if (typeof wp === 'undefined' || !wp.ajax) return;
        
        wp.ajax.post('hph_toggle_favorite', {
            listing_id: this.listingId,
            action_type: action,
            nonce: wp.nonce || ''
        }).catch(error => {
            console.warn('Failed to sync favorite to server:', error);
        });
    }
    
    // Share functionality
    shareProperty() {
        const propertyTitle = this.card.querySelector('.hph-property-title').textContent;
        const propertyPrice = this.card.querySelector('.hph-property-price')?.textContent || '';
        const shareData = {
            title: `${propertyTitle} - ${propertyPrice}`,
            text: `Check out this beautiful property: ${propertyTitle}`,
            url: window.location.href
        };
        
        if (navigator.share) {
            navigator.share(shareData).catch(error => {
                console.log('Error sharing:', error);
                this.fallbackShare(shareData);
            });
        } else {
            this.fallbackShare(shareData);
        }
    }
    
    fallbackShare(shareData) {
        // Copy URL to clipboard
        if (navigator.clipboard) {
            navigator.clipboard.writeText(shareData.url).then(() => {
                this.showMessage('Link copied to clipboard!');
            }).catch(() => {
                this.showMessage('Unable to copy link');
            });
        } else {
            this.showMessage('Share functionality not available');
        }
    }
    
    // Open house RSVP
    handleOpenHouseRSVP(openHouseId) {
        if (!openHouseId) return;
        
        this.showMessage('RSVP functionality would go here');
        
        // Here you would typically open a modal or redirect to RSVP form
        // For now, just simulate the action
        if (typeof wp !== 'undefined' && wp.ajax) {
            wp.ajax.post('hph_openhouse_rsvp', {
                openhouse_id: openHouseId,
                listing_id: this.listingId,
                nonce: wp.nonce || ''
            }).then(response => {
                this.showMessage('RSVP submitted successfully!');
            }).catch(error => {
                this.showMessage('Failed to submit RSVP');
            });
        }
    }
    
    // Handle other button clicks
    handleButtonClick(button) {
        const buttonText = button.textContent.trim();
        let message = `${buttonText} functionality would go here`;
        
        // Customize messages for specific buttons
        if (button.classList.contains('hph-virtual-tour-btn')) {
            message = 'Opening virtual tour...';
        } else if (button.classList.contains('hph-schedule-btn')) {
            message = 'Opening schedule form...';
        } else if (button.classList.contains('hph-calculator-btn')) {
            message = 'Opening mortgage calculator...';
        }
        
        this.showMessage(message);
        
        // Add button animation
        button.style.transform = 'scale(0.95)';
        setTimeout(() => {
            button.style.transform = '';
        }, 150);
    }
    
    // Message system
    showMessage(text) {
        // Remove existing message
        const existingMessage = document.querySelector('.hph-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Create new message
        const message = document.createElement('div');
        message.className = 'hph-message';
        message.textContent = text;
        message.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(8, 47, 73, 0.95);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            z-index: 10000;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: hphSlideDown 0.3s ease;
        `;
        
        document.body.appendChild(message);
        
        // Remove message after 3 seconds
        setTimeout(() => {
            if (message.parentNode) {
                message.style.animation = 'hphSlideUp 0.3s ease forwards';
                setTimeout(() => {
                    if (message.parentNode) {
                        message.remove();
                    }
                }, 300);
            }
        }, 3000);
    }
    
    // Cleanup
    destroy() {
        this.stopAutoAdvance();
        clearTimeout(this.inactivityTimer);
        
        // Remove event listeners would go here if needed
        // For now, we'll rely on garbage collection
    }
}

// Auto-initialize cards when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add CSS animations for messages
    if (!document.querySelector('#hph-message-styles')) {
        const style = document.createElement('style');
        style.id = 'hph-message-styles';
        style.textContent = `
            @keyframes hphSlideDown {
                from {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
            }
            @keyframes hphSlideUp {
                from {
                    opacity: 1;
                    transform: translateX(-50%) translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateX(-50%) translateY(-20px);
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Initialize all swipe cards
    const cards = document.querySelectorAll('.hph-swipe-card');
    cards.forEach(card => {
        new HPHSwipeCard(card);
    });
});

// Export for use in other scripts
window.HPHSwipeCard = HPHSwipeCard;