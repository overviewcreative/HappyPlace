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

/**
 * Theme Setup
 */
function happy_place_theme_setup() {
    // Add theme support
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('custom-logo');
    add_theme_support('customize-selective-refresh-widgets');
    
    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'happy-place'),
        'footer' => __('Footer Menu', 'happy-place'),
    ));
    
    // Add custom image sizes
    add_image_size('listing-thumbnail', 400, 300, true);
    add_image_size('listing-large', 1200, 800, true);
    add_image_size('agent-avatar', 150, 150, true);
}
add_action('after_setup_theme', 'happy_place_theme_setup');

/**
 * MAIN ASSET ENQUEUING FUNCTION
 * This is the only asset function we need
 */
function happy_place_enqueue_assets() {
    $theme_version = wp_get_theme()->get('Version');
    $theme_uri = get_template_directory_uri();
    $theme_dir = get_template_directory();
    
    // Get manifest for cache busting
    $manifest_path = $theme_dir . '/assets/dist/manifest.json';
    $manifest = array();
    
    if (file_exists($manifest_path)) {
        $manifest = json_decode(file_get_contents($manifest_path), true);
    }
    
    // Helper function
    function get_asset_path($asset_name, $manifest, $fallback_path) {
        return isset($manifest[$asset_name]) ? $manifest[$asset_name] : $fallback_path;
    }
    
    // Main CSS (compiled from SCSS)
    $main_css_path = get_asset_path('main.css', $manifest, 'css/main.css');
    $main_css_file = $theme_dir . '/assets/dist/' . $main_css_path;
    
    if (file_exists($main_css_file)) {
        wp_enqueue_style(
            'happy-place-main',
            $theme_uri . '/assets/dist/' . $main_css_path,
            array(),
            $theme_version
        );
    } else {
        // Fallback: add basic styles if CSS file is missing
        wp_enqueue_style('happy-place-fallback', false);
        wp_add_inline_style('happy-place-fallback', '
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
            .listing-single { background: #f8f9fa; min-height: 100vh; }
            .listing-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 3rem 0; }
            .listing-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 1rem; }
            .listing-content { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-bottom: 2rem; }
            .main-content { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
            .sidebar-widget { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            @media (max-width: 768px) { .listing-content { grid-template-columns: 1fr; } }
        ');
    }
    
    // Main JavaScript
    $main_js_path = get_asset_path('main.js', $manifest, 'js/main.js');
    $main_js_file = $theme_dir . '/assets/dist/' . $main_js_path;
    
    if (file_exists($main_js_file)) {
        wp_enqueue_script(
            'happy-place-main',
            $theme_uri . '/assets/dist/' . $main_js_path,
            array('jquery'),
            $theme_version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('happy-place-main', 'happyPlaceAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('happy_place_nonce'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'happy_place_enqueue_assets');

/**
 * Debug Asset Loading
 * Add ?debug_assets=1 to any URL to see debug info
 */
function debug_happy_place_assets() {
    if (current_user_can('administrator') && isset($_GET['debug_assets'])) {
        $theme_dir = get_template_directory();
        $theme_uri = get_template_directory_uri();
        
        echo '<div style="position: fixed; top: 20px; right: 20px; background: #000; color: #fff; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 12px; z-index: 9999; max-width: 400px; max-height: 80vh; overflow: auto;">';
        echo '<h3>🔧 Asset Debug Info</h3>';
        echo '<strong>Theme Directory:</strong> ' . $theme_dir . '<br>';
        echo '<strong>Theme URI:</strong> ' . $theme_uri . '<br><br>';
        
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
            
            echo '<strong>' . $name . ':</strong> ';
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
                    echo $key . ' → ' . $value . '<br>';
                }
            } else {
                echo 'Invalid JSON<br>';
            }
        }
        
        // Check if CSS is being enqueued
        global $wp_styles;
        echo '<br><strong>Enqueued Styles:</strong><br>';
        foreach ($wp_styles->queue as $handle) {
            if (strpos($handle, 'happy-place') !== false) {
                echo $handle . '<br>';
            }
        }
        
        echo '</div>';
    }
}
add_action('wp_head', 'debug_happy_place_assets');

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
 * Build check - verify our SCSS system is working
 */
function check_scss_build() {
    if (current_user_can('administrator') && isset($_GET['check_build'])) {
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
            echo "<div>$check: $status</div>";
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
}
add_action('wp_head', 'check_scss_build');

// Load legacy compatibility if needed
if (file_exists(get_template_directory() . '/inc/template-bridge.php')) {
    require_once get_template_directory() . '/inc/template-bridge.php';
}
if (file_exists(get_template_directory() . '/inc/template-functions.php')) {
    require_once get_template_directory() . '/inc/template-functions.php';
}
?>