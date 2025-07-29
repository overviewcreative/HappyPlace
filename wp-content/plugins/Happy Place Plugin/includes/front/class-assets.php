<?php
namespace HappyPlace\Front;

/**
 * Plugin Frontend Assets
 * 
 * Registers plugin-specific assets with the theme's Asset_Manager
 * No longer handles direct enqueuing - delegates to theme
 */
class Assets {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('init', [$this, 'register_plugin_assets']);
    }

    /**
     * Register plugin assets with theme's Asset_Manager
     */
    public function register_plugin_assets(): void {
        // For now, use fallback method - theme Asset_Manager integration can be added later
        add_action('wp_enqueue_scripts', [$this, 'fallback_enqueue_assets']);
    }

    /**
     * Fallback for when theme Asset_Manager is not available
     */
    public function fallback_enqueue_assets(): void {
        if (is_singular('listing')) {
            wp_enqueue_style(
                'happy-place-pdf-button',
                plugins_url('assets/css/pdf-button.css', dirname(__DIR__)),
                [],
                filemtime(plugin_dir_path(dirname(__DIR__)) . 'assets/css/pdf-button.css')
            );

            wp_enqueue_script('jquery');
        }
    }
}

// Initialize Assets
add_action('init', function() {
    Assets::get_instance();
});
