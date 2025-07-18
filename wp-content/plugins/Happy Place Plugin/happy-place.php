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
// SIMPLE INITIALIZATION WITHOUT NAMESPACE
// =============================================================================

// Load core functions first
require_once HPH_INCLUDES_PATH . 'dashboard-functions.php';
require_once HPH_INCLUDES_PATH . 'template-functions.php';

// Load utility classes that might be needed
if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-data-validator.php')) {
    require_once HPH_INCLUDES_PATH . 'utilities/class-data-validator.php';
    error_log('HPH: Data Validator utility loaded');
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

    } catch (Exception $e) {
        error_log('HPH: Error in main init: ' . $e->getMessage());
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

class Airtable_Integration {
    private static ?self $instance = null;

    // Singleton instance getter
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required integration files
     */
    private function load_dependencies(): void {
        require_once plugin_dir_path(__FILE__) . 'includes/integrations/class-airtable-two-way-sync.php';
        require_once plugin_dir_path(__FILE__) . 'includes/integrations/class-airtable-sync-ajax-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/integrations/class-airtable-settings.php';
    }

    /**
     * Initialize hooks and integrations
     */
    private function init_hooks(): void {
        // Register settings
        add_action('admin_init', [$this, 'register_airtable_settings']);

        // Setup periodic sync
        $this->schedule_periodic_sync();
    }

    /**
     * Register Airtable integration settings
     */
    public function register_airtable_settings(): void {
        register_setting(
            'happy_place_options_group', 
            'happy_place_airtable_options',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_airtable_settings']
            ]
        );

        add_settings_section(
            'happy_place_airtable_section', 
            'Airtable Integration', 
            [$this, 'airtable_section_callback'], 
            'happy-place-settings'
        );

        add_settings_field(
            'airtable_api_key', 
            'Airtable API Key', 
            [$this, 'render_api_key_field'], 
            'happy-place-settings', 
            'happy_place_airtable_section'
        );

        add_settings_field(
            'airtable_base_id', 
            'Airtable Base ID', 
            [$this, 'render_base_id_field'], 
            'happy-place-settings', 
            'happy_place_airtable_section'
        );
    }

    /**
     * Sanitize Airtable settings
     */
    public function sanitize_airtable_settings($input): array {
        $output = [];

        // Sanitize API Key
        if (isset($input['api_key'])) {
            $output['api_key'] = sanitize_text_field($input['api_key']);
        }

        // Sanitize Base ID
        if (isset($input['base_id'])) {
            $output['base_id'] = sanitize_text_field($input['base_id']);
        }

        return $output;
    }

    /**
     * Airtable settings section callback
     */
    public function airtable_section_callback(): void {
        echo '<p>Configure your Airtable integration settings.</p>';
    }

    /**
     * Render API Key input field
     */
    public function render_api_key_field(): void {
        $options = get_option('happy_place_airtable_options', []);
        $api_key = $options['api_key'] ?? '';
        ?>
        <input 
            type="text" 
            name="happy_place_airtable_options[api_key]" 
            value="<?php echo esc_attr($api_key); ?>" 
            class="regular-text"
            placeholder="Enter your Airtable API Key"
        />
        <p class="description">
            Get your API key from 
            <a href="https://airtable.com/account" target="_blank">Airtable Account</a>
        </p>
        <?php
    }

    /**
     * Render Base ID input field
     */
    public function render_base_id_field(): void {
        $options = get_option('happy_place_airtable_options', []);
        $base_id = $options['base_id'] ?? '';
        ?>
        <input 
            type="text" 
            name="happy_place_airtable_options[base_id]" 
            value="<?php echo esc_attr($base_id); ?>" 
            class="regular-text"
            placeholder="Enter your Airtable Base ID"
        />
        <p class="description">
            Find your Base ID in the Airtable API documentation
        </p>
        <?php
    }

    /**
     * Schedule periodic sync
     */
    private function schedule_periodic_sync(): void {
        // Add custom interval for sync
        add_filter('cron_schedules', [$this, 'add_sync_interval']);

        // Schedule sync if not already scheduled
        if (!wp_next_scheduled('hph_airtable_periodic_sync')) {
            wp_schedule_event(
                time(), 
                'every_six_hours', 
                'hph_airtable_periodic_sync'
            );
        }

        // Hook for periodic sync
        add_action('hph_airtable_periodic_sync', [$this, 'perform_periodic_sync']);
    }

    /**
     * Add custom sync interval
     */
    public function add_sync_interval($schedules): array {
        $schedules['every_six_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => __('Every Six Hours')
        ];
        return $schedules;
    }

    /**
     * Perform periodic background sync
     */
    public function perform_periodic_sync(): void {
        // Get Airtable configuration
        $options = get_option('happy_place_airtable_options', []);
        $base_id = $options['base_id'] ?? null;

        if (!$base_id) {
            error_log('Airtable Base ID not configured for periodic sync');
            return;
        }

        try {
            $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync(
                $base_id, 
                'Listings'  // Hardcoded table name, can be made configurable
            );

            // Perform two-way sync
            $airtable_to_wp_result = $sync->sync_airtable_to_wordpress();
            $wp_to_airtable_result = $sync->sync_wordpress_to_airtable();

            // Log sync results
            error_log('Periodic Airtable Sync Results: ' . print_r([
                'Airtable to WP' => $airtable_to_wp_result,
                'WP to Airtable' => $wp_to_airtable_result
            ], true));

        } catch (\Exception $e) {
            error_log('Periodic Airtable Sync Error: ' . $e->getMessage());
        }
    }

    /**
     * Add settings page to plugin menu
     */
    public function add_settings_page(): void {
        add_submenu_page(
            'happy-place-dashboard',  // Parent slug
            'Airtable Integration',   // Page title
            'Airtable Sync',          // Menu title
            'manage_options',          // Capability
            'happy-place-airtable',    // Menu slug
            [$this, 'render_settings_page'] // Callback
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('happy_place_options_group');
                do_settings_sections('happy-place-settings');
                submit_button('Save Airtable Settings');
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the Airtable integration
Airtable_Integration::get_instance();

error_log('HPH: Plugin file loaded successfully');