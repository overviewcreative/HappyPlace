<?php
/**
 * Assets Manager Class (Optimized)
 *
 * Handles webpack-built assets and plugin-specific resource loading
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Assets_Manager_Optimized
{
    private static ?self $instance = null;
    private array $manifest = [];
    private bool $manifest_loaded = false;

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Initialize assets
     */
    private function __construct() {
        add_action('init', [$this, 'load_manifest'], 5);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Load webpack manifest for asset versioning
     */
    public function load_manifest(): void {
        $manifest_path = HPH_PATH . 'dist/manifest.json';
        
        if (file_exists($manifest_path)) {
            $manifest_content = file_get_contents($manifest_path);
            $this->manifest = json_decode($manifest_content, true) ?? [];
            $this->manifest_loaded = true;
        }
    }

    /**
     * Get asset URL with proper versioning
     */
    private function get_asset_url(string $asset_key): string {
        if ($this->manifest_loaded && isset($this->manifest[$asset_key])) {
            return HPH_URL . 'dist/' . ltrim($this->manifest[$asset_key], '/');
        }
        
        // Fallback for development
        return HPH_URL . 'dist/' . $asset_key;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook_suffix): void {
        $screen = get_current_screen();
        
        // Load on all admin pages, but conditionally
        if ($this->should_load_admin_assets($screen, $hook_suffix)) {
            $this->enqueue_core_admin_assets();
        }
        
        // Load specific assets for plugin pages
        if ($this->is_plugin_admin_page($screen, $hook_suffix)) {
            $this->enqueue_plugin_admin_assets();
        }
    }

    /**
     * Check if admin assets should be loaded
     */
    private function should_load_admin_assets($screen, string $hook_suffix): bool {
        if (!$screen) {
            return false;
        }
        
        // Load on plugin admin pages
        if (strpos($screen->id, 'happy-place') !== false) {
            return true;
        }
        
        // Load on post edit pages for our post types
        if (in_array($screen->post_type ?? '', ['listing', 'agent', 'office', 'community'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Check if this is a plugin admin page
     */
    private function is_plugin_admin_page($screen, string $hook_suffix): bool {
        return $screen && strpos($screen->id, 'happy-place') !== false;
    }

    /**
     * Enqueue core admin assets (minimal)
     */
    private function enqueue_core_admin_assets(): void {
        // WordPress dependencies
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Our core admin bundle
        wp_enqueue_style(
            'hph-admin-core',
            $this->get_asset_url('css/admin.css'),
            ['wp-color-picker'],
            HPH_VERSION
        );
        
        wp_enqueue_script(
            'hph-admin-core',
            $this->get_asset_url('js/admin.js'),
            ['jquery', 'wp-color-picker'],
            HPH_VERSION,
            true
        );
    }

    /**
     * Enqueue plugin-specific admin assets
     */
    private function enqueue_plugin_admin_assets(): void {
        // Dashboard assets
        wp_enqueue_script(
            'hph-dashboard',
            $this->get_asset_url('js/dashboard.js'),
            ['hph-admin-core'],
            HPH_VERSION,
            true
        );
        
        // Marketing suite
        wp_enqueue_script(
            'hph-marketing-suite',
            $this->get_asset_url('js/marketing-suite.js'),
            ['hph-admin-core'],
            HPH_VERSION,
            true
        );
        
        // Field calculations
        wp_enqueue_script(
            'hph-field-calculations',
            $this->get_asset_url('js/field-calculations.js'),
            ['hph-admin-core'],
            HPH_VERSION,
            true
        );
        
        // Integrations
        wp_enqueue_script(
            'hph-integrations',
            $this->get_asset_url('js/integrations.js'),
            ['hph-admin-core'],
            HPH_VERSION,
            true
        );
        
        // Localize scripts
        $this->localize_admin_scripts();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void {
        // Only load if needed (on plugin-related pages)
        if (!$this->should_load_frontend_assets()) {
            return;
        }
        
        // Frontend CSS (if any)
        if ($this->manifest_loaded && isset($this->manifest['css/frontend.css'])) {
            wp_enqueue_style(
                'hph-frontend',
                $this->get_asset_url('css/frontend.css'),
                [],
                HPH_VERSION
            );
        }
    }

    /**
     * Check if frontend assets should be loaded
     */
    private function should_load_frontend_assets(): bool {
        global $post;
        
        // Load on single listing/agent pages
        if (is_singular(['listing', 'agent', 'office', 'community'])) {
            return true;
        }
        
        // Load on archive pages
        if (is_post_type_archive(['listing', 'agent', 'office', 'community'])) {
            return true;
        }
        
        // Load on pages with shortcodes
        if ($post && has_shortcode($post->post_content, 'happy_place_')) {
            return true;
        }
        
        return false;
    }

    /**
     * Localize admin scripts with data
     */
    private function localize_admin_scripts(): void {
        wp_localize_script('hph-admin-core', 'hphAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_admin_nonce'),
            'pluginUrl' => HPH_URL,
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Success!', 'happy-place'),
                'confirm' => __('Are you sure?', 'happy-place'),
            ],
            'currentScreen' => get_current_screen()?->id ?? '',
            'currentUser' => get_current_user_id(),
        ]);
    }

    /**
     * Get available built assets
     */
    public function get_built_assets(): array {
        return $this->manifest;
    }

    /**
     * Check if assets are built
     */
    public function assets_built(): bool {
        return $this->manifest_loaded && !empty($this->manifest);
    }
}