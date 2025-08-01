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

// Load theme manager (single point of initialization)
require_once HPH_THEME_PATH . '/inc/core/class-theme-manager.php';

// Initialize theme
add_action('after_setup_theme', function() {
    if (class_exists('HappyPlace\Core\Theme_Manager')) {
        HappyPlace\Core\Theme_Manager::get_instance();
    }
});

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
