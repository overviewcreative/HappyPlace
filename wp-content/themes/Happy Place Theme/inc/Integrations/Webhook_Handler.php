<?php

namespace HappyPlace\Integration;

/**
 * Webhook Handler
 *
 * Handles incoming webhooks with validation and security
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Webhook_Handler {
    
    /**
     * Webhook configuration
     * @var array
     */
    protected $config;
    
    /**
     * Supported webhook events
     * @var array
     */
    protected $supported_events = [];
    
    /**
     * Event handlers
     * @var array
     */
    protected $handlers = [];
    
    /**
     * Constructor
     * 
     * @param array $config Webhook configuration
     */
    public function __construct($config = []) {
        $this->config = wp_parse_args($config, [
            'endpoint' => '/wp-json/hph/v1/webhook',
            'secret' => '',
            'events' => [],
            'signature_header' => 'X-Signature',
            'event_header' => 'X-Event-Type',
            'timestamp_header' => 'X-Timestamp',
            'signature_algorithm' => 'sha256',
            'timestamp_tolerance' => 300 // 5 minutes
        ]);
        
        $this->supported_events = $this->config['events'];
    }
    
    /**
     * Process incoming webhook
     * 
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @return array Processing result
     */
    public function process_webhook($payload, $headers = []) {
        try {
            // Validate the webhook
            $this->validate_webhook($payload, $headers);
            
            // Extract event type
            $event_type = $this->extract_event_type($payload, $headers);
            
            // Check if event is supported
            if (!$this->is_event_supported($event_type)) {
                throw new Integration_Exception("Unsupported event type: {$event_type}");
            }
            
            // Process the event
            $result = $this->process_event($event_type, $payload);
            
            // Log successful processing
            $this->log_webhook_event($event_type, $payload, $result, true);
            
            return [
                'success' => true,
                'event_type' => $event_type,
                'result' => $result,
                'timestamp' => current_time('mysql')
            ];
            
        } catch (Integration_Exception $e) {
            // Log failed processing
            $this->log_webhook_event(
                $event_type ?? 'unknown', 
                $payload, 
                $e->getMessage(), 
                false
            );
            
            throw $e;
        }
    }
    
    /**
     * Validate webhook signature and timestamp
     * 
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @throws Integration_Exception If validation fails
     */
    protected function validate_webhook($payload, $headers) {
        // Check if secret is configured
        if (empty($this->config['secret'])) {
            // Skip signature validation if no secret configured
            return;
        }
        
        // Validate signature
        $this->validate_signature($payload, $headers);
        
        // Validate timestamp if provided
        $this->validate_timestamp($headers);
    }
    
    /**
     * Validate webhook signature
     * 
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @throws Integration_Exception If signature invalid
     */
    protected function validate_signature($payload, $headers) {
        $signature_header = $this->config['signature_header'];
        
        if (!isset($headers[$signature_header])) {
            throw new Integration_Exception("Missing signature header: {$signature_header}");
        }
        
        $received_signature = $headers[$signature_header];
        $expected_signature = $this->generate_signature($payload);
        
        if (!hash_equals($expected_signature, $received_signature)) {
            throw new Integration_Exception("Invalid webhook signature");
        }
    }
    
    /**
     * Validate webhook timestamp
     * 
     * @param array $headers Request headers
     * @throws Integration_Exception If timestamp invalid
     */
    protected function validate_timestamp($headers) {
        $timestamp_header = $this->config['timestamp_header'];
        
        if (!isset($headers[$timestamp_header])) {
            return; // Timestamp validation is optional
        }
        
        $webhook_timestamp = intval($headers[$timestamp_header]);
        $current_timestamp = time();
        $tolerance = $this->config['timestamp_tolerance'];
        
        if (abs($current_timestamp - $webhook_timestamp) > $tolerance) {
            throw new Integration_Exception("Webhook timestamp outside tolerance window");
        }
    }
    
    /**
     * Generate expected signature for payload
     * 
     * @param array $payload Webhook payload
     * @return string Expected signature
     */
    protected function generate_signature($payload) {
        $payload_string = is_string($payload) ? $payload : json_encode($payload);
        $algorithm = $this->config['signature_algorithm'];
        $secret = $this->config['secret'];
        
        return hash_hmac($algorithm, $payload_string, $secret);
    }
    
    /**
     * Extract event type from payload or headers
     * 
     * @param array $payload Webhook payload
     * @param array $headers Request headers
     * @return string Event type
     */
    protected function extract_event_type($payload, $headers) {
        $event_header = $this->config['event_header'];
        
        // Try to get event from header first
        if (isset($headers[$event_header])) {
            return $headers[$event_header];
        }
        
        // Try to get event from payload
        if (isset($payload['event'])) {
            return $payload['event'];
        }
        
        if (isset($payload['type'])) {
            return $payload['type'];
        }
        
        if (isset($payload['event_type'])) {
            return $payload['event_type'];
        }
        
        throw new Integration_Exception("Could not determine event type from webhook");
    }
    
    /**
     * Check if event type is supported
     * 
     * @param string $event_type Event type
     * @return bool True if supported
     */
    protected function is_event_supported($event_type) {
        return empty($this->supported_events) || in_array($event_type, $this->supported_events);
    }
    
    /**
     * Process webhook event
     * 
     * @param string $event_type Event type
     * @param array $payload Event payload
     * @return mixed Processing result
     */
    protected function process_event($event_type, $payload) {
        // Check for registered handler
        if (isset($this->handlers[$event_type])) {
            return call_user_func($this->handlers[$event_type], $payload);
        }
        
        // Use default processing
        return $this->default_event_processing($event_type, $payload);
    }
    
    /**
     * Default event processing
     * 
     * @param string $event_type Event type
     * @param array $payload Event payload
     * @return array Processing result
     */
    protected function default_event_processing($event_type, $payload) {
        // Trigger WordPress action for other plugins to handle
        do_action('hph_webhook_event', $event_type, $payload);
        do_action("hph_webhook_event_{$event_type}", $payload);
        
        return [
            'processed' => true,
            'event_type' => $event_type,
            'action_triggered' => true
        ];
    }
    
    /**
     * Register event handler
     * 
     * @param string $event_type Event type
     * @param callable $handler Event handler function
     */
    public function register_event_handler($event_type, $handler) {
        if (!is_callable($handler)) {
            throw new \InvalidArgumentException("Event handler must be callable");
        }
        
        $this->handlers[$event_type] = $handler;
    }
    
    /**
     * Unregister event handler
     * 
     * @param string $event_type Event type
     */
    public function unregister_event_handler($event_type) {
        unset($this->handlers[$event_type]);
    }
    
    /**
     * Get registered event handlers
     * 
     * @return array Event handlers
     */
    public function get_event_handlers() {
        return $this->handlers;
    }
    
    /**
     * Log webhook event
     * 
     * @param string $event_type Event type
     * @param array $payload Event payload
     * @param mixed $result Processing result
     * @param bool $success Success status
     */
    protected function log_webhook_event($event_type, $payload, $result, $success) {
        $log_data = [
            'event_type' => $event_type,
            'success' => $success,
            'timestamp' => current_time('mysql'),
            'payload_size' => strlen(json_encode($payload)),
            'result' => $success ? 'Success' : $result // Don't log full result on success for brevity
        ];
        
        // Store webhook log
        $this->store_webhook_log($log_data);
        
        // Trigger action for external logging
        do_action('hph_webhook_logged', $log_data, $payload, $result);
    }
    
    /**
     * Store webhook log in database
     * 
     * @param array $log_data Log data
     */
    protected function store_webhook_log($log_data) {
        // Get existing logs
        $logs = get_option('hph_webhook_logs', []);
        
        // Add new log
        $logs[] = $log_data;
        
        // Keep only last 100 logs to prevent option bloat
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        // Update option
        update_option('hph_webhook_logs', $logs);
    }
    
    /**
     * Get webhook logs
     * 
     * @param int $limit Number of logs to retrieve
     * @return array Webhook logs
     */
    public function get_webhook_logs($limit = 50) {
        $logs = get_option('hph_webhook_logs', []);
        
        // Return most recent logs first
        $logs = array_reverse($logs);
        
        if ($limit > 0) {
            $logs = array_slice($logs, 0, $limit);
        }
        
        return $logs;
    }
    
    /**
     * Clear webhook logs
     * 
     * @return bool Success status
     */
    public function clear_logs() {
        return delete_option('hph_webhook_logs');
    }
    
    /**
     * Get webhook statistics
     * 
     * @return array Statistics
     */
    public function get_statistics() {
        $logs = get_option('hph_webhook_logs', []);
        
        $stats = [
            'total_webhooks' => count($logs),
            'successful_webhooks' => 0,
            'failed_webhooks' => 0,
            'event_types' => [],
            'recent_activity' => []
        ];
        
        foreach ($logs as $log) {
            if ($log['success']) {
                $stats['successful_webhooks']++;
            } else {
                $stats['failed_webhooks']++;
            }
            
            $event_type = $log['event_type'];
            if (!isset($stats['event_types'][$event_type])) {
                $stats['event_types'][$event_type] = 0;
            }
            $stats['event_types'][$event_type]++;
        }
        
        // Get recent activity (last 10)
        $stats['recent_activity'] = array_slice(array_reverse($logs), 0, 10);
        
        return $stats;
    }
    
    /**
     * Test webhook processing with sample data
     * 
     * @param string $event_type Event type to test
     * @param array $test_payload Test payload
     * @return array Test result
     */
    public function test_webhook($event_type, $test_payload = []) {
        try {
            // Create test headers
            $test_headers = [];
            
            if (!empty($this->config['secret'])) {
                $signature = $this->generate_signature($test_payload);
                $test_headers[$this->config['signature_header']] = $signature;
            }
            
            $test_headers[$this->config['event_header']] = $event_type;
            $test_headers[$this->config['timestamp_header']] = time();
            
            // Process test webhook
            $result = $this->process_webhook($test_payload, $test_headers);
            
            return [
                'success' => true,
                'result' => $result,
                'message' => 'Test webhook processed successfully'
            ];
            
        } catch (Integration_Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Test webhook failed'
            ];
        }
    }
}
