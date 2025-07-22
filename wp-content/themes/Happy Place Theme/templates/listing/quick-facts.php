<?php
/**
 * Listing Quick Facts Sticky Bar
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Templates;

if (!defined('ABSPATH')) {
    exit;
}

$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Extract core stats with type safety
$bedrooms = (int) ($data['bedrooms'] ?? 0);
$bathrooms = (float) ($data['bathrooms'] ?? 0);
$sqft = (int) ($data['sqft'] ?? 0);
$lot_size = (float) ($data['lot_size'] ?? 0);
$price_per_sqft = (int) ($data['price_per_sqft'] ?? 0);
$year_built = $data['year_built'] ?? '';

// Don't show if no meaningful data
if (!$bedrooms && !$bathrooms && !$sqft) {
    return;
}
?>

<section class="quick-facts" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="quick-facts-container">
        
        <div class="property-basics">
            
            <?php if ($bedrooms > 0) : ?>
                <div class="basic-stat" data-stat="bedrooms" data-value="<?php echo esc_attr($bedrooms); ?>">
                    <i class="fas fa-bed basic-icon" aria-hidden="true"></i>
                    <span class="basic-value">
                        <?php 
                        printf(
                            _n('%d Bed', '%d Beds', $bedrooms, 'happy-place'),
                            $bedrooms
                        ); 
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($bathrooms > 0) : ?>
                <div class="basic-stat" data-stat="bathrooms" data-value="<?php echo esc_attr($bathrooms); ?>">
                    <i class="fas fa-bath basic-icon" aria-hidden="true"></i>
                    <span class="basic-value">
                        <?php 
                        if ($bathrooms == floor($bathrooms)) {
                            // Whole number
                            printf(
                                _n('%d Bath', '%d Baths', $bathrooms, 'happy-place'),
                                $bathrooms
                            );
                        } else {
                            // Has half bath
                            echo esc_html($bathrooms . ' ' . __('Baths', 'happy-place'));
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($sqft > 0) : ?>
                <div class="basic-stat" data-stat="sqft" data-value="<?php echo esc_attr($sqft); ?>">
                    <i class="fas fa-ruler-combined basic-icon" aria-hidden="true"></i>
                    <span class="basic-value">
                        <?php echo esc_html(number_format($sqft) . ' ' . __('Sq Ft', 'happy-place')); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($lot_size > 0) : ?>
                <div class="basic-stat" data-stat="lot-size" data-value="<?php echo esc_attr($lot_size); ?>">
                    <i class="fas fa-tree basic-icon" aria-hidden="true"></i>
                    <span class="basic-value">
                        <?php 
                        if ($lot_size >= 1) {
                            echo esc_html(number_format($lot_size, 2) . ' ' . __('Acres', 'happy-place'));
                        } else {
                            $sqft_lot = $lot_size * 43560; // Convert to sq ft
                            echo esc_html(number_format($sqft_lot) . ' ' . __('Sq Ft', 'happy-place'));
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($year_built) : ?>
                <div class="basic-stat" data-stat="year-built" data-value="<?php echo esc_attr($year_built); ?>">
                    <i class="fas fa-calendar basic-icon" aria-hidden="true"></i>
                    <span class="basic-value">
                        <?php echo esc_html(__('Built', 'happy-place') . ' ' . $year_built); ?>
                    </span>
                </div>
            <?php endif; ?>
            
        </div>
        
        <?php if ($price_per_sqft > 0) : ?>
            <div class="price-per-sqft">
                <div class="price-label"><?php esc_html_e('Price per Sq Ft', 'happy-place'); ?></div>
                <div class="price-value" data-price-per-sqft="<?php echo esc_attr($price_per_sqft); ?>">
                    $<?php echo esc_html(number_format($price_per_sqft)); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="primary-actions">
            <button class="btn btn-primary" 
                    type="button"
                    data-action="book-showing" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-calendar-check" aria-hidden="true"></i>
                <?php esc_html_e('Book Showing', 'happy-place'); ?>
            </button>
            <button class="btn btn-secondary" 
                    type="button"
                    data-action="save-listing" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-heart" aria-hidden="true"></i>
                <?php esc_html_e('Save', 'happy-place'); ?>
            </button>
            <button class="btn btn-secondary" 
                    type="button"
                    data-action="share-listing" 
                    data-listing-id="<?php echo esc_attr($listing_id); ?>"
                    data-url="<?php echo esc_attr(get_permalink($listing_id)); ?>"
                    data-title="<?php echo esc_attr(get_the_title($listing_id)); ?>">
                <i class="fas fa-share-alt" aria-hidden="true"></i>
                <?php esc_html_e('Share', 'happy-place'); ?>
            </button>
        </div>
        
    </div>
</section>