<?php
/**
 * Single Listing Template - Updated for Compact Hero Integration
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure bridge functions are available
if (!function_exists('hph_bridge_get_price')) {
    // Load bridge functions if not already loaded
    $bridge_file = get_template_directory() . '/inc/template-bridge.php';
    if (file_exists($bridge_file)) {
        require_once $bridge_file;
    }
}

get_header();

// Get listing data using bridge functions
$listing_id = get_the_ID();
$post = get_post($listing_id);

// Validate listing post type
if (get_post_type($listing_id) !== 'listing') {
    get_template_part('404');
    return;
}

// Use the enhanced hero data function we created
$hero_data = function_exists('hph_get_hero_data') ? hph_get_hero_data($listing_id) : null;

if (!$hero_data) {
    // Fallback: Build basic listing data using bridge functions
    $listing_data = [
        'id' => $listing_id,
        'title' => get_the_title($listing_id),
        'price' => hph_bridge_get_price($listing_id, false),
        'formatted_price' => hph_bridge_get_price_formatted($listing_id, 'standard'),
        'short_price' => hph_bridge_get_price_formatted($listing_id, 'short'),
        'address_full' => hph_bridge_get_address($listing_id, 'full'),
        'address_street' => hph_bridge_get_address($listing_id, 'street'),
        'address_city' => hph_bridge_get_address($listing_id, 'city'),
        'address_state' => hph_bridge_get_address($listing_id, 'state'),
        'address_zip' => hph_bridge_get_zip_code($listing_id),
        'beds' => hph_bridge_get_bedrooms($listing_id),
        'baths' => hph_bridge_get_bathrooms($listing_id),
        'baths_formatted' => hph_bridge_get_bathrooms_formatted($listing_id),
        'sqft' => hph_bridge_get_sqft($listing_id),
        'sqft_formatted' => hph_bridge_get_sqft_formatted($listing_id, 'standard'),
        'sqft_short' => hph_bridge_get_sqft_formatted($listing_id, 'short'),
        'lot_size_formatted' => hph_bridge_get_lot_size_formatted($listing_id),
        'status' => hph_bridge_get_status($listing_id),
        'formatted_status' => ucfirst(hph_bridge_get_status($listing_id)),
        'property_type' => hph_bridge_get_property_type($listing_id),
        'mls_number' => hph_bridge_get_mls_number($listing_id),
        'description' => get_the_content() ?: '',
        'features' => hph_bridge_get_features($listing_id, 'all'),
        'year_built' => get_field('year_built', $listing_id) ?: '',
    ];

    // Calculate price per square foot
    if (function_exists('hph_get_listing_price_per_sqft')) {
        $listing_data['price_per_sqft'] = hph_get_listing_price_per_sqft($listing_id, false);
        $listing_data['price_per_sqft_formatted'] = hph_get_listing_price_per_sqft($listing_id, true);
    } else {
        $price = (float) $listing_data['price'];
        $sqft = (float) $listing_data['sqft'];
        $listing_data['price_per_sqft'] = ($price > 0 && $sqft > 0) ? round($price / $sqft) : 0;
        $listing_data['price_per_sqft_formatted'] = $listing_data['price_per_sqft'] > 0 ? '$' . number_format($listing_data['price_per_sqft'], 2) . '/sq ft' : '';
    }

    // Get gallery using bridge function
    $gallery = hph_bridge_get_gallery($listing_id);

    // Fallback to featured image if no gallery
    if (empty($gallery) && has_post_thumbnail($listing_id)) {
        $gallery = [[
            'url' => get_the_post_thumbnail_url($listing_id, 'large'),
            'alt' => get_post_meta(get_post_thumbnail_id($listing_id), '_wp_attachment_image_alt', true) ?: $listing_data['title'],
            'ID' => get_post_thumbnail_id($listing_id),
            'sizes' => [
                'full' => get_the_post_thumbnail_url($listing_id, 'full'),
                'large' => get_the_post_thumbnail_url($listing_id, 'large')
            ]
        ]];
    }
} else {
    // Use hero data and build compatible listing data structure
    $listing_data = [
        'id' => $hero_data['id'],
        'title' => $hero_data['address']['full'] ?? get_the_title($listing_id),
        'price' => $hero_data['price']['raw'] ?? 0,
        'formatted_price' => $hero_data['price']['formatted'] ?? 'Price on Request',
        'address_full' => $hero_data['address']['full'] ?? '',
        'address_street' => $hero_data['address']['street'] ?? '',
        'address_city' => $hero_data['address']['city'] ?? '',
        'address_state' => $hero_data['address']['state'] ?? '',
        'beds' => $hero_data['stats']['bedrooms']['value'] ?? 0,
        'baths' => $hero_data['stats']['bathrooms']['value'] ?? 0,
        'sqft' => str_replace(',', '', $hero_data['stats']['sqft']['value'] ?? '0'),
        'sqft_formatted' => $hero_data['stats']['sqft']['value'] ?? '0',
        'status' => $hero_data['status']['value'] ?? 'active',
        'formatted_status' => $hero_data['status']['display'] ?? 'Active',
        'property_type' => $hero_data['address']['property_type'] ?? 'Single Family Home',
        'mls_number' => $hero_data['meta']['mls_number'] ?? '',
        'year_built' => $hero_data['stats']['year_built']['value'] ?? '',
        'price_per_sqft_formatted' => $hero_data['price']['per_sqft_formatted'] ?? '',
        'description' => get_the_content() ?: '',
        'features' => function_exists('hph_bridge_get_features') ? hph_bridge_get_features($listing_id, 'all') : [],
    ];
    
    $gallery = $hero_data['images'] ?? [];
}

// Template args for all template parts
$args = [
    'data' => $listing_data,
    'gallery' => $gallery,
    'listing_id' => $listing_id,
    'hero_data' => $hero_data // Pass hero data for advanced templates
];

// Debug output for development
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('HPH: Listing Data Count: ' . count($listing_data));
    error_log('HPH: Gallery Count: ' . count($gallery));
    error_log('HPH: Hero Data Available: ' . ($hero_data ? 'Yes' : 'No'));
}

// Enqueue template-specific assets
if (function_exists('hph_bridge_enqueue_template_assets')) {
    hph_bridge_enqueue_template_assets('single-listing');
}
?>

<div id="primary" class="content-area single-listing">
    <div class="listing-page" data-listing-id="<?php echo esc_attr($listing_id); ?>">
        
        <?php 
        // Compact Hero Section - Use our new template
        $hero_template = get_template_directory() . '/templates/listing/hero.php';
        if (file_exists($hero_template)) {
            include $hero_template;
        } else {
            // Fallback error message for development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div style="background: #f44336; color: white; padding: 20px; text-align: center;">';
                echo '<strong>Hero Template Missing:</strong> ' . $hero_template;
                echo '</div>';
            }
        }
        ?>

        <!-- Main Content Container -->
        <div class="main-content">
            <div class="hph-content-grid">
                
                <!-- Primary Content Column -->
                <div class="hph-main-content">
                    
                    <?php 
                    // Property Story Section (Description + Key Features)
                    $property_story_template = get_template_directory() . '/templates/listing/property-story.php';
                    if (file_exists($property_story_template)) {
                        include $property_story_template;
                    } else {
                        // Fallback: Basic description
                        if (!empty($listing_data['description'])) {
                            echo '<section class="hph-section hph-property-description">';
                            echo '<div class="hph-card">';
                            echo '<div class="hph-card__header">';
                            echo '<h2 class="hph-card__title">About This Property</h2>';
                            echo '</div>';
                            echo '<div class="hph-card__body">';
                            echo '<div class="hph-description-content">';
                            echo wp_kses_post($listing_data['description']);
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</section>';
                        }
                    }
                    ?>

                    <?php 
                    // Living Experience Section (Location + Lifestyle)
                    $living_experience_template = get_template_directory() . '/templates/listing/living-experience.php';
                    if (file_exists($living_experience_template)) {
                        include $living_experience_template;
                    }
                    ?>

                    <?php 
                    // Property Features Section
                    if (!empty($listing_data['features']) && is_array($listing_data['features'])) {
                        echo '<section class="hph-section hph-property-features">';
                        echo '<div class="hph-card">';
                        echo '<div class="hph-card__header">';
                        echo '<h2 class="hph-card__title"><i class="fas fa-list-ul"></i> Property Features</h2>';
                        echo '</div>';
                        echo '<div class="hph-card__body">';
                        echo '<div class="hph-features-grid">';
                        
                        foreach ($listing_data['features'] as $feature) {
                            if (is_string($feature)) {
                                echo '<div class="hph-feature-item">';
                                echo '<i class="fas fa-check" aria-hidden="true"></i>';
                                echo '<span>' . esc_html($feature) . '</span>';
                                echo '</div>';
                            } elseif (is_array($feature) && isset($feature['name'])) {
                                echo '<div class="hph-feature-item">';
                                echo '<i class="' . esc_attr($feature['icon'] ?? 'fas fa-check') . '" aria-hidden="true"></i>';
                                echo '<span>' . esc_html($feature['name']) . '</span>';
                                echo '</div>';
                            }
                        }
                        
                        echo '</div>';
                        echo '</div>';
                        echo '</div>';
                        echo '</section>';
                    }
                    ?>

                </div><!-- .hph-main-content -->

                <?php 
                // Sidebar with Agent Card, Mortgage Calculator, Quick Actions
                $sidebar_template = get_template_directory() . '/templates/listing/sidebar.php';
                if (file_exists($sidebar_template)) {
                    echo '<aside class="hph-sidebar">';
                    include $sidebar_template;
                    echo '</aside>';
                } else {
                    // Fallback: Basic sidebar with key info
                    echo '<aside class="hph-sidebar">';
                    echo '<div class="hph-sidebar-section">';
                    echo '<div class="hph-card">';
                    echo '<div class="hph-card__header">';
                    echo '<h3 class="hph-card__title">Property Details</h3>';
                    echo '</div>';
                    echo '<div class="hph-card__body">';
                    
                    if (!empty($listing_data['price_per_sqft_formatted'])) {
                        echo '<div class="hph-detail-item">';
                        echo '<span class="hph-detail-label">Price per Sq Ft:</span>';
                        echo '<span class="hph-detail-value">' . esc_html($listing_data['price_per_sqft_formatted']) . '</span>';
                        echo '</div>';
                    }
                    
                    if (!empty($listing_data['mls_number'])) {
                        echo '<div class="hph-detail-item">';
                        echo '<span class="hph-detail-label">MLS #:</span>';
                        echo '<span class="hph-detail-value">' . esc_html($listing_data['mls_number']) . '</span>';
                        echo '</div>';
                    }
                    
                    if (!empty($listing_data['year_built'])) {
                        echo '<div class="hph-detail-item">';
                        echo '<span class="hph-detail-label">Year Built:</span>';
                        echo '<span class="hph-detail-value">' . esc_html($listing_data['year_built']) . '</span>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '</aside>';
                }
                ?>

            </div><!-- .hph-content-grid -->
        </div><!-- .main-content -->

        <?php 
        // Full-width Photo Gallery Section
        $gallery_template = get_template_directory() . '/templates/listing/photo-gallery.php';
        if (file_exists($gallery_template)) {
            echo '<div class="hph-full-width-section hph-photo-gallery-section">';
            include $gallery_template;
            echo '</div>';
        } elseif (!empty($gallery) && count($gallery) > 1) {
            // Fallback: Basic gallery
            echo '<div class="hph-full-width-section hph-photo-gallery-section">';
            echo '<section class="hph-section">';
            echo '<div class="hph-section-header">';
            echo '<h2 class="hph-section-title">Photo Gallery</h2>';
            echo '</div>';
            echo '<div class="hph-photo-gallery">';
            
            foreach ($gallery as $index => $photo) {
                if ($index < 12) { // Limit fallback gallery
                    echo '<div class="hph-gallery-item">';
                    echo '<img src="' . esc_url($photo['sizes']['large'] ?? $photo['url']) . '" ';
                    echo 'alt="' . esc_attr($photo['alt'] ?? 'Property photo') . '" ';
                    echo 'loading="lazy" ';
                    echo 'data-index="' . esc_attr($index) . '">';
                    echo '</div>';
                }
            }
            
            echo '</div>';
            echo '</section>';
            echo '</div>';
        }
        ?>

        <?php 
        // Full-width Virtual Tour Section
        $virtual_tour_template = get_template_directory() . '/templates/listing/virtual-tour.php';
        if (file_exists($virtual_tour_template)) {
            echo '<div class="hph-full-width-section hph-virtual-tour-section">';
            include $virtual_tour_template;
            echo '</div>';
        }
        ?>

        <?php 
        // Full-width Map Section
        $map_template = get_template_directory() . '/templates/listing/map.php';
        if (file_exists($map_template)) {
            echo '<div class="hph-full-width-section hph-map-section">';
            include $map_template;
            echo '</div>';
        }
        ?>

    </div><!-- .listing-page -->
</div><!-- #primary -->

<!-- Expose listing data to JavaScript for our compact hero -->
<script type="application/json" id="hph-listing-data">
<?php echo wp_json_encode([
    'listing' => $listing_data,
    'gallery' => $gallery,
    'listingId' => $listing_id,
    'heroData' => $hero_data,
    'config' => [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('hph_listing_nonce'),
        'userId' => get_current_user_id(),
        'isLoggedIn' => is_user_logged_in()
    ]
]); ?>
</script>

<?php
get_footer(); 
?>