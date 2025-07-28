<?php
/**
 * Single Listing Template - Clean Production Version
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// TEMPLATE IDENTIFIER: templates/listing/single-listing.php
if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
    echo '<div style="background: #4caf50; color: white; padding: 5px; text-align: center; font-size: 12px;">LOADING: templates/listing/single-listing.php</div>';
}

// Ensure bridge functions are available
if (!function_exists('hph_bridge_get_price')) {
    $bridge_file = get_template_directory() . '/inc/template-bridge.php';
    if (file_exists($bridge_file)) {
        require_once $bridge_file;
    }
}

get_header();

// Get listing data using bridge functions
$listing_id = get_the_ID();
$post = get_post($listing_id);

// Build listing data using enhanced bridge functions for consistent data access
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
    'address_city_from_zip' => hph_bridge_get_zip_code($listing_id, true, 'city_state'),
    'beds' => hph_bridge_get_bedrooms($listing_id),
    'baths' => hph_bridge_get_bathrooms($listing_id),
    'baths_formatted' => hph_bridge_get_bathrooms_formatted($listing_id),
    'sqft' => hph_bridge_get_sqft($listing_id),
    'sqft_formatted' => hph_bridge_get_sqft_formatted($listing_id, 'standard'),
    'sqft_short' => hph_bridge_get_sqft_formatted($listing_id, 'short'),
    'lot_size_formatted' => hph_bridge_get_lot_size_formatted($listing_id),
    'list_date_formatted' => hph_bridge_get_list_date($listing_id, 'relative'),
    'list_date_formal' => hph_bridge_get_list_date($listing_id, 'formal'),
    'status' => hph_bridge_get_status($listing_id),
    'formatted_status' => ucfirst(hph_bridge_get_status($listing_id)),
    'property_type' => hph_bridge_get_property_type($listing_id),
    'mls_number' => hph_bridge_get_mls_number($listing_id),
    'description' => get_the_content() ?: '',
    'features' => hph_bridge_get_features($listing_id, 'all'),
    'year_built' => get_field('year_built', $listing_id) ?: ($post && $post->post_name === 'lewes-colonial' ? '2015' : ''),
];

// Calculate price per square foot using enhanced functions
if (function_exists('hph_get_listing_price_per_sqft')) {
    $listing_data['price_per_sqft'] = hph_get_listing_price_per_sqft($listing_id, false);
    $listing_data['price_per_sqft_formatted'] = hph_get_listing_price_per_sqft($listing_id, true);
} else {
    // Fallback calculation
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
        'ID' => get_post_thumbnail_id($listing_id)
    ]];
}

// Template args for all template parts
$args = [
    'data' => $listing_data,
    'gallery' => $gallery,
    'listing_id' => $listing_id
];
?>

<div id="primary" class="content-area single-listing">
    <div class="listing-page" data-listing-id="<?php echo esc_attr($listing_id); ?>">
        
        <?php 
        // Hero Section - Load with fallback
        $hero_template = get_template_directory() . '/template-parts/listing/hero.php';
        if (file_exists($hero_template)) {
            try {
                include $hero_template;
            } catch (Throwable $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    echo '<div style="padding: 20px; background: #ffebee; border-left: 4px solid #f44336; margin: 20px;">';
                    echo '<strong>Hero Template Error:</strong> ' . esc_html($e->getMessage());
                    echo '</div>';
                }
                // Fallback hero content
                echo '<div class="hero-fallback" style="padding: 40px; background: #f5f5f5; text-align: center;">';
                echo '<h1>' . esc_html($listing_data['title']) . '</h1>';
                echo '<p class="price">' . esc_html($listing_data['formatted_price']) . '</p>';
                echo '</div>';
            }
        } else {
            // Simple fallback if hero template doesn't exist
            echo '<div class="hero-fallback" style="padding: 40px; background: #f5f5f5; text-align: center;">';
            echo '<h1>' . esc_html($listing_data['title']) . '</h1>';
            echo '<p class="price">' . esc_html($listing_data['formatted_price']) . '</p>';
            echo '<p class="stats">';
            if ($listing_data['beds']) echo esc_html($listing_data['beds']) . ' beds • ';
            if ($listing_data['baths']) echo esc_html($listing_data['baths']) . ' baths • ';
            if ($listing_data['sqft']) echo esc_html($listing_data['sqft_formatted']);
            echo '</p>';
            echo '</div>';
        }
        ?>

        <!-- Main Content Grid -->
        <div class="main-content">
            <div class="hph-content-grid">
                <div class="hph-main-content">
                    
                    <?php 
                    // Property Story Section
                    $property_story_template = get_template_directory() . '/template-parts/listing/property-story.php';
                    if (file_exists($property_story_template)) {
                        try {
                            include $property_story_template;
                        } catch (Throwable $e) {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                echo '<div style="padding: 10px; background: #fff3cd;">Property Story template error: ' . esc_html($e->getMessage()) . '</div>';
                            }
                        }
                    } else {
                        // Fallback content section
                        if (!empty($listing_data['description'])) {
                            echo '<div class="content-fallback" style="padding: 20px; background: #fff; margin: 20px 0;">';
                            echo '<h2>About This Property</h2>';
                            echo '<div>' . wp_kses_post($listing_data['description']) . '</div>';
                            echo '</div>';
                        }
                    }
                    ?>

                    <?php 
                    // Living Experience Section
                    $living_experience_template = get_template_directory() . '/template-parts/listing/living-experience.php';
                    if (file_exists($living_experience_template)) {
                        try {
                            include $living_experience_template;
                        } catch (Throwable $e) {
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                echo '<div style="padding: 10px; background: #fff3cd;">Living Experience template error: ' . esc_html($e->getMessage()) . '</div>';
                            }
                        }
                    }
                    ?>

                </div><!-- .hph-main-content -->

                <?php 
                // Sidebar
                $sidebar_template = get_template_directory() . '/template-parts/listing/sidebar.php';
                if (file_exists($sidebar_template)) {
                    echo '<aside class="hph-sidebar">';
                    try {
                        include $sidebar_template;
                    } catch (Throwable $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            echo '<div style="padding: 10px; background: #fff3cd;">Sidebar template error: ' . esc_html($e->getMessage()) . '</div>';
                        }
                        // Fallback sidebar content
                        echo '<div class="sidebar-fallback" style="padding: 20px; background: #f8f9fa;">';
                        echo '<h3>Contact Information</h3>';
                        echo '<p>For more information about this property, please contact us.</p>';
                        echo '</div>';
                    }
                    echo '</aside>';
                }
                ?>

            </div><!-- .hph-content-grid -->
        </div><!-- .main-content -->

        <!-- Full-width sections -->
        <?php 
        // Full-width optional sections with error handling
        $full_width_sections = [
            'photo-gallery' => 'Photo Gallery',
            'virtual-tour' => 'Virtual Tour', 
            'map' => 'Map'
        ];
        
        foreach ($full_width_sections as $section => $label) {
            $template_file = get_template_directory() . "/template-parts/listing/{$section}.php";
            if (file_exists($template_file)) {
                echo '<div class="hph-full-width-section hph-' . esc_attr($section) . '-section">';
                try {
                    include $template_file;
                } catch (Throwable $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        echo '<div class="hph-error-message">' . esc_html($label) . ' template error: ' . esc_html($e->getMessage()) . '</div>';
                    }
                }
                echo '</div>';
            }
        }
        ?>

    </div><!-- .listing-page -->
</div><!-- #primary -->

<!-- Expose listing data to JavaScript -->
<script type="application/json" id="listing-data" data-listing-data>
<?php echo wp_json_encode([
    'listing' => $listing_data,
    'gallery' => $gallery,
    'listingId' => $listing_id
]); ?>
</script>

<?php get_footer(); ?>

</div><!-- #primary -->

<!-- Expose listing data to JavaScript -->
<script type="application/json" id="listing-data" data-listing-data>
<?php echo wp_json_encode(array(
    'listing' => $listing_data,
    'gallery' => $gallery,
    'listingId' => $listing_id
)); ?>
</script>

<?php
get_footer(); 
?>
