<?php

/**
 * Assets Manager Class - Simplified for Plugin
 *
 * Registers plugin assets with theme's Asset_Manager
 * No longer handles direct enqueuing - delegates to theme
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Assets_Manager
{
    /**
     * @var Assets_Manager Singleton instance
     */
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Alternative method name for compatibility
     */
    public static function instance(): self {
        return self::get_instance();
    }

    /**
     * Initialize plugin assets
     */
    private function __construct() {
        add_action('init', [$this, 'register_plugin_assets'], 5);
    }

    /**
     * Register plugin assets with theme
     */
    public function register_plugin_assets(): void {
        // Register plugin's specific assets that theme should know about
        $this->register_admin_assets();
        $this->register_frontend_assets();
    }

    /**
     * Register admin-specific assets
     */
    private function register_admin_assets(): void {
        if (!is_admin()) {
            return;
        }

        // Register with WordPress directly for admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Register frontend assets
     */
    private function register_frontend_assets(): void {
        // Frontend assets are handled by theme's Asset_Manager
        // This is now just a placeholder for plugin-specific frontend assets
    }

    /**
     * Enqueue admin assets directly
     */
    public function enqueue_admin_assets(): void {
        $screen = get_current_screen();
        
        if (!$screen || strpos($screen->id, 'happy-place') === false) {
            return;
        }

        // Enqueue admin-specific CSS/JS here
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    /**
     * Legacy method for template assets - now handled by theme
     */
    public function enqueue_template_assets_by_name($template_name): void {
        // This method is called by theme Template_Loader
        // For now, do nothing - let theme handle template assets
    }

    /**
     * Get plugin asset URL
     */
    public function get_asset_url($asset_path): string {
        return plugins_url('assets/' . ltrim($asset_path, '/'), dirname(__DIR__));
    }

    /**
     * Get plugin asset path
     */
    public function get_asset_path($asset_path): string {
        return plugin_dir_path(dirname(__DIR__)) . 'assets/' . ltrim($asset_path, '/');
    }
}