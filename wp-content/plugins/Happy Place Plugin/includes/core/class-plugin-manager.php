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
     * @var \HappyPlace\Core\Environment_Config Environment configuration
     */
    private ?\HappyPlace\Core\Environment_Config $env_config = null;
    
    /**
     * @var \HappyPlace\Core\Config_Manager Configuration manager
     */
    private ?\HappyPlace\Core\Config_Manager $config_manager = null;
    
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
        
        // Load configuration classes first
        $env_config_file = HPH_INCLUDES_PATH . 'core/class-environment-config.php';
        $config_manager_file = HPH_INCLUDES_PATH . 'core/class-config-manager.php';
        
        if (file_exists($env_config_file)) {
            require_once $env_config_file;
            error_log('HPH: Environment Config file loaded successfully');
        } else {
            error_log('HPH: Environment Config file not found: ' . $env_config_file);
        }
        
        if (file_exists($config_manager_file)) {
            require_once $config_manager_file;
            error_log('HPH: Config Manager file loaded successfully');
        } else {
            error_log('HPH: Config Manager file not found: ' . $config_manager_file);
        }
        
        // Initialize configuration system
        try {
            $this->env_config = \HappyPlace\Core\Environment_Config::get_instance();
            error_log('HPH: Environment Config initialized successfully');
        } catch (\Exception $e) {
            error_log('HPH: Error initializing Environment Config: ' . $e->getMessage());
        }
        
        try {
            $this->config_manager = \HappyPlace\Core\Config_Manager::get_instance();
            error_log('HPH: Config Manager initialized successfully');
        } catch (\Exception $e) {
            error_log('HPH: Error initializing Config Manager: ' . $e->getMessage());
        }
        
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
        $loaded_components = [];
        
        // Post Types (uses HappyPlace namespace)
        $post_types_file = HPH_INCLUDES_PATH . 'core/class-post-types.php';
        if (file_exists($post_types_file)) {
            require_once $post_types_file;
            if (class_exists('HappyPlace\\Core\\Post_Types')) {
                try {
                    \HappyPlace\Core\Post_Types::initialize();
                    $this->components['post_types'] = 'initialized';
                    $loaded_components[] = 'Post_Types';
                    error_log('HPH: Post_Types initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Post_Types: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Post_Types class not found after loading file');
            }
        } else {
            error_log('HPH: Post_Types file not found: ' . $post_types_file);
        }
        
        // Taxonomies (uses HappyPlace namespace)
        $taxonomies_file = HPH_INCLUDES_PATH . 'core/class-taxonomies.php';
        if (file_exists($taxonomies_file)) {
            require_once $taxonomies_file;
            if (class_exists('HappyPlace\\Core\\Taxonomies')) {
                try {
                    $this->components['taxonomies'] = \HappyPlace\Core\Taxonomies::get_instance();
                    $loaded_components[] = 'Taxonomies';
                    error_log('HPH: Taxonomies initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Taxonomies: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Taxonomies class not found after loading file');
            }
        } else {
            error_log('HPH: Taxonomies file not found: ' . $taxonomies_file);
        }
        
        // Assets Manager (uses HappyPlace namespace)
        $assets_file = HPH_INCLUDES_PATH . 'core/class-assets-manager.php';
        if (file_exists($assets_file)) {
            require_once $assets_file;
            if (class_exists('HappyPlace\\Core\\Assets_Manager')) {
                try {
                    $this->components['assets'] = \HappyPlace\Core\Assets_Manager::get_instance();
                    $loaded_components[] = 'Assets_Manager';
                    error_log('HPH: Assets_Manager initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Assets_Manager: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Assets_Manager class not found after loading file');
            }
        } else {
            error_log('HPH: Assets_Manager file not found: ' . $assets_file);
        }
        
        // Load modern AJAX system early
        $ajax_file = HPH_INCLUDES_PATH . 'api/ajax/class-ajax-coordinator.php';
        if (file_exists($ajax_file)) {
            require_once $ajax_file;
            if (class_exists('HappyPlace\\Api\\Ajax\\Ajax_Coordinator')) {
                try {
                    $this->components['ajax'] = \HappyPlace\Api\Ajax\Ajax_Coordinator::init();
                    $loaded_components[] = 'Ajax_Coordinator';
                    error_log('HPH: Ajax_Coordinator initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Ajax_Coordinator: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH: Ajax_Coordinator file not found: ' . $ajax_file);
        }
        
        // Fields Manager
        $enhanced_fields_file = HPH_INCLUDES_PATH . 'fields/class-enhanced-field-manager.php';
        $listing_calc_file = HPH_INCLUDES_PATH . 'fields/class-listing-calculator.php';
        
        if (file_exists($enhanced_fields_file)) {
            require_once $enhanced_fields_file;
            $loaded_components[] = 'Enhanced_Field_Manager';
        }
        
        if (file_exists($listing_calc_file)) {
            require_once $listing_calc_file;
            $loaded_components[] = 'Listing_Calculator';
        }
        
        // Form Management System
        $form_manager_file = HPH_INCLUDES_PATH . 'forms/class-form-manager.php';
        $form_validator_file = HPH_INCLUDES_PATH . 'forms/validators/class-form-validator.php';
        
        if (file_exists($form_manager_file)) {
            require_once $form_manager_file;
            if (class_exists('HappyPlace\\Forms\\Form_Manager')) {
                try {
                    // Include base form handler
                    $base_handler_file = HPH_INCLUDES_PATH . 'forms/class-base-form-handler.php';
                    if (file_exists($base_handler_file)) {
                        require_once $base_handler_file;
                    }
                    
                    // Include form renderer
                    $form_renderer_file = HPH_INCLUDES_PATH . 'forms/class-form-renderer.php';
                    if (file_exists($form_renderer_file)) {
                        require_once $form_renderer_file;
                    }
                    
                    // Initialize form validator
                    if (file_exists($form_validator_file)) {
                        require_once $form_validator_file;
                        \HappyPlace\Forms\Validators\Form_Validator::init();
                    }
                    
                    // Initialize form manager
                    \HappyPlace\Forms\Form_Manager::init();
                    $this->components['form_manager'] = 'initialized';
                    $loaded_components[] = 'Form_Manager';
                    error_log('HPH: Form_Manager initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Form_Manager: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Form_Manager class not found after loading file');
            }
        } else {
            error_log('HPH: Form_Manager file not found: ' . $form_manager_file);
        }
        
        // Dashboard System
        $dashboard_manager_file = HPH_INCLUDES_PATH . 'dashboard/class-dashboard-manager.php';
        if (file_exists($dashboard_manager_file)) {
            require_once $dashboard_manager_file;
            if (class_exists('HappyPlace\\Dashboard\\Dashboard_Manager')) {
                try {
                    $this->components['dashboard'] = \HappyPlace\Dashboard\Dashboard_Manager::get_instance();
                    $this->components['dashboard']->init();
                    $loaded_components[] = 'Dashboard_Manager';
                    error_log('HPH: Dashboard_Manager initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Dashboard_Manager: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Dashboard_Manager class not found after loading file');
            }
        } else {
            error_log('HPH: Dashboard_Manager file not found: ' . $dashboard_manager_file);
        }
        
        error_log('HPH: Core components loaded: ' . implode(', ', $loaded_components));
    }
    
    /**
     * Load admin components
     */
    private function load_admin_components(): void {
        $loaded_admin_components = [];
        
        // Config Admin
        $config_admin_file = HPH_INCLUDES_PATH . 'admin/class-config-admin.php';
        if (file_exists($config_admin_file)) {
            require_once $config_admin_file;
            if (class_exists('HappyPlace\\Admin\\Config_Admin')) {
                try {
                    $this->components['config_admin'] = \HappyPlace\Admin\Config_Admin::get_instance();
                    $loaded_admin_components[] = 'Config_Admin';
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Config_Admin: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH: Config_Admin file not found: ' . $config_admin_file);
        }
        
        // Enhanced Admin Menu (with AJAX and improved functionality)
        $admin_menu_file = HPH_INCLUDES_PATH . 'admin/class-admin-menu.php';
        if (file_exists($admin_menu_file)) {
            require_once $admin_menu_file;
            if (class_exists('HappyPlace\\Admin\\Admin_Menu')) {
                try {
                    $this->components['admin_menu'] = \HappyPlace\Admin\Admin_Menu::get_instance();
                    $loaded_admin_components[] = 'Admin_Menu';
                    error_log('HPH: Admin_Menu initialized successfully');
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing Admin_Menu: ' . $e->getMessage());
                }
            } else {
                error_log('HPH: Admin_Menu class not found after loading file');
            }
        } else {
            error_log('HPH: Admin_Menu file not found: ' . $admin_menu_file);
        }
        
        // CSV Import Manager
        $csv_import_file = HPH_INCLUDES_PATH . 'admin/class-csv-import-manager.php';
        if (file_exists($csv_import_file)) {
            require_once $csv_import_file;
            if (class_exists('HappyPlace\\Admin\\CSV_Import_Manager')) {
                try {
                    $this->components['csv_import'] = \HappyPlace\Admin\CSV_Import_Manager::get_instance();
                    $loaded_admin_components[] = 'CSV_Import_Manager';
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing CSV_Import_Manager: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH: CSV_Import_Manager file not found: ' . $csv_import_file);
        }
        
        // User Roles Manager (uses HappyPlace namespace)
        $user_roles_file = HPH_INCLUDES_PATH . 'users/class-user-roles-manager.php';
        if (file_exists($user_roles_file)) {
            require_once $user_roles_file;
            if (class_exists('HappyPlace\\Users\\User_Roles_Manager')) {
                try {
                    $this->components['user_roles'] = \HappyPlace\Users\User_Roles_Manager::get_instance();
                    $loaded_admin_components[] = 'User_Roles_Manager';
                } catch (\Exception $e) {
                    error_log('HPH: Error initializing User_Roles_Manager: ' . $e->getMessage());
                }
            }
        } else {
            error_log('HPH: User_Roles_Manager file not found: ' . $user_roles_file);
        }
        
        error_log('HPH: Admin components loaded: ' . implode(', ', $loaded_admin_components));
    }
    
    /**
     * Load frontend components
     */
    private function load_frontend_components(): void {
        // Flyer Generator (Graphics Component) - Using Full Version with Bridge Integration
        require_once HPH_INCLUDES_PATH . 'graphics/class-flyer-generator.php';
        if (class_exists('HappyPlace\\Graphics\\Flyer_Generator')) {
            $this->components['flyer_generator'] = \HappyPlace\Graphics\Flyer_Generator::get_instance();
        }
        
        // Utility classes
        if (file_exists(HPH_INCLUDES_PATH . 'utilities/class-image-processor.php')) {
            require_once HPH_INCLUDES_PATH . 'utilities/class-image-processor.php';
            if (class_exists('HappyPlace\\Utilities\\Image_Processor')) {
                $this->components['image_processor'] = new \HappyPlace\Utilities\Image_Processor();
            }
        }
        
        error_log('HPH: Frontend components loaded');
    }
    
    /**
     * Load integrations
     */
    private function load_integrations(): void {
        // Enhanced Airtable Sync System - Already handled by AJAX Integration handler
        require_once HPH_INCLUDES_PATH . 'integrations/init-enhanced-sync.php';
        
        error_log('HPH: Integration systems loaded');
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
        
        // Development tools only in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (file_exists(HPH_INCLUDES_PATH . 'fields/phase1-status-page.php')) {
                require_once HPH_INCLUDES_PATH . 'fields/phase1-status-page.php';
            }
        }
        
        error_log('HPH: ACF enhancements loaded');
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

    /**
     * Log integration error for admin display and debugging
     */
    public function log_integration_error(string $integration, string $error, array $context = []): void {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'integration' => $integration,
            'error' => $error,
            'context' => $context,
            'user_id' => get_current_user_id()
        ];
        
        // Log to WordPress
        error_log("HPH Integration Error [{$integration}]: {$error}");
        
        // Store for admin display
        $errors = get_option('hph_integration_errors', []);
        $errors[] = $log_entry;
        
        // Keep only last 50 errors
        if (count($errors) > 50) {
            $errors = array_slice($errors, -50);
        }
        
        update_option('hph_integration_errors', $errors);
    }

    /**
     * Get recent integration errors for admin display
     */
    public function get_integration_errors(int $limit = 10): array {
        $errors = get_option('hph_integration_errors', []);
        return array_slice($errors, -$limit);
    }

    /**
     * Clear integration error log
     */
    public function clear_integration_errors(): void {
        delete_option('hph_integration_errors');
    }
    
    /**
     * Get plugin status for diagnostics
     */
    public function get_status(): array {
        return [
            'loaded' => $this->loaded,
            'components_count' => count($this->components),
            'components' => array_keys($this->components),
            'version' => $this->get_version(),
            'last_rebuild' => get_option('hph_last_rebuild', 'Never'),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'constants' => [
                'HPH_VERSION' => defined('HPH_VERSION') ? HPH_VERSION : 'Not defined',
                'HPH_PATH' => defined('HPH_PATH') ? HPH_PATH : 'Not defined',
                'HPH_URL' => defined('HPH_URL') ? HPH_URL : 'Not defined',
                'HPH_ASSETS_URL' => defined('HPH_ASSETS_URL') ? HPH_ASSETS_URL : 'Not defined'
            ]
        ];
    }
}
