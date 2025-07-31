<?php
/**
 * Dashboard AJAX Handler
 * 
 * File: includes/api/ajax/handlers/class-dashboard-ajax.php
 */

namespace HappyPlace\Api\Ajax\Handlers;

use HappyPlace\Api\Ajax\Base_Ajax_Handler;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Ajax extends Base_Ajax_Handler {
    
    protected function get_actions(): array {
        return [
            'load_dashboard_section' => [
                'callback' => 'handle_load_section',
                'capability' => 'read',
                'rate_limit' => 30,
                'cache' => 600 // 10 minutes
            ],
            'dashboard_quick_stats' => [
                'callback' => 'handle_quick_stats',
                'capability' => 'read',
                'rate_limit' => 20,
                'cache' => 300 // 5 minutes
            ],
            'dashboard_recent_activity' => [
                'callback' => 'handle_recent_activity',
                'capability' => 'read',
                'rate_limit' => 15
            ],
            'dashboard_widget_data' => [
                'callback' => 'handle_widget_data',
                'capability' => 'read',
                'rate_limit' => 25
            ]
        ];
    }
    
    public function handle_load_section(): void {
        try {
            if (!$this->validate_required_params(['section' => 'string'])) {
                return;
            }

            $section = sanitize_text_field($_POST['section']);
            $data = [];

            switch ($section) {
                case 'listings_overview':
                    $data = $this->get_listings_overview();
                    break;
                case 'recent_activity':
                    $data = $this->get_recent_activity();
                    break;
                case 'performance_stats':
                    $data = $this->get_performance_stats();
                    break;
                case 'quick_actions':
                    $data = $this->get_quick_actions();
                    break;
                default:
                    $this->send_error('Unknown dashboard section');
                    return;
            }

            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Ajax Exception: ' . $e->getMessage());
            $this->send_error('Error loading dashboard section');
        }
    }
    
    public function handle_quick_stats(): void {
        try {
            $stats = [
                'total_listings' => $this->count_posts('listing', 'publish'),
                'active_listings' => $this->count_posts('listing', 'publish'),
                'pending_listings' => $this->count_posts('listing', 'pending'),
                'total_agents' => $this->count_users_by_role('agent'),
                'recent_inquiries' => $this->count_recent_inquiries(),
                'this_month_sales' => $this->count_monthly_sales()
            ];

            $this->send_success($stats);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Quick Stats Exception: ' . $e->getMessage());
            $this->send_error('Error loading dashboard statistics');
        }
    }

    public function handle_recent_activity(): void {
        try {
            $limit = intval($_POST['limit'] ?? 10);
            $activities = $this->get_recent_activities($limit);
            $this->send_success(['activities' => $activities]);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Recent Activity Exception: ' . $e->getMessage());
            $this->send_error('Error loading recent activity');
        }
    }

    public function handle_widget_data(): void {
        try {
            if (!$this->validate_required_params(['widget' => 'string'])) {
                return;
            }

            $widget = sanitize_text_field($_POST['widget']);
            $data = [];

            switch ($widget) {
                case 'listings_chart':
                    $data = $this->get_listings_chart_data();
                    break;
                case 'sales_performance':
                    $data = $this->get_sales_performance_data();
                    break;
                case 'agent_activity':
                    $data = $this->get_agent_activity_data();
                    break;
                default:
                    $this->send_error('Unknown widget type');
                    return;
            }

            $this->send_success($data);

        } catch (\Exception $e) {
            error_log('HPH Dashboard Widget Exception: ' . $e->getMessage());
            $this->send_error('Error loading widget data');
        }
    }

    // Helper methods
    private function get_listings_overview(): array {
        return [
            'total' => $this->count_posts('listing'),
            'published' => $this->count_posts('listing', 'publish'),
            'pending' => $this->count_posts('listing', 'pending'),
            'draft' => $this->count_posts('listing', 'draft'),
            'recent' => $this->get_recent_listings(5)
        ];
    }

    private function get_recent_activity(): array {
        return $this->get_recent_activities(10);
    }

    private function get_performance_stats(): array {
        return [
            'views_today' => $this->get_views_count('today'),
            'views_week' => $this->get_views_count('week'),
            'inquiries_today' => $this->count_recent_inquiries(1),
            'inquiries_week' => $this->count_recent_inquiries(7)
        ];
    }

    private function get_quick_actions(): array {
        return [
            'add_listing' => admin_url('post-new.php?post_type=listing'),
            'manage_agents' => admin_url('users.php?role=agent'),
            'view_inquiries' => admin_url('admin.php?page=happy-place-inquiries'),
            'settings' => admin_url('admin.php?page=happy-place-settings')
        ];
    }

    private function count_posts(string $post_type, string $status = 'any'): int {
        $args = ['post_type' => $post_type, 'post_status' => $status, 'numberposts' => -1];
        return count(get_posts($args));
    }

    private function count_users_by_role(string $role): int {
        $users = get_users(['role' => $role, 'count_total' => true]);
        return is_array($users) ? count($users) : $users;
    }

    private function count_recent_inquiries(int $days = 30): int {
        // This would integrate with your inquiries system
        return 0; // Placeholder
    }

    private function count_monthly_sales(): int {
        // This would integrate with your sales tracking
        return 0; // Placeholder
    }

    private function get_recent_listings(int $limit = 5): array {
        $posts = get_posts([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        return array_map(function($post) {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => $post->post_date,
                'status' => $post->post_status,
                'edit_link' => get_edit_post_link($post->ID)
            ];
        }, $posts);
    }

    private function get_recent_activities(int $limit = 10): array {
        // This would integrate with your activity logging system
        return []; // Placeholder - implement based on your activity tracking
    }

    private function get_views_count(string $period): int {
        // This would integrate with your analytics system
        return 0; // Placeholder
    }

    private function get_listings_chart_data(): array {
        // Return data for dashboard charts
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'data' => [10, 15, 12, 18, 22, 25]
        ];
    }

    private function get_sales_performance_data(): array {
        // Return sales performance metrics
        return [
            'total_sales' => 0,
            'average_price' => 0,
            'conversion_rate' => 0
        ];
    }

    private function get_agent_activity_data(): array {
        // Return agent activity metrics
        return [
            'active_agents' => $this->count_users_by_role('agent'),
            'top_performers' => []
        ];
    }
}
