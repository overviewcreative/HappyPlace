<?php

namespace HappyPlace\Integration;

/**
 * Integration Manager
 *
 * Central manager for all external integrations
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Integration_Manager {
    
    /**
     * Singleton instance
     * @var Integration_Manager
     */
    private static $instance = null;
    
    /**
     * Registered integrations
     * @var array
     */
    protected $integrations = [];
    
    /**
     * Active integrations
     * @var array
     */
    protected $active_integrations = [];
    
    /**
     * Integration status cache
     * @var array
     */
    protected $status_cache = [];
    
    /**
     * Get singleton instance
     * 
     * @return Integration_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->register_hooks();
        $this->load_active_integrations();
    }
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // AJAX hooks
        add_action('wp_ajax_hph_test_integration', [$this, 'ajax_test_integration']);
        add_action('wp_ajax_hph_sync_integration', [$this, 'ajax_sync_integration']);
        add_action('wp_ajax_hph_toggle_integration', [$this, 'ajax_toggle_integration']);
        
        // Cron hooks
        add_action('hph_sync_all_integrations', [$this, 'sync_all_integrations']);
        
        // Schedule sync if not already scheduled
        if (!wp_next_scheduled('hph_sync_all_integrations')) {
            wp_schedule_event(time(), 'hourly', 'hph_sync_all_integrations');
        }
    }
    
    /**
     * Register an integration
     * 
     * @param string $key Integration key
     * @param string $class_name Integration class name
     * @param array $config Default configuration
     */
    public function register_integration($key, $class_name, $config = []) {
        $this->integrations[$key] = [
            'class' => $class_name,
            'config' => $config,
            'enabled' => get_option("hph_integration_{$key}_enabled", false)
        ];
    }
    
    /**
     * Load active integrations
     */
    protected function load_active_integrations() {
        // Register built-in integrations
        $this->register_integration('airtable', Airtable_Integration::class, [
            'name' => 'Airtable',
            'description' => 'Real-time synchronization with Airtable databases'
        ]);
        
        $this->register_integration('crm', CRM_Integration::class, [
            'name' => 'CRM',
            'description' => 'Customer Relationship Management integration'
        ]);
        
        // Allow plugins to register additional integrations
        do_action('hph_register_integrations', $this);
        
        // Initialize enabled integrations
        foreach ($this->integrations as $key => $integration) {
            if ($integration['enabled']) {
                $this->initialize_integration($key);
            }
        }
    }
    
    /**
     * Initialize an integration
     * 
     * @param string $key Integration key
     * @return bool Success status
     */
    protected function initialize_integration($key) {
        if (!isset($this->integrations[$key])) {
            return false;
        }
        
        $integration_config = $this->integrations[$key];
        $class_name = $integration_config['class'];
        
        if (!class_exists($class_name)) {
            error_log("Integration class not found: {$class_name}");
            return false;
        }
        
        try {
            $config = get_option("hph_integration_{$key}_config", $integration_config['config']);
            $this->active_integrations[$key] = new $class_name($config);
            return true;
        } catch (\Exception $e) {
            error_log("Failed to initialize integration {$key}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get integration instance
     * 
     * @param string $key Integration key
     * @return Base_Integration|null Integration instance
     */
    public function get_integration($key) {
        return $this->active_integrations[$key] ?? null;
    }
    
    /**
     * Get all active integrations
     * 
     * @return array Active integrations
     */
    public function get_active_integrations() {
        return $this->active_integrations;
    }
    
    /**
     * Enable an integration
     * 
     * @param string $key Integration key
     * @return bool Success status
     */
    public function enable_integration($key) {
        if (!isset($this->integrations[$key])) {
            return false;
        }
        
        update_option("hph_integration_{$key}_enabled", true);
        $this->integrations[$key]['enabled'] = true;
        
        return $this->initialize_integration($key);
    }
    
    /**
     * Disable an integration
     * 
     * @param string $key Integration key
     * @return bool Success status
     */
    public function disable_integration($key) {
        update_option("hph_integration_{$key}_enabled", false);
        $this->integrations[$key]['enabled'] = false;
        
        unset($this->active_integrations[$key]);
        
        return true;
    }
    
    /**
     * Sync all active integrations
     */
    public function sync_all_integrations() {
        $results = [];
        
        foreach ($this->active_integrations as $key => $integration) {
            try {
                $results[$key] = $integration->sync_data();
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Store sync results
        update_option('hph_last_sync_results', $results);
        update_option('hph_last_sync_time', current_time('mysql'));
        
        return $results;
    }
    
    /**
     * Get integration status dashboard
     * 
     * @return array Status information
     */
    public function get_status_dashboard() {
        $dashboard = [
            'total_integrations' => count($this->integrations),
            'active_integrations' => count($this->active_integrations),
            'last_sync' => get_option('hph_last_sync_time', 'Never'),
            'integrations' => []
        ];
        
        foreach ($this->integrations as $key => $integration) {
            $status = [
                'key' => $key,
                'name' => $integration['config']['name'] ?? ucfirst($key),
                'enabled' => $integration['enabled'],
                'status' => 'Unknown'
            ];
            
            if ($integration['enabled'] && isset($this->active_integrations[$key])) {
                $instance = $this->active_integrations[$key];
                $instance_status = $instance->get_status();
                
                $status = array_merge($status, [
                    'status' => $instance_status['config_valid'] && $instance_status['api_reachable'] ? 'Active' : 'Error',
                    'last_sync' => $instance_status['last_sync_formatted'],
                    'error_count' => $instance_status['error_count']
                ]);
            } else {
                $status['status'] = 'Disabled';
            }
            
            $dashboard['integrations'][$key] = $status;
        }
        
        return $dashboard;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Integrations',
            'Integrations',
            'manage_options',
            'hph-integrations',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        foreach ($this->integrations as $key => $integration) {
            register_setting('hph_integrations', "hph_integration_{$key}_enabled");
            register_setting('hph_integrations', "hph_integration_{$key}_config");
        }
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        $dashboard = $this->get_status_dashboard();
        ?>
        <div class="wrap">
            <h1>Happy Place Integrations</h1>
            
            <div class="card">
                <h2>Integration Status</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Integration</th>
                            <th>Status</th>
                            <th>Last Sync</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dashboard['integrations'] as $key => $integration): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($integration['name']); ?></strong>
                                <div class="row-actions">
                                    <span>Key: <?php echo esc_html($key); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="integration-status integration-status--<?php echo strtolower($integration['status']); ?>">
                                    <?php echo esc_html($integration['status']); ?>
                                </span>
                                <?php if (isset($integration['error_count']) && $integration['error_count'] > 0): ?>
                                    <br><small><?php echo esc_html($integration['error_count']); ?> errors</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html($integration['last_sync'] ?? 'Never'); ?>
                            </td>
                            <td>
                                <button type="button" class="button" onclick="toggleIntegration('<?php echo esc_js($key); ?>')">
                                    <?php echo $integration['enabled'] ? 'Disable' : 'Enable'; ?>
                                </button>
                                <?php if ($integration['enabled']): ?>
                                <button type="button" class="button" onclick="testIntegration('<?php echo esc_js($key); ?>')">
                                    Test
                                </button>
                                <button type="button" class="button" onclick="syncIntegration('<?php echo esc_js($key); ?>')">
                                    Sync Now
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <p>
                    <button type="button" class="button button-primary" onclick="syncAllIntegrations()">
                        Sync All Integrations
                    </button>
                    <button type="button" class="button" onclick="refreshStatus()">
                        Refresh Status
                    </button>
                </p>
            </div>
        </div>
        
        <style>
            .integration-status {
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .integration-status--active { background: #46b450; color: white; }
            .integration-status--error { background: #dc3232; color: white; }
            .integration-status--disabled { background: #666; color: white; }
        </style>
        
        <script>
            function toggleIntegration(key) {
                // Implementation for toggling integration
                console.log('Toggle integration:', key);
            }
            
            function testIntegration(key) {
                // Implementation for testing integration
                console.log('Test integration:', key);
            }
            
            function syncIntegration(key) {
                // Implementation for syncing integration
                console.log('Sync integration:', key);
            }
            
            function syncAllIntegrations() {
                // Implementation for syncing all integrations
                console.log('Sync all integrations');
            }
            
            function refreshStatus() {
                location.reload();
            }
        </script>
        <?php
    }
    
    /**
     * AJAX: Test integration
     */
    public function ajax_test_integration() {
        check_ajax_referer('hph_integration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $key = sanitize_text_field($_POST['integration']);
        $integration = $this->get_integration($key);
        
        if (!$integration) {
            wp_send_json_error('Integration not found or not active');
        }
        
        try {
            $status = $integration->get_status();
            wp_send_json_success($status);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Sync integration
     */
    public function ajax_sync_integration() {
        check_ajax_referer('hph_integration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $key = sanitize_text_field($_POST['integration']);
        $integration = $this->get_integration($key);
        
        if (!$integration) {
            wp_send_json_error('Integration not found or not active');
        }
        
        try {
            $result = $integration->sync_data();
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Toggle integration
     */
    public function ajax_toggle_integration() {
        check_ajax_referer('hph_integration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        $key = sanitize_text_field($_POST['integration']);
        $enable = filter_var($_POST['enable'], FILTER_VALIDATE_BOOLEAN);
        
        if ($enable) {
            $result = $this->enable_integration($key);
        } else {
            $result = $this->disable_integration($key);
        }
        
        if ($result) {
            wp_send_json_success('Integration ' . ($enable ? 'enabled' : 'disabled'));
        } else {
            wp_send_json_error('Failed to ' . ($enable ? 'enable' : 'disable') . ' integration');
        }
    }
}
