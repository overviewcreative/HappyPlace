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

// Enqueue admin styles and scripts
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

// =============================================================================
// SIMPLE INITIALIZATION WITHOUT NAMESPACE
// =============================================================================

// Load core functions first
require_once HPH_INCLUDES_PATH . 'dashboard-functions.php';
require_once HPH_INCLUDES_PATH . 'template-functions.php';

// Load bridge functions for template integration (BEFORE AJAX handlers)
require_once HPH_INCLUDES_PATH . 'constants/class-listing-fields.php';

// Load shortcodes
require_once HPH_INCLUDES_PATH . 'shortcodes.php';

// Load AJAX handlers (AFTER bridge functions)
require_once HPH_INCLUDES_PATH . 'api/class-ajax-handler.php';

// Load Advanced Form AJAX Handler
if (file_exists(HPH_INCLUDES_PATH . 'ajax/class-advanced-form-ajax.php')) {
    require_once HPH_INCLUDES_PATH . 'ajax/class-advanced-form-ajax.php';
    error_log('HPH: Advanced Form AJAX Handler loaded');
}

// Load Flyer Generator AJAX Handler
if (file_exists(HPH_INCLUDES_PATH . 'ajax/flyer-generator.php')) {
    require_once HPH_INCLUDES_PATH . 'ajax/flyer-generator.php';
    error_log('HPH: Flyer Generator AJAX Handler loaded');
}

// Load Flyer Generator Class
if (file_exists(HPH_INCLUDES_PATH . 'graphics/class-flyer-generator.php')) {
    require_once HPH_INCLUDES_PATH . 'graphics/class-flyer-generator.php';
    error_log('HPH: Flyer Generator Class loaded');
}

// Load utility classes that might be needed
if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-data-validator.php')) {
    require_once HPH_INCLUDES_PATH . 'utilities/class-data-validator.php';
    error_log('HPH: Data Validator utility loaded');
}

// Load image processor
if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-image-processor.php')) {
    require_once HPH_INCLUDES_PATH . 'utilities/class-image-processor.php';
    error_log('HPH: Image Processor utility loaded');
}

// Initialize early (post types and taxonomies)
add_action('init', 'hph_init_early', 5);

// Main initialization
add_action('plugins_loaded', 'hph_init_main', 10);

// Dashboard initialization
add_action('wp_loaded', 'hph_init_dashboard', 10);

// Activation hook
register_activation_hook(__FILE__, 'hph_activate');

// Load translations
add_action('init', 'hph_load_textdomain', 0);

/**
 * Early initialization
 */
function hph_init_early() {
    try {
        // Load Post Types
        require_once HPH_INCLUDES_PATH . 'core/class-post-types.php';
        if (class_exists('HappyPlace\\Core\\Post_Types')) {
            HappyPlace\Core\Post_Types::initialize();
            error_log('HPH: Post Types initialized');
        }

        // Load Taxonomies
        require_once HPH_INCLUDES_PATH . 'core/class-taxonomies.php';
        if (class_exists('HappyPlace\\Core\\Taxonomies')) {
            HappyPlace\Core\Taxonomies::get_instance();
            error_log('HPH: Taxonomies initialized');
        }

        // Load User Roles
        require_once HPH_INCLUDES_PATH . 'users/class-user-roles-manager.php';
        if (class_exists('HappyPlace\\Users\\User_Roles_Manager')) {
            HappyPlace\Users\User_Roles_Manager::get_instance();
            error_log('HPH: User Roles initialized');
        }

    } catch (Exception $e) {
        error_log('HPH: Error in early init: ' . $e->getMessage());
    }
}

/**
 * Main initialization
 */
function hph_init_main() {
    try {
        // Load User Dashboard Manager
        if (file_exists(HPH_INCLUDES_PATH . 'users/class-user-dashboard-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'users/class-user-dashboard-manager.php';
            if (class_exists('HappyPlace\\Users\\User_Dashboard_Manager')) {
                if (method_exists('HappyPlace\\Users\\User_Dashboard_Manager', 'get_instance')) {
                    HappyPlace\Users\User_Dashboard_Manager::get_instance();
                    error_log('HPH: User Dashboard Manager initialized via get_instance');
                } elseif (method_exists('HappyPlace\\Users\\User_Dashboard_Manager', 'instance')) {
                    HappyPlace\Users\User_Dashboard_Manager::instance();
                    error_log('HPH: User Dashboard Manager initialized via instance');
                } else {
                    error_log('HPH: User Dashboard Manager class found but no instance method');
                }
            } else {
                error_log('HPH: User Dashboard Manager class not found');
            }
        } else {
            error_log('HPH: User Dashboard Manager file not found');
        }

        // Load Assets Manager (skip if problematic)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-assets-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-assets-manager.php';
            if (class_exists('HappyPlace\\Core\\Assets_Manager')) {
                // Check if the constant issue is fixed
                if (defined('HPH_PLUGIN_URL') || defined('HPH_URL')) {
                    if (method_exists('HappyPlace\\Core\\Assets_Manager', 'get_instance')) {
                        HappyPlace\Core\Assets_Manager::get_instance();
                        error_log('HPH: Assets Manager initialized via get_instance');
                    } elseif (method_exists('HappyPlace\\Core\\Assets_Manager', 'instance')) {
                        HappyPlace\Core\Assets_Manager::instance();
                        error_log('HPH: Assets Manager initialized via instance');
                    } else {
                        error_log('HPH: Assets Manager class found but no instance method available');
                    }
                } else {
                    error_log('HPH: Assets Manager skipped - required constants not defined');
                }
            } else {
                error_log('HPH: Assets Manager class not found');
            }
        } else {
            error_log('HPH: Assets Manager file not found at: ' . HPH_INCLUDES_PATH . 'core/class-assets-manager.php');
        }

        // Initialize Image Processor
        if (class_exists('HappyPlace\\Utilities\\Image_Processor')) {
            new HappyPlace\Utilities\Image_Processor();
            error_log('HPH: Image Processor initialized');
        } else {
            error_log('HPH: Image Processor class not found');
        }

        // Load Image Optimization AJAX Handler
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-image-optimization-ajax.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-image-optimization-ajax.php';
            error_log('HPH: Image Optimization AJAX Handler loaded');
        }

        // Skip Template Loader if file doesn't exist (not critical for dashboard)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-template-loader.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-template-loader.php';
            if (class_exists('HappyPlace\\Core\\Template_Loader')) {
                if (method_exists('HappyPlace\\Core\\Template_Loader', 'instance')) {
                    HappyPlace\Core\Template_Loader::instance();
                    error_log('HPH: Template Loader initialized via instance');
                } elseif (method_exists('HappyPlace\\Core\\Template_Loader', 'get_instance')) {
                    HappyPlace\Core\Template_Loader::get_instance();
                    error_log('HPH: Template Loader initialized via get_instance');
                } else {
                    error_log('HPH: Template Loader class found but no instance method available');
                }
            } else {
                error_log('HPH: Template Loader class not found');
            }
        } else {
            error_log('HPH: Template Loader file not found (skipping - not critical for dashboard)');
        }

        error_log('HPH: Main initialization completed');

        // Load External API Auto-Population Service
        if (file_exists(HPH_INCLUDES_PATH . 'services/class-external-api-auto-population.php')) {
            require_once HPH_INCLUDES_PATH . 'services/class-external-api-auto-population.php';
            if (class_exists('HappyPlace\\Services\\External_API_Auto_Population')) {
                HappyPlace\Services\External_API_Auto_Population::get_instance();
                error_log('HPH: External API Auto-Population Service initialized');
            }
        }

        // Load City API Integration Service
        if (file_exists(HPH_INCLUDES_PATH . 'services/class-city-api-integration.php')) {
            require_once HPH_INCLUDES_PATH . 'services/class-city-api-integration.php';
            if (class_exists('HappyPlace\\Services\\City_API_Integration')) {
                HappyPlace\Services\City_API_Integration::get_instance();
                error_log('HPH: City API Integration Service initialized');
            }
        }

        // Load Admin Classes (only on admin pages)
        if (is_admin()) {
            hph_init_admin();
        }

        // Load ACF Enhancements
        hph_init_acf_enhancements();

        // Load debug tools in development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            hph_init_debug_tools();
        }

    } catch (Exception $e) {
        error_log('HPH: Error in main init: ' . $e->getMessage());
    }
}

/**
 * Initialize ACF enhancements
 */
function hph_init_acf_enhancements() {
    try {
        // Load Consolidated ACF Manager
        require_once HPH_INCLUDES_PATH . 'fields/class-acf-manager.php';
        if (class_exists('HappyPlace\\Fields\\ACF_Manager')) {
            HappyPlace\Fields\ACF_Manager::get_instance();
            error_log('HPH: Consolidated ACF Manager initialized');
        }

        error_log('HPH: ACF enhancements loaded successfully');

    } catch (Exception $e) {
        error_log('HPH: Error in ACF enhancements: ' . $e->getMessage());
    }
}

/**
 * Initialize debug tools (development only)
 */
function hph_init_debug_tools() {
    try {
        // City API test
        if (file_exists(HPH_INCLUDES_PATH . 'debug/city-api-test.php')) {
            require_once HPH_INCLUDES_PATH . 'debug/city-api-test.php';
        }
        
        error_log('HPH: Debug tools loaded');
    } catch (Exception $e) {
        error_log('HPH: Error loading debug tools: ' . $e->getMessage());
    }
}

/**
 * Admin initialization
 */
function hph_init_admin() {
    try {
        // Load Admin Menu
        require_once HPH_INCLUDES_PATH . 'admin/class-admin-menu.php';
        if (class_exists('HPH\\Admin\\Admin_Menu')) {
            HPH\Admin\Admin_Menu::get_instance();
            error_log('HPH: Admin Menu initialized');
        }

        // Load Integrations Manager
        require_once HPH_INCLUDES_PATH . 'admin/class-integrations-manager.php';
        if (class_exists('HPH\\Admin\\Integrations_Manager')) {
            HPH\Admin\Integrations_Manager::get_instance();
            error_log('HPH: Integrations Manager initialized');
        }

        // Load Settings Page
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-settings-page.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-settings-page.php';
            if (class_exists('HPH\\Admin\\Settings_Page')) {
                HPH\Admin\Settings_Page::get_instance();
                error_log('HPH: Settings Page initialized');
            }
        }

        // Load Admin Dashboard
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-admin-dashboard.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-admin-dashboard.php';
            if (class_exists('HPH\\Admin\\Admin_Dashboard')) {
                HPH\Admin\Admin_Dashboard::get_instance();
                error_log('HPH: Admin Dashboard initialized');
            }
        }

        // Load External API Settings
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-external-api-settings.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-external-api-settings.php';
            if (class_exists('HappyPlace\\Admin\\External_API_Settings')) {
                HappyPlace\Admin\External_API_Settings::get_instance();
                error_log('HPH: External API Settings initialized');
            }
        }

        // Load Location Intelligence Admin
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-location-intelligence-admin.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-location-intelligence-admin.php';
            if (class_exists('HappyPlace\\Admin\\Location_Intelligence_Admin')) {
                HappyPlace\Admin\Location_Intelligence_Admin::get_instance();
                error_log('HPH: Location Intelligence Admin initialized');
            }
        }

        // Load Auto-Population Notices
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-auto-population-notices.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-auto-population-notices.php';
            if (class_exists('HappyPlace\\Admin\\Auto_Population_Notices')) {
                HappyPlace\Admin\Auto_Population_Notices::get_instance();
                error_log('HPH: Auto-Population Notices initialized');
            }
        }

        // Load ACF Field Groups Migration
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-acf-field-groups-migration.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-acf-field-groups-migration.php';
            // Migration class auto-initializes if in admin
            error_log('HPH: ACF Field Groups Migration loaded');
        }

        // Load ACF Auto Migration (handles automatic migration to new field structure)
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-acf-auto-migration.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-acf-auto-migration.php';
            // Auto-migration class auto-initializes if in admin
            error_log('HPH: ACF Auto Migration loaded');
        }

        // Load ACF Migration Helper (manual migration tools)
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-acf-migration.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-acf-migration.php';
            // Migration helper class auto-initializes if in admin
            error_log('HPH: ACF Migration Helper loaded');
        }

        error_log('HPH: Admin classes loaded successfully');

        // Initialize cron schedules for integrations
        hph_init_cron_schedules();

    } catch (\Exception $e) {
        error_log('HPH: Error in admin init: ' . $e->getMessage());
    }
}

/**
 * Initialize cron schedules
 */
function hph_init_cron_schedules() {
    // Add custom cron intervals
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
}

/**
 * Perform periodic Airtable sync
 */
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

/**
 * Dashboard initialization
 */
function hph_init_dashboard() {
    try {
        // This is the critical part for your dashboard!
        $dashboard_ajax_file = HPH_INCLUDES_PATH . 'dashboard/class-dashboard-ajax-handler.php';
        
        if (file_exists($dashboard_ajax_file)) {
            require_once $dashboard_ajax_file;
            
            if (class_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler')) {
                if (method_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler', 'instance')) {
                    HappyPlace\Dashboard\HPH_Dashboard_Ajax_Handler::instance();
                    error_log('HPH: Dashboard AJAX Handler initialized successfully via instance');
                } elseif (method_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler', 'get_instance')) {
                    HappyPlace\Dashboard\HPH_Dashboard_Ajax_Handler::get_instance();
                    error_log('HPH: Dashboard AJAX Handler initialized successfully via get_instance');
                } else {
                    error_log('HPH: Dashboard AJAX Handler class found but no instance method available');
                }
            } else {
                error_log('HPH: CRITICAL - Dashboard AJAX Handler class not found after loading file');
            }
        } else {
            error_log('HPH: CRITICAL - Dashboard AJAX Handler file not found at: ' . $dashboard_ajax_file);
        }

        // Load dashboard sections (only if they exist)
        $sections = [
            'overview' => 'dashboard/sections/class-overview-section.php',
            'listings' => 'dashboard/sections/class-listings-section.php',
        ];

        foreach ($sections as $name => $file) {
            $path = HPH_INCLUDES_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
                error_log("HPH: Loaded {$name} section");
            } else {
                error_log("HPH: Section file not found: {$path}");
            }
        }

        // Setup dashboard hooks
        hph_setup_dashboard_hooks();

        error_log('HPH: Dashboard initialization completed');

    } catch (Exception $e) {
        error_log('HPH: Error in dashboard init: ' . $e->getMessage());
    }
}

/**
 * Setup dashboard-specific hooks
 */
function hph_setup_dashboard_hooks() {
    // Add rewrite rules for dashboard
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
}

/**
 * Load textdomain
 */
function hph_load_textdomain() {
    load_plugin_textdomain('happy-place', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

/**
 * Plugin activation
 */
function hph_activate() {
    error_log('HPH: Plugin activation hook triggered');
    
    // Flush rewrite rules
    flush_rewrite_rules(true);

    // Set default options
    add_option('hph_version', HPH_VERSION);
    add_option('hph_activated_time', time());

    do_action('happy_place_activated');
}

/**
 * Compatibility function for dashboard
 */
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

/**
 * Template loading for custom post types and dashboard
 */
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

/**
 * Locate template in theme or plugin
 */
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

error_log('HPH: Plugin file loaded successfully');