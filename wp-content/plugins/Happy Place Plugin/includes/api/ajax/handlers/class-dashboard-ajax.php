<?php
/**
 * Dashboard AJAX Handler - Comprehensive Dashboard Management
 *
 * Handles all dashboard-related AJAX operations including:
 * - Dashboard section loading
 * - Agent dashboard functionality
 * - Dashboard settings management
 * - Real-time dashboard updates
 * - Dashboard tool operations
 *
 * @package HappyPlace
 * @subpackage Api\Ajax\Handlers
 * @since 2.0.0
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard AJAX Handler Class
 *
 * Consolidates dashboard functionality from multiple sources:
 * - Dashboard section management
 * - Agent dashboard operations
 * - Dashboard widgets and tools
 * - Real-time data updates
 */
class Dashboard_Ajax extends Base_Ajax_Handler {

    /**
     * Dashboard configuration
     */
    private array $dashboard_config = [
        'cache_duration' => 300, // 5 minutes
        'max_items_per_section' => 50,
        'allowed_sections' => [
            'overview',
            'listings', 
            'clients',
            'transactions',
            'leads',
            'calendar',
            'reports',
            'settings'
        ]
    ];

    /**
     * Define AJAX actions and their configurations
     */
    protected function get_actions(): array {
        return [
            // Core Dashboard Actions
            'dashboard_action' => [
                'callback' => 'handle_dashboard_action',
                'capability' => 'edit_posts',
                'rate_limit' => 20,
                'cache' => 300
            ],
            'load_dashboard_section' => [
                'callback' => 'handle_load_section',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 180
            ],
            'refresh_dashboard_data' => [
                'callback' => 'handle_refresh_data',
                'capability' => 'read',
                'rate_limit' => 15
            ],
            
            // Dashboard Settings
            'save_dashboard_settings' => [
                'callback' => 'handle_save_settings',
                'capability' => 'manage_options',
                'rate_limit' => 10
            ],
            'get_dashboard_settings' => [
                'callback' => 'handle_get_settings',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 600
            ],
            
            // Dashboard Widgets
            'load_widget_data' => [
                'callback' => 'handle_load_widget',
                'capability' => 'read',
                'rate_limit' => 40,
                'cache' => 300
            ],
            'save_widget_config' => [
                'callback' => 'handle_save_widget_config',
                'capability' => 'edit_posts',
                'rate_limit' => 10
            ],
            
            // Dashboard Tools
            'dashboard_quick_action' => [
                'callback' => 'handle_quick_action',
                'capability' => 'edit_posts',
                'rate_limit' => 25
            ],
            'export_dashboard_data' => [
                'callback' => 'handle_export_data',
                'capability' => 'export',
                'rate_limit' => 5
            ],
            
            // Real-time Updates
            'get_dashboard_notifications' => [
                'callback' => 'handle_get_notifications',
                'capability' => 'read',
                'rate_limit' => 60
            ],
            'mark_notification_read' => [
                'callback' => 'handle_mark_notification_read',
                'capability' => 'read',
                'rate_limit' => 100
            ]
        ];
    }

    /**
     * Handle general dashboard actions
     */
    public function handle_dashboard_action(): void {
        try {
            if (!$this->validate_required_params(['action_type' => 'string'])) {
                return;
            }

            $action_type = sanitize_text_field($_POST['action_type']);
            $data = $_POST['data'] ?? [];

            switch ($action_type) {
                case 'get_overview_stats':
                    $stats = $this->get_overview_statistics();
                    $this->send_success($stats);
                    break;

                case 'get_recent_activity':
                    $activity = $this->get_recent_activity();
                    $this->send_success($activity);
                    break;

                case 'update_dashboard_layout':
                    $result = $this->update_dashboard_layout($data);
                    $this->send_success($result);
                    break;

                default:
                    $this->send_error('Unknown dashboard action');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Ajax Exception: ' . $e->getMessage());
            $this->send_error('Dashboard action failed');
        }
    }

    /**
     * Handle dashboard section loading
     */
    public function handle_load_section(): void {
        try {
            if (!$this->validate_required_params(['section' => 'string'])) {
                return;
            }

            $section = sanitize_text_field($_POST['section']);
            $page = intval($_POST['page'] ?? 1);
            $filters = $_POST['filters'] ?? [];

            // Validate section
            if (!in_array($section, $this->dashboard_config['allowed_sections'])) {
                $this->send_error('Invalid dashboard section');
                return;
            }

            $section_data = $this->load_section_data($section, $page, $filters);
            
            $this->send_success([
                'section' => $section,
                'data' => $section_data,
                'page' => $page,
                'timestamp' => current_time('timestamp')
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Section Load Exception: ' . $e->getMessage());
            $this->send_error('Failed to load dashboard section');
        }
    }

    /**
     * Handle dashboard data refresh
     */
    public function handle_refresh_data(): void {
        try {
            $sections = $_POST['sections'] ?? ['overview'];
            $results = [];

            foreach ($sections as $section) {
                if (in_array($section, $this->dashboard_config['allowed_sections'])) {
                    $results[$section] = $this->refresh_section_data($section);
                }
            }

            $this->send_success([
                'refreshed_sections' => $results,
                'timestamp' => current_time('timestamp')
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Refresh Exception: ' . $e->getMessage());
            $this->send_error('Failed to refresh dashboard data');
        }
    }

    /**
     * Handle dashboard settings save
     */
    public function handle_save_settings(): void {
        try {
            if (!$this->validate_required_params(['settings' => 'array'])) {
                return;
            }

            $settings = $_POST['settings'];
            $user_id = get_current_user_id();

            // Sanitize and validate settings
            $sanitized_settings = $this->sanitize_dashboard_settings($settings);
            
            // Save user-specific dashboard settings
            $saved = update_user_meta($user_id, 'hph_dashboard_settings', $sanitized_settings);

            if ($saved !== false) {
                $this->send_success([
                    'message' => 'Dashboard settings saved successfully',
                    'settings' => $sanitized_settings
                ]);
            } else {
                $this->send_error('Failed to save dashboard settings');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Settings Save Exception: ' . $e->getMessage());
            $this->send_error('Failed to save settings');
        }
    }

    /**
     * Handle get dashboard settings
     */
    public function handle_get_settings(): void {
        try {
            $user_id = get_current_user_id();
            $settings = get_user_meta($user_id, 'hph_dashboard_settings', true);

            // Provide defaults if no settings exist
            if (empty($settings)) {
                $settings = $this->get_default_dashboard_settings();
            }

            $this->send_success([
                'settings' => $settings,
                'user_id' => $user_id
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Get Settings Exception: ' . $e->getMessage());
            $this->send_error('Failed to load settings');
        }
    }

    /**
     * Handle widget data loading
     */
    public function handle_load_widget(): void {
        try {
            if (!$this->validate_required_params(['widget_type' => 'string'])) {
                return;
            }

            $widget_type = sanitize_text_field($_POST['widget_type']);
            $widget_config = $_POST['config'] ?? [];

            $widget_data = $this->get_widget_data($widget_type, $widget_config);

            $this->send_success([
                'widget_type' => $widget_type,
                'data' => $widget_data,
                'config' => $widget_config
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Widget Load Exception: ' . $e->getMessage());
            $this->send_error('Failed to load widget data');
        }
    }

    /**
     * Handle widget configuration save
     */
    public function handle_save_widget_config(): void {
        try {
            if (!$this->validate_required_params([
                'widget_id' => 'string',
                'config' => 'array'
            ])) {
                return;
            }

            $widget_id = sanitize_text_field($_POST['widget_id']);
            $config = $_POST['config'];
            $user_id = get_current_user_id();

            // Save widget configuration
            $widget_configs = get_user_meta($user_id, 'hph_widget_configs', true) ?: [];
            $widget_configs[$widget_id] = $config;
            
            update_user_meta($user_id, 'hph_widget_configs', $widget_configs);

            $this->send_success([
                'message' => 'Widget configuration saved',
                'widget_id' => $widget_id
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Widget Config Exception: ' . $e->getMessage());
            $this->send_error('Failed to save widget configuration');
        }
    }

    /**
     * Handle quick actions
     */
    public function handle_quick_action(): void {
        try {
            if (!$this->validate_required_params(['quick_action' => 'string'])) {
                return;
            }

            $action = sanitize_text_field($_POST['quick_action']);
            $params = $_POST['params'] ?? [];

            $result = $this->execute_quick_action($action, $params);

            $this->send_success([
                'action' => $action,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Quick Action Exception: ' . $e->getMessage());
            $this->send_error('Quick action failed');
        }
    }

    /**
     * Handle data export
     */
    public function handle_export_data(): void {
        try {
            if (!$this->validate_required_params(['export_type' => 'string'])) {
                return;
            }

            $export_type = sanitize_text_field($_POST['export_type']);
            $date_range = $_POST['date_range'] ?? [];
            $filters = $_POST['filters'] ?? [];

            $export_data = $this->generate_export_data($export_type, $date_range, $filters);

            $this->send_success([
                'export_type' => $export_type,
                'data' => $export_data,
                'filename' => $this->generate_export_filename($export_type)
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Export Exception: ' . $e->getMessage());
            $this->send_error('Export failed');
        }
    }

    /**
     * Handle notifications retrieval
     */
    public function handle_get_notifications(): void {
        try {
            $user_id = get_current_user_id();
            $limit = intval($_POST['limit'] ?? 10);
            $offset = intval($_POST['offset'] ?? 0);

            $notifications = $this->get_user_notifications($user_id, $limit, $offset);

            $this->send_success([
                'notifications' => $notifications,
                'unread_count' => $this->get_unread_count($user_id)
            ]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Notifications Exception: ' . $e->getMessage());
            $this->send_error('Failed to load notifications');
        }
    }

    /**
     * Handle mark notification as read
     */
    public function handle_mark_notification_read(): void {
        try {
            if (!$this->validate_required_params(['notification_id' => 'int'])) {
                return;
            }

            $notification_id = intval($_POST['notification_id']);
            $user_id = get_current_user_id();

            $result = $this->mark_notification_read($notification_id, $user_id);

            if ($result) {
                $this->send_success([
                    'message' => 'Notification marked as read',
                    'notification_id' => $notification_id
                ]);
            } else {
                $this->send_error('Failed to mark notification as read');
            }

        } catch (\Exception $e) {
            error_log('HPH Dashboard Mark Read Exception: ' . $e->getMessage());
            $this->send_error('Failed to update notification');
        }
    }

    /**
     * Private helper methods
     */

    private function get_overview_statistics(): array {
        $user_id = get_current_user_id();
        
        return [
            'total_listings' => $this->count_user_listings($user_id),
            'active_listings' => $this->count_active_listings($user_id),
            'total_clients' => $this->count_user_clients($user_id),
            'pending_transactions' => $this->count_pending_transactions($user_id),
            'monthly_revenue' => $this->get_monthly_revenue($user_id),
            'leads_this_month' => $this->count_monthly_leads($user_id)
        ];
    }

    private function get_recent_activity(): array {
        $user_id = get_current_user_id();
        
        return [
            'recent_listings' => $this->get_recent_listings($user_id, 5),
            'recent_clients' => $this->get_recent_clients($user_id, 5),
            'recent_transactions' => $this->get_recent_transactions($user_id, 5)
        ];
    }

    private function load_section_data(string $section, int $page, array $filters): array {
        switch ($section) {
            case 'listings':
                return $this->get_listings_data($page, $filters);
            case 'clients':
                return $this->get_clients_data($page, $filters);
            case 'transactions':
                return $this->get_transactions_data($page, $filters);
            case 'reports':
                return $this->get_reports_data($filters);
            default:
                return [];
        }
    }

    private function sanitize_dashboard_settings(array $settings): array {
        $sanitized = [];
        
        // Define allowed settings keys and their types
        $allowed_settings = [
            'layout' => 'string',
            'widgets' => 'array',
            'theme' => 'string',
            'notifications' => 'array',
            'refresh_interval' => 'int'
        ];

        foreach ($allowed_settings as $key => $type) {
            if (isset($settings[$key])) {
                switch ($type) {
                    case 'string':
                        $sanitized[$key] = sanitize_text_field($settings[$key]);
                        break;
                    case 'int':
                        $sanitized[$key] = intval($settings[$key]);
                        break;
                    case 'array':
                        $sanitized[$key] = is_array($settings[$key]) ? $settings[$key] : [];
                        break;
                }
            }
        }

        return $sanitized;
    }

    private function get_default_dashboard_settings(): array {
        return [
            'layout' => 'grid',
            'widgets' => ['overview', 'recent_listings', 'notifications'],
            'theme' => 'light',
            'notifications' => ['email' => true, 'browser' => true],
            'refresh_interval' => 300
        ];
    }

    private function count_user_listings(int $user_id): int {
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        return count($listings);
    }

    private function count_active_listings(int $user_id): int {
        $listings = get_posts([
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        return count($listings);
    }

    private function count_user_clients(int $user_id): int {
        // This would integrate with your client management system
        return 0; // Placeholder
    }

    private function count_pending_transactions(int $user_id): int {
        // This would integrate with your transaction management system
        return 0; // Placeholder
    }

    private function get_monthly_revenue(int $user_id): float {
        // This would integrate with your transaction/commission system
        return 0.0; // Placeholder
    }

    private function count_monthly_leads(int $user_id): int {
        // This would integrate with your lead management system
        return 0; // Placeholder
    }

    private function get_listings_data(int $page, array $filters): array {
        $per_page = $this->dashboard_config['max_items_per_section'];
        $offset = ($page - 1) * $per_page;

        $args = [
            'post_type' => 'listing',
            'post_status' => 'any',
            'posts_per_page' => $per_page,
            'offset' => $offset
        ];

        // Apply filters
        if (!empty($filters['status'])) {
            $args['post_status'] = sanitize_text_field($filters['status']);
        }

        $listings = get_posts($args);
        $formatted_listings = [];

        foreach ($listings as $listing) {
            $formatted_listings[] = [
                'id' => $listing->ID,
                'title' => $listing->post_title,
                'status' => $listing->post_status,
                'date' => $listing->post_date,
                'price' => get_field('price', $listing->ID),
                'address' => get_field('address', $listing->ID)
            ];
        }

        return $formatted_listings;
    }

    private function get_clients_data(int $page, array $filters): array {
        // Placeholder for client data - integrate with your client management
        return [];
    }

    private function get_transactions_data(int $page, array $filters): array {
        // Placeholder for transaction data - integrate with your transaction management
        return [];
    }

    private function get_reports_data(array $filters): array {
        // Placeholder for reports data
        return [];
    }
}