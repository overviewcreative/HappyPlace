<?php
/**
 * Photo Gallery Template Part
 * 
 * Interactive photo gallery for listing images
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$listing_data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? 0;

// Get gallery images
$gallery_images = $listing_data['gallery'] ?? [];
$featured_image = $listing_data['featured_image'] ?? null;

// Combine featured image with gallery if it exists
$all_images = [];
if ($featured_image) {
    $all_images[] = $featured_image;
}
$all_images = array_merge($all_images, $gallery_images);

// Remove duplicates based on ID
$all_images = array_values(array_unique($all_images, SORT_REGULAR));

if (empty($all_images)) {
    return; // No images to display
}

// Get property info for overlay
$address = $listing_data['details']['full_address'] ?? '';
$price = $listing_data['core']['price'] ?? 0;
?>

<section class="photo-gallery-section" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="gallery-header">
        <div class="container">
            <div class="header-content">
                <div class="gallery-info">
                    <h2 class="gallery-title">Property Photos</h2>
                    <p class="gallery-count"><?php echo count($all_images); ?> Photos</p>
                </div>
                <div class="gallery-actions">
                    <button class="gallery-btn view-all-btn" data-action="view-all">
                        <i class="fas fa-expand"></i>
                        View All Photos
                    </button>
                    <button class="gallery-btn slideshow-btn" data-action="slideshow">
                        <i class="fas fa-play"></i>
                        Slideshow
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="gallery-container">
        <!-- Main Gallery Grid -->
        <div class="gallery-grid" id="photo-gallery">
            <?php foreach ($all_images as $index => $image): ?>
            <?php
                $image_url = is_array($image) ? ($image['url'] ?? '') : wp_get_attachment_image_url($image, 'large');
                $image_alt = is_array($image) ? ($image['alt'] ?? '') : get_post_meta($image, '_wp_attachment_image_alt', true);
                $thumbnail_url = is_array($image) ? ($image['sizes']['medium'] ?? $image_url) : wp_get_attachment_image_url($image, 'medium');
                
                // Determine grid position for featured layout
                $grid_class = '';
                if ($index === 0 && count($all_images) > 1) {
                    $grid_class = 'featured-image';
                } elseif ($index <= 4) {
                    $grid_class = 'grid-item-' . $index;
                } else {
                    $grid_class = 'hidden-image';
                }
            ?>
            <div class="gallery-item <?php echo esc_attr($grid_class); ?>" 
                 data-index="<?php echo esc_attr($index); ?>"
                 data-src="<?php echo esc_url($image_url); ?>"
                 data-alt="<?php echo esc_attr($image_alt ?: 'Property photo'); ?>">
                
                <img src="<?php echo esc_url($thumbnail_url); ?>" 
                     alt="<?php echo esc_attr($image_alt ?: 'Property photo'); ?>"
                     loading="<?php echo $index < 5 ? 'eager' : 'lazy'; ?>">
                
                <?php if ($index > 4 && count($all_images) > 5): ?>
                <div class="more-photos-overlay">
                    <span class="more-count">+<?php echo (count($all_images) - 5); ?></span>
                    <span class="more-text">More Photos</span>
                </div>
                <?php endif; ?>
                
                <div class="image-overlay">
                    <div class="overlay-content">
                        <button class="zoom-btn" data-action="zoom" data-index="<?php echo esc_attr($index); ?>">
                            <i class="fas fa-search-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Thumbnail Strip (for mobile/tablet) -->
        <div class="thumbnail-strip" id="thumbnail-strip">
            <?php foreach ($all_images as $index => $image): ?>
            <?php
                $thumbnail_url = is_array($image) ? ($image['sizes']['thumbnail'] ?? $image['url']) : wp_get_attachment_image_url($image, 'thumbnail');
                $image_alt = is_array($image) ? ($image['alt'] ?? '') : get_post_meta($image, '_wp_attachment_image_alt', true);
            ?>
            <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                 data-index="<?php echo esc_attr($index); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" 
                     alt="<?php echo esc_attr($image_alt ?: 'Property thumbnail'); ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Lightbox Modal -->
<div class="lightbox-modal" id="lightbox-modal" style="display: none;">
    <div class="lightbox-overlay"></div>
    <div class="lightbox-container">
        <button class="lightbox-close" id="lightbox-close">
            <i class="fas fa-times"></i>
        </button>
        
        <button class="lightbox-nav lightbox-prev" id="lightbox-prev">
            <i class="fas fa-chevron-left"></i>
        </button>
        
        <button class="lightbox-nav lightbox-next" id="lightbox-next">
            <i class="fas fa-chevron-right"></i>
        </button>
        
        <div class="lightbox-content">
            <img id="lightbox-image" src="" alt="">
            <div class="lightbox-info">
                <div class="property-info">
                    <h3><?php echo esc_html($address); ?></h3>
                    <?php if ($price): ?>
                    <p class="price">$<?php echo number_format($price); ?></p>
                    <?php endif; ?>
                </div>
                <div class="image-counter">
                    <span id="current-image">1</span> of <span id="total-images"><?php echo count($all_images); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Thumbnail Navigation -->
        <div class="lightbox-thumbnails" id="lightbox-thumbnails">
            <?php foreach ($all_images as $index => $image): ?>
            <?php
                $thumbnail_url = is_array($image) ? ($image['sizes']['thumbnail'] ?? $image['url']) : wp_get_attachment_image_url($image, 'thumbnail');
            ?>
            <div class="lightbox-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                 data-index="<?php echo esc_attr($index); ?>">
                <img src="<?php echo esc_url($thumbnail_url); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.photo-gallery-section {
    background: #fafbfc;
    padding: 3rem 0;
    margin: 3rem 0;
}

/* Gallery Header */
.gallery-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.gallery-info h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 0.5rem 0;
}

.gallery-count {
    color: var(--text-muted);
    margin: 0;
    font-size: 1rem;
}

.gallery-actions {
    display: flex;
    gap: 1rem;
}

.gallery-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
}

.gallery-btn:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-1px);
}

/* Gallery Grid */
.gallery-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-template-rows: repeat(2, 250px);
    gap: 0.75rem;
    border-radius: 12px;
    overflow: hidden;
}

.gallery-item {
    position: relative;
    cursor: pointer;
    overflow: hidden;
    background: #f3f4f6;
    transition: all 0.3s ease;
}

.gallery-item:hover {
    transform: scale(1.02);
    z-index: 2;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.gallery-item:hover img {
    transform: scale(1.05);
}

/* Featured Image Layout */
.featured-image {
    grid-column: 1 / 3;
    grid-row: 1 / 3;
}

.grid-item-1 {
    grid-column: 3;
    grid-row: 1;
}

.grid-item-2 {
    grid-column: 4;
    grid-row: 1;
}

.grid-item-3 {
    grid-column: 3;
    grid-row: 2;
}

.grid-item-4 {
    grid-column: 4;
    grid-row: 2;
}

.hidden-image {
    display: none;
}

/* More Photos Overlay */
.more-photos-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    z-index: 2;
}

.more-count {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.more-text {
    font-size: 1rem;
    font-weight: 500;
}

/* Image Overlay */
.image-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gallery-item:hover .image-overlay {
    opacity: 1;
}

.zoom-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.zoom-btn:hover {
    background: white;
    transform: scale(1.1);
}

.zoom-btn i {
    font-size: 1.2rem;
    color: var(--text-dark);
}

/* Thumbnail Strip */
.thumbnail-strip {
    display: none;
    overflow-x: auto;
    gap: 0.5rem;
    padding: 1rem 0;
    margin-top: 1rem;
}

.thumbnail-item {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    border: 3px solid transparent;
    transition: border-color 0.2s ease;
}

.thumbnail-item.active {
    border-color: var(--primary-color);
}

.thumbnail-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Lightbox Modal */
.lightbox-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(10px);
}

.lightbox-container {
    position: relative;
    width: 90vw;
    height: 90vh;
    display: flex;
    flex-direction: column;
}

.lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.5);
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    color: white;
    cursor: pointer;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.lightbox-close:hover {
    background: rgba(0, 0, 0, 0.8);
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.5);
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    color: white;
    cursor: pointer;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.lightbox-nav:hover {
    background: rgba(0, 0, 0, 0.8);
    transform: translateY(-50%) scale(1.1);
}

.lightbox-prev {
    left: 2rem;
}

.lightbox-next {
    right: 2rem;
}

.lightbox-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 70vh;
    object-fit: contain;
    border-radius: 8px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.lightbox-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
    max-width: 800px;
    margin-top: 2rem;
    padding: 1rem 2rem;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 8px;
    color: white;
}

.property-info h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.property-info .price {
    margin: 0.25rem 0 0 0;
    font-size: 1.1rem;
    color: var(--primary-light);
}

.image-counter {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.8);
}

/* Lightbox Thumbnails */
.lightbox-thumbnails {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1rem;
    overflow-x: auto;
    padding: 1rem 0;
}

.lightbox-thumb {
    flex-shrink: 0;
    width: 60px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    opacity: 0.6;
}

.lightbox-thumb.active {
    border-color: var(--primary-color);
    opacity: 1;
}

.lightbox-thumb:hover {
    opacity: 1;
    transform: scale(1.05);
}

.lightbox-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .gallery-grid {
        grid-template-rows: repeat(2, 200px);
    }
}

@media (max-width: 768px) {
    .photo-gallery-section {
        padding: 2rem 0;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .gallery-actions {
        justify-content: center;
    }
    
    .gallery-grid {
        display: none; /* Hide grid on mobile */
    }
    
    .thumbnail-strip {
        display: flex; /* Show thumbnail strip on mobile */
    }
    
    .lightbox-nav {
        width: 50px;
        height: 50px;
    }
    
    .lightbox-prev {
        left: 1rem;
    }
    
    .lightbox-next {
        right: 1rem;
    }
    
    .lightbox-info {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1rem;
    }
    
    .lightbox-thumbnails {
        padding: 0.5rem;
        gap: 0.25rem;
    }
    
    .lightbox-thumb {
        width: 50px;
        height: 50px;
    }
}

@media (max-width: 480px) {
    .gallery-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .gallery-btn {
        width: 100%;
        justify-content: center;
    }
    
    .lightbox-container {
        width: 95vw;
        height: 95vh;
    }
    
    .lightbox-nav {
        width: 40px;
        height: 40px;
    }
    
    .lightbox-prev {
        left: 0.5rem;
    }
    
    .lightbox-next {
        right: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const photoGallery = {
        currentIndex: 0,
        images: [],
        isLightboxOpen: false,
        slideshowInterval: null,
        
        init() {
            this.collectImages();
            this.bindEvents();
            this.setupKeyboardNavigation();
        },
        
        collectImages() {
            const galleryItems = document.querySelectorAll('.gallery-item');
            this.images = Array.from(galleryItems).map(item => ({
                src: item.dataset.src,
                alt: item.dataset.alt,
                index: parseInt(item.dataset.index)
            }));
        },
        
        bindEvents() {
            // Gallery item clicks
            document.querySelectorAll('.gallery-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    this.openLightbox(index);
                });
            });
            
            // Gallery action buttons
            document.querySelector('.view-all-btn')?.addEventListener('click', () => {
                this.openLightbox(0);
            });
            
            document.querySelector('.slideshow-btn')?.addEventListener('click', () => {
                this.startSlideshow();
            });
            
            // Thumbnail strip navigation
            document.querySelectorAll('.thumbnail-item').forEach(thumb => {
                thumb.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    this.showImage(index);
                    this.updateThumbnailActive(index);
                });
            });
            
            // Lightbox controls
            document.getElementById('lightbox-close')?.addEventListener('click', () => {
                this.closeLightbox();
            });
            
            document.querySelector('.lightbox-overlay')?.addEventListener('click', () => {
                this.closeLightbox();
            });
            
            document.getElementById('lightbox-prev')?.addEventListener('click', () => {
                this.previousImage();
            });
            
            document.getElementById('lightbox-next')?.addEventListener('click', () => {
                this.nextImage();
            });
            
            // Lightbox thumbnails
            document.querySelectorAll('.lightbox-thumb').forEach(thumb => {
                thumb.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    this.showLightboxImage(index);
                });
            });
            
            // Touch/swipe support for mobile
            this.setupTouchNavigation();
        },
        
        setupKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                if (!this.isLightboxOpen) return;
                
                switch(e.key) {
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
                }
            });
        },
        
        setupTouchNavigation() {
            let startX = 0;
            let endX = 0;
            
            const lightboxContent = document.querySelector('.lightbox-content');
            if (!lightboxContent) return;
            
            lightboxContent.addEventListener('touchstart', (e) => {
                startX = e.changedTouches[0].screenX;
            });
            
            lightboxContent.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].screenX;
                this.handleSwipe();
            });
            
            const handleSwipe = () => {
                const swipeThreshold = 50;
                const diff = startX - endX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        this.nextImage(); // Swipe left - next image
                    } else {
                        this.previousImage(); // Swipe right - previous image
                    }
                }
            };
            
            this.handleSwipe = handleSwipe;
        },
        
        openLightbox(index = 0) {
            this.currentIndex = index;
            this.isLightboxOpen = true;
            
            const lightbox = document.getElementById('lightbox-modal');
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            this.showLightboxImage(index);
            this.trackEvent('photo_gallery_opened', { image_index: index });
            
            // Preload adjacent images
            this.preloadImages();
        },
        
        closeLightbox() {
            this.isLightboxOpen = false;
            this.stopSlideshow();
            
            const lightbox = document.getElementById('lightbox-modal');
            lightbox.style.display = 'none';
            document.body.style.overflow = '';
            
            this.trackEvent('photo_gallery_closed');
        },
        
        showLightboxImage(index) {
            if (index < 0 || index >= this.images.length) return;
            
            this.currentIndex = index;
            const image = this.images[index];
            
            const lightboxImage = document.getElementById('lightbox-image');
            const currentImageSpan = document.getElementById('current-image');
            
            // Show loading state
            lightboxImage.style.opacity = '0.5';
            
            // Update image
            lightboxImage.src = image.src;
            lightboxImage.alt = image.alt;
            
            // Update counter
            currentImageSpan.textContent = index + 1;
            
            // Update thumbnail active state
            this.updateLightboxThumbnailActive(index);
            
            // Fade in when loaded
            lightboxImage.onload = () => {
                lightboxImage.style.opacity = '1';
            };
        },
        
        showImage(index) {
            // For thumbnail strip navigation (mobile)
            // This would be used if you have a single large image display
            this.currentIndex = index;
        },
        
        previousImage() {
            const newIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.images.length - 1;
            this.showLightboxImage(newIndex);
        },
        
        nextImage() {
            const newIndex = this.currentIndex < this.images.length - 1 ? this.currentIndex + 1 : 0;
            this.showLightboxImage(newIndex);
        },
        
        updateThumbnailActive(index) {
            document.querySelectorAll('.thumbnail-item').forEach(thumb => {
                thumb.classList.remove('active');
            });
            document.querySelector(`.thumbnail-item[data-index="${index}"]`)?.classList.add('active');
        },
        
        updateLightboxThumbnailActive(index) {
            document.querySelectorAll('.lightbox-thumb').forEach(thumb => {
                thumb.classList.remove('active');
            });
            document.querySelector(`.lightbox-thumb[data-index="${index}"]`)?.classList.add('active');
        },
        
        startSlideshow() {
            this.openLightbox(0);
            this.slideshowInterval = setInterval(() => {
                this.nextImage();
            }, 3000); // Change image every 3 seconds
            
            this.trackEvent('slideshow_started');
        },
        
        stopSlideshow() {
            if (this.slideshowInterval) {
                clearInterval(this.slideshowInterval);
                this.slideshowInterval = null;
            }
        },
        
        toggleSlideshow() {
            if (this.slideshowInterval) {
                this.stopSlideshow();
            } else {
                this.startSlideshow();
            }
        },
        
        preloadImages() {
            // Preload next and previous images for smoother navigation
            const preloadIndices = [
                this.currentIndex - 1 >= 0 ? this.currentIndex - 1 : this.images.length - 1,
                this.currentIndex + 1 < this.images.length ? this.currentIndex + 1 : 0
            ];
            
            preloadIndices.forEach(index => {
                if (index !== this.currentIndex) {
                    const img = new Image();
                    img.src = this.images[index].src;
                }
            });
        },
        
        trackEvent(eventName, parameters = {}) {
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    event_category: 'photo_gallery',
                    event_label: window.location.pathname,
                    listing_id: document.querySelector('.photo-gallery-section')?.dataset.listingId,
                    ...parameters
                });
            }
        }
    };
    
    // Initialize gallery if images exist
    if (document.querySelector('.photo-gallery-section')) {
        photoGallery.init();
    }
});
</script>