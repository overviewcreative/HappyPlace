<?php
/**
 * Assets Manager Class
 *
 * Handles all theme assets (CSS, JavaScript, etc.)
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Assets {
    /**
     * Instance of this class
     *
     * @var Assets
     */
    private static $instance = null;

    /**
     * Get instance of this class
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Asset paths
     */
    private array $asset_paths = [];

    /**
     * Version cache
     */
    private array $version_cache = [];

    /**
     * Constructor
     */
    private function __construct() {
        $this->setup_asset_paths();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Setup asset paths
     */
    private function setup_asset_paths(): void {
        $this->asset_paths = [
            'css' => [
                'core' => HPH_ASSETS_URI . '/css/',
                'admin' => HPH_ASSETS_URI . '/css/admin/',
                'templates' => HPH_ASSETS_URI . '/css/templates/',
            ],
            'js' => [
                'core' => HPH_ASSETS_URI . '/js/',
                'admin' => HPH_ASSETS_URI . '/js/admin/',
                'templates' => HPH_ASSETS_URI . '/js/templates/',
            ]
        ];
    }

    /**
     * Enqueue frontend styles
     */
    public function enqueue_styles(): void {
        // Core styles - Always load these
        wp_enqueue_style(
            'happy-place-style',
            get_stylesheet_uri(),
            [],
            $this->get_asset_version(get_stylesheet_uri())
        );

        // Base styles - Core dependency for all other styles
        wp_enqueue_style(
            'happy-place-base',
            $this->asset_paths['css']['core'] . 'base.css',
            ['happy-place-style'],
            $this->get_asset_version($this->asset_paths['css']['core'] . 'base.css')
        );

        // Listing styles
        if (is_post_type_archive('listing') || is_singular('listing')) {
            wp_enqueue_style(
                'happy-place-listings',
                HPH_ASSETS_URI . '/css/listings.css',
                ['happy-place-style'],
                HPH_THEME_VERSION
            );

            wp_enqueue_style(
                'happy-place-maps',
                HPH_ASSETS_URI . '/css/maps.css',
                ['happy-place-style'],
                HPH_THEME_VERSION
            );

            wp_enqueue_style(
                'happy-place-filters',
                HPH_ASSETS_URI . '/css/filters.css',
                ['happy-place-style'],
                HPH_THEME_VERSION
            );
        }

        // Dashboard styles - Enhanced detection for rebuilt dashboard
        if (is_page('agent-dashboard') || is_page_template('page-templates/agent-dashboard-rebuilt.php')) {
            // Load variables.css first
            wp_enqueue_style(
                'happyplace-variables',
                HPH_ASSETS_URI . '/css/variables.css',
                [],
                HPH_THEME_VERSION
            );
            
            // Load core.css with variables dependency
            wp_enqueue_style(
                'happyplace-core',
                HPH_ASSETS_URI . '/css/core.css',
                ['happyplace-variables'],
                HPH_THEME_VERSION
            );
            
            // Load dashboard styles in proper order
            wp_enqueue_style(
                'happyplace-dashboard-core',
                HPH_ASSETS_URI . '/css/dashboard-core.css',
                ['happyplace-core'],
                HPH_THEME_VERSION
            );
            
            wp_enqueue_style(
                'happyplace-dashboard-utilities',
                HPH_ASSETS_URI . '/css/dashboard-utilities.css',
                ['happyplace-dashboard-core'],
                HPH_THEME_VERSION
            );
            
            wp_enqueue_style(
                'happyplace-dashboard-sections',
                HPH_ASSETS_URI . '/css/dashboard-sections.css',
                ['happyplace-dashboard-core'],
                HPH_THEME_VERSION
            );
            
            wp_enqueue_style(
                'happyplace-dashboard-responsive',
                HPH_ASSETS_URI . '/css/dashboard-responsive.css',
                ['happyplace-dashboard-core'],
                HPH_THEME_VERSION
            );
            
            wp_enqueue_style(
                'happyplace-listing-cards',
                HPH_ASSETS_URI . '/css/listing-card.css',
                ['happyplace-core'],
                HPH_THEME_VERSION
            );
            
            wp_enqueue_style(
                'happyplace-listing-swipe-cards',
                HPH_ASSETS_URI . '/css/listing-swipe-card.css',
                ['happyplace-core'],
                HPH_THEME_VERSION
            );
        }
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        // Main theme JavaScript
        wp_enqueue_script(
            'happy-place-scripts',
            HPH_ASSETS_URI . '/js/main.js',
            ['jquery'],
            HPH_THEME_VERSION,
            true
        );

        // Core AJAX configuration
        wp_localize_script('happy-place-scripts', 'hphCore', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'themeNonce' => wp_create_nonce('hph_theme_nonce'),
            'dashboardNonce' => wp_create_nonce('hph_dashboard_nonce'),
            'currentUser' => get_current_user_id(),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Success', 'happy-place'),
            ]
        ]);

        // Listing functionality
        if (is_post_type_archive('listing') || is_singular('listing')) {
            // Enqueue Google Maps
            wp_enqueue_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . $this->get_maps_api_key() . '&libraries=places',
                [],
                null,
                true
            );

            // Maps functionality
            wp_enqueue_script(
                'happy-place-maps',
                HPH_ASSETS_URI . '/js/maps.js',
                ['google-maps', 'jquery'],
                HPH_THEME_VERSION,
                true
            );

            // Listings functionality
            wp_enqueue_script(
                'happy-place-listings',
                HPH_ASSETS_URI . '/js/listings.js',
                ['jquery'],
                HPH_THEME_VERSION,
                true
            );

            // Filters functionality
            wp_enqueue_script(
                'happy-place-filters',
                HPH_ASSETS_URI . '/js/filters.js',
                ['jquery'],
                HPH_THEME_VERSION,
                true
            );

            // Localize scripts
            wp_localize_script('happy-place-maps', 'happyPlaceMaps', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_maps_nonce'),
            ]);

            wp_localize_script('happy-place-listings', 'happyPlaceListings', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_listings_nonce'),
                'messages' => [
                    'loadMore' => __('Load More', 'happy-place'),
                    'noResults' => __('No listings found matching your criteria.', 'happy-place'),
                    'loading' => __('Loading...', 'happy-place')
                ]
            ]);
        }

        // Dashboard functionality
        if (is_page('agent-dashboard')) {
            wp_enqueue_script(
                'happy-place-dashboard',
                HPH_ASSETS_URI . '/js/dashboard.js',
                ['jquery'],
                HPH_THEME_VERSION,
                true
            );

            // Localize script
            wp_localize_script('happy-place-dashboard', 'happyPlaceDashboard', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('happy_place_dashboard_nonce'),
                'messages' => [
                    'listingSaved' => __('Listing saved successfully', 'happy-place'),
                    'listingError' => __('Error saving listing', 'happy-place'),
                    'deleteConfirm' => __('Are you sure you want to delete this listing?', 'happy-place')
                ]
            ]);
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Admin styles
        wp_enqueue_style(
            'happy-place-admin',
            HPH_ASSETS_URI . '/css/admin.css',
            [],
            HPH_THEME_VERSION
        );

        // Admin scripts
        wp_enqueue_script(
            'happy-place-admin',
            HPH_ASSETS_URI . '/js/admin.js',
            ['jquery'],
            HPH_THEME_VERSION,
            true
        );
    }

    /**
     * Get version for an asset file
     */
    private function get_asset_version(string $file): string {
        $cache_key = md5($file);
        
        if (isset($this->version_cache[$cache_key])) {
            return $this->version_cache[$cache_key];
        }

        // Convert URI to file path
        $file_path = str_replace(
            HPH_ASSETS_URI,
            HPH_THEME_DIR . '/assets',
            $file
        );

        $version = HPH_THEME_VERSION;
        if (file_exists($file_path)) {
            $version = (string)filemtime($file_path);
        }
        
        $this->version_cache[$cache_key] = $version;
        return $version;
    }

    /**
     * Load template-specific assets
     */
    private function load_template_assets(): void {
        $template_name = $this->get_current_template_name();
        if (!$template_name) {
            return;
        }

        // CSS
        $css_path = $this->asset_paths['css']['templates'] . $template_name . '.css';
        $css_file = str_replace(HPH_ASSETS_URI, HPH_THEME_DIR . '/assets', $css_path);
        
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'happy-place-template-' . $template_name,
                $css_path,
                ['happy-place-style'],
                $this->get_asset_version($css_path)
            );
        }

        // JavaScript
        $js_path = $this->asset_paths['js']['templates'] . $template_name . '.js';
        $js_file = str_replace(HPH_ASSETS_URI, HPH_THEME_DIR . '/assets', $js_path);
        
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'happy-place-template-' . $template_name,
                $js_path,
                ['happy-place-scripts'],
                $this->get_asset_version($js_path),
                true
            );
        }
    }

    /**
     * Get current template name
     */
    private function get_current_template_name(): ?string {
        // Check for custom page template
        $template_slug = get_page_template_slug();
        if ($template_slug) {
            return basename($template_slug, '.php');
        }

        // Post type specific templates
        if (is_singular()) {
            return 'single-' . get_post_type();
        }

        if (is_post_type_archive()) {
            return 'archive-' . get_post_type();
        }

        if (is_archive()) {
            return 'archive';
        }

        if (is_home() || is_front_page()) {
            return 'index';
        }

        return null;
    }

    /**
     * Get Google Maps API key
     */
    private function get_maps_api_key(): string {
        $key = get_option('hph_google_maps_api_key', '');
        if (!$key && defined('\\GOOGLE_MAPS_API_KEY')) {
            $key = \GOOGLE_MAPS_API_KEY;
        }
        return $key;
    }
}
