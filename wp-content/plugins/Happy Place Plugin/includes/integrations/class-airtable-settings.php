<?php
namespace HappyPlace\Integrations;

class Airtable_Settings {
    private static ?self $instance = null;
    private string $option_name = 'happy_place_airtable_options';
    private string $page_slug = 'happy-place-airtable-sync';

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_hph_two_way_airtable_sync', [$this, 'handle_sync_ajax']);
        add_action('wp_ajax_hph_save_airtable_config', [$this, 'save_airtable_config']);
    }

    /**
     * Register settings for Airtable integration
     */
    public function register_settings(): void {
        register_setting(
            'happy_place_airtable_options',
            $this->option_name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => [
                    'base_id' => '',
                    'sync_table' => 'Listings'
                ]
            ]
        );
    }

    /**
     * Sanitize settings input
     */
    public function sanitize_settings($input): array {
        $sanitized = [];
        
        if (isset($input['base_id'])) {
            $sanitized['base_id'] = sanitize_text_field($input['base_id']);
        }
        
        if (isset($input['sync_table'])) {
            $sanitized['sync_table'] = sanitize_text_field($input['sync_table']);
        }
        
        return $sanitized;
    }

    /**
     * Add settings page to WordPress menu
     */
    public function add_settings_page(): void {
        add_menu_page(
            'Airtable Two-Way Sync',   // Page title
            'Airtable Sync',           // Menu title
            'manage_options',           // Capability
            $this->page_slug,           // Menu slug
            [$this, 'render_settings_page'], // Callback
            'dashicons-database-import', // Icon
            30                          // Position
        );
    }

    /**
     * Enqueue necessary scripts
     */
    public function enqueue_scripts($hook): void {
        if ($hook !== 'toplevel_page_' . $this->page_slug) {
            return;
        }

        wp_enqueue_script(
            'happy-place-airtable-sync',
            plugin_dir_url(__FILE__) . '../../assets/js/airtable-two-way-sync.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('happy-place-airtable-sync', 'happyPlaceAirtable', [
            'nonce' => wp_create_nonce('happy_place_airtable_two_way_sync'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

        // Inline styles
        wp_add_inline_style('wp-admin', '
            .sync-progress-container {
                background-color: #f0f0f0;
                height: 20px;
                margin: 15px 0;
                border-radius: 10px;
                overflow: hidden;
            }
            .sync-progress {
                width: 0;
                height: 100%;
                background-color: #46b450;
                transition: width 0.5s ease;
            }
            #hph-sync-status .notice {
                margin: 10px 0;
            }
        ');
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        // Get current settings
        $options = $this->get_settings();
        ?>
        <div class="wrap">
            <h1>Airtable Two-Way Sync</h1>

            <div id="hph-airtable-sync-container">
                <form id="hph-airtable-config-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="airtable-base-id">Airtable Base ID</label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    id="airtable-base-id" 
                                    name="base_id" 
                                    value="<?php echo esc_attr($options['base_id'] ?? ''); ?>" 
                                    class="regular-text"
                                    placeholder="Enter Airtable Base ID"
                                >
                                <p class="description">
                                    Find your Base ID in the 
                                    <a href="https://airtable.com/api" target="_blank">Airtable API documentation</a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="airtable-table-name">Table Name</label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    id="airtable-table-name" 
                                    name="sync_table" 
                                    value="<?php echo esc_attr($options['sync_table'] ?? 'Listings'); ?>" 
                                    class="regular-text"
                                    placeholder="Enter Table Name"
                                >
                                <p class="description">
                                    Name of the Airtable table to synchronize
                                </p>
                            </td>
                        </tr>
                    </table>

                    <div class="sync-actions">
                        <button 
                            type="submit" 
                            class="button button-primary"
                        >
                            Save Configuration
                        </button>
                        <button 
                            id="hph-trigger-two-way-sync" 
                            class="button button-secondary"
                        >
                            Trigger Two-Way Sync
                        </button>
                    </div>
                </form>

                <div class="sync-progress-container">
                    <div 
                        id="hph-sync-progress" 
                        class="sync-progress"
                    ></div>
                </div>

                <div 
                    id="hph-sync-status" 
                    class="sync-status"
                ></div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX save of Airtable configuration
     */
    public function save_airtable_config(): void {
        // Verify nonce
        check_ajax_referer('happy_place_airtable_two_way_sync', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        // Parse and sanitize input
        $base_id = sanitize_text_field($_POST['base_id'] ?? '');
        $sync_table = sanitize_text_field($_POST['sync_table'] ?? 'Listings');

        // Update options
        $options = $this->get_settings();
        $options['base_id'] = $base_id;
        $options['sync_table'] = $sync_table;

        // Save options
        update_option($this->option_name, $options);

        wp_send_json_success('Configuration saved successfully');
    }

    /**
     * Handle two-way sync AJAX request
     */
    public function handle_sync_ajax(): void {
        // Verify nonce for security
        check_ajax_referer('happy_place_airtable_two_way_sync', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        try {
            // Get configuration from options
            $options = $this->get_settings();
            $base_id = $options['base_id'] ?? null;
            $table_name = $options['sync_table'] ?? 'Listings';

            if (!$base_id) {
                throw new \Exception('Airtable Base ID not configured');
            }

            // Perform two-way sync
            $sync = new Airtable_Two_Way_Sync($base_id, $table_name);

            // Sync from Airtable to WordPress
            $airtable_to_wp_result = $sync->sync_airtable_to_wordpress();

            // Sync from WordPress to Airtable
            $wp_to_airtable_result = $sync->sync_wordpress_to_airtable();

            // Send successful response
            wp_send_json_success([
                'airtable_to_wp' => $airtable_to_wp_result,
                'wp_to_airtable' => $wp_to_airtable_result
            ]);

        } catch (\Exception $e) {
            // Send error response
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Get current Airtable settings
     */
    public function get_settings(): array {
        return get_option($this->option_name, [
            'base_id' => '',
            'sync_table' => 'Listings'
        ]);
    }
}

// Initialize the Airtable settings
Airtable_Settings::get_instance();