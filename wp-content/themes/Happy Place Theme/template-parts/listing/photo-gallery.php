<?php
/**
 * Photo Gallery Template Part - Rewritten
 * Clean, modern implementation following listing swipe card design
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID and data
$listing_id = $listing_id ?? get_the_ID();

// Use bridge functions for data retrieval
$gallery_images = function_exists('get_field') ? get_field('property_gallery', $listing_id) : [];
$featured_image_id = get_post_thumbnail_id($listing_id);

// Combine featured image with gallery
$all_images = [];

// Add featured image first if exists
if ($featured_image_id && has_post_thumbnail($listing_id)) {
    $featured_image = [
        'ID' => $featured_image_id,
        'url' => get_the_post_thumbnail_url($listing_id, 'large'),
        'alt' => get_post_meta($featured_image_id, '_wp_attachment_image_alt', true) ?: get_the_title($listing_id),
        'sizes' => [
            'large' => get_the_post_thumbnail_url($listing_id, 'large'),
            'medium' => get_the_post_thumbnail_url($listing_id, 'medium'),
            'thumbnail' => get_the_post_thumbnail_url($listing_id, 'thumbnail')
        ]
    ];
    $all_images[] = $featured_image;
}

// Add gallery images
if (is_array($gallery_images) && !empty($gallery_images)) {
    foreach ($gallery_images as $image) {
        // Skip if it's the same as featured image
        if (isset($image['ID']) && $image['ID'] == $featured_image_id) {
            continue;
        }
        
        $all_images[] = [
            'ID' => $image['ID'] ?? 0,
            'url' => $image['url'] ?? '',
            'alt' => $image['alt'] ?? '',
            'sizes' => $image['sizes'] ?? []
        ];
    }
}

// Add demo data for lewes-colonial if no images
if (empty($all_images)) {
    $post = get_post($listing_id);
    if ($post && $post->post_name === 'lewes-colonial') {
        $all_images = [
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&h=800&fit=crop',
                'alt' => 'Beautiful colonial home exterior',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=150&h=150&fit=crop'
                ]
            ],
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1200&h=800&fit=crop',
                'alt' => 'Elegant living room with modern furnishings',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=150&h=150&fit=crop'
                ]
            ],
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop',
                'alt' => 'Gourmet kitchen with granite countertops',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=150&h=150&fit=crop'
                ]
            ],
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=800&fit=crop',
                'alt' => 'Master bedroom suite',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=150&h=150&fit=crop'
                ]
            ],
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1584622781400-0d26ab962e0d?w=1200&h=800&fit=crop',
                'alt' => 'Luxurious bathroom with soaking tub',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1584622781400-0d26ab962e0d?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1584622781400-0d26ab962e0d?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1584622781400-0d26ab962e0d?w=150&h=150&fit=crop'
                ]
            ],
            [
                'ID' => 0,
                'url' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=1200&h=800&fit=crop',
                'alt' => 'Spacious backyard with deck',
                'sizes' => [
                    'large' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=1200&h=800&fit=crop',
                    'medium' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=600&h=400&fit=crop',
                    'thumbnail' => 'https://images.unsplash.com/photo-1449844908441-8829872d2607?w=150&h=150&fit=crop'
                ]
            ]
        ];
    }
}

// Exit if no images
if (empty($all_images)) {
    return;
}

$total_images = count($all_images);
$address = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : get_the_title($listing_id);
?>

<section class="hph-photo-gallery" id="photo-gallery-section" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="container">
        
        <!-- Gallery Header -->
        <div class="hph-gallery-header">
            <div class="hph-gallery-title-group">
                <h2 class="hph-gallery-title">Photo Gallery</h2>
                <p class="hph-gallery-subtitle">Explore every detail of <?php echo esc_html($address); ?></p>
            </div>
            
            <div class="hph-gallery-stats">
                <span class="hph-photo-count">
                    <i class="fas fa-images"></i>
                    <?php echo $total_images; ?> Photos
                </span>
            </div>
        </div>

        <!-- Main Gallery Grid -->
        <div class="hph-gallery-grid" id="gallery-grid">
            <?php foreach ($all_images as $index => $image): ?>
            <?php
                $image_url = $image['sizes']['large'] ?? $image['url'];
                $thumb_url = $image['sizes']['medium'] ?? $image['url'];
                $image_alt = $image['alt'] ?: "Photo " . ($index + 1) . " of " . $address;
                
                // Determine grid class based on position
                $grid_class = 'hph-gallery-item';
                if ($index === 0 && $total_images > 1) {
                    $grid_class .= ' hph-gallery-item--featured';
                } elseif ($index <= 4) {
                    $grid_class .= ' hph-gallery-item--standard';
                } else {
                    $grid_class .= ' hph-gallery-item--hidden'; // Hidden in grid, shown in lightbox
                }
            ?>
            
            <div class="<?php echo esc_attr($grid_class); ?>" 
                 data-index="<?php echo $index; ?>"
                 data-image-url="<?php echo esc_url($image_url); ?>"
                 data-thumb-url="<?php echo esc_url($thumb_url); ?>"
                 data-alt="<?php echo esc_attr($image_alt); ?>">
                
                <div class="hph-gallery-image-container">
                    <img src="<?php echo esc_url($thumb_url); ?>" 
                         alt="<?php echo esc_attr($image_alt); ?>"
                         class="hph-gallery-image"
                         loading="<?php echo $index < 6 ? 'eager' : 'lazy'; ?>">
                    
                    <div class="hph-gallery-overlay">
                        <button class="hph-gallery-view-btn" 
                                data-action="view-image" 
                                data-index="<?php echo $index; ?>"
                                aria-label="View larger image">
                            <i class="fas fa-search-plus"></i>
                        </button>
                    </div>
                    
                    <?php if ($index === 4 && $total_images > 5): ?>
                    <div class="hph-gallery-more-overlay">
                        <button class="hph-gallery-more-btn" 
                                data-action="view-all" 
                                aria-label="View all <?php echo $total_images; ?> photos">
                            <span class="hph-more-count">+<?php echo $total_images - 5; ?></span>
                            <span class="hph-more-text">More Photos</span>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Gallery Actions -->
        <div class="hph-gallery-actions">
            <button class="hph-btn hph-btn--primary" id="view-all-photos-btn">
                <i class="fas fa-expand"></i>
                View All <?php echo $total_images; ?> Photos
            </button>
            
            <button class="hph-btn hph-btn--outline" id="slideshow-btn">
                <i class="fas fa-play"></i>
                Start Slideshow
            </button>
            
            <button class="hph-btn hph-btn--outline" id="download-photos-btn">
                <i class="fas fa-download"></i>
                Request High-Res Photos
            </button>
        </div>
        
    </div>
</section>

<!-- Lightbox Modal -->
<div class="hph-lightbox" id="photo-lightbox" style="display: none;">
    <div class="hph-lightbox-backdrop"></div>
    
    <div class="hph-lightbox-container">
        <!-- Lightbox Header -->
        <div class="hph-lightbox-header">
            <div class="hph-lightbox-counter">
                <span id="current-photo-number">1</span> of <?php echo $total_images; ?>
            </div>
            
            <div class="hph-lightbox-controls">
                <button class="hph-lightbox-btn" id="slideshow-toggle-btn" title="Toggle slideshow">
                    <i class="fas fa-play"></i>
                </button>
                <button class="hph-lightbox-btn" id="fullscreen-btn" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>
                <button class="hph-lightbox-btn" id="close-lightbox-btn" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Lightbox Main Image -->
        <div class="hph-lightbox-main">
            <button class="hph-lightbox-nav hph-lightbox-prev" id="prev-photo-btn" aria-label="Previous photo">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="hph-lightbox-image-container">
                <img id="lightbox-main-image" 
                     src="" 
                     alt="" 
                     class="hph-lightbox-image">
                
                <!-- Loading spinner -->
                <div class="hph-lightbox-loading" id="lightbox-loading">
                    <div class="hph-spinner"></div>
                </div>
            </div>
            
            <button class="hph-lightbox-nav hph-lightbox-next" id="next-photo-btn" aria-label="Next photo">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Lightbox Thumbnails -->
        <div class="hph-lightbox-thumbnails" id="lightbox-thumbnails">
            <div class="hph-thumbnails-container">
                <?php foreach ($all_images as $index => $image): ?>
                <?php
                    $thumb_url = $image['sizes']['thumbnail'] ?? $image['sizes']['medium'] ?? $image['url'];
                    $image_alt = $image['alt'] ?: "Thumbnail " . ($index + 1);
                ?>
                <button class="hph-thumbnail-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                        data-index="<?php echo $index; ?>"
                        aria-label="View photo <?php echo $index + 1; ?>">
                    <img src="<?php echo esc_url($thumb_url); ?>" 
                         alt="<?php echo esc_attr($image_alt); ?>"
                         class="hph-thumbnail-image"
                         loading="lazy">
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Only include inline styles if not already enqueued
if (!wp_style_is('hph-photo-gallery', 'enqueued')) {
    echo '<style id="hph-photo-gallery-inline-styles">';
    include __DIR__ . '/../assets/css/photo-gallery-inline.css';
    echo '</style>';
}
?>