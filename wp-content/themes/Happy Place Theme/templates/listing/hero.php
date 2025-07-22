<?php
/**
 * Listing Hero Section
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

// Get template args from Template_Loader
$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Extract hero data with fallbacks
$gallery = $data['gallery'] ?? [];
$address = $data['address'] ?: get_the_title();
$price = $data['formatted_price'] ?: __('Price on Request', 'happy-place');
$status = $data['status'] ?? 'active';
$formatted_status = $data['formatted_status'] ?? ucfirst($status);

// Process gallery with fallbacks
if (empty($gallery)) {
    // Try featured image
    if (has_post_thumbnail($listing_id)) {
        $gallery = [
            [
                'url' => get_the_post_thumbnail_url($listing_id, 'large'),
                'alt' => get_post_meta(get_post_thumbnail_id($listing_id), '_wp_attachment_image_alt', true) ?: $address,
                'sizes' => ['large' => get_the_post_thumbnail_url($listing_id, 'large')]
            ]
        ];
    } else {
        // Default placeholder
        $default_image = apply_filters('hph_default_listing_image', get_template_directory_uri() . '/assets/images/default-property.jpg');
        $gallery = [
            [
                'url' => $default_image,
                'alt' => $address,
                'sizes' => ['large' => $default_image]
            ]
        ];
    }
}

$photo_count = count($gallery);
$has_multiple_photos = $photo_count > 1;
?>

<section class="hero-section" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="hero-carousel" 
         data-photos="<?php echo esc_attr($photo_count); ?>"
         <?php if ($has_multiple_photos) : ?>
         data-autoplay="true" 
         data-interval="5000"
         <?php endif; ?>
    >
        <?php foreach ($gallery as $index => $photo) : ?>
            <?php if (!empty($photo['url'])) : ?>
                <img 
                    src="<?php echo esc_url($photo['sizes']['large'] ?? $photo['url']); ?>" 
                    alt="<?php echo esc_attr($photo['alt'] ?? $address); ?>"
                    class="carousel-image <?php echo $index === 0 ? 'active' : ''; ?>"
                    loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>"
                    data-index="<?php echo esc_attr($index); ?>"
                    <?php if (!empty($photo['sizes']['medium'])) : ?>
                    data-medium="<?php echo esc_url($photo['sizes']['medium']); ?>"
                    <?php endif; ?>
                >
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="hero-overlay">
        <div class="hero-status">
            <span class="status-badge <?php echo esc_attr('status-' . strtolower($status)); ?>">
                <?php echo esc_html($formatted_status); ?>
            </span>
        </div>
        
        <div class="hero-content">
            <h1 class="hero-address"><?php echo esc_html($address); ?></h1>
            <div class="hero-price" data-price="<?php echo esc_attr($data['price'] ?? 0); ?>">
                <?php echo esc_html($price); ?>
            </div>
        </div>
    </div>
    
    <?php if ($has_multiple_photos) : ?>
        <!-- Photo Count Indicator -->
        <div class="photo-count">
            <i class="fas fa-images" aria-hidden="true"></i> 
            <span class="photo-count-text">
                <?php 
                printf(
                    _n('%d Photo', '%d Photos', $photo_count, 'happy-place'),
                    $photo_count
                ); 
                ?>
            </span>
        </div>
        
        <!-- Carousel Controls -->
        <div class="carousel-controls">
            <button class="carousel-btn carousel-prev" 
                    type="button"
                    aria-label="<?php esc_attr_e('Previous photo', 'happy-place'); ?>"
                    data-action="previous">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="carousel-btn carousel-next" 
                    type="button"
                    aria-label="<?php esc_attr_e('Next photo', 'happy-place'); ?>"
                    data-action="next">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
        
        <!-- Carousel Dots Navigation -->
        <?php if ($photo_count <= 10) : // Only show dots for reasonable number of photos ?>
            <div class="carousel-dots">
                <?php for ($i = 0; $i < $photo_count; $i++) : ?>
                    <button class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>" 
                            type="button"
                            data-index="<?php echo esc_attr($i); ?>"
                            aria-label="<?php echo esc_attr(sprintf(__('Go to photo %d', 'happy-place'), $i + 1)); ?>">
                    </button>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
// Pass data to JavaScript (Asset_Loader will handle the single-listing.js bundle)
if ($has_multiple_photos) : ?>
    <script type="application/json" id="hero-carousel-data">
        <?php echo wp_json_encode([
            'photos' => array_map(function($photo, $index) use ($address) {
                return [
                    'url' => $photo['sizes']['large'] ?? $photo['url'],
                    'medium' => $photo['sizes']['medium'] ?? null,
                    'alt' => $photo['alt'] ?? $address,
                    'index' => $index
                ];
            }, $gallery, array_keys($gallery)),
            'autoplay' => true,
            'interval' => 5000
        ]); ?>
    </script>
<?php endif; ?>