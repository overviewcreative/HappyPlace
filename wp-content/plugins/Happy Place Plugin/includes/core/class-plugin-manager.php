<?php
/**
 * Plugin Manager Class
 * 
 * Main orchestrator for the Happy Place Plugin
 * Handles initialization, loading, and coordination of all plugin components
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Manager {
    
    /**
     * @var Plugin_Manager Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * @var array Plugin components
     */
    private array $components = [];
    
    /**
     * @var bool Whether plugin is fully loaded
     */
    private bool $loaded = false;
    
    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    
    /**
     * Initialize the plugin
     */
    private function __construct() {
        add_action('plugins_loaded', [$this, 'load_plugin'], 10);
        add_action('init', [$this, 'init_plugin'], 5);
        add_action('admin_init', [$this, 'init_admin'], 10);
        
        // Register activation/deactivation hooks
        register_activation_hook(HPH_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(HPH_PLUGIN_FILE, [$this, 'deactivate']);
    }
    
    /**
     * Load plugin components
     */
    public function load_plugin(): void {
        // Load translations
        $this->load_textdomain();
        
        // Load core components
        $this->load_core_components();
        
        // Load admin components if needed
        if (is_admin()) {
            $this->load_admin_components();
        }
        
        // Load frontend components
        if (!is_admin()) {
            $this->load_frontend_components();
        }
        
        // Load integrations
        $this->load_integrations();
        
        // Load ACF enhancements
        $this->load_acf_enhancements();
        
        $this->loaded = true;
        
        // Fire hook for extensions
        do_action('hph_plugin_loaded', $this);
    }
    
    /**
     * Initialize plugin after WordPress is loaded
     */
    public function init_plugin(): void {
        if (!$this->loaded) {
            return;
        }
        
        // Initialize all loaded components
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
        
        // Fire hook for post-initialization
        do_action('hph_plugin_initialized', $this);
    }
    
    /**
     * Initialize admin-specific functionality
     */
    public function init_admin(): void {
        if (!$this->loaded || !is_admin()) {
            return;
        }
        
        // Initialize admin components
        foreach ($this->components as $component) {
            if (method_exists($component, 'init_admin')) {
                $component->init_admin();
            }
        }
        
        // Fire hook for admin initialization
        do_action('hph_admin_initialized', $this);
    }
    
    /**
     * Load core components
     */
    private function load_core_components(): void {
        // Post Types (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-post-types.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-post-types.php';
            if (class_exists('HappyPlace\\Core\\Post_Types')) {
                // This class uses initialize() method, not singleton
                \HappyPlace\Core\Post_Types::initialize();
                $this->components['post_types'] = 'initialized';
            }
        }
        
        // Taxonomies (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-taxonomies.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-taxonomies.php';
            if (class_exists('HappyPlace\\Core\\Taxonomies')) {
                $this->components['taxonomies'] = \HappyPlace\Core\Taxonomies::get_instance();
            }
        }
        
        // Assets Manager (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-assets-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-assets-manager.php';
            if (class_exists('HappyPlace\\Core\\Assets_Manager')) {
                if (method_exists('HappyPlace\\Core\\Assets_Manager', 'get_instance')) {
                    $this->components['assets'] = \HappyPlace\Core\Assets_Manager::get_instance();
                } elseif (method_exists('HappyPlace\\Core\\Assets_Manager', 'instance')) {
                    $this->components['assets'] = \HappyPlace\Core\Assets_Manager::instance();
                }
            }
        }
        
        // Database Manager (check if exists)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-database.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-database.php';
            if (class_exists('HappyPlace\\Core\\Database')) {
                $this->components['database'] = \HappyPlace\Core\Database::get_instance();
            }
        }
        
        // Template Engine (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'core/class-template-engine.php')) {
            require_once HPH_INCLUDES_PATH . 'core/class-template-engine.php';
            if (class_exists('HappyPlace\\Core\\Template_Engine')) {
                if (method_exists('HappyPlace\\Core\\Template_Engine', 'get_instance')) {
                    $this->components['template_engine'] = \HappyPlace\Core\Template_Engine::get_instance();
                } elseif (method_exists('HappyPlace\\Core\\Template_Engine', 'instance')) {
                    $this->components['template_engine'] = \HappyPlace\Core\Template_Engine::instance();
                }
            }
        }
    }
    
    /**
     * Load admin components
     */
    private function load_admin_components(): void {
        // Admin Menu (uses HPH namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-admin-menu.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-admin-menu.php';
            if (class_exists('HPH\\Admin\\Admin_Menu')) {
                $this->components['admin_menu'] = \HPH\Admin\Admin_Menu::get_instance();
            }
        }
        
        // Settings Page (uses HPH namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'admin/class-settings-page.php')) {
            require_once HPH_INCLUDES_PATH . 'admin/class-settings-page.php';
            if (class_exists('HPH\\Admin\\Settings_Page')) {
                $this->components['settings'] = \HPH\Admin\Settings_Page::get_instance();
            }
        }
        
        // User Roles Manager (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'users/class-user-roles-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'users/class-user-roles-manager.php';
            if (class_exists('HappyPlace\\Users\\User_Roles_Manager')) {
                $this->components['user_roles'] = \HappyPlace\Users\User_Roles_Manager::get_instance();
            }
        }
        
        // Dashboard Manager (uses HappyPlace namespace)
        if (file_exists(HPH_INCLUDES_PATH . 'users/class-user-dashboard-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'users/class-user-dashboard-manager.php';
            if (class_exists('HappyPlace\\Users\\User_Dashboard_Manager')) {
                if (method_exists('HappyPlace\\Users\\User_Dashboard_Manager', 'get_instance')) {
                    $this->components['dashboard'] = \HappyPlace\Users\User_Dashboard_Manager::get_instance();
                } elseif (method_exists('HappyPlace\\Users\\User_Dashboard_Manager', 'instance')) {
                    $this->components['dashboard'] = \HappyPlace\Users\User_Dashboard_Manager::instance();
                }
            }
        }
    }
    
    /**
     * Load frontend components
     */
    private function load_frontend_components(): void {
        // Template Functions
        if (file_exists(HPH_INCLUDES_PATH . 'template-functions.php')) {
            require_once HPH_INCLUDES_PATH . 'template-functions.php';
        }
        
        // Shortcodes
        if (file_exists(HPH_INCLUDES_PATH . 'shortcodes.php')) {
            require_once HPH_INCLUDES_PATH . 'shortcodes.php';
        }
        
        // Dashboard AJAX Handler (critical for frontend dashboard)
        if (file_exists(HPH_INCLUDES_PATH . 'dashboard/class-dashboard-ajax-handler.php')) {
            require_once HPH_INCLUDES_PATH . 'dashboard/class-dashboard-ajax-handler.php';
            if (class_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler')) {
                if (method_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler', 'instance')) {
                    $this->components['dashboard_ajax'] = \HappyPlace\Dashboard\HPH_Dashboard_Ajax_Handler::instance();
                } elseif (method_exists('HappyPlace\\Dashboard\\HPH_Dashboard_Ajax_Handler', 'get_instance')) {
                    $this->components['dashboard_ajax'] = \HappyPlace\Dashboard\HPH_Dashboard_Ajax_Handler::get_instance();
                }
            }
        }
        
        // Load AJAX handlers that work for both admin and frontend
        if (file_exists(HPH_INCLUDES_PATH . 'api/class-ajax-handler.php')) {
            require_once HPH_INCLUDES_PATH . 'api/class-ajax-handler.php';
        }
        
        // Advanced Form AJAX Handler
        if (file_exists(HPH_INCLUDES_PATH . 'ajax/class-advanced-form-ajax.php')) {
            require_once HPH_INCLUDES_PATH . 'ajax/class-advanced-form-ajax.php';
        }
        
        // Flyer Generator AJAX Handler
        if (file_exists(HPH_INCLUDES_PATH . 'ajax/flyer-generator.php')) {
            require_once HPH_INCLUDES_PATH . 'ajax/flyer-generator.php';
        }
        
        // Utility classes
        if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-data-validator.php')) {
            require_once HPH_INCLUDES_PATH . 'utilities/class-data-validator.php';
        }
        
        if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-image-processor.php')) {
            require_once HPH_INCLUDES_PATH . 'utilities/class-image-processor.php';
            if (class_exists('HappyPlace\\Utilities\\Image_Processor')) {
                $this->components['image_processor'] = new \HappyPlace\Utilities\Image_Processor();
            }
        }
    }
    
    /**
     * Load integrations
     */
    private function load_integrations(): void {
        // Load Airtable Two-Way Sync
        if (file_exists(HPH_INCLUDES_PATH . 'integrations/class-airtable-two-way-sync.php')) {
            require_once HPH_INCLUDES_PATH . 'integrations/class-airtable-two-way-sync.php';
            if (class_exists('HappyPlace\\Integrations\\Airtable_Two_Way_Sync')) {
                // Note: This class may not use singleton pattern
                // $this->components['airtable_sync'] = \HappyPlace\Integrations\Airtable_Two_Way_Sync::get_instance();
            }
        }
        
        // Load other integration classes as they're created
        // Base Integration class for future use
        if (file_exists(HPH_INCLUDES_PATH . 'integrations/class-base-integration.php')) {
            require_once HPH_INCLUDES_PATH . 'integrations/class-base-integration.php';
        }
    }
    
    /**
     * Load ACF enhancements
     */
    private function load_acf_enhancements(): void {
        // Load Consolidated ACF Manager
        if (file_exists(HPH_INCLUDES_PATH . 'fields/class-acf-manager.php')) {
            require_once HPH_INCLUDES_PATH . 'fields/class-acf-manager.php';
            if (class_exists('HappyPlace\\Fields\\ACF_Manager')) {
                $this->components['acf_manager'] = \HappyPlace\Fields\ACF_Manager::get_instance();
            }
        }
    }
    
    /**
     * Load plugin textdomain
     */
    private function load_textdomain(): void {
        load_plugin_textdomain(
            'happy-place',
            false,
            dirname(plugin_basename(HPH_PLUGIN_FILE)) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate(): void {
        // Create database tables if needed
        if (isset($this->components['database'])) {
            $this->components['database']->create_tables();
        }
        
        // Set up custom post types and taxonomies
        if (isset($this->components['post_types'])) {
            $this->components['post_types']->register_post_types();
        }
        
        if (isset($this->components['taxonomies'])) {
            $this->components['taxonomies']->register_taxonomies();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('hph_plugin_activated', time());
        
        // Fire activation hook
        do_action('hph_plugin_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set deactivation flag
        update_option('hph_plugin_deactivated', time());
        
        // Fire deactivation hook
        do_action('hph_plugin_deactivated');
    }
    
    /**
     * Get loaded component
     */
    public function get_component(string $component_name): ?object {
        return $this->components[$component_name] ?? null;
    }
    
    /**
     * Check if plugin is fully loaded
     */
    public function is_loaded(): bool {
        return $this->loaded;
    }
    
    /**
     * Get plugin version
     */
    public function get_version(): string {
        return HPH_VERSION;
    }
    
    /**
     * Get plugin path
     */
    public function get_plugin_path(string $path = ''): string {
        return HPH_PATH . ltrim($path, '/');
    }
    
    /**
     * Get plugin URL
     */
    public function get_plugin_url(string $path = ''): string {
        return HPH_URL . ltrim($path, '/');
    }
}
