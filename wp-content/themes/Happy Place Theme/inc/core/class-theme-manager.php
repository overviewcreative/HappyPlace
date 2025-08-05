<?php
/**
 * Theme Manager Class
 *
 * Main theme orchestrator - handles core setup, feature support, and initialization
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Theme_Manager {
    /**
     * Instance of this class
     *
     * @var Theme_Manager
     */
    private static $instance = null;
    
    /**
     * Theme components
     *
     * @var array
     */
    private $components = [];
    
    /**
     * Whether theme is fully loaded
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * Get instance of this class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_core_components();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('after_setup_theme', [$this, 'theme_setup'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 10);
        add_action('widgets_init', [$this, 'register_sidebars'], 10);
        add_action('init', [$this, 'init_theme_components'], 5);
        add_filter('excerpt_length', [$this, 'custom_excerpt_length'], 999);
        add_filter('excerpt_more', [$this, 'custom_excerpt_more']);
    }
    
    /**
     * Load core theme components
     */
    private function load_core_components() {
        $loaded_components = [];
        
        // Load Asset Manager first (critical for CSS/JS loading)
        $asset_manager_file = HPH_THEME_PATH . '/inc/core/class-asset-manager.php';
        if (file_exists($asset_manager_file)) {
            require_once $asset_manager_file;
            if (class_exists('HappyPlace\Core\Asset_Manager')) {
                try {
                    $this->components['asset_manager'] = Asset_Manager::init();
                    $loaded_components[] = 'Asset_Manager';
                    error_log('HPH Theme: Asset_Manager loaded successfully');
                } catch (\Exception $e) {
                    error_log('HPH Theme: Error loading Asset_Manager: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH Theme: Asset_Manager file not found: ' . $asset_manager_file);
        }
        
        // Load Template Engine
        $template_engine_file = HPH_THEME_PATH . '/inc/core/class-template-engine.php';
        if (file_exists($template_engine_file)) {
            require_once $template_engine_file;
            if (class_exists('HappyPlace\Core\Template_Engine')) {
                try {
                    $this->components['template_engine'] = Template_Engine::instance();
                    $loaded_components[] = 'Template_Engine';
                    error_log('HPH Theme: Template_Engine loaded successfully');
                } catch (\Exception $e) {
                    error_log('HPH Theme: Error loading Template_Engine: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH Theme: Template_Engine file not found: ' . $template_engine_file);
        }
        
        // Load Component Manager
        $component_manager_file = HPH_THEME_PATH . '/inc/core/class-component-manager.php';
        if (file_exists($component_manager_file)) {
            require_once $component_manager_file;
            if (class_exists('HappyPlace\Core\Component_Manager')) {
                try {
                    $this->components['component_manager'] = Component_Manager::init();
                    $loaded_components[] = 'Component_Manager';
                    error_log('HPH Theme: Component_Manager loaded successfully');
                } catch (\Exception $e) {
                    error_log('HPH Theme: Error loading Component_Manager: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH Theme: Component_Manager file not found: ' . $component_manager_file);
        }
        
        // Load Bridge Functions
        $this->load_bridge_functions();
        
        error_log('HPH Theme: Core components loaded: ' . implode(', ', $loaded_components));
    }
    
    /**
     * Load bridge functions
     */
    private function load_bridge_functions() {
        $bridge_files = [
            'template-helpers.php',
            'listing-bridge.php', 
            'archive-bridge.php',
            'template-bridge.php'
        ];
        
        $loaded_bridges = [];
        foreach ($bridge_files as $bridge_file) {
            $full_path = HPH_THEME_PATH . '/inc/bridge/' . $bridge_file;
            if (file_exists($full_path)) {
                require_once $full_path;
                $loaded_bridges[] = basename($bridge_file, '.php');
            } else {
                error_log('HPH Theme: Bridge file not found: ' . $full_path);
            }
        }
        
        if (!empty($loaded_bridges)) {
            error_log('HPH Theme: Bridge functions loaded: ' . implode(', ', $loaded_bridges));
        }
    }
    
    /**
     * Initialize theme components after WordPress init
     */
    public function init_theme_components() {
        // Initialize all loaded components
        foreach ($this->components as $name => $component) {
            if (is_object($component) && method_exists($component, 'init')) {
                try {
                    $component->init();
                    error_log('HPH Theme: ' . $name . ' initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH Theme: Error initializing ' . $name . ': ' . $e->getMessage());
                }
            }
        }
        
        $this->loaded = true;
        do_action('hph_theme_loaded', $this);
    }
    
    /**
     * Enqueue theme assets
     */
    public function enqueue_assets() {
        // Asset_Manager handles its own enqueuing via wp_enqueue_scripts hook
        // So we don't need to manually call it here, just ensure fallbacks exist
        if (!isset($this->components['asset_manager'])) {
            // Fallback asset loading if Asset_Manager failed to load
            wp_enqueue_style('happy-place-theme', HPH_THEME_URL . '/assets/css/theme.css', [], HPH_THEME_VERSION);
            wp_enqueue_script('happy-place-theme', HPH_THEME_URL . '/assets/js/theme.js', ['jquery'], HPH_THEME_VERSION, true);
            error_log('HPH Theme: Using fallback asset loading');
        }
    }

    /**
     * Setup theme features and support
     */
    public function theme_setup() {
        // Theme support
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script'
        ]);

        // Menu locations
        register_nav_menus([
            'primary' => __('Primary Menu', 'happy-place'),
            'footer' => __('Footer Menu', 'happy-place'),
            'social' => __('Social Links Menu', 'happy-place'),
        ]);

        // Image sizes
        add_image_size('listing-thumbnail', 400, 300, true);
        add_image_size('listing-gallery', 800, 600, true);
        add_image_size('agent-photo', 300, 300, true);
    }

    /**
     * Register widget areas
     */
    public function register_sidebars() {
        register_sidebar([
            'name'          => __('Primary Sidebar', 'happy-place'),
            'id'            => 'sidebar-1',
            'description'   => __('Main sidebar that appears on the right.', 'happy-place'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ]);

        register_sidebar([
            'name'          => __('Footer Widget Area', 'happy-place'),
            'id'            => 'footer-widgets',
            'description'   => __('Appears in the footer section of the site.', 'happy-place'),
            'before_widget' => '<div id="%1$s" class="footer-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="footer-widget-title">',
            'after_title'   => '</h4>',
        ]);
    }

    /**
     * Custom excerpt length
     */
    public function custom_excerpt_length($length) {
        return 20;
    }

    /**
     * Custom excerpt more
     */
    public function custom_excerpt_more($more) {
        return '...';
    }
}
