<?php

/**
 * Assets Manager
 * 
 * Handles all theme asset loading, API integrations, and template-specific assets.
 * Manages core assets, dashboard assets, template assets, and API scripts.
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Assets Manager Class
 * 
 * Manages:
 * - Core theme assets (CSS/JS)
 * - Dashboard-specific assets
 * - Template-specific assets
 * - API scripts (Google Maps, etc.)
 * - Form handling assets
 * - Hierarchical loading order
 */
class HPH_Assets_Manager
{
    /**
     * @var HPH_Assets_Manager|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array Template-specific asset configurations
     */
    private array $template_assets = [];

    /**
     * @var array Loaded assets tracking
     */
    private array $loaded_assets = [];

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor - Initialize asset management
     */
    private function __construct()
    {
        $this->setup_hooks();
        $this->configure_template_assets();

        if (WP_DEBUG) {
            add_action('wp_footer', [$this, 'debug_loaded_assets']);
        }
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void
    {
        // Core asset loading
        add_action('wp_enqueue_scripts', [$this, 'register_api_scripts'], 5);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_core_assets'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_template_assets'], 15);
        add_action('wp_enqueue_scripts', [$this, 'localize_scripts'], 20);

        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Asset optimization
        add_filter('script_loader_tag', [$this, 'optimize_script_loading'], 10, 2);
        add_filter('style_loader_tag', [$this, 'optimize_style_loading'], 10, 2);
    }

    /**
     * Configure template-specific asset mappings
     */
    private function configure_template_assets(): void
    {
        $this->template_assets = [
            // Main templates
            'templates/dashboard/agent-dashboard.php' => [
                'styles' => ['dashboard-core', 'dashboard-sections', 'dashboard-responsive'],
                'scripts' => ['dashboard', 'dashboard-forms', 'dashboard-charts'],
                'api_deps' => ['google-maps']
            ],

            // Listing templates
            'archive-listing.php' => [
                'styles' => ['archive-listing', 'listing-filters', 'listing-map', 'map-clusters'],
                'scripts' => ['archive-listing', 'listing-filters', 'listing-map', 'listing-filters-ajax'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'single-listing.php' => [
                'styles' => ['single-listing', 'listing-gallery', 'listing-map', 'map-info-window'],
                'scripts' => ['single-listing', 'listing-gallery', 'listing-contact', 'listing-map'],
                'api_deps' => ['google-maps']
            ],

            // Agent templates
            'archive-agent.php' => [
                'styles' => ['agent-archive', 'core', 'agent-styles'],
                'scripts' => ['agent-archive', 'agent-filters', 'agent-contact'],
                'api_deps' => []
            ],
            'single-agent.php' => [
                'styles' => ['agent-single', 'core', 'agent-styles'],
                'scripts' => ['agent-contact'],
                'api_deps' => []
            ],

            // Community templates
            'archive-community.php' => [
                'styles' => ['archive-listing', 'listing-filters', 'listing-map', 'map-clusters'],
                'scripts' => ['archive-listing', 'listing-filters', 'listing-map'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'single-community.php' => [
                'styles' => ['single-listing', 'listing-map', 'map-info-window'],
                'scripts' => ['single-listing', 'listing-map'],
                'api_deps' => ['google-maps']
            ],

            // City templates
            'archive-city.php' => [
                'styles' => ['archive-listing', 'listing-filters', 'listing-map', 'map-clusters'],
                'scripts' => ['archive-listing', 'listing-filters', 'listing-map'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'single-city.php' => [
                'styles' => ['single-listing', 'listing-map', 'map-info-window'],
                'scripts' => ['single-listing', 'listing-map'],
                'api_deps' => ['google-maps']
            ],

            // Local place templates
            'archive-local-place.php' => [
                'styles' => ['archive-listing', 'listing-filters', 'listing-map', 'map-clusters'],
                'scripts' => ['archive-listing', 'listing-filters', 'listing-map'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'single-local-place.php' => [
                'styles' => ['single-listing', 'listing-map', 'map-info-window'],
                'scripts' => ['listing-map', 'google-places-autocomplete'],
                'api_deps' => ['google-maps']
            ],

            // Open house templates
            'archive-open-house.php' => [
                'styles' => ['archive-listing', 'listing-filters', 'listing-map', 'map-clusters'],
                'scripts' => ['archive-listing', 'listing-filters', 'listing-map'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'single-open-house.php' => [
                'styles' => ['single-listing', 'listing-map', 'map-info-window'],
                'scripts' => ['single-listing', 'listing-contact', 'listing-map'],
                'api_deps' => ['google-maps']
            ],

            // Transaction templates
            'archive-transaction.php' => [
                'styles' => ['dashboard-core', 'archive-listing'],
                'scripts' => ['dashboard', 'listing-filters'],
                'api_deps' => []
            ],
            'single-transaction.php' => [
                'styles' => ['dashboard-core', 'single-listing'],
                'scripts' => ['dashboard'],
                'api_deps' => []
            ],

            // Dashboard sections
            'overview.php' => [
                'styles' => ['dashboard-sections'],
                'scripts' => ['dashboard-charts']
            ],
            'listings.php' => [
                'styles' => ['dashboard-sections', 'listing-filters'],
                'scripts' => ['dashboard-forms', 'listing-filters']
            ],
            'leads.php' => [
                'styles' => ['dashboard-sections'],
                'scripts' => ['dashboard-forms']
            ],
            'profile.php' => [
                'styles' => ['dashboard-sections'],
                'scripts' => ['dashboard-forms']
            ],

            // Template parts that might need specific assets
            'templates/template-parts/listing/map-view.php' => [
                'styles' => ['listing-map', 'map-clusters', 'map-info-window'],
                'scripts' => ['listing-map', 'listing-map-clusterer'],
                'api_deps' => ['google-maps', 'markerclustererplus']
            ],
            'templates/template-parts/listing/filters-listing.php' => [
                'styles' => ['listing-filters', 'listing-filters-ajax'],
                'scripts' => ['listing-filters', 'listing-filters-ajax', 'filter-sidebar']
            ],
            'templates/template-parts/calculators/mortgage-calculator.php' => [
                'styles' => ['dashboard-utilities'],
                'scripts' => ['mortgage-calculator']
            ],
            'templates/template-parts/listing/card-listing.php' => [
                'styles' => ['listing-swipe-card', 'listing-list-card'],
                'scripts' => ['listing-swipe-card']
            ]
        ];
    }

    /**
     * Register API scripts (Google Maps, etc.)
     * Priority: 5 - Load API scripts first
     */
    public function register_api_scripts(): void
    {
        // Google Maps API
        $maps_api_key = get_option('hph_google_maps_api_key') ?: get_theme_mod('google_maps_api_key');
        if ($maps_api_key) {
            wp_register_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($maps_api_key) . '&libraries=places',
                [],
                null,
                true
            );

            // MarkerClustererPlus for map clustering
            wp_register_script(
                'markerclustererplus',
                get_template_directory_uri() . '/assets/js/lib/markerclustererplus.min.js',
                ['google-maps'],
                '1.2.10',
                true
            );
        }

        // Chart.js for dashboard charts
        wp_register_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            [],
            '3.9.1',
            true
        );
    }

    /**
     * Enqueue core theme assets
     * Priority: 10 - Load core assets after API scripts
     */
    public function enqueue_core_assets(): void
    {
        $theme_version = wp_get_theme()->get('Version');
        $theme_uri = get_template_directory_uri();

        // Font Awesome - Core dependency
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
            [],
            '6.4.2'
        );

        // Main theme stylesheet - Foundation
        wp_enqueue_style(
            'happyplace-main',
            get_stylesheet_uri(),
            ['font-awesome'],
            $theme_version
        );

        // Core theme styles - Enhanced functionality
        if (file_exists(get_template_directory() . '/assets/css/core.css')) {
            wp_enqueue_style(
                'happyplace-core',
                $theme_uri . '/assets/css/core.css',
                ['happyplace-main'],
                $this->get_file_version('/assets/css/core.css')
            );
        }

        // Listing-related core styles (using existing CSS files)
        $listing_styles = [
            'archive-listing' => '/assets/css/archive-listing.css',
            'listing-swipe-card' => '/assets/css/listing-swipe-card.css',
            'single-listing' => '/assets/css/single-listing.css'
        ];

        foreach ($listing_styles as $handle => $path) {
            if (file_exists(get_template_directory() . $path)) {
                wp_enqueue_style(
                    "happyplace-{$handle}",
                    $theme_uri . $path,
                    ['happyplace-core'],
                    $this->get_file_version($path)
                );
            }
        }

        // Core JavaScript
        wp_enqueue_script(
            'happyplace-main',
            $theme_uri . '/assets/js/main.js',
            ['jquery'],
            $this->get_file_version('/assets/js/main.js'),
            true
        );

        // Listing swipe functionality (from original theme)
        if (file_exists(get_template_directory() . '/assets/js/listing-swipe-card.js')) {
            wp_enqueue_script(
                'happyplace-listing-swipe',
                $theme_uri . '/assets/js/listing-swipe-card.js',
                ['jquery'],
                $this->get_file_version('/assets/js/listing-swipe-card.js'),
                true
            );
        }

        // Enqueue listing integration script
        wp_enqueue_script(
            'hph-listing-integration',
            $theme_uri . '/assets/js/listing-integration.js',
            ['jquery'],
            $theme_version,
            true
        );

        // Localize script with necessary data
        wp_localize_script(
            'hph-listing-integration',
            'happyplace_vars',
            [
                'rest_url' => esc_url_raw(rest_url()),
                'nonce'    => wp_create_nonce('wp_rest')
            ]
        );
    }

    /**
     * Enqueue template-specific assets based on current template
     * Priority: 15 - Load template assets after core assets
     */
    public function enqueue_template_assets(): void
    {
        $current_template = $this->get_current_template();

        if (!$current_template || !isset($this->template_assets[$current_template])) {
            return;
        }

        $assets = $this->template_assets[$current_template];
        $theme_uri = get_template_directory_uri();

        // Load API dependencies first
        if (!empty($assets['api_deps'])) {
            foreach ($assets['api_deps'] as $api_script) {
                if (wp_script_is($api_script, 'registered')) {
                    wp_enqueue_script($api_script);
                }
            }
        }

        // Load template-specific styles
        if (!empty($assets['styles'])) {
            foreach ($assets['styles'] as $style_handle) {
                $this->enqueue_conditional_style($style_handle);
            }
        }

        // Load template-specific scripts
        if (!empty($assets['scripts'])) {
            foreach ($assets['scripts'] as $script_handle) {
                $this->enqueue_conditional_script($script_handle);
            }
        }

        // Track loaded template
        $this->loaded_assets['template'] = $current_template;
    }

    /**
     * Enqueue template assets by template name (called by template loader)
     */
    public function enqueue_template_assets_by_name(string $template_name): void
    {
        if (!isset($this->template_assets[$template_name])) {
            return;
        }

        $assets = $this->template_assets[$template_name];
        $theme_uri = get_template_directory_uri();

        // Load API dependencies first
        if (!empty($assets['api_deps'])) {
            foreach ($assets['api_deps'] as $api_script) {
                if (wp_script_is($api_script, 'registered')) {
                    wp_enqueue_script($api_script);
                }
            }
        }

        // Load template-specific styles
        if (!empty($assets['styles'])) {
            foreach ($assets['styles'] as $style_handle) {
                $this->enqueue_conditional_style($style_handle);
            }
        }

        // Load template-specific scripts
        if (!empty($assets['scripts'])) {
            foreach ($assets['scripts'] as $script_handle) {
                $this->enqueue_conditional_script($script_handle);
            }
        }

        // Track loaded template
        $this->loaded_assets['template'] = $template_name;
    }

    /**
     * Enqueue assets for template parts (called manually when needed)
     */
    public function enqueue_template_part_assets(string $template_part): void
    {
        $template_key = "templates/template-parts/{$template_part}.php";

        if (isset($this->template_assets[$template_key])) {
            $this->enqueue_template_assets_by_name($template_key);
        }
    }

    /**
     * Enhanced template detection that works with custom template hierarchy
     */
    public function get_current_template(): ?string
    {
        global $template;

        if (!empty($template)) {
            $template_name = basename($template);
            $template_path = str_replace(get_template_directory() . '/', '', $template);

            // Check full path first (for dashboard templates)
            if (isset($this->template_assets[$template_path])) {
                return $template_path;
            }

            // Check if this template is in our asset configuration
            if (isset($this->template_assets[$template_name])) {
                return $template_name;
            }
        }

        // Check for page templates (like dashboard)
        if (is_page_template()) {
            $page_template = get_page_template_slug();
            if ($page_template && isset($this->template_assets[$page_template])) {
                return $page_template;
            }
        }

        // Check for dashboard via URL or query vars
        if (hph_is_dashboard()) {
            return 'templates/dashboard/agent-dashboard.php';
        }

        // Check for custom post type templates
        if (is_single() || is_archive()) {
            $post_type = get_post_type();
            if (is_single()) {
                $template_name = "single-{$post_type}.php";
            } else {
                $template_name = "archive-{$post_type}.php";
            }

            if (isset($this->template_assets[$template_name])) {
                return $template_name;
            }
        }

        return null;
    }

    /**
     * Enqueue conditional style if file exists
     */
    private function enqueue_conditional_style(string $handle): void
    {
        $theme_uri = get_template_directory_uri();
        $style_path = "/assets/css/{$handle}.css";
        $full_path = get_template_directory() . $style_path;

        if (file_exists($full_path)) {
            $dependencies = $this->get_style_dependencies($handle);

            wp_enqueue_style(
                "hph-{$handle}",
                $theme_uri . $style_path,
                $dependencies,
                $this->get_file_version($style_path)
            );

            $this->loaded_assets['styles'][] = $handle;
        }
    }

    /**
     * Enqueue conditional script if file exists
     */
    private function enqueue_conditional_script(string $handle): void
    {
        $theme_uri = get_template_directory_uri();
        $script_path = "/assets/js/{$handle}.js";
        $full_path = get_template_directory() . $script_path;

        if (file_exists($full_path)) {
            $dependencies = $this->get_script_dependencies($handle);

            wp_enqueue_script(
                "hph-{$handle}",
                $theme_uri . $script_path,
                $dependencies,
                $this->get_file_version($script_path),
                true
            );

            $this->loaded_assets['scripts'][] = $handle;
        }
    }

    /**
     * Get style dependencies based on handle
     */
    private function get_style_dependencies(string $handle): array
    {
        $dependencies_map = [
            // Dashboard styles
            'dashboard-core' => ['happyplace-core'],
            'dashboard-sections' => ['hph-dashboard-core'],
            'dashboard-responsive' => ['hph-dashboard-core'],
            'dashboard-modals' => ['hph-dashboard-core'],
            'dashboard-utilities' => ['hph-dashboard-core'],
            'dashboard-charts' => ['hph-dashboard-sections'],

            // Listing styles
            'archive-listing' => ['happyplace-core'],
            'single-listing' => ['happyplace-core'],
            'listing-filters' => ['hph-archive-listing'],
            'listing-filters-ajax' => ['hph-listing-filters'],
            'listing-gallery' => ['hph-single-listing'],
            'listing-map' => ['happyplace-core'],
            'listing-swipe-card' => ['happyplace-core'],
            'listing-list-card' => ['happyplace-core'],

            // Map styles
            'map-clusters' => ['hph-listing-map'],
            'map-info-window' => ['hph-listing-map'],

            // Agent styles
            'agent-profile' => ['happyplace-core'],
            'agent-styles' => ['happyplace-core'],

            // Community styles
            'community-profile' => ['happyplace-core'],

            // City styles
            'city-profile' => ['happyplace-core'],

            // Local place styles
            'local-place-profile' => ['happyplace-core']
        ];

        return $dependencies_map[$handle] ?? ['happyplace-core'];
    }

    /**
     * Get script dependencies based on handle
     */
    private function get_script_dependencies(string $handle): array
    {
        $dependencies_map = [
            // Dashboard scripts
            'dashboard' => ['jquery', 'happyplace-main'],
            'dashboard-forms' => ['jquery', 'hph-dashboard'],
            'dashboard-charts' => ['chart-js', 'hph-dashboard'],

            // Listing scripts
            'archive-listing' => ['jquery', 'happyplace-main'],
            'listing-filters' => ['jquery', 'happyplace-main'],
            'listing-filters-ajax' => ['jquery', 'hph-listing-filters'],
            'listing-map' => ['google-maps'],
            'listing-map-clusterer' => ['google-maps', 'markerclustererplus'],
            'listing-gallery' => ['jquery'],
            'listing-contact' => ['jquery'],
            'single-listing' => ['jquery', 'happyplace-main'],

            // Agent scripts
            'agent-contact' => ['jquery'],
            'agent-filters' => ['jquery', 'happyplace-main'],
            'agent-archive' => ['jquery'],

            // Map scripts
            'google-places-autocomplete' => ['google-maps'],

            // Utility scripts
            'filter-sidebar' => ['jquery'],
            'mortgage-calculator' => ['jquery'],
            'listing-swipe-card' => ['jquery'],
            'price-range' => ['jquery']
        ];

        return $dependencies_map[$handle] ?? ['jquery'];
    }

    /**
     * Localize scripts with necessary data
     * Priority: 20 - Localize after all scripts are loaded
     */
    public function localize_scripts(): void
    {
        // Main AJAX configuration
        if (wp_script_is('happyplace-main', 'enqueued')) {
            wp_localize_script('happyplace-main', 'hphAjax', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_nonce'),
                'searchNonce' => wp_create_nonce('hph_search_nonce'),
                'debug' => WP_DEBUG,
                'restUrl' => rest_url(),
                'restNonce' => wp_create_nonce('wp_rest')
            ]);
        }

        // Dashboard-specific configuration
        if (wp_script_is('hph-dashboard', 'enqueued')) {
            wp_localize_script('hph-dashboard', 'hphDashboard', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_dashboard_nonce'),
                'currentSection' => $this->get_current_dashboard_section(),
                'userCan' => [
                    'edit_listings' => current_user_can('edit_posts'),
                    'manage_leads' => current_user_can('agent') || current_user_can('administrator'),
                    'view_stats' => current_user_can('agent') || current_user_can('administrator')
                ]
            ]);
        }

        // Maps configuration (if Google Maps is loaded)
        if (wp_script_is('google-maps', 'enqueued')) {
            wp_localize_script('google-maps', 'hphMaps', [
                'defaultCenter' => [
                    'lat' => floatval(get_theme_mod('default_map_lat', 40.7128)),
                    'lng' => floatval(get_theme_mod('default_map_lng', -74.0060))
                ],
                'defaultZoom' => intval(get_theme_mod('default_map_zoom', 12)),
                'mapStyle' => get_theme_mod('map_style', 'roadmap')
            ]);
        }
    }

    /**
     * Enqueue admin-specific assets
     */
    public function enqueue_admin_assets($hook): void
    {
        // Only load on relevant admin pages
        if (!in_array($hook, ['post.php', 'post-new.php', 'edit.php', 'settings_page_theme-settings'])) {
            return;
        }

        $theme_uri = get_template_directory_uri();

        // Admin styles
        if (file_exists(get_template_directory() . '/assets/css/admin.css')) {
            wp_enqueue_style(
                'hph-admin',
                $theme_uri . '/assets/css/admin.css',
                [],
                $this->get_file_version('/assets/css/admin.css')
            );
        }

        // Admin scripts
        if (file_exists(get_template_directory() . '/assets/js/admin.js')) {
            wp_enqueue_script(
                'hph-admin',
                $theme_uri . '/assets/js/admin.js',
                ['jquery'],
                $this->get_file_version('/assets/js/admin.js'),
                true
            );
        }
    }

    /**
     * Optimize script loading with async/defer
     */
    public function optimize_script_loading(string $tag, string $handle): string
    {
        $async_scripts = ['google-maps', 'chart-js'];
        $defer_scripts = ['markerclustererplus'];

        if (in_array($handle, $async_scripts)) {
            $tag = str_replace('<script ', '<script async ', $tag);
        }

        if (in_array($handle, $defer_scripts)) {
            $tag = str_replace('<script ', '<script defer ', $tag);
        }

        return $tag;
    }

    /**
     * Optimize style loading
     */
    public function optimize_style_loading(string $tag, string $handle): string
    {
        // Add preload for critical styles
        $critical_styles = ['font-awesome', 'happyplace-main'];

        if (in_array($handle, $critical_styles)) {
            $tag = str_replace("rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag);
        }

        return $tag;
    }

    /**
     * Get current dashboard section
     */
    private function get_current_dashboard_section(): string
    {
        return sanitize_key($_GET['section'] ?? 'overview');
    }

    /**
     * Get file modification time for cache busting
     */
    private function get_file_version(string $relative_path): string
    {
        $full_path = get_template_directory() . $relative_path;
        return file_exists($full_path) ? filemtime($full_path) : wp_get_theme()->get('Version');
    }

    /**
     * Debug loaded assets (WP_DEBUG only)
     */
    public function debug_loaded_assets(): void
    {
        if (!WP_DEBUG || !current_user_can('administrator')) {
            return;
        }

        $missing_assets = $this->validate_assets();

        echo "\n<!-- HPH Assets Debug -->\n";
        echo "<!-- Template: " . ($this->loaded_assets['template'] ?? 'Unknown') . " -->\n";
        echo "<!-- Styles: " . implode(', ', $this->loaded_assets['styles'] ?? []) . " -->\n";
        echo "<!-- Scripts: " . implode(', ', $this->loaded_assets['scripts'] ?? []) . " -->\n";

        if (!empty($missing_assets['styles']) || !empty($missing_assets['scripts'])) {
            echo "<!-- MISSING ASSETS WARNING -->\n";
            if (!empty($missing_assets['styles'])) {
                echo "<!-- Missing CSS: " . implode(', ', $missing_assets['styles']) . " -->\n";
            }
            if (!empty($missing_assets['scripts'])) {
                echo "<!-- Missing JS: " . implode(', ', $missing_assets['scripts']) . " -->\n";
            }
        }

        echo "<!-- End HPH Assets Debug -->\n\n";
    }

    /**
     * Validate that all referenced asset files exist
     * For debugging purposes
     */
    public function validate_assets(): array
    {
        $missing_assets = [
            'styles' => [],
            'scripts' => []
        ];

        foreach ($this->template_assets as $template => $assets) {
            // Check styles
            if (!empty($assets['styles'])) {
                foreach ($assets['styles'] as $style_handle) {
                    $style_path = get_template_directory() . "/assets/css/{$style_handle}.css";
                    if (!file_exists($style_path)) {
                        $missing_assets['styles'][] = "{$template}: {$style_handle}.css";
                    }
                }
            }

            // Check scripts
            if (!empty($assets['scripts'])) {
                foreach ($assets['scripts'] as $script_handle) {
                    $script_path = get_template_directory() . "/assets/js/{$script_handle}.js";
                    if (!file_exists($script_path)) {
                        $missing_assets['scripts'][] = "{$template}: {$script_handle}.js";
                    }
                }
            }
        }

        return $missing_assets;
    }
}

// Initialize the assets manager
HPH_Assets_Manager::instance();
