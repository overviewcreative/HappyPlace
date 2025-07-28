<?php
/**
 * View Tracking System for Happy Place Real Estate Platform
 * 
 * Comprehensive tracking of listing views, user engagement, and analytics
 * 
 * @package HappyPlace
 * @subpackage Analytics
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class HPH_View_Tracking {
    
    /**
     * @var HPH_View_Tracking|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array View tracking configuration
     */
    private array $config = [
        'track_anonymous' => true,
        'track_agents' => false, // Don't count agent views of their own listings
        'session_timeout' => 1800, // 30 minutes
        'bot_user_agents' => [
            'googlebot', 'bingbot', 'slurp', 'duckduckbot', 'baiduspider',
            'yandexbot', 'facebookexternalhit', 'twitterbot', 'linkedinbot'
        ]
    ];

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->ensure_database_tables();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Track listing views
        add_action('wp', [$this, 'track_listing_view']);
        
        // AJAX endpoints for real-time tracking
        add_action('wp_ajax_hph_track_view', [$this, 'ajax_track_view']);
        add_action('wp_ajax_nopriv_hph_track_view', [$this, 'ajax_track_view']);
        
        // Dashboard analytics
        add_action('wp_ajax_hph_get_view_analytics', [$this, 'get_view_analytics']);
        
        // Cleanup old data
        add_action('hph_daily_cleanup', [$this, 'cleanup_old_data']);
        
        // Enqueue tracking script
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tracking_script']);
    }

    /**
     * Ensure database tables exist
     */
    private function ensure_database_tables(): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'hph_view_tracking';
        
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(64) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            referrer text,
            view_date datetime DEFAULT CURRENT_TIMESTAMP,
            view_duration int(11) DEFAULT 0,
            page_type varchar(50) DEFAULT 'single',
            device_type varchar(20) DEFAULT 'desktop',
            is_unique_session tinyint(1) DEFAULT 1,
            latitude decimal(10,8) DEFAULT NULL,
            longitude decimal(11,8) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY view_date (view_date),
            KEY is_unique_session (is_unique_session)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create summary table for faster queries
        $summary_table = $wpdb->prefix . 'hph_view_summary';
        
        $summary_sql = "CREATE TABLE IF NOT EXISTS $summary_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            date_recorded date NOT NULL,
            total_views int(11) DEFAULT 0,
            unique_views int(11) DEFAULT 0,
            avg_duration decimal(8,2) DEFAULT 0,
            bounce_rate decimal(5,2) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY listing_date (listing_id, date_recorded),
            KEY listing_id (listing_id),
            KEY date_recorded (date_recorded)
        ) $charset_collate;";

        dbDelta($summary_sql);
    }

    /**
     * Track listing view on page load
     */
    public function track_listing_view(): void {
        if (!is_singular('listing')) {
            return;
        }

        $listing_id = get_the_ID();
        if (!$listing_id) {
            return;
        }

        // Skip if bot or crawler
        if ($this->is_bot()) {
            return;
        }

        // Skip if agent viewing their own listing
        if ($this->is_own_listing($listing_id)) {
            return;
        }

        $this->record_view($listing_id, [
            'page_type' => 'single',
            'track_duration' => true
        ]);
    }

    /**
     * AJAX endpoint for tracking views
     */
    public function ajax_track_view(): void {
        if (!check_ajax_referer('hph_tracking_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        $page_type = sanitize_text_field($_POST['page_type'] ?? 'ajax');

        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
        }

        $view_id = $this->record_view($listing_id, [
            'page_type' => $page_type,
            'duration' => $duration
        ]);

        wp_send_json_success([
            'view_id' => $view_id,
            'tracked' => true
        ]);
    }

    /**
     * Record a view in the database
     */
    private function record_view(int $listing_id, array $options = []): ?int {
        global $wpdb;

        $session_id = $this->get_session_id();
        $ip_address = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        
        // Check if this is a unique session view
        $is_unique = $this->is_unique_session_view($listing_id, $session_id);

        // Get location data
        $location = $this->get_location_data($ip_address);

        $data = [
            'listing_id' => $listing_id,
            'user_id' => is_user_logged_in() ? get_current_user_id() : null,
            'session_id' => $session_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referrer' => $referrer,
            'view_date' => current_time('mysql'),
            'view_duration' => $options['duration'] ?? 0,
            'page_type' => $options['page_type'] ?? 'single',
            'device_type' => $this->get_device_type($user_agent),
            'is_unique_session' => $is_unique ? 1 : 0,
            'latitude' => $location['lat'] ?? null,
            'longitude' => $location['lng'] ?? null,
            'city' => $location['city'] ?? null,
            'country' => $location['country'] ?? null
        ];

        $table_name = $wpdb->prefix . 'hph_view_tracking';
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            error_log('HPH View Tracking: Failed to insert view record - ' . $wpdb->last_error);
            return null;
        }

        $view_id = $wpdb->insert_id;

        // Update listing meta
        $this->update_listing_view_meta($listing_id, $is_unique);

        // Update daily summary
        $this->update_daily_summary($listing_id, $is_unique, $data['view_duration']);

        return $view_id;
    }

    /**
     * Update listing meta fields
     */
    private function update_listing_view_meta(int $listing_id, bool $is_unique): void {
        // Update total views
        $total_views = get_post_meta($listing_id, '_listing_views', true) ?: 0;
        update_post_meta($listing_id, '_listing_views', $total_views + 1);

        // Update unique views
        if ($is_unique) {
            $unique_views = get_post_meta($listing_id, '_listing_unique_views', true) ?: 0;
            update_post_meta($listing_id, '_listing_unique_views', $unique_views + 1);
        }

        // Update monthly views for dashboard
        $monthly_key = '_monthly_views_' . date('Y_m');
        $monthly_views = get_post_meta($listing_id, $monthly_key, true) ?: 0;
        update_post_meta($listing_id, $monthly_key, $monthly_views + 1);

        // Update last viewed
        update_post_meta($listing_id, '_last_viewed', current_time('mysql'));
    }

    /**
     * Update daily summary table
     */
    private function update_daily_summary(int $listing_id, bool $is_unique, int $duration): void {
        global $wpdb;

        $summary_table = $wpdb->prefix . 'hph_view_summary';
        $today = current_time('Y-m-d');

        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $summary_table WHERE listing_id = %d AND date_recorded = %s",
            $listing_id,
            $today
        ));

        if ($existing) {
            // Update existing record
            $total_views = $existing->total_views + 1;
            $unique_views = $existing->unique_views + ($is_unique ? 1 : 0);
            
            // Calculate new average duration
            $total_duration = ($existing->avg_duration * $existing->total_views) + $duration;
            $avg_duration = $total_duration / $total_views;

            $wpdb->update(
                $summary_table,
                [
                    'total_views' => $total_views,
                    'unique_views' => $unique_views,
                    'avg_duration' => round($avg_duration, 2)
                ],
                [
                    'listing_id' => $listing_id,
                    'date_recorded' => $today
                ]
            );
        } else {
            // Create new record
            $wpdb->insert(
                $summary_table,
                [
                    'listing_id' => $listing_id,
                    'date_recorded' => $today,
                    'total_views' => 1,
                    'unique_views' => $is_unique ? 1 : 0,
                    'avg_duration' => $duration
                ]
            );
        }
    }

    /**
     * Get analytics data for dashboard
     */
    public function get_view_analytics(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error('Unauthorized');
        }

        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $period = sanitize_text_field($_POST['period'] ?? '30');

        $analytics = $this->get_listing_analytics($listing_id, $period);

        wp_send_json_success($analytics);
    }

    /**
     * Get comprehensive listing analytics
     */
    public function get_listing_analytics(int $listing_id, string $period = '30'): array {
        global $wpdb;

        $tracking_table = $wpdb->prefix . 'hph_view_tracking';
        $summary_table = $wpdb->prefix . 'hph_view_summary';

        // Calculate date range
        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$period} days"));

        // Get summary data
        $summary = $wpdb->get_results($wpdb->prepare(
            "SELECT date_recorded, total_views, unique_views, avg_duration 
             FROM $summary_table 
             WHERE listing_id = %d AND date_recorded BETWEEN %s AND %s
             ORDER BY date_recorded ASC",
            $listing_id,
            $start_date,
            $end_date
        ));

        // Get device breakdown
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
             FROM $tracking_table 
             WHERE listing_id = %d AND DATE(view_date) BETWEEN %s AND %s
             GROUP BY device_type",
            $listing_id,
            $start_date,
            $end_date
        ));

        // Get referrer data
        $referrers = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                    WHEN referrer LIKE '%%google.%%' THEN 'Google'
                    WHEN referrer LIKE '%%facebook.%%' THEN 'Facebook'
                    WHEN referrer LIKE '%%twitter.%%' THEN 'Twitter'
                    WHEN referrer LIKE '%%linkedin.%%' THEN 'LinkedIn'
                    ELSE 'Other'
                END as source,
                COUNT(*) as count
             FROM $tracking_table 
             WHERE listing_id = %d AND DATE(view_date) BETWEEN %s AND %s
             GROUP BY source
             ORDER BY count DESC",
            $listing_id,
            $start_date,
            $end_date
        ));

        // Get geographic data
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT city, country, COUNT(*) as count
             FROM $tracking_table 
             WHERE listing_id = %d AND DATE(view_date) BETWEEN %s AND %s
             AND city IS NOT NULL
             GROUP BY city, country
             ORDER BY count DESC
             LIMIT 10",
            $listing_id,
            $start_date,
            $end_date
        ));

        // Calculate totals
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT session_id) as unique_views,
                AVG(view_duration) as avg_duration,
                MAX(view_date) as last_view
             FROM $tracking_table 
             WHERE listing_id = %d AND DATE(view_date) BETWEEN %s AND %s",
            $listing_id,
            $start_date,
            $end_date
        ));

        return [
            'period' => $period,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'totals' => $totals,
            'daily_data' => $summary,
            'devices' => $devices,
            'referrers' => $referrers,
            'locations' => $locations
        ];
    }

    /**
     * Get agent's total analytics
     */
    public function get_agent_analytics(int $agent_id, string $period = '30'): array {
        global $wpdb;

        // Get agent's listings
        $listing_ids = get_posts([
            'author' => $agent_id,
            'post_type' => 'listing',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ]);

        if (empty($listing_ids)) {
            return [
                'total_views' => 0,
                'unique_views' => 0,
                'avg_duration' => 0,
                'monthly_views' => 0
            ];
        }

        $tracking_table = $wpdb->prefix . 'hph_view_tracking';
        $placeholders = implode(',', array_fill(0, count($listing_ids), '%d'));

        $end_date = current_time('Y-m-d');
        $start_date = date('Y-m-d', strtotime("-{$period} days"));

        $query = $wpdb->prepare(
            "SELECT 
                COUNT(*) as total_views,
                COUNT(DISTINCT session_id) as unique_views,
                AVG(view_duration) as avg_duration
             FROM $tracking_table 
             WHERE listing_id IN ($placeholders) 
             AND DATE(view_date) BETWEEN %s AND %s",
            array_merge($listing_ids, [$start_date, $end_date])
        );

        $results = $wpdb->get_row($query);

        // Calculate monthly views for dashboard
        $monthly_views = 0;
        foreach ($listing_ids as $listing_id) {
            $monthly_key = '_monthly_views_' . date('Y_m');
            $monthly_views += (int) get_post_meta($listing_id, $monthly_key, true);
        }

        return [
            'total_views' => $results->total_views ?: 0,
            'unique_views' => $results->unique_views ?: 0,
            'avg_duration' => round($results->avg_duration ?: 0, 2),
            'monthly_views' => $monthly_views
        ];
    }

    /**
     * Enqueue tracking script
     */
    public function enqueue_tracking_script(): void {
        if (!is_singular('listing')) {
            return;
        }

        wp_enqueue_script(
            'hph-view-tracking',
            get_template_directory_uri() . '/assets/js/view-tracking.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('hph-view-tracking', 'hphTracking', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_tracking_nonce'),
            'listingId' => get_the_ID(),
            'trackDuration' => true,
            'heartbeatInterval' => 30000 // 30 seconds
        ]);
    }

    /**
     * Helper methods
     */
    private function get_session_id(): string {
        if (!session_id()) {
            session_start();
        }
        return session_id() ?: uniqid('hph_', true);
    }

    private function get_client_ip(): string {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function is_bot(): bool {
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        foreach ($this->config['bot_user_agents'] as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }

    private function is_own_listing(int $listing_id): bool {
        if (!$this->config['track_agents'] && is_user_logged_in()) {
            $listing_author = get_post_field('post_author', $listing_id);
            return get_current_user_id() == $listing_author;
        }
        
        return false;
    }

    private function is_unique_session_view(int $listing_id, string $session_id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_view_tracking';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE listing_id = %d AND session_id = %s 
             AND view_date > %s",
            $listing_id,
            $session_id,
            date('Y-m-d H:i:s', time() - $this->config['session_timeout'])
        ));
        
        return $count == 0;
    }

    private function get_device_type(string $user_agent): string {
        $user_agent = strtolower($user_agent);
        
        if (strpos($user_agent, 'mobile') !== false || strpos($user_agent, 'android') !== false) {
            return 'mobile';
        } elseif (strpos($user_agent, 'tablet') !== false || strpos($user_agent, 'ipad') !== false) {
            return 'tablet';
        }
        
        return 'desktop';
    }

    private function get_location_data(string $ip): array {
        // Simple geolocation - you could integrate with services like MaxMind or IP-API
        // For now, return empty data
        return [];
    }

    /**
     * Cleanup old data
     */
    public function cleanup_old_data(): void {
        global $wpdb;

        $tracking_table = $wpdb->prefix . 'hph_view_tracking';
        $retention_days = apply_filters('hph_view_tracking_retention_days', 365);

        $wpdb->query($wpdb->prepare(
            "DELETE FROM $tracking_table WHERE view_date < %s",
            date('Y-m-d', strtotime("-{$retention_days} days"))
        ));
    }
}

// Initialize the view tracking system
HPH_View_Tracking::instance();
