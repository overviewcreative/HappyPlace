<?php
/**
 * Template part for displaying listing hero section
 * 
 * This is a fallback template part used when the Hero component is not available.
 * 
 * @package HappyPlace
 * @subpackage TemplateParts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data
$listing_id = isset($listing_id) ? $listing_id : get_the_ID();
$hero_data = isset($hero_data) ? $hero_data : [];

// Fallback data if not provided
if (empty($hero_data)) {
    $hero_data = function_exists('hph_bridge_get_hero_data') 
        ? hph_bridge_get_hero_data($listing_id)
        : hph_fallback_get_hero_data($listing_id);
}

$title = $hero_data['title'] ?? get_the_title();
$price = $hero_data['price'] ?? '';
$featured_image = $hero_data['featured_image'] ?? get_the_post_thumbnail_url($listing_id, 'large');
$gallery = $hero_data['gallery'] ?? [];
$address = $hero_data['address'] ?? '';
?>

<section class="hph-listing-hero fallback-hero">
    <div class="hero-background">
        <?php if (!empty($featured_image)): ?>
            <img src="<?php echo esc_url($featured_image); ?>" alt="<?php echo esc_attr($title); ?>" class="hero-image">
        <?php endif; ?>
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content">
        <div class="container">
            <div class="hero-info">
                <?php if (!empty($title)): ?>
                    <h1 class="hero-title"><?php echo esc_html($title); ?></h1>
                <?php endif; ?>
                
                <?php if (!empty($address)): ?>
                    <p class="hero-address"><?php echo esc_html($address); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($price)): ?>
                    <div class="hero-price">
                        <span class="price-amount"><?php echo esc_html($price); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="hero-actions">
                    <button class="btn btn-primary action-btn" data-action="schedule-tour" data-listing="<?php echo esc_attr($listing_id); ?>">
                        Schedule Tour
                    </button>
                    <button class="btn btn-secondary action-btn" data-action="contact-agent" data-listing="<?php echo esc_attr($listing_id); ?>">
                        Contact Agent
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($gallery) && count($gallery) > 1): ?>
        <div class="hero-gallery-nav">
            <button class="gallery-nav-btn gallery-prev" aria-label="Previous image">‹</button>
            <button class="gallery-nav-btn gallery-next" aria-label="Next image">›</button>
            <div class="gallery-indicators">
                <?php foreach ($gallery as $index => $image): ?>
                    <button class="indicator <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>
