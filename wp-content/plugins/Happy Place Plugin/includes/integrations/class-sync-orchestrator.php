<?php
/**
 * Enhanced Airtable Sync Orchestrator
 * 
 * Main controller for the enhanced Airtable sync system.
 * Provides unified interface, scheduling, monitoring, and administration.
 * 
 * @package HappyPlace
 * @since 5.0.0
 */

namespace HappyPlace\Integrations;

class Sync_Orchestrator {
    
    private Enhanced_Airtable_Sync $sync_engine;
    private Media_Sync_Manager $media_manager;
    
    // Configuration
    private array $settings;
    private bool $sync_enabled = false;
    
    // Monitoring
    private array $sync_history = [];
    private array $performance_metrics = [];
    
    public function __construct() {
        $this->load_settings();
        $this->initialize_components();
        $this->setup_hooks();
    }
    
    /**
     * Load sync settings
     */
    private function load_settings(): void {
        $this->settings = get_option('hph_airtable_sync_settings', [
            'base_id' => '',
            'table_name' => 'Listings',
            'api_key' => '',
            'sync_enabled' => false,
            'auto_sync_interval' => 'hourly',
            'sync_direction' => 'bidirectional',
            'conflict_resolution' => 'wp_wins',
            'media_sync_enabled' => true,
            'batch_size' => 50,
            'max_retries' => 3,
            'debug_mode' => false
        ]);
        
        $this->sync_enabled = $this->settings['sync_enabled'] && 
                              !empty($this->settings['base_id']) && 
                              !empty($this->settings['api_key']);
    }
    
    /**
     * Initialize sync components
     */
    private function initialize_components(): void {
        if ($this->sync_enabled) {
            $this->sync_engine = new Enhanced_Airtable_Sync(
                $this->settings['base_id'],
                $this->settings['table_name'],
                $this->settings['api_key']
            );
            
            $this->media_manager = new Media_Sync_Manager();
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void {
        // Admin interface
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_hph_manual_sync', [$this, 'handle_manual_sync']);
        add_action('wp_ajax_hph_test_connection', [$this, 'test_airtable_connection']);
        
        // Scheduled sync
        if ($this->sync_enabled) {
            $interval = $this->settings['auto_sync_interval'];
            add_action("hph_auto_sync_{$interval}", [$this, 'run_scheduled_sync']);
            
            if (!wp_next_scheduled("hph_auto_sync_{$interval}")) {
                wp_schedule_event(time(), $interval, "hph_auto_sync_{$interval}");
            }
        }
        
        // Listing save hooks for real-time sync
        add_action('acf/save_post', [$this, 'handle_listing_save'], 30);
        
        // Cleanup hooks
        add_action('hph_sync_cleanup', [$this, 'cleanup_sync_data']);
        if (!wp_next_scheduled('hph_sync_cleanup')) {
            wp_schedule_event(time(), 'daily', 'hph_sync_cleanup');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'edit.php?post_type=happy_place_listing',
            'Airtable Sync',
            'Airtable Sync',
            'manage_options',
            'hph-airtable-sync',
            [$this, 'admin_page']
        );
    }
    
    /**
     * Admin page content
     */
    public function admin_page(): void {
        $current_tab = $_GET['tab'] ?? 'settings';
        
        ?>
        <div class="wrap">
            <h1>Enhanced Airtable Sync</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?post_type=happy_place_listing&page=hph-airtable-sync&tab=settings" 
                   class="nav-tab <?= $current_tab === 'settings' ? 'nav-tab-active' : '' ?>">
                    Settings
                </a>
                <a href="?post_type=happy_place_listing&page=hph-airtable-sync&tab=sync" 
                   class="nav-tab <?= $current_tab === 'sync' ? 'nav-tab-active' : '' ?>">
                    Manual Sync
                </a>
                <a href="?post_type=happy_place_listing&page=hph-airtable-sync&tab=monitor" 
                   class="nav-tab <?= $current_tab === 'monitor' ? 'nav-tab-active' : '' ?>">
                    Monitoring
                </a>
                <a href="?post_type=happy_place_listing&page=hph-airtable-sync&tab=fields" 
                   class="nav-tab <?= $current_tab === 'fields' ? 'nav-tab-active' : '' ?>">
                    Field Mapping
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($current_tab) {
                    case 'settings':
                        $this->render_settings_tab();
                        break;
                    case 'sync':
                        $this->render_sync_tab();
                        break;
                    case 'monitor':
                        $this->render_monitor_tab();
                        break;
                    case 'fields':
                        $this->render_fields_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings tab
     */
    private function render_settings_tab(): void {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('hph_airtable_sync_settings');
            do_settings_sections('hph_airtable_sync_settings');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Airtable Base ID</th>
                    <td>
                        <input type="text" name="hph_airtable_sync_settings[base_id]" 
                               value="<?= esc_attr($this->settings['base_id']) ?>" 
                               class="regular-text" />
                        <p class="description">Your Airtable base ID (starts with "app")</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Table Name</th>
                    <td>
                        <input type="text" name="hph_airtable_sync_settings[table_name]" 
                               value="<?= esc_attr($this->settings['table_name']) ?>" 
                               class="regular-text" />
                        <p class="description">Name of the table in Airtable (default: Listings)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="password" name="hph_airtable_sync_settings[api_key]" 
                               value="<?= esc_attr($this->settings['api_key']) ?>" 
                               class="regular-text" />
                        <p class="description">Your Airtable API key or Personal Access Token</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Sync</th>
                    <td>
                        <label>
                            <input type="checkbox" name="hph_airtable_sync_settings[sync_enabled]" 
                                   value="1" <?= checked($this->settings['sync_enabled'], true, false) ?> />
                            Enable automatic synchronization
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sync Interval</th>
                    <td>
                        <select name="hph_airtable_sync_settings[auto_sync_interval]">
                            <option value="hourly" <?= selected($this->settings['auto_sync_interval'], 'hourly', false) ?>>Every Hour</option>
                            <option value="twicedaily" <?= selected($this->settings['auto_sync_interval'], 'twicedaily', false) ?>>Twice Daily</option>
                            <option value="daily" <?= selected($this->settings['auto_sync_interval'], 'daily', false) ?>>Daily</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sync Direction</th>
                    <td>
                        <select name="hph_airtable_sync_settings[sync_direction]">
                            <option value="bidirectional" <?= selected($this->settings['sync_direction'], 'bidirectional', false) ?>>Bidirectional</option>
                            <option value="airtable_to_wp" <?= selected($this->settings['sync_direction'], 'airtable_to_wp', false) ?>>Airtable to WordPress Only</option>
                            <option value="wp_to_airtable" <?= selected($this->settings['sync_direction'], 'wp_to_airtable', false) ?>>WordPress to Airtable Only</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Media Sync</th>
                    <td>
                        <label>
                            <input type="checkbox" name="hph_airtable_sync_settings[media_sync_enabled]" 
                                   value="1" <?= checked($this->settings['media_sync_enabled'], true, false) ?> />
                            Sync photos and attachments
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="hph-connection-test">
            <h3>Connection Test</h3>
            <button type="button" id="test-connection" class="button button-secondary">
                Test Airtable Connection
            </button>
            <div id="connection-result"></div>
        </div>
        
        <script>
        document.getElementById('test-connection').addEventListener('click', function() {
            const button = this;
            const result = document.getElementById('connection-result');
            
            button.disabled = true;
            button.textContent = 'Testing...';
            result.innerHTML = '';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=hph_test_connection&_wpnonce=<?= wp_create_nonce('hph_test_connection') ?>'
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.textContent = 'Test Airtable Connection';
                
                if (data.success) {
                    result.innerHTML = '<div class="notice notice-success"><p>✅ Connection successful!</p></div>';
                } else {
                    result.innerHTML = '<div class="notice notice-error"><p>❌ Connection failed: ' + data.data + '</p></div>';
                }
            })
            .catch(error => {
                button.disabled = false;
                button.textContent = 'Test Airtable Connection';
                result.innerHTML = '<div class="notice notice-error"><p>❌ Test failed: ' + error.message + '</p></div>';
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render sync tab
     */
    private function render_sync_tab(): void {
        if (!$this->sync_enabled) {
            echo '<div class="notice notice-warning"><p>Sync is not enabled. Please configure settings first.</p></div>';
            return;
        }
        
        ?>
        <div class="hph-manual-sync">
            <h3>Manual Synchronization</h3>
            <p>Run a manual sync between WordPress and Airtable. This will process all changes since the last sync.</p>
            
            <div class="sync-controls">
                <button type="button" id="sync-airtable-to-wp" class="button button-primary">
                    Sync Airtable → WordPress
                </button>
                <button type="button" id="sync-wp-to-airtable" class="button button-primary">
                    Sync WordPress → Airtable
                </button>
                <button type="button" id="sync-bidirectional" class="button button-primary">
                    Bidirectional Sync
                </button>
            </div>
            
            <div id="sync-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <div class="progress-text">Initializing sync...</div>
            </div>
            
            <div id="sync-results"></div>
        </div>
        
        <style>
        .sync-controls {
            margin: 20px 0;
        }
        .sync-controls .button {
            margin-right: 10px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background-color: #0073aa;
            width: 0%;
            transition: width 0.3s ease;
        }
        .progress-text {
            font-weight: bold;
            color: #666;
        }
        </style>
        
        <script>
        document.querySelectorAll('[id^="sync-"]').forEach(button => {
            button.addEventListener('click', function() {
                const direction = this.id.replace('sync-', '');
                runManualSync(direction);
            });
        });
        
        function runManualSync(direction) {
            const progressDiv = document.getElementById('sync-progress');
            const resultsDiv = document.getElementById('sync-results');
            const buttons = document.querySelectorAll('[id^="sync-"]');
            
            // Disable buttons and show progress
            buttons.forEach(btn => btn.disabled = true);
            progressDiv.style.display = 'block';
            resultsDiv.innerHTML = '';
            
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=hph_manual_sync&direction=' + direction + '&_wpnonce=<?= wp_create_nonce('hph_manual_sync') ?>'
            })
            .then(response => response.json())
            .then(data => {
                buttons.forEach(btn => btn.disabled = false);
                progressDiv.style.display = 'none';
                
                if (data.success) {
                    resultsDiv.innerHTML = '<div class="notice notice-success"><p>✅ Sync completed successfully!</p><pre>' + JSON.stringify(data.data, null, 2) + '</pre></div>';
                } else {
                    resultsDiv.innerHTML = '<div class="notice notice-error"><p>❌ Sync failed: ' + data.data + '</p></div>';
                }
            })
            .catch(error => {
                buttons.forEach(btn => btn.disabled = false);
                progressDiv.style.display = 'none';
                resultsDiv.innerHTML = '<div class="notice notice-error"><p>❌ Sync failed: ' + error.message + '</p></div>';
            });
        }
        </script>
        <?php
    }
    
    /**
     * Render monitoring tab
     */
    private function render_monitor_tab(): void {
        $sync_history = $this->get_sync_history();
        $media_stats = $this->media_manager ? $this->media_manager->get_sync_statistics() : [];
        
        ?>
        <div class="hph-monitoring">
            <h3>Sync Status</h3>
            
            <div class="sync-status-cards">
                <div class="status-card">
                    <h4>Last Sync</h4>
                    <p><?= esc_html($this->get_last_sync_time()) ?></p>
                </div>
                <div class="status-card">
                    <h4>Sync Status</h4>
                    <p class="status-<?= $this->sync_enabled ? 'enabled' : 'disabled' ?>">
                        <?= $this->sync_enabled ? 'Enabled' : 'Disabled' ?>
                    </p>
                </div>
                <div class="status-card">
                    <h4>Media Files Synced</h4>
                    <p><?= $media_stats['synced_files'] ?? 0 ?></p>
                </div>
                <div class="status-card">
                    <h4>Total Media Size</h4>
                    <p><?= ($media_stats['total_size_mb'] ?? 0) . ' MB' ?></p>
                </div>
            </div>
            
            <h3>Recent Sync History</h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Direction</th>
                        <th>Status</th>
                        <th>Records</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sync_history)): ?>
                        <tr>
                            <td colspan="5">No sync history available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($sync_history, 0, 10) as $entry): ?>
                            <tr>
                                <td><?= esc_html($entry['date']) ?></td>
                                <td><?= esc_html($entry['direction']) ?></td>
                                <td>
                                    <span class="status-<?= $entry['status'] ?>">
                                        <?= esc_html(ucfirst($entry['status'])) ?>
                                    </span>
                                </td>
                                <td><?= esc_html($entry['records_processed'] ?? 0) ?></td>
                                <td><?= esc_html($entry['duration'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .sync-status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .status-card {
            background: #fff;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .status-card h4 {
            margin: 0 0 10px 0;
            color: #666;
        }
        .status-card p {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        .status-enabled {
            color: #46b450;
        }
        .status-disabled {
            color: #dc3232;
        }
        .status-success {
            color: #46b450;
        }
        .status-error {
            color: #dc3232;
        }
        </style>
        <?php
    }
    
    /**
     * Render field mapping tab
     */
    private function render_fields_tab(): void {
        if (!$this->sync_enabled) {
            echo '<div class="notice notice-warning"><p>Sync is not enabled. Please configure settings first.</p></div>';
            return;
        }
        
        $field_mapping = $this->sync_engine->enhanced_field_mapping ?? [];
        
        ?>
        <div class="hph-field-mapping">
            <h3>Enhanced Field Mapping</h3>
            <p>This shows the complete field mapping between WordPress and Airtable with smart sync behavior.</p>
            
            <div class="field-mapping-filters">
                <label>
                    Filter by type:
                    <select id="field-type-filter">
                        <option value="">All Fields</option>
                        <option value="manual">Manual Sync</option>
                        <option value="calculated_wp">Calculated (WP)</option>
                        <option value="calculated_airtable">Calculated (Airtable)</option>
                        <option value="media">Media</option>
                        <option value="readonly">Read Only</option>
                    </select>
                </label>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>WordPress Field</th>
                        <th>Airtable Field</th>
                        <th>Type</th>
                        <th>Sync Direction</th>
                        <th>Data Type</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($field_mapping as $wp_field => $config): ?>
                        <tr class="field-row" data-type="<?= esc_attr($config['type'] ?? 'manual') ?>">
                            <td><code><?= esc_html($wp_field) ?></code></td>
                            <td><?= esc_html($config['airtable_field'] ?? '') ?></td>
                            <td>
                                <span class="field-type field-type-<?= esc_attr($config['type'] ?? 'manual') ?>">
                                    <?= esc_html(ucfirst(str_replace('_', ' ', $config['type'] ?? 'manual'))) ?>
                                </span>
                            </td>
                            <td><?= esc_html($config['sync_direction'] ?? 'bidirectional') ?></td>
                            <td><?= esc_html($config['data_type'] ?? 'string') ?></td>
                            <td>
                                <?php
                                $notes = [];
                                if (!empty($config['calculation'])) {
                                    $notes[] = 'Calc: ' . $config['calculation'];
                                }
                                if (!empty($config['triggers_calculation'])) {
                                    $notes[] = 'Triggers: ' . implode(', ', $config['triggers_calculation']);
                                }
                                if (!empty($config['auto_populate'])) {
                                    $notes[] = 'Auto: ' . $config['auto_populate'];
                                }
                                if (!empty($config['max_files'])) {
                                    $notes[] = 'Max files: ' . $config['max_files'];
                                }
                                echo esc_html(implode(' | ', $notes));
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
        .field-mapping-filters {
            margin: 20px 0;
        }
        .field-type {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .field-type-manual {
            background: #e1f5fe;
            color: #0277bd;
        }
        .field-type-calculated_wp {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .field-type-calculated_airtable {
            background: #fff3e0;
            color: #f57c00;
        }
        .field-type-media {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .field-type-readonly {
            background: #fafafa;
            color: #757575;
        }
        </style>
        
        <script>
        document.getElementById('field-type-filter').addEventListener('change', function() {
            const filterValue = this.value;
            const rows = document.querySelectorAll('.field-row');
            
            rows.forEach(row => {
                if (!filterValue || row.dataset.type === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('hph_airtable_sync_settings', 'hph_airtable_sync_settings');
    }
    
    /**
     * Handle manual sync AJAX request
     */
    public function handle_manual_sync(): void {
        check_ajax_referer('hph_manual_sync');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $direction = sanitize_text_field($_POST['direction'] ?? '');
        
        if (!$this->sync_enabled) {
            wp_send_json_error('Sync is not enabled');
        }
        
        $start_time = microtime(true);
        
        try {
            switch ($direction) {
                case 'airtable-to-wp':
                    $result = $this->sync_engine->sync_airtable_to_wordpress();
                    break;
                case 'wp-to-airtable':
                    $result = $this->sync_engine->sync_wordpress_to_airtable();
                    break;
                case 'bidirectional':
                    $result1 = $this->sync_engine->sync_airtable_to_wordpress();
                    $result2 = $this->sync_engine->sync_wordpress_to_airtable();
                    $result = [
                        'airtable_to_wp' => $result1,
                        'wp_to_airtable' => $result2,
                        'success' => $result1['success'] && $result2['success']
                    ];
                    break;
                default:
                    wp_send_json_error('Invalid sync direction');
            }
            
            $duration = round(microtime(true) - $start_time, 2);
            
            // Log sync history
            $this->log_sync_event($direction, $result['success'] ? 'success' : 'error', $result, $duration);
            
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $start_time, 2);
            $this->log_sync_event($direction, 'error', ['error' => $e->getMessage()], $duration);
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Test Airtable connection
     */
    public function test_airtable_connection(): void {
        check_ajax_referer('hph_test_connection');
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $base_id = $this->settings['base_id'];
        $api_key = $this->settings['api_key'];
        $table_name = $this->settings['table_name'];
        
        if (empty($base_id) || empty($api_key)) {
            wp_send_json_error('Base ID and API key are required');
        }
        
        $url = "https://api.airtable.com/v0/{$base_id}/{$table_name}?maxRecords=1";
        
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error('Connection failed: ' . $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            wp_send_json_success('Connection successful');
        } else {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            $error = $data['error']['message'] ?? "HTTP {$code}";
            wp_send_json_error($error);
        }
    }
    
    /**
     * Handle listing save for real-time sync
     */
    public function handle_listing_save($post_id): void {
        if (get_post_type($post_id) !== 'happy_place_listing' || !$this->sync_enabled) {
            return;
        }
        
        // Check if sync is enabled for this listing
        $sync_enabled = get_post_meta($post_id, '_airtable_sync_enabled', true);
        if (!$sync_enabled) {
            return;
        }
        
        // Schedule async sync to avoid blocking the save
        wp_schedule_single_event(time() + 60, 'hph_sync_single_listing', [$post_id]);
    }
    
    /**
     * Run scheduled sync
     */
    public function run_scheduled_sync(): void {
        if (!$this->sync_enabled) {
            return;
        }
        
        try {
            $start_time = microtime(true);
            
            // Run bidirectional sync
            $result1 = $this->sync_engine->sync_airtable_to_wordpress();
            $result2 = $this->sync_engine->sync_wordpress_to_airtable();
            
            $duration = round(microtime(true) - $start_time, 2);
            $success = $result1['success'] && $result2['success'];
            
            $this->log_sync_event('scheduled_bidirectional', $success ? 'success' : 'error', [
                'airtable_to_wp' => $result1,
                'wp_to_airtable' => $result2
            ], $duration);
            
        } catch (\Exception $e) {
            error_log('HPH Scheduled Sync Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Log sync event
     */
    private function log_sync_event(string $direction, string $status, array $data, float $duration): void {
        $history = get_option('hph_sync_history', []);
        
        $event = [
            'date' => current_time('Y-m-d H:i:s'),
            'direction' => $direction,
            'status' => $status,
            'duration' => $duration . 's',
            'records_processed' => $data['stats']['total_processed'] ?? 0,
            'data' => $data
        ];
        
        array_unshift($history, $event);
        
        // Keep only last 50 entries
        $history = array_slice($history, 0, 50);
        
        update_option('hph_sync_history', $history);
    }
    
    /**
     * Get sync history
     */
    private function get_sync_history(): array {
        return get_option('hph_sync_history', []);
    }
    
    /**
     * Get last sync time
     */
    private function get_last_sync_time(): string {
        $history = $this->get_sync_history();
        
        if (empty($history)) {
            return 'Never';
        }
        
        return $history[0]['date'] ?? 'Unknown';
    }
    
    /**
     * Cleanup sync data
     */
    public function cleanup_sync_data(): void {
        if ($this->media_manager) {
            $this->media_manager->cleanup_orphaned_media();
        }
        
        // Clean old sync history (keep only 50 most recent)
        $history = get_option('hph_sync_history', []);
        if (count($history) > 50) {
            update_option('hph_sync_history', array_slice($history, 0, 50));
        }
    }
}
