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
