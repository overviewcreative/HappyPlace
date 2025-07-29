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
if (!defined('HPH_ADMIN_URL')) {
    define('HPH_ADMIN_URL', HPH_URL . 'admin/');
}
if (!defined('HPH_ASSETS_URL')) {
    define('HPH_ASSETS_URL', HPH_URL . 'assets/');
}

// Plugin Paths
if (!defined('HPH_PATH')) {
    define('HPH_PATH', plugin_dir_path(__FILE__));
}
if (!defined('HPH_ADMIN_PATH')) {
    define('HPH_ADMIN_PATH', HPH_PATH . 'admin/');
}
if (!defined('HPH_INCLUDES_PATH')) {
    define('HPH_INCLUDES_PATH', HPH_PATH . 'includes/');
}
if (!defined('HPH_ASSETS_PATH')) {
    define('HPH_ASSETS_PATH', HPH_PATH . 'assets/');
}

// Plugin Directory (alias for compatibility)
if (!defined('HPH_PLUGIN_DIR')) {
    define('HPH_PLUGIN_DIR', HPH_PATH);
}

// Additional constants that some classes might need
if (!defined('HPH_PLUGIN_URL')) {
    define('HPH_PLUGIN_URL', HPH_URL);
}

// =============================================================================
// AUTOLOADER FOR LEGACY FUNCTIONS
// =============================================================================

// Load essential functions that don't require classes
require_once HPH_INCLUDES_PATH . 'dashboard-functions.php';
require_once HPH_INCLUDES_PATH . 'template-functions.php';

// Load shortcodes
require_once HPH_INCLUDES_PATH . 'shortcodes.php';

// Load Listing Calulator
require_once HPH_INCLUDES_PATH . 'fields/class-listing-calculator.php';

// Load Enhanced Field Manager (Phase 1)
require_once HPH_INCLUDES_PATH . 'fields/class-enhanced-field-manager.php';

// Load Validation AJAX Handlers (Phase 4+)
require_once HPH_INCLUDES_PATH . 'class-validation-ajax.php';

// Bridge functions are managed by the theme for modularity

// Load Phase 1 Status Page (Development tool)
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once HPH_INCLUDES_PATH . 'fields/phase1-status-page.php';
}

// =============================================================================
// PLUGIN MANAGER INITIALIZATION
// =============================================================================

// Load the Plugin Manager
require_once HPH_INCLUDES_PATH . 'core/class-plugin-manager.php';

// Initialize the plugin via Plugin Manager
add_action('plugins_loaded', function() {
    \HappyPlace\Core\Plugin_Manager::get_instance();
}, 5);

// Load Enhanced Airtable Sync System
require_once HPH_INCLUDES_PATH . 'integrations/init-enhanced-sync.php';

// =============================================================================
// LEGACY SUPPORT FUNCTIONS (for backward compatibility)
// =============================================================================

/**
 * Legacy compatibility functions that some parts might still rely on
 */

// Simple function for ACF field groups CSS
add_action('admin_enqueue_scripts', 'hph_enqueue_admin_assets');
function hph_enqueue_admin_assets($hook) {
    // Only load on post edit screens for listings
    if (!in_array($hook, ['post.php', 'post-new.php'])) {
        return;
    }
    
    global $post;
    if (!$post || $post->post_type !== 'listing') {
        return;
    }
    
    // Enqueue ACF field groups CSS
    wp_enqueue_style(
        'hph-acf-field-groups',
        HPH_PLUGIN_URL . 'assets/css/acf-field-groups.css',
        [],
        HPH_VERSION
    );
}

// Dashboard compatibility function
function hph_is_dashboard() {
    if (!is_page()) {
        return false;
    }
    
    $page_template = get_post_meta(get_the_ID(), '_wp_page_template', true);
    return in_array($page_template, [
        'agent-dashboard.php',
        'agent-dashboard-rebuilt.php',
        'page-templates/agent-dashboard-rebuilt.php'
    ]);
}

// Template loading for custom post types and dashboard
add_filter('template_include', 'hph_template_include', 99);
function hph_template_include($template) {
    $post_type = get_post_type();

    // Handle single post templates
    if (is_singular() && in_array($post_type, ['listing', 'agent', 'open_house'])) {
        $custom_template = hph_locate_template("single-{$post_type}.php", [
            "templates/{$post_type}/",
            "templates/",
        ]);
        
        if ($custom_template) {
            return $custom_template;
        }
    }

    // Handle archive templates
    if (is_post_type_archive() && in_array($post_type, ['listing', 'agent', 'open_house'])) {
        $custom_template = hph_locate_template("archive-{$post_type}.php", [
            "templates/{$post_type}/",
            "templates/",
        ]);
        
        if ($custom_template) {
            return $custom_template;
        }
    }

    // Handle dashboard template
    if (is_page()) {
        $page_template = get_post_meta(get_the_ID(), '_wp_page_template', true);
        
        if ($page_template === 'agent-dashboard.php') {
            $custom_template = hph_locate_template('agent-dashboard.php', [
                'templates/dashboard/',
                'templates/',
                '',
            ]);
            
            if ($custom_template) {
                return $custom_template;
            }
        }
    }

    return $template;
}

// Locate template in theme or plugin
function hph_locate_template($template_name, $subdirs = ['']) {
    // Check theme first
    foreach ($subdirs as $subdir) {
        $theme_template = get_template_directory() . '/' . $subdir . $template_name;
        if (file_exists($theme_template)) {
            return $theme_template;
        }
    }

    // Check plugin
    foreach ($subdirs as $subdir) {
        $plugin_template = HPH_PATH . 'templates/' . $subdir . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return null;
}

// ACF Integration
add_action('init', 'hph_acf_integration', 15);
function hph_acf_integration() {
    if (!class_exists('ACF')) {
        return;
    }

    // Make post types available in ACF
    add_filter('acf/get_post_types', function ($post_types) {
        $custom_types = ['listing', 'open_house', 'agent'];
        
        foreach ($custom_types as $type) {
            $type_obj = get_post_type_object($type);
            if ($type_obj) {
                $post_types[$type] = $type_obj->labels->singular_name;
            }
        }
        
        return $post_types;
    }, 10);

    // Add post types to ACF location rules
    add_filter('acf/location/rule_values/post_type', function ($choices) {
        $custom_types = ['listing', 'open_house', 'agent'];
        
        foreach ($custom_types as $type) {
            $type_obj = get_post_type_object($type);
            if ($type_obj) {
                $choices[$type] = $type_obj->labels->singular_name;
            }
        }
        
        return $choices;
    }, 10);

    // Add taxonomies to ACF location rules
    add_filter('acf/location/rule_values/taxonomy', function ($choices) {
        $taxonomies = ['property_type', 'listing_location', 'listing_status'];
        
        foreach ($taxonomies as $tax_name) {
            $tax = get_taxonomy($tax_name);
            if ($tax) {
                $choices[$tax_name] = $tax->labels->singular_name;
            }
        }
        
        return $choices;
    }, 10);
}

// Setup dashboard hooks for rewrite rules
add_action('init', function () {
    add_rewrite_rule(
        '^agent-dashboard/?$',
        'index.php?pagename=agent-dashboard',
        'top'
    );
    add_rewrite_rule(
        '^agent-dashboard/([^/]+)/?$',
        'index.php?pagename=agent-dashboard&section=$matches[1]',
        'top'
    );
});

// Add query vars
add_filter('query_vars', function ($vars) {
    $vars[] = 'section';
    $vars[] = 'action';
    return $vars;
});

// Dashboard-specific body classes
add_filter('body_class', function ($classes) {
    if (is_page() && get_post_meta(get_the_ID(), '_wp_page_template', true) === 'agent-dashboard.php') {
        $classes[] = 'hph-dashboard-page';
        $classes[] = 'page-template-agent-dashboard';
    }
    return $classes;
});

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
    $schedules['every_twelve_hours'] = [
        'interval' => 12 * HOUR_IN_SECONDS,
        'display'  => __('Every 12 Hours', 'happy-place')
    ];
    return $schedules;
});

// Hook for periodic Airtable sync
add_action('hph_airtable_periodic_sync', 'hph_perform_airtable_sync');
function hph_perform_airtable_sync() {
    try {
        $options = get_option('happy_place_integrations', []);
        $airtable_settings = $options['airtable'] ?? [];

        if (empty($airtable_settings['enabled']) || 
            empty($airtable_settings['auto_sync']) || 
            empty($airtable_settings['api_key']) || 
            empty($airtable_settings['base_id'])) {
            error_log('HPH: Airtable sync skipped - not configured or disabled');
            return;
        }

        // Load the sync class
        require_once plugin_dir_path(__FILE__) . 'includes/integrations/class-airtable-two-way-sync.php';
        
        $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync(
            $airtable_settings['base_id'],
            $airtable_settings['table_name'] ?? 'Listings'
        );

        // Perform two-way sync
        $airtable_to_wp = $sync->sync_airtable_to_wordpress();
        $wp_to_airtable = $sync->sync_wordpress_to_airtable();

        error_log('HPH: Periodic Airtable sync completed - ' . json_encode([
            'airtable_to_wp' => $airtable_to_wp,
            'wp_to_airtable' => $wp_to_airtable
        ]));

    } catch (\Exception $e) {
        error_log('HPH: Periodic Airtable sync error: ' . $e->getMessage());
    }
}

error_log('HPH: Streamlined plugin file loaded successfully with Plugin Manager');
