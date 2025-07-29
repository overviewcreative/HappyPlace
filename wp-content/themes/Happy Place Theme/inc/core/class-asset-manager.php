<?php
/**
 * Asset Manager - Single Source of Truth for All Asset Loading
 * 
 * This class replaces ALL existing asset loading systems and provides:
 * - Single CSS file loading (main.css) with webpack hash-based caching
 * - Single JS file loading (main.js) with proper dependencies
 * - Conditional template-specific assets when needed
 * - Admin and login asset support
 * - Emergency fallbacks when webpack compilation fails
 * - Clean integration with WordPress enqueue system
 * 
 * @package HappyPlace
 * @subpackage Core
 * @since 2.0.0
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Asset_Manager {
    
    /**
     * Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * Webpack manifest array
     */
    private array $manifest = [];
    
    /**
     * Assets directory URI
     */
    private string $assets_uri;
    
    /**
     * Assets directory path
     */
    private string $assets_dir;
    
    /**
     * Theme version for fallback cache busting
     */
    private string $theme_version;
    
    /**
     * Initialize singleton instance
     */
    public static function init(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor - enforces singleton pattern
     */
    private function __construct() {
        $this->assets_uri = get_template_directory_uri() . '/assets/dist';
        $this->assets_dir = get_template_directory() . '/assets/dist';
        $this->theme_version = wp_get_theme()->get('Version') ?: '1.0.0';
        
        $this->load_manifest();
        $this->register_hooks();
        $this->cleanup_legacy_assets();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 10);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10);
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_assets'], 10);
        
        // Optimize loading
        add_filter('style_loader_tag', [$this, 'add_critical_css_attributes'], 10, 4);
        add_filter('script_loader_tag', [$this, 'add_script_attributes'], 10, 3);
    }
    
    /**
     * Load webpack manifest file
     */
    private function load_manifest(): void {
        $manifest_path = $this->assets_dir . '/manifest.json';
        
        if (file_exists($manifest_path)) {
            $manifest_content = file_get_contents($manifest_path);
            $this->manifest = json_decode($manifest_content, true) ?: [];
        }
        
        // Log warning if manifest is missing in development
        if (empty($this->manifest) && (defined('WP_DEBUG') && WP_DEBUG)) {
            error_log('Happy Place Theme: webpack manifest.json not found. Run "npm run build" to compile assets.');
        }
    }
    
    /**
     * Clean up legacy asset loading systems
     */
    private function cleanup_legacy_assets(): void {
        // Remove old asset loading functions that might conflict
        remove_action('wp_enqueue_scripts', 'happy_place_enqueue_assets', 10);
        remove_action('wp_enqueue_scripts', 'hph_enqueue_assets', 10);
        remove_action('wp_enqueue_scripts', 'hph_bridge_enqueue_template_assets', 10);
        
        // Remove style.css enqueuing (we use webpack-compiled CSS only)
        add_action('wp_enqueue_scripts', function() {
            wp_dequeue_style('happy-place-style');
            wp_deregister_style('happy-place-style');
        }, 5);
    }
    
    /**
     * Enqueue frontend assets - SINGLE SYSTEM FOR ALL FRONTEND ASSETS
     */
    public function enqueue_frontend_assets(): void {
        // 1. Main CSS - Contains ALL theme styling
        $this->enqueue_asset([
            'key' => 'main.css',
            'handle' => 'hph-main-styles',
            'type' => 'style',
            'deps' => [],
            'fallback' => 'css/main.css'
        ]);
        
        // 2. Main JavaScript - Contains ALL theme functionality
        $this->enqueue_asset([
            'key' => 'main.js',
            'handle' => 'hph-main-scripts',
            'type' => 'script',
            'deps' => ['jquery'],
            'in_footer' => true,
            'fallback' => 'js/main.js'
        ]);
        
        // 3. Runtime chunk (if webpack code splitting is used)
        if (isset($this->manifest['runtime.js'])) {
            $this->enqueue_asset([
                'key' => 'runtime.js',
                'handle' => 'hph-runtime',
                'type' => 'script',
                'deps' => [],
                'in_footer' => true
            ]);
        }
        
        // 4. Vendor chunk (if webpack code splitting is used)
        if (isset($this->manifest['vendor.js'])) {
            $this->enqueue_asset([
                'key' => 'vendor.js',
                'handle' => 'hph-vendor',
                'type' => 'script',
                'deps' => ['hph-runtime'],
                'in_footer' => true
            ]);
        }
        
        // 5. Template-specific assets (conditional loading)
        $this->enqueue_template_specific_assets();
        
        // 6. Localize script data for JavaScript
        $this->localize_frontend_scripts();
    }
    
    /**
     * Enqueue admin-specific assets
     */
    public function enqueue_admin_assets(): void {
        // Admin CSS (if exists)
        $this->enqueue_asset([
            'key' => 'admin.css',
            'handle' => 'hph-admin-styles',
            'type' => 'style',
            'deps' => [],
            'fallback' => 'css/admin.css'
        ]);
        
        // Admin JavaScript (if exists)
        $this->enqueue_asset([
            'key' => 'admin.js',
            'handle' => 'hph-admin-scripts',
            'type' => 'script',
            'deps' => ['jquery'],
            'in_footer' => true,
            'fallback' => 'js/admin.js'
        ]);
        
        // Dashboard-specific assets (only on dashboard pages)
        if ($this->is_dashboard_page()) {
            $this->enqueue_asset([
                'key' => 'dashboard.css',
                'handle' => 'hph-dashboard-styles',
                'type' => 'style',
                'deps' => ['hph-admin-styles']
            ]);
            
            $this->enqueue_asset([
                'key' => 'dashboard.js',
                'handle' => 'hph-dashboard-scripts',
                'type' => 'script',
                'deps' => ['hph-admin-scripts'],
                'in_footer' => true
            ]);
        }
    }
    
    /**
     * Enqueue login page assets
     */
    public function enqueue_login_assets(): void {
        // Use main styles for login page (or login-specific if exists)
        $this->enqueue_asset([
            'key' => 'login.css',
            'handle' => 'hph-login-styles',
            'type' => 'style',
            'deps' => [],
            'fallback' => 'main.css' // Fallback to main styles
        ]);
    }
    
    /**
     * Enqueue template-specific assets only when needed
     */
    private function enqueue_template_specific_assets(): void {
        $template_conditions = [
            // Listing pages
            'is_singular("listing")' => 'single-listing',
            'is_post_type_archive("listing")' => 'listing-archive',
            
            // Agent pages
            'is_singular("agent")' => 'agent-profile',
            'is_post_type_archive("agent")' => 'agent-archive',
            
            // Dashboard pages
            'is_page_template("page-dashboard.php")' => 'dashboard',
            'is_page_template("agent-dashboard.php")' => 'dashboard',
            
            // Search pages
            'is_search()' => 'search',
            
            // Home page
            'is_front_page()' => 'home'
        ];
        
        foreach ($template_conditions as $condition => $asset_prefix) {
            if ($this->evaluate_condition($condition)) {
                // Load template-specific CSS
                $this->enqueue_asset([
                    'key' => $asset_prefix . '.css',
                    'handle' => "hph-template-{$asset_prefix}",
                    'type' => 'style',
                    'deps' => ['hph-main-styles']
                ]);
                
                // Load template-specific JS
                $this->enqueue_asset([
                    'key' => $asset_prefix . '.js',
                    'handle' => "hph-template-{$asset_prefix}",
                    'type' => 'script',
                    'deps' => ['hph-main-scripts'],
                    'in_footer' => true
                ]);
                
                break; // Only load one template asset set
            }
        }
    }
    
    /**
     * Safely evaluate WordPress conditional functions
     */
    private function evaluate_condition(string $condition): bool {
        try {
            return eval("return $condition;");
        } catch (ParseError $e) {
            error_log("Happy Place Theme: Invalid condition '$condition' - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enqueue a single asset with comprehensive error handling
     */
    private function enqueue_asset(array $args): void {
        $defaults = [
            'key' => '',
            'handle' => '',
            'type' => 'style', // 'style' or 'script'
            'deps' => [],
            'in_footer' => false,
            'fallback' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Get asset URL
        $asset_url = $this->get_asset_url($args['key'], $args['fallback']);
        
        if (!$asset_url) {
            return; // Asset doesn't exist
        }
        
        // Enqueue based on type
        if ($args['type'] === 'style') {
            wp_enqueue_style(
                $args['handle'],
                $asset_url,
                $args['deps'],
                null // Version handled by webpack hash
            );
        } elseif ($args['type'] === 'script') {
            wp_enqueue_script(
                $args['handle'],
                $asset_url,
                $args['deps'],
                null, // Version handled by webpack hash
                $args['in_footer']
            );
        }
    }
    
    /**
     * Get asset URL with fallback support
     */
    private function get_asset_url(string $key, ?string $fallback = null): ?string {
        // Try webpack manifest first
        if (isset($this->manifest[$key])) {
            return $this->assets_uri . '/' . $this->manifest[$key];
        }
        
        // Try fallback path
        if ($fallback) {
            $fallback_path = $this->assets_dir . '/' . $fallback;
            if (file_exists($fallback_path)) {
                return $this->assets_uri . '/' . $fallback . '?v=' . $this->theme_version;
            }
        }
        
        // Try direct path without hash
        $direct_path = $this->assets_dir . '/' . $key;
        if (file_exists($direct_path)) {
            return $this->assets_uri . '/' . $key . '?v=' . $this->theme_version;
        }
        
        return null;
    }
    
    /**
     * Localize script data for frontend JavaScript
     */
    private function localize_frontend_scripts(): void {
        wp_localize_script('hph-main-scripts', 'hphTheme', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_nonce'),
            'assetsUrl' => $this->assets_uri,
            'themeUrl' => get_template_directory_uri(),
            'isLoggedIn' => is_user_logged_in(),
            'currentUserId' => get_current_user_id(),
            'currentPostId' => get_the_ID(),
            'isAdmin' => current_user_can('manage_options'),
            'breakpoints' => [
                'sm' => 640,
                'md' => 768,
                'lg' => 1024,
                'xl' => 1280,
                '2xl' => 1536
            ],
            'debug' => (defined('WP_DEBUG') && WP_DEBUG)
        ]);
    }
    
    /**
     * Check if current page is a dashboard page
     */
    private function is_dashboard_page(): bool {
        global $pagenow;
        
        // Check for plugin admin pages
        if (isset($_GET['page']) && strpos($_GET['page'], 'happy-place') === 0) {
            return true;
        }
        
        // Check for theme dashboard pages
        return is_page_template('page-dashboard.php') || 
               is_page_template('agent-dashboard.php');
    }
    
    /**
     * Add critical CSS attributes for performance
     */
    public function add_critical_css_attributes(string $html, string $handle, string $href, string $media): string {
        // Mark main styles as critical
        if ($handle === 'hph-main-styles') {
            $html = str_replace("media='all'", "media='all' data-critical='true'", $html);
        }
        
        return $html;
    }
    
    /**
     * Add script attributes for performance
     */
    public function add_script_attributes(string $tag, string $handle, string $src): string {
        // Add async/defer attributes to specific scripts
        $async_scripts = ['hph-vendor', 'hph-components'];
        $defer_scripts = ['hph-main-scripts'];
        
        if (in_array($handle, $async_scripts, true)) {
            $tag = str_replace(' src=', ' async src=', $tag);
        } elseif (in_array($handle, $defer_scripts, true)) {
            $tag = str_replace(' src=', ' defer src=', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Public method to get asset URL (for use in templates)
     */
    public function asset(string $key): string {
        return $this->get_asset_url($key) ?: '';
    }
    
    /**
     * Check if an asset exists
     */
    public function has_asset(string $key): bool {
        return $this->get_asset_url($key) !== null;
    }
    
    /**
     * Get all loaded assets (for debugging)
     */
    public function get_loaded_assets(): array {
        return [
            'manifest' => $this->manifest,
            'assets_dir' => $this->assets_dir,
            'assets_uri' => $this->assets_uri,
            'theme_version' => $this->theme_version
        ];
    }
}