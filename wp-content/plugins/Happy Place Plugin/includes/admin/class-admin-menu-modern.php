<?php
/**
 * Modernized Admin Menu System for Happy Place Plugin
 * 
 * Fully integrated with optimized Plugin Manager and modern AJAX architecture.
 * Features streamlined performance, consistent security patterns, and clean UI.
 * 
 * @package HappyPlace
 * @subpackage Admin
 * @version 3.0.0
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Menu
{
    private static ?self $instance = null;
    
    /**
     * Menu capability requirements - standardized with Plugin Manager
     */
    private array $menu_capabilities = [
        'dashboard' => 'read',
        'listings' => 'edit_posts',
        'integrations' => 'manage_options',
        'tools' => 'manage_options',
        'system_health' => 'manage_options',
        'marketing_suite' => 'edit_posts',
        'settings' => 'manage_options',
        'developer' => 'manage_options'
    ];

    /**
     * Modern AJAX endpoints - integrated with our AJAX system
     */
    private array $ajax_endpoints = [
        'dashboard_quick_stats' => 'Dashboard_Ajax',
        'dashboard_recent_activity' => 'Dashboard_Ajax', 
        'get_listings' => 'Listing_Ajax',
        'generate_flyer' => 'Flyer_Ajax',
        'validate_system' => 'Admin_Ajax',
        'refresh_cache' => 'Admin_Ajax'
    ];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_modern_assets']);
        add_action('admin_init', [$this, 'initialize_admin_features']);
    }

    /**
     * Register all admin menu pages with modern structure
     */
    public function register_menu_pages(): void
    {
        // Main dashboard page
        add_menu_page(
            __('Happy Place Dashboard', 'happy-place'),
            __('Happy Place', 'happy-place'),
            $this->menu_capabilities['dashboard'],
            'happy-place-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-building',
            30
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

        // Marketing Suite (consolidated flyer generator)
        add_submenu_page(
            'happy-place-dashboard',
            __('Marketing Suite', 'happy-place'),
            __('🎨 Marketing Suite', 'happy-place'),
            $this->menu_capabilities['marketing_suite'],
            'happy-place-marketing-suite',
            [$this, 'render_marketing_suite']
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

        // System Health Monitor
        add_submenu_page(
            'happy-place-dashboard',
            __('System Health', 'happy-place'),
            __('💚 System Health', 'happy-place'),
            $this->menu_capabilities['system_health'],
            'happy-place-system-health',
            [$this, 'render_system_health']
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

        // Developer Tools (Debug mode only)
        if ($this->is_debug_mode()) {
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
     * Enqueue modernized admin assets with optimized loading
     */
    public function enqueue_modern_assets($hook_suffix): void
    {
        // Only load on our admin pages
        if (strpos($hook_suffix, 'happy-place') === false) {
            return;
        }

        // Modern admin styles
        wp_enqueue_style(
            'hph-modern-admin',
            HPH_ASSETS_URL . 'css/modern-admin.css',
            [],
            HPH_VERSION
        );

        // Modern admin scripts with proper dependencies
        wp_enqueue_script(
            'hph-modern-admin',
            HPH_ASSETS_URL . 'js/modern-admin.js',
            ['jquery', 'wp-api', 'wp-hooks'],
            HPH_VERSION,
            true
        );

        // Localize with modern AJAX nonce system
        wp_localize_script('hph-modern-admin', 'hphModernAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('hph/v1/'),
            'nonce' => wp_create_nonce('hph_ajax_nonce'), // Use our standardized nonce
            'currentPage' => $hook_suffix,
            'pluginUrl' => HPH_URL, // Use defined constant
            'endpoints' => $this->get_ajax_endpoints(),
            'capabilities' => $this->get_user_capabilities(),
            'i18n' => $this->get_localization_strings()
        ]);
    }

    /**
     * Initialize admin features with modern patterns
     */
    public function initialize_admin_features(): void
    {
        // No legacy AJAX handlers - use our centralized system
        // Modern admin features initialization
        $this->setup_admin_notices();
        $this->register_admin_settings();
    }

    /**
     * Render main dashboard with modern layout
     */
    public function render_dashboard(): void
    {
        $this->render_admin_header('dashboard', __('Dashboard', 'happy-place'), 'dashicons-dashboard');
        
        echo '<div class="hph-modern-dashboard">';
        
        // Quick stats section
        echo '<div class="hph-dashboard-section">';
        echo '<div class="hph-stats-container" id="hph-dashboard-stats">';
        echo '<div class="hph-loading-placeholder">' . __('Loading dashboard statistics...', 'happy-place') . '</div>';
        echo '</div>';
        echo '</div>';

        // System status overview
        echo '<div class="hph-dashboard-section">';
        echo '<h2>' . __('System Status', 'happy-place') . '</h2>';
        echo '<div class="hph-system-status" id="hph-system-status">';
        echo '<div class="hph-loading-placeholder">' . __('Checking system status...', 'happy-place') . '</div>';
        echo '</div>';
        echo '</div>';

        // Recent activity
        echo '<div class="hph-dashboard-section">';
        echo '<h2>' . __('Recent Activity', 'happy-place') . '</h2>';
        echo '<div class="hph-recent-activity" id="hph-recent-activity">';
        $this->render_recent_activity_static();
        echo '</div>';
        echo '</div>';

        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render listings management with modern interface
     */
    public function render_listings_management(): void
    {
        if (!current_user_can($this->menu_capabilities['listings'])) {
            $this->render_access_denied('listings');
            return;
        }

        $this->render_admin_header('listings', __('Listings Management', 'happy-place'), 'dashicons-admin-home');
        
        echo '<div class="hph-modern-listings">';
        
        // Quick actions toolbar
        echo '<div class="hph-toolbar">';
        echo '<div class="hph-toolbar-actions">';
        echo '<a href="' . admin_url('post-new.php?post_type=listing') . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-plus-alt"></span> ' . __('Add New Listing', 'happy-place');
        echo '</a>';
        echo '<button class="button button-secondary" onclick="hphAdmin.refreshListings()">';
        echo '<span class="dashicons dashicons-update"></span> ' . __('Refresh', 'happy-place');
        echo '</button>';
        echo '</div>';
        echo '</div>';

        // Listings overview container
        echo '<div class="hph-listings-overview" id="hph-listings-overview">';
        echo '<div class="hph-loading-placeholder">' . __('Loading listings overview...', 'happy-place') . '</div>';
        echo '</div>';

        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render marketing suite with modern consolidated interface
     */
    public function render_marketing_suite(): void
    {
        if (!current_user_can($this->menu_capabilities['marketing_suite'])) {
            $this->render_access_denied('marketing_suite');
            return;
        }

        $this->render_admin_header('marketing-suite', __('Marketing Suite', 'happy-place'), 'dashicons-format-image');
        
        echo '<div class="hph-modern-marketing-suite">';
        
        // Marketing suite interface
        echo '<div class="hph-marketing-interface">';
        echo '<div class="hph-marketing-generator" id="hph-marketing-generator">';
        
        // Format selection
        echo '<div class="hph-format-selection">';
        echo '<h3>' . __('Choose Format', 'happy-place') . '</h3>';
        echo '<div class="hph-format-grid">';
        
        $formats = [
            'flyer' => ['name' => __('Property Flyer', 'happy-place'), 'icon' => 'format-image'],
            'postcard' => ['name' => __('Postcard', 'happy-place'), 'icon' => 'admin-post'],
            'brochure' => ['name' => __('Brochure', 'happy-place'), 'icon' => 'book'],
            'social-post' => ['name' => __('Social Media Post', 'happy-place'), 'icon' => 'share'],
            'email-template' => ['name' => __('Email Template', 'happy-place'), 'icon' => 'email']
        ];
        
        foreach ($formats as $format_key => $format) {
            echo '<div class="hph-format-option" data-format="' . esc_attr($format_key) . '">';
            echo '<div class="hph-format-icon">';
            echo '<span class="dashicons dashicons-' . esc_attr($format['icon']) . '"></span>';
            echo '</div>';
            echo '<div class="hph-format-name">' . esc_html($format['name']) . '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';

        // Generator interface (loaded via modern JS)
        echo '<div class="hph-generator-interface" id="hph-generator-interface" style="display: none;">';
        echo '<div class="hph-loading-placeholder">' . __('Loading marketing generator...', 'happy-place') . '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render integrations hub with modern monitoring
     */
    public function render_integrations_hub(): void
    {
        if (!current_user_can($this->menu_capabilities['integrations'])) {
            $this->render_access_denied('integrations');
            return;
        }

        $this->render_admin_header('integrations', __('Integrations Hub', 'happy-place'), 'dashicons-networking');
        
        echo '<div class="hph-modern-integrations">';
        
        // Integration status grid
        echo '<div class="hph-integration-status" id="hph-integration-status">';
        echo '<div class="hph-loading-placeholder">' . __('Loading integration status...', 'happy-place') . '</div>';
        echo '</div>';

        // Available integrations
        echo '<div class="hph-available-integrations">';
        echo '<h2>' . __('Available Integrations', 'happy-place') . '</h2>';
        echo '<div class="hph-integration-grid" id="hph-available-integrations">';
        echo '<div class="hph-loading-placeholder">' . __('Loading available integrations...', 'happy-place') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render tools utilities with modern interface
     */
    public function render_tools_utilities(): void
    {
        if (!current_user_can($this->menu_capabilities['tools'])) {
            $this->render_access_denied('tools');
            return;
        }

        $this->render_admin_header('tools', __('Tools & Utilities', 'happy-place'), 'dashicons-admin-tools');
        
        echo '<div class="hph-modern-tools">';
        
        // Tool categories
        echo '<div class="hph-tool-categories">';
        
        // Data Management Tools
        echo '<div class="hph-tool-category">';
        echo '<h3>' . __('Data Management', 'happy-place') . '</h3>';
        echo '<div class="hph-tool-grid">';
        
        $data_tools = [
            'csv-import' => ['name' => __('CSV Import', 'happy-place'), 'icon' => 'upload', 'desc' => __('Import listings from CSV files', 'happy-place')],
            'data-export' => ['name' => __('Data Export', 'happy-place'), 'icon' => 'download', 'desc' => __('Export listings and analytics', 'happy-place')],
            'bulk-operations' => ['name' => __('Bulk Operations', 'happy-place'), 'icon' => 'edit', 'desc' => __('Bulk edit listings and properties', 'happy-place')]
        ];
        
        foreach ($data_tools as $tool_key => $tool) {
            $this->render_tool_card($tool_key, $tool);
        }
        
        echo '</div>';
        echo '</div>';

        // Maintenance Tools
        echo '<div class="hph-tool-category">';
        echo '<h3>' . __('Maintenance', 'happy-place') . '</h3>';
        echo '<div class="hph-tool-grid">';
        
        $maintenance_tools = [
            'cache-management' => ['name' => __('Cache Management', 'happy-place'), 'icon' => 'performance', 'desc' => __('Manage plugin caches', 'happy-place')],
            'log-viewer' => ['name' => __('Log Viewer', 'happy-place'), 'icon' => 'visibility', 'desc' => __('View system logs and errors', 'happy-place')],
            'database-cleanup' => ['name' => __('Database Cleanup', 'happy-place'), 'icon' => 'database', 'desc' => __('Clean up old data', 'happy-place')]
        ];
        
        foreach ($maintenance_tools as $tool_key => $tool) {
            $this->render_tool_card($tool_key, $tool);
        }
        
        echo '</div>';
        echo '</div>';

        echo '</div>';

        // Tool interfaces (loaded dynamically)
        echo '<div class="hph-tool-interfaces" id="hph-tool-interfaces"></div>';
        
        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render system health with modern monitoring
     */
    public function render_system_health(): void
    {
        if (!current_user_can($this->menu_capabilities['system_health'])) {
            $this->render_access_denied('system_health');
            return;
        }

        // Check if Enhanced Systems Dashboard is available
        if (class_exists('\HappyPlace\Monitoring\Enhanced_Systems_Dashboard')) {
            $dashboard = \HappyPlace\Monitoring\Enhanced_Systems_Dashboard::get_instance();
            $dashboard->render_dashboard_page();
            return;
        }

        // Fallback modern health monitor
        $this->render_admin_header('system-health', __('System Health', 'happy-place'), 'dashicons-heart');
        
        echo '<div class="hph-modern-health">';
        
        // System metrics
        echo '<div class="hph-health-metrics" id="hph-health-metrics">';
        echo '<div class="hph-loading-placeholder">' . __('Loading system health metrics...', 'happy-place') . '</div>';
        echo '</div>';

        // Performance monitoring
        echo '<div class="hph-performance-monitoring">';
        echo '<h2>' . __('Performance Monitoring', 'happy-place') . '</h2>';
        echo '<div class="hph-performance-charts" id="hph-performance-charts">';
        echo '<div class="hph-loading-placeholder">' . __('Loading performance data...', 'happy-place') . '</div>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render settings with modern interface
     */
    public function render_settings(): void
    {
        if (!current_user_can($this->menu_capabilities['settings'])) {
            $this->render_access_denied('settings');
            return;
        }

        $this->render_admin_header('settings', __('Settings', 'happy-place'), 'dashicons-admin-generic');
        
        echo '<div class="hph-modern-settings">';
        
        // Settings form
        echo '<form method="post" action="options.php" class="hph-settings-form">';
        settings_fields('happy_place_settings');
        do_settings_sections('happy_place_settings');
        
        echo '<div class="hph-settings-sections">';
        
        // General Settings
        echo '<div class="hph-settings-section">';
        echo '<h2>' . __('General Settings', 'happy-place') . '</h2>';
        echo '<table class="form-table">';
        
        // Add modern settings fields here
        $this->render_settings_fields();
        
        echo '</table>';
        echo '</div>';

        echo '</div>';
        
        submit_button(__('Save Settings', 'happy-place'), 'primary', 'submit', true, ['class' => 'hph-save-settings']);
        echo '</form>';
        
        echo '</div>';
        
        $this->render_admin_footer();
    }

    /**
     * Render developer tools with modern interface
     */
    public function render_developer_tools(): void
    {
        if (!current_user_can($this->menu_capabilities['developer'])) {
            $this->render_access_denied('developer');
            return;
        }

        $this->render_admin_header('developer', __('Developer Tools', 'happy-place'), 'dashicons-editor-code');
        
        echo '<div class="hph-modern-developer">';
        
        // Developer actions
        echo '<div class="hph-dev-actions">';
        echo '<h3>' . __('Development Actions', 'happy-place') . '</h3>';
        echo '<div class="hph-dev-buttons">';
        
        echo '<button class="button button-primary" onclick="hphAdmin.runSystemTest()">';
        echo '<span class="dashicons dashicons-admin-tools"></span> ' . __('Run System Test', 'happy-place');
        echo '</button>';
        
        echo '<button class="button button-secondary" onclick="hphAdmin.clearAllCaches()">';
        echo '<span class="dashicons dashicons-performance"></span> ' . __('Clear All Caches', 'happy-place');
        echo '</button>';
        
        echo '<button class="button button-secondary" onclick="hphAdmin.regenerateAssets()">';
        echo '<span class="dashicons dashicons-update"></span> ' . __('Regenerate Assets', 'happy-place');
        echo '</button>';
        
        echo '</div>';
        echo '</div>';

        // Test results
        echo '<div class="hph-test-results" id="hph-test-results" style="display: none;"></div>';
        
        echo '</div>';
        
        $this->render_admin_footer();
    }

    // Helper Methods

    /**
     * Render standardized admin header
     */
    private function render_admin_header(string $page_id, string $title, string $icon): void
    {
        echo '<div class="wrap hph-modern-admin" data-page="' . esc_attr($page_id) . '">';
        echo '<div class="hph-admin-header">';
        echo '<h1 class="hph-admin-title">';
        echo '<span class="dashicons ' . esc_attr($icon) . '"></span> ';
        echo esc_html($title);
        echo '</h1>';
        echo '</div>';
    }

    /**
     * Render standardized admin footer
     */
    private function render_admin_footer(): void
    {
        echo '</div>'; // Close wrap
    }

    /**
     * Render access denied message
     */
    private function render_access_denied(string $capability): void
    {
        echo '<div class="wrap hph-modern-admin">';
        echo '<div class="notice notice-error">';
        echo '<p>' . sprintf(__('You do not have permission to access %s.', 'happy-place'), $capability) . '</p>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render tool card component
     */
    private function render_tool_card(string $tool_key, array $tool): void
    {
        echo '<div class="hph-tool-card" data-tool="' . esc_attr($tool_key) . '">';
        echo '<div class="hph-tool-icon">';
        echo '<span class="dashicons dashicons-' . esc_attr($tool['icon']) . '"></span>';
        echo '</div>';
        echo '<div class="hph-tool-content">';
        echo '<h4>' . esc_html($tool['name']) . '</h4>';
        echo '<p>' . esc_html($tool['desc']) . '</p>';
        echo '<button class="button button-primary" onclick="hphAdmin.launchTool(\'' . esc_js($tool_key) . '\')">';
        echo __('Launch Tool', 'happy-place');
        echo '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Render recent activity (static fallback)
     */
    private function render_recent_activity_static(): void
    {
        $recent_posts = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => 5,
            'post_status' => 'publish'
        ]);

        if (empty($recent_posts)) {
            echo '<p>' . __('No recent activity found.', 'happy-place') . '</p>';
            return;
        }

        echo '<div class="hph-activity-list">';
        foreach ($recent_posts as $post) {
            echo '<div class="hph-activity-item">';
            echo '<span class="hph-activity-icon dashicons dashicons-admin-home"></span>';
            echo '<div class="hph-activity-content">';
            echo '<strong>' . esc_html($post->post_title) . '</strong>';
            echo '<br><small>' . sprintf(__('Added %s', 'happy-place'), human_time_diff(strtotime($post->post_date))) . '</small>';
            echo '</div>';
            echo '<div class="hph-activity-actions">';
            echo '<a href="' . get_edit_post_link($post->ID) . '" class="button button-small">' . __('Edit', 'happy-place') . '</a>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render settings fields
     */
    private function render_settings_fields(): void
    {
        // Add settings fields based on Plugin Manager requirements
        $settings = [
            'enable_caching' => __('Enable Caching', 'happy-place'),
            'enable_analytics' => __('Enable Analytics', 'happy-place'),
            'enable_integrations' => __('Enable Integrations', 'happy-place')
        ];

        foreach ($settings as $setting_key => $setting_label) {
            echo '<tr>';
            echo '<th scope="row">' . esc_html($setting_label) . '</th>';
            echo '<td>';
            echo '<input type="checkbox" id="' . esc_attr($setting_key) . '" name="happy_place_settings[' . esc_attr($setting_key) . ']" value="1" />';
            echo '<label for="' . esc_attr($setting_key) . '">' . __('Enable', 'happy-place') . '</label>';
            echo '</td>';
            echo '</tr>';
        }
    }

    /**
     * Setup admin notices
     */
    private function setup_admin_notices(): void
    {
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /**
     * Display admin notices
     */
    public function display_admin_notices(): void
    {
        // Modern admin notices handled via JS
    }

    /**
     * Register admin settings
     */
    private function register_admin_settings(): void
    {
        register_setting('happy_place_settings', 'happy_place_settings');
    }

    /**
     * Get AJAX endpoints for frontend
     */
    private function get_ajax_endpoints(): array
    {
        return array_keys($this->ajax_endpoints);
    }

    /**
     * Get user capabilities for frontend
     */
    private function get_user_capabilities(): array
    {
        $capabilities = [];
        foreach ($this->menu_capabilities as $cap_key => $cap_value) {
            $capabilities[$cap_key] = current_user_can($cap_value);
        }
        return $capabilities;
    }

    /**
     * Get localization strings
     */
    private function get_localization_strings(): array
    {
        return [
            'loading' => __('Loading...', 'happy-place'),
            'saving' => __('Saving...', 'happy-place'),
            'saved' => __('Saved!', 'happy-place'),
            'error' => __('Error occurred', 'happy-place'),
            'confirm' => __('Are you sure?', 'happy-place'),
            'success' => __('Success!', 'happy-place'),
            'warning' => __('Warning', 'happy-place'),
            'critical' => __('Critical', 'happy-place')
        ];
    }

    /**
     * Check if debug mode is enabled
     */
    private function is_debug_mode(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options');
    }
}
