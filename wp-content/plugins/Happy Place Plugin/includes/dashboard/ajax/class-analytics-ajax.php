<?php

/**
 * Analytics AJAX Handler
 * 
 * Handles performance analytics, reporting, and dashboard statistics.
 * Provides data for charts, reports, and performance tracking.
 * 
 * @package HappyPlace\Dashboard\Ajax
 * @since 3.0.0
 */

namespace HappyPlace\Dashboard\Ajax;

use Exception;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics AJAX Handler Class
 * 
 * Handles:
 * - Performance data collection and reporting
 * - Chart data generation
 * - Dashboard statistics
 * - Report generation and export
 * - Real-time analytics updates
 */
class HPH_Analytics_Ajax extends HPH_Base_Ajax
{
    /**
     * @var array Available time periods for analytics
     */
    private array $time_periods = [
        '7d' => '7 days',
        '30d' => '30 days',
        '90d' => '90 days',
        '1y' => '1 year'
    ];

    /**
     * @var array Available metrics
     */
    private array $available_metrics = [
        'views' => 'Page Views',
        'inquiries' => 'Inquiries',
        'listings' => 'Listings',
        'leads' => 'Leads',
        'conversions' => 'Conversions'
    ];

    /**
     * Register AJAX actions for analytics
     */
    protected function register_ajax_actions(): void
    {
        // Dashboard stats
        add_action('wp_ajax_hph_get_dashboard_stats', [$this, 'get_dashboard_stats']);
        add_action('wp_ajax_hph_get_overview_stats', [$this, 'get_overview_stats']);
        
        // Performance data
        add_action('wp_ajax_hph_get_performance_data', [$this, 'get_performance_data']);
        add_action('wp_ajax_hph_get_chart_data', [$this, 'get_chart_data']);
        add_action('wp_ajax_hph_get_analytics_summary', [$this, 'get_analytics_summary']);
        
        // Reports
        add_action('wp_ajax_hph_generate_report', [$this, 'generate_report']);
        add_action('wp_ajax_hph_download_report', [$this, 'download_report']);
        add_action('wp_ajax_hph_schedule_report', [$this, 'schedule_report']);
        
        // Real-time tracking
        add_action('wp_ajax_hph_track_event', [$this, 'track_event']);
        add_action('wp_ajax_hph_get_real_time_stats', [$this, 'get_real_time_stats']);
        
        // Public tracking (for frontend)
        add_action('wp_ajax_nopriv_hph_track_page_view', [$this, 'track_page_view']);
        add_action('wp_ajax_hph_track_page_view', [$this, 'track_page_view']);
    }

    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $period = sanitize_key($_POST['period'] ?? '30d');
        
        if (!isset($this->time_periods[$period])) {
            $period = '30d';
        }

        try {
            $user_context = $this->get_user_context();
            $stats = $this->calculate_dashboard_statistics($period, $user_context);

            $this->send_success([
                'stats' => $stats,
                'period' => $period,
                'period_label' => $this->time_periods[$period],
                'last_updated' => current_time('mysql'),
                'cache_duration' => 300 // 5 minutes
            ]);

        } catch (Exception $e) {
            error_log('HPH Dashboard Stats Error: ' . $e->getMessage());
            $this->send_error('Failed to fetch dashboard statistics');
        }
    }

    /**
     * Get performance data for charts
     */
    public function get_performance_data(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $period = sanitize_key($_POST['period'] ?? '30d');
        $metric = sanitize_key($_POST['metric'] ?? 'views');
        
        if (!isset($this->time_periods[$period])) {
            $period = '30d';
        }

        if (!isset($this->available_metrics[$metric])) {
            $metric = 'views';
        }

        try {
            $user_context = $this->get_user_context();
            $performance_data = $this->get_metric_data($metric, $period, $user_context);

            $this->send_success($this->format_response_data($performance_data, 'performance'));

        } catch (Exception $e) {
            error_log('HPH Performance Data Error: ' . $e->getMessage());
            $this->send_error('Failed to fetch performance data');
        }
    }

    /**
     * Get chart data for specific visualization
     */
    public function get_chart_data(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $chart_type = sanitize_key($_POST['chart_type'] ?? 'line');
        $metrics = $_POST['metrics'] ?? ['views'];
        $period = sanitize_key($_POST['period'] ?? '30d');

        try {
            $user_context = $this->get_user_context();
            $chart_data = $this->generate_chart_data($chart_type, $metrics, $period, $user_context);

            $this->send_success([
                'chart_data' => $chart_data,
                'chart_type' => $chart_type,
                'metrics' => $metrics,
                'period' => $period
            ]);

        } catch (Exception $e) {
            error_log('HPH Chart Data Error: ' . $e->getMessage());
            $this->send_error('Failed to generate chart data');
        }
    }

    /**
     * Track user events for analytics
     */
    public function track_event(): void
    {
        $event_type = sanitize_key($_POST['event_type'] ?? '');
        $event_data = $_POST['event_data'] ?? [];
        
        if (empty($event_type)) {
            $this->send_error('Event type required');
            return;
        }

        try {
            $this->record_event($event_type, $event_data);
            $this->send_success(['tracked' => true]);

        } catch (Exception $e) {
            error_log('HPH Event Tracking Error: ' . $e->getMessage());
            $this->send_success(['tracked' => false]); // Don't fail on tracking errors
        }
    }

    /**
     * Track page views (public endpoint)
     */
    public function track_page_view(): void
    {
        $page_id = intval($_POST['page_id'] ?? 0);
        $page_type = sanitize_key($_POST['page_type'] ?? 'listing');
        $referrer = sanitize_url($_POST['referrer'] ?? '');

        if ($page_id <= 0) {
            $this->send_success(['tracked' => false]);
            return;
        }

        try {
            $this->record_page_view($page_id, $page_type, $referrer);
            $this->send_success(['tracked' => true]);

        } catch (Exception $e) {
            error_log('HPH Page View Tracking Error: ' . $e->getMessage());
            $this->send_success(['tracked' => false]);
        }
    }

    /**
     * Generate analytics report
     */
    public function generate_report(): void
    {
        // Security verification
        if (!$this->verify_nonce()) {
            $this->handle_security_failure('nonce');
            return;
        }

        if (!$this->check_capabilities(['edit_posts'])) {
            $this->handle_security_failure('capabilities');
            return;
        }

        $report_type = sanitize_key($_POST['report_type'] ?? 'summary');
        $period = sanitize_key($_POST['period'] ?? '30d');
        $format = sanitize_key($_POST['format'] ?? 'html');

        try {
            $user_context = $this->get_user_context();
            $report_data = $this->build_report($report_type, $period, $format, $user_context);

            $this->send_success([
                'report' => $report_data,
                'report_type' => $report_type,
                'period' => $period,
                'format' => $format,
                'generated_at' => current_time('mysql')
            ]);

        } catch (Exception $e) {
            error_log('HPH Report Generation Error: ' . $e->getMessage());
            $this->send_error('Failed to generate report');
        }
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculate_dashboard_statistics(string $period, array $user_context): array
    {
        $end_date = current_time('mysql');
        $start_date = $this->get_period_start_date($period);
        
        $stats = [
            'total_listings' => $this->get_listings_count($user_context['user_id'], $start_date, $end_date),
            'active_listings' => $this->get_listings_count($user_context['user_id'], $start_date, $end_date, 'publish'),
            'total_leads' => $this->get_leads_count($user_context['user_id'], $start_date, $end_date),
            'new_leads' => $this->get_leads_count($user_context['user_id'], $start_date, $end_date, 'new'),
            'total_views' => $this->get_total_views($user_context['user_id'], $start_date, $end_date),
            'total_inquiries' => $this->get_inquiries_count($user_context['user_id'], $start_date, $end_date),
            'conversion_rate' => $this->calculate_conversion_rate($user_context['user_id'], $start_date, $end_date),
            'avg_price' => $this->calculate_average_listing_price($user_context['user_id'], $start_date, $end_date)
        ];

        // Add comparison with previous period
        $prev_start_date = $this->get_previous_period_start($period, $start_date);
        $prev_stats = [
            'total_listings' => $this->get_listings_count($user_context['user_id'], $prev_start_date, $start_date),
            'total_leads' => $this->get_leads_count($user_context['user_id'], $prev_start_date, $start_date),
            'total_views' => $this->get_total_views($user_context['user_id'], $prev_start_date, $start_date),
            'total_inquiries' => $this->get_inquiries_count($user_context['user_id'], $prev_start_date, $start_date)
        ];

        // Calculate changes
        foreach ($prev_stats as $key => $prev_value) {
            $current_value = $stats[$key];
            $change = $prev_value > 0 ? (($current_value - $prev_value) / $prev_value) * 100 : 0;
            $stats["{$key}_change"] = round($change, 1);
        }

        return $stats;
    }

    /**
     * Get metric data for charts
     */
    private function get_metric_data(string $metric, string $period, array $user_context): array
    {
        $end_date = current_time('mysql');
        $start_date = $this->get_period_start_date($period);
        
        $data_points = $this->get_daily_metric_data($metric, $start_date, $end_date, $user_context['user_id']);
        
        return [
            'labels' => array_keys($data_points),
            'values' => array_values($data_points),
            'total' => array_sum($data_points),
            'period' => $period,
            'metric' => $metric,
            'metric_label' => $this->available_metrics[$metric] ?? $metric
        ];
    }

    /**
     * Generate chart data based on type and metrics
     */
    private function generate_chart_data(string $chart_type, array $metrics, string $period, array $user_context): array
    {
        $chart_data = [
            'type' => $chart_type,
            'datasets' => []
        ];

        $end_date = current_time('mysql');
        $start_date = $this->get_period_start_date($period);

        foreach ($metrics as $metric) {
            if (!isset($this->available_metrics[$metric])) {
                continue;
            }

            $data_points = $this->get_daily_metric_data($metric, $start_date, $end_date, $user_context['user_id']);
            
            $chart_data['datasets'][] = [
                'label' => $this->available_metrics[$metric],
                'data' => array_values($data_points),
                'backgroundColor' => $this->get_metric_color($metric),
                'borderColor' => $this->get_metric_color($metric),
                'tension' => 0.4
            ];
        }

        $chart_data['labels'] = array_keys($data_points ?? []);

        return $chart_data;
    }

    /**
     * Record analytics event
     */
    private function record_event(string $event_type, array $event_data): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_analytics_events';
        
        $wpdb->insert($table_name, [
            'event_type' => $event_type,
            'event_data' => json_encode($event_data),
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        ], ['%s', '%s', '%d', '%s', '%s', '%s']);
    }

    /**
     * Record page view
     */
    private function record_page_view(int $page_id, string $page_type, string $referrer): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_page_views';
        
        $wpdb->insert($table_name, [
            'page_id' => $page_id,
            'page_type' => $page_type,
            'referrer' => $referrer,
            'user_id' => get_current_user_id() ?: null,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'viewed_at' => current_time('mysql')
        ], ['%d', '%s', '%s', '%d', '%s', '%s', '%s']);

        // Update post meta for listing views
        if ($page_type === 'listing') {
            $current_views = get_post_meta($page_id, '_listing_views', true) ?: 0;
            update_post_meta($page_id, '_listing_views', $current_views + 1);
        }
    }

    /**
     * Get period start date
     */
    private function get_period_start_date(string $period): string
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        return date('Y-m-d H:i:s', strtotime("-{$days} days"));
    }

    /**
     * Get previous period start date
     */
    private function get_previous_period_start(string $period, string $current_start): string
    {
        $days = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        return date('Y-m-d H:i:s', strtotime("-{$days} days", strtotime($current_start)));
    }

    /**
     * Get daily metric data
     */
    private function get_daily_metric_data(string $metric, string $start_date, string $end_date, int $user_id): array
    {
        // This would typically query the analytics tables
        // For now, return sample data
        $data_points = [];
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        while ($current_date <= $end_timestamp) {
            $date_key = date('M j', $current_date);
            $data_points[$date_key] = rand(0, 100); // Sample data
            $current_date = strtotime('+1 day', $current_date);
        }

        return $data_points;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string
    {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get color for metric in charts
     */
    private function get_metric_color(string $metric): string
    {
        return match($metric) {
            'views' => '#3b82f6',
            'inquiries' => '#10b981',
            'listings' => '#f59e0b',
            'leads' => '#8b5cf6',
            'conversions' => '#ef4444',
            default => '#6b7280'
        };
    }

    // Placeholder methods for database queries
    private function get_listings_count(int $user_id, string $start_date, string $end_date, string $status = ''): int
    {
        // Would query wp_posts for listings
        return rand(10, 50);
    }

    private function get_leads_count(int $user_id, string $start_date, string $end_date, string $status = ''): int
    {
        // Would query wp_posts for leads
        return rand(5, 25);
    }

    private function get_total_views(int $user_id, string $start_date, string $end_date): int
    {
        // Would query analytics tables
        return rand(100, 1000);
    }

    private function get_inquiries_count(int $user_id, string $start_date, string $end_date): int
    {
        // Would query inquiries/contact forms
        return rand(5, 30);
    }

    private function calculate_conversion_rate(int $user_id, string $start_date, string $end_date): float
    {
        // Would calculate based on leads vs conversions
        return round(rand(5, 15) + (rand(0, 99) / 100), 2);
    }

    private function calculate_average_listing_price(int $user_id, string $start_date, string $end_date): float
    {
        // Would query listing prices
        return rand(200000, 800000);
    }

    // Placeholder methods for future implementation
    public function get_overview_stats(): void { $this->send_error('Not implemented yet'); }
    public function get_analytics_summary(): void { $this->send_error('Not implemented yet'); }
    public function download_report(): void { $this->send_error('Not implemented yet'); }
    public function schedule_report(): void { $this->send_error('Not implemented yet'); }
    public function get_real_time_stats(): void { $this->send_error('Not implemented yet'); }
    
    private function build_report(string $type, string $period, string $format, array $user_context): array
    {
        return ['placeholder' => 'Report generation coming soon'];
    }
}
