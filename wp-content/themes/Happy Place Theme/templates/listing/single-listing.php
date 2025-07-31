<?php
/**
 * Single Listing Template - Complete Implementation
 * 
 * This template serves as the testing ground for all components
 * and demonstrates proper integration with the Happy Place architecture.
 * 
 * @package HappyPlace
 * @template-name single-listing.php
 * @version 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Start performance monitoring
$template_start_time = microtime(true);

get_header();

// Get listing ID and validate
$listing_id = get_the_ID();
if (!$listing_id || get_post_type() !== 'listing') {
    // Handle error case
    echo '<div class="error-message">Invalid listing.</div>';
    get_footer();
    return;
}

// Get comprehensive listing data through bridge functions
$listing_data = [];
$components_status = [];
$debug_info = [];

try {
    // Core listing data
    $listing_data = function_exists('hph_bridge_get_listing_data') 
        ? hph_bridge_get_listing_data($listing_id)
        : hph_fallback_get_listing_data($listing_id);
    
    // Hero/header data
    $hero_data = function_exists('hph_bridge_get_hero_data')
        ? hph_bridge_get_hero_data($listing_id)
        : hph_fallback_get_hero_data($listing_id);
    
    // Gallery data
    $gallery_data = function_exists('hph_bridge_get_gallery_data')
        ? hph_bridge_get_gallery_data($listing_id)
        : hph_fallback_get_gallery_data($listing_id);
    
    // Property details
    $property_details = function_exists('hph_bridge_get_property_details')
        ? hph_bridge_get_property_details($listing_id)
        : hph_fallback_get_property_details($listing_id);
    
    // Features and amenities
    $features_data = function_exists('hph_bridge_get_features')
        ? hph_bridge_get_features($listing_id)
        : hph_fallback_get_features($listing_id);
    
    // Agent information
    $agent_data = function_exists('hph_bridge_get_agent_data')
        ? hph_bridge_get_agent_data($listing_id)
        : hph_fallback_get_agent_data($listing_id);
    
    // Financial data for calculator
    $financial_data = function_exists('hph_bridge_get_financial_data')
        ? hph_bridge_get_financial_data($listing_id)
        : hph_fallback_get_financial_data($listing_id);
    
    // Similar/related listings
    $similar_listings = function_exists('hph_bridge_get_similar_listings')
        ? hph_bridge_get_similar_listings($listing_id, 3)
        : hph_fallback_get_similar_listings($listing_id, 3);

} catch (Exception $e) {
    // Log error and provide fallback
    error_log('HPH Single Listing Error: ' . $e->getMessage());
    $listing_data = hph_emergency_fallback_data($listing_id);
}

// Test component availability
$components_status = [
    'hero' => class_exists('HappyPlace\\Components\\Listing\\Hero'),
    'gallery' => class_exists('HappyPlace\\Components\\Listing\\Gallery'),
    'details' => class_exists('HappyPlace\\Components\\Listing\\Details'),
    'features' => class_exists('HappyPlace\\Components\\Listing\\Features'),
    'agent_card' => class_exists('HappyPlace\\Components\\Agent\\Card'),
    'mortgage_calculator' => class_exists('HappyPlace\\Components\\Tools\\Mortgage_Calculator'),
    'contact_form' => class_exists('HappyPlace\\Components\\Forms\\Contact_Form'),
    'similar_listings' => class_exists('HappyPlace\\Components\\Listing\\Similar_Grid')
];

// Prepare template arguments
$template_args = [
    'listing_id' => $listing_id,
    'listing_data' => $listing_data,
    'hero_data' => $hero_data,
    'gallery_data' => $gallery_data,
    'property_details' => $property_details,
    'features_data' => $features_data,
    'agent_data' => $agent_data,
    'financial_data' => $financial_data,
    'similar_listings' => $similar_listings,
    'components_status' => $components_status
];

// Enqueue template-specific assets
if (function_exists('hph_enqueue_template_assets')) {
    hph_enqueue_template_assets('single-listing');
}

// Add structured data
if (function_exists('hph_add_listing_schema')) {
    hph_add_listing_schema($listing_data);
}

// Debug information for development
if (defined('WP_DEBUG') && WP_DEBUG) {
    $debug_info = [
        'listing_id' => $listing_id,
        'data_sources' => [
            'bridge_functions' => function_exists('hph_bridge_get_listing_data'),
            'plugin_active' => class_exists('HPH_Plugin_Manager'),
            'acf_active' => function_exists('get_field')
        ],
        'component_count' => count(array_filter($components_status)),
        'total_components' => count($components_status),
        'memory_usage' => memory_get_usage(true),
        'queries_before' => get_num_queries()
    ];
}
?>

<article id="listing-<?php echo esc_attr($listing_id); ?>" class="single-listing-page" itemscope itemtype="https://schema.org/RealEstateListing">
    
    <!-- Hero Section -->
    <section class="listing-hero-section" data-section="hero">
        <?php 
        if ($components_status['hero']) {
            // Use Hero Component
            try {
                $hero_component = new HappyPlace\Components\Listing\Hero([
                    'listing_id' => $listing_id,
                    'hero_data' => $hero_data,
                    'gallery_data' => $gallery_data,
                    'variant' => 'full-width',
                    'show_gallery' => true,
                    'show_quick_facts' => true
                ]);
                $hero_component->display();
                
            } catch (Exception $e) {
                error_log('HPH Hero Component Error: ' . $e->getMessage());
                // Fall back to template part
                hph_get_template_part('listing/hero', null, $template_args);
            }
            
        } else {
            // Use template part fallback
            hph_get_template_part('listing/hero', null, $template_args);
        }
        ?>
    </section>

    <!-- Main Content Area -->
    <div class="listing-content-wrapper">
        <div class="hph-container">
            <div class="listing-content-grid">
                
                <!-- Primary Content Column -->
                <main class="listing-main-content" role="main">
                    
                    <!-- Property Description -->
                    <section class="property-description-section" data-section="description">
                        <header class="section-header">
                            <h2 class="section-title">About This Property</h2>
                        </header>
                        
                        <div class="property-description-content">
                            <?php 
                            $description = $listing_data['description'] ?? get_the_content();
                            if ($description) {
                                echo '<div class="formatted-content">' . wpautop($description) . '</div>';
                            } else {
                                echo '<p class="no-description">Property description coming soon.</p>';
                            }
                            ?>
                        </div>
                    </section>

                    <!-- Property Details -->
                    <section class="property-details-section" data-section="details">
                        <?php 
                        if ($components_status['details']) {
                            // Use Details Component
                            try {
                                $details_component = new HappyPlace\Components\Listing\Details([
                                    'listing_id' => $listing_id,
                                    'property_details' => $property_details,
                                    'variant' => 'detailed',
                                    'show_categories' => ['basic', 'dimensions', 'utilities', 'features']
                                ]);
                                $details_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Details Component Error: ' . $e->getMessage());
                                hph_get_template_part('listing/details', null, $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('listing/details', null, $template_args);
                        }
                        ?>
                    </section>

                    <!-- Features & Amenities -->
                    <section class="property-features-section" data-section="features">
                        <?php 
                        if ($components_status['features'] && !empty($features_data)) {
                            // Use Features Component
                            try {
                                $features_component = new HappyPlace\Components\Listing\Features([
                                    'listing_id' => $listing_id,
                                    'features_data' => $features_data,
                                    'layout' => 'grid',
                                    'show_categories' => true,
                                    'show_icons' => true
                                ]);
                                $features_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Features Component Error: ' . $e->getMessage());
                                hph_get_template_part('listing/features', null, $template_args);
                            }
                            
                        } elseif (!empty($features_data)) {
                            // Template part fallback
                            hph_get_template_part('listing/features', null, $template_args);
                        }
                        ?>
                    </section>

                    <!-- Photo Gallery (if not in hero) -->
                    <?php if (!empty($gallery_data) && count($gallery_data) > 3) : ?>
                    <section class="property-gallery-section" data-section="gallery">
                        <?php 
                        if ($components_status['gallery']) {
                            // Use Gallery Component
                            try {
                                $gallery_component = new HappyPlace\Components\Listing\Gallery([
                                    'listing_id' => $listing_id,
                                    'gallery_data' => $gallery_data,
                                    'variant' => 'detailed',
                                    'layout' => 'masonry',
                                    'show_lightbox' => true,
                                    'lazy_load' => true
                                ]);
                                $gallery_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Gallery Component Error: ' . $e->getMessage());
                                hph_get_template_part('listing/gallery', null, $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('listing/gallery', null, $template_args);
                        }
                        ?>
                    </section>
                    <?php endif; ?>

                    <!-- Neighborhood Information -->
                    <section class="neighborhood-section" data-section="neighborhood">
                        <?php hph_get_template_part('listing/neighborhood', null, $template_args); ?>
                    </section>

                    <!-- Similar Listings -->
                    <?php if (!empty($similar_listings)) : ?>
                    <section class="similar-listings-section" data-section="similar">
                        <?php 
                        if ($components_status['similar_listings']) {
                            // Use Similar Listings Component
                            try {
                                $similar_component = new HappyPlace\Components\Listing\Similar_Grid([
                                    'current_listing_id' => $listing_id,
                                    'similar_listings' => $similar_listings,
                                    'columns' => 3,
                                    'show_title' => true,
                                    'title' => 'Similar Properties'
                                ]);
                                $similar_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Similar Listings Component Error: ' . $e->getMessage());
                                hph_get_template_part('listing/similar-listings', null, $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('listing/similar-listings', null, $template_args);
                        }
                        ?>
                    </section>
                    <?php endif; ?>

                </main>

                <!-- Sidebar -->
                <aside class="listing-sidebar" role="complementary">
                    
                    <!-- Agent Card -->
                    <div class="sidebar-section agent-section">
                        <?php 
                        if ($components_status['agent_card'] && !empty($agent_data)) {
                            // Use Agent Card Component
                            try {
                                $agent_component = new HappyPlace\Components\Agent\Card([
                                    'agent_id' => $agent_data['id'] ?? 0,
                                    'agent_data' => $agent_data,
                                    'variant' => 'sidebar',
                                    'show_contact_form' => true,
                                    'show_listings_count' => true
                                ]);
                                $agent_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Agent Card Component Error: ' . $e->getMessage());
                                hph_get_template_part('agent/card', 'sidebar', $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('agent/card', 'sidebar', $template_args);
                        }
                        ?>
                    </div>

                    <!-- Mortgage Calculator -->
                    <div class="sidebar-section calculator-section">
                        <?php 
                        if ($components_status['mortgage_calculator'] && !empty($financial_data)) {
                            // Use Mortgage Calculator Component
                            try {
                                $calculator_component = new HappyPlace\Components\Tools\Mortgage_Calculator([
                                    'listing_id' => $listing_id,
                                    'financial_data' => $financial_data,
                                    'variant' => 'sidebar',
                                    'show_taxes' => true,
                                    'show_insurance' => true,
                                    'show_pmi' => true
                                ]);
                                $calculator_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Calculator Component Error: ' . $e->getMessage());
                                hph_get_template_part('tools/mortgage-calculator', 'sidebar', $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('tools/mortgage-calculator', 'sidebar', $template_args);
                        }
                        ?>
                    </div>

                    <!-- Contact Form -->
                    <div class="sidebar-section contact-section">
                        <?php 
                        if ($components_status['contact_form']) {
                            // Use Contact Form Component
                            try {
                                $contact_component = new HappyPlace\Components\Forms\Contact_Form([
                                    'listing_id' => $listing_id,
                                    'agent_id' => $agent_data['id'] ?? 0,
                                    'variant' => 'sidebar',
                                    'form_type' => 'listing_inquiry',
                                    'show_phone' => true,
                                    'show_tour_request' => true
                                ]);
                                $contact_component->display();
                                
                            } catch (Exception $e) {
                                error_log('HPH Contact Form Component Error: ' . $e->getMessage());
                                hph_get_template_part('forms/contact-form', 'sidebar', $template_args);
                            }
                            
                        } else {
                            // Template part fallback
                            hph_get_template_part('forms/contact-form', 'sidebar', $template_args);
                        }
                        ?>
                    </div>

                    <!-- Additional Sidebar Widgets -->
                    <?php if (is_active_sidebar('listing-sidebar')) : ?>
                        <div class="sidebar-section widgets-section">
                            <?php dynamic_sidebar('listing-sidebar'); ?>
                        </div>
                    <?php endif; ?>

                </aside>

            </div>
        </div>
    </div>

    <!-- Component Testing Debug Panel (Development Only) -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG && isset($_GET['component_test'])) : ?>
    <div id="component-debug-panel" style="position: fixed; bottom: 0; right: 0; background: #000; color: #fff; padding: 20px; max-width: 400px; max-height: 300px; overflow: auto; z-index: 9999; font-family: monospace; font-size: 12px;">
        <h4 style="margin: 0 0 10px 0; color: #4CAF50;">🧪 Component Test Results</h4>
        
        <div style="margin-bottom: 15px;">
            <strong>Components Available:</strong><br>
            <?php foreach ($components_status as $component => $available) : ?>
                <span style="color: <?php echo $available ? '#4CAF50' : '#f44336'; ?>;">
                    <?php echo $available ? '✅' : '❌'; ?> <?php echo ucwords(str_replace('_', ' ', $component)); ?>
                </span><br>
            <?php endforeach; ?>
        </div>

        <div style="margin-bottom: 15px;">
            <strong>Performance:</strong><br>
            Load Time: <?php echo round((microtime(true) - $template_start_time) * 1000, 2); ?>ms<br>
            Memory: <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?>MB<br>
            Queries: <?php echo get_num_queries() - ($debug_info['queries_before'] ?? 0); ?><br>
        </div>

        <div>
            <strong>Data Sources:</strong><br>
            Bridge Functions: <?php echo $debug_info['data_sources']['bridge_functions'] ? '✅' : '❌'; ?><br>
            Plugin Active: <?php echo $debug_info['data_sources']['plugin_active'] ? '✅' : '❌'; ?><br>
            ACF Active: <?php echo $debug_info['data_sources']['acf_active'] ? '✅' : '❌'; ?><br>
        </div>

        <div style="margin-top: 10px; font-size: 10px; opacity: 0.7;">
            Add ?component_test=1 to URL to see this panel
        </div>
    </div>
    <?php endif; ?>

</article>

<?php
// Performance logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    $template_end_time = microtime(true);
    $execution_time = ($template_end_time - $template_start_time) * 1000;
    error_log("HPH Single Listing Template: {$execution_time}ms, " . get_num_queries() . " queries, " . round(memory_get_usage(true) / 1024 / 1024, 2) . "MB memory");
}

get_footer();

/**
 * Fallback functions for when bridge functions aren't available
 */
function hph_fallback_get_listing_data($listing_id) {
    return [
        'id' => $listing_id,
        'title' => get_the_title($listing_id),
        'description' => get_the_content(null, false, $listing_id),
        'price' => get_post_meta($listing_id, 'price', true) ?: 'Contact for Price',
        'status' => get_post_meta($listing_id, 'status', true) ?: 'Available',
        'address' => get_post_meta($listing_id, 'address', true) ?: '',
        'bedrooms' => get_post_meta($listing_id, 'bedrooms', true) ?: '',
        'bathrooms' => get_post_meta($listing_id, 'bathrooms', true) ?: '',
        'square_feet' => get_post_meta($listing_id, 'square_feet', true) ?: ''
    ];
}

function hph_fallback_get_hero_data($listing_id) {
    return [
        'title' => get_the_title($listing_id),
        'price' => get_post_meta($listing_id, 'price', true) ?: 'Contact for Price',
        'address' => get_post_meta($listing_id, 'address', true) ?: '',
        'featured_image' => get_the_post_thumbnail_url($listing_id, 'full')
    ];
}

function hph_fallback_get_gallery_data($listing_id) {
    $images = [];
    if (has_post_thumbnail($listing_id)) {
        $images[] = [
            'url' => get_the_post_thumbnail_url($listing_id, 'full'),
            'alt' => get_the_title($listing_id)
        ];
    }
    return $images;
}

function hph_fallback_get_property_details($listing_id) {
    return [
        'bedrooms' => get_post_meta($listing_id, 'bedrooms', true) ?: 'N/A',
        'bathrooms' => get_post_meta($listing_id, 'bathrooms', true) ?: 'N/A',
        'square_feet' => get_post_meta($listing_id, 'square_feet', true) ?: 'N/A',
        'lot_size' => get_post_meta($listing_id, 'lot_size', true) ?: 'N/A',
        'year_built' => get_post_meta($listing_id, 'year_built', true) ?: 'N/A',
        'property_type' => get_post_meta($listing_id, 'property_type', true) ?: 'Single Family Home'
    ];
}

function hph_fallback_get_features($listing_id) {
    return [
        'interior' => ['Hardwood Floors', 'Updated Kitchen', 'Walk-in Closets'],
        'exterior' => ['Private Yard', 'Garage', 'Patio'],
        'community' => ['Swimming Pool', 'Fitness Center', 'Clubhouse']
    ];
}

function hph_fallback_get_agent_data($listing_id) {
    return [
        'id' => 1,
        'name' => 'Real Estate Agent',
        'email' => 'agent@example.com',
        'phone' => '(555) 123-4567',
        'bio' => 'Experienced real estate professional.',
        'photo' => get_template_directory_uri() . '/assets/images/default-agent.jpg'
    ];
}

function hph_fallback_get_financial_data($listing_id) {
    $price = get_post_meta($listing_id, 'price', true);
    $price_numeric = is_numeric($price) ? intval($price) : 350000;
    
    return [
        'price' => $price_numeric,
        'down_payment_percent' => 20,
        'interest_rate' => 6.5,
        'loan_term' => 30,
        'property_taxes' => round($price_numeric * 0.012 / 12),
        'insurance' => round($price_numeric * 0.003 / 12),
        'hoa_fees' => 0
    ];
}

function hph_fallback_get_similar_listings($listing_id, $count = 3) {
    $similar = get_posts([
        'post_type' => 'listing',
        'posts_per_page' => $count,
        'exclude' => [$listing_id],
        'meta_key' => 'price',
        'orderby' => 'rand'
    ]);
    
    return array_map(function($post) {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'price' => get_post_meta($post->ID, 'price', true) ?: 'Contact for Price',
            'image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'url' => get_permalink($post->ID)
        ];
    }, $similar);
}

function hph_emergency_fallback_data($listing_id) {
    return [
        'id' => $listing_id,
        'title' => 'Property Listing',
        'description' => 'Property details will be available soon.',
        'price' => 'Contact for Price',
        'status' => 'Available'
    ];
}
?>