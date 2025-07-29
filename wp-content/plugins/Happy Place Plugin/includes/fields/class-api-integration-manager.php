<?php
/**
 * API Integration Manager
 * Phase 4 Day 4-7: API Integrations & Performance Optimization
 * 
 * Manages external API integrations including MLS, Google Maps, and other data sources
 * 
 * @package HappyPlace
 * @subpackage API
 * @since 4.4.0
 */

namespace HappyPlace\API;

if (!defined('ABSPATH')) {
    exit;
}

class API_Integration_Manager
{
    private static ?self $instance = null;
    private array $api_configs = [];
    private array $rate_limits = [];
    private array $cache_durations = [];

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->init_api_configs();
        $this->init_hooks();
    }

    /**
     * Initialize API configurations
     */
    private function init_api_configs(): void
    {
        $this->api_configs = [
            'google_maps' => [
                'base_url' => 'https://maps.googleapis.com/maps/api',
                'key' => get_option('hph_google_maps_api_key', ''),
                'services' => ['geocoding', 'places', 'distance_matrix', 'elevation'],
                'rate_limit' => 2500, // requests per day
                'timeout' => 30
            ],
            'mls_data' => [
                'base_url' => get_option('hph_mls_api_url', ''),
                'key' => get_option('hph_mls_api_key', ''),
                'username' => get_option('hph_mls_username', ''),
                'password' => get_option('hph_mls_password', ''),
                'rate_limit' => 1000, // requests per hour
                'timeout' => 60
            ],
            'property_data' => [
                'base_url' => get_option('hph_property_data_api_url', ''),
                'key' => get_option('hph_property_data_api_key', ''),
                'rate_limit' => 500, // requests per hour
                'timeout' => 45
            ],
            'market_analytics' => [
                'base_url' => get_option('hph_market_analytics_api_url', ''),
                'key' => get_option('hph_market_analytics_api_key', ''),
                'rate_limit' => 200, // requests per hour
                'timeout' => 30
            ]
        ];

        // Set cache durations (in seconds)
        $this->cache_durations = [
            'geocoding' => 7 * DAY_IN_SECONDS, // 1 week
            'property_details' => 6 * HOUR_IN_SECONDS, // 6 hours
            'market_data' => 1 * DAY_IN_SECONDS, // 1 day
            'neighborhood_info' => 3 * DAY_IN_SECONDS, // 3 days
            'school_data' => 7 * DAY_IN_SECONDS, // 1 week
            'crime_stats' => 1 * DAY_IN_SECONDS, // 1 day
            'demographics' => 30 * DAY_IN_SECONDS // 30 days
        ];
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        add_action('init', [$this, 'schedule_api_maintenance']);
        add_action('wp_ajax_hph_test_api_connection', [$this, 'test_api_connection']);
        add_action('wp_ajax_hph_refresh_api_cache', [$this, 'ajax_refresh_api_cache']);
        add_action('wp_ajax_hph_get_cache_stats', [$this, 'ajax_get_cache_stats']);
        add_action('wp_ajax_hph_get_usage_stats', [$this, 'ajax_get_usage_stats']);
        add_action('hph_api_maintenance', [$this, 'perform_api_maintenance']);
        
        // Add settings page
        add_action('admin_menu', [$this, 'add_api_settings_page']);
    }

    /**
     * Make API request with caching and rate limiting
     */
    public function make_api_request(string $service, string $endpoint, array $params = [], array $options = []): array
    {
        // Check rate limits
        if (!$this->check_rate_limit($service)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded for service: ' . $service,
                'data' => null
            ];
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key($service, $endpoint, $params);
        
        // Check cache first
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false && !($options['bypass_cache'] ?? false)) {
            return [
                'success' => true,
                'data' => $cached_data,
                'cached' => true,
                'service' => $service
            ];
        }

        // Make the actual API request
        $response = $this->execute_api_request($service, $endpoint, $params, $options);
        
        // Cache successful responses
        if ($response['success'] && !empty($response['data'])) {
            $cache_duration = $this->get_cache_duration($service, $endpoint);
            set_transient($cache_key, $response['data'], $cache_duration);
        }

        // Log API usage
        $this->log_api_usage($service, $response['success']);

        return $response;
    }

    /**
     * Execute actual API request
     */
    private function execute_api_request(string $service, string $endpoint, array $params, array $options): array
    {
        if (!isset($this->api_configs[$service])) {
            return [
                'success' => false,
                'error' => 'Unknown API service: ' . $service,
                'data' => null
            ];
        }

        $config = $this->api_configs[$service];
        $url = $this->build_api_url($config, $endpoint, $params);
        
        $args = [
            'timeout' => $config['timeout'],
            'headers' => $this->build_headers($service, $config),
            'method' => $options['method'] ?? 'GET'
        ];

        // Add body for POST requests
        if (($options['method'] ?? 'GET') === 'POST') {
            $args['body'] = $options['body'] ?? json_encode($params);
            $args['headers']['Content-Type'] = 'application/json';
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'data' => null
            ];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            return [
                'success' => false,
                'error' => "API request failed with status code: {$status_code}",
                'data' => null,
                'status_code' => $status_code
            ];
        }

        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from API',
                'data' => null
            ];
        }

        return [
            'success' => true,
            'data' => $data,
            'cached' => false,
            'service' => $service
        ];
    }

    /**
     * Build API URL with parameters
     */
    private function build_api_url(array $config, string $endpoint, array $params): string
    {
        $url = rtrim($config['base_url'], '/') . '/' . ltrim($endpoint, '/');
        
        // Add API key to parameters if available
        if (!empty($config['key'])) {
            $params['key'] = $config['key'];
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Build request headers
     */
    private function build_headers(string $service, array $config): array
    {
        $headers = [
            'User-Agent' => 'HappyPlace/1.0 WordPress Plugin',
            'Accept' => 'application/json'
        ];

        // Add authentication headers based on service
        switch ($service) {
            case 'mls_data':
                if (!empty($config['username']) && !empty($config['password'])) {
                    $headers['Authorization'] = 'Basic ' . base64_encode($config['username'] . ':' . $config['password']);
                }
                break;
                
            case 'property_data':
            case 'market_analytics':
                if (!empty($config['key'])) {
                    $headers['X-API-Key'] = $config['key'];
                }
                break;
        }

        return $headers;
    }

    /**
     * Check rate limits for service
     */
    private function check_rate_limit(string $service): bool
    {
        if (!isset($this->api_configs[$service]['rate_limit'])) {
            return true;
        }

        $rate_limit = $this->api_configs[$service]['rate_limit'];
        $current_usage = (int) get_transient("hph_api_usage_{$service}") ?: 0;

        return $current_usage < $rate_limit;
    }

    /**
     * Log API usage for rate limiting
     */
    private function log_api_usage(string $service, bool $success): void
    {
        $usage_key = "hph_api_usage_{$service}";
        $current_usage = (int) get_transient($usage_key) ?: 0;
        
        if ($success) {
            $current_usage++;
            set_transient($usage_key, $current_usage, HOUR_IN_SECONDS);
        }

        // Log detailed usage
        $log_data = [
            'timestamp' => current_time('mysql'),
            'service' => $service,
            'success' => $success,
            'daily_usage' => $current_usage
        ];

        $usage_log = get_option('hph_api_usage_log', []);
        $usage_log[] = $log_data;
        
        // Keep only last 1000 entries
        if (count($usage_log) > 1000) {
            $usage_log = array_slice($usage_log, -1000);
        }
        
        update_option('hph_api_usage_log', $usage_log);
    }

    /**
     * Generate cache key for API request
     */
    private function generate_cache_key(string $service, string $endpoint, array $params): string
    {
        $key_parts = [$service, $endpoint, serialize($params)];
        return 'hph_api_' . md5(implode('|', $key_parts));
    }

    /**
     * Get cache duration for service/endpoint
     */
    private function get_cache_duration(string $service, string $endpoint): int
    {
        // Map endpoints to cache types
        $cache_mapping = [
            'geocode' => 'geocoding',
            'places' => 'neighborhood_info',
            'property' => 'property_details',
            'market' => 'market_data',
            'schools' => 'school_data',
            'crime' => 'crime_stats',
            'demographics' => 'demographics'
        ];

        foreach ($cache_mapping as $endpoint_pattern => $cache_type) {
            if (strpos($endpoint, $endpoint_pattern) !== false) {
                return $this->cache_durations[$cache_type] ?? HOUR_IN_SECONDS;
            }
        }

        // Default cache duration
        return HOUR_IN_SECONDS;
    }

    /**
     * AJAX handler for refreshing API cache
     */
    public function ajax_refresh_api_cache(): void
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error('Access denied');
        }

        $service = sanitize_text_field($_POST['service'] ?? '');
        $this->refresh_api_cache($service);
        
        wp_send_json_success('Cache refreshed successfully');
    }

    /**
     * AJAX handler for getting cache statistics
     */
    public function ajax_get_cache_stats(): void
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error('Access denied');
        }

        $stats = $this->get_cache_statistics();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for getting usage statistics
     */
    public function ajax_get_usage_stats(): void
    {
        if (!current_user_can('administrator')) {
            wp_send_json_error('Access denied');
        }

        $stats = $this->get_usage_statistics();
        wp_send_json_success($stats);
    }

    /**
     * Refresh API cache for specific service
     */
    public function refresh_api_cache(string $service = ''): void
    {
        if (empty($service)) {
            // Clear all API cache
            $this->clear_all_api_cache();
        } else {
            // Clear cache for specific service
            $this->clear_service_cache($service);
        }
    }

    /**
     * Clear all API cache
     */
    private function clear_all_api_cache(): void
    {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hph_api_%' 
             OR option_name LIKE '_transient_timeout_hph_api_%'"
        );
        
        error_log('✅ All API cache cleared');
    }

    /**
     * Clear cache for specific service
     */
    private function clear_service_cache(string $service): void
    {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 OR option_name LIKE %s",
                "_transient_hph_api_{$service}_%",
                "_transient_timeout_hph_api_{$service}_%"
            )
        );
        
        error_log("✅ Cache cleared for service: {$service}");
    }

    /**
     * Schedule API maintenance tasks
     */
    public function schedule_api_maintenance(): void
    {
        if (!wp_next_scheduled('hph_api_maintenance')) {
            wp_schedule_event(time(), 'daily', 'hph_api_maintenance');
        }
    }

    /**
     * Perform API maintenance
     */
    public function perform_api_maintenance(): void
    {
        // Clean old cache entries
        $this->clean_expired_cache();
        
        // Reset daily rate limits
        $this->reset_rate_limits();
        
        // Clean usage logs
        $this->clean_usage_logs();
        
        error_log('✅ API maintenance completed');
    }

    /**
     * Clean expired cache entries
     */
    private function clean_expired_cache(): void
    {
        global $wpdb;
        
        // Clean expired transients
        $wpdb->query(
            "DELETE t1, t2 FROM {$wpdb->options} t1
             LEFT JOIN {$wpdb->options} t2 ON t2.option_name = CONCAT('_transient_timeout_', SUBSTRING(t1.option_name, 12))
             WHERE t1.option_name LIKE '_transient_hph_api_%'
             AND t2.option_value < UNIX_TIMESTAMP()"
        );
    }

    /**
     * Reset daily rate limits
     */
    private function reset_rate_limits(): void
    {
        foreach (array_keys($this->api_configs) as $service) {
            delete_transient("hph_api_usage_{$service}");
        }
    }

    /**
     * Clean old usage logs
     */
    private function clean_usage_logs(): void
    {
        $usage_log = get_option('hph_api_usage_log', []);
        
        // Keep only last 30 days
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        $usage_log = array_filter($usage_log, function($entry) use ($cutoff_date) {
            return $entry['timestamp'] > $cutoff_date;
        });
        
        update_option('hph_api_usage_log', array_values($usage_log));
    }

    /**
     * Test API connection
     */
    public function test_api_connection(): void
    {
        if (!current_user_can('administrator')) {
            wp_die('Access denied');
        }

        $service = sanitize_text_field($_POST['service'] ?? '');
        
        if (empty($service) || !isset($this->api_configs[$service])) {
            wp_send_json_error('Invalid service specified');
        }

        // Test based on service type
        switch ($service) {
            case 'google_maps':
                $result = $this->test_google_maps_connection();
                break;
                
            case 'mls_data':
                $result = $this->test_mls_connection();
                break;
                
            default:
                $result = $this->test_generic_connection($service);
                break;
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test Google Maps API connection
     */
    private function test_google_maps_connection(): array
    {
        $test_response = $this->make_api_request(
            'google_maps',
            'geocode/json',
            ['address' => '1600 Amphitheatre Parkway, Mountain View, CA'],
            ['bypass_cache' => true]
        );

        return [
            'success' => $test_response['success'],
            'message' => $test_response['success'] ? 'Google Maps API connection successful' : $test_response['error'],
            'data' => $test_response['data'] ?? null
        ];
    }

    /**
     * Test MLS API connection
     */
    private function test_mls_connection(): array
    {
        $test_response = $this->make_api_request(
            'mls_data',
            'ping',
            [],
            ['bypass_cache' => true]
        );

        return [
            'success' => $test_response['success'],
            'message' => $test_response['success'] ? 'MLS API connection successful' : $test_response['error'],
            'data' => $test_response['data'] ?? null
        ];
    }

    /**
     * Test generic API connection
     */
    private function test_generic_connection(string $service): array
    {
        $test_response = $this->make_api_request(
            $service,
            'health',
            [],
            ['bypass_cache' => true]
        );

        return [
            'success' => $test_response['success'],
            'message' => $test_response['success'] ? ucwords($service) . ' API connection successful' : $test_response['error'],
            'data' => $test_response['data'] ?? null
        ];
    }

    /**
     * Add API settings page
     */
    public function add_api_settings_page(): void
    {
        add_options_page(
            'Happy Place API Settings',
            'API Settings',
            'administrator',
            'hph-api-settings',
            [$this, 'render_api_settings_page']
        );
    }

    /**
     * Render API settings page
     */
    public function render_api_settings_page(): void
    {
        if (isset($_POST['submit'])) {
            $this->save_api_settings();
        }
        
        include plugin_dir_path(__FILE__) . '../templates/api-settings-page.php';
    }

    /**
     * Save API settings
     */
    private function save_api_settings(): void
    {
        if (!current_user_can('administrator') || !wp_verify_nonce($_POST['hph_api_nonce'], 'hph_api_settings')) {
            wp_die('Security check failed');
        }

        $settings = [
            'hph_google_maps_api_key',
            'hph_mls_api_url',
            'hph_mls_api_key',
            'hph_mls_username',
            'hph_mls_password',
            'hph_property_data_api_url',
            'hph_property_data_api_key',
            'hph_market_analytics_api_url',
            'hph_market_analytics_api_key'
        ];

        foreach ($settings as $setting) {
            $value = sanitize_text_field($_POST[$setting] ?? '');
            update_option($setting, $value);
        }

        add_settings_error('hph_api_settings', 'settings_updated', 'API settings saved successfully!', 'updated');
    }

    /**
     * Get API usage statistics
     */
    public function get_usage_statistics(): array
    {
        $stats = [];
        
        foreach (array_keys($this->api_configs) as $service) {
            $current_usage = (int) get_transient("hph_api_usage_{$service}") ?: 0;
            $rate_limit = $this->api_configs[$service]['rate_limit'];
            
            $stats[$service] = [
                'current_usage' => $current_usage,
                'rate_limit' => $rate_limit,
                'percentage' => $rate_limit > 0 ? round(($current_usage / $rate_limit) * 100, 2) : 0,
                'remaining' => max(0, $rate_limit - $current_usage)
            ];
        }
        
        return $stats;
    }

    /**
     * Get cache statistics
     */
    public function get_cache_statistics(): array
    {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hph_api_%'"
        );
        
        $cache_size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_hph_api_%'"
        );
        
        return [
            'total_entries' => (int) $cache_count,
            'total_size_bytes' => (int) $cache_size,
            'total_size_mb' => round($cache_size / 1024 / 1024, 2)
        ];
    }
}

// Initialize the API Integration Manager
add_action('init', function() {
    API_Integration_Manager::get_instance();
});
