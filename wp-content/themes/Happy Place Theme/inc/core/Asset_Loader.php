<?php

namespace HappyPlace\Core;

/**
 * Asset Loader - Enhanced Version
 * 
 * Handles asset loading with performance optimization and caching
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Asset_Loader {

    public function enqueue_template_assets_by_name(string $template_name): void {
    $template_type = $this->get_template_type_from_name($template_name);
    if ($template_type) {
        $this->enqueue_template_assets($template_type);
    }
}

private function get_template_type_from_name(string $template_name): ?string {
    $mappings = [
        'single-listing.php' => 'listing',
        'archive-listing.php' => 'listing', 
        'single-agent.php' => 'agent',
        'archive-agent.php' => 'agent',
        'agent-dashboard.php' => 'dashboard',
        // Add more mappings as needed
    ];
    
    return $mappings[$template_name] ?? null;
}
    
    private static ?self $instance = null;
    private array $loaded_assets = [];
    private array $asset_dependencies = [];
    
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_core_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue core frontend assets
     */
    public function enqueue_core_assets(): void {
        $theme_version = wp_get_theme()->get('Version');
        
        // FontAwesome Icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            [],
            '6.4.0'
        );
        
        // Use webpack assets
        $manifest = $this->get_webpack_manifest();
        
        // Core CSS from webpack
        if (isset($manifest['main.css'])) {
            wp_enqueue_style(
                'hph-core-styles',
                get_template_directory_uri() . '/assets/dist/' . $manifest['main.css'],
                ['font-awesome'],
                null // Use webpack hash for versioning
            );
        }
        
        // Core JavaScript from webpack
        if (isset($manifest['main.js'])) {
            wp_enqueue_script(
                'hph-core-scripts',
                get_template_directory_uri() . '/assets/dist/' . $manifest['main.js'],
                ['jquery'],
                null, // Use webpack hash for versioning
                true
            );
        }
        
        // Components bundle
        if (isset($manifest['components.js'])) {
            wp_enqueue_script(
                'hph-components',
                get_template_directory_uri() . '/assets/dist/' . $manifest['components.js'],
                ['hph-core-scripts'],
                null,
                true
            );
        }
        
        // Localize script data
        $this->localize_core_scripts();
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void {
        $manifest = $this->get_webpack_manifest();
        
        // Admin assets from webpack
        if (isset($manifest['admin.js'])) {
            wp_enqueue_script(
                'hph-admin-scripts',
                get_template_directory_uri() . '/assets/dist/' . $manifest['admin.js'],
                ['jquery'],
                null,
                true
            );
        }
    }

    /**
     * Get webpack manifest for asset mapping
     */
    private function get_webpack_manifest(): array {
        static $manifest = null;
        
        if ($manifest === null) {
            $manifest_path = get_template_directory() . '/manifest.json';
            
            if (file_exists($manifest_path)) {
                $manifest_content = file_get_contents($manifest_path);
                $manifest = json_decode($manifest_content, true) ?: [];
            } else {
                $manifest = [];
                error_log('Webpack manifest not found at: ' . $manifest_path);
            }
        }
        
        return $manifest;
    }

    /**
     * Enqueue template-specific assets
     */
    public function enqueue_template_assets(string $template_type): void {
        if (in_array($template_type, $this->loaded_assets)) {
            return; // Already loaded
        }

        $theme_version = wp_get_theme()->get('Version');
        
        switch ($template_type) {
            case 'listing':
                $this->enqueue_listing_assets($theme_version);
                break;
            case 'agent':
                $this->enqueue_agent_assets($theme_version);
                break;
            case 'dashboard':
                $this->enqueue_dashboard_assets($theme_version);
                break;
            case 'map':
                $this->enqueue_map_assets($theme_version);
                break;
        }
        
        $this->loaded_assets[] = $template_type;
    }

    /**
     * Enqueue listing-specific assets
     */
    private function enqueue_listing_assets(string $version): void {
        $manifest = $this->get_webpack_manifest();
        
        // Listing-specific JavaScript from webpack
        if (isset($manifest['single-listing.js'])) {
            wp_enqueue_script(
                'hph-listing-scripts',
                get_template_directory_uri() . '/assets/dist/' . $manifest['single-listing.js'],
                ['hph-core-scripts'],
                null,
                true
            );
        }
        
        // Photo gallery assets
        wp_enqueue_style('lightbox');
        wp_enqueue_script('lightbox');
    }

    /**
     * Enqueue agent-specific assets
     */
    private function enqueue_agent_assets(string $version): void {
        // Agent assets will be handled by main CSS bundle for now
        // Individual agent JS can be added to webpack config if needed
    }

    /**
     * Enqueue dashboard assets
     */
    private function enqueue_dashboard_assets(string $version): void {
        $manifest = $this->get_webpack_manifest();
        
        // Dashboard JavaScript from webpack
        if (isset($manifest['dashboard.js'])) {
            wp_enqueue_script(
                'hph-dashboard-scripts',
                get_template_directory_uri() . '/assets/dist/' . $manifest['dashboard.js'],
                ['hph-core-scripts', 'jquery-ui-sortable'],
                null,
                true
            );
        }
        
        // Chart.js for analytics
        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '3.9.1',
            true
        );
    }

    /**
     * Enqueue map assets
     */
    private function enqueue_map_assets(string $version): void {
        $manifest = $this->get_webpack_manifest();
        $google_maps_api_key = get_option('hph_google_maps_api_key', '');
        
        if ($google_maps_api_key) {
            wp_enqueue_script(
                'google-maps-api',
                "https://maps.googleapis.com/maps/api/js?key={$google_maps_api_key}&libraries=places",
                [],
                null,
                true
            );
        }
        
        // Map JavaScript from webpack
        if (isset($manifest['maps.js'])) {
            wp_enqueue_script(
                'hph-map-scripts',
                get_template_directory_uri() . '/assets/dist/' . $manifest['maps.js'],
                ['google-maps-api'],
                null,
                true
            );
        }
    }

    /**
     * Localize core script data
     */
    private function localize_core_scripts(): void {
        wp_localize_script('hph-core-scripts', 'hphAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_ajax_nonce'),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred. Please try again.', 'happy-place'),
                'success' => __('Success!', 'happy-place'),
                'favoriteAdded' => __('Property added to favorites', 'happy-place'),
                'favoriteRemoved' => __('Property removed from favorites', 'happy-place'),
            ]
        ]);
        
        // Hero-specific localization
        wp_localize_script('hph-core-scripts', 'hphHero', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_hero_nonce'),
            'strings' => [
                'favoriteAdded' => __('Added to favorites', 'happy-place'),
                'favoriteRemoved' => __('Removed from favorites', 'happy-place'),
                'tourScheduled' => __('Tour request sent', 'happy-place'),
                'galleryOpened' => __('Opening gallery...', 'happy-place'),
            ]
        ]);
    }

    /**
     * Register asset dependency
     */
    public function register_dependency(string $asset, array $dependencies): void {
        $this->asset_dependencies[$asset] = $dependencies;
    }

    /**
     * Load conditional assets based on page context
     */
    public function load_conditional_assets(): void {
        global $post;
        
        // Load listing assets on listing pages
        if (is_singular('listing') || is_post_type_archive('listing')) {
            $this->enqueue_template_assets('listing');
        }
        
        // Load agent assets on agent pages
        if (is_singular('agent') || is_post_type_archive('agent')) {
            $this->enqueue_template_assets('agent');
        }
        
        // Load dashboard assets on dashboard pages
        if ($this->is_dashboard_page()) {
            $this->enqueue_template_assets('dashboard');
        }
        
        // Load map assets when map is present
        if ($this->page_has_map()) {
            $this->enqueue_template_assets('map');
        }
    }

    /**
     * Check if current page is dashboard
     */
    private function is_dashboard_page(): bool {
        return is_page() && get_page_template_slug() === 'agent-dashboard.php';
    }

    /**
     * Check if page has map component
     */
    private function page_has_map(): bool {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if map shortcode or block is present
        return has_shortcode($post->post_content, 'hph_map') ||
               strpos($post->post_content, 'wp:happy-place/map') !== false ||
               is_post_type_archive('listing'); // Listing archive always has map
    }

    /**
     * Optimize asset loading with critical CSS inlining
     */
    public function inline_critical_css(): void {
        if (!is_front_page()) {
            return;
        }
        
        $critical_css_file = get_template_directory() . '/assets/css/critical.css';
        if (file_exists($critical_css_file)) {
            echo '<style id="hph-critical-css">';
            echo file_get_contents($critical_css_file);
            echo '</style>';
        }
    }

    /**
     * Preload important assets
     */
    public function preload_assets(): void {
        // Preload critical fonts
        echo '<link rel="preload" href="' . get_template_directory_uri() . '/assets/fonts/main.woff2" as="font" type="font/woff2" crossorigin>';
        
        // Preload hero images on specific pages
        if (is_front_page()) {
            $hero_image = get_theme_mod('hero_background_image');
            if ($hero_image) {
                echo '<link rel="preload" href="' . esc_url($hero_image) . '" as="image">';
            }
        }
    }

    /**
     * Add resource hints
     */
    public function add_resource_hints(): void {
        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        echo '<link rel="dns-prefetch" href="//maps.googleapis.com">';
        
        // Preconnect to CDNs
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    }
}

// Initialize on wp_head
add_action('wp_head', function() {
    $asset_loader = Asset_Loader::instance();
    $asset_loader->inline_critical_css();
    $asset_loader->preload_assets();
    $asset_loader->add_resource_hints();
}, 1);

// Load conditional assets
add_action('wp_enqueue_scripts', function() {
    Asset_Loader::instance()->load_conditional_assets();
}, 20);

// Maintain backward compatibility
if (!class_exists('HPH_Asset_Loader')) {
    class HPH_Asset_Loader {
        public static function enqueue_template_assets(string $template_type): void {
            Asset_Loader::instance()->enqueue_template_assets($template_type);
        }
    }
}
