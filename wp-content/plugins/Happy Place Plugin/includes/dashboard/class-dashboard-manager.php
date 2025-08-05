<?php
/**
 * Dashboard Manager - Unified coordinator for all dashboard functionality
 * 
 * Central management system for agent, broker, and client dashboards with
 * role-based access control, section management, and extensible architecture.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Manager {
    
    /**
     * @var Dashboard_Manager Singleton instance
     */
    private static ?self $instance = null;
    
    /**
     * @var array Registered dashboard sections
     */
    private array $sections = [];
    
    /**
     * @var array Registered dashboard widgets
     */
    private array $widgets = [];
    
    
    /**
     * @var Dashboard_Data_Provider Data provider instance
     */
    private ?Dashboard_Data_Provider $data_provider = null;
    
    /**
     * @var Dashboard_Permissions Permissions manager instance
     */
    private ?Dashboard_Permissions $permissions = null;
    
    /**
     * @var Marketing_Suite_Integration Marketing suite integration
     */
    private ?Marketing_Suite_Integration $marketing_suite = null;
    
    /**
     * @var Dashboard_Listing_Forms Forms integration
     */
    private ?Dashboard_Listing_Forms $forms = null;
    
    /**
     * @var array Dashboard configuration
     */
    private array $config = [];
    
    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_config();
        $this->init_hooks();
    }
    
    /**
     * Initialize dashboard system
     */
    public function init(): void {
        // Initialize core components
        $this->init_core_components();
        
        // Register default sections
        $this->register_default_sections();
        
        // Register default widgets
        $this->register_default_widgets();
        
        // Setup dashboard routes on init hook (rewrite system not available yet)
        add_action('init', [$this, 'setup_routes']);
        
        // Initialize assets
        $this->init_assets();
        
        error_log('HPH Dashboard: Dashboard Manager initialized successfully');
    }
    
    /**
     * Initialize configuration
     */
    private function init_config(): void {
        $this->config = [
            'dashboard_slug' => 'agent-dashboard',
            'cache_duration' => 15 * MINUTE_IN_SECONDS,
            'refresh_interval' => 30, // seconds
            'mobile_breakpoint' => 768,
            'sections_per_page' => 10,
            'widgets_per_section' => 6,
            'enable_real_time' => true,
            'enable_mobile_app' => true,
            'enable_notifications' => true
        ];
        
        // Allow customization via filters
        $this->config = apply_filters('hph_dashboard_config', $this->config);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Core initialization
        add_action('init', [$this, 'init'], 15);
        
        // Template handling
        add_filter('template_include', [$this, 'maybe_load_dashboard_template']);
        add_filter('template_redirect', [$this, 'handle_dashboard_redirects']);
        
        // Asset management
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_dashboard_assets']);
        
        // AJAX handlers
        add_action('wp_ajax_hph_dashboard_section', [$this, 'ajax_load_section']);
        add_action('wp_ajax_hph_dashboard_widget', [$this, 'ajax_load_widget']);
        add_action('wp_ajax_hph_dashboard_action', [$this, 'ajax_handle_action']);
        add_action('wp_ajax_hph_dashboard_form_load', [$this, 'ajax_load_form']);
        add_action('wp_ajax_hph_dashboard_form_submit', [$this, 'ajax_submit_form']);
        add_action('wp_ajax_hph_dashboard_notifications', [$this, 'ajax_load_notifications']);
        add_action('wp_ajax_hph_dismiss_notification', [$this, 'ajax_dismiss_notification']);
        add_action('wp_ajax_hph_dashboard_search', [$this, 'ajax_dashboard_search']);
        add_action('wp_ajax_hph_get_listing_details', [$this, 'ajax_get_listing_details']);
        
        // Enhanced form actions
        add_action('wp_ajax_hph_dashboard_create_listing', [$this, 'ajax_create_listing']);
        add_action('wp_ajax_hph_dashboard_create_agent', [$this, 'ajax_create_agent']);
        add_action('wp_ajax_hph_dashboard_create_lead', [$this, 'ajax_create_lead']);
        add_action('wp_ajax_hph_dashboard_create_open_house', [$this, 'ajax_create_open_house']);
        add_action('wp_ajax_hph_dashboard_get_user_listings', [$this, 'ajax_get_user_listings']);
        add_action('wp_ajax_hph_dashboard_load_section', [$this, 'ajax_load_section']);
        add_action('wp_ajax_hph_dashboard_refresh_widget', [$this, 'ajax_refresh_widget']);
        add_action('wp_ajax_hph_delete_listing', [$this, 'ajax_delete_listing']);
        add_action('wp_ajax_hph_toggle_listing_status', [$this, 'ajax_toggle_listing_status']);
        
        // User role changes
        add_action('set_user_role', [$this, 'handle_user_role_change'], 10, 3);
        
        // Dashboard page creation
        add_action('after_switch_theme', [$this, 'create_dashboard_pages']);
        
        // Cleanup on deactivation
        register_deactivation_hook(HPH_PLUGIN_FILE, [$this, 'cleanup_dashboard']);
    }
    
    /**
     * Initialize core components
     */
    private function init_core_components(): void {
        // Initialize renderer
        // Renderer removed - presentation logic handled by theme
        
        // Initialize data provider
        require_once plugin_dir_path(__FILE__) . 'class-dashboard-data-provider.php';
        $this->data_provider = new Dashboard_Data_Provider($this->config);
        
        // Initialize permissions
        require_once plugin_dir_path(__FILE__) . 'class-dashboard-permissions.php';
        $this->permissions = new Dashboard_Permissions();
        
        // Initialize marketing suite integration
        require_once plugin_dir_path(__FILE__) . 'class-marketing-suite-integration.php';
        $this->marketing_suite = new Marketing_Suite_Integration();
        
        // Set data provider for marketing suite
        if ($this->data_provider) {
            $this->marketing_suite->set_data_provider($this->data_provider);
        }
        
        // Initialize forms integration
        require_once plugin_dir_path(__FILE__) . 'class-dashboard-listing-forms.php';
        $this->forms = new Dashboard_Listing_Forms();
        
        $this->marketing_suite->init();
        
        error_log('HPH Dashboard: Core components initialized');
    }
    
    /**
     * Register default dashboard sections
     */
    private function register_default_sections(): void {
        // Load base section class first
        $base_section_file = plugin_dir_path(__FILE__) . 'sections/class-base-dashboard-section.php';
        if (file_exists($base_section_file)) {
            require_once $base_section_file;
            error_log('HPH Dashboard: Base dashboard section loaded');
        } else {
            error_log('HPH Dashboard: Base dashboard section file not found: ' . $base_section_file);
            return;
        }
        
        $section_files = [
            'overview' => plugin_dir_path(__FILE__) . 'sections/class-overview-section.php',
            'listings' => plugin_dir_path(__FILE__) . 'sections/class-listings-section.php',
            'marketing' => plugin_dir_path(__FILE__) . 'sections/class-marketing-section.php',
            'analytics' => plugin_dir_path(__FILE__) . 'sections/class-analytics-section.php',
            'leads' => plugin_dir_path(__FILE__) . 'sections/class-leads-section.php',
            'profile' => plugin_dir_path(__FILE__) . 'sections/class-profile-section.php',
            'settings' => plugin_dir_path(__FILE__) . 'sections/class-settings-section.php'
        ];
        
        foreach ($section_files as $section_id => $file_path) {
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Create section class name
                $class_name = 'HappyPlace\\Dashboard\\Sections\\' . ucfirst($section_id) . '_Section';
                
                if (class_exists($class_name)) {
                    // Check if class has instance() method (singleton pattern)
                    if (method_exists($class_name, 'instance')) {
                        $section = $class_name::instance();
                    } else {
                        // Check if constructor is accessible
                        try {
                            $section = new $class_name();
                        } catch (\Error $e) {
                            error_log("HPH Dashboard: Cannot instantiate {$section_id} section: " . $e->getMessage());
                            continue;
                        }
                    }
                    
                    $this->register_section($section_id, $section);
                    error_log("HPH Dashboard: Registered {$section_id} section");
                } else {
                    error_log("HPH Dashboard: Section class {$class_name} not found");
                }
            } else {
                error_log("HPH Dashboard: Section file not found: {$file_path}");
            }
        }
    }
    
    /**
     * Register default dashboard widgets
     */
    private function register_default_widgets(): void {
        // Load base widget class first
        $base_widget_file = plugin_dir_path(__FILE__) . 'widgets/class-base-dashboard-widget.php';
        if (file_exists($base_widget_file)) {
            require_once $base_widget_file;
            error_log('HPH Dashboard: Base dashboard widget loaded');
        } else {
            error_log('HPH Dashboard: Base dashboard widget file not found: ' . $base_widget_file);
            return;
        }
        
        $widget_files = [
            'quick-stats' => plugin_dir_path(__FILE__) . 'widgets/class-quick-stats-widget.php',
            'recent-activity' => plugin_dir_path(__FILE__) . 'widgets/class-recent-activity-widget.php'
        ];
        
        foreach ($widget_files as $widget_id => $file_path) {
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Create widget class name
                $widget_class_name = str_replace('-', '_', $widget_id);
                $class_name = 'HappyPlace\\Dashboard\\Widgets\\' . 
                              ucfirst($widget_class_name) . '_Widget';
                
                if (class_exists($class_name)) {
                    // Check if class has instance() method (singleton pattern)
                    if (method_exists($class_name, 'instance')) {
                        $widget = $class_name::instance();
                    } else {
                        // Check if constructor is accessible
                        try {
                            $widget = new $class_name();
                        } catch (\Error $e) {
                            error_log("HPH Dashboard: Cannot instantiate {$widget_id} widget: " . $e->getMessage());
                            continue;
                        }
                    }
                    
                    $this->register_widget($widget_id, $widget);
                    error_log("HPH Dashboard: Registered {$widget_id} widget");
                } else {
                    error_log("HPH Dashboard: Widget class {$class_name} not found");
                }
            } else {
                error_log("HPH Dashboard: Widget file not found: {$file_path}");
            }
        }
    }
    
    /**
     * Register a dashboard section
     *
     * @param string $section_id Section identifier
     * @param object $section Section instance
     * @param array $config Section configuration
     */
    public function register_section(string $section_id, $section, array $config = []): void {
        $default_config = [
            'title' => ucfirst(str_replace(['-', '_'], ' ', $section_id)),
            'icon' => 'fas fa-dashboard',
            'priority' => 10,
            'capabilities' => ['read'],
            'menu_position' => null,
            'parent_section' => null,
            'visible' => true,
            'ajax_enabled' => true
        ];
        
        $this->sections[$section_id] = [
            'instance' => $section,
            'config' => wp_parse_args($config, $default_config)
        ];
        
        do_action('hph_dashboard_section_registered', $section_id, $section, $config);
    }
    
    /**
     * Register a dashboard widget
     *
     * @param string $widget_id Widget identifier
     * @param object $widget Widget instance
     * @param array $config Widget configuration
     */
    public function register_widget(string $widget_id, $widget, array $config = []): void {
        $default_config = [
            'title' => ucfirst(str_replace(['-', '_'], ' ', $widget_id)),
            'description' => '',
            'sections' => ['overview'], // Which sections to show in
            'priority' => 10,
            'capabilities' => ['read'],
            'cache_duration' => $this->config['cache_duration'],
            'refresh_enabled' => true,
            'collapsible' => true
        ];
        
        $this->widgets[$widget_id] = [
            'instance' => $widget,
            'config' => wp_parse_args($config, $default_config)
        ];
        
        do_action('hph_dashboard_widget_registered', $widget_id, $widget, $config);
    }
    
    /**
     * Get dashboard sections available to current user
     *
     * @param int $user_id User ID (optional, defaults to current user)
     * @return array Available sections
     */
    public function get_available_sections(int $user_id = 0): array {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $available_sections = [];
        
        foreach ($this->sections as $section_id => $section_data) {
            // Check permissions
            if (!$this->permissions->user_can_access_section($user_id, $section_id, $section_data['config'])) {
                continue;
            }
            
            // Check visibility
            if (!$section_data['config']['visible']) {
                continue;
            }
            
            $available_sections[$section_id] = [
                'id' => $section_id,
                'title' => $section_data['config']['title'],
                'icon' => $section_data['config']['icon'],
                'priority' => $section_data['config']['priority'],
                'ajax_enabled' => $section_data['config']['ajax_enabled']
            ];
        }
        
        // Sort by priority
        uasort($available_sections, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $available_sections;
    }
    
    /**
     * Get dashboard widgets for a section
     *
     * @param string $section_id Section identifier
     * @param int $user_id User ID (optional, defaults to current user)
     * @return array Available widgets
     */
    public function get_section_widgets(string $section_id, int $user_id = 0): array {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $available_widgets = [];
        
        foreach ($this->widgets as $widget_id => $widget_data) {
            // Check if widget is available for this section
            $widget_sections = $widget_data['config']['sections'];
            if (!in_array($section_id, $widget_sections) && !in_array('all', $widget_sections)) {
                continue;
            }
            
            // Check permissions
            if (!$this->permissions->user_can_access_widget($user_id, $widget_id, $widget_data['config'])) {
                continue;
            }
            
            $available_widgets[$widget_id] = [
                'id' => $widget_id,
                'title' => $widget_data['config']['title'],
                'description' => $widget_data['config']['description'],
                'priority' => $widget_data['config']['priority'],
                'collapsible' => $widget_data['config']['collapsible'],
                'refresh_enabled' => $widget_data['config']['refresh_enabled']
            ];
        }
        
        // Sort by priority
        uasort($available_widgets, function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
        
        return $available_widgets;
    }
    
    /**
     * Render dashboard section
     *
     * @param string $section_id Section identifier
     * @param array $args Additional arguments
     * @return string Rendered section HTML
     */
    public function render_section(string $section_id, array $args = []): string {
        if (!isset($this->sections[$section_id])) {
            return $this->render_error('Section not found: ' . $section_id);
        }
        
        $section_data = $this->sections[$section_id];
        
        // Check permissions
        if (!$this->permissions->user_can_access_section(get_current_user_id(), $section_id, $section_data['config'])) {
            return $this->render_error('Access denied to section: ' . $section_id);
        }
        
        // Use renderer to generate section HTML
        return $this->renderer->render_section($section_id, $section_data, $args);
    }
    
    /**
     * Render dashboard widget
     *
     * @param string $widget_id Widget identifier
     * @param array $args Additional arguments
     * @return string Rendered widget HTML
     */
    public function render_widget(string $widget_id, array $args = []): string {
        if (!isset($this->widgets[$widget_id])) {
            return $this->render_error('Widget not found: ' . $widget_id);
        }
        
        $widget_data = $this->widgets[$widget_id];
        
        // Check permissions
        if (!$this->permissions->user_can_access_widget(get_current_user_id(), $widget_id, $widget_data['config'])) {
            return $this->render_error('Access denied to widget: ' . $widget_id);
        }
        
        // Use renderer to generate widget HTML
        return $this->renderer->render_widget($widget_id, $widget_data, $args);
    }
    
    /**
     * Setup dashboard routes
     */
    public function setup_routes(): void {
        // Main dashboard route
        add_rewrite_rule(
            '^' . $this->config['dashboard_slug'] . '/?$',
            'index.php?pagename=' . $this->config['dashboard_slug'],
            'top'
        );
        
        // Section routes
        add_rewrite_rule(
            '^' . $this->config['dashboard_slug'] . '/([^/]+)/?$',
            'index.php?pagename=' . $this->config['dashboard_slug'] . '&dashboard_section=$matches[1]',
            'top'
        );
        
        // Add query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'dashboard_section';
            return $vars;
        });
    }
    
    /**
     * Initialize dashboard assets
     */
    private function init_assets(): void {
        // Assets moved to theme - plugin handles data only
        error_log('HPH Dashboard: Asset registration moved to theme layer');
    }
    
    /**
     * Check if current request is for dashboard
     *
     * @return bool True if dashboard request
     */
    public function is_dashboard_request(): bool {
        global $wp_query;
        
        // Check if we're on the dashboard page
        if (is_page($this->config['dashboard_slug'])) {
            return true;
        }
        
        // Check for dashboard query vars
        if (get_query_var('dashboard_section')) {
            return true;
        }
        
        // Check for AJAX dashboard requests
        if (wp_doing_ajax() && isset($_POST['action'])) {
            $ajax_actions = ['hph_dashboard_section', 'hph_dashboard_widget', 'hph_dashboard_action'];
            if (in_array($_POST['action'], $ajax_actions)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Maybe load dashboard template
     *
     * @param string $template Current template
     * @return string Template to load
     */
    public function maybe_load_dashboard_template(string $template): string {
        if (!$this->is_dashboard_request()) {
            return $template;
        }
        
        // Check permissions
        if (!$this->permissions->user_can_access_dashboard(get_current_user_id())) {
            return get_404_template();
        }
        
        // Look for dashboard template in theme first
        $theme_template = locate_template(['dashboard/dashboard-main.php', 'page-' . $this->config['dashboard_slug'] . '.php']);
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Use plugin template as fallback
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/dashboard-main.php';
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        return $template;
    }
    
    /**
     * Handle dashboard redirects
     */
    public function handle_dashboard_redirects(): void {
        if (!$this->is_dashboard_request()) {
            return;
        }
        
        // Redirect non-logged-in users to login
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        
        // Redirect users without dashboard access
        if (!$this->permissions->user_can_access_dashboard(get_current_user_id())) {
            wp_redirect(home_url());
            exit;
        }
    }
    
    /**
     * Enqueue dashboard assets for frontend
     */
    public function enqueue_dashboard_assets(): void {
        if (!$this->is_dashboard_request()) {
            return;
        }
        
        // Theme handles asset enqueueing - plugin provides data via filter
        add_filter('hph_dashboard_localize_data', function($data) {
            return array_merge($data, [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_dashboard_nonce'),
                'config' => $this->config,
                'user_id' => get_current_user_id(),
                'sections' => $this->get_available_sections(),
                'strings' => [
                    'loading' => __('Loading...', 'happy-place'),
                    'error' => __('An error occurred', 'happy-place'),
                    'success' => __('Success', 'happy-place'),
                    'confirm' => __('Are you sure?', 'happy-place')
                ]
            ]);
        });
    }
    
    /**
     * Enqueue dashboard assets for admin
     */
    public function enqueue_admin_dashboard_assets($hook): void {
        // Only load on relevant admin pages
        if (!in_array($hook, ['toplevel_page_happy-place', 'happy-place_page_dashboard'])) {
            return;
        }
        
        $this->enqueue_dashboard_assets();
    }
    
    /**
     * AJAX handler for loading sections
     */
    public function ajax_load_section(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $section_id = sanitize_text_field($_POST['section'] ?? '');
        $args = $_POST['args'] ?? [];
        
        if (empty($section_id)) {
            wp_send_json_error(['message' => 'Section ID required']);
        }
        
        $html = $this->render_section($section_id, $args);
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * AJAX handler for loading widgets
     */
    public function ajax_load_widget(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $widget_id = sanitize_text_field($_POST['widget'] ?? '');
        $args = $_POST['args'] ?? [];
        
        if (empty($widget_id)) {
            wp_send_json_error(['message' => 'Widget ID required']);
        }
        
        $html = $this->render_widget($widget_id, $args);
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * AJAX handler for dashboard actions
     */
    public function ajax_handle_action(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['dashboard_action'] ?? '');
        $data = $_POST['data'] ?? [];
        
        if (empty($action)) {
            wp_send_json_error(['message' => 'Action required']);
        }
        
        // Allow sections and widgets to handle their own actions
        $result = apply_filters('hph_dashboard_handle_action', null, $action, $data);
        
        if ($result !== null) {
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            } else {
                wp_send_json_success($result);
            }
        }
        
        wp_send_json_error(['message' => 'Unknown action: ' . $action]);
    }
    
    /**
     * Handle user role changes
     *
     * @param int $user_id User ID
     * @param string $role New role
     * @param array $old_roles Previous roles
     */
    public function handle_user_role_change(int $user_id, string $role, array $old_roles): void {
        // Clear user dashboard cache when role changes
        $this->clear_user_cache($user_id);
        
        do_action('hph_dashboard_user_role_changed', $user_id, $role, $old_roles);
    }
    
    /**
     * Create dashboard pages
     */
    public function create_dashboard_pages(): void {
        $dashboard_page = [
            'post_title' => __('Agent Dashboard', 'happy-place'),
            'post_content' => __('Agent dashboard - managed by Happy Place plugin.', 'happy-place'),
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $this->config['dashboard_slug']
        ];
        
        $existing_page = get_page_by_path($this->config['dashboard_slug']);
        if (!$existing_page) {
            $page_id = wp_insert_post($dashboard_page);
            if ($page_id) {
                update_option('hph_dashboard_page_id', $page_id);
                error_log('HPH Dashboard: Created dashboard page with ID ' . $page_id);
            }
        }
    }
    
    /**
     * Clear dashboard cache for user
     *
     * @param int $user_id User ID
     */
    public function clear_user_cache(int $user_id): void {
        $cache_keys = [
            "hph_dashboard_sections_{$user_id}",
            "hph_dashboard_widgets_{$user_id}",
            "hph_dashboard_data_{$user_id}"
        ];
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'hph_dashboard');
        }
        
        do_action('hph_dashboard_cache_cleared', $user_id);
    }
    
    /**
     * Render error message
     *
     * @param string $message Error message
     * @return string Error HTML
     */
    private function render_error(string $message): string {
        return '<div class="hph-dashboard-error"><p>' . esc_html($message) . '</p></div>';
    }
    
    /**
     * Cleanup dashboard on deactivation
     */
    public function cleanup_dashboard(): void {
        // Clear all dashboard caches
        wp_cache_flush_group('hph_dashboard');
        
        // Remove dashboard pages (optional - might want to keep them)
        // $page_id = get_option('hph_dashboard_page_id');
        // if ($page_id) {
        //     wp_delete_post($page_id, true);
        //     delete_option('hph_dashboard_page_id');
        // }
        
        error_log('HPH Dashboard: Cleanup completed');
    }
    
    /**
     * Get registered sections
     *
     * @return array Registered sections
     */
    public function get_sections(): array {
        return $this->sections;
    }
    
    /**
     * Get registered widgets
     *
     * @return array Registered widgets
     */
    public function get_widgets(): array {
        return $this->widgets;
    }
    
    /**
     * Get dashboard configuration
     *
     * @return array Dashboard config
     */
    public function get_config(): array {
        return $this->config;
    }
    
    /**
     * Get data provider instance
     *
     * @return Dashboard_Data_Provider|null
     */
    public function get_data_provider(): ?Dashboard_Data_Provider {
        return $this->data_provider;
    }
    
    
    /**
     * Get permissions manager instance
     *
     * @return Dashboard_Permissions|null
     */
    public function get_permissions(): ?Dashboard_Permissions {
        return $this->permissions;
    }
    
    /**
     * Handle AJAX form loading
     */
    public function ajax_load_form(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        
        if (empty($form_id)) {
            wp_send_json_error(['message' => __('Form ID required', 'happy-place')]);
        }
        
        // Get form definition from dashboard form integration
        if (class_exists('\\HappyPlace\\Dashboard\\Dashboard_Form_Integration')) {
            $form_integration = new \HappyPlace\Dashboard\Dashboard_Form_Integration();
            $form_definitions = $form_integration->get_dashboard_forms();
            
            if (!isset($form_definitions[$form_id])) {
                wp_send_json_error(['message' => __('Form not found', 'happy-place')]);
            }
            
            $form_config = $form_definitions[$form_id];
            
            // Render form using Form Manager
            if (class_exists('\\HappyPlace\\Forms\\Form_Manager')) {
                $form_html = \HappyPlace\Forms\Form_Manager::render_form($form_config['type'], [
                    'template' => $form_config['template'] ?? null,
                    'context' => 'dashboard'
                ]);
                
                if (is_wp_error($form_html)) {
                    wp_send_json_error(['message' => $form_html->get_error_message()]);
                }
                
                wp_send_json_success([
                    'html' => $form_html,
                    'config' => $form_config
                ]);
            }
        }
        
        wp_send_json_error(['message' => __('Form system not available', 'happy-place')]);
    }
    
    /**
     * Handle AJAX form submission
     */
    public function ajax_submit_form(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $form_id = sanitize_text_field($_POST['form_id'] ?? '');
        $section_id = sanitize_text_field($_POST['section_id'] ?? '');
        
        if (empty($form_id)) {
            wp_send_json_error(['message' => __('Form ID required', 'happy-place')]);
        }
        
        // Get form type from form integration
        if (class_exists('\\HappyPlace\\Dashboard\\Dashboard_Form_Integration')) {
            $form_integration = new \HappyPlace\Dashboard\Dashboard_Form_Integration();
            $form_definitions = $form_integration->get_dashboard_forms();
            
            if (isset($form_definitions[$form_id])) {
                $form_type = $form_definitions[$form_id]['type'];
                
                // Submit via Form Manager
                if (class_exists('\\HappyPlace\\Forms\\Form_Manager')) {
                    $handler = \HappyPlace\Forms\Form_Manager::get_handler($form_type);
                    
                    if ($handler) {
                        $result = $handler->handle_submission($_POST);
                        
                        if (is_wp_error($result)) {
                            wp_send_json_error([
                                'message' => $result->get_error_message(),
                                'errors' => $result->get_error_data()
                            ]);
                        }
                        
                        wp_send_json_success([
                            'message' => __('Form submitted successfully', 'happy-place'),
                            'action' => 'refresh_section',
                            'section_id' => $section_id,
                            'data' => $result
                        ]);
                    }
                }
            }
        }
        
        wp_send_json_error(['message' => __('Form submission failed', 'happy-place')]);
    }
    
    /**
     * Handle AJAX notifications loading
     */
    public function ajax_load_notifications(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in', 'happy-place')]);
        }
        
        // Get notifications from data provider
        if ($this->data_provider) {
            $notifications = $this->data_provider->get_notifications($user_id);
            
            wp_send_json_success([
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
        }
        
        // Fallback
        wp_send_json_success([
            'notifications' => [],
            'count' => 0
        ]);
    }
    
    /**
     * Handle AJAX notification dismissal
     */
    public function ajax_dismiss_notification(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $notification_id = sanitize_text_field($_POST['notification_id'] ?? '');
        $user_id = get_current_user_id();
        
        if (empty($notification_id) || !$user_id) {
            wp_send_json_error(['message' => __('Invalid request', 'happy-place')]);
        }
        
        // Store dismissed notification
        $dismissed = get_user_meta($user_id, 'hph_dismissed_notifications', true) ?: [];
        
        if (!in_array($notification_id, $dismissed)) {
            $dismissed[] = $notification_id;
            update_user_meta($user_id, 'hph_dismissed_notifications', $dismissed);
        }
        
        wp_send_json_success(['message' => __('Notification dismissed', 'happy-place')]);
    }
    
    /**
     * Handle AJAX dashboard search
     */
    public function ajax_dashboard_search(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (strlen($query) < 3) {
            wp_send_json_error(['message' => __('Search query too short', 'happy-place')]);
        }
        
        $results = [];
        
        // Search listings
        $listings = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => 10,
            's' => $query,
            'post_status' => 'publish'
        ]);
        
        foreach ($listings as $listing) {
            $results['listings'][] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'url' => get_permalink($listing->ID)
            ];
        }
        
        // Search leads
        $leads = get_posts([
            'post_type' => 'lead',
            'posts_per_page' => 10,
            's' => $query,
            'post_status' => 'any'
        ]);
        
        foreach ($leads as $lead) {
            $results['leads'][] = [
                'id' => $lead->ID,
                'name' => $lead->post_title,
                'url' => admin_url('post.php?post=' . $lead->ID . '&action=edit')
            ];
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle AJAX listing details request
     */
    public function ajax_get_listing_details(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Listing ID required', 'happy-place')]);
        }
        
        // Check if listing exists and user has access
        $listing = get_post($listing_id);
        
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => __('Listing not found', 'happy-place')]);
        }
        
        // Get listing details
        $details = [
            'id' => $listing_id,
            'title' => $listing->post_title,
            'address' => get_post_meta($listing_id, 'listing_address', true),
            'price' => get_post_meta($listing_id, 'listing_price', true),
            'bedrooms' => get_post_meta($listing_id, 'bedrooms', true),
            'bathrooms' => get_post_meta($listing_id, 'bathrooms', true),
            'square_feet' => get_post_meta($listing_id, 'square_feet', true)
        ];
        
        wp_send_json_success($details);
    }
    
    /**
     * Handle AJAX create listing request
     */
    public function ajax_create_listing(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'draft');
        $property_type = sanitize_text_field($_POST['property_type'] ?? 'house');
        $bedrooms = sanitize_text_field($_POST['bedrooms'] ?? '');
        $bathrooms = sanitize_text_field($_POST['bathrooms'] ?? '');
        $address = sanitize_text_field($_POST['address'] ?? '');
        $square_feet = intval($_POST['square_feet'] ?? 0);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (empty($title) || empty($address) || $price <= 0) {
            wp_send_json_error(['message' => __('Please fill in all required fields', 'happy-place')]);
        }
        
        // Create listing post
        $listing_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'listing',
            'post_author' => get_current_user_id()
        ]);
        
        if (is_wp_error($listing_id)) {
            wp_send_json_error(['message' => __('Failed to create listing', 'happy-place')]);
        }
        
        // Update meta fields
        update_post_meta($listing_id, 'price', $price);
        update_post_meta($listing_id, 'listing_status', $status);
        update_post_meta($listing_id, 'property_type', $property_type);
        update_post_meta($listing_id, 'bedrooms', $bedrooms);
        update_post_meta($listing_id, 'bathrooms', $bathrooms);
        update_post_meta($listing_id, 'address', $address);
        update_post_meta($listing_id, 'square_feet', $square_feet);
        
        // Associate with current user's agent profile
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        if ($agent_id) {
            update_post_meta($listing_id, 'listing_agent', $agent_id);
        }
        
        wp_send_json_success([
            'message' => __('Listing created successfully', 'happy-place'),
            'listing_id' => $listing_id,
            'redirect' => get_permalink($listing_id)
        ]);
    }
    
    /**
     * Handle AJAX create agent request
     */
    public function ajax_create_agent(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $license_number = sanitize_text_field($_POST['license_number'] ?? '');
        $specialty = sanitize_text_field($_POST['specialty'] ?? '');
        $bio = sanitize_textarea_field($_POST['bio'] ?? '');
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            wp_send_json_error(['message' => __('Please fill in all required fields', 'happy-place')]);
        }
        
        // Create agent post
        $agent_id = wp_insert_post([
            'post_title' => $first_name . ' ' . $last_name,
            'post_content' => $bio,
            'post_status' => 'publish',
            'post_type' => 'agent',
            'post_author' => get_current_user_id()
        ]);
        
        if (is_wp_error($agent_id)) {
            wp_send_json_error(['message' => __('Failed to create agent profile', 'happy-place')]);
        }
        
        // Update meta fields
        update_post_meta($agent_id, 'first_name', $first_name);
        update_post_meta($agent_id, 'last_name', $last_name);
        update_post_meta($agent_id, 'email', $email);
        update_post_meta($agent_id, 'phone', $phone);
        update_post_meta($agent_id, 'license_number', $license_number);
        update_post_meta($agent_id, 'specialty', $specialty);
        
        wp_send_json_success([
            'message' => __('Agent profile created successfully', 'happy-place'),
            'agent_id' => $agent_id
        ]);
    }
    
    /**
     * Handle AJAX create lead request
     */
    public function ajax_create_lead(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $source = sanitize_text_field($_POST['source'] ?? 'website');
        $budget_min = intval($_POST['budget_min'] ?? 0);
        $budget_max = intval($_POST['budget_max'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Please fill in all required fields', 'happy-place')]);
        }
        
        // Create lead post
        $lead_id = wp_insert_post([
            'post_title' => $name,
            'post_content' => $notes,
            'post_status' => 'publish',
            'post_type' => 'lead',
            'post_author' => get_current_user_id()
        ]);
        
        if (is_wp_error($lead_id)) {
            wp_send_json_error(['message' => __('Failed to create lead', 'happy-place')]);
        }
        
        // Update meta fields
        update_post_meta($lead_id, 'email', $email);
        update_post_meta($lead_id, 'phone', $phone);
        update_post_meta($lead_id, 'source', $source);
        update_post_meta($lead_id, 'budget_min', $budget_min);
        update_post_meta($lead_id, 'budget_max', $budget_max);
        update_post_meta($lead_id, 'status', 'new');
        
        // Associate with current user's agent profile
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        if ($agent_id) {
            update_post_meta($lead_id, 'assigned_agent', $agent_id);
        }
        
        wp_send_json_success([
            'message' => __('Lead created successfully', 'happy-place'),
            'lead_id' => $lead_id
        ]);
    }
    
    /**
     * Handle AJAX create open house request
     */
    public function ajax_create_open_house(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? 'Open House');
        $date = sanitize_text_field($_POST['date'] ?? '');
        $start_time = sanitize_text_field($_POST['start_time'] ?? '');
        $end_time = sanitize_text_field($_POST['end_time'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'scheduled');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        
        if (!$listing_id || empty($date) || empty($start_time) || empty($end_time)) {
            wp_send_json_error(['message' => __('Please fill in all required fields', 'happy-place')]);
        }
        
        // Verify listing exists
        $listing = get_post($listing_id);
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => __('Invalid listing selected', 'happy-place')]);
        }
        
        // Create open house post
        $openhouse_id = wp_insert_post([
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'open_house',
            'post_author' => get_current_user_id()
        ]);
        
        if (is_wp_error($openhouse_id)) {
            wp_send_json_error(['message' => __('Failed to create open house', 'happy-place')]);
        }
        
        // Update meta fields
        update_post_meta($openhouse_id, 'listing', $listing_id);
        update_post_meta($openhouse_id, 'open_house_date', $date);
        update_post_meta($openhouse_id, 'start_time', $start_time);
        update_post_meta($openhouse_id, 'end_time', $end_time);
        update_post_meta($openhouse_id, 'status', $status);
        
        // Associate with current user's agent profile
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        if ($agent_id) {
            update_post_meta($openhouse_id, 'hosting_agent', $agent_id);
        }
        
        wp_send_json_success([
            'message' => __('Open house scheduled successfully', 'happy-place'),
            'openhouse_id' => $openhouse_id
        ]);
    }
    
    /**
     * Handle AJAX get user listings request
     */
    public function ajax_get_user_listings(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $user_id = get_current_user_id();
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        
        if (!$agent_id) {
            wp_send_json_error(['message' => __('No agent profile found', 'happy-place')]);
        }
        
        $listings = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        $formatted_listings = [];
        foreach ($listings as $listing) {
            $formatted_listings[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title
            ];
        }
        
        wp_send_json_success(['listings' => $formatted_listings]);
    }
    
    /**
     * Handle AJAX refresh widget request
     */
    public function ajax_refresh_widget(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');
        
        if (empty($widget_id)) {
            wp_send_json_error(['message' => __('Widget ID required', 'happy-place')]);
        }
        
        // Get widget data (simplified version)
        $content = '<p>' . __('Widget refreshed successfully', 'happy-place') . '</p>';
        
        wp_send_json_success(['content' => $content]);
    }
    
    /**
     * Handle AJAX delete listing request
     */
    public function ajax_delete_listing(): void {
        check_ajax_referer('hph_dashboard_nonce', 'nonce');
        
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        
        if (!$listing_id) {
            wp_send_json_error(['message' => __('Listing ID required', 'happy-place')]);
        }
        
        // Check if listing exists and user owns it
        $listing = get_post($listing_id);
        
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => __('Listing not found', 'happy-place')]);
        }
        
        if ($listing->post_author != get_current_user_id() && !current_user_can('delete_others_posts')) {
            wp_send_json_error(['message' => __('You can only delete your own listings', 'happy-place')]);
        }
        
        // Delete the listing
        $result = wp_delete_post($listing_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => __('Listing deleted successfully', 'happy-place')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete listing', 'happy-place')]);
        }
    }
    
    /**
     * Handle AJAX toggle listing status request
     */
    public function ajax_toggle_listing_status(): void {
        check_ajax_referer('hph_dashboard', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $listing_id = intval($_POST['listing_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');
        
        if (!$listing_id || !$new_status) {
            wp_send_json_error(['message' => __('Listing ID and status required', 'happy-place')]);
        }
        
        // Validate status
        $allowed_statuses = ['publish', 'draft', 'pending'];
        if (!in_array($new_status, $allowed_statuses)) {
            wp_send_json_error(['message' => __('Invalid status', 'happy-place')]);
        }
        
        // Check if listing exists and user owns it
        $listing = get_post($listing_id);
        
        if (!$listing || $listing->post_type !== 'listing') {
            wp_send_json_error(['message' => __('Listing not found', 'happy-place')]);
        }
        
        if ($listing->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => __('You can only edit your own listings', 'happy-place')]);
        }
        
        // Update the listing status
        $result = wp_update_post([
            'ID' => $listing_id,
            'post_status' => $new_status
        ]);
        
        if ($result && !is_wp_error($result)) {
            wp_send_json_success([
                'message' => __('Listing status updated successfully', 'happy-place'),
                'new_status' => $new_status
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to update listing status', 'happy-place')]);
        }
    }
}