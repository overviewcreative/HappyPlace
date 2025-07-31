<?php
/**
 * Performance Optimization Manager
 * Phase 4 Day 4-7: Advanced Performance Features
 * 
 * Handles caching, lazy loading, CDN integration, and performance monitoring
 * 
 * @package HappyPlace
 * @subpackage Performance
 * @since 4.4.0
 */

namespace HappyPlace\Performance;

if (!defined('ABSPATH')) {
    exit;
}

class Performance_Optimization_Manager
{
    private static ?self $instance = null;
    private array $cache_strategies = [];
    private array $performance_metrics = [];
    private bool $monitoring_enabled = false;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->init_cache_strategies();
        $this->init_hooks();
        $this->setup_monitoring();
    }

    /**
     * Initialize cache strategies
     */
    private function init_cache_strategies(): void
    {
        $this->cache_strategies = [
            'aggressive' => [
                'listing_cache' => 60, // 1 hour
                'image_cache' => 48, // 48 hours
                'market_cache' => 12, // 12 hours
                'search_cache' => 30, // 30 minutes
                'enabled_features' => ['object_cache', 'transient_cache', 'browser_cache']
            ],
            'balanced' => [
                'listing_cache' => 30, // 30 minutes
                'image_cache' => 24, // 24 hours
                'market_cache' => 6, // 6 hours
                'search_cache' => 15, // 15 minutes
                'enabled_features' => ['object_cache', 'transient_cache']
            ],
            'minimal' => [
                'listing_cache' => 5, // 5 minutes
                'image_cache' => 2, // 2 hours
                'market_cache' => 1, // 1 hour
                'search_cache' => 5, // 5 minutes
                'enabled_features' => ['transient_cache']
            ]
        ];
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        // Performance optimization hooks
        \add_action('wp_enqueue_scripts', [$this, 'optimize_asset_loading']);
        \add_action('wp_head', [$this, 'add_performance_headers'], 1);
        \add_filter('wp_resource_hints', [$this, 'add_resource_hints'], 10, 2);
        
        // Lazy loading hooks
        \add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading'], 10, 3);
        \add_filter('the_content', [$this, 'add_content_lazy_loading']);
        
        // Cache management hooks
        \add_action('hph_clear_performance_cache', [$this, 'clear_all_caches']);
        \add_action('wp_ajax_hph_performance_report', [$this, 'get_performance_report']);
        \add_action('wp_ajax_hph_clear_cache', [$this, 'ajax_clear_cache']);
        
        // Database optimization
        \add_action('hph_optimize_database', [$this, 'optimize_database']);
        
        // CDN integration
        add_filter('wp_get_attachment_url', [$this, 'maybe_use_cdn_url']);
        add_filter('wp_calculate_image_srcset', [$this, 'maybe_use_cdn_srcset']);
    }

    /**
     * Setup performance monitoring
     */
    private function setup_monitoring(): void
    {
        $monitoring_options = get_field('api_performance_monitoring', 'options') ?: [];
        $this->monitoring_enabled = !empty($monitoring_options);
        
        if ($this->monitoring_enabled) {
            add_action('init', [$this, 'start_performance_monitoring']);
            add_action('wp_footer', [$this, 'end_performance_monitoring']);
        }
    }

    /**
     * Get cache strategy based on settings
     */
    public function get_cache_strategy(): array
    {
        $strategy_name = get_field('api_cache_strategy', 'options') ?: 'balanced';
        $strategy = $this->cache_strategies[$strategy_name] ?? $this->cache_strategies['balanced'];
        
        // Override with custom durations if set
        $custom_durations = get_field('api_cache_durations', 'options');
        if ($custom_durations) {
            $strategy['listing_cache'] = $custom_durations['listing_cache'] ?? $strategy['listing_cache'];
            $strategy['image_cache'] = $custom_durations['image_cache'] * 60 ?? $strategy['image_cache']; // Convert hours to minutes
            $strategy['market_cache'] = $custom_durations['market_cache'] * 60 ?? $strategy['market_cache'];
            $strategy['search_cache'] = $custom_durations['search_cache'] ?? $strategy['search_cache'];
        }
        
        return $strategy;
    }

    /**
     * Smart cache management
     */
    public function get_cached_data(string $key, callable $data_callback, string $cache_type = 'listing'): mixed
    {
        $strategy = $this->get_cache_strategy();
        $cache_duration = $strategy["{$cache_type}_cache"] * MINUTE_IN_SECONDS;
        
        // Try object cache first (if available)
        if (in_array('object_cache', $strategy['enabled_features']) && function_exists('wp_cache_get')) {
            $cached = wp_cache_get($key, 'hph_' . $cache_type);
            if ($cached !== false) {
                $this->record_cache_hit($cache_type);
                return $cached;
            }
        }
        
        // Try transient cache
        if (in_array('transient_cache', $strategy['enabled_features'])) {
            $cached = get_transient('hph_' . $key);
            if ($cached !== false) {
                $this->record_cache_hit($cache_type);
                return $cached;
            }
        }
        
        // Cache miss - get fresh data
        $this->record_cache_miss($cache_type);
        $fresh_data = $data_callback();
        
        // Store in caches
        if (in_array('object_cache', $strategy['enabled_features']) && function_exists('wp_cache_set')) {
            wp_cache_set($key, $fresh_data, 'hph_' . $cache_type, $cache_duration);
        }
        
        if (in_array('transient_cache', $strategy['enabled_features'])) {
            set_transient('hph_' . $key, $fresh_data, $cache_duration);
        }
        
        return $fresh_data;
    }

    /**
     * Optimize asset loading
     */
    public function optimize_asset_loading(): void
    {
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', function($tag, $handle) {
            $defer_scripts = ['hph-search-filters', 'hph-lazy-loading', 'hph-analytics'];
            
            if (in_array($handle, $defer_scripts)) {
                return str_replace('></script>', ' defer></script>', $tag);
            }
            
            return $tag;
        }, 10, 2);
        
        // Preload critical resources
        $this->preload_critical_resources();
    }

    /**
     * Add performance headers
     */
    public function add_performance_headers(): void
    {
        // Add DNS prefetch for external domains
        echo '<link rel="dns-prefetch" href="//maps.googleapis.com">' . "\n";
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
        
        // Add preconnect for critical external resources
        echo '<link rel="preconnect" href="https://maps.googleapis.com" crossorigin>' . "\n";
        
        // Add performance hints
        echo '<meta http-equiv="x-dns-prefetch-control" content="on">' . "\n";
    }

    /**
     * Add resource hints
     */
    public function add_resource_hints(array $hints, string $relation_type): array
    {
        if ($relation_type === 'dns-prefetch') {
            $hints[] = 'maps.googleapis.com';
            $hints[] = 'fonts.googleapis.com';
            
            // Add CDN domain if configured
            $cdn_settings = get_field('api_cdn_integration', 'options');
            if ($cdn_settings['cdn_enabled'] && $cdn_settings['cdn_url']) {
                $cdn_domain = parse_url($cdn_settings['cdn_url'], PHP_URL_HOST);
                if ($cdn_domain) {
                    $hints[] = $cdn_domain;
                }
            }
        }
        
        return $hints;
    }

    /**
     * Add lazy loading attributes
     */
    public function add_lazy_loading(array $attr, $attachment, $size): array
    {
        $lazy_options = get_field('api_lazy_loading', 'options') ?: [];
        
        if (in_array('images', $lazy_options) && !is_admin()) {
            $attr['loading'] = 'lazy';
            $attr['decoding'] = 'async';
        }
        
        return $attr;
    }

    /**
     * Add lazy loading to content
     */
    public function add_content_lazy_loading(string $content): string
    {
        $lazy_options = get_field('api_lazy_loading', 'options') ?: [];
        
        if (in_array('images', $lazy_options) && !is_admin()) {
            // Add loading="lazy" to images that don't have it
            $content = preg_replace('/<img(?![^>]*loading=)[^>]*>/i', 
                '<img loading="lazy" decoding="async" $0', $content);
        }
        
        return $content;
    }

    /**
     * CDN URL replacement
     */
    public function maybe_use_cdn_url(string $url): string
    {
        $cdn_settings = get_field('api_cdn_integration', 'options');
        
        if ($cdn_settings['cdn_enabled'] && $cdn_settings['cdn_url']) {
            $upload_dir = wp_upload_dir();
            $upload_url = $upload_dir['baseurl'];
            
            if (strpos($url, $upload_url) === 0) {
                return str_replace($upload_url, rtrim($cdn_settings['cdn_url'], '/'), $url);
            }
        }
        
        return $url;
    }

    /**
     * CDN srcset replacement
     */
    public function maybe_use_cdn_srcset(array $sources): array
    {
        $cdn_settings = get_field('api_cdn_integration', 'options');
        
        if ($cdn_settings['cdn_enabled'] && $cdn_settings['cdn_url']) {
            $upload_dir = wp_upload_dir();
            $upload_url = $upload_dir['baseurl'];
            
            foreach ($sources as &$source) {
                if (strpos($source['url'], $upload_url) === 0) {
                    $source['url'] = str_replace($upload_url, rtrim($cdn_settings['cdn_url'], '/'), $source['url']);
                }
            }
        }
        
        return $sources;
    }

    /**
     * Start performance monitoring
     */
    public function start_performance_monitoring(): void
    {
        if (!$this->monitoring_enabled) return;
        
        $this->performance_metrics['start_time'] = microtime(true);
        $this->performance_metrics['start_memory'] = memory_get_usage(true);
        $this->performance_metrics['queries_start'] = get_num_queries();
    }

    /**
     * End performance monitoring and log results
     */
    public function end_performance_monitoring(): void
    {
        if (!$this->monitoring_enabled || !isset($this->performance_metrics['start_time'])) return;
        
        $end_time = microtime(true);
        $end_memory = memory_get_usage(true);
        $end_queries = get_num_queries();
        
        $metrics = [
            'page_load_time' => $end_time - $this->performance_metrics['start_time'],
            'memory_usage' => $end_memory - $this->performance_metrics['start_memory'],
            'database_queries' => $end_queries - $this->performance_metrics['queries_start'],
            'timestamp' => current_time('mysql'),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        // Store metrics in database
        $this->store_performance_metrics($metrics);
        
        // Check alert thresholds
        $this->check_performance_alerts($metrics);
    }

    /**
     * Store performance metrics
     */
    private function store_performance_metrics(array $metrics): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_performance_metrics';
        
        // Create table if it doesn't exist
        $this->maybe_create_performance_table();
        
        $wpdb->insert(
            $table_name,
            [
                'page_load_time' => $metrics['page_load_time'],
                'memory_usage' => $metrics['memory_usage'],
                'database_queries' => $metrics['database_queries'],
                'url' => $metrics['url'],
                'user_agent' => $metrics['user_agent'],
                'recorded_at' => $metrics['timestamp']
            ],
            ['%f', '%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Create performance metrics table
     */
    private function maybe_create_performance_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'hph_performance_metrics';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            page_load_time decimal(10,4) NOT NULL,
            memory_usage bigint(20) NOT NULL,
            database_queries int(11) NOT NULL,
            url varchar(255) NOT NULL,
            user_agent text,
            recorded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY recorded_at (recorded_at),
            KEY url (url)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Check performance alerts
     */
    private function check_performance_alerts(array $metrics): void
    {
        $dashboard_settings = get_field('api_analytics_dashboard', 'options');
        $thresholds = $dashboard_settings['alert_thresholds'] ?? [];
        
        $alerts = [];
        
        // Check slow response time
        if (isset($thresholds['slow_response']) && $metrics['page_load_time'] > $thresholds['slow_response']) {
            $alerts[] = "Slow page load: {$metrics['page_load_time']}s (threshold: {$thresholds['slow_response']}s)";
        }
        
        // Check excessive database queries
        if ($metrics['database_queries'] > 50) {
            $alerts[] = "High database query count: {$metrics['database_queries']} queries";
        }
        
        // Check memory usage
        if ($metrics['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $memory_mb = round($metrics['memory_usage'] / 1024 / 1024, 2);
            $alerts[] = "High memory usage: {$memory_mb}MB";
        }
        
        // Send alerts if any
        if (!empty($alerts)) {
            $this->send_performance_alerts($alerts, $metrics);
        }
    }

    /**
     * Send performance alerts
     */
    private function send_performance_alerts(array $alerts, array $metrics): void
    {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = "[{$site_name}] Performance Alert";
        $message = "Performance issues detected:\n\n";
        
        foreach ($alerts as $alert) {
            $message .= "• {$alert}\n";
        }
        
        $message .= "\nPage: {$metrics['url']}\n";
        $message .= "Time: {$metrics['timestamp']}\n";
        
        wp_mail($admin_email, $subject, $message);
        
        // Also log to error log
        error_log("HPH Performance Alert: " . implode(', ', $alerts));
    }

    /**
     * Record cache hit
     */
    private function record_cache_hit(string $cache_type): void
    {
        $stats = get_option('hph_cache_stats', []);
        $stats[$cache_type]['hits'] = ($stats[$cache_type]['hits'] ?? 0) + 1;
        update_option('hph_cache_stats', $stats);
    }

    /**
     * Record cache miss
     */
    private function record_cache_miss(string $cache_type): void
    {
        $stats = get_option('hph_cache_stats', []);
        $stats[$cache_type]['misses'] = ($stats[$cache_type]['misses'] ?? 0) + 1;
        update_option('hph_cache_stats', $stats);
    }

    /**
     * Get cache statistics
     */
    public function get_cache_stats(): array
    {
        $stats = get_option('hph_cache_stats', []);
        $formatted_stats = [];
        
        foreach ($stats as $type => $data) {
            $total = ($data['hits'] ?? 0) + ($data['misses'] ?? 0);
            $hit_rate = $total > 0 ? round(($data['hits'] ?? 0) / $total * 100, 2) : 0;
            
            $formatted_stats[$type] = [
                'hits' => $data['hits'] ?? 0,
                'misses' => $data['misses'] ?? 0,
                'total' => $total,
                'hit_rate' => $hit_rate
            ];
        }
        
        return $formatted_stats;
    }

    /**
     * Clear all caches
     */
    public function clear_all_caches(): void
    {
        // Clear WordPress object cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Clear all HPH transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hph_%' 
             OR option_name LIKE '_transient_timeout_hph_%'"
        );
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Reset cache stats
        delete_option('hph_cache_stats');
        
        do_action('hph_after_cache_clear');
    }

    /**
     * AJAX: Get performance report
     */
    public function get_performance_report(): void
    {
        if (!current_user_can('administrator')) {
            wp_die('Access denied');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hph_performance_metrics';
        
        // Get recent metrics (last 24 hours)
        $recent_metrics = $wpdb->get_results(
            "SELECT * FROM {$table_name} 
             WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             ORDER BY recorded_at DESC"
        );
        
        // Calculate averages
        $avg_load_time = $wpdb->get_var(
            "SELECT AVG(page_load_time) FROM {$table_name} 
             WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        $avg_queries = $wpdb->get_var(
            "SELECT AVG(database_queries) FROM {$table_name} 
             WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        $cache_stats = $this->get_cache_stats();
        
        wp_send_json_success([
            'recent_metrics' => $recent_metrics,
            'averages' => [
                'load_time' => round($avg_load_time, 3),
                'database_queries' => round($avg_queries, 1)
            ],
            'cache_stats' => $cache_stats,
            'recommendations' => $this->get_performance_recommendations()
        ]);
    }

    /**
     * Get performance recommendations
     */
    private function get_performance_recommendations(): array
    {
        $recommendations = [];
        $cache_stats = $this->get_cache_stats();
        
        // Check cache hit rates
        foreach ($cache_stats as $type => $stats) {
            if ($stats['hit_rate'] < 80) {
                $recommendations[] = "Low cache hit rate for {$type}: {$stats['hit_rate']}%. Consider increasing cache duration.";
            }
        }
        
        // Check if CDN is enabled
        $cdn_settings = get_field('api_cdn_integration', 'options');
        if (!$cdn_settings['cdn_enabled']) {
            $recommendations[] = "Consider enabling CDN integration to improve image load times.";
        }
        
        // Check lazy loading
        $lazy_options = get_field('api_lazy_loading', 'options') ?: [];
        if (!in_array('images', $lazy_options)) {
            $recommendations[] = "Enable image lazy loading to improve initial page load times.";
        }
        
        return $recommendations;
    }

    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache(): void
    {
        if (!current_user_can('administrator')) {
            wp_die('Access denied');
        }
        
        $this->clear_all_caches();
        
        wp_send_json_success('All caches cleared successfully');
    }

    /**
     * Preload critical resources
     */
    private function preload_critical_resources(): void
    {
        // Preload critical CSS and JS
        echo '<link rel="preload" href="' . plugin_dir_url(__FILE__) . '../assets/css/listing-search.css" as="style">' . "\n";
        echo '<link rel="preload" href="' . plugin_dir_url(__FILE__) . '../assets/js/listing-map.js" as="script">' . "\n";
        
        // Preload Google Fonts if used
        echo '<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" as="style">' . "\n";
    }

    /**
     * Database optimization
     */
    public function optimize_database(): void
    {
        global $wpdb;
        
        // Clean up old performance metrics (older than 30 days)
        $table_name = $wpdb->prefix . 'hph_performance_metrics';
        $wpdb->query(
            "DELETE FROM {$table_name} 
             WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // Optimize tables
        $wpdb->query("OPTIMIZE TABLE {$table_name}");
        
        // Clean up old transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_hph_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        // Update option to track last optimization
        update_option('hph_last_db_optimization', current_time('mysql'));
    }
}

// Initialize the Performance Optimization Manager
add_action('init', function() {
    Performance_Optimization_Manager::get_instance();
});
