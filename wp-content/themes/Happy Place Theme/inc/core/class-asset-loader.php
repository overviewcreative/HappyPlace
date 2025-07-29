<?php
/**
 * Asset Loader - Single Source of Truth
 * Replaces ALL existing asset loading systems
 * 
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Asset_Loader {
    private static ?self $instance = null;
    private array $manifest = [];
    private string $assets_uri;
    private string $assets_dir;
    
    public static function init(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        $this->assets_uri = get_template_directory_uri() . '/assets/dist';
        $this->assets_dir = get_template_directory() . '/assets/dist';
        $this->load_manifest();
        
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_assets']);
    }
    
    /**
     * Load webpack manifest
     */
    private function load_manifest(): void {
        $manifest_path = $this->assets_dir . '/manifest.json';
        
        if (file_exists($manifest_path)) {
            $this->manifest = json_decode(file_get_contents($manifest_path), true) ?: [];
        }
    }
    
    /**
     * Enqueue frontend assets - SINGLE SYSTEM
     */
    public function enqueue_frontend_assets(): void {
        // 1. Core CSS (contains everything)
        $this->enqueue_asset('main.css', 'hph-styles', 'style');
        
        // 2. Core JavaScript
        $this->enqueue_asset('main.js', 'hph-scripts', 'script', ['jquery']);
        
        // 3. Components JavaScript (if separate bundle)
        if (isset($this->manifest['components.js'])) {
            $this->enqueue_asset('components.js', 'hph-components', 'script', ['hph-scripts']);
        }
        
        // 4. Template-specific assets (conditional)
        $this->enqueue_template_assets();
        
        // 5. Localize script data
        $this->localize_scripts();
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void {
        // Admin-specific styles and scripts
        if (isset($this->manifest['admin.css'])) {
            $this->enqueue_asset('admin.css', 'hph-admin-styles', 'style');
        }
        
        if (isset($this->manifest['admin.js'])) {
            $this->enqueue_asset('admin.js', 'hph-admin-scripts', 'script', ['jquery']);
        }
    }
    
    /**
     * Enqueue login assets
     */
    public function enqueue_login_assets(): void {
        // Login page specific assets
        if (isset($this->manifest['login.css'])) {
            $this->enqueue_asset('login.css', 'hph-login-styles', 'style');
        } else {
            // Fallback to main styles for login
            $this->enqueue_asset('main.css', 'hph-login-styles', 'style');
        }
    }
    
    /**
     * Enqueue a single asset with proper versioning
     */
    private function enqueue_asset(string $key, string $handle, string $type, array $deps = []): void {
        if (!isset($this->manifest[$key])) {
            // Try direct file if manifest doesn't exist
            $direct_file = $this->get_direct_asset_path($key);
            if ($direct_file) {
                $url = $this->assets_uri . '/' . $direct_file;
            } else {
                return;
            }
        } else {
            $url = $this->assets_uri . '/' . $this->manifest[$key];
        }
        
        if ($type === 'style') {
            wp_enqueue_style($handle, $url, $deps, null);
        } elseif ($type === 'script') {
            wp_enqueue_script($handle, $url, $deps, null, true);
        }
    }
    
    /**
     * Get direct asset path when manifest is not available
     */
    private function get_direct_asset_path(string $key): ?string {
        $direct_paths = [
            'main.css' => 'css/main.css',
            'main.js' => 'js/main.js',
            'single-listing.css' => 'css/single-listing.css',
            'single-listing.js' => 'js/single-listing.js',
            'admin.css' => 'css/admin.css',
            'admin.js' => 'js/admin.js'
        ];
        
        if (isset($direct_paths[$key])) {
            $file_path = $this->assets_dir . '/' . $direct_paths[$key];
            if (file_exists($file_path)) {
                return $direct_paths[$key];
            }
        }
        
        return null;
    }
    
    /**
     * Template-specific assets (only when needed)
     */
    private function enqueue_template_assets(): void {
        $template_assets = [
            'is_singular("listing")' => 'single-listing',
            'is_post_type_archive("listing")' => 'listing-archive',
            'is_singular("agent")' => 'agent-profile',
            'is_page_template("agent-dashboard.php")' => 'dashboard'
        ];
        
        foreach ($template_assets as $condition => $asset_key) {
            if (eval("return $condition;")) {
                $this->enqueue_asset($asset_key . '.css', "hph-template-{$asset_key}", 'style', ['hph-styles']);
                $this->enqueue_asset($asset_key . '.js', "hph-template-{$asset_key}", 'script', ['hph-scripts']);
                break; // Only load one template asset set
            }
        }
    }
    
    /**
     * Localize script data
     */
    private function localize_scripts(): void {
        wp_localize_script('hph-scripts', 'hphAssets', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_nonce'),
            'assetsUrl' => $this->assets_uri,
            'isLoggedIn' => is_user_logged_in(),
            'currentUser' => get_current_user_id(),
            'themePath' => get_template_directory_uri()
        ]);
    }
    
    /**
     * Get asset URL
     */
    public function get_asset_url(string $key): string {
        if (isset($this->manifest[$key])) {
            return $this->assets_uri . '/' . $this->manifest[$key];
        }
        
        // Try direct path
        $direct_path = $this->get_direct_asset_path($key);
        if ($direct_path) {
            return $this->assets_uri . '/' . $direct_path;
        }
        
        return '';
    }
    
    /**
     * Check if asset exists
     */
    public function asset_exists(string $key): bool {
        return isset($this->manifest[$key]) || $this->get_direct_asset_path($key) !== null;
    }
}