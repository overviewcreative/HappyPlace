<?php
/**
 * Airtable Integration Admin Page
 * 
 * Unified admin interface for Airtable sync configuration and management.
 * 
 * @package HappyPlace
 * @since 6.0.0
 */

namespace HappyPlace\Admin;

use HappyPlace\Integrations\Enhanced_Airtable_Sync;
use HappyPlace\Integrations\Media_Sync_Manager;

if (!defined('ABSPATH')) {
    exit;
}

class Airtable_Admin {
    
    private static ?self $instance = null;
    
    public static function get_instance(): self {
        return self::$instance ??= new self();
    }
    
    private function __construct() {
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Handle AJAX requests
        add_action('wp_ajax_hph_test_airtable_connection', [$this, 'test_connection']);
        add_action('wp_ajax_hph_sync_airtable_manual', [$this, 'manual_sync']);
        add_action('wp_ajax_hph_get_sync_status', [$this, 'get_sync_status']);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting('hph_airtable_settings', 'hph_airtable_sync_settings', [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize_settings'],
            'default' => $this->get_default_settings()
        ]);
        
        // Settings sections
        add_settings_section(
            'hph_airtable_connection',
            __('Connection Settings', 'happy-place'),
            [$this, 'connection_section_callback'],
            'hph_airtable_settings'
        );
        
        add_settings_section(
            'hph_airtable_sync_options',
            __('Sync Options', 'happy-place'),
            [$this, 'sync_options_section_callback'],
            'hph_airtable_settings'
        );
        
        add_settings_section(
            'hph_airtable_field_mapping',
            __('Field Mapping', 'happy-place'),
            [$this, 'field_mapping_section_callback'],
            'hph_airtable_settings'
        );
        
        // Connection fields
        add_settings_field(
            'api_key',
            __('API Key', 'happy-place'),
            [$this, 'api_key_field'],
            'hph_airtable_settings',
            'hph_airtable_connection'
        );
        
        add_settings_field(
            'base_id',
            __('Base ID', 'happy-place'),
            [$this, 'base_id_field'],
            'hph_airtable_settings',
            'hph_airtable_connection'
        );
        
        add_settings_field(
            'table_name',
            __('Table Name', 'happy-place'),
            [$this, 'table_name_field'],
            'hph_airtable_settings',
            'hph_airtable_connection'
        );
        
        // Sync option fields
        add_settings_field(
            'sync_enabled',
            __('Enable Sync', 'happy-place'),
            [$this, 'sync_enabled_field'],
            'hph_airtable_settings',
            'hph_airtable_sync_options'
        );
        
        add_settings_field(
            'sync_direction',
            __('Sync Direction', 'happy-place'),
            [$this, 'sync_direction_field'],
            'hph_airtable_settings',
            'hph_airtable_sync_options'
        );
        
        add_settings_field(
            'auto_sync',
            __('Automatic Sync', 'happy-place'),
            [$this, 'auto_sync_field'],
            'hph_airtable_settings',
            'hph_airtable_sync_options'
        );
        
        add_settings_field(
            'sync_frequency',
            __('Sync Frequency', 'happy-place'),
            [$this, 'sync_frequency_field'],
            'hph_airtable_settings',
            'hph_airtable_sync_options'
        );
        
        add_settings_field(
            'media_sync',
            __('Media Sync', 'happy-place'),
            [$this, 'media_sync_field'],
            'hph_airtable_settings',
            'hph_airtable_sync_options'
        );
    }
    
    /**
     * Render admin page
     */
    public function render_page(): void {
        $settings = get_option('hph_airtable_sync_settings', $this->get_default_settings());
        $current_tab = $_GET['tab'] ?? 'settings';
        
        ?>
        <div class="wrap hph-airtable-admin">
            <h1><?php esc_html_e('Airtable Integration', 'happy-place'); ?></h1>
            
            <?php $this->render_connection_status($settings); ?>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=happy-place-integrations&tab=settings" 
                   class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-integrations&tab=sync" 
                   class="nav-tab <?php echo $current_tab === 'sync' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Sync Management', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-integrations&tab=logs" 
                   class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Sync Logs', 'happy-place'); ?>
                </a>
                <a href="?page=happy-place-integrations&tab=help" 
                   class="nav-tab <?php echo $current_tab === 'help' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Help', 'happy-place'); ?>
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
                    case 'logs':
                        $this->render_logs_tab();
                        break;
                    case 'help':
                        $this->render_help_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render connection status
     */
    private function render_connection_status($settings): void {
        $is_configured = !empty($settings['api_key']) && !empty($settings['base_id']);
        $status_class = $is_configured ? 'notice-success' : 'notice-warning';
        $status_text = $is_configured ? __('Connected', 'happy-place') : __('Not Configured', 'happy-place');
        
        ?>
        <div class="notice <?php echo esc_attr($status_class); ?> hph-connection-status">
            <p>
                <strong><?php esc_html_e('Connection Status:', 'happy-place'); ?></strong>
                <span class="status-indicator"><?php echo esc_html($status_text); ?></span>
                
                <?php if ($is_configured): ?>
                    <button type="button" class="button button-secondary" id="test-connection">
                        <?php esc_html_e('Test Connection', 'happy-place'); ?>
                    </button>
                <?php endif; ?>
            </p>
            <div id="connection-test-result" style="display: none;"></div>
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
            settings_fields('hph_airtable_settings');
            do_settings_sections('hph_airtable_settings');
            submit_button();
            ?>
        </form>
        <?php
    }
    
    /**
     * Render sync management tab
     */
    private function render_sync_tab(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $last_sync = get_option('hph_airtable_last_sync', []);
        
        ?>
        <div class="hph-sync-management">
            <div class="sync-status-cards">
                <div class="sync-card">
                    <h3><?php esc_html_e('Last Sync', 'happy-place'); ?></h3>
                    <p class="sync-time">
                        <?php 
                        if (!empty($last_sync['timestamp'])) {
                            echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_sync['timestamp']));
                        } else {
                            esc_html_e('Never', 'happy-place');
                        }
                        ?>
                    </p>
                    <p class="sync-details">
                        <?php
                        if (!empty($last_sync['processed'])) {
                            printf(
                                esc_html__('%d records processed', 'happy-place'),
                                intval($last_sync['processed'])
                            );
                        }
                        ?>
                    </p>
                </div>
                
                <div class="sync-card">
                    <h3><?php esc_html_e('Next Scheduled Sync', 'happy-place'); ?></h3>
                    <p class="sync-time">
                        <?php
                        if (!empty($settings['auto_sync']) && !empty($settings['sync_frequency'])) {
                            $next_sync = wp_next_scheduled('hph_airtable_periodic_sync');
                            if ($next_sync) {
                                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_sync));
                            } else {
                                esc_html_e('Not scheduled', 'happy-place');
                            }
                        } else {
                            esc_html_e('Disabled', 'happy-place');
                        }
                        ?>
                    </p>
                </div>
                
                <div class="sync-card">
                    <h3><?php esc_html_e('Sync Status', 'happy-place'); ?></h3>
                    <p class="sync-status" id="current-sync-status">
                        <?php esc_html_e('Ready', 'happy-place'); ?>
                    </p>
                </div>
            </div>
            
            <div class="sync-actions">
                <h3><?php esc_html_e('Manual Sync', 'happy-place'); ?></h3>
                <p><?php esc_html_e('Trigger a manual sync between WordPress and Airtable.', 'happy-place'); ?></p>
                
                <div class="sync-buttons">
                    <button type="button" class="button button-primary" id="manual-sync-both">
                        <?php esc_html_e('Full Two-Way Sync', 'happy-place'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="manual-sync-from-airtable">
                        <?php esc_html_e('Sync from Airtable', 'happy-place'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="manual-sync-to-airtable">
                        <?php esc_html_e('Sync to Airtable', 'happy-place'); ?>
                    </button>
                </div>
                
                <div id="sync-progress" style="display: none;">
                    <div class="sync-progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="sync-progress-text"></p>
                </div>
                
                <div id="sync-results" style="display: none;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render logs tab
     */
    private function render_logs_tab(): void {
        $logs = get_option('hph_airtable_sync_logs', []);
        $logs = array_slice(array_reverse($logs), 0, 50); // Show last 50 logs
        
        ?>
        <div class="hph-sync-logs">
            <div class="logs-header">
                <h3><?php esc_html_e('Sync Logs', 'happy-place'); ?></h3>
                <button type="button" class="button button-secondary" id="clear-logs">
                    <?php esc_html_e('Clear Logs', 'happy-place'); ?>
                </button>
            </div>
            
            <?php if (empty($logs)): ?>
                <p><?php esc_html_e('No sync logs available.', 'happy-place'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date/Time', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Type', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Direction', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Status', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Records', 'happy-place'); ?></th>
                            <th><?php esc_html_e('Message', 'happy-place'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log['timestamp'])); ?></td>
                                <td><?php echo esc_html($log['type'] ?? 'sync'); ?></td>
                                <td><?php echo esc_html($log['direction'] ?? 'both'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($log['status'] ?? 'unknown'); ?>">
                                        <?php echo esc_html(ucfirst($log['status'] ?? 'unknown')); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log['records_processed'] ?? '0'); ?></td>
                                <td><?php echo esc_html($log['message'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render help tab
     */
    private function render_help_tab(): void {
        ?>
        <div class="hph-airtable-help">
            <div class="help-section">
                <h3><?php esc_html_e('Getting Started', 'happy-place'); ?></h3>
                <ol>
                    <li><?php esc_html_e('Create an Airtable account and base for your listings', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Generate an API key from your Airtable account settings', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Copy your Base ID from the Airtable API documentation', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Enter these credentials in the Settings tab', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Test the connection and configure sync options', 'happy-place'); ?></li>
                </ol>
            </div>
            
            <div class="help-section">
                <h3><?php esc_html_e('Field Mapping', 'happy-place'); ?></h3>
                <p><?php esc_html_e('The following WordPress fields are automatically mapped to Airtable:', 'happy-place'); ?></p>
                <ul>
                    <li><strong>Title</strong> → Title</li>
                    <li><strong>Description</strong> → Description</li>
                    <li><strong>Price</strong> → Price</li>
                    <li><strong>Bedrooms</strong> → Bedrooms</li>
                    <li><strong>Bathrooms</strong> → Bathrooms</li>
                    <li><strong>Square Feet</strong> → Square_Feet</li>
                    <li><strong>Address</strong> → Address</li>
                    <li><strong>City</strong> → City</li>
                    <li><strong>State</strong> → State</li>
                    <li><strong>ZIP Code</strong> → ZIP_Code</li>
                </ul>
            </div>
            
            <div class="help-section">
                <h3><?php esc_html_e('Troubleshooting', 'happy-place'); ?></h3>
                <h4><?php esc_html_e('Connection Issues', 'happy-place'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Verify your API key is correct and has proper permissions', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Check that your Base ID matches exactly (case-sensitive)', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Ensure the table name exists in your Airtable base', 'happy-place'); ?></li>
                </ul>
                
                <h4><?php esc_html_e('Sync Issues', 'happy-place'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Check the Sync Logs tab for detailed error messages', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Ensure field names in Airtable match the expected mapping', 'happy-place'); ?></li>
                    <li><?php esc_html_e('Verify that required fields have values', 'happy-place'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * Field callbacks
     */
    public function connection_section_callback(): void {
        echo '<p>' . esc_html__('Enter your Airtable connection details to enable sync functionality.', 'happy-place') . '</p>';
    }
    
    public function sync_options_section_callback(): void {
        echo '<p>' . esc_html__('Configure how and when data syncs between WordPress and Airtable.', 'happy-place') . '</p>';
    }
    
    public function field_mapping_section_callback(): void {
        echo '<p>' . esc_html__('Advanced field mapping options (automatic for standard fields).', 'happy-place') . '</p>';
    }
    
    public function api_key_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $value = $settings['api_key'] ?? '';
        
        echo '<input type="password" name="hph_airtable_sync_settings[api_key]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your Airtable API key (keep this secure)', 'happy-place') . '</p>';
    }
    
    public function base_id_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $value = $settings['base_id'] ?? '';
        
        echo '<input type="text" name="hph_airtable_sync_settings[base_id]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your Airtable Base ID (found in API documentation)', 'happy-place') . '</p>';
    }
    
    public function table_name_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $value = $settings['table_name'] ?? 'Listings';
        
        echo '<input type="text" name="hph_airtable_sync_settings[table_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Name of the table in your Airtable base', 'happy-place') . '</p>';
    }
    
    public function sync_enabled_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $checked = !empty($settings['sync_enabled']) ? 'checked' : '';
        
        echo '<label><input type="checkbox" name="hph_airtable_sync_settings[sync_enabled]" value="1" ' . $checked . ' /> ';
        echo esc_html__('Enable Airtable sync functionality', 'happy-place') . '</label>';
    }
    
    public function sync_direction_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $value = $settings['sync_direction'] ?? 'bidirectional';
        
        $options = [
            'bidirectional' => __('Two-way (bidirectional)', 'happy-place'),
            'to_airtable' => __('WordPress to Airtable only', 'happy-place'),
            'from_airtable' => __('Airtable to WordPress only', 'happy-place')
        ];
        
        echo '<select name="hph_airtable_sync_settings[sync_direction]">';
        foreach ($options as $key => $label) {
            $selected = $value === $key ? 'selected' : '';
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    public function auto_sync_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $checked = !empty($settings['auto_sync']) ? 'checked' : '';
        
        echo '<label><input type="checkbox" name="hph_airtable_sync_settings[auto_sync]" value="1" ' . $checked . ' /> ';
        echo esc_html__('Enable automatic periodic sync', 'happy-place') . '</label>';
    }
    
    public function sync_frequency_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $value = $settings['sync_frequency'] ?? 'hourly';
        
        $frequencies = wp_get_schedules();
        $allowed_frequencies = ['hourly', 'twicedaily', 'daily', 'every_three_hours', 'every_six_hours', 'every_twelve_hours'];
        
        echo '<select name="hph_airtable_sync_settings[sync_frequency]">';
        foreach ($allowed_frequencies as $frequency) {
            if (isset($frequencies[$frequency])) {
                $selected = $value === $frequency ? 'selected' : '';
                echo '<option value="' . esc_attr($frequency) . '" ' . $selected . '>' . esc_html($frequencies[$frequency]['display']) . '</option>';
            }
        }
        echo '</select>';
    }
    
    public function media_sync_field(): void {
        $settings = get_option('hph_airtable_sync_settings', []);
        $checked = !empty($settings['media_sync']) ? 'checked' : '';
        
        echo '<label><input type="checkbox" name="hph_airtable_sync_settings[media_sync]" value="1" ' . $checked . ' /> ';
        echo esc_html__('Enable media/image sync', 'happy-place') . '</label>';
        echo '<p class="description">' . esc_html__('Sync images between WordPress and Airtable', 'happy-place') . '</p>';
    }
    
    /**
     * AJAX handlers
     */
    public function test_connection(): void {
        check_ajax_referer('hph_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $settings = get_option('hph_airtable_sync_settings', []);
        
        if (empty($settings['api_key']) || empty($settings['base_id'])) {
            wp_send_json_error(__('API key and Base ID are required', 'happy-place'));
        }
        
        try {
            $sync = new Enhanced_Airtable_Sync(
                $settings['base_id'],
                $settings['table_name'] ?? 'Listings',
                $settings['api_key']
            );
            
            $result = $sync->test_connection();
            
            if ($result['success']) {
                wp_send_json_success(__('Connection successful!', 'happy-place'));
            } else {
                wp_send_json_error($result['message'] ?? __('Connection failed', 'happy-place'));
            }
            
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function manual_sync(): void {
        check_ajax_referer('hph_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $direction = sanitize_text_field($_POST['direction'] ?? 'both');
        $settings = get_option('hph_airtable_sync_settings', []);
        
        if (empty($settings['sync_enabled'])) {
            wp_send_json_error(__('Sync is not enabled', 'happy-place'));
        }
        
        try {
            $sync = new Enhanced_Airtable_Sync(
                $settings['base_id'],
                $settings['table_name'] ?? 'Listings',
                $settings['api_key']
            );
            
            $results = [];
            
            if ($direction === 'both' || $direction === 'from_airtable') {
                $results['from_airtable'] = $sync->sync_airtable_to_wordpress();
            }
            
            if ($direction === 'both' || $direction === 'to_airtable') {
                $results['to_airtable'] = $sync->sync_wordpress_to_airtable();
            }
            
            // Log the sync
            $this->log_sync('manual', $direction, 'success', $results);
            
            wp_send_json_success([
                'message' => __('Sync completed successfully', 'happy-place'),
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            $this->log_sync('manual', $direction, 'error', [], $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    public function get_sync_status(): void {
        check_ajax_referer('hph_admin_nonce', 'nonce');
        
        $last_sync = get_option('hph_airtable_last_sync', []);
        $next_sync = wp_next_scheduled('hph_airtable_periodic_sync');
        
        wp_send_json_success([
            'last_sync' => $last_sync,
            'next_sync' => $next_sync,
            'is_running' => get_transient('hph_airtable_sync_running') ? true : false
        ]);
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook): void {
        if (strpos($hook, 'happy-place') === false) {
            return;
        }
        
        wp_enqueue_script(
            'hph-airtable-admin',
            HPH_ASSETS_URL . 'js/airtable-admin.js',
            ['jquery'],
            HPH_VERSION,
            true
        );
        
        wp_localize_script('hph-airtable-admin', 'hphAirtable', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_admin_nonce'),
            'strings' => [
                'testing' => __('Testing connection...', 'happy-place'),
                'syncing' => __('Syncing...', 'happy-place'),
                'error' => __('Error', 'happy-place'),
                'success' => __('Success', 'happy-place')
            ]
        ]);
        
        wp_enqueue_style(
            'hph-airtable-admin',
            HPH_ASSETS_URL . 'css/airtable-admin.css',
            [],
            HPH_VERSION
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input): array {
        $sanitized = [];
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key'] ?? '');
        $sanitized['base_id'] = sanitize_text_field($input['base_id'] ?? '');
        $sanitized['table_name'] = sanitize_text_field($input['table_name'] ?? 'Listings');
        $sanitized['sync_enabled'] = !empty($input['sync_enabled']);
        $sanitized['sync_direction'] = in_array($input['sync_direction'] ?? '', ['bidirectional', 'to_airtable', 'from_airtable']) 
            ? $input['sync_direction'] : 'bidirectional';
        $sanitized['auto_sync'] = !empty($input['auto_sync']);
        $sanitized['sync_frequency'] = sanitize_text_field($input['sync_frequency'] ?? 'hourly');
        $sanitized['media_sync'] = !empty($input['media_sync']);
        
        // Update cron schedule if auto sync settings changed
        $this->update_cron_schedule($sanitized);
        
        return $sanitized;
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings(): array {
        return [
            'api_key' => '',
            'base_id' => '',
            'table_name' => 'Listings',
            'sync_enabled' => false,
            'sync_direction' => 'bidirectional',
            'auto_sync' => false,
            'sync_frequency' => 'hourly',
            'media_sync' => true
        ];
    }
    
    /**
     * Update cron schedule
     */
    private function update_cron_schedule($settings): void {
        // Clear existing schedule
        wp_clear_scheduled_hook('hph_airtable_periodic_sync');
        
        // Schedule new cron if auto sync is enabled
        if (!empty($settings['auto_sync']) && !empty($settings['sync_enabled'])) {
            wp_schedule_event(time(), $settings['sync_frequency'], 'hph_airtable_periodic_sync');
        }
    }
    
    /**
     * Log sync activity
     */
    private function log_sync($type, $direction, $status, $results = [], $message = ''): void {
        $logs = get_option('hph_airtable_sync_logs', []);
        
        $log_entry = [
            'timestamp' => time(),
            'type' => $type,
            'direction' => $direction,
            'status' => $status,
            'records_processed' => is_array($results) ? array_sum(array_column($results, 'processed')) : 0,
            'message' => $message
        ];
        
        $logs[] = $log_entry;
        
        // Keep only last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        update_option('hph_airtable_sync_logs', $logs);
        
        // Update last sync info
        if ($status === 'success') {
            update_option('hph_airtable_last_sync', $log_entry);
        }
    }
}
