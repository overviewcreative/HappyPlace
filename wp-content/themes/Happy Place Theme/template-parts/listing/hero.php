<?php
/**
 * Listing Hero Section - Optimized Bridge Integration
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing ID and bridge data
$listing_id = $args['listing_id'] ?? get_the_ID();

// Use bridge functions with proper fallbacks
$gallery_images = function_exists('hph_bridge_get_gallery') ? hph_bridge_get_gallery($listing_id) : [];
$formatted_price = function_exists('hph_bridge_get_price_formatted') ? hph_bridge_get_price_formatted($listing_id, 'standard') : 
                  (function_exists('hph_bridge_get_price') ? hph_bridge_get_price($listing_id, true) : 'Price on Request');
$raw_price = function_exists('hph_bridge_get_price') ? hph_bridge_get_price($listing_id, false) : 0;
$full_address = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'full') : get_the_title();

// Enhanced property stats with proper formatting
$bedrooms = function_exists('hph_bridge_get_bedrooms') ? hph_bridge_get_bedrooms($listing_id) : 0;
$bathrooms_formatted = function_exists('hph_bridge_get_bathrooms_formatted') ? hph_bridge_get_bathrooms_formatted($listing_id) : 
                      (function_exists('hph_bridge_get_bathrooms') ? hph_bridge_get_bathrooms($listing_id, true) : '0');
$sqft_formatted = function_exists('hph_bridge_get_sqft_formatted') ? hph_bridge_get_sqft_formatted($listing_id, 'standard') : 
                 (function_exists('hph_bridge_get_sqft') ? number_format(hph_bridge_get_sqft($listing_id)) . ' sq ft' : '');

// Get lot size using updated bridge function (now returns acres)
$lot_size_formatted = function_exists('hph_bridge_get_lot_size_formatted') ? hph_bridge_get_lot_size_formatted($listing_id, 'auto') : 
                     (function_exists('hph_bridge_get_lot_size') ? hph_bridge_get_lot_size($listing_id, true) : '');

$status = function_exists('hph_bridge_get_status') ? hph_bridge_get_status($listing_id) : 'active';
$property_type = function_exists('hph_bridge_get_property_type') ? hph_bridge_get_property_type($listing_id) : '';

// Enhanced address data for better display
$street_full = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'street') : '';
$city = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'city') : '';
$state = function_exists('hph_bridge_get_address') ? hph_bridge_get_address($listing_id, 'state') : '';
$zip = function_exists('hph_bridge_get_zip_code') ? hph_bridge_get_zip_code($listing_id) : '';

// Build display addresses
$main_address = $street_full ?: get_the_title($listing_id);
$location_parts = array_filter([$city, $state, $zip]);
$sub_address = !empty($location_parts) ? implode(', ', $location_parts) : '';

// Build hero data array for template
$hero_data = [
    'id' => $listing_id,
    'images' => $gallery_images,
    'address' => [
        'full' => $full_address,
        'street' => $street_full,
        'main_display' => $main_address,
        'sub_display' => $sub_address,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
        'city_state' => trim($city . ', ' . $state, ', ') ?: 'Prime Location',
        'property_type' => $property_type ?: 'Single Family Home'
    ],
    'price' => [
        'raw' => $raw_price,
        'formatted' => $formatted_price,
        'per_sqft_formatted' => function_exists('hph_bridge_get_price_per_sqft') ? 
                                hph_bridge_get_price_per_sqft($listing_id, true) : 
                                ($raw_price > 0 && !empty($sqft_formatted) ? '$' . number_format($raw_price / (int)filter_var($sqft_formatted, FILTER_SANITIZE_NUMBER_INT), 2) . '/sq ft' : '')
    ],
    'stats' => [
        'bedrooms' => [
            'value' => $bedrooms > 0 ? $bedrooms : '',
            'label' => $bedrooms == 1 ? 'Bedroom' : 'Bedrooms',
            'icon' => 'fas fa-bed'
        ],
        'bathrooms' => [
            'value' => $bathrooms_formatted ?: '',
            'label' => 'Baths',
            'icon' => 'fas fa-bath'
        ],
        'sqft' => [
            'value' => $sqft_formatted ?: '',
            'label' => 'Sq Ft',
            'icon' => 'fas fa-ruler-combined'
        ],
        'lot_size' => [
            'value' => $lot_size_formatted ?: '',
            'label' => 'Lot Size',
            'icon' => 'fas fa-expand-arrows-alt'
        ]
    ],
    'status' => [
        'value' => $status,
        'display' => ucfirst($status),
        'class' => strtolower($status)
    ],
    'meta' => [
        'mls_number' => '',
        'days_on_market' => 0
    ]
];

// Extract data with fallbacks
$listing_id = $hero_data['id'];
$gallery = $hero_data['images'] ?? [];
$address = $hero_data['address'] ?? [];
$price = $hero_data['price'] ?? [];
$stats = $hero_data['stats'] ?? [];
$status = $hero_data['status'] ?? [];
$meta = $hero_data['meta'] ?? [];

$photo_count = count($gallery);
$has_multiple_photos = $photo_count > 1;
?>

<section class="hph-hero" 
         data-component="hero"
         data-listing-id="<?php echo esc_attr($listing_id); ?>"
         data-photos="<?php echo esc_attr($photo_count); ?>"
         <?php if ($has_multiple_photos) : ?>
         data-autoplay="true" 
         data-interval="6000"
         <?php endif; ?>>
    
    <?php if (!empty($gallery)) : ?>
        <!-- Image Carousel -->
        <div class="hph-hero__carousel">
            <?php foreach ($gallery as $index => $photo) : ?>
                <div class="hph-hero__slide <?php echo $index === 0 ? 'hph-hero__slide--active' : ''; ?>" 
                     data-slide="<?php echo esc_attr($index); ?>"
                     style="background-image: url('<?php echo esc_url($photo['sizes']['full'] ?? $photo['url']); ?>');"
                     role="img"
                     aria-label="<?php echo esc_attr($photo['alt'] ?? $address['street'] ?? 'Property photo'); ?>">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Gradient Overlay -->
    <div class="hph-hero__overlay" aria-hidden="true"></div>

    <!-- Hero Content Wrapper -->
    <div class="hph-hero__wrapper">
        
        <!-- Compact Header -->
        <header class="hph-hero__header">
            
            <!-- Status & Meta Info -->
            <div class="hph-hero__meta">
                
                <?php if (!empty($status)) : ?>
                    <div class="hph-hero__badge hph-hero__badge--<?php echo esc_attr($status['class']); ?>">
                        <?php echo esc_html($status['display']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['mls_number'])) : ?>
                    <div class="hph-hero__meta-badge">
                        <i class="fas fa-hashtag" aria-hidden="true"></i>
                        <span><?php echo esc_html($meta['mls_number']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($meta['days_on_market']) && $meta['days_on_market'] > 0) : ?>
                    <div class="hph-hero__meta-badge">
                        <i class="fas fa-calendar-day" aria-hidden="true"></i>
                        <span><?php echo esc_html($meta['days_on_market']); ?> Day<?php echo $meta['days_on_market'] > 1 ? 's' : ''; ?> on Market</span>
                    </div>
                <?php endif; ?>
                
            </div>

            <!-- Photo Counter & Controls -->
            <div class="hph-hero__controls">
                
                <?php if ($photo_count > 0) : ?>
                    <div class="hph-hero__photo-counter">
                        <i class="fas fa-<?php echo $has_multiple_photos ? 'images' : 'image'; ?>" aria-hidden="true"></i>
                        <?php if ($has_multiple_photos) : ?>
                            <span class="hph-hero__current-photo">1</span>
                            <span>/<?php echo esc_html($photo_count); ?></span>
                        <?php else : ?>
                            <span>1 Photo</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($has_multiple_photos) : ?>
                    <nav class="hph-hero__nav" aria-label="<?php esc_attr_e('Photo navigation', 'happy-place'); ?>">
                        <button class="hph-hero__nav-btn hph-hero__nav-btn--prev" 
                                data-action="prev"
                                aria-label="<?php esc_attr_e('Previous photo', 'happy-place'); ?>">
                            <i class="fas fa-chevron-left hph-icon" aria-hidden="true"></i>
                        </button>
                        <button class="hph-hero__nav-btn hph-hero__nav-btn--next" 
                                data-action="next"
                                aria-label="<?php esc_attr_e('Next photo', 'happy-place'); ?>">
                            <i class="fas fa-chevron-right hph-icon" aria-hidden="true"></i>
                        </button>
                    </nav>
                <?php endif; ?>
                
            </div>

        </header>

        <!-- Main Content - Centered -->
        <div class="hph-hero__content">
            
            <?php if (!empty($address['property_type'])) : ?>
                <div class="hph-hero__property-type"><?php echo esc_html($address['property_type']); ?></div>
            <?php endif; ?>
            
            <h1 class="hph-hero__address">
                <?php echo esc_html($address['main_display'] ?? $address['street'] ?? $address['full'] ?? get_the_title($listing_id)); ?>
            </h1>
            
            <?php if (!empty($address['sub_display'])) : ?>
                <div class="hph-hero__location">
                    <i class="fas fa-map-marker-alt hph-icon" aria-hidden="true"></i>
                    <span><?php echo esc_html($address['sub_display']); ?></span>
                </div>
            <?php elseif (!empty($address['city_state'])) : ?>
                <div class="hph-hero__location">
                    <i class="fas fa-map-marker-alt hph-icon" aria-hidden="true"></i>
                    <span><?php echo esc_html($address['city_state']); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($price['formatted'])) : ?>
                <div class="hph-hero__price" data-price="<?php echo esc_attr($price['raw'] ?? 0); ?>">
                    <?php echo esc_html($price['formatted']); ?>
                </div>
                
                <?php if (!empty($price['per_sqft_formatted'])) : ?>
                    <div class="hph-hero__price-per-sqft">
                        <?php echo esc_html($price['per_sqft_formatted']); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
        </div>

        <!-- Compact Footer -->
        <footer class="hph-hero__footer">
            
            <!-- Inline Stats -->
            <?php if (!empty($stats)) : ?>
                <div class="hph-hero__stats">
                    
                    <?php 
                    // Limit to most important stats for compact display
                    $display_stats = array_slice($stats, 0, 5, true);
                    ?>
                    
                    <?php foreach ($display_stats as $stat_key => $stat) : ?>
                        <?php if (!empty($stat['value'])) : ?>
                            <div class="hph-hero__stat" data-stat="<?php echo esc_attr($stat_key); ?>">
                                <div class="hph-hero__stat-icon">
                                    <i class="<?php echo esc_attr($stat['icon']); ?> hph-icon" aria-hidden="true"></i>
                                </div>
                                <div class="hph-hero__stat-content">
                                    <div class="hph-hero__stat-value"><?php echo esc_html($stat['value']); ?></div>
                                    <div class="hph-hero__stat-label"><?php echo esc_html($stat['label']); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="hph-hero__actions">
                
                <button class="hph-hero__btn hph-hero__btn--primary" 
                        data-action="schedule-tour"
                        data-listing-id="<?php echo esc_attr($listing_id); ?>">
                    <i class="fas fa-calendar-plus hph-icon" aria-hidden="true"></i>
                    <span><?php esc_html_e('Schedule Tour', 'happy-place'); ?></span>
                </button>
                
                <button class="hph-hero__btn hph-hero__btn--secondary" 
                        data-action="view-gallery"
                        data-photos="<?php echo esc_attr($photo_count); ?>">
                    <i class="fas fa-images hph-icon" aria-hidden="true"></i>
                    <span><?php esc_html_e('View All Photos', 'happy-place'); ?></span>
                </button>
                
                <button class="hph-hero__btn hph-hero__btn--icon <?php echo hph_is_favorite($listing_id) ? 'is-favorite' : ''; ?>" 
                        data-action="favorite"
                        data-listing-id="<?php echo esc_attr($listing_id); ?>"
                        aria-label="<?php echo hph_is_favorite($listing_id) ? esc_attr__('Remove from favorites', 'happy-place') : esc_attr__('Add to favorites', 'happy-place'); ?>">
                    <i class="<?php echo hph_is_favorite($listing_id) ? 'fas fa-heart' : 'far fa-heart'; ?> hph-icon" aria-hidden="true"></i>
                    <span class="btn-text">
                        <?php echo hph_is_favorite($listing_id) ? esc_html__('Saved', 'happy-place') : esc_html__('Save', 'happy-place'); ?>
                    </span>
                </button>
                
                <button class="hph-hero__btn hph-hero__btn--icon" 
                        data-action="share"
                        data-listing-id="<?php echo esc_attr($listing_id); ?>"
                        aria-label="<?php esc_attr_e('Share listing', 'happy-place'); ?>">
                    <i class="fas fa-share-alt hph-icon" aria-hidden="true"></i>
                    <span class="btn-text"><?php esc_html_e('Share', 'happy-place'); ?></span>
                </button>
                
            </div>

        </footer>

    </div>

</section>

<!-- Quick Facts Bar Below Hero -->
<section class="hph-quick-facts" data-component="quick-facts">
    <div class="hph-quick-facts__wrapper">
        
        <div class="hph-quick-facts__content">
            
            <!-- Primary Stats -->
            <div class="hph-quick-facts__primary">
                <?php if (!empty($stats['bedrooms']['value'])) : ?>
                    <div class="hph-quick-fact">
                        <i class="<?php echo esc_attr($stats['bedrooms']['icon']); ?> hph-icon" aria-hidden="true"></i>
                        <span class="hph-quick-fact__value"><?php echo esc_html($stats['bedrooms']['value']); ?></span>
                        <span class="hph-quick-fact__label"><?php echo esc_html($stats['bedrooms']['label']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($stats['bathrooms']['value'])) : ?>
                    <div class="hph-quick-fact">
                        <i class="<?php echo esc_attr($stats['bathrooms']['icon']); ?> hph-icon" aria-hidden="true"></i>
                        <span class="hph-quick-fact__value"><?php echo esc_html($stats['bathrooms']['value']); ?></span>
                        <span class="hph-quick-fact__label"><?php echo esc_html($stats['bathrooms']['label']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($stats['sqft']['value'])) : ?>
                    <div class="hph-quick-fact">
                        <i class="<?php echo esc_attr($stats['sqft']['icon']); ?> hph-icon" aria-hidden="true"></i>
                        <span class="hph-quick-fact__value"><?php echo esc_html($stats['sqft']['value']); ?></span>
                        <span class="hph-quick-fact__label"><?php echo esc_html($stats['sqft']['label']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($stats['lot_size']['value'])) : ?>
                    <div class="hph-quick-fact">
                        <i class="<?php echo esc_attr($stats['lot_size']['icon']); ?> hph-icon" aria-hidden="true"></i>
                        <span class="hph-quick-fact__value"><?php echo esc_html($stats['lot_size']['value']); ?></span>
                        <span class="hph-quick-fact__label"><?php echo esc_html($stats['lot_size']['label']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Price Per Sq Ft -->
            <?php if (!empty($price['per_sqft_formatted'])) : ?>
                <div class="hph-quick-facts__price-per-sqft">
                    <div class="hph-price-per-sqft">
                        <span class="hph-price-per-sqft__label">Price per Sq Ft</span>
                        <span class="hph-price-per-sqft__value"><?php echo esc_html($price['per_sqft_formatted']); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Secondary Actions -->
            <div class="hph-quick-facts__actions">
                <button class="hph-btn hph-btn--outline hph-btn--sm" 
                        data-action="contact">
                    <i class="fas fa-phone hph-icon" aria-hidden="true"></i>
                    <span><?php esc_html_e('Contact Agent', 'happy-place'); ?></span>
                </button>
            </div>
            
        </div>
        
    </div>
</section>

<style>
/* Quick Facts Bar Styles */
.hph-quick-facts {
    background: var(--hph-color-white);
    border-bottom: 1px solid var(--hph-color-gray-200);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 0;
    z-index: 100;
}

.hph-quick-facts__wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--hph-spacing-4) var(--hph-spacing-6);
    
    @media (max-width: 768px) {
        padding: var(--hph-spacing-3) var(--hph-spacing-4);
    }
}

.hph-quick-facts__content {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: var(--hph-spacing-8);
    
    @media (max-width: 1024px) {
        grid-template-columns: 1fr auto;
        gap: var(--hph-spacing-6);
    }
    
    @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: var(--hph-spacing-4);
        text-align: center;
    }
}

.hph-quick-facts__primary {
    display: flex;
    gap: var(--hph-spacing-8);
    
    @media (max-width: 768px) {
        justify-content: center;
        gap: var(--hph-spacing-6);
    }
    
    @media (max-width: 480px) {
        flex-direction: column;
        gap: var(--hph-spacing-3);
    }
}

.hph-quick-fact {
    display: flex;
    align-items: center;
    gap: var(--hph-spacing-2);
    
    @media (max-width: 480px) {
        justify-content: center;
    }
}

.hph-quick-fact .hph-icon {
    color: var(--hph-color-primary);
    font-size: 1rem;
    flex-shrink: 0;
}

.hph-quick-fact__value {
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--hph-color-text-primary);
}

.hph-quick-fact__label {
    font-size: 0.9rem;
    color: var(--hph-color-text-secondary);
    margin-left: var(--hph-spacing-1);
}

.hph-quick-facts__price-per-sqft {
    text-align: right;
    
    @media (max-width: 768px) {
        text-align: center;
    }
}

.hph-price-per-sqft__label {
    display: block;
    font-size: var(--hph-font-size-xs);
    color: var(--hph-color-gray-500);
    margin-bottom: var(--hph-spacing-1);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.hph-price-per-sqft__value {
    font-size: var(--hph-font-size-lg);
    font-weight: var(--hph-font-bold);
    color: var(--hph-primary-600);
}

.hph-quick-facts__actions {
    @media (max-width: 1024px) {
        display: none;
    }
}

.hph-btn--sm {
    padding: var(--hph-spacing-2) var(--hph-spacing-4);
    font-size: var(--hph-font-size-xs);
}

.hph-btn--outline {
    background: transparent;
    color: var(--hph-color-gray-700);
    border: 1px solid var(--hph-color-gray-300);
    
    &:hover {
        background: var(--hph-color-gray-50);
        border-color: var(--hph-primary-500);
        color: var(--hph-primary-600);
    }
}
</style>

<?php
// Enhanced template bridge function with compact hero optimizations
if (!function_exists('hph_get_hero_data')) :
    function hph_get_hero_data($listing_id) {
        // Performance cache
        $cache_key = 'hph_hero_compact_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false && !WP_DEBUG) {
            return $cached;
        }
        
        // Get optimized gallery (limit for performance)
        $gallery = hph_hero_get_gallery($listing_id, 10); // Max 10 images for hero
        
        // Get essential address data
        $address = [
            'full' => get_the_title($listing_id),
            'street' => get_field('address_street', $listing_id) ?: get_the_title($listing_id),
            'city' => get_field('address_city', $listing_id) ?: '',
            'state' => get_field('address_state', $listing_id) ?: '',
            'property_type' => get_field('property_type', $listing_id) ?: 'Single Family Home'
        ];
        
        $address['city_state'] = trim($address['city'] . ', ' . $address['state'], ', ') ?: 'Prime Location';
        
        // Get price data
        $price_raw = (int) get_field('price', $listing_id);
        $price = [
            'raw' => $price_raw,
            'formatted' => $price_raw > 0 ? '$' . number_format($price_raw) : 'Price on Request'
        ];
        
        // Calculate price per sqft
        $sqft = (int) get_field('square_footage', $listing_id);
        if ($price_raw > 0 && $sqft > 0) {
            $price_per_sqft = round($price_raw / $sqft);
            $price['per_sqft_formatted'] = '$' . number_format($price_per_sqft) . '/sq ft';
        }
        
        // Get essential stats only
        $bedrooms = (int) get_field('bedrooms', $listing_id);
        $bathrooms = (float) get_field('bathrooms', $listing_id);
        
        $stats = [];
        
        if ($bedrooms > 0) {
            $stats['bedrooms'] = [
                'value' => $bedrooms,
                'label' => _n('Bedroom', 'Bedrooms', $bedrooms, 'happy-place'),
                'icon' => 'fas fa-bed'
            ];
        }
        
        if ($bathrooms > 0) {
            $stats['bathrooms'] = [
                'value' => $bathrooms == floor($bathrooms) ? (int) $bathrooms : $bathrooms,
                'label' => _n('Bathroom', 'Bathrooms', $bathrooms, 'happy-place'),
                'icon' => 'fas fa-bath'
            ];
        }
        
        if ($sqft > 0) {
            $stats['sqft'] = [
                'value' => number_format($sqft),
                'label' => 'Square Feet',
                'icon' => 'fas fa-ruler-combined'
            ];
        }
        
        // Add additional stats if available
        $lot_size = get_field('lot_size', $listing_id);
        if ($lot_size) {
            $stats['lot_size'] = [
                'value' => $lot_size,
                'label' => 'Lot Size',
                'icon' => 'fas fa-expand-arrows-alt'
            ];
        }
        
        $year_built = get_field('year_built', $listing_id);
        if ($year_built) {
            $stats['year_built'] = [
                'value' => $year_built,
                'label' => 'Year Built',
                'icon' => 'fas fa-calendar-alt'
            ];
        }
        
        // Status
        $status_value = get_field('status', $listing_id) ?: 'active';
        $status = [
            'value' => $status_value,
            'display' => ucfirst(str_replace(['_', '-'], ' ', $status_value)),
            'class' => strtolower(str_replace(['_', ' '], '-', $status_value))
        ];
        
        // Meta
        $meta = [
            'mls_number' => get_field('mls_number', $listing_id),
            'days_on_market' => hph_calculate_days_on_market($listing_id)
        ];
        
        $hero_data = [
            'id' => $listing_id,
            'images' => $gallery,
            'address' => $address,
            'price' => $price,
            'stats' => $stats,
            'status' => $status,
            'meta' => $meta
        ];
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $hero_data, 'hph_listings', 30 * MINUTE_IN_SECONDS);
        
        return $hero_data;
    }
endif;

// Helper function for days on market calculation
if (!function_exists('hph_calculate_days_on_market')) :
    function hph_calculate_days_on_market($listing_id) {
        $list_date = get_field('list_date', $listing_id);
        if (!$list_date) {
            return 0;
        }
        
        $list_timestamp = is_numeric($list_date) ? $list_date : strtotime($list_date);
        return max(0, floor((time() - $list_timestamp) / DAY_IN_SECONDS));
    }
endif;

// Enhanced gallery function with limit
if (!function_exists('hph_hero_get_gallery')) :
    function hph_hero_get_gallery($listing_id, $limit = 10) {
        $gallery = [];
        
        // ACF Gallery field
        if (function_exists('get_field')) {
            $acf_gallery = get_field('gallery', $listing_id);
            if (is_array($acf_gallery) && !empty($acf_gallery)) {
                $limited_gallery = array_slice($acf_gallery, 0, $limit);
                foreach ($limited_gallery as $image) {
                    if (is_array($image)) {
                        $gallery[] = [
                            'ID' => $image['ID'] ?? 0,
                            'url' => $image['url'] ?? $image['sizes']['large'] ?? '',
                            'alt' => $image['alt'] ?? get_the_title($listing_id),
                            'sizes' => $image['sizes'] ?? []
                        ];
                    }
                }
            }
        }
        
        // Featured image fallback
        if (empty($gallery) && has_post_thumbnail($listing_id)) {
            $featured_id = get_post_thumbnail_id($listing_id);
            $gallery[] = [
                'ID' => $featured_id,
                'url' => get_the_post_thumbnail_url($listing_id, 'full'),
                'alt' => get_post_meta($featured_id, '_wp_attachment_image_alt', true) ?: get_the_title($listing_id),
                'sizes' => [
                    'full' => get_the_post_thumbnail_url($listing_id, 'full'),
                    'large' => get_the_post_thumbnail_url($listing_id, 'large')
                ]
            ];
        }
        
        return $gallery;
    }
endif;
?>