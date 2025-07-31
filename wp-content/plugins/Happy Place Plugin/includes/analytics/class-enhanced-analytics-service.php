<?php
/**
 * Enhanced Analytics Service
 * Phase 4 Day 4-7: Advanced Analytics & Reporting
 * 
 * Comprehensive analytics tracking, user behavior analysis, and performance reporting
 * 
 * @package HappyPlace
 * @subpackage Analytics
 * @since 4.4.0
 */

namespace HappyPlace\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Analytics_Service
{
    private static ?self $instance = null;
    private array $tracking_config = [];
    private bool $analytics_enabled = false;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->init_tracking_config();
        $this->init_hooks();
        $this->setup_database_tables();
    }

    /**
     * Initialize tracking configuration
     */
    private function init_tracking_config(): void
    {
        $tracking_options = get_field('api_user_behavior_tracking', 'options') ?: [];
        $this->analytics_enabled = !empty($tracking_options);
        
        $this->tracking_config = [
            'search_patterns' => in_array('search_patterns', $tracking_options),
            'listing_views' => in_array('listing_views', $tracking_options),
            'filter_usage' => in_array('filter_usage', $tracking_options),
            'session_duration' => in_array('session_duration', $tracking_options),
            'bounce_rate' => in_array('bounce_rate', $tracking_options),
            'conversion_tracking' => in_array('conversion_tracking', $tracking_options)
        ];
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        if (!$this->analytics_enabled) return;
        
        // Tracking hooks
        \add_action('wp_footer', [$this, 'add_tracking_script']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueue_analytics_scripts']);
        
        // AJAX handlers for analytics data
        \add_action('wp_ajax_hph_track_event', [$this, 'track_event']);
        \add_action('wp_ajax_nopriv_hph_track_event', [$this, 'track_event']);
        \add_action('wp_ajax_hph_analytics_report', [$this, 'get_analytics_report']);
        
        // Automatic tracking hooks
        \add_action('wp', [$this, 'track_page_view']);
        \add_action('hph_listing_view', [$this, 'track_listing_view']);
        \add_action('hph_search_performed', [$this, 'track_search_event']);
        \add_action('hph_filter_applied', [$this, 'track_filter_usage']);
        \add_action('hph_lead_submitted', [$this, 'track_conversion']);
        
        // Cleanup old data
        \add_action('hph_analytics_cleanup', [$this, 'cleanup_old_data']);
        
        // Schedule cleanup
        if (!\wp_next_scheduled('hph_analytics_cleanup')) {
            \wp_schedule_event(\time(), 'daily', 'hph_analytics_cleanup');
        }
    }

    /**
     * Setup database tables for analytics
     */
    private function setup_database_tables(): void
    {
        global $wpdb;
        
        $this->create_page_views_table();
        $this->create_user_sessions_table();
        $this->create_search_analytics_table();
        $this->create_conversion_tracking_table();
    }

    /**
     * Create page views table
     */
    private function create_page_views_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_page_views';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            page_url varchar(500) NOT NULL,
            page_title varchar(255) DEFAULT NULL,
            page_type varchar(50) DEFAULT NULL,
            listing_id bigint(20) DEFAULT NULL,
            referrer varchar(500) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            view_duration int(11) DEFAULT 0,
            scroll_depth int(3) DEFAULT 0,
            exit_page boolean DEFAULT FALSE,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY listing_id (listing_id),
            KEY page_type (page_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create user sessions table
     */
    private function create_user_sessions_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_user_sessions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL UNIQUE,
            user_id bigint(20) DEFAULT NULL,
            session_start datetime NOT NULL,
            session_end datetime DEFAULT NULL,
            session_duration int(11) DEFAULT 0,
            page_views int(11) DEFAULT 0,
            bounce boolean DEFAULT TRUE,
            conversion boolean DEFAULT FALSE,
            conversion_type varchar(50) DEFAULT NULL,
            source varchar(100) DEFAULT NULL,
            medium varchar(100) DEFAULT NULL,
            campaign varchar(100) DEFAULT NULL,
            device_type varchar(20) DEFAULT NULL,
            browser varchar(50) DEFAULT NULL,
            os varchar(50) DEFAULT NULL,
            country varchar(2) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY user_id (user_id),
            KEY session_start (session_start)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create search analytics table
     */
    private function create_search_analytics_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_search_analytics';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            search_query text DEFAULT NULL,
            search_filters json DEFAULT NULL,
            results_count int(11) DEFAULT 0,
            click_position int(11) DEFAULT NULL,
            clicked_listing_id bigint(20) DEFAULT NULL,
            no_results boolean DEFAULT FALSE,
            search_duration int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY clicked_listing_id (clicked_listing_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create conversion tracking table
     */
    private function create_conversion_tracking_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_conversions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            conversion_type varchar(50) NOT NULL,
            listing_id bigint(20) DEFAULT NULL,
            agent_id bigint(20) DEFAULT NULL,
            conversion_value decimal(10,2) DEFAULT NULL,
            form_data json DEFAULT NULL,
            source_page varchar(500) DEFAULT NULL,
            time_to_conversion int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY conversion_type (conversion_type),
            KEY listing_id (listing_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get or create session ID
     */
    private function get_session_id(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['hph_session_id'])) {
            $_SESSION['hph_session_id'] = wp_generate_uuid4();
            
            // Create session record
            $this->create_session_record($_SESSION['hph_session_id']);
        }
        
        return $_SESSION['hph_session_id'];
    }

    /**
     * Create session record
     */
    private function create_session_record(string $session_id): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_user_sessions';
        
        // Parse user agent for device info
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device_info = $this->parse_user_agent($user_agent);
        
        // Parse referrer for source/medium/campaign
        $source_info = $this->parse_referrer($_SERVER['HTTP_REFERER'] ?? '');
        
        $wpdb->insert(
            $table_name,
            [
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null,
                'session_start' => current_time('mysql'),
                'source' => $source_info['source'],
                'medium' => $source_info['medium'],
                'campaign' => $source_info['campaign'],
                'device_type' => $device_info['device_type'],
                'browser' => $device_info['browser'],
                'os' => $device_info['os']
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Track page view
     */
    public function track_page_view(): void
    {
        if (!$this->analytics_enabled || \is_admin()) return;
        
        $session_id = $this->get_session_id();
        
        global $wpdb, $post;
        
        $page_data = [
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: null,
            'page_url' => home_url($_SERVER['REQUEST_URI'] ?? ''),
            'page_title' => wp_get_document_title(),
            'page_type' => $this->determine_page_type(),
            'listing_id' => ($post && $post->post_type === 'listing') ? $post->ID : null,
            'referrer' => $_SERVER['HTTP_REFERER'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time('mysql')
        ];
        
        $table_name = $wpdb->prefix . 'hph_page_views';
        $wpdb->insert($table_name, $page_data);
        
        // Update session page count
        $this->update_session_stats($session_id);
    }

    /**
     * Track listing view
     */
    public function track_listing_view(int $listing_id): void
    {
        if (!$this->tracking_config['listing_views']) return;
        
        $session_id = $this->get_session_id();
        
        // Track as a specific event
        $this->track_custom_event('listing_view', [
            'listing_id' => $listing_id,
            'listing_price' => get_field('price', $listing_id),
            'listing_type' => get_field('property_type', $listing_id),
            'listing_city' => get_field('city', $listing_id)
        ]);
    }

    /**
     * Track search event
     */
    public function track_search_event(array $search_data): void
    {
        if (!$this->tracking_config['search_patterns']) return;
        
        global $wpdb;
        
        $session_id = $this->get_session_id();
        
        $table_name = $wpdb->prefix . 'hph_search_analytics';
        $wpdb->insert(
            $table_name,
            [
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null,
                'search_query' => $search_data['query'] ?? '',
                'search_filters' => json_encode($search_data['filters'] ?? []),
                'results_count' => $search_data['results_count'] ?? 0,
                'no_results' => ($search_data['results_count'] ?? 0) === 0,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%s', '%d', '%d', '%s']
        );
    }

    /**
     * Track filter usage
     */
    public function track_filter_usage(array $filter_data): void
    {
        if (!$this->tracking_config['filter_usage']) return;
        
        $this->track_custom_event('filter_applied', $filter_data);
    }

    /**
     * Track conversion
     */
    public function track_conversion(array $conversion_data): void
    {
        if (!$this->tracking_config['conversion_tracking']) return;
        
        global $wpdb;
        
        $session_id = $this->get_session_id();
        
        // Calculate time to conversion
        $session_start = $wpdb->get_var($wpdb->prepare(
            "SELECT session_start FROM {$wpdb->prefix}hph_user_sessions WHERE session_id = %s",
            $session_id
        ));
        
        $time_to_conversion = $session_start ? 
            strtotime(current_time('mysql')) - strtotime($session_start) : 0;
        
        $table_name = $wpdb->prefix . 'hph_conversions';
        $wpdb->insert(
            $table_name,
            [
                'session_id' => $session_id,
                'user_id' => get_current_user_id() ?: null,
                'conversion_type' => $conversion_data['type'],
                'listing_id' => $conversion_data['listing_id'] ?? null,
                'agent_id' => $conversion_data['agent_id'] ?? null,
                'conversion_value' => $conversion_data['value'] ?? 0,
                'form_data' => json_encode($conversion_data['form_data'] ?? []),
                'source_page' => $conversion_data['source_page'] ?? '',
                'time_to_conversion' => $time_to_conversion,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%d', '%d', '%f', '%s', '%s', '%d', '%s']
        );
        
        // Mark session as converted
        $wpdb->update(
            $wpdb->prefix . 'hph_user_sessions',
            [
                'conversion' => true,
                'conversion_type' => $conversion_data['type']
            ],
            ['session_id' => $session_id],
            ['%d', '%s'],
            ['%s']
        );
    }

    /**
     * Track custom event via AJAX
     */
    public function track_event(): void
    {
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        $event_data = $_POST['event_data'] ?? [];
        
        if (empty($event_type)) {
            \wp_send_json_error('Event type required');
        }
        
        $this->track_custom_event($event_type, $event_data);
        \wp_send_json_success('Event tracked');
    }

    /**
     * Track custom event
     */
    private function track_custom_event(string $event_type, array $event_data): void
    {
        global $wpdb;
        
        $session_id = $this->get_session_id();
        
        // Store in a generic events table or use existing tables based on event type
        switch ($event_type) {
            case 'scroll_depth':
                $this->update_page_scroll_depth($session_id, $event_data['depth'] ?? 0);
                break;
                
            case 'time_on_page':
                $this->update_page_duration($session_id, $event_data['duration'] ?? 0);
                break;
                
            default:
                // Store in a generic events table (create if needed)
                $this->store_generic_event($session_id, $event_type, $event_data);
        }
    }

    /**
     * Update page scroll depth
     */
    private function update_page_scroll_depth(string $session_id, int $depth): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_page_views';
        $current_url = home_url($_SERVER['REQUEST_URI'] ?? '');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET scroll_depth = GREATEST(scroll_depth, %d) 
             WHERE session_id = %s AND page_url = %s 
             ORDER BY id DESC LIMIT 1",
            $depth, $session_id, $current_url
        ));
    }

    /**
     * Update page duration
     */
    private function update_page_duration(string $session_id, int $duration): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_page_views';
        $current_url = home_url($_SERVER['REQUEST_URI'] ?? '');
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} 
             SET view_duration = %d 
             WHERE session_id = %s AND page_url = %s 
             ORDER BY id DESC LIMIT 1",
            $duration, $session_id, $current_url
        ));
    }

    /**
     * Update session statistics
     */
    private function update_session_stats(string $session_id): void
    {
        global $wpdb;
        
        $sessions_table = $wpdb->prefix . 'hph_user_sessions';
        
        // Count page views in this session
        $page_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hph_page_views WHERE session_id = %s",
            $session_id
        ));
        
        // Update bounce status (more than 1 page view = not a bounce)
        $bounce = $page_views <= 1;
        
        $wpdb->update(
            $sessions_table,
            [
                'page_views' => $page_views,
                'bounce' => $bounce,
                'session_end' => current_time('mysql')
            ],
            ['session_id' => $session_id],
            ['%d', '%d', '%s'],
            ['%s']
        );
    }

    /**
     * Get analytics report
     */
    public function get_analytics_report(): void
    {
        if (!\current_user_can('administrator')) {
            \wp_die('Access denied');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '7days');
        $report_data = $this->generate_analytics_report($period);
        
        \wp_send_json_success($report_data);
    }

    /**
     * Generate analytics report
     */
    private function generate_analytics_report(string $period): array
    {
        global $wpdb;
        
        // Determine date range
        $date_clause = $this->get_date_clause($period);
        
        // Get basic metrics
        $sessions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hph_user_sessions WHERE {$date_clause}");
        $page_views = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hph_page_views WHERE {$date_clause}");
        $bounces = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hph_user_sessions WHERE bounce = 1 AND {$date_clause}");
        $conversions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}hph_conversions WHERE {$date_clause}");
        
        // Calculate rates
        $bounce_rate = $sessions > 0 ? round(($bounces / $sessions) * 100, 2) : 0;
        $conversion_rate = $sessions > 0 ? round(($conversions / $sessions) * 100, 2) : 0;
        $avg_pages_per_session = $sessions > 0 ? round($page_views / $sessions, 2) : 0;
        
        // Get top pages
        $top_pages = $wpdb->get_results(
            "SELECT page_url, page_title, COUNT(*) as views 
             FROM {$wpdb->prefix}hph_page_views 
             WHERE {$date_clause} 
             GROUP BY page_url, page_title 
             ORDER BY views DESC 
             LIMIT 10"
        );
        
        // Get top searches
        $top_searches = $wpdb->get_results(
            "SELECT search_query, COUNT(*) as searches 
             FROM {$wpdb->prefix}hph_search_analytics 
             WHERE search_query != '' AND {$date_clause} 
             GROUP BY search_query 
             ORDER BY searches DESC 
             LIMIT 10"
        );
        
        // Get conversion types
        $conversion_types = $wpdb->get_results(
            "SELECT conversion_type, COUNT(*) as count 
             FROM {$wpdb->prefix}hph_conversions 
             WHERE {$date_clause} 
             GROUP BY conversion_type 
             ORDER BY count DESC"
        );
        
        return [
            'overview' => [
                'sessions' => $sessions,
                'page_views' => $page_views,
                'bounce_rate' => $bounce_rate,
                'conversion_rate' => $conversion_rate,
                'avg_pages_per_session' => $avg_pages_per_session
            ],
            'top_pages' => $top_pages,
            'top_searches' => $top_searches,
            'conversions' => $conversion_types,
            'period' => $period
        ];
    }

    /**
     * Get date clause for SQL queries
     */
    private function get_date_clause(string $period): string
    {
        switch ($period) {
            case '24hours':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            case '7days':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30days':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '3months':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            default:
                return "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }
    }

    /**
     * Add tracking script to footer
     */
    public function add_tracking_script(): void
    {
        if (!$this->analytics_enabled || \is_admin()) return;
        
        ?>
        <script>
        (function() {
            'use strict';
            
            // Track page view duration
            let startTime = Date.now();
            let maxScroll = 0;
            
            // Track scroll depth
            function trackScroll() {
                const scrollPercent = Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100);
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                }
            }
            
            // Send tracking data
            function sendTrackingData(eventType, data) {
                if (typeof hph_ajax_url === 'undefined') return;
                
                fetch(hph_ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'hph_track_event',
                        event_type: eventType,
                        event_data: JSON.stringify(data)
                    })
                }).catch(console.error);
            }
            
            // Track scroll depth
            window.addEventListener('scroll', trackScroll, { passive: true });
            
            // Track time on page when leaving
            window.addEventListener('beforeunload', function() {
                const timeOnPage = Math.round((Date.now() - startTime) / 1000);
                
                sendTrackingData('time_on_page', { duration: timeOnPage });
                sendTrackingData('scroll_depth', { depth: maxScroll });
            });
            
            // Track listing views
            if (document.body.classList.contains('single-listing')) {
                const listingId = document.querySelector('[data-listing-id]')?.dataset.listingId;
                if (listingId) {
                    sendTrackingData('listing_view', { listing_id: listingId });
                }
            }
            
            // Track search interactions
            const searchForms = document.querySelectorAll('.hph-search-form');
            searchForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const formData = new FormData(form);
                    const searchData = {};
                    
                    for (let [key, value] of formData.entries()) {
                        searchData[key] = value;
                    }
                    
                    sendTrackingData('search_performed', searchData);
                });
            });
            
        })();
        </script>
        <?php
    }

    /**
     * Enqueue analytics scripts
     */
    public function enqueue_analytics_scripts(): void
    {
        wp_localize_script('jquery', 'hph_ajax_url', admin_url('admin-ajax.php'));
    }

    /**
     * Parse user agent for device information
     */
    private function parse_user_agent(string $user_agent): array
    {
        $device_type = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';
        
        // Simple device detection
        if (preg_match('/Mobile|Android|iPhone|iPad/', $user_agent)) {
            $device_type = preg_match('/iPad/', $user_agent) ? 'tablet' : 'mobile';
        }
        
        // Simple browser detection
        if (preg_match('/Chrome/', $user_agent)) $browser = 'Chrome';
        elseif (preg_match('/Firefox/', $user_agent)) $browser = 'Firefox';
        elseif (preg_match('/Safari/', $user_agent)) $browser = 'Safari';
        elseif (preg_match('/Edge/', $user_agent)) $browser = 'Edge';
        
        // Simple OS detection
        if (preg_match('/Windows/', $user_agent)) $os = 'Windows';
        elseif (preg_match('/Mac/', $user_agent)) $os = 'macOS';
        elseif (preg_match('/Linux/', $user_agent)) $os = 'Linux';
        elseif (preg_match('/Android/', $user_agent)) $os = 'Android';
        elseif (preg_match('/iOS/', $user_agent)) $os = 'iOS';
        
        return [
            'device_type' => $device_type,
            'browser' => $browser,
            'os' => $os
        ];
    }

    /**
     * Parse referrer for source information
     */
    private function parse_referrer(string $referrer): array
    {
        if (empty($referrer)) {
            return ['source' => 'direct', 'medium' => 'none', 'campaign' => null];
        }
        
        $host = parse_url($referrer, PHP_URL_HOST);
        
        // Check for social media
        $social_sites = ['facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com'];
        foreach ($social_sites as $site) {
            if (strpos($host, $site) !== false) {
                return ['source' => $site, 'medium' => 'social', 'campaign' => null];
            }
        }
        
        // Check for search engines
        $search_engines = ['google.com', 'bing.com', 'yahoo.com', 'duckduckgo.com'];
        foreach ($search_engines as $engine) {
            if (strpos($host, $engine) !== false) {
                return ['source' => $engine, 'medium' => 'organic', 'campaign' => null];
            }
        }
        
        // Default to referral
        return ['source' => $host, 'medium' => 'referral', 'campaign' => null];
    }

    /**
     * Determine page type
     */
    private function determine_page_type(): string
    {
        global $post;
        
        if (is_home() || is_front_page()) return 'home';
        if (is_single() && $post->post_type === 'listing') return 'listing_detail';
        if (is_post_type_archive('listing')) return 'listing_archive';
        if (is_search()) return 'search_results';
        if (is_page()) return 'page';
        if (is_single()) return 'post';
        
        return 'other';
    }

    /**
     * Get client IP address
     */
    private function get_client_ip(): string
    {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle multiple IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * Store generic event
     */
    private function store_generic_event(string $session_id, string $event_type, array $event_data): void
    {
        // This would create a generic events table if needed
        // For now, we'll just log it
        error_log("HPH Analytics Event: {$event_type} - " . json_encode($event_data));
    }

    /**
     * Cleanup old analytics data
     */
    public function cleanup_old_data(): void
    {
        global $wpdb;
        
        $retention_days = 90; // Keep 90 days of data
        
        $tables = [
            $wpdb->prefix . 'hph_page_views',
            $wpdb->prefix . 'hph_user_sessions',
            $wpdb->prefix . 'hph_search_analytics',
            $wpdb->prefix . 'hph_conversions'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $retention_days
            ));
        }
        
        error_log("✅ Analytics data cleanup completed - removed data older than {$retention_days} days");
    }
}

// Initialize the Enhanced Analytics Service
add_action('init', function() {
    Enhanced_Analytics_Service::get_instance();
});
