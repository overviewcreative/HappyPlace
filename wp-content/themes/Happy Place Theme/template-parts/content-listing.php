<?php
/**
 * Template part for displaying listing posts
 *
 * @package HappyPlace
 */

// Get listing data
$listing_id = get_the_ID();
$listing_data = get_post_meta($listing_id);
$price = get_post_meta($listing_id, 'listing_price', true);
$bedrooms = get_post_meta($listing_id, 'listing_bedrooms', true);
$bathrooms = get_post_meta($listing_id, 'listing_bathrooms', true);
$sqft = get_post_meta($listing_id, 'listing_sqft', true);
$address = get_post_meta($listing_id, 'listing_address', true);
$status = get_post_meta($listing_id, 'listing_status', true);
?>

<article id="listing-<?php the_ID(); ?>" <?php post_class('hph-listing-card'); ?> itemscope itemtype="https://schema.org/Product">
    <div class="hph-listing-card-inner">
        
        <?php if (has_post_thumbnail()) : ?>
            <div class="hph-listing-image">
                <a href="<?php echo esc_url(get_permalink()); ?>">
                    <?php the_post_thumbnail('medium_large', [
                        'class' => 'hph-listing-thumbnail',
                        'itemprop' => 'image'
                    ]); ?>
                </a>
                
                <?php if ($status) : ?>
                    <span class="hph-listing-status hph-status-<?php echo esc_attr(strtolower($status)); ?>">
                        <?php echo esc_html(ucfirst($status)); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($price) : ?>
                    <div class="hph-listing-price" itemprop="price">
                        <?php echo esc_html(number_format($price)); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="hph-listing-content">
            <header class="hph-listing-header">
                <h3 class="hph-listing-title" itemprop="name">
                    <a href="<?php echo esc_url(get_permalink()); ?>">
                        <?php the_title(); ?>
                    </a>
                </h3>
                
                <?php if ($address) : ?>
                    <div class="hph-listing-address" itemprop="address">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo esc_html($address); ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="hph-listing-features">
                <?php if ($bedrooms) : ?>
                    <span class="hph-feature hph-bedrooms">
                        <i class="fas fa-bed"></i>
                        <?php echo esc_html($bedrooms); ?> 
                        <?php echo esc_html(_n('Bed', 'Beds', $bedrooms, 'happy-place')); ?>
                    </span>
                <?php endif; ?>

                <?php if ($bathrooms) : ?>
                    <span class="hph-feature hph-bathrooms">
                        <i class="fas fa-bath"></i>
                        <?php echo esc_html($bathrooms); ?> 
                        <?php echo esc_html(_n('Bath', 'Baths', $bathrooms, 'happy-place')); ?>
                    </span>
                <?php endif; ?>

                <?php if ($sqft) : ?>
                    <span class="hph-feature hph-sqft">
                        <i class="fas fa-expand-arrows-alt"></i>
                        <?php echo esc_html(number_format($sqft)); ?> sq ft
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!is_singular()) : ?>
                <div class="hph-listing-excerpt">
                    <?php the_excerpt(); ?>
                </div>
            <?php endif; ?>

            <div class="hph-listing-actions">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="action-btn action-btn--primary">
                    <?php esc_html_e('View Details', 'happy-place'); ?>
                </a>
                
                <button class="action-btn action-btn--outline hph-save-listing" data-listing-id="<?php echo esc_attr($listing_id); ?>">
                    <i class="fas fa-heart"></i>
                    <?php esc_html_e('Save', 'happy-place'); ?>
                </button>
            </div>
        </div>
    </div>
</article>
