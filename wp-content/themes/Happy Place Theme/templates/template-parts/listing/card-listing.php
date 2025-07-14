<?php
/**
 * Template Part: Listing Card
 * 
 * This template part displays a single listing in card format.
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

$price = get_field('price');
$bedrooms = get_field('bedrooms');
$bathrooms = get_field('bathrooms');
$square_feet = get_field('square_feet');
$featured = get_field('featured');
$status = get_field('status');
?>

<article <?php post_class('hph-listing-card'); ?>>
    <a href="<?php the_permalink(); ?>" class="hph-listing-thumbnail">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('listing-thumb'); ?>
        <?php else : ?>
            <img src="<?php echo HPH_THEME_URI; ?>/assets/images/placeholder.jpg" 
                 alt="<?php _e('Property Image', 'happy-place'); ?>">
        <?php endif; ?>
        
        <?php if ($featured) : ?>
            <span class="hph-badge hph-badge-primary hph-badge-featured">
                <?php _e('Featured', 'happy-place'); ?>
            </span>
        <?php endif; ?>

        <?php if ($status) : ?>
            <span class="hph-badge hph-badge-<?php echo esc_attr($status); ?>">
                <?php echo esc_html(ucfirst($status)); ?>
            </span>
        <?php endif; ?>
    </a>

    <div class="hph-listing-details">
        <h2 class="hph-listing-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>
        
        <div class="hph-listing-meta">
            <?php if ($price) : ?>
                <div class="hph-badge hph-badge-primary">
                    <?php echo HPH_Theme::format_price($price); ?>
                </div>
            <?php endif; ?>
            
            <div class="hph-listing-specs">
                <?php if ($bedrooms) : ?>
                    <span><i class="fas fa-bed"></i> <?php echo $bedrooms; ?></span>
                <?php endif; ?>
                
                <?php if ($bathrooms) : ?>
                    <span><i class="fas fa-bath"></i> <?php echo $bathrooms; ?></span>
                <?php endif; ?>
                
                <?php if ($square_feet) : ?>
                    <span><i class="fas fa-ruler-combined"></i> 
                        <?php echo number_format($square_feet); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php 
        $excerpt = get_the_excerpt();
        if ($excerpt) : ?>
            <div class="hph-listing-excerpt">
                <?php echo wp_trim_words($excerpt, 20); ?>
            </div>
        <?php endif; ?>

        <?php
        $location = get_field('location');
        if ($location && isset($location['address'])) : ?>
            <div class="hph-listing-location">
                <i class="fas fa-map-marker-alt"></i>
                <?php echo esc_html($location['address']); ?>
            </div>
        <?php endif; ?>
    </div>
</article>
