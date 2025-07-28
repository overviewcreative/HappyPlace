/**
 * Photo Gallery JavaScript - Interactive Functionality
 * Clean, modern implementation following listing swipe card aesthetic
 */

class HPHPhotoGallery {
    constructor() {
        this.currentIndex = 0;
        this.images = [];
        this.isLightboxOpen = false;
        this.slideshowInterval = null;
        this.isFullscreen = false;
        this.preloadedImages = new Set();
        
        this.init();
    }

    init() {
        this.collectImages();
        this.bindEvents();
        this.preloadCriticalImages();
    }

    collectImages() {
        const galleryItems = document.querySelectorAll('.hph-gallery-item');
        this.images = Array.from(galleryItems).map((item, index) => ({
            index: index,
            src: item.dataset.imageUrl,
            thumb: item.dataset.thumbUrl,
            alt: item.dataset.alt || `Photo ${index + 1}`,
            element: item
        }));
    }

    bindEvents() {
        // Gallery grid interactions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.hph-gallery-view-btn') || e.target.closest('.hph-gallery-view-btn')) {
                this.handleImageClick(e);
            }
            if (e.target.matches('.hph-gallery-more-btn') || e.target.closest('.hph-gallery-more-btn')) {
                this.openLightbox(0);
            }
        });

        // Gallery action buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('#view-all-photos-btn')) {
                this.openLightbox(0);
            }
            if (e.target.matches('#slideshow-btn')) {
                this.startSlideshow();
            }
            if (e.target.matches('#download-photos-btn')) {
                this.handleDownloadRequest();
            }
        });

        // Lightbox controls
        document.addEventListener('click', (e) => {
            if (e.target.matches('#close-lightbox-btn') || e.target.matches('.hph-lightbox-backdrop')) {
                this.closeLightbox();
            }
            if (e.target.matches('#prev-photo-btn')) {
                this.previousImage();
            }
            if (e.target.matches('#next-photo-btn')) {
                this.nextImage();
            }
            if (e.target.matches('#slideshow-toggle-btn')) {
                this.toggleSlideshow();
            }
            if (e.target.matches('#fullscreen-btn')) {
                this.toggleFullscreen();
            }
        });

        // Thumbnail navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('.hph-thumbnail-btn') || e.target.closest('.hph-thumbnail-btn')) {
                const btn = e.target.matches('.hph-thumbnail-btn') ? e.target : e.target.closest('.hph-thumbnail-btn');
                const index = parseInt(btn.dataset.index);
                this.showImage(index);
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.isLightboxOpen) return;
            
            switch (e.key) {
                case 'Escape':
                    this.closeLightbox();
                    break;
                case 'ArrowLeft':
                    e.preventDefault();
                    this.previousImage();
                    break;
                case 'ArrowRight':
                    e.preventDefault();
                    this.nextImage();
                    break;
                case ' ':
                    e.preventDefault();
                    this.toggleSlideshow();
                    break;
                case 'f':
                case 'F':
                    if (e.ctrlKey || e.metaKey) {
                        e.preventDefault();
                        this.toggleFullscreen();
                    }
                    break;
            }
        });

        // Touch/swipe support for mobile
        this.setupTouchEvents();

        // Fullscreen change events
        document.addEventListener('fullscreenchange', () => {
            this.handleFullscreenChange();
        });

        // Window resize
        window.addEventListener('resize', () => {
            this.handleResize();
        });
    }

    handleImageClick(e) {
        const btn = e.target.matches('.hph-gallery-view-btn') ? e.target : e.target.closest('.hph-gallery-view-btn');
        const index = parseInt(btn.dataset.index);
        this.openLightbox(index);
    }

    openLightbox(startIndex = 0) {
        this.currentIndex = startIndex;
        this.isLightboxOpen = true;
        
        const lightbox = document.getElementById('photo-lightbox');
        if (lightbox) {
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Show image with fade in effect
            setTimeout(() => {
                this.showImage(startIndex, true);
            }, 50);
            
            this.trackEvent('lightbox_opened', { 
                start_index: startIndex,
                total_images: this.images.length 
            });
        }
    }

    closeLightbox() {
        this.isLightboxOpen = false;
        this.stopSlideshow();
        
        const lightbox = document.getElementById('photo-lightbox');
        if (lightbox) {
            lightbox.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Exit fullscreen if active
        if (this.isFullscreen && document.fullscreenElement) {
            document.exitFullscreen();
        }
        
        this.trackEvent('lightbox_closed', { 
            final_index: this.currentIndex,
            total_viewed: this.currentIndex + 1 
        });
    }

    showImage(index, isInitial = false) {
        if (index < 0 || index >= this.images.length) return;
        
        this.currentIndex = index;
        const image = this.images[index];
        
        // Update main image
        const mainImg = document.getElementById('lightbox-main-image');
        const loading = document.getElementById('lightbox-loading');
        
        if (mainImg && loading) {
            // Show loading if not cached
            if (!this.preloadedImages.has(image.src)) {
                loading.classList.add('show');
                mainImg.style.opacity = '0.5';
            }
            
            // Load image
            const img = new Image();
            img.onload = () => {
                mainImg.src = image.src;
                mainImg.alt = image.alt;
                mainImg.style.opacity = '1';
                loading.classList.remove('show');
                this.preloadedImages.add(image.src);
                
                // Preload adjacent images
                this.preloadAdjacentImages(index);
            };
            
            img.onerror = () => {
                console.error('Failed to load image:', image.src);
                loading.classList.remove('show');
                mainImg.style.opacity = '1';
            };
            
            img.src = image.src;
        }
        
        // Update counter
        const counter = document.getElementById('current-photo-number');
        if (counter) {
            counter.textContent = index + 1;
        }
        
        // Update thumbnails
        this.updateThumbnailActive(index);
        
        // Update navigation buttons
        this.updateNavigationButtons();
        
        // Scroll thumbnail into view
        this.scrollThumbnailIntoView(index);
        
        if (!isInitial) {
            this.trackEvent('image_viewed', { 
                index: index,
                image_src: image.src 
            });
        }
    }

    previousImage() {
        const newIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
        this.showImage(newIndex);
    }

    nextImage() {
        const newIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
        this.showImage(newIndex);
    }

    updateThumbnailActive(index) {
        document.querySelectorAll('.hph-thumbnail-btn').forEach((btn, i) => {
            btn.classList.toggle('active', i === index);
        });
    }

    updateNavigationButtons() {
        const prevBtn = document.getElementById('prev-photo-btn');
        const nextBtn = document.getElementById('next-photo-btn');
        
        // Always enable navigation (loops around)
        if (prevBtn) prevBtn.disabled = false;
        if (nextBtn) nextBtn.disabled = false;
    }

    scrollThumbnailIntoView(index) {
        const thumbnail = document.querySelector(`.hph-thumbnail-btn[data-index="${index}"]`);
        if (thumbnail) {
            thumbnail.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }

    startSlideshow() {
        if (!this.isLightboxOpen) {
            this.openLightbox(0);
        }
        
        this.slideshowInterval = setInterval(() => {
            this.nextImage();
        }, 4000); // 4 seconds per image
        
        this.updateSlideshowButton(true);
        this.trackEvent('slideshow_started');
    }

    stopSlideshow() {
        if (this.slideshowInterval) {
            clearInterval(this.slideshowInterval);
            this.slideshowInterval = null;
            this.updateSlideshowButton(false);
        }
    }

    toggleSlideshow() {
        if (this.slideshowInterval) {
            this.stopSlideshow();
        } else {
            this.startSlideshow();
        }
    }

    updateSlideshowButton(isPlaying) {
        const btn = document.getElementById('slideshow-toggle-btn');
        const icon = btn?.querySelector('i');
        
        if (icon) {
            if (isPlaying) {
                icon.className = 'fas fa-pause';
                btn.title = 'Pause slideshow';
            } else {
                icon.className = 'fas fa-play';
                btn.title = 'Start slideshow';
            }
        }
    }

    toggleFullscreen() {
        const lightbox = document.getElementById('photo-lightbox');
        
        if (!document.fullscreenElement) {
            lightbox.requestFullscreen().then(() => {
                this.isFullscreen = true;
                this.updateFullscreenButton(true);
            }).catch(err => {
                console.error('Error attempting to enable fullscreen:', err);
            });
        } else {
            document.exitFullscreen().then(() => {
                this.isFullscreen = false;
                this.updateFullscreenButton(false);
            });
        }
    }

    updateFullscreenButton(isFullscreen) {
        const btn = document.getElementById('fullscreen-btn');
        const icon = btn?.querySelector('i');
        
        if (icon) {
            if (isFullscreen) {
                icon.className = 'fas fa-compress';
                btn.title = 'Exit fullscreen';
            } else {
                icon.className = 'fas fa-expand';
                btn.title = 'Enter fullscreen';
            }
        }
    }

    handleFullscreenChange() {
        this.isFullscreen = !!document.fullscreenElement;
        this.updateFullscreenButton(this.isFullscreen);
    }

    preloadCriticalImages() {
        // Preload first 3 images for better UX
        this.images.slice(0, 3).forEach(image => {
            const img = new Image();
            img.onload = () => {
                this.preloadedImages.add(image.src);
            };
            img.src = image.src;
        });
    }

    preloadAdjacentImages(currentIndex) {
        const preloadIndices = [
            currentIndex - 1 >= 0 ? currentIndex - 1 : this.images.length - 1,
            currentIndex + 1 < this.images.length ? currentIndex + 1 : 0
        ];
        
        preloadIndices.forEach(index => {
            if (index !== currentIndex && !this.preloadedImages.has(this.images[index].src)) {
                const img = new Image();
                img.onload = () => {
                    this.preloadedImages.add(this.images[index].src);
                };
                img.src = this.images[index].src;
            }
        });
    }

    setupTouchEvents() {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;
        
        const lightboxMain = document.querySelector('.hph-lightbox-main');
        if (!lightboxMain) return;
        
        lightboxMain.addEventListener('touchstart', (e) => {
            if (!this.isLightboxOpen) return;
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        }, { passive: true });
        
        lightboxMain.addEventListener('touchend', (e) => {
            if (!this.isLightboxOpen) return;
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const absDeltaX = Math.abs(deltaX);
            const absDeltaY = Math.abs(deltaY);
            
            // Only trigger if horizontal swipe is dominant and significant
            if (absDeltaX > absDeltaY && absDeltaX > 50) {
                if (deltaX > 0) {
                    this.previousImage();
                } else {
                    this.nextImage();
                }
            }
        }, { passive: true });
    }

    handleResize() {
        // Handle responsive behavior on resize
        if (this.isLightboxOpen && window.innerWidth <= 768) {
            // Adjust for mobile view
            this.scrollThumbnailIntoView(this.currentIndex);
        }
    }

    handleDownloadRequest() {
        // Trigger custom event for download request
        const event = new CustomEvent('hph:request-photos', {
            detail: { 
                listing_id: document.querySelector('.hph-photo-gallery')?.dataset.listingId,
                total_images: this.images.length
            }
        });
        document.dispatchEvent(event);
        
        this.trackEvent('photo_download_requested', { 
            total_images: this.images.length 
        });
    }

    trackEvent(eventName, parameters = {}) {
        // Google Analytics 4 tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'photo_gallery',
                event_label: window.location.pathname,
                ...parameters
            });
        }

        // Custom analytics hook
        if (typeof window.hphAnalytics !== 'undefined') {
            window.hphAnalytics.track(eventName, parameters);
        }

        // Debug logging
        if (window.location.hostname === 'localhost' || window.location.search.includes('debug=1')) {
            console.log('HPH Gallery Analytics:', eventName, parameters);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.hph-photo-gallery')) {
        new HPHPhotoGallery();
    }
});

// Export for use in other modules
window.HPHPhotoGallery = HPHPhotoGallery;