<?php
/**
 * Happy Place Theme - Streamlined Functions
 */

if (!defined('ABSPATH')) { exit; }

// Theme constants
define('HPH_THEME_VERSION', wp_get_theme()->get('Version'));
define('HPH_THEME_DIR', get_template_directory());
define('HPH_THEME_URI', get_template_directory_uri());

// Load core managers
require_once HPH_THEME_DIR . '/inc/core/class-theme-manager.php';
require_once HPH_THEME_DIR . '/inc/core/class-asset-loader.php';
require_once HPH_THEME_DIR . '/inc/core/class-template-engine.php';
require_once HPH_THEME_DIR . '/inc/core/class-component-manager.php';

// Load bridge modules
require_once HPH_THEME_DIR . '/inc/bridge/interface-data-contract.php';
require_once HPH_THEME_DIR . '/inc/bridge/class-fallback-data-provider.php';
require_once HPH_THEME_DIR . '/inc/bridge/data-provider-registry.php';
require_once HPH_THEME_DIR . '/inc/bridge/cache-bridge.php';
require_once HPH_THEME_DIR . '/inc/bridge/listing-bridge.php';
require_once HPH_THEME_DIR . '/inc/bridge/agent-bridge.php';
require_once HPH_THEME_DIR . '/inc/bridge/financial-bridge.php';
require_once HPH_THEME_DIR . '/inc/bridge/fallback-bridge.php';

// Load utilities
require_once HPH_THEME_DIR . '/inc/utilities/formatting-functions.php';
require_once HPH_THEME_DIR . '/inc/utilities/helper-functions.php';
require_once HPH_THEME_DIR . '/inc/utilities/image-functions.php';
require_once HPH_THEME_DIR . '/inc/utilities/Geocoding.php';

// Initialize managers
add_action('after_setup_theme', fn() => HappyPlace\Core\Theme_Manager::get_instance());
add_action('init', fn() => HappyPlace\Core\Asset_Loader::init());
add_action('init', fn() => HappyPlace\Core\Template_Engine::instance());
add_action('init', fn() => HappyPlace\Core\Component_Manager::init());
add_action('init', function() {
    if (class_exists('HPH_Shortcode_Manager')) {
        HPH_Shortcode_Manager::get_instance();
    }
});

// All other features handled by managers
