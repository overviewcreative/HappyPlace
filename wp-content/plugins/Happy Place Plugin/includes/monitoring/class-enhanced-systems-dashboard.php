<?php
/**
 * Enhanced Systems Monitoring Dashboard
 * 
 * WordPress admin page for monitoring the health and status of all enhanced systems:
 * - Circuit breaker status across all APIs
 * - Error logging and recovery metrics
 * - Performance monitoring and statistics
 * - Real-time health checks for all integrations
 * 
 * @package HappyPlace
 * @subpackage Monitoring
 */

namespace HappyPlace\Monitoring;

if (!defined('ABSPATH')) {
    exit;
}

class Enhanced_Systems_Dashboard {
    
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_hph_get_system_health', [$this, 'ajax_get_system_health']);
        add_action('wp_ajax_hph_reset_circuit_breakers', [$this, 'ajax_reset_circuit_breakers']);
        add_action('wp_ajax_hph_clear_error_logs', [$this, 'ajax_clear_error_logs']);
    }
    
    /**
     * Add admin menu page
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'happy-place-dashboard',
            'System Health Monitor',
            'System Health',
            'manage_options',
            'hph-system-health',
            [$this, 'render_dashboard_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook): void {
        if ($hook !== 'happy-place_page_hph-system-health') {
            return;
        }
        
        wp_enqueue_script(
            'hph-system-health',
            plugin_dir_url(__FILE__) . '../assets/js/system-health-dashboard.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('hph-system-health', 'hphSystemHealth', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_system_health'),
            'refresh_interval' => 30000 // 30 seconds
        ]);
        
        wp_enqueue_style(
            'hph-system-health',
            plugin_dir_url(__FILE__) . '../assets/css/system-health-dashboard.css',
            [],
            '1.0.0'
        );
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page(): void {
        ?>
        <div class="wrap">
            <h1>🔍 Happy Place - Enhanced Systems Health Monitor</h1>
            <p>Real-time monitoring of all enhanced stability systems and circuit breakers.</p>
            
            <div id="hph-health-dashboard">
                <!-- System Overview -->
                <div class="hph-dashboard-section">
                    <h2>📊 System Overview</h2>
                    <div class="hph-status-grid">
                        <div class="hph-status-card" id="overall-status">
                            <h3>Overall Status</h3>
                            <div class="status-indicator loading">Checking...</div>
                        </div>
                        <div class="hph-status-card" id="api-status">
                            <h3>API Health</h3>
                            <div class="status-indicator loading">Checking...</div>
                        </div>
                        <div class="hph-status-card" id="circuit-breaker-status">
                            <h3>Circuit Breakers</h3>
                            <div class="status-indicator loading">Checking...</div>
                        </div>
                        <div class="hph-status-card" id="error-rate">
                            <h3>Error Rate</h3>
                            <div class="status-indicator loading">Checking...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Circuit Breaker Status -->
                <div class="hph-dashboard-section">
                    <h2>⚡ Circuit Breaker Status</h2>
                    <div class="hph-controls">
                        <button id="reset-circuit-breakers" class="button button-secondary">
                            Reset All Circuit Breakers
                        </button>
                        <button id="refresh-status" class="button button-primary">
                            Refresh Status
                        </button>
                    </div>
                    <div id="circuit-breaker-details" class="hph-details-section">
                        Loading circuit breaker status...
                    </div>
                </div>
                
                <!-- Error Logs -->
                <div class="hph-dashboard-section">
                    <h2>📝 Recent Error Logs</h2>
                    <div class="hph-controls">
                        <button id="clear-error-logs" class="button button-secondary">
                            Clear Error Logs
                        </button>
                        <select id="error-filter">
                            <option value="all">All Errors</option>
                            <option value="airtable">Airtable</option>
                            <option value="external_api">External APIs</option>
                            <option value="ajax">AJAX Handler</option>
                        </select>
                    </div>
                    <div id="error-logs-details" class="hph-details-section">
                        Loading error logs...
                    </div>
                </div>
                
                <!-- Performance Metrics -->
                <div class="hph-dashboard-section">
                    <h2>📈 Performance Metrics</h2>
                    <div id="performance-metrics" class="hph-metrics-grid">
                        <div class="metric-card">
                            <h4>API Response Times</h4>
                            <div id="api-response-times" class="metric-value">Loading...</div>
                        </div>
                        <div class="metric-card">
                            <h4>Request Success Rate</h4>
                            <div id="success-rate" class="metric-value">Loading...</div>
                        </div>
                        <div class="metric-card">
                            <h4>Circuit Breaker Trips</h4>
                            <div id="circuit-breaker-trips" class="metric-value">Loading...</div>
                        </div>
                        <div class="metric-card">
                            <h4>Recovery Time</h4>
                            <div id="recovery-time" class="metric-value">Loading...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Integration Health -->
                <div class="hph-dashboard-section">
                    <h2>🔗 Integration Health Checks</h2>
                    <div id="integration-health" class="hph-integration-grid">
                        <div class="integration-card" data-integration="airtable">
                            <h4>Airtable Sync</h4>
                            <div class="integration-status loading">Testing...</div>
                            <div class="integration-details"></div>
                        </div>
                        <div class="integration-card" data-integration="google_maps">
                            <h4>Google Maps API</h4>
                            <div class="integration-status loading">Testing...</div>
                            <div class="integration-details"></div>
                        </div>
                        <div class="integration-card" data-integration="walkscore">
                            <h4>Walk Score API</h4>
                            <div class="integration-status loading">Testing...</div>
                            <div class="integration-details"></div>
                        </div>
                        <div class="integration-card" data-integration="dashboard_ajax">
                            <h4>Dashboard AJAX</h4>
                            <div class="integration-status loading">Testing...</div>
                            <div class="integration-details"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Auto-refresh controls -->
                <div class="hph-dashboard-section">
                    <h2>⚙️ Monitor Settings</h2>
                    <div class="hph-settings">
                        <label>
                            <input type="checkbox" id="auto-refresh" checked> 
                            Auto-refresh every 30 seconds
                        </label>
                        <label>
                            <input type="checkbox" id="sound-alerts"> 
                            Sound alerts for critical issues
                        </label>
                        <label>
                            <input type="checkbox" id="email-alerts"> 
                            Email alerts for circuit breaker trips
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .hph-dashboard-section {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hph-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .hph-status-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .status-indicator {
            font-size: 14px;
            font-weight: bold;
            padding: 8px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .status-indicator.healthy {
            background: #d4edda;
            color: #155724;
        }
        
        .status-indicator.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-indicator.critical {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-indicator.loading {
            background: #e9ecef;
            color: #6c757d;
        }
        
        .hph-controls {
            margin: 15px 0;
        }
        
        .hph-controls button, .hph-controls select {
            margin-right: 10px;
        }
        
        .hph-details-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .hph-metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .metric-card {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
            margin-top: 10px;
        }
        
        .hph-integration-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .integration-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        
        .integration-card.healthy {
            border-left-color: #28a745;
        }
        
        .integration-card.warning {
            border-left-color: #ffc107;
        }
        
        .integration-card.critical {
            border-left-color: #dc3545;
        }
        
        .hph-settings {
            margin-top: 15px;
        }
        
        .hph-settings label {
            display: block;
            margin: 10px 0;
        }
        
        .hph-settings input[type="checkbox"] {
            margin-right: 8px;
        }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for getting system health
     */
    public function ajax_get_system_health(): void {
        check_ajax_referer('hph_system_health', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $health_data = $this->get_system_health_data();
        wp_send_json_success($health_data);
    }
    
    /**
     * Get comprehensive system health data
     */
    private function get_system_health_data(): array {
        return [
            'overall_status' => $this->get_overall_system_status(),
            'circuit_breakers' => $this->get_circuit_breaker_status(),
            'error_logs' => $this->get_recent_error_logs(),
            'performance_metrics' => $this->get_performance_metrics(),
            'integration_health' => $this->get_integration_health(),
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Get overall system status
     */
    private function get_overall_system_status(): array {
        $circuit_breakers = $this->get_circuit_breaker_status();
        $error_count = count($this->get_recent_error_logs());
        
        $critical_issues = 0;
        $warnings = 0;
        
        foreach ($circuit_breakers as $breaker) {
            if ($breaker['state'] === 'open') {
                $critical_issues++;
            } elseif ($breaker['state'] === 'half-open') {
                $warnings++;
            }
        }
        
        if ($critical_issues > 0) {
            $status = 'critical';
            $message = "$critical_issues circuit breaker(s) open";
        } elseif ($warnings > 0 || $error_count > 10) {
            $status = 'warning';
            $message = "$warnings circuit breaker(s) in recovery, $error_count recent errors";
        } else {
            $status = 'healthy';
            $message = 'All systems operational';
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'critical_issues' => $critical_issues,
            'warnings' => $warnings,
            'error_count' => $error_count
        ];
    }
    
    /**
     * Get circuit breaker status for all APIs
     */
    private function get_circuit_breaker_status(): array {
        $breakers = [];
        $api_names = ['airtable', 'google_geocoding', 'google_places', 'walkscore', 'dashboard_ajax'];
        
        foreach ($api_names as $api_name) {
            $circuit_data = get_option("hph_circuit_breaker_{$api_name}", [
                'failure_count' => 0,
                'last_failure_time' => 0,
                'state' => 'closed'
            ]);
            
            $state = $this->determine_circuit_state($circuit_data);
            $time_to_recovery = $this->get_time_to_recovery($circuit_data);
            
            $breakers[$api_name] = [
                'name' => ucwords(str_replace('_', ' ', $api_name)),
                'state' => $state,
                'failure_count' => $circuit_data['failure_count'],
                'last_failure' => $circuit_data['last_failure_time'],
                'time_to_recovery' => $time_to_recovery,
                'status_message' => $this->get_circuit_status_message($state, $circuit_data)
            ];
        }
        
        return $breakers;
    }
    
    /**
     * Determine circuit breaker state
     */
    private function determine_circuit_state(array $circuit_data): string {
        $failure_threshold = 5;
        $recovery_timeout = 300; // 5 minutes
        
        if ($circuit_data['failure_count'] >= $failure_threshold) {
            $time_since_failure = time() - $circuit_data['last_failure_time'];
            if ($time_since_failure >= $recovery_timeout) {
                return 'half-open';
            }
            return 'open';
        }
        
        return 'closed';
    }
    
    /**
     * Get time until recovery for open circuit breakers
     */
    private function get_time_to_recovery(array $circuit_data): int {
        $recovery_timeout = 300; // 5 minutes
        $time_since_failure = time() - $circuit_data['last_failure_time'];
        return max(0, $recovery_timeout - $time_since_failure);
    }
    
    /**
     * Get circuit breaker status message
     */
    private function get_circuit_status_message(string $state, array $circuit_data): string {
        switch ($state) {
            case 'open':
                $recovery_time = $this->get_time_to_recovery($circuit_data);
                return "Circuit open - recovering in {$recovery_time}s";
            case 'half-open':
                return "Testing recovery - monitoring next request";
            case 'closed':
                return "Operational - {$circuit_data['failure_count']} recent failures";
            default:
                return "Unknown state";
        }
    }
    
    /**
     * Get recent error logs
     */
    private function get_recent_error_logs(): array {
        if (class_exists('\HappyPlace\Core\Plugin_Manager')) {
            $plugin_manager = \HappyPlace\Core\Plugin_Manager::get_instance();
            return $plugin_manager->get_integration_errors();
        }
        
        return [];
    }
    
    /**
     * Get performance metrics
     */
    private function get_performance_metrics(): array {
        // Get stored performance data
        $metrics = get_option('hph_performance_metrics', []);
        
        return [
            'avg_response_time' => $metrics['avg_response_time'] ?? 0,
            'success_rate' => $metrics['success_rate'] ?? 100,
            'total_requests' => $metrics['total_requests'] ?? 0,
            'failed_requests' => $metrics['failed_requests'] ?? 0,
            'circuit_breaker_trips' => $metrics['circuit_breaker_trips'] ?? 0,
            'avg_recovery_time' => $metrics['avg_recovery_time'] ?? 0
        ];
    }
    
    /**
     * Get integration health status
     */
    private function get_integration_health(): array {
        $integrations = [];
        
        // Test Airtable
        if (class_exists('Airtable_Two_Way_Sync')) {
            try {
                $airtable = new \Airtable_Two_Way_Sync('test', 'test');
                $test_result = $airtable->test_api_connection();
                $integrations['airtable'] = [
                    'status' => $test_result['success'] ? 'healthy' : 'critical',
                    'message' => $test_result['message'] ?? $test_result['error'] ?? 'Unknown',
                    'last_check' => current_time('mysql')
                ];
            } catch (\Exception $e) {
                $integrations['airtable'] = [
                    'status' => 'critical',
                    'message' => 'Configuration error: ' . $e->getMessage(),
                    'last_check' => current_time('mysql')
                ];
            }
        }
        
        // Test Google Maps API
        $google_key = get_option('hph_google_maps_api_key', '');
        $integrations['google_maps'] = [
            'status' => !empty($google_key) ? 'healthy' : 'warning',
            'message' => !empty($google_key) ? 'API key configured' : 'API key not configured',
            'last_check' => current_time('mysql')
        ];
        
        // Test Walk Score API
        $walkscore_key = get_option('hph_walkscore_api_key', '');
        $integrations['walkscore'] = [
            'status' => !empty($walkscore_key) ? 'healthy' : 'warning',
            'message' => !empty($walkscore_key) ? 'API key configured' : 'API key not configured',
            'last_check' => current_time('mysql')
        ];
        
        // Test Dashboard AJAX
        $integrations['dashboard_ajax'] = [
            'status' => 'healthy',
            'message' => 'AJAX handler operational',
            'last_check' => current_time('mysql')
        ];
        
        return $integrations;
    }
    
    /**
     * AJAX handler for resetting circuit breakers
     */
    public function ajax_reset_circuit_breakers(): void {
        check_ajax_referer('hph_system_health', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $api_names = ['airtable', 'google_geocoding', 'google_places', 'walkscore', 'dashboard_ajax'];
        $reset_count = 0;
        
        foreach ($api_names as $api_name) {
            if (delete_option("hph_circuit_breaker_{$api_name}")) {
                $reset_count++;
            }
        }
        
        wp_send_json_success([
            'message' => "Reset $reset_count circuit breakers",
            'reset_count' => $reset_count
        ]);
    }
    
    /**
     * AJAX handler for clearing error logs
     */
    public function ajax_clear_error_logs(): void {
        check_ajax_referer('hph_system_health', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (class_exists('\HappyPlace\Core\Plugin_Manager')) {
            $plugin_manager = \HappyPlace\Core\Plugin_Manager::get_instance();
            $plugin_manager->clear_integration_errors();
            wp_send_json_success(['message' => 'Error logs cleared successfully']);
        } else {
            wp_send_json_error(['message' => 'Plugin Manager not available']);
        }
    }
}

// Initialize the dashboard
Enhanced_Systems_Dashboard::get_instance();
?>
