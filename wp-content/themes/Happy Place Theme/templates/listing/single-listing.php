<?php
/**
 * Single Listing Template
 * 
 * Works with HappyPlace\Core\Template_Loader and Asset_Loader
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get listing data efficiently (single query + caching)
$listing_id = get_the_ID();

// Check if bridge functions are available
if (!function_exists('hph_get_all_listing_data')) {
    // Fallback: Load bridge functions if not loaded
    if (file_exists(WP_CONTENT_DIR . '/plugins/Happy Place Plugin/includes/constants/class-listing-fields.php')) {
        require_once WP_CONTENT_DIR . '/plugins/Happy Place Plugin/includes/constants/class-listing-fields.php';
    }
}

$listing_data = function_exists('hph_get_all_listing_data') ? hph_get_all_listing_data($listing_id) : null;

// Fallback for invalid listing
if (empty($listing_data) || get_post_type($listing_id) !== 'listing') {
    // Log the issue for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("HPH Template: Single listing template failed to load data for listing ID: {$listing_id}");
        error_log("HPH Template: Post type: " . get_post_type($listing_id));
        error_log("HPH Template: Bridge function exists: " . (function_exists('hph_get_all_listing_data') ? 'Yes' : 'No'));
    }
    
    get_template_part('404');
    return;
}

// Asset_Loader will automatically enqueue single-listing.js bundle
get_header(); ?>

<div class="listing-page" data-listing-id="<?php echo esc_attr($listing_id); ?>">
    
    <?php 
    // Hero Section - Your Template_Loader handles hierarchy
    hph_get_template_part('hero', '', [
        'data' => $listing_data, 
        'listing_id' => $listing_id
    ]); 
    ?>
    
    <?php 
    // Quick Facts Sticky Bar
    hph_get_template_part('quick-facts', '', [
        'data' => $listing_data, 
        'listing_id' => $listing_id
    ]); 
    ?>
    
    <main class="main-content">
        <div class="content-grid">
            <div class="primary-content">
                
                <?php 
                // Property Description & Story
                hph_get_template_part('description', '', [
                    'data' => $listing_data, 
                    'listing_id' => $listing_id
                ]); 
                ?>
                
                <?php 
                // Living Experience (Location + Lifestyle)
                hph_get_template_part('living-experience', '', [
                    'data' => $listing_data, 
                    'listing_id' => $listing_id
                ]); 
                ?>
                
            </div>
            
            <aside class="sidebar">
                
                <?php 
                // Agent Card (if agent exists)
                if (!empty($listing_data['agent'])) {
                    hph_get_template_part('sidebar/agent-card', '', [
                        'data' => $listing_data, 
                        'listing_id' => $listing_id
                    ]);
                }
                ?>
                
                <?php 
                // Mortgage Calculator
                hph_get_template_part('sidebar/mortgage-calculator', '', [
                    'data' => $listing_data, 
                    'listing_id' => $listing_id
                ]); 
                ?>
                
                <?php 
                // Quick Actions
                hph_get_template_part('sidebar/quick-actions', '', [
                    'data' => $listing_data, 
                    'listing_id' => $listing_id
                ]); 
                ?>
                
            </aside>
        </div>
    </main>
    
    <?php 
    // Full-Width Experience Sections
    
    // Photo Gallery (if photos exist)
    if (!empty($listing_data['gallery'])) {
        hph_get_template_part('photo-gallery', '', [
            'data' => $listing_data, 
            'listing_id' => $listing_id
        ]);
    }
    ?>
    
    <?php 
    // Virtual Tour (if URL exists)
    $virtual_tour_url = hph_get_listing_field($listing_id, 'virtual_tour_url');
    if ($virtual_tour_url) {
        hph_get_template_part('virtual-tour', '', [
            'data' => $listing_data, 
            'listing_id' => $listing_id,
            'tour_url' => $virtual_tour_url
        ]);
    }
    ?>
    
    <?php 
    // Map Section (if coordinates exist)
    if (!empty($listing_data['coordinates']['latitude']) && !empty($listing_data['coordinates']['longitude'])) {
        hph_get_template_part('map', '', [
            'data' => $listing_data, 
            'listing_id' => $listing_id
        ]);
    }
    ?>
    
</div>

<?php 
// Hook for additional content
do_action('hph_after_listing_content', $listing_data, $listing_id);

get_footer(); 
?>