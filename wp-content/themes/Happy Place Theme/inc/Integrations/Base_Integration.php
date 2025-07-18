<?php

namespace HappyPlace\Integration;

/**
 * Base Integration Framework
 *
 * Provides a standardized foundation for all external integrations
 * including API clients, rate limiting, caching, and error handling.
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
abstract class Base_Integration {
    
    /**
     * API client instance
     * @var mixed
     */
    protected $api_client;
    
    /**
     * Integration configuration
     * @var array
     */
    protected $config;
    
    /**
     * Cache manager instance
     * @var Integration_Cache_Manager
     */
    protected $cache_manager;
    
    /**
     * Rate limiter instance
     * @var API_Rate_Limiter
     */
    protected $rate_limiter;
    
    /**
     * Webhook handler instance
     * @var Webhook_Handler
     */
    protected $webhook_handler;
    
    /**
     * Integration name/type
     * @var string
     */
    protected $integration_type;
    
    /**
     * Last sync timestamp
     * @var int
     */
    protected $last_sync;
    
    /**
     * Error log
     * @var array
     */
    protected $errors = [];
    
    /**
     * Constructor
     * 
     * @param array $config Integration configuration
     */
    public function __construct($config = []) {
        $this->config = wp_parse_args($config, $this->get_defaults());
        $this->integration_type = $this->get_integration_type();
        
        // Initialize managers
        $this->cache_manager = new Integration_Cache_Manager($this->integration_type);
        $this->rate_limiter = new API_Rate_Limiter($this->get_rate_limits());
        $this->webhook_handler = new Webhook_Handler($this->get_webhook_config());
        
        // Initialize API client
        $this->init_api_client();
        
        // Load last sync info
        $this->last_sync = get_option("hph_{$this->integration_type}_last_sync", 0);
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Abstract methods for implementation
     */
    abstract protected function init_api_client();
    abstract protected function get_defaults();
    abstract protected function get_rate_limits();
    abstract protected function get_webhook_config();
    abstract protected function get_integration_type();
    abstract protected function transform_incoming_data($data);
    abstract protected function transform_outgoing_data($data);
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        // Cron sync
        add_action("hph_{$this->integration_type}_sync_cron", [$this, 'scheduled_sync']);
        
        // Webhook endpoint
        add_action('rest_api_init', [$this, 'register_webhook_endpoint']);
        
        // Admin notices for errors
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }
    
    /**
     * Main data synchronization method
     * 
     * @param string $direction Sync direction: 'incoming', 'outgoing', or 'bidirectional'
     * @return array Sync results
     */
    public function sync_data($direction = 'bidirectional') {
        try {
            // Check rate limits
            $this->rate_limiter->check_limits();
            
            $sync_start = microtime(true);
            $results = [
                'direction' => $direction,
                'timestamp' => current_time('mysql'),
                'success' => false,
                'data' => []
            ];
            
            switch ($direction) {
                case 'incoming':
                    $results['data'] = $this->sync_incoming_data();
                    break;
                    
                case 'outgoing':
                    $results['data'] = $this->sync_outgoing_data();
                    break;
                    
                case 'bidirectional':
                default:
                    $incoming = $this->sync_incoming_data();
                    $outgoing = $this->sync_outgoing_data();
                    $results['data'] = [
                        'incoming' => $incoming,
                        'outgoing' => $outgoing
                    ];
                    break;
            }
            
            $results['success'] = true;
            $results['duration'] = microtime(true) - $sync_start;
            
            // Update last sync timestamp
            $this->last_sync = time();
            update_option("hph_{$this->integration_type}_last_sync", $this->last_sync);
            
            // Log successful sync
            $this->log_sync_result($results);
            
            return $results;
            
        } catch (\Exception $e) {
            $this->log_error('Sync failed', $e);
            throw new Integration_Exception('Sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync data from external source to WordPress
     * 
     * @return array Sync results
     */
    protected function sync_incoming_data() {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];
        
        try {
            $external_data = $this->fetch_external_data();
            
            foreach ($external_data as $item) {
                try {
                    $wp_data = $this->transform_incoming_data($item);
                    $result = $this->upsert_wp_data($wp_data);
                    
                    if ($result['created']) {
                        $results['created']++;
                    } else {
                        $results['updated']++;
                    }
                    
                    $results['processed']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'item_id' => $item['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            throw new Integration_Exception('Failed to fetch external data: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Sync data from WordPress to external source
     * 
     * @return array Sync results
     */
    protected function sync_outgoing_data() {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'errors' => []
        ];
        
        try {
            $wp_data = $this->fetch_wp_data();
            
            foreach ($wp_data as $item) {
                try {
                    $external_data = $this->transform_outgoing_data($item);
                    $result = $this->send_to_external($external_data);
                    
                    if ($result) {
                        $results['sent']++;
                    }
                    
                    $results['processed']++;
                    
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'item_id' => $item['ID'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
        } catch (\Exception $e) {
            throw new Integration_Exception('Failed to sync outgoing data: ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Real-time webhook processing
     * 
     * @param array $payload Webhook payload
     * @return bool Success status
     */
    public function handle_webhook($payload) {
        try {
            // Validate webhook signature
            $this->validate_webhook_signature($payload);
            
            // Process webhook data
            $data = $this->transform_incoming_data($payload['data']);
            $result = $this->process_webhook_data($data);
            
            // Invalidate relevant caches
            $this->invalidate_cache($data);
            
            // Log webhook processing
            $this->log_webhook_event($payload, $result);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->log_error('Webhook processing failed', $e);
            return false;
        }
    }
    
    /**
     * Enhanced caching with invalidation
     * 
     * @param string $key Cache key
     * @param callable $callback Data fetching callback
     * @param int $expiration Cache expiration in seconds
     * @return mixed Cached or fresh data
     */
    protected function get_cached_data($key, $callback = null, $expiration = 3600) {
        $cache_key = $this->get_cache_key($key);
        $cached = $this->cache_manager->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        if ($callback && is_callable($callback)) {
            $data = $callback();
            $this->cache_manager->set($cache_key, $data, $expiration);
            return $data;
        }
        
        return false;
    }
    
    /**
     * Invalidate cache for specific data
     * 
     * @param array $data Data that was updated
     */
    protected function invalidate_cache($data) {
        $cache_keys = $this->get_cache_keys_for_data($data);
        
        foreach ($cache_keys as $key) {
            $this->cache_manager->delete($key);
        }
        
        // Trigger action for other systems to invalidate their caches
        do_action("hph_{$this->integration_type}_cache_invalidated", $data);
    }
    
    /**
     * Register webhook REST endpoint
     */
    public function register_webhook_endpoint() {
        $webhook_config = $this->get_webhook_config();
        
        if (!empty($webhook_config['endpoint'])) {
            register_rest_route('hph/v1', trim($webhook_config['endpoint'], '/'), [
                'methods' => 'POST',
                'callback' => [$this, 'handle_webhook_request'],
                'permission_callback' => [$this, 'verify_webhook_permission']
            ]);
        }
    }
    
    /**
     * Handle incoming webhook request
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle_webhook_request($request) {
        try {
            $payload = $request->get_json_params();
            $result = $this->handle_webhook($payload);
            
            return new \WP_REST_Response([
                'success' => $result,
                'message' => $result ? 'Webhook processed successfully' : 'Webhook processing failed'
            ], $result ? 200 : 400);
            
        } catch (\Exception $e) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verify webhook permission
     * 
     * @param \WP_REST_Request $request
     * @return bool
     */
    public function verify_webhook_permission($request) {
        // Basic implementation - should be overridden by specific integrations
        return true;
    }
    
    /**
     * Scheduled sync via cron
     */
    public function scheduled_sync() {
        try {
            $this->sync_data();
        } catch (\Exception $e) {
            $this->log_error('Scheduled sync failed', $e);
        }
    }
    
    /**
     * Display admin notices for integration errors
     */
    public function display_admin_notices() {
        if (current_user_can('manage_options') && !empty($this->errors)) {
            foreach ($this->errors as $error) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html($this->integration_type) . ' Integration Error:</strong> ';
                echo esc_html($error['message']) . '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Error handling and logging
     * 
     * @param string $message Error message
     * @param \Exception $exception Optional exception
     */
    protected function log_error($message, $exception = null) {
        $log_data = [
            'integration' => $this->integration_type,
            'message' => $message,
            'timestamp' => current_time('mysql'),
            'config' => $this->sanitize_config_for_logging()
        ];
        
        if ($exception) {
            $log_data['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        // Add to internal error log
        $this->errors[] = $log_data;
        
        // WordPress error log
        error_log('Happy Place Integration Error: ' . json_encode($log_data));
        
        // Store in database for admin review
        do_action('hph_integration_error', $log_data);
    }
    
    /**
     * Sanitize config for logging (remove sensitive data)
     * 
     * @return array Sanitized config
     */
    protected function sanitize_config_for_logging() {
        $config = $this->config;
        $sensitive_keys = ['api_key', 'secret', 'password', 'token'];
        
        foreach ($sensitive_keys as $key) {
            if (isset($config[$key])) {
                $config[$key] = '***REDACTED***';
            }
        }
        
        return $config;
    }
    
    /**
     * Generate cache key
     * 
     * @param string $key Base key
     * @return string Full cache key
     */
    protected function get_cache_key($key) {
        return "hph_{$this->integration_type}_{$key}";
    }
    
    /**
     * Get cache keys for specific data (to be overridden)
     * 
     * @param array $data
     * @return array Cache keys
     */
    protected function get_cache_keys_for_data($data) {
        return [];
    }
    
    /**
     * Validate webhook signature (to be overridden)
     * 
     * @param array $payload
     * @throws Integration_Exception
     */
    protected function validate_webhook_signature($payload) {
        // Default implementation - should be overridden
        return true;
    }
    
    /**
     * Process webhook data (to be overridden)
     * 
     * @param array $data
     * @return bool
     */
    protected function process_webhook_data($data) {
        return $this->upsert_wp_data($data);
    }
    
    /**
     * Fetch data from external source (to be overridden)
     * 
     * @return array External data
     */
    protected function fetch_external_data() {
        return [];
    }
    
    /**
     * Fetch WordPress data for sync (to be overridden)
     * 
     * @return array WordPress data
     */
    protected function fetch_wp_data() {
        return [];
    }
    
    /**
     * Upsert WordPress data (to be overridden)
     * 
     * @param array $data
     * @return array Result with 'created' boolean
     */
    protected function upsert_wp_data($data) {
        return ['created' => false];
    }
    
    /**
     * Send data to external service (to be overridden)
     * 
     * @param array $data
     * @return bool Success
     */
    protected function send_to_external($data) {
        return true;
    }
    
    /**
     * Log sync result
     * 
     * @param array $results
     */
    protected function log_sync_result($results) {
        do_action("hph_{$this->integration_type}_sync_completed", $results);
    }
    
    /**
     * Log webhook event
     * 
     * @param array $payload
     * @param bool $result
     */
    protected function log_webhook_event($payload, $result) {
        do_action("hph_{$this->integration_type}_webhook_processed", $payload, $result);
    }
    
    /**
     * Get integration status
     * 
     * @return array Status information
     */
    public function get_status() {
        return [
            'type' => $this->integration_type,
            'last_sync' => $this->last_sync,
            'last_sync_formatted' => $this->last_sync ? 
                wp_date(get_option('date_format') . ' ' . get_option('time_format'), $this->last_sync) : 
                'Never',
            'error_count' => count($this->errors),
            'config_valid' => $this->validate_config(),
            'api_reachable' => $this->test_api_connection()
        ];
    }
    
    /**
     * Validate configuration (to be overridden)
     * 
     * @return bool
     */
    protected function validate_config() {
        return !empty($this->config);
    }
    
    /**
     * Test API connection (to be overridden)
     * 
     * @return bool
     */
    protected function test_api_connection() {
        return true;
    }
}
