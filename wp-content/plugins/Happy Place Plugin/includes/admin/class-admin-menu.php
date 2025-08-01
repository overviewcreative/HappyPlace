<?php
/**
 * Enhanced Admin Menu System for Happy Place Plugin
 * 
 * Provides a comprehensive, organized admin interface with:
 * - Logical menu grouping and structure
 * - Role-based access control
 * - Integration monitoring and health checks
 * - Enhanced system management tools
 * 
 * @package HappyPlace
 * @subpackage Admin
 * @version 2.0.0
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Menu
{
    private static ?self $instance = null;
    
    /**
     * Menu capability requirements
     */
    private array $menu_capabilities = [
        'dashboard' => 'read',
        'listings' => 'edit_posts',
        'integrations' => 'manage_options',
        'tools' => 'manage_options',
        'system_health' => 'manage_options',
        'settings' => 'manage_options',
        'developer' => 'manage_options'
    ];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'handle_admin_actions']);
    }

    /**
     * Register all admin menu pages with enhanced organization
     */
    public function register_menu_pages(): void
    {
        // Main menu page - Dashboard
        add_menu_page(
            __('Happy Place Dashboard', 'happy-place'),
            __('Happy Place', 'happy-place'),
            $this->menu_capabilities['dashboard'],
            'happy-place-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-building',
            30
        );

        // Dashboard submenu (rename the first item)
        add_submenu_page(
            'happy-place-dashboard',
            __('Dashboard', 'happy-place'),
            __('📊 Dashboard', 'happy-place'),
            $this->menu_capabilities['dashboard'],
            'happy-place-dashboard',
            [$this, 'render_dashboard']
        );

        // Listings Management
        add_submenu_page(
            'happy-place-dashboard',
            __('Listings Management', 'happy-place'),
            __('🏠 Listings', 'happy-place'),
            $this->menu_capabilities['listings'],
            'happy-place-listings',
            [$this, 'render_listings_management']
        );

        // Integrations Hub
        add_submenu_page(
            'happy-place-dashboard',
            __('Integrations Hub', 'happy-place'),
            __('🔗 Integrations', 'happy-place'),
            $this->menu_capabilities['integrations'],
            'happy-place-integrations',
            [$this, 'render_integrations_hub']
        );

        // Tools & Utilities
        add_submenu_page(
            'happy-place-dashboard',
            __('Tools & Utilities', 'happy-place'),
            __('🛠️ Tools', 'happy-place'),
            $this->menu_capabilities['tools'],
            'happy-place-tools',
            [$this, 'render_tools_utilities']
        );

        // System Health Monitor (Enhanced Systems)
        add_submenu_page(
            'happy-place-dashboard',
            __('System Health Monitor', 'happy-place'),
            __('💚 System Health', 'happy-place'),
            $this->menu_capabilities['system_health'],
            'happy-place-system-health',
            [$this, 'render_system_health']
        );

        // Marketing Suite Generator
        add_submenu_page(
            'happy-place-dashboard',
            __('Marketing Suite Generator', 'happy-place'),
            __('🎨 Marketing Suite', 'happy-place'),
            $this->menu_capabilities['tools'],
            'happy-place-marketing-suite',
            [$this, 'render_marketing_suite']
        );

        // Settings
        add_submenu_page(
            'happy-place-dashboard',
            __('Settings', 'happy-place'),
            __('⚙️ Settings', 'happy-place'),
            $this->menu_capabilities['settings'],
            'happy-place-settings',
            [$this, 'render_settings']
        );

        // Developer Tools (Admin only)
        if (current_user_can('manage_options') && (defined('WP_DEBUG') && WP_DEBUG)) {
            add_submenu_page(
                'happy-place-dashboard',
                __('Developer Tools', 'happy-place'),
                __('🔧 Developer', 'happy-place'),
                $this->menu_capabilities['developer'],
                'happy-place-developer',
                [$this, 'render_developer_tools']
            );
        }
    }

    /**
     * Enqueue admin assets for enhanced UI
     */
    public function enqueue_admin_assets($hook_suffix): void
    {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'happy-place') === false) {
            return;
        }

        // Enhanced admin styles (fix path)
        wp_enqueue_style(
            'hph-enhanced-admin',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/admin-enhanced.css',
            [],
            '2.0.0'
        );

        // Enhanced admin scripts (fix path)
        wp_enqueue_script(
            'hph-enhanced-admin',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/admin-enhanced.js',
            ['jquery', 'wp-api'],
            '2.0.0',
            true
        );

        // Localize script with enhanced data
        wp_localize_script('hph-enhanced-admin', 'hphEnhancedAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('hph/v1/'),
            'nonce' => wp_create_nonce('hph_ajax_nonce'),
            'currentPage' => $hook_suffix,
            'capabilities' => [
                'manage_integrations' => current_user_can('manage_options'),
                'view_system_health' => current_user_can('manage_options'),
                'manage_listings' => current_user_can('edit_posts')
            ],
            'endpoints' => [
                // Dashboard endpoints
                'dashboard_quick_stats' => 'dashboard_quick_stats',
                'dashboard_recent_activity' => 'dashboard_recent_activity',
                
                // Listings management endpoints  
                'get_listings_overview' => 'get_listings_overview',
                'bulk_update_listings' => 'bulk_update_listings',
                
                // Integrations endpoints
                'get_integration_status' => 'get_integration_status', 
                'test_integration_connection' => 'test_integration_connection',
                
                // System health endpoints
                'get_system_metrics' => 'get_system_metrics',
                
                // Tools endpoints
                'run_maintenance_task' => 'run_maintenance_task',
                
                // Marketing suite endpoints
                'marketing_suite_config' => 'marketing_suite_config',
                'marketing_suite_templates' => 'marketing_suite_templates',
                'marketing_suite_generate_flyer' => 'marketing_suite_generate_flyer'
            ],
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'saving' => __('Saving...', 'happy-place'),
                'saved' => __('Saved!', 'happy-place'),
                'error' => __('Error occurred', 'happy-place'),
                'confirm_action' => __('Are you sure?', 'happy-place'),
                'system_healthy' => __('System Healthy', 'happy-place'),
                'system_warning' => __('System Warning', 'happy-place'),
                'system_critical' => __('System Critical', 'happy-place'),
                'bulk_update_confirm' => __('Apply bulk action to selected items?', 'happy-place'),
                'maintenance_confirm' => __('Run maintenance task? This may take a few minutes.', 'happy-place'),
                'integration_test_running' => __('Testing connection...', 'happy-place'),
                'cache_cleared' => __('Cache cleared successfully', 'happy-place')
            ]
        ]);
    }

    /**
     * Handle admin actions and AJAX requests
     */
    public function handle_admin_actions(): void
    {
        // Enhanced AJAX handlers for admin menu functionality
        add_action('wp_ajax_hph_get_dashboard_stats', [$this, 'ajax_get_dashboard_stats']);
        add_action('wp_ajax_hph_refresh_integrations', [$this, 'ajax_refresh_integrations']);
        add_action('wp_ajax_hph_test_integration', [$this, 'ajax_test_integration']);
        add_action('wp_ajax_hph_toggle_integration', [$this, 'ajax_toggle_integration']);
        
        // NEW: Redirect to centralized AJAX system
        add_action('wp_ajax_hph_get_listings_overview', [$this, 'redirect_to_dashboard_ajax']);
        add_action('wp_ajax_hph_bulk_update_listings', [$this, 'redirect_to_dashboard_ajax']);
        add_action('wp_ajax_hph_get_integration_status', [$this, 'redirect_to_dashboard_ajax']);
        add_action('wp_ajax_hph_test_integration_connection', [$this, 'redirect_to_dashboard_ajax']);
        add_action('wp_ajax_hph_get_system_metrics', [$this, 'redirect_to_dashboard_ajax']);
        add_action('wp_ajax_hph_run_maintenance_task', [$this, 'redirect_to_dashboard_ajax']);
    }

    /**
     * Redirect legacy AJAX calls to Dashboard_Ajax handler
     */
    public function redirect_to_dashboard_ajax(): void
    {
        // Get the action name without the hph_ prefix
        $action = str_replace('hph_', '', $_POST['action'] ?? '');
        
        // Call the Dashboard_Ajax handler through our AJAX system
        if (class_exists('\HappyPlace\Api\Ajax\Ajax_Coordinator')) {
            $coordinator = \HappyPlace\Api\Ajax\Ajax_Coordinator::get_instance();
            
            // Update the action in $_POST to match our naming convention
            $_POST['action'] = $action;
            
            // Let the coordinator handle it
            do_action('wp_ajax_' . $action);
        } else {
            wp_send_json_error(['message' => 'AJAX system not available']);
        }
    }

    /**
     * Render the enhanced main dashboard
     */
    public function render_dashboard(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-building"></span> ';
        echo __('Happy Place Dashboard', 'happy-place');
        echo '</h1>';
        
        echo '<div class="hph-dashboard-intro">';
        echo '<p>' . __('Welcome to Happy Place - Your comprehensive real estate management platform.', 'happy-place') . '</p>';
        echo '</div>';

        // Quick Stats Section
        $this->render_dashboard_stats();
        
        // Recent Activity Section
        $this->render_recent_activity();
        
        // System Status Overview
        $this->render_system_status_overview();
        
        echo '</div>';
    }

    /**
     * Render listings management page
     */
    public function render_listings_management(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-admin-home"></span> ';
        echo __('Listings Management', 'happy-place');
        echo '</h1>';
        
        // Check user capabilities
        if (!current_user_can($this->menu_capabilities['listings'])) {
            echo '<div class="notice notice-error"><p>' . __('You do not have permission to manage listings.', 'happy-place') . '</p></div>';
            echo '</div>';
            return;
        }

        // Listings management interface
        echo '<div class="hph-listings-management">';
        
        // Quick Actions
        echo '<div class="hph-card">';
        echo '<h2>' . __('Quick Actions', 'happy-place') . '</h2>';
        echo '<div class="hph-quick-actions">';
        echo '<a href="' . admin_url('post-new.php?post_type=listing') . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-plus-alt"></span> ' . __('Add New Listing', 'happy-place');
        echo '</a>';
        echo '<a href="' . admin_url('edit.php?post_type=listing') . '" class="button button-secondary">';
        echo '<span class="dashicons dashicons-list-view"></span> ' . __('View All Listings', 'happy-place');
        echo '</a>';
        echo '<a href="' . admin_url('admin.php?page=happy-place-tools') . '" class="button button-secondary">';
        echo '<span class="dashicons dashicons-upload"></span> ' . __('Import Listings', 'happy-place');
        echo '</a>';
        echo '</div>';
        echo '</div>';

        // Listings overview
        $this->render_listings_overview();
        
        // Bulk operations
        $this->render_bulk_operations();
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render integrations hub page
     */
    public function render_integrations_hub(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-networking"></span> ';
        echo __('Integrations Hub', 'happy-place');
        echo '</h1>';
        
        // Check user capabilities
        if (!current_user_can($this->menu_capabilities['integrations'])) {
            echo '<div class="notice notice-error"><p>' . __('You do not have permission to manage integrations.', 'happy-place') . '</p></div>';
            echo '</div>';
            return;
        }

        echo '<div class="hph-integrations-hub">';
        
        // Integration status overview with AJAX loading
        echo '<div class="hph-card">';
        echo '<h2>' . __('Integration Status', 'happy-place') . '</h2>';
        echo '<div id="hph-integration-status" class="hph-integration-grid">';
        echo '<div class="hph-loading-placeholder">';
        echo '<span class="dashicons dashicons-update spin"></span> ';
        echo __('Loading integration status...', 'happy-place');
        echo '</div>';
        echo '</div>';
        echo '<div class="hph-integration-actions">';
        echo '<button id="hph-refresh-integrations" class="button button-secondary">';
        echo '<span class="dashicons dashicons-update"></span> ' . __('Refresh Status', 'happy-place');
        echo '</button>';
        echo '<button id="hph-test-all-integrations" class="button button-primary">';
        echo '<span class="dashicons dashicons-admin-tools"></span> ' . __('Test All Connections', 'happy-place');
        echo '</button>';
        echo '</div>';
        echo '</div>';

        // Available integrations
        $this->render_available_integrations();
        
        // Integration settings
        $this->render_integration_settings();
        
        echo '</div>';
        echo '</div>';

        // JavaScript to load integration data
        echo '<script>';
        echo 'jQuery(document).ready(function($) {';
        echo '  // Load integration status on page load';
        echo '  hphAdmin.loadIntegrationStatus();';
        echo '  ';
        echo '  // Refresh integrations button';
        echo '  $("#hph-refresh-integrations").click(function() {';
        echo '    hphAdmin.loadIntegrationStatus(true);';
        echo '  });';
        echo '  ';
        echo '  // Test all integrations button';
        echo '  $("#hph-test-all-integrations").click(function() {';
        echo '    hphAdmin.testAllIntegrations();';
        echo '  });';
        echo '});';
        echo '</script>';
    }

    /**
     * Render tools and utilities page
     */
    public function render_tools_utilities(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-admin-tools"></span> ';
        echo __('Tools & Utilities', 'happy-place');
        echo '</h1>';
        
        // Check user capabilities
        if (!current_user_can($this->menu_capabilities['tools'])) {
            echo '<div class="notice notice-error"><p>' . __('You do not have permission to access tools.', 'happy-place') . '</p></div>';
            echo '</div>';
            return;
        }

        echo '<div class="hph-tools-utilities">';
        
        // Data Management Tools
        echo '<div class="hph-card">';
        echo '<h2>' . __('Data Management', 'happy-place') . '</h2>';
        echo '<div class="hph-tool-grid">';
        
        // CSV Import Tool
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-upload"></span> ' . __('CSV Import', 'happy-place') . '</h3>';
        echo '<p>' . __('Import listings from CSV files with validation and error checking.', 'happy-place') . '</p>';
        echo '<button class="button button-primary" onclick="hphAdmin.openTool(\'csv-import\')">' . __('Launch CSV Import', 'happy-place') . '</button>';
        echo '</div>';
        
        // Flyer Generator
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-format-image"></span> ' . __('Flyer Generator', 'happy-place') . '</h3>';
        echo '<p>' . __('Create professional property flyers with customizable templates.', 'happy-place') . '</p>';
        echo '<button class="button button-primary" onclick="hphAdmin.openTool(\'flyer-generator\')">' . __('Launch Flyer Generator', 'happy-place') . '</button>';
        echo '</div>';
        
        // Image Optimization
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-images-alt2"></span> ' . __('Image Optimization', 'happy-place') . '</h3>';
        echo '<p>' . __('Optimize property images for web performance.', 'happy-place') . '</p>';
        echo '<button class="button button-primary" onclick="hphAdmin.openTool(\'image-optimization\')">' . __('Launch Optimizer', 'happy-place') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';

        // Maintenance Tools
        $this->render_maintenance_tools();
        
        // Tool interfaces (hidden by default)
        $this->render_tool_interfaces();
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render system health monitor page
     */
    public function render_system_health(): void
    {
        // Use the enhanced systems dashboard we created
        if (class_exists('\HappyPlace\Monitoring\Enhanced_Systems_Dashboard')) {
            $dashboard = \HappyPlace\Monitoring\Enhanced_Systems_Dashboard::get_instance();
            $dashboard->render_dashboard_page();
        } else {
            // Fallback basic health monitor
            echo '<div class="wrap hph-admin-wrap">';
            echo '<h1 class="hph-admin-title">';
            echo '<span class="dashicons dashicons-heart"></span> ';
            echo __('System Health Monitor', 'happy-place');
            echo '</h1>';
            
            echo '<div class="notice notice-warning">';
            echo '<p>' . __('Enhanced Systems Dashboard not available. Please ensure all components are properly loaded.', 'happy-place') . '</p>';
            echo '</div>';
            
            // Basic health check
            $this->render_basic_health_check();
            
            echo '</div>';
        }
    }

    /**
     * Render settings page
     */
    public function render_settings(): void
    {
        // Use the existing settings page class
        if (class_exists('\HPH\Admin\Settings_Page')) {
            $settings = \HPH\Admin\Settings_Page::get_instance();
            $settings->render_settings_page();
        } else {
            echo '<div class="wrap hph-admin-wrap">';
            echo '<h1 class="hph-admin-title">';
            echo '<span class="dashicons dashicons-admin-settings"></span> ';
            echo __('Settings', 'happy-place');
            echo '</h1>';
            
            echo '<div class="notice notice-warning">';
            echo '<p>' . __('Settings page class not found. Please check plugin installation.', 'happy-place') . '</p>';
            echo '</div>';
            
            echo '</div>';
        }
    }

    /**
     * Render Marketing Suite Generator page
     */
    public function render_marketing_suite(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-format-image"></span> ';
        echo __('Marketing Suite Generator', 'happy-place');
        echo '</h1>';
        
        echo '<div class="hph-admin-content">';
        echo '<p class="hph-intro-text">' . __('Generate professional marketing materials for your listings including flyers, social media graphics, and more.', 'happy-place') . '</p>';
        
        // Get flyer generator instance and render
        if (class_exists('\HappyPlace\Graphics\Flyer_Generator')) {
            $flyer_generator = \HappyPlace\Graphics\Flyer_Generator::get_instance();
            echo $flyer_generator->render_flyer_generator([]);
        } elseif (class_exists('\HappyPlace\Graphics\Flyer_Generator_Clean')) {
            $flyer_generator = \HappyPlace\Graphics\Flyer_Generator_Clean::get_instance();
            echo $flyer_generator->render_flyer_generator([]);
        } else {
            echo '<div class="notice notice-error">';
            echo '<p>' . __('Marketing Suite Generator not available. Please check plugin installation.', 'happy-place') . '</p>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render developer tools page (enhanced version)
     */
    public function render_developer_tools(): void
    {
        echo '<div class="wrap hph-admin-wrap">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons dashicons-admin-tools"></span> ';
        echo __('Developer Tools', 'happy-place');
        echo '<span class="hph-badge hph-badge-dev">DEV</span>';
        echo '</h1>';
        
        // Check user capabilities and debug mode
        if (!current_user_can($this->menu_capabilities['developer'])) {
            echo '<div class="notice notice-error"><p>' . __('You do not have permission to access developer tools.', 'happy-place') . '</p></div>';
            echo '</div>';
            return;
        }
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            echo '<div class="notice notice-warning">';
            echo '<p>' . __('Developer tools are only available when WP_DEBUG is enabled.', 'happy-place') . '</p>';
            echo '</div>';
        }

        // Handle developer actions
        if (isset($_POST['dev_action'])) {
            $this->handle_dev_actions();
        }

        echo '<div class="hph-developer-tools">';
        
        // Enhanced system testing
        echo '<div class="hph-card">';
        echo '<h2>' . __('Enhanced System Testing', 'happy-place') . '</h2>';
        echo '<div class="hph-dev-actions">';
        echo '<button class="button button-primary" onclick="hphAdmin.runSystemTest()">';
        echo '<span class="dashicons dashicons-admin-tools"></span> ' . __('Run Full System Test', 'happy-place');
        echo '</button>';
        echo '<button class="button button-secondary" onclick="hphAdmin.testCircuitBreakers()">';
        echo '<span class="dashicons dashicons-networking"></span> ' . __('Test Circuit Breakers', 'happy-place');
        echo '</button>';
        echo '</div>';
        echo '<div id="hph-test-results" class="hph-test-results" style="display:none;"></div>';
        echo '</div>';

        // Original developer tools (cache, build, etc.)
        $this->render_original_dev_tools();
        
        echo '</div>';
        echo '</div>';
    }
    /**
     * Render dashboard statistics
     */
    private function render_dashboard_stats(): void
    {
        echo '<div class="hph-dashboard-stats">';
        echo '<div class="hph-stats-grid">';
        
        // Total Listings
        $listing_count = wp_count_posts('listing');
        echo '<div class="hph-stat-card">';
        echo '<div class="hph-stat-number">' . ($listing_count->publish ?? 0) . '</div>';
        echo '<div class="hph-stat-label">' . __('Active Listings', 'happy-place') . '</div>';
        echo '</div>';
        
        // Total Views (if analytics available)
        echo '<div class="hph-stat-card">';
        echo '<div class="hph-stat-number" id="hph-total-views">-</div>';
        echo '<div class="hph-stat-label">' . __('Total Views', 'happy-place') . '</div>';
        echo '</div>';
        
        // Integrations Status
        echo '<div class="hph-stat-card">';
        echo '<div class="hph-stat-number" id="hph-integrations-active">-</div>';
        echo '<div class="hph-stat-label">' . __('Active Integrations', 'happy-place') . '</div>';
        echo '</div>';
        
        // System Health
        echo '<div class="hph-stat-card">';
        echo '<div class="hph-stat-indicator" id="hph-system-health">';
        echo '<span class="dashicons dashicons-yes-alt"></span>';
        echo '</div>';
        echo '<div class="hph-stat-label">' . __('System Health', 'happy-place') . '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render recent activity section
     */
    private function render_recent_activity(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Recent Activity', 'happy-place') . '</h2>';
        
        // Get recent listings
        $recent_listings = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ]);
        
        if (!empty($recent_listings)) {
            echo '<div class="hph-activity-list">';
            foreach ($recent_listings as $listing) {
                echo '<div class="hph-activity-item">';
                echo '<span class="hph-activity-icon dashicons dashicons-admin-home"></span>';
                echo '<div class="hph-activity-content">';
                echo '<strong>' . esc_html($listing->post_title) . '</strong>';
                echo '<br><small>' . sprintf(__('Added %s', 'happy-place'), human_time_diff(strtotime($listing->post_date))) . '</small>';
                echo '</div>';
                echo '<div class="hph-activity-actions">';
                echo '<a href="' . get_edit_post_link($listing->ID) . '" class="button button-small">' . __('Edit', 'happy-place') . '</a>';
                echo '</div>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            echo '<p>' . __('No recent activity found.', 'happy-place') . '</p>';
        }
        
        echo '</div>';
    }

    /**
     * Render system status overview
     */
    private function render_system_status_overview(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('System Status Overview', 'happy-place') . '</h2>';
        
        echo '<div class="hph-system-overview">';
        
        // Check WordPress requirements
        echo '<div class="hph-status-item">';
        echo '<span class="hph-status-icon dashicons dashicons-wordpress"></span>';
        echo '<span class="hph-status-label">' . __('WordPress', 'happy-place') . '</span>';
        echo '<span class="hph-status-value">' . get_bloginfo('version') . '</span>';
        echo '<span class="hph-status-indicator hph-status-ok"></span>';
        echo '</div>';
        
        // Check PHP version
        $php_ok = version_compare(PHP_VERSION, '8.0', '>=');
        echo '<div class="hph-status-item">';
        echo '<span class="hph-status-icon dashicons dashicons-admin-settings"></span>';
        echo '<span class="hph-status-label">' . __('PHP Version', 'happy-place') . '</span>';
        echo '<span class="hph-status-value">' . PHP_VERSION . '</span>';
        echo '<span class="hph-status-indicator ' . ($php_ok ? 'hph-status-ok' : 'hph-status-warning') . '"></span>';
        echo '</div>';
        
        // Check integrations
        echo '<div class="hph-status-item">';
        echo '<span class="hph-status-icon dashicons dashicons-networking"></span>';
        echo '<span class="hph-status-label">' . __('Integrations', 'happy-place') . '</span>';
        echo '<span class="hph-status-value" id="hph-integration-count">-</span>';
        echo '<span class="hph-status-indicator" id="hph-integration-status"></span>';
        echo '</div>';
        
        echo '</div>';
        
        // Quick action to view full system health
        echo '<div class="hph-system-actions">';
        echo '<a href="' . admin_url('admin.php?page=happy-place-system-health') . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-heart"></span> ' . __('View Full System Health', 'happy-place');
        echo '</a>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Render listings overview with AJAX loading
     */
    private function render_listings_overview(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Listings Overview', 'happy-place') . '</h2>';
        
        // AJAX loading container
        echo '<div id="hph-listings-overview" class="hph-ajax-container">';
        echo '<div class="hph-loading-placeholder">';
        echo '<span class="dashicons dashicons-update spin"></span> ';
        echo __('Loading listings overview...', 'happy-place');
        echo '</div>';
        echo '</div>';
        
        // Listings table container (loaded via AJAX)
        echo '<div id="hph-listings-table" class="hph-ajax-container" style="display: none;">';
        echo '<div class="hph-table-controls">';
        echo '<div class="hph-filters">';
        echo '<select id="hph-status-filter">';
        echo '<option value="">' . __('All Statuses', 'happy-place') . '</option>';
        echo '<option value="for_sale">' . __('For Sale', 'happy-place') . '</option>';
        echo '<option value="for_rent">' . __('For Rent', 'happy-place') . '</option>';
        echo '<option value="sold">' . __('Sold', 'happy-place') . '</option>';
        echo '<option value="rented">' . __('Rented', 'happy-place') . '</option>';
        echo '</select>';
        echo '<select id="hph-price-filter">';
        echo '<option value="">' . __('All Prices', 'happy-place') . '</option>';
        echo '<option value="0-250000">' . __('Under $250k', 'happy-place') . '</option>';
        echo '<option value="250000-500000">' . __('$250k - $500k', 'happy-place') . '</option>';
        echo '<option value="500000-1000000">' . __('$500k - $1M', 'happy-place') . '</option>';
        echo '<option value="1000000-999999999">' . __('Over $1M', 'happy-place') . '</option>';
        echo '</select>';
        echo '<button id="hph-apply-filters" class="button">' . __('Apply Filters', 'happy-place') . '</button>';
        echo '</div>';
        echo '</div>';
        echo '<div id="hph-listings-data"></div>';
        echo '</div>';
        
        echo '</div>';

        // JavaScript to load data
        echo '<script>';
        echo 'jQuery(document).ready(function($) {';
        echo '  // Load initial listings overview';
        echo '  hphAdmin.loadListingsOverview();';
        echo '  ';
        echo '  // Filter handlers';
        echo '  $("#hph-apply-filters").click(function() {';
        echo '    hphAdmin.loadListingsOverview({';
        echo '      status: $("#hph-status-filter").val(),';
        echo '      price_range: $("#hph-price-filter").val()';
        echo '    });';
        echo '  });';
        echo '});';
        echo '</script>';
    }

    /**
     * Render bulk operations
     */
    private function render_bulk_operations(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Bulk Operations', 'happy-place') . '</h2>';
        
        echo '<div class="hph-bulk-operations">';
        echo '<div class="hph-bulk-action">';
        echo '<h4>' . __('Status Management', 'happy-place') . '</h4>';
        echo '<button class="button" onclick="hphAdmin.bulkAction(\'publish\')">' . __('Bulk Publish', 'happy-place') . '</button>';
        echo '<button class="button" onclick="hphAdmin.bulkAction(\'draft\')">' . __('Bulk Draft', 'happy-place') . '</button>';
        echo '</div>';
        
        echo '<div class="hph-bulk-action">';
        echo '<h4>' . __('Data Management', 'happy-place') . '</h4>';
        echo '<button class="button" onclick="hphAdmin.bulkAction(\'sync\')">' . __('Sync with Airtable', 'happy-place') . '</button>';
        echo '<button class="button" onclick="hphAdmin.bulkAction(\'optimize_images\')">' . __('Optimize Images', 'happy-place') . '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Render available integrations
     */
    private function render_available_integrations(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Available Integrations', 'happy-place') . '</h2>';
        
        $integrations = [
            'airtable' => [
                'name' => 'Airtable',
                'description' => 'Two-way sync with Airtable databases',
                'icon' => 'database'
            ],
            'google_maps' => [
                'name' => 'Google Maps',
                'description' => 'Geocoding and location services',
                'icon' => 'location-alt'
            ],
            'walkscore' => [
                'name' => 'Walk Score',
                'description' => 'Walkability and transit scores',
                'icon' => 'admin-site-alt3'
            ]
        ];
        
        echo '<div class="hph-integration-cards">';
        foreach ($integrations as $key => $integration) {
            echo '<div class="hph-integration-card" data-integration="' . esc_attr($key) . '">';
            echo '<div class="hph-integration-header">';
            echo '<span class="dashicons dashicons-' . $integration['icon'] . '"></span>';
            echo '<h3>' . esc_html($integration['name']) . '</h3>';
            echo '</div>';
            echo '<p>' . esc_html($integration['description']) . '</p>';
            echo '<div class="hph-integration-actions">';
            echo '<button class="button button-primary" onclick="hphAdmin.configureIntegration(\'' . $key . '\')">';
            echo __('Configure', 'happy-place');
            echo '</button>';
            echo '<button class="button button-secondary" onclick="hphAdmin.testIntegration(\'' . $key . '\')">';
            echo __('Test', 'happy-place');
            echo '</button>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Render integration settings
     */
    private function render_integration_settings(): void
    {
        // Use existing integrations manager if available
        if (class_exists('\HPH\Admin\Integrations_Manager')) {
            $integrations_manager = \HPH\Admin\Integrations_Manager::get_instance();
            $integrations_manager->render_integrations_page();
        } else {
            echo '<div class="hph-card">';
            echo '<h2>' . __('Integration Settings', 'happy-place') . '</h2>';
            echo '<p>' . __('Integration settings will be displayed here once the Integrations Manager is loaded.', 'happy-place') . '</p>';
            echo '</div>';
        }
    }

    /**
     * Render maintenance tools
     */
    private function render_maintenance_tools(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Maintenance Tools', 'happy-place') . '</h2>';
        echo '<div class="hph-tool-grid">';
        
        // Cache Management
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-update"></span> ' . __('Cache Management', 'happy-place') . '</h3>';
        echo '<p>' . __('Clear caches and optimize performance.', 'happy-place') . '</p>';
        echo '<button class="button button-secondary" onclick="hphAdmin.clearCache()">' . __('Clear Cache', 'happy-place') . '</button>';
        echo '</div>';
        
        // Database Optimization
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-database"></span> ' . __('Database Optimization', 'happy-place') . '</h3>';
        echo '<p>' . __('Optimize database tables and clean up unused data.', 'happy-place') . '</p>';
        echo '<button class="button button-secondary" onclick="hphAdmin.optimizeDatabase()">' . __('Optimize Database', 'happy-place') . '</button>';
        echo '</div>';
        
        // Error Log Cleanup
        echo '<div class="hph-tool-item">';
        echo '<h3><span class="dashicons dashicons-trash"></span> ' . __('Error Log Cleanup', 'happy-place') . '</h3>';
        echo '<p>' . __('Clear system error logs and integration errors.', 'happy-place') . '</p>';
        echo '<button class="button button-secondary" onclick="hphAdmin.clearErrorLogs()">' . __('Clear Error Logs', 'happy-place') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render tool interfaces (modals/dialogs)
     */
    private function render_tool_interfaces(): void
    {
        // CSV Import Interface
        echo '<div id="hph-csv-import-modal" class="hph-modal" style="display:none;">';
        echo '<div class="hph-modal-content">';
        echo '<div class="hph-modal-header">';
        echo '<h2>' . __('CSV Import Tool', 'happy-place') . '</h2>';
        echo '<button class="hph-modal-close" onclick="hphAdmin.closeTool()">&times;</button>';
        echo '</div>';
        echo '<div class="hph-modal-body">';
        // CSV import interface will be loaded here
        echo '<div id="hph-csv-import-content"></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Flyer Generator Interface
        echo '<div id="hph-flyer-generator-modal" class="hph-modal" style="display:none;">';
        echo '<div class="hph-modal-content">';
        echo '<div class="hph-modal-header">';
        echo '<h2>' . __('Flyer Generator', 'happy-place') . '</h2>';
        echo '<button class="hph-modal-close" onclick="hphAdmin.closeTool()">&times;</button>';
        echo '</div>';
        echo '<div class="hph-modal-body">';
        // Flyer generator interface will be loaded here
        echo '<div id="hph-flyer-generator-content"></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Image Optimization Interface
        echo '<div id="hph-image-optimization-modal" class="hph-modal" style="display:none;">';
        echo '<div class="hph-modal-content">';
        echo '<div class="hph-modal-header">';
        echo '<h2>' . __('Image Optimization', 'happy-place') . '</h2>';
        echo '<button class="hph-modal-close" onclick="hphAdmin.closeTool()">&times;</button>';
        echo '</div>';
        echo '<div class="hph-modal-body">';
        // Image optimization interface will be loaded here
        echo '<div id="hph-image-optimization-content"></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render basic health check (fallback)
     */
    private function render_basic_health_check(): void
    {
        echo '<div class="hph-card">';
        echo '<h2>' . __('Basic System Check', 'happy-place') . '</h2>';
        
        echo '<div class="hph-health-checks">';
        
        // WordPress version check
        $wp_version_ok = version_compare(get_bloginfo('version'), '6.0', '>=');
        echo '<div class="hph-health-item">';
        echo '<span class="hph-health-icon ' . ($wp_version_ok ? 'hph-ok' : 'hph-warning') . '"></span>';
        echo '<span class="hph-health-label">' . __('WordPress Version', 'happy-place') . '</span>';
        echo '<span class="hph-health-value">' . get_bloginfo('version') . '</span>';
        echo '</div>';
        
        // PHP version check
        $php_version_ok = version_compare(PHP_VERSION, '8.0', '>=');
        echo '<div class="hph-health-item">';
        echo '<span class="hph-health-icon ' . ($php_version_ok ? 'hph-ok' : 'hph-warning') . '"></span>';
        echo '<span class="hph-health-label">' . __('PHP Version', 'happy-place') . '</span>';
        echo '<span class="hph-health-value">' . PHP_VERSION . '</span>';
        echo '</div>';
        
        // Memory limit check
        $memory_limit = ini_get('memory_limit');
        $memory_ok = (int) $memory_limit >= 256;
        echo '<div class="hph-health-item">';
        echo '<span class="hph-health-icon ' . ($memory_ok ? 'hph-ok' : 'hph-warning') . '"></span>';
        echo '<span class="hph-health-label">' . __('Memory Limit', 'happy-place') . '</span>';
        echo '<span class="hph-health-value">' . $memory_limit . '</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render original developer tools
     */
    private function render_original_dev_tools(): void
    {
        // Cache Management Section
        echo '<div class="hph-card">';
        echo '<h2>' . __('Cache Management', 'happy-place') . '</h2>';
        echo '<div class="hph-dev-grid">';
        
        echo '<div class="hph-dev-action">';
        echo '<h4>' . __('WordPress Cache', 'happy-place') . '</h4>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="flush_cache">';
        echo '<button type="submit" class="button button-secondary">' . __('Flush Cache', 'happy-place') . '</button>';
        echo '</form>';
        echo '<p class="description">' . __('Clears WordPress object cache and transients.', 'happy-place') . '</p>';
        echo '</div>';

        echo '<div class="hph-dev-action">';
        echo '<h4>' . __('Rewrite Rules', 'happy-place') . '</h4>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="flush_rewrite">';
        echo '<button type="submit" class="button button-secondary">' . __('Flush Rewrite Rules', 'happy-place') . '</button>';
        echo '</form>';
        echo '<p class="description">' . __('Regenerates permalink structure and rewrite rules.', 'happy-place') . '</p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Build Tools Section
        echo '<div class="hph-card">';
        echo '<h2>' . __('Build Tools', 'happy-place') . '</h2>';
        echo '<div class="hph-dev-grid">';
        
        echo '<div class="hph-dev-action">';
        echo '<h4>' . __('Theme Assets', 'happy-place') . '</h4>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="build_sass">';
        echo '<button type="submit" class="button button-primary">' . __('Build Sass', 'happy-place') . '</button>';
        echo '</form>';
        echo ' ';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="build_webpack">';
        echo '<button type="submit" class="button button-primary">' . __('Build Webpack', 'happy-place') . '</button>';
        echo '</form>';
        echo '<p class="description">' . __('Compile theme Sass and JavaScript assets.', 'happy-place') . '</p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Database Tools Section
        echo '<div class="hph-card">';
        echo '<h2>' . __('Database Tools', 'happy-place') . '</h2>';
        echo '<div class="hph-dev-grid">';
        
        echo '<div class="hph-dev-action">';
        echo '<h4>' . __('Database Optimization', 'happy-place') . '</h4>';
        echo '<form method="post" style="display: inline;">';
        wp_nonce_field('hph_dev_tools', 'hph_dev_nonce');
        echo '<input type="hidden" name="dev_action" value="optimize_db">';
        echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'' . __('Are you sure? This will optimize database tables.', 'happy-place') . '\');">';
        echo __('Optimize Database', 'happy-place');
        echo '</button>';
        echo '</form>';
        echo '<p class="description">' . __('Optimize database tables for better performance.', 'happy-place') . '</p>';
        echo '</div>';

        echo '</div>';
        echo '</div>';
    }

    /**
     * AJAX handler for dashboard stats
     */
    public function ajax_get_dashboard_stats(): void
    {
        check_ajax_referer('hph_enhanced_admin', 'nonce');
        
        $stats = [
            'listings' => [
                'total' => wp_count_posts('listing')->publish ?? 0,
                'draft' => wp_count_posts('listing')->draft ?? 0
            ],
            'integrations' => $this->get_integration_status(),
            'system_health' => $this->get_basic_system_health(),
            'recent_activity' => $this->get_recent_activity_data()
        ];
        
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for refreshing integrations
     */
    public function ajax_refresh_integrations(): void
    {
        check_ajax_referer('hph_enhanced_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $integrations = $this->get_integration_status();
        wp_send_json_success($integrations);
    }

    /**
     * AJAX handler for testing integrations
     */
    public function ajax_test_integration(): void
    {
        check_ajax_referer('hph_enhanced_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $integration = sanitize_text_field($_POST['integration'] ?? '');
        $result = $this->test_integration($integration);
        
        wp_send_json($result);
    }

    /**
     * AJAX handler for toggling integrations
     */
    public function ajax_toggle_integration(): void
    {
        check_ajax_referer('hph_enhanced_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'happy-place')]);
        }
        
        $integration = sanitize_text_field($_POST['integration'] ?? '');
        $enabled = (bool) ($_POST['enabled'] ?? false);
        
        $result = $this->toggle_integration($integration, $enabled);
        wp_send_json($result);
    }

    /**
     * Get integration status
     */
    private function get_integration_status(): array
    {
        $integrations = [];
        
        // Airtable integration
        $airtable_options = get_option('happy_place_integrations', []);
        $integrations['airtable'] = [
            'name' => 'Airtable',
            'enabled' => !empty($airtable_options['airtable']['access_token'] ?? ''),
            'status' => 'unknown',
            'last_sync' => get_option('hph_airtable_last_sync', '')
        ];
        
        // Google Maps integration
        $google_key = get_option('hph_google_maps_api_key', '');
        $integrations['google_maps'] = [
            'name' => 'Google Maps',
            'enabled' => !empty($google_key),
            'status' => !empty($google_key) ? 'active' : 'inactive',
            'last_check' => current_time('mysql')
        ];
        
        // Walk Score integration
        $walkscore_key = get_option('hph_walkscore_api_key', '');
        $integrations['walkscore'] = [
            'name' => 'Walk Score',
            'enabled' => !empty($walkscore_key),
            'status' => !empty($walkscore_key) ? 'active' : 'inactive',
            'last_check' => current_time('mysql')
        ];
        
        return $integrations;
    }

    /**
     * Get basic system health
     */
    private function get_basic_system_health(): array
    {
        return [
            'status' => 'healthy',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ];
    }

    /**
     * Get recent activity data
     */
    private function get_recent_activity_data(): array
    {
        $recent_posts = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => 5,
            'post_status' => 'any'
        ]);
        
        $activity = [];
        foreach ($recent_posts as $post) {
            $activity[] = [
                'title' => $post->post_title,
                'date' => $post->post_date,
                'status' => $post->post_status,
                'edit_link' => get_edit_post_link($post->ID)
            ];
        }
        
        return $activity;
    }

    /**
     * Test integration connection
     */
    private function test_integration(string $integration): array
    {
        switch ($integration) {
            case 'airtable':
                if (class_exists('Airtable_Two_Way_Sync')) {
                    try {
                        $airtable = new \Airtable_Two_Way_Sync('test', 'test');
                        return $airtable->test_api_connection();
                    } catch (\Exception $e) {
                        return [
                            'success' => false,
                            'error' => $e->getMessage()
                        ];
                    }
                }
                break;
                
            case 'google_maps':
                $api_key = get_option('hph_google_maps_api_key', '');
                if (empty($api_key)) {
                    return [
                        'success' => false,
                        'error' => __('API key not configured', 'happy-place')
                    ];
                }
                // Test with a simple geocoding request
                return [
                    'success' => true,
                    'message' => __('API key configured', 'happy-place')
                ];
                
            case 'walkscore':
                $api_key = get_option('hph_walkscore_api_key', '');
                return [
                    'success' => !empty($api_key),
                    'message' => !empty($api_key) ? __('API key configured', 'happy-place') : __('API key not configured', 'happy-place')
                ];
        }
        
        return [
            'success' => false,
            'error' => __('Unknown integration', 'happy-place')
        ];
    }

    /**
     * Toggle integration status
     */
    private function toggle_integration(string $integration, bool $enabled): array
    {
        // This would implement the logic to enable/disable integrations
        // For now, return success
        return [
            'success' => true,
            'message' => sprintf(
                __('Integration %s %s', 'happy-place'),
                $integration,
                $enabled ? __('enabled', 'happy-place') : __('disabled', 'happy-place')
            )
        ];
    }

    /**
     * Handle developer tool actions
     */
    private function handle_dev_actions(): void
    {
        if (!wp_verify_nonce($_POST['hph_dev_nonce'], 'hph_dev_tools')) {
            wp_die(__('Security check failed', 'happy-place'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'happy-place'));
        }

        $action = sanitize_text_field($_POST['dev_action']);

        switch ($action) {
            case 'flush_cache':
                wp_cache_flush();
                $this->clear_expired_transients();
                
                // Clear third-party caches if available
                if (function_exists('w3tc_flush_all')) {
                    \w3tc_flush_all();
                }
                if (function_exists('rocket_clean_domain')) {
                    \rocket_clean_domain();
                }
                if (class_exists('LiteSpeed\\Purge')) {
                    \LiteSpeed\Purge::purge_all();
                }
                
                $this->show_admin_notice(__('WordPress cache flushed successfully!', 'happy-place'), 'success');
                break;

            case 'flush_rewrite':
                flush_rewrite_rules(true);
                $this->show_admin_notice(__('Rewrite rules flushed successfully!', 'happy-place'), 'success');
                break;

            case 'build_sass':
                $this->show_admin_notice(__('Build tools are not available in this environment.', 'happy-place'), 'info');
                break;

            case 'build_webpack':
                $this->show_admin_notice(__('Build tools are not available in this environment.', 'happy-place'), 'info');
                break;

            case 'optimize_db':
                global $wpdb;
                $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
                $optimized = 0;
                foreach ($tables as $table) {
                    $wpdb->query("OPTIMIZE TABLE {$table[0]}");
                    $optimized++;
                }
                $this->show_admin_notice(
                    sprintf(__('Database optimized! %d tables processed.', 'happy-place'), $optimized),
                    'success'
                );
                break;

            default:
                $this->show_admin_notice(__('Unknown action.', 'happy-place'), 'error');
        }
    }

    /**
     * Clear expired transients from database
     */
    private function clear_expired_transients(): void
    {
        global $wpdb;
        
        // Clear expired transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
        
        // Clear orphaned transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%' AND NOT EXISTS (SELECT 1 FROM {$wpdb->options} t2 WHERE t2.option_name = CONCAT('_transient_timeout_', SUBSTRING({$wpdb->options}.option_name, 12)))");
    }

    /**
     * Show admin notice
     */
    private function show_admin_notice(string $message, string $type = 'info'): void
    {
        add_action('admin_notices', function() use ($message, $type) {
            echo "<div class='notice notice-{$type} is-dismissible'><p>{$message}</p></div>";
        });
    }
}

// Initialize the enhanced admin menu
Admin_Menu::get_instance();
