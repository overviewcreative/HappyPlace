<?php
/**
 * Quick Facts Template Part - Fixed Version
 * 
 * Sticky bar displaying key property statistics
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extract data from template args
$data = $args['data'] ?? [];
$listing_id = $args['listing_id'] ?? get_the_ID();

// Get basic property data with safe fallbacks
$bedrooms = 0;
$bathrooms = 0;
$sqft = 0;
$price = 0;

// Try to get data from bridge functions first, then fallback to direct data
if (function_exists('hph_bridge_get_bedrooms')) {
    $bedrooms = hph_bridge_get_bedrooms($listing_id);
} elseif (isset($data['bedrooms'])) {
    $bedrooms = (int) $data['bedrooms'];
} elseif (function_exists('get_field')) {
    $bedrooms = (int) get_field('bedrooms', $listing_id);
}

if (function_exists('hph_bridge_get_bathrooms')) {
    $bathrooms = hph_bridge_get_bathrooms($listing_id);
} elseif (isset($data['bathrooms'])) {
    $bathrooms = (float) $data['bathrooms'];
} elseif (function_exists('get_field')) {
    $bathrooms = (float) get_field('bathrooms', $listing_id);
}

if (function_exists('hph_bridge_get_sqft')) {
    $sqft = hph_bridge_get_sqft($listing_id);
} elseif (isset($data['sqft'])) {
    $sqft = (int) $data['sqft'];
} elseif (function_exists('get_field')) {
    $sqft = (int) get_field('square_footage', $listing_id);
}

if (function_exists('hph_bridge_get_price')) {
    $price = hph_bridge_get_price($listing_id, false);
} elseif (isset($data['price'])) {
    $price = (int) $data['price'];
} elseif (function_exists('get_field')) {
    $price = (int) get_field('price', $listing_id);
}

// Calculate price per sqft
$price_per_sqft = ($price > 0 && $sqft > 0) ? round($price / $sqft) : 0;

// Don't show if no meaningful data
if ($bedrooms <= 0 && $bathrooms <= 0 && $sqft <= 0) {
    return;
}

// Check if favorite function exists
$is_favorite = false;
if (function_exists('hph_is_favorite')) {
    $is_favorite = hph_is_favorite($listing_id);
}
?>

<section class="quick-facts" data-template-part="quick-facts" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    <div class="quick-facts-container">
        
        <div class="property-basics">
            
            <?php if ($bedrooms > 0) : ?>
                <div class="basic-stat" data-bridge-field="bedrooms" data-value="<?php echo esc_attr($bedrooms); ?>">
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
                <div class="basic-stat" data-bridge-field="bathrooms" data-value="<?php echo esc_attr($bathrooms); ?>">
                    <i class="fas fa-bath basic-icon" aria-hidden="true"></i>
                    <span class="basic-value bathroom-display">
                        <?php 
                        if ($bathrooms == floor($bathrooms)) {
                            // Whole number
                            printf(
                                _n('%d Bath', '%d Baths', $bathrooms, 'happy-place'),
                                $bathrooms
                            );
                        } else {
                            // Has half bath - show as decimal
                            echo esc_html($bathrooms . ' ' . __('Baths', 'happy-place'));
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($sqft > 0) : ?>
                <div class="basic-stat" data-bridge-field="sqft" data-value="<?php echo esc_attr($sqft); ?>">
                    <i class="fas fa-ruler-combined basic-icon" aria-hidden="true"></i>
                    <span class="basic-value sqft-display">
                        <?php echo esc_html(number_format($sqft) . ' ' . __('Sq Ft', 'happy-place')); ?>
                    </span>
                </div>
            <?php endif; ?>
            
        </div>

        <?php if ($price_per_sqft > 0) : ?>
            <div class="price-per-sqft" data-bridge-field="price_per_sqft" data-value="<?php echo esc_attr($price_per_sqft); ?>">
                <div class="price-label">
                    <?php esc_html_e('Price per Sq Ft', 'happy-place'); ?>
                </div>
                <div class="price-value">
                    $<?php echo esc_html(number_format($price_per_sqft)); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="primary-actions">
            
            <button class="hph-btn hph-btn--primary schedule-btn" 
                    data-action="schedule-tour"
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                <?php esc_html_e('Schedule Tour', 'happy-place'); ?>
            </button>
            
            <button class="hph-btn hph-btn--secondary contact-btn" 
                    data-action="contact"
                    data-listing-id="<?php echo esc_attr($listing_id); ?>">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <?php esc_html_e('Contact Agent', 'happy-place'); ?>
            </button>
            
            <?php if (function_exists('hph_is_favorite')) : ?>
                <button class="hph-btn hph-btn--outline favorite-btn <?php echo $is_favorite ? 'is-favorite' : ''; ?>" 
                        data-action="favorite"
                        data-listing-id="<?php echo esc_attr($listing_id); ?>"
                        data-nonce="<?php echo wp_create_nonce('hph_favorite_nonce'); ?>"
                        aria-label="<?php echo $is_favorite ? __('Remove from favorites', 'happy-place') : __('Add to favorites', 'happy-place'); ?>">
                    <i class="<?php echo $is_favorite ? 'fas fa-heart' : 'far fa-heart'; ?>" aria-hidden="true"></i>
                </button>
            <?php endif; ?>
            
        </div>

    </div>
</section>