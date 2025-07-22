<?php

namespace HPH\Admin;

/**
 * Integrations Manager
 * Handles all third-party integration settings and configurations
 */
class Integrations_Manager
{
    private static ?self $instance = null;
    private string $option_name = 'happy_place_integrations';

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_hph_test_airtable_connection', [$this, 'test_airtable_connection']);
        add_action('wp_ajax_hph_trigger_manual_sync', [$this, 'trigger_manual_sync']);
        add_action('wp_ajax_hph_save_cron_settings', [$this, 'save_cron_settings']);
    }

    /**
     * Register settings for integrations
     */
    public function register_settings(): void
    {
        register_setting(
            'happy_place_integrations',
            $this->option_name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => [
                    'airtable' => [
                        'access_token' => '',
                        'base_id' => '',
                        'table_name' => 'Listings',
                        'enabled' => false,
                        'sync_interval' => 'every_six_hours',
                        'auto_sync' => false
                    ]
                ]
            ]
        );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input): array
    {
        $sanitized = [];
        
        if (isset($input['airtable'])) {
            $airtable = $input['airtable'];
            $sanitized['airtable'] = [
                'access_token' => sanitize_text_field($airtable['access_token'] ?? ''),
                'base_id' => sanitize_text_field($airtable['base_id'] ?? ''),
                'table_name' => sanitize_text_field($airtable['table_name'] ?? 'Listings'),
                'enabled' => !empty($airtable['enabled']),
                'sync_interval' => sanitize_text_field($airtable['sync_interval'] ?? 'every_six_hours'),
                'auto_sync' => !empty($airtable['auto_sync'])
            ];

            // Update cron schedule when settings are saved
            $this->update_cron_schedule($sanitized['airtable']);
        }
        
        return $sanitized;
    }

    /**
     * Update cron schedule based on settings
     */
    private function update_cron_schedule($airtable_settings): void
    {
        // Clear existing scheduled event
        wp_clear_scheduled_hook('hph_airtable_periodic_sync');

        // Schedule new event if auto sync is enabled
        if (!empty($airtable_settings['enabled']) && 
            !empty($airtable_settings['auto_sync']) && 
            !empty($airtable_settings['access_token']) && 
            !empty($airtable_settings['base_id'])) {
            
            $interval = $airtable_settings['sync_interval'] ?? 'every_six_hours';
            wp_schedule_event(time(), $interval, 'hph_airtable_periodic_sync');
            
            error_log('HPH: Scheduled Airtable sync with interval: ' . $interval);
        } else {
            error_log('HPH: Airtable auto sync disabled or not configured');
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook): void
    {
        if ($hook !== 'happy-place_page_happy-place-integrations') {
            return;
        }

        wp_enqueue_script(
            'happy-place-integrations',
            plugin_dir_url(__FILE__) . '../../assets/js/integrations.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('happy-place-integrations', 'hphIntegrations', [
            'nonce' => wp_create_nonce('hph_integrations_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'strings' => [
                'testing' => __('Testing connection...', 'happy-place'),
                'success' => __('Connection successful!', 'happy-place'),
                'error' => __('Connection failed:', 'happy-place'),
                'syncing' => __('Starting sync...', 'happy-place'),
                'sync_complete' => __('Sync completed!', 'happy-place'),
                'sync_error' => __('Sync failed:', 'happy-place')
            ]
        ]);

        // Add admin styles
        wp_add_inline_style('wp-admin', $this->get_admin_styles());
    }

    /**
     * Get admin styles
     */
    private function get_admin_styles(): string
    {
        return '
            .hph-integration-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .hph-integration-header {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
            }
            .hph-integration-logo {
                width: 40px;
                height: 40px;
                margin-right: 15px;
                background: #0073aa;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
            }
            .hph-integration-title {
                margin: 0;
                font-size: 18px;
            }
            .hph-integration-status {
                margin-left: auto;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: 500;
            }
            .hph-status-enabled {
                background: #d4edda;
                color: #155724;
            }
            .hph-status-disabled {
                background: #f8d7da;
                color: #721c24;
            }
            .hph-settings-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 15px;
            }
            .hph-settings-section h4 {
                margin-top: 0;
                margin-bottom: 10px;
                color: #23282d;
            }
            .hph-form-row {
                margin-bottom: 15px;
            }
            .hph-form-row label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .hph-form-row input,
            .hph-form-row select {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .hph-button-group {
                margin-top: 15px;
            }
            .hph-button-group .button {
                margin-right: 10px;
            }
            .hph-test-result {
                margin-top: 10px;
                padding: 10px;
                border-radius: 4px;
                display: none;
            }
            .hph-test-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .hph-test-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .hph-sync-progress {
                margin-top: 10px;
                display: none;
            }
            .hph-progress-bar {
                background: #f1f1f1;
                height: 20px;
                border-radius: 10px;
                overflow: hidden;
            }
            .hph-progress-fill {
                background: #0073aa;
                height: 100%;
                width: 0%;
                transition: width 0.3s ease;
            }
        ';
    }

    /**
     * Render the integrations page
     */
    public function render_integrations_page(): void
    {
        $options = get_option($this->option_name, []);
        $airtable_settings = $options['airtable'] ?? [];
        
        ?>
        <div class="wrap">
            <h1><?php _e('Integrations', 'happy-place'); ?></h1>
            <p><?php _e('Manage your third-party integrations and sync settings.', 'happy-place'); ?></p>

            <form method="post" action="options.php" id="hph-integrations-form">
                <?php
                settings_fields('happy_place_integrations');
                ?>

                <!-- Airtable Integration -->
                <div class="hph-integration-card">
                    <div class="hph-integration-header">
                        <div class="hph-integration-logo">AT</div>
                        <h3 class="hph-integration-title"><?php _e('Airtable Integration', 'happy-place'); ?></h3>
                        <span class="hph-integration-status <?php echo !empty($airtable_settings['enabled']) ? 'hph-status-enabled' : 'hph-status-disabled'; ?>">
                            <?php echo !empty($airtable_settings['enabled']) ? __('Enabled', 'happy-place') : __('Disabled', 'happy-place'); ?>
                        </span>
                    </div>

                    <p><?php _e('Sync your property listings with Airtable for advanced data management and collaboration.', 'happy-place'); ?></p>

                    <div class="hph-settings-grid">
                        <div class="hph-settings-section">
                            <h4><?php _e('Connection Settings', 'happy-place'); ?></h4>
                            
                            <div class="hph-form-row">
                                <label for="airtable_access_token"><?php _e('Personal Access Token', 'happy-place'); ?> *</label>
                                <input 
                                    type="password" 
                                    id="airtable_access_token" 
                                    name="<?php echo esc_attr($this->option_name); ?>[airtable][access_token]" 
                                    value="<?php echo esc_attr($airtable_settings['access_token'] ?? ''); ?>"
                                    placeholder="<?php _e('Enter your Airtable Personal Access Token', 'happy-place'); ?>"
                                />
                                <p class="description">
                                    <?php _e('Create a Personal Access Token at', 'happy-place'); ?> 
                                    <a href="https://airtable.com/create/tokens" target="_blank"><?php _e('Airtable Developer Hub', 'happy-place'); ?></a>.
                                    <br><?php _e('Required scopes: data.records:read, data.records:write. Add your base as a resource.', 'happy-place'); ?>
                                    <br><?php _e('API keys were deprecated as of February 2024.', 'happy-place'); ?>
                                </p>
                            </div>

                            <div class="hph-form-row">
                                <label for="airtable_base_id"><?php _e('Base ID', 'happy-place'); ?> *</label>
                                <input 
                                    type="text" 
                                    id="airtable_base_id" 
                                    name="<?php echo esc_attr($this->option_name); ?>[airtable][base_id]" 
                                    value="<?php echo esc_attr($airtable_settings['base_id'] ?? ''); ?>"
                                    placeholder="<?php _e('e.g., appXXXXXXXXXXXXXX', 'happy-place'); ?>"
                                />
                                <p class="description">
                                    <?php _e('Find your Base ID in your Airtable base URL (starts with "app") or in the', 'happy-place'); ?> 
                                    <a href="https://airtable.com/developers/web/api/introduction" target="_blank"><?php _e('Airtable API documentation', 'happy-place'); ?></a>
                                </p>
                            </div>

                            <div class="hph-form-row">
                                <label for="airtable_table_name"><?php _e('Table Name', 'happy-place'); ?></label>
                                <input 
                                    type="text" 
                                    id="airtable_table_name" 
                                    name="<?php echo esc_attr($this->option_name); ?>[airtable][table_name]" 
                                    value="<?php echo esc_attr($airtable_settings['table_name'] ?? 'Listings'); ?>"
                                    placeholder="<?php _e('Listings', 'happy-place'); ?>"
                                />
                            </div>

                            <div class="hph-button-group">
                                <button type="button" class="button" id="hph-test-airtable"><?php _e('Test Connection', 'happy-place'); ?></button>
                                <button type="button" class="button button-secondary" id="hph-manual-sync"><?php _e('Manual Sync', 'happy-place'); ?></button>
                            </div>

                            <div id="hph-test-result" class="hph-test-result"></div>
                            <div id="hph-sync-progress" class="hph-sync-progress">
                                <div class="hph-progress-bar">
                                    <div class="hph-progress-fill"></div>
                                </div>
                                <p id="hph-sync-status"><?php _e('Preparing sync...', 'happy-place'); ?></p>
                            </div>
                        </div>

                        <div class="hph-settings-section">
                            <h4><?php _e('Sync Settings', 'happy-place'); ?></h4>
                            
                            <div class="hph-form-row">
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo esc_attr($this->option_name); ?>[airtable][enabled]" 
                                        value="1"
                                        <?php checked(!empty($airtable_settings['enabled'])); ?>
                                    />
                                    <?php _e('Enable Airtable Integration', 'happy-place'); ?>
                                </label>
                            </div>

                            <div class="hph-form-row">
                                <label>
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo esc_attr($this->option_name); ?>[airtable][auto_sync]" 
                                        value="1"
                                        <?php checked(!empty($airtable_settings['auto_sync'])); ?>
                                    />
                                    <?php _e('Enable Automatic Sync', 'happy-place'); ?>
                                </label>
                            </div>

                            <div class="hph-form-row">
                                <label for="airtable_sync_interval"><?php _e('Sync Interval', 'happy-place'); ?></label>
                                <select 
                                    id="airtable_sync_interval" 
                                    name="<?php echo esc_attr($this->option_name); ?>[airtable][sync_interval]"
                                >
                                    <option value="every_hour" <?php selected($airtable_settings['sync_interval'] ?? '', 'every_hour'); ?>><?php _e('Every Hour', 'happy-place'); ?></option>
                                    <option value="every_three_hours" <?php selected($airtable_settings['sync_interval'] ?? '', 'every_three_hours'); ?>><?php _e('Every 3 Hours', 'happy-place'); ?></option>
                                    <option value="every_six_hours" <?php selected($airtable_settings['sync_interval'] ?? 'every_six_hours', 'every_six_hours'); ?>><?php _e('Every 6 Hours', 'happy-place'); ?></option>
                                    <option value="every_twelve_hours" <?php selected($airtable_settings['sync_interval'] ?? '', 'every_twelve_hours'); ?>><?php _e('Every 12 Hours', 'happy-place'); ?></option>
                                    <option value="daily" <?php selected($airtable_settings['sync_interval'] ?? '', 'daily'); ?>><?php _e('Daily', 'happy-place'); ?></option>
                                </select>
                            </div>

                            <div class="hph-form-row">
                                <h5><?php _e('Next Scheduled Sync', 'happy-place'); ?></h5>
                                <?php
                                $next_sync = wp_next_scheduled('hph_airtable_periodic_sync');
                                if ($next_sync) {
                                    echo '<p>' . sprintf(__('Next sync: %s', 'happy-place'), date('Y-m-d H:i:s', $next_sync)) . '</p>';
                                } else {
                                    echo '<p>' . __('No sync scheduled', 'happy-place') . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php submit_button(__('Save Integration Settings', 'happy-place')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Test Airtable connection via AJAX
     */
    public function test_airtable_connection(): void
    {
        check_ajax_referer('hph_integrations_nonce', 'nonce');

        if (!current_user_can('read')) { // Most permissive - all logged in users
            wp_die(__('Insufficient permissions', 'happy-place'));
        }

        $access_token = sanitize_text_field($_POST['access_token'] ?? '');
        $base_id = sanitize_text_field($_POST['base_id'] ?? '');
        $table_name = sanitize_text_field($_POST['table_name'] ?? 'Listings');

        if (empty($access_token) || empty($base_id)) {
            wp_send_json_error(__('Personal Access Token and Base ID are required', 'happy-place'));
        }

        try {
            // Test connection by making a simple API call
            $url = "https://api.airtable.com/v0/{$base_id}/{$table_name}?maxRecords=1";
            
            $response = wp_remote_get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            
            if ($code === 200) {
                wp_send_json_success(__('Connection successful! Airtable integration is working.', 'happy-place'));
            } else {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                $error_message = $data['error']['message'] ?? sprintf(__('HTTP Error: %s', 'happy-place'), $code);
                wp_send_json_error($error_message);
            }

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Trigger manual sync via AJAX
     */
    public function trigger_manual_sync(): void
    {
        check_ajax_referer('hph_integrations_nonce', 'nonce');

        if (!current_user_can('read')) { // Most permissive - all logged in users
            wp_die(__('Insufficient permissions', 'happy-place'));
        }

        try {
            $options = get_option($this->option_name, []);
            $airtable_settings = $options['airtable'] ?? [];

            if (empty($airtable_settings['access_token']) || empty($airtable_settings['base_id'])) {
                wp_send_json_error(__('Airtable Personal Access Token and Base ID must be configured first', 'happy-place'));
            }

            // Add some debug logging
            error_log('HPH: Starting manual sync with Base ID: ' . $airtable_settings['base_id']);

            // Load the sync class
            require_once plugin_dir_path(dirname(__FILE__)) . 'integrations/class-airtable-two-way-sync.php';
            
            $sync = new \HappyPlace\Integrations\Airtable_Two_Way_Sync(
                $airtable_settings['base_id'],
                $airtable_settings['table_name'] ?? 'Listings'
            );

            // Perform both directions of sync
            $airtable_to_wp = $sync->sync_airtable_to_wordpress();
            $wp_to_airtable = $sync->sync_wordpress_to_airtable();

            error_log('HPH: Sync completed - Airtable to WP: ' . json_encode($airtable_to_wp));
            error_log('HPH: Sync completed - WP to Airtable: ' . json_encode($wp_to_airtable));

            $message = sprintf(
                'Sync completed: %s created, %s updated from Airtable. %s created, %s updated to Airtable.',
                $airtable_to_wp['stats']['created'] ?? 'undefined',
                $airtable_to_wp['stats']['updated'] ?? 'undefined',
                $wp_to_airtable['stats']['created'] ?? 'undefined',
                $wp_to_airtable['stats']['updated'] ?? 'undefined'
            );

            // Add field mapping warnings if present
            $warnings = [];
            if (!empty($airtable_to_wp['warnings'])) {
                $warnings = array_merge($warnings, $airtable_to_wp['warnings']);
            }
            if (!empty($wp_to_airtable['warnings'])) {
                $warnings = array_merge($warnings, $wp_to_airtable['warnings']);
            }

            $response_data = [
                'airtable_to_wp' => $airtable_to_wp,
                'wp_to_airtable' => $wp_to_airtable,
                'message' => $message
            ];

            if (!empty($warnings)) {
                $response_data['warnings'] = $warnings;
                $response_data['message'] .= ' Note: ' . implode(' ', $warnings);
            }

            wp_send_json_success($response_data);

        } catch (\Exception $e) {
            error_log('HPH: Sync error - ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Save cron settings and reschedule
     */
    public function save_cron_settings(): void
    {
        check_ajax_referer('hph_integrations_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'happy-place'));
        }

        $interval = sanitize_text_field($_POST['interval'] ?? 'every_six_hours');
        $enabled = !empty($_POST['enabled']);

        // Clear existing scheduled event
        wp_clear_scheduled_hook('hph_airtable_periodic_sync');

        if ($enabled) {
            // Schedule new event
            wp_schedule_event(time(), $interval, 'hph_airtable_periodic_sync');
        }

        wp_send_json_success(__('Cron settings updated successfully', 'happy-place'));
    }
}
