<?php

/**
 * Assets Manager Class
 *
 * Handles all asset (CSS/JS) management for the Happy Place Plugin
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
     * @var array Cached asset requirements
     */
    private array $asset_cache = [];

    /**
     * @var array Asset dependencies
     */
    private array $dependencies = [];

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets'], 5);
        add_action('admin_enqueue_scripts', [$this, 'register_admin_assets'], 5);
        add_action('init', [$this, 'setup_asset_dependencies'], 5);
    }

    /**
     * Setup asset dependencies
     */
    public function setup_asset_dependencies(): void
    {
        $this->dependencies = [
            'styles' => [
                'listing' => ['core', 'grid'],
                'agent' => ['core', 'profile'],
                'dashboard' => ['core', 'dashboard', 'forms'],
                'community' => ['core', 'maps'],
                'city' => ['core', 'maps', 'charts']
            ],
            'scripts' => [
                'listing' => ['core', 'maps', 'filters'],
                'agent' => ['core', 'profile'],
                'dashboard' => ['core', 'dashboard', 'forms'],
                'community' => ['core', 'maps'],
                'city' => ['core', 'maps', 'charts']
            ],
            'api_deps' => [
                'maps' => ['google-maps'],
                'charts' => ['google-charts'],
                'listings' => ['google-maps', 'markerclustererplus']
            ]
        ];

        do_action('hph_after_asset_dependencies_setup', $this->dependencies);
    }

    /**
     * Register main assets
     */
    public function register_assets(): void
    {
        // Core styles
        wp_register_style(
            'hph-core',
            HPH_PLUGIN_URL . 'assets/css/core.css',
            [],
            defined('HPH_VERSION') ? HPH_VERSION : '1.0.0'
        );

        // Core scripts
        wp_register_script(
            'hph-core',
            HPH_PLUGIN_URL . 'assets/js/core.js',
            ['jquery'],
            defined('HPH_VERSION') ? HPH_VERSION : '1.0.0',
            true
        );

        // Let other components register their assets
        do_action('hph_register_assets');
    }

    /**
     * Register admin assets
     */
    public function register_admin_assets(): void
    {
        wp_register_style(
            'hph-admin',
            HPH_PLUGIN_URL . 'assets/css/admin.css',
            [],
            defined('HPH_VERSION') ? HPH_VERSION : '1.0.0'
        );

        wp_register_script(
            'hph-admin',
            HPH_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            defined('HPH_VERSION') ? HPH_VERSION : '1.0.0',
            true
        );

        do_action('hph_register_admin_assets');
    }

    /**
     * Enqueue template-specific assets
     */
    public function enqueue_template_assets_by_name(string $template_name): void
    {
        // Check cache first
        if (isset($this->asset_cache[$template_name])) {
            $this->enqueue_cached_assets($template_name);
            return;
        }

        // Get base requirements
        $requirements = $this->get_template_base_requirements($template_name);

        // Cache for future use
        $this->asset_cache[$template_name] = $requirements;

        // Enqueue all required assets
        $this->enqueue_cached_assets($template_name);
    }

    /**
     * Get base requirements for a template
     */
    private function get_template_base_requirements(string $template_name): array
    {
        $template_type = $this->get_template_type($template_name);

        return [
            'styles' => $this->dependencies['styles'][$template_type] ?? ['core'],
            'scripts' => $this->dependencies['scripts'][$template_type] ?? ['core'],
            'api_deps' => $this->dependencies['api_deps'][$template_type] ?? []
        ];
    }

    /**
     * Enqueue cached assets
     */
    private function enqueue_cached_assets(string $template_name): void
    {
        $requirements = $this->asset_cache[$template_name];

        // Enqueue styles
        foreach ($requirements['styles'] as $style) {
            wp_enqueue_style("hph-{$style}");
        }

        // Enqueue scripts
        foreach ($requirements['scripts'] as $script) {
            wp_enqueue_script("hph-{$script}");
        }

        // Handle API dependencies
        foreach ($requirements['api_deps'] as $api) {
            do_action("hph_load_{$api}");
        }
    }

    /**
     * Get template type from template name
     */
    private function get_template_type(string $template_name): string
    {
        if (strpos($template_name, 'listing') !== false) return 'listing';
        if (strpos($template_name, 'agent') !== false) return 'agent';
        if (strpos($template_name, 'dashboard') !== false) return 'dashboard';
        if (strpos($template_name, 'community') !== false) return 'community';
        if (strpos($template_name, 'city') !== false) return 'city';

        return 'core';
    }
}
