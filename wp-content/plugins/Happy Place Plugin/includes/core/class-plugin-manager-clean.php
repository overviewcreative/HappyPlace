<?php
/**
 * Plugin Manager Class (Optimized)
 * 
 * Streamlined orchestrator for the Happy Place Plugin
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

class Plugin_Manager_Clean {
    
    private static ?self $instance = null;
    private array $components = [];
    private bool $loaded = false;
    
    /**
     * Component loading configuration
     */
    private array $core_components = [
        'Environment_Config' => 'core/class-environment-config.php',
        'Config_Manager' => 'core/class-config-manager.php',
        'Post_Types' => 'core/class-post-types.php',
        'Taxonomies' => 'core/class-taxonomies.php',
        'Database' => 'core/class-database.php',
        'Assets_Manager' => 'core/class-assets-manager.php',
    ];
    
    private array $admin_components = [
        'Admin_Menu' => 'admin/class-admin-menu.php',
        'Settings_Page' => 'admin/class-settings-page.php',
    ];
    
    private array $frontend_components = [
        'Assets' => 'front/class-assets.php',
    ];
    
    private array $integration_components = [
        'REST_API' => 'api/class-rest-api.php',
        'Bridge_Function_Manager' => 'bridge/class-bridge-function-manager.php',
    ];
    
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
        $this->load_textdomain();
        
        // Load components in order
        $this->load_component_group($this->core_components, 'HappyPlace\\Core\\');
        
        if (is_admin()) {
            $this->load_component_group($this->admin_components, 'HappyPlace\\Admin\\');
        } else {
            $this->load_component_group($this->frontend_components, 'HappyPlace\\Front\\');
        }
        
        $this->load_component_group($this->integration_components, 'HappyPlace\\');
        
        // Load specialized components
        $this->load_acf_enhancements();
        $this->load_dashboard_components();
        
        $this->loaded = true;
        do_action('hph_plugin_loaded', $this);
    }
    
    /**
     * Load a group of components
     */
    private function load_component_group(array $components, string $namespace_prefix): void {
        foreach ($components as $class_name => $file_path) {
            $full_path = HPH_INCLUDES_PATH . $file_path;
            $full_class_name = $namespace_prefix . $class_name;
            
            if (file_exists($full_path)) {
                require_once $full_path;
                
                if (class_exists($full_class_name)) {
                    try {
                        // Initialize static classes or get instance
                        if (method_exists($full_class_name, 'get_instance')) {
                            $this->components[$class_name] = $full_class_name::get_instance();
                        } elseif (method_exists($full_class_name, 'initialize')) {
                            $full_class_name::initialize();
                            $this->components[$class_name] = 'initialized';
                        } else {
                            $this->components[$class_name] = new $full_class_name();
                        }
                        
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("HPH: {$class_name} loaded successfully");
                        }
                    } catch (\Exception $e) {
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log("HPH: Error loading {$class_name}: " . $e->getMessage());
                        }
                    }
                } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("HPH: Class {$full_class_name} not found after loading {$file_path}");
                }
            } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH: File not found: {$full_path}");
            }
        }
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
            if (is_object($component) && method_exists($component, 'init')) {
                $component->init();
            }
        }
        
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
            if (is_object($component) && method_exists($component, 'init_admin')) {
                $component->init_admin();
            }
        }
        
        do_action('hph_admin_initialized', $this);
    }
    
    /**
     * Load ACF enhancements
     */
    private function load_acf_enhancements(): void {
        $acf_files = [
            'fields/class-enhanced-acf-fields.php',
            'fields/class-listing-calculator.php',
        ];
        
        foreach ($acf_files as $file) {
            $path = HPH_INCLUDES_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
    
    /**
     * Load dashboard components
     */
    private function load_dashboard_components(): void {
        $dashboard_files = [
            // Load base classes first
            'dashboard/sections/class-base-dashboard-section.php',
            'dashboard/widgets/class-base-dashboard-widget.php',
            
            // Then specific implementations
            'dashboard/class-marketing-suite-generator.php',
            'dashboard/sections/class-marketing-section.php',
            'dashboard/handlers/class-flyer-generator-handler.php',
        ];
        
        foreach ($dashboard_files as $file) {
            $path = HPH_INCLUDES_PATH . $file;
            if (file_exists($path)) {
                require_once $path;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("HPH: Loaded dashboard component: {$file}");
                }
            } elseif (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("HPH: Dashboard component not found: {$file}");
            }
        }
    }
    
    /**
     * Load textdomain for translations
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
        // Create necessary database tables, options, etc.
        if (method_exists($this->components['Database'] ?? null, 'create_tables')) {
            $this->components['Database']->create_tables();
        }
        
        // Flush rewrite rules after registering post types
        flush_rewrite_rules();
        
        do_action('hph_plugin_activated');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        // Clean up scheduled events, flush rules, etc.
        wp_clear_scheduled_hook('hph_daily_sync');
        flush_rewrite_rules();
        
        do_action('hph_plugin_deactivated');
    }
    
    /**
     * Get loaded component
     */
    public function get_component(string $name) {
        return $this->components[$name] ?? null;
    }
    
    /**
     * Check if plugin is fully loaded
     */
    public function is_loaded(): bool {
        return $this->loaded;
    }
    
    /**
     * Get all loaded components
     */
    public function get_components(): array {
        return $this->components;
    }
}