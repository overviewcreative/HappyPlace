<?php
/**
 * Happy Place Theme Functions
 * Clean version focused on SCSS system
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Theme constants
define('HPH_THEME_VERSION', '2.0.0');
define('HPH_THEME_PATH', get_template_directory());
define('HPH_THEME_URL', get_template_directory_uri());
define('HPH_THEME_DIR', get_template_directory());
define('HPH_ASSETS_URI', get_template_directory_uri() . '/assets');

// =============================================================================
// THEME SETUP & CONFIGURATION
// =============================================================================

/**
 * Theme Support Setup
 * Basic WordPress features we need to enable
 */
function hph_theme_support() {
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    
    // Initialize shortcode manager
    HPH_Shortcode_Manager::get_instance();
}
add_action('after_setup_theme', 'hph_theme_support');

// =============================================================================
// IMAGE OPTIMIZATION & MEDIA HANDLING
// =============================================================================

/**
 * Image Optimization and WebP Support
 */
function happy_place_image_optimization() {
    // Enable native lazy loading for images
    add_filter('wp_lazy_loading_enabled', '__return_true');
    
    // Add WebP support
    add_filter('upload_mimes', function($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    });
    
    // Add srcset and sizes attributes for responsive images
    add_filter('wp_calculate_image_srcset', 'happy_place_custom_srcset', 10, 5);
    
    // Optimize image quality
    add_filter('jpeg_quality', function() { return 85; });
    add_filter('wp_editor_set_quality', function() { return 85; });
}
add_action('init', 'happy_place_image_optimization');

/**
 * Custom srcset for real estate images
 */
function happy_place_custom_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    // Add WebP versions if they exist
    foreach ($sources as $width => $source) {
        $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source['url']);
        $webp_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $webp_url);
        
        if (file_exists($webp_path) && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            $sources[$width]['url'] = $webp_url;
        }
    }
    
    return $sources;
}

/**
 * Enhanced image rendering for listings
 */
function happy_place_get_listing_image($post_id = null, $size = 'listing-large', $attr = array()) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Default attributes
    $default_attr = array(
        'class' => 'hph-listing-image',
        'loading' => 'lazy',
        'decoding' => 'async'
    );
    
    $attr = array_merge($default_attr, $attr);
    
    // Try main_photo ACF field first
    $main_photo = get_field('main_photo', $post_id);
    if ($main_photo) {
        $image_id = is_array($main_photo) ? $main_photo['ID'] : $main_photo;
        return wp_get_attachment_image($image_id, $size, false, $attr);
    }
    
    // Fallback to featured image
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail($post_id, $size, $attr);
    }
    
    // Fallback to placeholder
    return happy_place_get_image_placeholder($size, $attr);
}

/**
 * Get image placeholder
 */
function happy_place_get_image_placeholder($size = 'listing-large', $attr = array()) {
    $sizes = array(
        'listing-thumbnail' => array(400, 300),
        'listing-medium' => array(600, 450),
        'listing-large' => array(1200, 800),
        'listing-hero' => array(1920, 1080),
        'agent-avatar' => array(150, 150),
        'agent-thumbnail' => array(200, 200)
    );
    
    $dimensions = isset($sizes[$size]) ? $sizes[$size] : array(400, 300);
    $width = $dimensions[0];
    $height = $dimensions[1];
    
    $default_attr = array(
        'class' => 'hph-image-placeholder',
        'alt' => 'Property Image Coming Soon',
        'width' => $width,
        'height' => $height
    );
    
    $attr = array_merge($default_attr, $attr);
    
    // Create a simple SVG placeholder
    $svg_content = sprintf(
        '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder">
            <rect width="100%%" height="100%%" fill="#e2e8f0"/>
            <text x="50%%" y="50%%" fill="#64748b" font-family="sans-serif" font-size="14" text-anchor="middle" dy=".35em">
                📷 Property Photo
            </text>
        </svg>',
        $width,
        $height
    );
    
    $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
    
    $attr_string = '';
    foreach ($attr as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }
    
    return sprintf('<img src="%s"%s>', $data_uri, $attr_string);
}

/**
 * Gallery function for listing images
 */
function happy_place_get_listing_gallery($post_id = null, $size = 'listing-gallery', $limit = 12) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $gallery = get_field('photo_gallery', $post_id);
    if (!$gallery || !is_array($gallery)) {
        return '';
    }
    
    $images = array_slice($gallery, 0, $limit);
    $output = '<div class="hph-listing-gallery">';
    
    foreach ($images as $index => $image) {
        $image_id = is_array($image) ? $image['ID'] : $image;
        $attr = array(
            'class' => 'hph-gallery-image',
            'loading' => $index < 4 ? 'eager' : 'lazy', // Load first 4 eagerly
            'data-index' => $index
        );
        
        $output .= '<div class="hph-gallery-item">';
        $output .= wp_get_attachment_image($image_id, $size, false, $attr);
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}

// =============================================================================
// ASSET MANAGEMENT
// =============================================================================

/**
 * Include Shortcode System
 */
require_once get_template_directory() . '/inc/shortcodes/class-shortcode-manager.php';

/**
 * Include Shortcode Admin Interface
 */
if (is_admin()) {
    require_once get_template_directory() . '/inc/shortcodes/class-shortcode-admin.php';
}

/**
 * MAIN ASSET ENQUEUING FUNCTION - Consolidated SCSS Build System
 * This is the only asset function we need
 */
function happy_place_enqueue_assets() {
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    $theme_version = HPH_THEME_VERSION;
    
    // CACHE BUSTING - Force fresh CSS loading
    $cache_bust = time(); // Current timestamp to force refresh
    
    // Debug info
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('HPH Debug: Theme dir = ' . $theme_dir);
        error_log('HPH Debug: Theme URI = ' . $theme_uri);
        error_log('HPH Debug: Cache bust = ' . $cache_bust);
        
        // Add debug script for hero carousel
        wp_enqueue_script(
            'happy-place-hero-debug',
            $theme_uri . '/debug-hero-carousel.js',
            array(),
            $cache_bust,
            true
        );
    }
    
    // Clear any existing enqueued styles 
    wp_dequeue_style('happy-place-main');
    wp_deregister_style('happy-place-main');
    wp_dequeue_style('happy-place-fallback');
    wp_deregister_style('happy-place-fallback');
    
    // Load webpack manifest for proper asset versioning
    $manifest_path = get_template_directory() . '/assets/dist/manifest.json';
    $manifest = [];
    
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true) ?: [];
    }
    
    // Primary: Enqueue the compiled SCSS main.css using webpack manifest
    if (isset($manifest['main.css'])) {
        $main_css_url = str_replace(' ', '%20', $theme_uri . '/assets/dist/' . $manifest['main.css']);
        wp_enqueue_style(
            'happy-place-main',
            $main_css_url,
            array(),
            null, // Use webpack hash for versioning instead of cache_bust
            'all'
        );
    } else {
        // Fallback to direct path if manifest not available
        $main_css_url = str_replace(' ', '%20', $theme_uri . '/assets/dist/css/main.css');
        wp_enqueue_style(
            'happy-place-main',
            $main_css_url,
            array(),
            $cache_bust,
            'all'
        );
    }
    
    // FontAwesome - Load from CDN for reliability
    wp_enqueue_style(
        'fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );
    
    // Secondary: WordPress theme style.css (contains all compiled CSS now)
    wp_enqueue_style(
        'happy-place-style', 
        get_stylesheet_uri(), 
        array('fontawesome'), // Load after FontAwesome
        $cache_bust
    );
    
    // Shortcode styles (conditional loading handled by shortcode manager)
    wp_enqueue_style(
        'hph-shortcode-styles',
        get_template_directory_uri() . '/assets/src/scss/shortcodes.scss',
        array('happy-place-style'),
        $cache_bust
    );
    
    // Debug CSS loading
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('HPH Debug: Main CSS URL = ' . $main_css_url . '?v=' . $cache_bust);
        error_log('HPH Debug: Style.css URL = ' . get_stylesheet_uri());
    }
    
    // Template-specific CSS for single listing
    if (is_singular('listing') || is_page_template('single-listing.php')) {
        // Load from webpack build or fallback to source
        $manifest_path = get_template_directory() . '/assets/dist/manifest.json';
        
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            
            // Load single-listing specific CSS from webpack build
            if (isset($manifest['single-listing.css'])) {
                $listing_css_path = get_template_directory() . '/assets/dist/' . $manifest['single-listing.css'];
                if (file_exists($listing_css_path)) {
                    wp_enqueue_style(
                        'happy-place-single-listing',
                        $theme_uri . '/assets/dist/' . $manifest['single-listing.css'],
                        array('happy-place-main'),
                        filemtime($listing_css_path)
                    );
                }
            }
        } else {
            // Fallback: Check if compiled CSS exists in dist folder
            $dist_css_file = get_template_directory() . '/assets/dist/css/single-listing.css';
            if (file_exists($dist_css_file)) {
                wp_enqueue_style(
                    'happy-place-single-listing',
                    $theme_uri . '/assets/dist/css/single-listing.css',
                    array('happy-place-main'),
                    filemtime($dist_css_file)
                );
            }
        }
        
        // Add critical inline CSS for grid layout
        $inline_css = '
        /* Single Listing Grid Layout */
        .hph-content-grid {
            display: grid !important;
            grid-template-columns: 1fr 320px !important;
            gap: var(--hph-spacing-8, 2rem) !important;
        }
        
        .hph-main-content {
            min-width: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: var(--hph-spacing-8, 2rem) !important;
        }
        
        .hph-sidebar {
            position: sticky !important;
            top: var(--hph-spacing-6, 1.5rem) !important;
            width: 320px !important;
            flex-shrink: 0 !important;
        }
        
        @media (max-width: 1024px) {
            .hph-content-grid {
                grid-template-columns: 1fr !important;
            }
            
            .hph-sidebar {
                position: static !important;
                width: 100% !important;
                order: -1 !important;
            }
        }';
        
        wp_add_inline_style('happy-place-main', $inline_css);
    }
    
    // Main JavaScript - Use built version from manifest if available
    $manifest_path = $theme_dir . '/assets/dist/manifest.json';
    $main_js_loaded = false;
    
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);
        
        if (isset($manifest['main.js'])) {
            $main_js_path = $theme_dir . '/assets/dist/' . $manifest['main.js'];
            if (file_exists($main_js_path)) {
                wp_enqueue_script(
                    'happy-place-main',
                    $theme_uri . '/assets/dist/' . $manifest['main.js'],
                    array('jquery'),
                    filemtime($main_js_path),
                    true
                );
                
                // Add module type attribute for ES6 imports
                add_filter('script_loader_tag', function($tag, $handle) {
                    if ($handle === 'happy-place-main') {
                        return str_replace('<script ', '<script type="module" ', $tag);
                    }
                    return $tag;
                }, 10, 2);
                
                $main_js_loaded = true;
            }
        }
    }
    
    // Fallback to source file if webpack build not available
    if (!$main_js_loaded) {
        $main_js_file = $theme_dir . '/assets/src/js/main.js';
        if (file_exists($main_js_file)) {
            wp_enqueue_script(
                'happy-place-main',
                $theme_uri . '/assets/src/js/main.js',
                array('jquery'),
                $cache_bust, // Cache bust JS too
                true
            );
            
            // Add module type attribute for ES6 imports
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'happy-place-main') {
                    return str_replace('<script ', '<script type="module" ', $tag);
                }
                return $tag;
            }, 10, 2);
        }
    }
    
    // Localize script for AJAX if main script was loaded
    if ($main_js_loaded || file_exists($theme_dir . '/assets/src/js/main.js')) {
        wp_localize_script('happy-place-main', 'happyPlaceAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('happy_place_nonce'),
        ));
    }
    
    // Template-specific JavaScript for single listing
    if (is_singular('listing') || is_page_template('single-listing.php')) {
        $manifest_path = get_template_directory() . '/assets/dist/manifest.json';
        $script_loaded = false;
        
        // Load single-listing-init.js first for immediate functionality
        $init_js_file = get_template_directory() . '/assets/dist/js/single-listing-init.js';
        if (file_exists($init_js_file)) {
            wp_enqueue_script(
                'happy-place-single-listing-init',
                $theme_uri . '/assets/dist/js/single-listing-init.js',
                array(),
                filemtime($init_js_file),
                false // Load in head for immediate execution
            );
        }
        
        // Try to load from webpack build first
        if (file_exists($manifest_path)) {
            $manifest = json_decode(file_get_contents($manifest_path), true);
            
            if (isset($manifest['single-listing.js'])) {
                $listing_js_path = get_template_directory() . '/assets/dist/' . $manifest['single-listing.js'];
                if (file_exists($listing_js_path)) {
                    wp_enqueue_script(
                        'happy-place-single-listing',
                        $theme_uri . '/assets/dist/' . $manifest['single-listing.js'],
                        array('happy-place-main', 'happy-place-single-listing-init'),
                        filemtime($listing_js_path),
                        true
                    );
                    
                    // Add module type attribute for ES6 imports
                    add_filter('script_loader_tag', function($tag, $handle) {
                        if ($handle === 'happy-place-single-listing') {
                            return str_replace('<script ', '<script type="module" ', $tag);
                        }
                        return $tag;
                    }, 10, 2);
                    
                    $script_loaded = true;
                }
            }
        }
        
        // Fallback to source file if webpack build not available
        if (!$script_loaded) {
            $single_listing_js_file = get_template_directory() . '/assets/src/js/single-listing.js';
            if (file_exists($single_listing_js_file)) {
                wp_enqueue_script(
                    'happy-place-single-listing',
                    $theme_uri . '/assets/src/js/single-listing.js',
                    array('happy-place-main', 'happy-place-single-listing-init'),
                    filemtime($single_listing_js_file),
                    true
                );
                
                // Add module type attribute for ES6 imports
                add_filter('script_loader_tag', function($tag, $handle) {
                    if ($handle === 'happy-place-single-listing') {
                        return str_replace('<script ', '<script type="module" ', $tag);
                    }
                    return $tag;
                }, 10, 2);
                
                $script_loaded = true;
            }
        }
        
        // Localize script data if any script was loaded
        if ($script_loaded) {
            // Get comprehensive listing data
            $listing_data = [];
            if (function_exists('hph_get_all_listing_data')) {
                $listing_data = hph_get_all_listing_data(get_the_ID());
            }
            
            // Localize with enhanced data structure
            wp_localize_script('happy-place-single-listing', 'happyPlaceData', array(
                'listing' => $listing_data,
                'listingId' => get_the_ID(),
                'mapApiKey' => get_theme_mod('google_maps_api_key', ''),
                'userId' => get_current_user_id(),
                'isLoggedIn' => is_user_logged_in(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_nonce'),
                'themeUrl' => $theme_uri,
                'debugMode' => defined('WP_DEBUG') && WP_DEBUG
            ));
            
            // Legacy compatibility
            wp_localize_script('happy-place-single-listing', 'hphListingData', array(
                'listing' => $listing_data,
                'mapApiKey' => get_theme_mod('google_maps_api_key', ''),
                'userId' => get_current_user_id(),
                'isLoggedIn' => is_user_logged_in(),
            ));
        }
    }
}
add_action('wp_enqueue_scripts', 'happy_place_enqueue_assets', 5);

// CACHE CLEARING FUNCTIONS
function happy_place_clear_all_caches() {
    // Clear WordPress object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Clear opcache if available
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Clear any transients related to our theme
    delete_transient('happy_place_css_version');
    
    // Force browser cache refresh by updating theme mod
    set_theme_mod('css_cache_bust', time());
    
    error_log('HPH: All caches cleared at ' . date('Y-m-d H:i:s'));
}

// Clear caches when theme is activated
add_action('after_switch_theme', 'happy_place_clear_all_caches');

// Clear caches when customizer saves
add_action('customize_save_after', 'happy_place_clear_all_caches');

/**
 * Debug Asset Loading - Only for Development
 * Add ?debug_assets=1 to any URL to see debug info
 */
function debug_happy_place_assets() {
    if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('administrator') || !isset($_GET['debug_assets'])) {
        return;
    }
    
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();
    
    echo '<div style="position: fixed; top: 20px; right: 20px; background: #000; color: #fff; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 12px; z-index: 9999; max-width: 400px; max-height: 80vh; overflow: auto;">';
    echo '<h3>🔧 Asset Debug Info</h3>';
    echo '<strong>Theme Directory:</strong> ' . esc_html($theme_dir) . '<br>';
    echo '<strong>Theme URI:</strong> ' . esc_html($theme_uri) . '<br><br>';
    
    // Check critical files
    $files_to_check = array(
        'SCSS Source' => '/assets/src/scss/main.scss',
        'CSS Built' => '/assets/dist/css/main.css',
        'JS Built' => '/assets/dist/js/main.js',
        'Manifest' => '/assets/dist/manifest.json',
        'Webpack Config' => '/webpack.config.js',
        'Package.json' => '/package.json',
    );
    
    foreach ($files_to_check as $name => $path) {
        $full_path = $theme_dir . $path;
        $exists = file_exists($full_path);
        $size = $exists ? filesize($full_path) : 0;
        
        echo '<strong>' . esc_html($name) . ':</strong> ';
        echo $exists ? '✅ EXISTS' : '❌ MISSING';
        echo ' (' . ($size > 0 ? number_format($size) . ' bytes' : 'empty') . ')<br>';
    }
    
    // Check manifest content
    $manifest_path = $theme_dir . '/assets/dist/manifest.json';
    if (file_exists($manifest_path)) {
        echo '<br><strong>Manifest Contents:</strong><br>';
        $manifest = json_decode(file_get_contents($manifest_path), true);
        if ($manifest) {
            foreach ($manifest as $key => $value) {
                echo esc_html($key) . ' → ' . esc_html($value) . '<br>';
            }
        } else {
            echo 'Invalid JSON<br>';
        }
    }
    
    // Check if CSS is being enqueued
    global $wp_styles;
    echo '<br><strong>Enqueued Styles:</strong><br>';
    if (isset($wp_styles->queue)) {
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'happy-place') !== false) {
                echo esc_html($handle) . '<br>';
            }
        }
    }
    
    echo '</div>';
}
add_action('wp_head', 'check_scss_build');

// =============================================================================
// POST TYPES & META BOXES  
// =============================================================================

/**
 * Register listing post type
 */
function register_listing_post_type() {
    if (!post_type_exists('listing')) {
        register_post_type('listing', array(
            'labels' => array(
                'name' => 'Listings',
                'singular_name' => 'Listing',
                'add_new_item' => 'Add New Listing',
                'edit_item' => 'Edit Listing',
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'listings'),
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-admin-home',
            'show_in_rest' => true,
        ));
    }
}
add_action('init', 'register_listing_post_type');

/**
 * Add custom meta boxes for listing fields
 */
function add_listing_meta_boxes() {
    add_meta_box(
        'listing_details',
        'Listing Details',
        'listing_details_callback',
        'listing',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_listing_meta_boxes');

function listing_details_callback($post) {
    wp_nonce_field('listing_details_nonce', 'listing_details_nonce');
    
    $fields = array(
        'listing_price' => 'Price',
        'listing_status' => 'Status',
        'listing_address' => 'Address',
        'bedrooms' => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'square_feet' => 'Square Feet',
        'lot_size' => 'Lot Size',
        'year_built' => 'Year Built',
    );
    
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $value = get_post_meta($post->ID, $key, true);
        echo '<tr>';
        echo '<th><label for="' . $key . '">' . $label . '</label></th>';
        echo '<td><input type="text" id="' . $key . '" name="' . $key . '" value="' . esc_attr($value) . '" style="width: 100%;" /></td>';
        echo '</tr>';
    }
    echo '</table>';
}

/**
 * Save listing meta
 */
function save_listing_meta($post_id) {
    if (!isset($_POST['listing_details_nonce']) || !wp_verify_nonce($_POST['listing_details_nonce'], 'listing_details_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    $fields = array(
        'listing_price', 'listing_status', 'listing_address',
        'bedrooms', 'bathrooms', 'square_feet', 'lot_size', 'year_built'
    );
    
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'save_listing_meta');

// =============================================================================
// WIDGETS & SIDEBARS
// =============================================================================

/**
 * Register widget areas
 */

/**
 * AJAX handler for contact form
 */
function handle_listing_contact_form() {
    // Simple contact form handler
    if (!isset($_POST['name']) || !isset($_POST['email'])) {
        wp_send_json_error('Missing required fields.');
    }
    
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $message = sanitize_textarea_field($_POST['message']);
    
    // Send email
    $to = get_option('admin_email');
    $subject = 'New Listing Inquiry from ' . $name;
    $body = "Name: $name\nEmail: $email\nPhone: $phone\n\nMessage:\n$message";
    
    $sent = wp_mail($to, $subject, $body);
    
    if ($sent) {
        wp_send_json_success('Message sent successfully!');
    } else {
        wp_send_json_error('Failed to send message.');
    }
}
add_action('wp_ajax_listing_contact_form', 'handle_listing_contact_form');
add_action('wp_ajax_nopriv_listing_contact_form', 'handle_listing_contact_form');

/**
 * Register widget areas
 */
function register_happy_place_sidebars() {
    register_sidebar(array(
        'name' => 'Main Sidebar',
        'id' => 'sidebar-1',
        'description' => 'Main sidebar area',
        'before_widget' => '<div class="widget">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="widget-title">',
        'after_title' => '</h3>',
    ));
}
add_action('widgets_init', 'register_happy_place_sidebars');

// =============================================================================
// MAINTENANCE & CLEANUP
// =============================================================================

/**
 * Clean up - remove conflicting functions from legacy code
 */
function clean_up_legacy_hooks() {
    // Remove duplicate asset enqueuing functions
    remove_action('wp_enqueue_scripts', 'hph_enqueue_assets');
    remove_action('wp_enqueue_scripts', 'enqueue_listing_assets');
    
    // Remove duplicate debug functions
    remove_action('wp_head', 'hph_debug_asset_paths');
    remove_action('wp_footer', 'hph_debug_asset_paths');
    remove_action('admin_head', 'hph_debug_asset_paths');
    remove_action('admin_footer', 'hph_debug_asset_paths');
}
add_action('init', 'clean_up_legacy_hooks', 1);

/**
 * Build check - verify our SCSS system is working (Development Only)
 */
function check_scss_build() {
    if (!defined('WP_DEBUG') || !WP_DEBUG || !current_user_can('administrator') || !isset($_GET['check_build'])) {
        return;
    }
    
    $theme_dir = get_template_directory();
    
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ddd; border-radius: 8px; font-family: monospace;">';
    echo '<h3>🔍 SCSS Build Check</h3>';
    
    // Check build process
    $checks = array(
        'Source SCSS exists' => file_exists($theme_dir . '/assets/src/scss/main.scss'),
        'Built CSS exists' => file_exists($theme_dir . '/assets/dist/css/main.css'),
        'Webpack config exists' => file_exists($theme_dir . '/webpack.config.js'),
        'Package.json exists' => file_exists($theme_dir . '/package.json'),
        'Node modules exist' => file_exists($theme_dir . '/node_modules'),
        'Manifest exists' => file_exists($theme_dir . '/assets/dist/manifest.json'),
    );
    
    foreach ($checks as $check => $result) {
        $status = $result ? '✅ PASS' : '❌ FAIL';
        echo "<div>" . esc_html($check) . ": $status</div>";
    }
    
    // Check if CSS has content
    $css_file = $theme_dir . '/assets/dist/css/main.css';
    if (file_exists($css_file)) {
        $css_content = file_get_contents($css_file);
        $has_listing_styles = strpos($css_content, 'listing-single') !== false;
        echo '<div>CSS contains listing styles: ' . ($has_listing_styles ? '✅ YES' : '❌ NO') . '</div>';
        echo '<div>CSS file size: ' . number_format(filesize($css_file)) . ' bytes</div>';
    }
    
    echo '<hr><h4>Next Steps:</h4>';
    echo '<div>1. Run: npm install</div>';
    echo '<div>2. Run: npm run build</div>';
    echo '<div>3. Check for build errors in terminal</div>';
    echo '<div>4. Refresh this page to see updated results</div>';
    echo '</div>';
}
add_action('wp_head', 'check_scss_build');

// =============================================================================
// CORE THEME INCLUDES
// =============================================================================

// Load legacy compatibility if needed
if (file_exists(get_template_directory() . '/inc/template-bridge.php')) {
    require_once get_template_directory() . '/inc/template-bridge.php';
}

// Load enhanced hero AJAX handlers
if (file_exists(get_template_directory() . '/inc/ajax/hero-handlers.php')) {
    require_once get_template_directory() . '/inc/ajax/hero-handlers.php';
}

// Initialize Template Loader
if (file_exists(get_template_directory() . '/inc/core/Template_Loader.php')) {
    require_once get_template_directory() . '/inc/core/Template_Loader.php';
    
    // Initialize the Template_Loader instance to hook into WordPress
    add_action('after_setup_theme', function() {
        \HappyPlace\Core\Template_Loader::get_instance();
    }, 5);
}

// Load template helper functions
if (file_exists(get_template_directory() . '/inc/template-helpers.php')) {
    require_once get_template_directory() . '/inc/template-helpers.php';
}

// Development-only debug files (removed in cleanup)
// Debug files have been removed to clean up the theme

// =============================================================================
// AJAX HANDLERS
// =============================================================================

/**
 * Enhanced AJAX handlers for single listing page functionality
 */

/**
 * Handle book property showing requests
 */
function handle_book_property_showing() {
    // Check nonce first
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'happy_place_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    // Rate limiting - prevent spam
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $rate_limit_key = 'showing_request_' . md5($user_ip);
    $recent_requests = get_transient($rate_limit_key);
    
    if ($recent_requests && $recent_requests >= 3) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait before submitting again.'));
        return;
    }

    // Sanitize inputs
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $date = sanitize_text_field($_POST['date'] ?? '');
    $time = sanitize_text_field($_POST['time'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    // Validate required fields
    if (empty($name) || empty($email) || empty($date) || empty($time)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        return;
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        return;
    }

    // Get listing information
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'listing') {
        wp_send_json_error(array('message' => 'Invalid listing.'));
        return;
    }

    // Get agent email (from listing or default)
    $agent_email = get_field('agent_email', $listing_id);
    if (!$agent_email) {
        $agent_email = get_option('admin_email');
    }

    // Prepare email content
    $subject = sprintf('Showing Request for %s', get_the_title($listing_id));
    $listing_url = get_permalink($listing_id);
    $listing_address = get_field('address', $listing_id);

    $email_content = sprintf(
        "New showing request received:\n\n" .
        "Property: %s\n" .
        "Address: %s\n" .
        "Listing URL: %s\n\n" .
        "Requester Information:\n" .
        "Name: %s\n" .
        "Email: %s\n" .
        "Phone: %s\n\n" .
        "Requested Date: %s\n" .
        "Requested Time: %s\n\n" .
        "Message:\n%s\n\n" .
        "Please contact the requester to schedule the showing.",
        get_the_title($listing_id),
        $listing_address ?: 'Address not specified',
        $listing_url,
        $name,
        $email,
        $phone ?: 'Not provided',
        $date,
        $time,
        $message ?: 'No additional message'
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $email
    );

    // Send email
    $email_sent = wp_mail($agent_email, $subject, $email_content, $headers);

    if ($email_sent) {
        // Track request for rate limiting
        set_transient($rate_limit_key, ($recent_requests ?: 0) + 1, 300); // 5 minutes
        
        // Log the showing request (optional)
        do_action('hph_showing_request_submitted', array(
            'listing_id' => $listing_id,
            'requester_name' => $name,
            'requester_email' => $email,
            'requested_date' => $date,
            'requested_time' => $time
        ));

        wp_send_json_success(array(
            'message' => 'Your showing request has been sent successfully!'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to send showing request. Please try again.'));
    }
}
add_action('wp_ajax_book_property_showing', 'handle_book_property_showing');
add_action('wp_ajax_nopriv_book_property_showing', 'handle_book_property_showing');

/**
 * Handle contact agent requests
 */
function handle_contact_agent() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'happy_place_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    // Sanitize inputs
    $listing_id = intval($_POST['listing_id']);
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $subject = sanitize_text_field($_POST['subject']);
    $message = sanitize_textarea_field($_POST['message']);

    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        return;
    }

    if (!is_email($email)) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        return;
    }

    // Get listing information
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'listing') {
        wp_send_json_error(array('message' => 'Invalid listing.'));
        return;
    }

    // Get agent email
    $agent_email = get_field('agent_email', $listing_id);
    if (!$agent_email) {
        $agent_email = get_option('admin_email');
    }

    // Prepare email content
    $email_subject = sprintf('Contact Request: %s - %s', $subject, get_the_title($listing_id));
    $listing_url = get_permalink($listing_id);
    $listing_address = get_field('address', $listing_id);

    $email_content = sprintf(
        "New contact request received:\n\n" .
        "Property: %s\n" .
        "Address: %s\n" .
        "Listing URL: %s\n\n" .
        "Contact Information:\n" .
        "Name: %s\n" .
        "Email: %s\n" .
        "Phone: %s\n\n" .
        "Subject: %s\n\n" .
        "Message:\n%s",
        get_the_title($listing_id),
        $listing_address ?: 'Address not specified',
        $listing_url,
        $name,
        $email,
        $phone ?: 'Not provided',
        $subject,
        $message
    );

    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $email
    );

    // Send email
    $email_sent = wp_mail($agent_email, $email_subject, $email_content, $headers);

    if ($email_sent) {
        // Log the contact request (optional)
        do_action('hph_contact_request_submitted', array(
            'listing_id' => $listing_id,
            'requester_name' => $name,
            'requester_email' => $email,
            'subject' => $subject
        ));

        wp_send_json_success(array(
            'message' => 'Your message has been sent successfully!'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to send message. Please try again.'));
    }
}
add_action('wp_ajax_contact_agent', 'handle_contact_agent');
add_action('wp_ajax_nopriv_contact_agent', 'handle_contact_agent');

/**
 * Handle save/unsave listing requests
 */
function handle_save_listing() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to save listings.'));
        return;
    }

    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'happy_place_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    $listing_id = intval($_POST['listing_id']);
    $user_id = get_current_user_id();

    // Validate listing
    $listing = get_post($listing_id);
    if (!$listing || $listing->post_type !== 'listing') {
        wp_send_json_error(array('message' => 'Invalid listing.'));
        return;
    }

    // Get current saved listings
    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    if (!is_array($saved_listings)) {
        $saved_listings = array();
    }

    // Check if already saved
    if (in_array($listing_id, $saved_listings)) {
        wp_send_json_error(array('message' => 'Listing is already saved.'));
        return;
    }

    // Add to saved listings
    $saved_listings[] = $listing_id;
    $updated = update_user_meta($user_id, 'saved_listings', $saved_listings);

    if ($updated !== false) {
        // Log the save action (optional)
        do_action('hph_listing_saved', array(
            'listing_id' => $listing_id,
            'user_id' => $user_id
        ));

        wp_send_json_success(array(
            'message' => 'Listing saved to your favorites!'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to save listing.'));
    }
}
add_action('wp_ajax_save_listing', 'handle_save_listing');

/**
 * Handle unsave listing requests
 */
function handle_unsave_listing() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Please log in to manage saved listings.'));
        return;
    }

    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'happy_place_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
        return;
    }

    $listing_id = intval($_POST['listing_id']);
    $user_id = get_current_user_id();

    // Get current saved listings
    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    if (!is_array($saved_listings)) {
        $saved_listings = array();
    }

    // Remove from saved listings
    $saved_listings = array_filter($saved_listings, function($id) use ($listing_id) {
        return $id != $listing_id;
    });

    $updated = update_user_meta($user_id, 'saved_listings', array_values($saved_listings));

    if ($updated !== false) {
        // Log the unsave action (optional)
        do_action('hph_listing_unsaved', array(
            'listing_id' => $listing_id,
            'user_id' => $user_id
        ));

        wp_send_json_success(array(
            'message' => 'Listing removed from favorites.'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to remove listing.'));
    }
}
add_action('wp_ajax_unsave_listing', 'handle_unsave_listing');

// =============================================================================
// UTILITY FUNCTIONS
// =============================================================================

/**
 * Helper function to check if listing is saved by current user
 */
function is_listing_saved($listing_id, $user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }

    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    if (!is_array($saved_listings)) {
        return false;
    }

    return in_array($listing_id, $saved_listings);
}

/**
 * Get saved listings count for user
 */
function get_user_saved_listings_count($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return 0;
    }

    $saved_listings = get_user_meta($user_id, 'saved_listings', true);
    return is_array($saved_listings) ? count($saved_listings) : 0;
}

// =============================================================================
// ADVANCED FORM INTEGRATION
// =============================================================================

/**
 * The Advanced Form AJAX handlers are now in the Happy Place Plugin
 * /wp-content/plugins/Happy Place Plugin/includes/ajax/class-advanced-form-ajax.php
 */

/**
 * Enqueue scripts for Advanced Multistep Form
 */
function happy_place_enqueue_multistep_form_scripts() {
    // Only load on dashboard pages
    if (!is_page_template() && !is_admin()) {
        return;
    }
    
    // Check if we're on a dashboard page with the form
    global $wp_query;
    if (isset($_GET['action']) && in_array($_GET['action'], ['new', 'edit'])) {
        
        // Enqueue React and ReactDOM from CDN for development
        wp_enqueue_script(
            'react',
            'https://unpkg.com/react@18/umd/react.production.min.js',
            array(),
            '18.0.0',
            true
        );
        
        wp_enqueue_script(
            'react-dom',
            'https://unpkg.com/react-dom@18/umd/react-dom.production.min.js',
            array('react'),
            '18.0.0',
            true
        );
        
        // Enqueue our React components (will need to be compiled from JSX)
        wp_enqueue_script(
            'hph-multistep-form',
            get_template_directory_uri() . '/assets/js/multistep-listing-form.js',
            array('react', 'react-dom', 'wp-util'),
            HPH_THEME_VERSION,
            true
        );
        
        wp_enqueue_script(
            'hph-media-upload-handler',
            get_template_directory_uri() . '/assets/js/media-upload-handler.js',
            array('react', 'react-dom', 'wp-util', 'media-upload'),
            HPH_THEME_VERSION,
            true
        );
        
        // Localize script with WordPress data
        wp_localize_script('hph-multistep-form', 'hphAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_advanced_form_nonce'),
            'userId' => get_current_user_id(),
            'googleMapsApiKey' => get_option('hph_google_maps_api_key', ''),
            'mediaUploadUrl' => admin_url('async-upload.php'),
            'mediaLibraryUrl' => admin_url('media-upload.php'),
        ));
        
        // Load Google Places API if key is available
        $google_api_key = get_option('hph_google_maps_api_key');
        if (!empty($google_api_key)) {
            wp_enqueue_script(
                'google-places-api',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($google_api_key) . '&libraries=places',
                array(),
                null,
                true
            );
        }
    }
}
add_action('wp_enqueue_scripts', 'happy_place_enqueue_multistep_form_scripts');

?>