<?php
/**
 * Happy Place Theme Functions
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define theme constants
define('HPH_THEME_VERSION', '2.0.0');
define('HPH_THEME_PATH', get_template_directory());
define('HPH_THEME_URL', get_template_directory_uri());

// Error logging helper for theme
if (!function_exists('hph_theme_log_error')) {
    function hph_theme_log_error($message, $context = '') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HPH Theme: ' . $context . ' - ' . $message);
        }
    }
}

// Load theme manager (single point of initialization)
$theme_manager_file = HPH_THEME_PATH . '/inc/core/class-theme-manager.php';
if (file_exists($theme_manager_file)) {
    require_once $theme_manager_file;
    hph_theme_log_error('Theme Manager file loaded', 'INIT');
} else {
    hph_theme_log_error('Theme Manager file not found: ' . $theme_manager_file, 'FATAL');
}

// Initialize theme with proper error handling
add_action('after_setup_theme', function() {
    try {
        if (class_exists('HappyPlace\Core\Theme_Manager')) {
            $theme_instance = HappyPlace\Core\Theme_Manager::get_instance();
            hph_theme_log_error('Theme Manager instance created successfully', 'INIT');
        } else {
            hph_theme_log_error('Theme_Manager class not found after require', 'ERROR');
        }
    } catch (Exception $e) {
        hph_theme_log_error('Failed to initialize Theme_Manager: ' . $e->getMessage(), 'FATAL');
    }
});

// Fallback loading if Theme_Manager fails
add_action('init', function() {
    if (!class_exists('HappyPlace\Core\Theme_Manager')) {
        hph_theme_log_error('Theme_Manager not initialized, loading fallback bridge functions', 'FALLBACK');
        
        // Load essential bridge functions directly
        $bridge_files = [
            HPH_THEME_PATH . '/inc/bridge/template-helpers.php',
            HPH_THEME_PATH . '/inc/bridge/listing-bridge.php',
            HPH_THEME_PATH . '/inc/bridge/archive-bridge.php'
        ];
        
        foreach ($bridge_files as $bridge_file) {
            if (file_exists($bridge_file)) {
                require_once $bridge_file;
                hph_theme_log_error('Loaded bridge file: ' . basename($bridge_file), 'FALLBACK');
            }
        }
    }
}, 5);

// Emergency fallback for critical functions when plugin is inactive
if (!function_exists('hph_bridge_get_listing_data')) {
    function hph_bridge_get_listing_data($listing_id) {
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => 0,
            'status' => 'available'
        ];
    }
}

if (!function_exists('hph_fallback_get_listing_data')) {
    function hph_fallback_get_listing_data($listing_id) {
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => 0,
            'status' => 'available',
            'bedrooms' => 0,
            'bathrooms' => 0,
            'square_footage' => 0,
            'address' => '',
            'description' => get_the_content(null, false, $listing_id),
            'images' => [],
            'agent_id' => 0,
            'listing_date' => get_the_date('Y-m-d', $listing_id),
            'features' => []
        ];
    }
}

if (!function_exists('hph_fallback_get_hero_data')) {
    function hph_fallback_get_hero_data($listing_id) {
        return [
            'title' => get_the_title($listing_id),
            'price' => '0',
            'status' => 'Available',
            'images' => [],
            'address' => ''
        ];
    }
}

if (!function_exists('hph_fallback_get_gallery_data')) {
    function hph_fallback_get_gallery_data($listing_id) {
        return [];
    }
}

if (!function_exists('hph_fallback_get_property_details')) {
    function hph_fallback_get_property_details($listing_id) {
        return [];
    }
}

if (!function_exists('hph_fallback_get_features')) {
    function hph_fallback_get_features($listing_id) {
        return [];
    }
}

if (!function_exists('hph_fallback_get_agent_data')) {
    function hph_fallback_get_agent_data($listing_id) {
        return [];
    }
}

if (!function_exists('hph_fallback_get_financial_data')) {
    function hph_fallback_get_financial_data($listing_id) {
        return [];
    }
}

// Bridge functions are now handled in dedicated bridge files
// Fallback functions remain for emergency situations when bridge files fail to load

// Admin tools accessibility functions
if (!function_exists('hph_is_admin_tools_accessible')) {
    function hph_is_admin_tools_accessible() {
        return current_user_can('manage_options') || current_user_can('edit_posts');
    }
}

if (!function_exists('hph_is_agent_tools_accessible')) {
    function hph_is_agent_tools_accessible() {
        return current_user_can('edit_posts') || in_array('real_estate_agent', wp_get_current_user()->roles);
    }
}

// Component class existence checker
if (!function_exists('hph_component_exists')) {
    function hph_component_exists($component_name) {
        $component_classes = [
            'hero' => 'HPH_Hero_Component',
            'gallery' => 'HPH_Property_Gallery_Component',
            'details' => 'HPH_Property_Details_Component',
            'features' => 'HPH_Property_Features_Component',
            'contact_form' => 'HPH_Contact_Form_Component',
            'calculator' => 'HPH_Financial_Calculator_Component',
            'agent_card' => 'HPH_Agent_Card_Component',
            'related_listings' => 'HPH_Related_Listings_Component',
            'agent_stats' => 'HPH_Agent_Stats_Component',
            'agent_listings' => 'HPH_Agent_Listings_Component'
        ];
        
        return isset($component_classes[$component_name]) && class_exists($component_classes[$component_name]);
    }
}

// System status checker removed - diagnostics directory cleaned up

if (!function_exists('hph_fallback_get_similar_listings')) {
    function hph_fallback_get_similar_listings($listing_id, $count = 3) {
        return [];
    }
}

if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part($slug, $name = '', $args = []) {
        // Fallback template part function
        $template = '';
        if ($name) {
            $template = get_template_directory() . "/template-parts/{$slug}-{$name}.php";
        }
        if (!$template || !file_exists($template)) {
            $template = get_template_directory() . "/template-parts/{$slug}.php";
        }
        if (file_exists($template)) {
            include $template;
        }
    }
}

if (!function_exists('hph_bridge_get_agent_profile')) {
    function hph_bridge_get_agent_profile($agent_id) {
        return [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'email' => '',
            'phone' => ''
        ];
    }
}

// Asset management emergency fallback
if (!function_exists('hph_enqueue_template_assets')) {
    function hph_enqueue_template_assets($template_name) {
        // Fallback - load main styles at minimum
        wp_enqueue_style('hph-main', HPH_THEME_URL . '/assets/dist/css/main.css', [], HPH_THEME_VERSION);
        wp_enqueue_script('hph-main', HPH_THEME_URL . '/assets/dist/js/main.js', ['jquery'], HPH_THEME_VERSION, true);
    }
}
