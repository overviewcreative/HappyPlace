<?php
/**
 * Plugin Name: Happy Place
 * Plugin URI: https://theparkergroup.com
 * Description: Advanced real estate features and MLS compliance for The Parker Group
 * Version: 1.0.0
 * Author: The Parker Group
 * Author URI: https://theparkergroup.com
 * License: GPL v2 or later
 * Text Domain: happy-place
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// DEFINE CONSTANTS
// =============================================================================

// Plugin File Path
if (!defined('HPH_PLUGIN_FILE')) {
    define('HPH_PLUGIN_FILE', __FILE__);
}

// Plugin Version
if (!defined('HPH_VERSION')) {
    define('HPH_VERSION', '1.0.0');
}

// Plugin URLs
if (!defined('HPH_URL')) {
    define('HPH_URL', plugin_dir_url(__FILE__));
}
if (!defined('HPH_ASSETS_URL')) {
    define('HPH_ASSETS_URL', HPH_URL . 'assets/');
}

// Plugin Paths
if (!defined('HPH_PATH')) {
    define('HPH_PATH', plugin_dir_path(__FILE__));
}
if (!defined('HPH_INCLUDES_PATH')) {
    define('HPH_INCLUDES_PATH', HPH_PATH . 'includes/');
}
if (!defined('HPH_ASSETS_PATH')) {
    define('HPH_ASSETS_PATH', HPH_PATH . 'assets/');
}

// Compatibility aliases
if (!defined('HPH_PLUGIN_DIR')) {
    define('HPH_PLUGIN_DIR', HPH_PATH);
}
if (!defined('HPH_PLUGIN_URL')) {
    define('HPH_PLUGIN_URL', HPH_URL);
}

// =============================================================================
// CORE INITIALIZATION
// =============================================================================

// Ensure error logging for debugging
if (!function_exists('hph_log_error')) {
    function hph_log_error($message, $context = '') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HPH Plugin: ' . $context . ' - ' . $message);
        }
    }
}

// Load Plugin Manager and Initialize
$plugin_manager_file = HPH_INCLUDES_PATH . 'core/class-plugin-manager-clean.php';
if (file_exists($plugin_manager_file)) {
    require_once $plugin_manager_file;
    
    // Initialize the clean plugin manager
    try {
        if (class_exists('HappyPlace\Core\Plugin_Manager_Clean')) {
            $plugin_instance = \HappyPlace\Core\Plugin_Manager_Clean::get_instance();
            if (defined('WP_DEBUG') && WP_DEBUG) {
                hph_log_error('Clean Plugin Manager initialized successfully', 'INIT');
            }
        } else {
            hph_log_error('Plugin_Manager_Clean class not found', 'ERROR');
        }
    } catch (Exception $e) {
        hph_log_error('Failed to initialize Plugin_Manager_Clean: ' . $e->getMessage(), 'FATAL');
    }
} else {
    // Fallback to original plugin manager
    $fallback_file = HPH_INCLUDES_PATH . 'core/class-plugin-manager.php';
    if (file_exists($fallback_file)) {
        require_once $fallback_file;
        try {
            $plugin_instance = \HappyPlace\Core\Plugin_Manager::get_instance();
        } catch (Exception $e) {
            hph_log_error('Fallback plugin manager failed: ' . $e->getMessage(), 'FATAL');
        }
    } else {
        hph_log_error('No plugin manager found', 'FATAL');
    }
}

// Load debug file in development
if (defined('WP_DEBUG') && WP_DEBUG) {
}

// =============================================================================
// ESSENTIAL LEGACY COMPATIBILITY
// =============================================================================

// Only include minimal legacy functions that external code might depend on
require_once HPH_INCLUDES_PATH . 'dashboard-functions.php';
require_once HPH_INCLUDES_PATH . 'shortcodes.php';

// Custom cron schedules for integrations
add_filter('cron_schedules', function($schedules) {
    $schedules['every_three_hours'] = [
        'interval' => 3 * HOUR_IN_SECONDS,
        'display'  => __('Every 3 Hours', 'happy-place')
    ];
    $schedules['every_six_hours'] = [
        'interval' => 6 * HOUR_IN_SECONDS,
        'display'  => __('Every 6 Hours', 'happy-place')
    ];
    return $schedules;
});

error_log('HPH: Streamlined plugin initialization complete');
