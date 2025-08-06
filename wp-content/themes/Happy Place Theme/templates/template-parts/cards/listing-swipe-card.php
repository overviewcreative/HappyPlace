<?php
/**
 * Template Part: Happy Place Swipeable Listing Card
 * 
 * Displays a property listing in an interactive swipeable card format
 * Works with listing custom fields and open house data
 * 
 * @package HappyPlace
 * 
 * @param array $args {
 *     Optional arguments for the card
 *     @type bool   $hero_mode    Whether to display as hero variant (default: false)
 *     @type string $card_id      Unique ID for the card (auto-generated if not provided)
 *     @type int    $post_id      Post ID to display (uses current post if not provided)
 *     @type string $size         Card size: 'default', 'large', 'small' (default: 'default')
 * }
 */

// Get arguments
$args = wp_parse_args($args, [
    'hero_mode' => false,
    'card_id' => 'hph-card-' . get_the_ID() . '-' . wp_rand(),
    'post_id' => get_the_ID(),
    'size' => 'default'
]);

// Get listing data
$listing_id = $args['post_id'];
$price = get_field('price', $listing_id);
$bedrooms = get_field('bedrooms', $listing_id);
$bathrooms = get_field('bathrooms', $listing_id);
$square_footage = get_field('square_footage', $listing_id);
$lot_size = get_field('lot_size', $listing_id);
$property_type = get_field('property_type', $listing_id);
$status = get_field('status', $listing_id);
$highlight_badges = get_field('highlight_badges', $listing_id);
$gallery = get_field('photo_gallery', $listing_id);
$interior_features = get_field('interior_features', $listing_id);
$exterior_features = get_field('exterior_features', $listing_id);
$utility_features = get_field('utilitty_features', $listing_id);

// Address fields
$street_address = get_field('street_address', $listing_id);
$city = get_field('city', $listing_id);
$state = get_field('state', $listing_id);
$zip_code = get_field('zip_code', $listing_id);
$full_address = get_field('full_address', $listing_id);

// Property details
$year_built = get_field('year_built', $listing_id);
$garage = get_field('garage', $listing_id);
$pool = get_field('pool', $listing_id);
$fireplace = get_field('fireplace', $listing_id);
$basement = get_field('basement', $listing_id);
$deck_patio = get_field('deck_patio', $listing_id);

// Financial details
$property_tax = get_field('property_tax', $listing_id);
$hoa_fees = get_field('hoa_fees', $listing_id);
$estimated_payment = get_field('estimated_payment', $listing_id);
$price_per_sqft = get_field('price_per_sqft', $listing_id);

// Neighborhood data
$school_district = get_field('school_district', $listing_id);
$walkability_score = get_field('walkability_score', $listing_id);

// Agent information
$agent_id = get_field('agent', $listing_id);
$agent_name = '';
$agent_title = '';
$agent_phone = '';
$agent_email = '';
$agent_photo = '';
if ($agent_id) {
    $agent_name = get_the_title($agent_id);
    $agent_title = get_field('title', $agent_id);
    $agent_phone = get_field('phone', $agent_id);
    $agent_email = get_field('email', $agent_id);
    $agent_photo = get_field('profile_photo', $agent_id);
}

// Get open house data
$open_houses = get_posts([
    'post_type' => 'open_house',
    'meta_query' => [
        [
            'key' => 'related_listing',
            'value' => $listing_id,
            'compare' => '='
        ],
        [
            'key' => 'open_house_date',
            'value' => date('Y-m-d'),
            'compare' => '>='
        ]
    ],
    'meta_key' => 'open_house_date',
    'orderby' => 'meta_value',
    'order' => 'ASC',
    'posts_per_page' => 3
]);

// Prepare gallery images
$photos = [];
if ($gallery && !empty($gallery)) {
    // If gallery is an array of IDs (ACF "Image ID" return format)
    if (is_array($gallery) && isset($gallery[0]) && is_numeric($gallery[0])) {
        foreach ($gallery as $image_id) {
            $large = wp_get_attachment_image_src($image_id, 'large');
            $medium = wp_get_attachment_image_src($image_id, 'medium');
            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            $photos[] = [
                'url' => $large ? $large[0] : '',
                'thumbnail' => $medium ? $medium[0] : '',
                'alt' => $alt ?: get_the_title($listing_id)
            ];
        }
    } else {
        // ACF "Image Array" return format
        foreach ($gallery as $image) {
            $photos[] = [
                'url' => $image['sizes']['large'] ?? $image['url'],
                'thumbnail' => $image['sizes']['medium'] ?? $image['url'],
                'alt' => $image['alt'] ?: get_the_title($listing_id)
            ];
        }
    }
}

// Fallback to featured image if no gallery
if (empty($photos) && has_post_thumbnail($listing_id)) {
    $photos[] = [
        'url' => get_the_post_thumbnail_url($listing_id, 'large'),
        'thumbnail' => get_the_post_thumbnail_url($listing_id, 'medium'),
        'alt' => get_the_title($listing_id)
    ];
}

// Default placeholder if no images
if (empty($photos)) {
    $photos[] = [
        'url' => get_theme_file_uri('assets/images/property-placeholder.jpg'),
        'thumbnail' => get_theme_file_uri('assets/images/property-placeholder.jpg'),
        'alt' => get_the_title($listing_id)
    ];
}

// Format address
$display_address = '';
if ($full_address) {
    $display_address = $full_address;
} else {
    if ($street_address) {
        $display_address = $street_address;
        if ($city) $display_address .= ', ' . $city;
        if ($state) $display_address .= ', ' . $state;
        if ($zip_code) $display_address .= ' ' . $zip_code;
    }
}

// Card classes
$card_classes = ['hph-swipe-card'];
if ($args['hero_mode']) {
    $card_classes[] = 'hph-swipe-card--hero';
}
$card_classes[] = 'hph-swipe-card--' . $args['size'];

// Determine info sections based on available data
$info_sections = [
    'basic' => true, // Always show basic info
];

if (!empty($features) || $bedrooms || $bathrooms) {
    $info_sections['interior'] = true;
}

if ($garage || $pool || $fireplace || $deck_patio || $lot_size) {
    $info_sections['exterior'] = true;
}

if ($school_district || $walkability_score) {
    $info_sections['neighborhood'] = true;
}

if ($price || $property_tax || $hoa_fees || $estimated_payment) {
    $info_sections['financial'] = true;
}

if (!empty($open_houses)) {
    $info_sections['openhouse'] = true;
}

if ($agent_id && $agent_name) {
    $info_sections['agent'] = true;
}

// Get all selected features from the checkbox field
$interior_features = get_field('interior_features', $listing_id);

// Feature icon mapping (optional)
$feature_icons = [
    'pool' => 'fa-solid fa-water-ladder',
    'garage' => 'fa-solid fa-warehouse',
    'fireplace' => 'fa-solid fa-fire',
    'deck_patio' => 'fa-solid fa-table-cells-large',
    'basement' => 'fa-solid fa-layer-group',
    'hardwood_floors' => 'fa-solid fa-grip-lines',
    'updated_kitchen' => 'fa-solid fa-utensils',
    'walk_in_closet' => 'fa-solid fa-shirt',
    'central_air' => 'fa-solid fa-fan',
    'solar_panels' => 'fa-solid fa-solar-panel',
    'generator' => 'fa-solid fa-bolt',
    'security_system' => 'fa-solid fa-shield-halved',
    'smart_home' => 'fa-solid fa-house-signal',
    // Add more mappings as needed
];

// Split features into groups
$interior_keys = ['hardwood_floors', 'updated_kitchen', 'walk_in_closet', 'fireplace', 'basement', 'year_built'];
$exterior_keys = ['pool', 'garage', 'deck_patio', 'lot_size'];
$utility_keys = ['central_air', 'solar_panels', 'generator', 'security_system', 'smart_home'];

$interior_features = [];
$exterior_features = [];
$utility_features = [];

if (!empty($features) && is_array($features)) {
    foreach ($features as $feature) {
        if (in_array($feature, $interior_keys)) {
            $interior_features[] = $feature;
        } elseif (in_array($feature, $exterior_keys)) {
            $exterior_features[] = $feature;
        } elseif (in_array($feature, $utility_keys)) {
            $utility_features[] = $feature;
        }
    }
}

// Add utility section if there are utility features
if (!empty($utility_features)) {
    $info_sections['utility'] = true;
}

$total_sections = count($info_sections);
?>

<article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" 
         id="<?php echo esc_attr($args['card_id']); ?>"
         data-listing-id="<?php echo esc_attr($listing_id); ?>"
         data-total-sections="<?php echo esc_attr($total_sections); ?>">
    
    <!-- Navigation Controls -->
    <div class="hph-nav-controls">
        <button class="hph-nav-btn hph-nav-btn--prev" 
                aria-label="<?php esc_attr_e('Previous section', 'happy-place'); ?>">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hph-nav-btn hph-nav-btn--next" 
                aria-label="<?php esc_attr_e('Next section', 'happy-place'); ?>">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Photo Indicators -->
    <div class="hph-photo-indicators">
        <?php for ($i = 0; $i < $total_sections; $i++) : ?>
            <div class="hph-indicator <?php echo $i === 0 ? 'hph-indicator--active' : ''; ?>"></div>
        <?php endfor; ?>
    </div>
    
    <!-- Action Buttons -->
    <div class="hph-action-buttons">
        <button class="hph-action-btn hph-favorite-btn" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                aria-label="<?php esc_attr_e('Add to favorites', 'happy-place'); ?>">
            <i class="fas fa-heart"></i>
        </button>
        <button class="hph-action-btn hph-share-btn" 
                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                aria-label="<?php esc_attr_e('Share property', 'happy-place'); ?>">
            <i class="fas fa-share-alt"></i>
        </button>
    </div>
    
    <!-- Property Badges -->
    <div class="hph-property-badges">
        <?php if (is_array($highlight_badges) && !empty($highlight_badges)) : ?>
            <?php foreach ($highlight_badges as $badge) : ?>
                <?php if ($badge === 'new') : ?>
                    <span class="hph-badge hph-badge--success">
                        <i class="fas fa-plus"></i>
                        <?php esc_html_e('New Listing', 'happy-place'); ?>
                    </span>
                <?php elseif ($badge === 'price_drop') : ?>
                    <span class="hph-badge hph-badge--warning">
                        <i class="fas fa-arrow-down"></i>
                        <?php esc_html_e('Price Reduced', 'happy-place'); ?>
                    </span>
                <?php elseif ($badge === 'open_house') : ?>
                    <span class="hph-badge hph-badge--primary">
                        <i class="fas fa-calendar"></i>
                        <?php esc_html_e('Open House', 'happy-place'); ?>
                    </span>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($open_houses)) : ?>
            <span class="hph-badge hph-badge--primary">
                <i class="fas fa-calendar-alt"></i>
                <?php esc_html_e('Open House', 'happy-place'); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Image Container -->
    <div class="hph-card-image-container">
        <?php foreach ($photos as $index => $photo) : ?>
            <img src="<?php echo esc_url($photo['url']); ?>" 
                 alt="<?php echo esc_attr($photo['alt']); ?>" 
                 class="hph-card-image <?php echo $index === 0 ? 'hph-card-image--active' : ''; ?>"
                 loading="<?php echo $index === 0 ? 'eager' : 'lazy'; ?>">
        <?php endforeach; ?>
        
        <!-- Touch Areas for Mobile -->
        <div class="hph-touch-area hph-touch-area--left"></div>
        <div class="hph-touch-area hph-touch-area--right"></div>
    </div>
    
    <!-- Info Overlay -->
    <div class="hph-info-overlay">
        <!-- Fixed Address Section - stays consistent across all slides -->
        <div class="hph-fixed-address">
            <h2 class="hph-property-title">
                <?php echo $street_address ? esc_html($street_address) : get_the_title($listing_id); ?>
            </h2>
            <div class="hph-property-location">
                <i class="fas fa-map-marker-alt"></i>
                <span><?php echo esc_html($display_address ?: get_the_title($listing_id)); ?></span>
            </div>
        </div>
        
        <!-- Basic Info (Always first section) -->
        <?php if ($info_sections['basic']) : ?>
        <div class="hph-info-section hph-info-section--basic hph-info-section--active" data-section="basic">
            <?php if ($price) : ?>
                <div class="hph-property-price">
                    <?php echo esc_html(number_format($price)); ?>
                    <?php if ($price_per_sqft) : ?>
                        <span class="hph-price-per-sqft">
                            <?php echo sprintf(__('$%s/sq ft', 'happy-place'), number_format($price_per_sqft)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="hph-property-stats">
                <?php if ($bedrooms) : ?>
                    <div class="hph-stat-item">
                        <div class="hph-stat-icon">
                            <i class="<?php echo esc_attr($stat_icons['bedrooms']); ?>"></i>
                            <?php echo esc_html($bedrooms); ?>
                        </div>
                        <span><?php echo _n('Bedroom', 'Bedrooms', $bedrooms, 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($bathrooms) : ?>
                    <div class="hph-stat-item">
                        <div class="hph-stat-icon">
                            <i class="<?php echo esc_attr($stat_icons['bathrooms']); ?>"></i>
                            <?php echo esc_html($bathrooms); ?>
                        </div>
                        <span><?php echo _n('Bathroom', 'Bathrooms', $bathrooms, 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($square_footage) : ?>
                    <div class="hph-stat-item">
                        <div class="hph-stat-icon">
                            <i class="<?php echo esc_attr($stat_icons['square_footage']); ?>"></i>
                            <?php echo esc_html(number_format($square_footage/1000, 1)); ?>K
                        </div>
                        <span><?php esc_html_e('Sq Ft', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($lot_size) : ?>
                    <div class="hph-stat-item">
                        <div class="hph-stat-icon">
                            <i class="<?php echo esc_attr($stat_icons['lot_size']); ?>"></i>
                            <?php echo esc_html($lot_size); ?>
                        </div>
                        <span><?php esc_html_e('Acres', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-save-btn" data-listing-id="<?php echo esc_attr($listing_id); ?>">
                    <i class="fas fa-heart"></i> <?php esc_html_e('Save', 'happy-place'); ?>
                </button>
                <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="hph-quick-action-btn hph-quick-action-btn--primary">
                    <i class="fas fa-eye"></i> <?php esc_html_e('View Details', 'happy-place'); ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Interior Features -->
        <?php if ($info_sections['interior']) : ?>
        <div class="hph-info-section hph-info-section--interior" data-section="interior">
            <h3 class="hph-section-title"><?php esc_html_e('Interior Features', 'happy-place'); ?></h3>
            <div class="hph-features-grid">
                <?php foreach ($interior_features as $features) : 
                    $icon_class = $feature_icons[$feature] ?? 'fa-solid fa-star';
                ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="<?php echo esc_attr($icon_class); ?>"></i></div>
                        <span><?php echo esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($year_built) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-calendar"></i></div>
                        <span><?php echo sprintf(__('Built in %d', 'happy-place'), $year_built); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($basement && !in_array('basement', $interior_features)) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-layer-group"></i></div>
                        <span><?php esc_html_e('Basement', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-virtual-tour-btn">
                    <i class="fas fa-video"></i> <?php esc_html_e('Virtual Tour', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-schedule-btn">
                    <i class="fas fa-calendar"></i> <?php esc_html_e('Schedule Visit', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Exterior Features -->
        <?php if ($info_sections['exterior']) : ?>
        <div class="hph-info-section hph-info-section--exterior" data-section="exterior">
            <h3 class="hph-section-title"><?php esc_html_e('Exterior & Amenities', 'happy-place'); ?></h3>
            <div class="hph-features-grid">
                <?php foreach ($exterior_features as $exterior_feature) : 
                    $icon_class = $feature_icons[$feature] ?? 'fa-solid fa-star';
                ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="<?php echo esc_attr($icon_class); ?>"></i></div>
                        <span><?php echo esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if ($garage && !in_array('garage', $exterior_features)) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-warehouse"></i></div>
                        <span><?php echo sprintf(__('%d-Car Garage', 'happy-place'), $garage); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($pool && !in_array('pool', $exterior_features)) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-water-ladder"></i></div>
                        <span><?php esc_html_e('Swimming Pool', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                <?php 
                // Check for specific checkbox option
                $has_deck_patio = in_array('deck_patio', $exterior_features);
                if ($has_deck_patio) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-table-cells-large"></i></div>
                        <span><?php esc_html_e('Deck/Patio', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($lot_size && !in_array('lot_size', $exterior_features)) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="fa-solid fa-tree"></i></div>
                        <span><?php echo sprintf(__('%s Acre Lot', 'happy-place'), $lot_size); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-gallery-btn">
                    <i class="fas fa-images"></i> <?php esc_html_e('More Photos', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-tour-btn">
                    <i class="fas fa-home"></i> <?php esc_html_e('Book Tour', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Utility & Systems Features -->
        <?php if (!empty($info_sections['utility'])) : ?>
        <div class="hph-info-section hph-info-section--utility" data-section="utility">
            <h3 class="hph-section-title"><?php esc_html_e('Utility & Systems', 'happy-place'); ?></h3>
            <div class="hph-features-grid">
                <?php foreach ($utility_features as $feature) : 
                    $icon_class = $feature_icons[$feature] ?? 'fa-solid fa-star';
                ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon"><i class="<?php echo esc_attr($icon_class); ?>"></i></div>
                        <span><?php echo esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-calculator-btn">
                    <i class="fas fa-calculator"></i> <?php esc_html_e('Calculator', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-preapproval-btn">
                    <i class="fas fa-check-circle"></i> <?php esc_html_e('Get Pre-approved', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Neighborhood Info -->
        <?php if ($info_sections['neighborhood']) : ?>
        <div class="hph-info-section hph-info-section--neighborhood" data-section="neighborhood">
            <h3 class="hph-section-title"><?php esc_html_e('Neighborhood & Location', 'happy-place'); ?></h3>
            <div class="hph-features-grid">
                <?php if ($school_district) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon">S</div>
                        <span><?php echo esc_html($school_district); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($walkability_score) : ?>
                    <div class="hph-feature-item">
                        <div class="hph-feature-icon">W</div>
                        <span><?php echo sprintf(__('Walk Score: %d', 'happy-place'), $walkability_score); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="hph-feature-item">
                    <div class="hph-feature-icon">T</div>
                    <span><?php esc_html_e('Public Transit', 'happy-place'); ?></span>
                </div>
                <div class="hph-feature-item">
                    <div class="hph-feature-icon">P</div>
                    <span><?php esc_html_e('Parks Nearby', 'happy-place'); ?></span>
                </div>
                <div class="hph-feature-item">
                    <div class="hph-feature-icon">R</div>
                    <span><?php esc_html_e('Restaurants', 'happy-place'); ?></span>
                </div>
                <div class="hph-feature-item">
                    <div class="hph-feature-icon">M</div>
                    <span><?php esc_html_e('Shopping', 'happy-place'); ?></span>
                </div>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-map-btn">
                    <i class="fas fa-map"></i> <?php esc_html_e('View Map', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-explore-btn">
                    <i class="fas fa-search-location"></i> <?php esc_html_e('Explore Area', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Financial Details -->
        <?php if ($info_sections['financial']) : ?>
        <div class="hph-info-section hph-info-section--financial" data-section="financial">
            <h3 class="hph-section-title"><?php esc_html_e('Financial Details', 'happy-place'); ?></h3>
            <div class="hph-price-breakdown">
                <?php if ($price) : ?>
                    <div class="hph-price-item">
                        <span class="hph-price-label"><?php esc_html_e('List Price', 'happy-place'); ?></span>
                        <span class="hph-price-value">$<?php echo esc_html(number_format($price)); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($property_tax) : ?>
                    <div class="hph-price-item">
                        <span class="hph-price-label"><?php esc_html_e('Property Tax', 'happy-place'); ?></span>
                        <span class="hph-price-value">$<?php echo esc_html(number_format($property_tax)); ?>/year</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($hoa_fees) : ?>
                    <div class="hph-price-item">
                        <span class="hph-price-label"><?php esc_html_e('HOA Fees', 'happy-place'); ?></span>
                        <span class="hph-price-value">$<?php echo esc_html(number_format($hoa_fees)); ?>/month</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($estimated_payment) : ?>
                    <div class="hph-price-item">
                        <span class="hph-price-label"><?php esc_html_e('Est. Monthly Payment', 'happy-place'); ?></span>
                        <span class="hph-price-value">$<?php echo esc_html(number_format($estimated_payment)); ?>/month</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-calculator-btn">
                    <i class="fas fa-calculator"></i> <?php esc_html_e('Calculator', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-preapproval-btn">
                    <i class="fas fa-check-circle"></i> <?php esc_html_e('Get Pre-approved', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Open House Info -->
        <?php if ($info_sections['openhouse']) : ?>
        <div class="hph-info-section hph-info-section--openhouse" data-section="openhouse">
            <h3 class="hph-section-title">
                <i class="fas fa-calendar-alt"></i>
                <?php esc_html_e('Upcoming Open Houses', 'happy-place'); ?>
            </h3>
            <div class="hph-openhouse-list">
                <?php foreach ($open_houses as $open_house) : 
                    $oh_date = get_field('open_house_date', $open_house->ID);
                    $oh_start_time = get_field('start_time', $open_house->ID);
                    $oh_end_time = get_field('end_time', $open_house->ID);
                    $oh_agent = get_field('hosting_agent', $open_house->ID);
                    $oh_instructions = get_field('special_instructions', $open_house->ID);
                ?>
                    <div class="hph-openhouse-item">
                        <div class="hph-openhouse-date">
                            <div class="hph-openhouse-day">
                                <?php echo date('D', strtotime($oh_date)); ?>
                            </div>
                            <div class="hph-openhouse-month-day">
                                <?php echo date('M j', strtotime($oh_date)); ?>
                            </div>
                        </div>
                        <div class="hph-openhouse-details">
                            <div class="hph-openhouse-time">
                                <?php echo date('g:i A', strtotime($oh_start_time)); ?> - 
                                <?php echo date('g:i A', strtotime($oh_end_time)); ?>
                            </div>
                            <?php if ($oh_agent) : ?>
                                <div class="hph-openhouse-agent">
                                    <?php esc_html_e('Hosted by', 'happy-place'); ?> <?php echo esc_html(get_the_title($oh_agent)); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($oh_instructions) : ?>
                                <div class="hph-openhouse-instructions">
                                    <?php echo esc_html($oh_instructions); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="hph-openhouse-action">
                            <button class="hph-openhouse-rsvp-btn" data-openhouse-id="<?php echo esc_attr($open_house->ID); ?>">
                                <i class="fas fa-calendar-plus"></i>
                                <?php esc_html_e('RSVP', 'happy-place'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="hph-quick-actions">
                <button class="hph-quick-action-btn hph-directions-btn">
                    <i class="fas fa-directions"></i> <?php esc_html_e('Get Directions', 'happy-place'); ?>
                </button>
                <button class="hph-quick-action-btn hph-quick-action-btn--primary hph-rsvp-all-btn">
                    <i class="fas fa-calendar-check"></i> <?php esc_html_e('RSVP to All', 'happy-place'); ?>
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Agent Info -->
        <?php if ($info_sections['agent']) : ?>
        <div class="hph-info-section hph-info-section--agent" data-section="agent">
            <h3 class="hph-section-title"><?php esc_html_e('Listed by', 'happy-place'); ?></h3>
            <div class="hph-agent-card">
                <div class="hph-agent-avatar">
                    <?php if ($agent_photo && !empty($agent_photo)) : ?>
                        <img src="<?php echo esc_url($agent_photo['sizes']['thumbnail'] ?? $agent_photo['url']); ?>" 
                             alt="<?php echo esc_attr($agent_name); ?>" />
                    <?php else : ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="hph-agent-details">
                    <div class="hph-agent-name"><?php echo esc_html($agent_name); ?></div>
                    <?php if ($agent_title) : ?>
                        <div class="hph-agent-title"><?php echo esc_html($agent_title); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hph-contact-actions">
                <?php if ($agent_email) : ?>
                    <a href="mailto:<?php echo esc_attr($agent_email); ?>" class="hph-contact-btn">
                        <i class="fas fa-envelope"></i>
                        <?php esc_html_e('Email', 'happy-place'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($agent_phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9]/', '', $agent_phone)); ?>" 
                       class="hph-contact-btn hph-contact-btn--primary">
                        <i class="fas fa-phone"></i>
                        <?php esc_html_e('Call Now', 'happy-place'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Features Slide (show all selected) -->
        <?php if (!empty($features) && is_array($features)) : ?>
        <div class="hph-info-section hph-info-section--features" data-section="features">
            <h3 class="hph-section-title"><?php esc_html_e('Features', 'happy-place'); ?></h3>
            <div class="hph-features-grid">
                <?php foreach ($interior_features as $feature) : 
                    $icon_class = $feature_icons[$feature] ?? '';
                ?>
                    <div class="hph-feature-item">
                        <?php if ($icon_class): ?>
                            <div class="hph-feature-icon"><i class="<?php echo esc_attr($icon_class); ?>"></i></div>
                        <?php endif; ?>
                        <span><?php echo esc_html($feature); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</article>