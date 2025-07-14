<?php

/**
 * Happy Place Theme Functions
 * 
 * Main theme functions file for the Happy Place Real Estate Theme.
 * This file initializes the theme and loads all required components.
 * 
 * @package HappyPlace
 * @version 1.0.0
 * @author Happy Place Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// THEME CONSTANTS
// =============================================================================

define('HPH_THEME_VERSION', '1.0.0');
define('HPH_THEME_DIR', get_template_directory());
define('HPH_THEME_URI', get_template_directory_uri());

// =============================================================================
// LOAD REQUIRED FILES
// =============================================================================

// Load Composer autoloader if it exists
if (file_exists(HPH_THEME_DIR . '/vendor/autoload.php')) {
    require_once HPH_THEME_DIR . '/vendor/autoload.php';
}

// Core classes that must be loaded first
require_once HPH_THEME_DIR . '/inc/class-theme-setup.php';
require_once HPH_THEME_DIR . '/inc/class-assets-manager.php';
require_once HPH_THEME_DIR . '/inc/class-template-loader.php';
require_once HPH_THEME_DIR . '/inc/translations.php';

// Helper functions
require_once HPH_THEME_DIR . '/inc/template-functions.php';
require_once HPH_THEME_DIR . '/inc/template-helpers.php';
require_once HPH_THEME_DIR . '/inc/template-tags.php';

// Feature-specific classes
require_once HPH_THEME_DIR . '/inc/class-geocoding.php';
require_once HPH_THEME_DIR . '/inc/class-listing-admin.php';
require_once HPH_THEME_DIR . '/inc/class-listing-helper.php';

// Integration and setup files
require_once HPH_THEME_DIR . '/inc/plugin-integration.php';
require_once HPH_THEME_DIR . '/inc/dashboard-setup.php';
require_once HPH_THEME_DIR . '/inc/dashboard-manager.php';
require_once HPH_THEME_DIR . '/inc/shortcodes.php';

// =============================================================================
// MAIN THEME CLASS
// =============================================================================

class HPH_Theme
{
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Initialize theme
     */
    private function __construct()
    {
        $this->load_dependencies();
        $this->setup_hooks();
        $this->add_rewrite_rules();
    }

    /**
     * Load all existing inc/ files in proper order
     */
    private function load_dependencies(): void
    {
        // All files are now loaded in the main theme initialization
        // This method remains for backward compatibility and potential future use
    }

    /**
     * Safely load a file with error handling
     */
    private function load_file(string $file, string $type = 'general'): bool
    {
        $path = HPH_THEME_DIR . '/' . $file;

        if (file_exists($path)) {
            require_once $path;
            return true;
        } else {
            // Only log missing core files as errors
            if ($type === 'core') {
                error_log("HPH Theme: Core file missing: {$file}");
            } else {
                error_log("HPH Theme: Optional {$type} file not found: {$file}");
            }
            return false;
        }
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void
    {
        add_action('after_setup_theme', [$this, 'theme_setup']);
        add_action('init', [$this, 'init_theme_features']);
        add_action('widgets_init', [$this, 'register_sidebars']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        // Template loading is now handled by Template_Loader class
        add_filter('body_class', [$this, 'add_body_classes']);

        // Clean up WordPress bloat
        add_action('wp_enqueue_scripts', [$this, 'cleanup_wp_assets'], 999);
    }

    /**
     * Theme setup - runs after WordPress is loaded
     */
    public function theme_setup(): void
    {
        // Theme supports
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form',
            'gallery',
            'caption',
            'comment-form',
            'comment-list'
        ]);
        add_theme_support('custom-logo', [
            'height'      => 100,
            'width'       => 400,
            'flex-height' => true,
            'flex-width'  => true,
        ]);
        add_theme_support('customize-selective-refresh-widgets');
        add_theme_support('responsive-embeds');

        // Navigation menus
        register_nav_menus([
            'primary' => __('Primary Menu', 'happy-place'),
            'footer-links-1' => __('Footer Links 1', 'happy-place'),
            'footer-links-2' => __('Footer Links 2', 'happy-place'),
            'footer-links-3' => __('Footer Links 3', 'happy-place'),
            'footer-legal' => __('Footer Legal', 'happy-place'),
        ]);

        // Add theme support for editor styles
        add_theme_support('editor-styles');
        add_editor_style('assets/css/editor-style.css');
    }

    /**
     * Initialize theme features
     */
    public function init_theme_features(): void
    {
        $this->register_image_sizes();
        $this->setup_google_maps_settings();
        $this->maybe_flush_rewrite_rules();
    }

    /**
     * Register custom image sizes
     */
    private function register_image_sizes(): void
    {
        // Property images
        add_image_size('listing-thumb', 480, 320, true);
        add_image_size('listing-gallery', 1200, 800, true);
        add_image_size('listing-hero', 1600, 600, true);
        add_image_size('listing-card', 400, 300, true);

        // People images
        add_image_size('agent-avatar', 150, 150, true);
        add_image_size('agent-large', 480, 640, true);
        add_image_size('agent-profile', 300, 400, true);

        // Location images
        add_image_size('community-thumb', 480, 320, true);
        add_image_size('community-hero', 1600, 600, true);
        add_image_size('city-thumb', 480, 320, true);
        add_image_size('city-hero', 1600, 600, true);

        // Local places
        add_image_size('local-place-thumb', 320, 240, true);
        add_image_size('local-place-feature', 800, 600, true);
        add_image_size('local-place-map-marker', 64, 64, true);

        // Open houses
        add_image_size('open-house-thumb', 480, 320, true);
        add_image_size('open-house-gallery', 800, 600, true);
    }

    /**
     * Setup Google Maps API settings
     */
    private function setup_google_maps_settings(): void
    {
        if (is_admin()) {
            add_action('admin_init', function () {
                register_setting('general', 'hph_google_maps_api_key', [
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default' => ''
                ]);

                add_settings_section(
                    'hph_maps_settings',
                    __('Map Settings', 'happy-place'),
                    function () {
                        echo '<p>' . __('Configure Google Maps integration.', 'happy-place') . '</p>';
                    },
                    'general'
                );

                add_settings_field(
                    'hph_google_maps_api_key',
                    __('Google Maps API Key', 'happy-place'),
                    function () {
                        $key = get_option('hph_google_maps_api_key');
                        echo '<input type="text" class="regular-text" name="hph_google_maps_api_key" value="' . esc_attr($key) . '">';
                        echo '<p class="description">' . __('Enter your Google Maps API key.', 'happy-place') . '</p>';
                    },
                    'general',
                    'hph_maps_settings'
                );
            });
        }
    }

    /**
     * Register widget areas
     */
    public function register_sidebars(): void
    {
        // Main sidebar
        register_sidebar([
            'name' => __('Main Sidebar', 'happy-place'),
            'id' => 'sidebar-1',
            'description' => __('Add widgets here to appear in the sidebar.', 'happy-place'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ]);

        // Footer widgets
        for ($i = 1; $i <= 4; $i++) {
            register_sidebar([
                'name' => sprintf(__('Footer %d', 'happy-place'), $i),
                'id' => "footer-{$i}",
                'description' => sprintf(__('Footer widget area %d', 'happy-place'), $i),
                'before_widget' => '<div id="%1$s" class="widget %2$s">',
                'after_widget' => '</div>',
                'before_title' => '<h3 class="widget-title">',
                'after_title' => '</h3>',
            ]);
        }
    }

    /**
     * Enqueue theme assets via asset manager
     */
    public function enqueue_assets(): void
    {
        // Use your existing asset manager if available
        if (class_exists('HPH_Assets_Manager')) {
            HPH_Assets_Manager::instance();
        } else {
            // Fallback asset loading
            $this->fallback_enqueue_assets();
        }
    }

    /**
     * Fallback asset loading if asset manager not available
     */
    private function fallback_enqueue_assets(): void
    {
        $version = HPH_THEME_VERSION;

        // Main stylesheet
        wp_enqueue_style(
            'happyplace-main',
            HPH_THEME_URI . '/assets/css/theme.css',
            [],
            $version
        );

        // Main JavaScript
        wp_enqueue_script(
            'happyplace-main',
            HPH_THEME_URI . '/assets/js/theme.js',
            ['jquery'],
            $version,
            true
        );

        // Localize script for theme AJAX (not dashboard)
        wp_localize_script('happyplace-main', 'hphTheme', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_theme_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
            ]
        ]);
    }

    /**
     * Add custom query variables
     */
    public function add_query_vars($vars): array
    {
        $custom_vars = [
            // Dashboard
            'dashboard_section',
            'section',

            // Search & Filters
            'search_term',
            'location',
            'city',
            'community',
            'place',
            'price_min',
            'price_max',
            'bedrooms',
            'bathrooms',
            'property_type',
            'features',
            'sort',
            'view',

            // Dates
            'date_from',
            'date_to',
            'time_from',
            'time_to',

            // Other
            'agent_id',
            'listing_id'
        ];

        return array_merge($vars, $custom_vars);
    }

    /**
     * Load custom templates
     */
    public function load_custom_templates($template): string
    {
        // Agent Dashboard - check multiple locations
        if (is_page('agent-dashboard') || is_page_template('page-agent-dashboard.php')) {
            $custom_templates = [
                HPH_THEME_DIR . '/page-templates/page-agent-dashboard.php',
                HPH_THEME_DIR . '/templates/dashboard/agent-dashboard.php',
                HPH_THEME_DIR . '/agent-dashboard.php'
            ];

            foreach ($custom_templates as $custom_template) {
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }

        // Single post types
        if (is_singular()) {
            $post_type = get_post_type();
            $custom_templates = [
                HPH_THEME_DIR . "/single-{$post_type}.php",
                HPH_THEME_DIR . "/templates/{$post_type}/single-{$post_type}.php"
            ];

            foreach ($custom_templates as $custom_template) {
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }

        // Archive post types
        if (is_post_type_archive()) {
            $post_type = get_post_type();
            $custom_templates = [
                HPH_THEME_DIR . "/archive-{$post_type}.php",
                HPH_THEME_DIR . "/templates/{$post_type}/archive-{$post_type}.php"
            ];

            foreach ($custom_templates as $custom_template) {
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
        }

        return $template;
    }

    /**
     * Add custom body classes
     */
    public function add_body_classes($classes): array
    {
        // Dashboard pages - only add if we can detect them
        if (function_exists('hph_is_dashboard') && hph_is_dashboard()) {
            $classes[] = 'hph-dashboard-page';
            $classes[] = 'page-template-agent-dashboard';

            // Add current dashboard section if function exists
            if (function_exists('hph_get_dashboard_section')) {
                $section = hph_get_dashboard_section();
                if ($section) {
                    $classes[] = "hph-dashboard-section-{$section}";
                }
            }
        }

        // Post type specific classes
        if (is_singular()) {
            $post_type = get_post_type();
            $classes[] = "single-{$post_type}-page";
        }

        if (is_post_type_archive()) {
            $post_type = get_post_type();
            $classes[] = "archive-{$post_type}-page";
        }

        return $classes;
    }

    /**
     * Add rewrite rules for agent dashboard
     */
    public function add_rewrite_rules(): void
    {
        add_action('init', function () {
            add_rewrite_tag('%dashboard_section%', '([^&]+)');
            add_rewrite_rule(
                '^agent-dashboard/?$',
                'index.php?pagename=agent-dashboard',
                'top'
            );
            add_rewrite_rule(
                '^agent-dashboard/([^/]+)/?$',
                'index.php?pagename=agent-dashboard&dashboard_section=$matches[1]',
                'top'
            );
        });
    }

    /**
     * Clean up WordPress bloat
     */
    public function cleanup_wp_assets(): void
    {
        // Remove block library CSS if not using Gutenberg extensively
        if (!is_admin()) {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-blocks-style'); // WooCommerce blocks
        }

        // Remove emoji scripts (unless already done in template-functions.php)
        if (!function_exists('happy_place_disable_emojis')) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
        }

        // Remove other unnecessary scripts
        wp_dequeue_script('wp-embed');
    }

    /**
     * Flush rewrite rules if version changed
     */
    private function maybe_flush_rewrite_rules(): void
    {
        $version = get_option('hph_theme_version', '');
        if ($version !== HPH_THEME_VERSION) {
            flush_rewrite_rules();
            update_option('hph_theme_version', HPH_THEME_VERSION);
        }
    }
}

// =============================================================================
// PLUGIN INTEGRATION FUNCTIONS (Only if not already defined)
// =============================================================================

/**
 * Check if Happy Place Plugin dashboard handler is available
 */
if (!function_exists('hph_plugin_dashboard_available')) {
    function hph_plugin_dashboard_available(): bool
    {
        return class_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler');
    }
}

/**
 * Check if Happy Place Plugin is active
 */
if (!function_exists('hph_plugin_active')) {
    function hph_plugin_active(): bool
    {
        return class_exists('HappyPlace\\Core\\Post_Types');
    }
}

/**
 * Get dashboard data from plugin safely
 */
if (!function_exists('hph_get_dashboard_data')) {
    function hph_get_dashboard_data(string $section): array
    {
        if (!hph_plugin_dashboard_available()) {
            return [];
        }

        return apply_filters('hph_get_dashboard_section_data', [], $section);
    }
}

/**
 * Get filtered listings from plugin safely
 */
if (!function_exists('hph_get_listings')) {
    function hph_get_listings(array $filters = []): array
    {
        if (!hph_plugin_active()) {
            return [];
        }

        return apply_filters('hph_get_filtered_listings', [], $filters);
    }
}

/**
 * Get listing by ID from plugin safely
 */
if (!function_exists('hph_get_listing_by_id')) {
    function hph_get_listing_by_id(int $listing_id)
    {
        if (!hph_plugin_active()) {
            return null;
        }

        return apply_filters('hph_get_listing_by_id', null, $listing_id);
    }
}

// =============================================================================
// DASHBOARD FUNCTIONS (Only if not already defined in template-functions.php)
// =============================================================================

/**
 * Check if current page is a dashboard page
 * Only define if not already defined in template-functions.php
 */
if (!function_exists('hph_is_dashboard')) {
    function hph_is_dashboard(): bool
    {
        global $post;

        // Check if current page is agent dashboard
        if (is_page() && $post) {
            if ($post->post_name === 'agent-dashboard') {
                return true;
            }

            $template = get_page_template_slug($post->ID);
            if (strpos($template, 'dashboard') !== false) {
                return true;
            }
        }

        // Check URL patterns
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($request_uri, '/dashboard') !== false ||
            strpos($request_uri, '/agent-dashboard') !== false;
    }
}

/**
 * Get current dashboard section
 */
if (!function_exists('hph_get_dashboard_section')) {
    function hph_get_dashboard_section(): string
    {
        // Try query var first
        $section = get_query_var('dashboard_section');
        if ($section) {
            return sanitize_key($section);
        }

        // Try GET parameter
        $section = $_GET['section'] ?? '';
        if ($section) {
            return sanitize_key($section);
        }

        // Default to overview
        return 'overview';
    }
}

/**
 * Get dashboard URL with optional section
 */
if (!function_exists('hph_get_dashboard_url')) {
    function hph_get_dashboard_url(string $section = ''): string
    {
        $page = get_page_by_path('agent-dashboard');
        $base_url = $page ? get_permalink($page->ID) : home_url('/agent-dashboard/');

        return $section ? add_query_arg('section', $section, $base_url) : $base_url;
    }
}

/**
 * Check if user can access dashboard
 */
if (!function_exists('hph_user_can_access_dashboard')) {
    function hph_user_can_access_dashboard(): bool
    {
        if (!is_user_logged_in()) {
            return false;
        }

        return current_user_can('agent') ||
            current_user_can('administrator') ||
            current_user_can('edit_posts');
    }
}

// =============================================================================
// ACF INTEGRATION (Only if ACF is available)
// =============================================================================

/**
 * Initialize ACF Options Pages
 */
if (function_exists('acf_add_options_page')) {
    acf_add_options_page([
        'page_title'  => __('Company Settings', 'happy-place'),
        'menu_title'  => __('Company Settings', 'happy-place'),
        'menu_slug'   => 'company-settings',
        'capability'  => 'manage_options',
        'redirect'    => false,
        'position'    => 2
    ]);
}

/**
 * Register ACF field groups for user profiles
 */
function hph_register_acf_fields(): void
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Agent Details Field Group for User Profiles
    acf_add_local_field_group([
        'key' => 'group_agent_details',
        'title' => 'Agent Details',
        'fields' => [
            [
                'key' => 'field_agent_photo',
                'label' => 'Agent Photo',
                'name' => 'agent_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ],
            [
                'key' => 'field_agent_name',
                'label' => 'Full Name',
                'name' => 'name',
                'type' => 'text',
                'required' => 1,
            ],
            [
                'key' => 'field_agent_title',
                'label' => 'Title/Position',
                'name' => 'title',
                'type' => 'text',
            ],
            [
                'key' => 'field_agent_phone',
                'label' => 'Phone Number',
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'key' => 'field_agent_email',
                'label' => 'Email Address',
                'name' => 'email',
                'type' => 'email',
            ],
            [
                'key' => 'field_agent_bio',
                'label' => 'Biography',
                'name' => 'bio',
                'type' => 'textarea',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ],
                [
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'administrator',
                ],
            ],
            [
                [
                    'param' => 'user_form',
                    'operator' => '==',
                    'value' => 'edit',
                ],
                [
                    'param' => 'user_role',
                    'operator' => '==',
                    'value' => 'hph_agent',
                ],
            ],
        ],
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
    ]);
}
add_action('acf/init', 'hph_register_acf_fields');

// =============================================================================
// INITIALIZATION
// =============================================================================

/**
 * Initialize the theme
 */
function hph_theme_init(): HPH_Theme
{
    return HPH_Theme::instance();
}

// Start the theme
hph_theme_init();

/**
 * Create dashboard page if it doesn't exist
 */
add_action('init', function () {
    // Only run on admin and if plugin is active
    if (!is_admin() || !function_exists('hph_plugin_active') || !hph_plugin_active()) {
        return;
    }

    $page = get_page_by_path('agent-dashboard');
    if (!$page) {
        $page_id = wp_insert_post([
            'post_title' => 'Agent Dashboard',
            'post_name' => 'agent-dashboard',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '<!-- wp:paragraph --><p>This is the agent dashboard. Please log in to access your agent tools.</p><!-- /wp:paragraph -->',
            'meta_input' => [
                '_wp_page_template' => 'page-agent-dashboard.php'
            ]
        ]);

        if (!is_wp_error($page_id)) {
            error_log('HPH Theme: Created agent dashboard page (ID: ' . $page_id . ')');
        }
    }
}, 20);

/**
 * Register custom page templates
 */
add_filter('theme_page_templates', function ($templates) {
    $templates['page-agent-dashboard.php'] = __('Agent Dashboard', 'happy-place');
    return $templates;
});

/**
 * Load text domain for translations
 */
add_action('after_setup_theme', function () {
    load_theme_textdomain('happy-place', HPH_THEME_DIR . '/languages');
});

/**
 * Ensure dashboard assets are properly enqueued when needed
 */
add_action('wp_enqueue_scripts', function () {
    // Only add dashboard-specific scripts if we're on dashboard and have plugin support
    if (
        function_exists('hph_is_dashboard') &&
        function_exists('hph_plugin_dashboard_available') &&
        hph_is_dashboard() &&
        hph_plugin_dashboard_available()
    ) {

        // Ensure dashboard nonce is available for plugin AJAX
        wp_localize_script('jquery', 'hphAjax', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_dashboard_nonce'),
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce'),
            'themeNonce' => wp_create_nonce('hph_theme_nonce'),
            'currentUser' => get_current_user_id(),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Success', 'happy-place'),
            ]
        ]);
    }
}, 20);
