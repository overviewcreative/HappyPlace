<?php
namespace HappyPlace\Integrations;

class Airtable_Sync_Ajax_Handler {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    public function __construct() {
        add_action('wp_ajax_hph_two_way_airtable_sync', [$this, 'handle_two_way_sync']);
        // Disable admin menu registration - handled by unified Integrations Manager
        // add_action('admin_menu', [$this, 'add_sync_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_sync_scripts']);
    }

    /**
     * Add sync page to WordPress admin
     * DISABLED - Now handled by Integrations Manager
     */
    public function add_sync_page(): void {
        // Menu registration disabled - handled by unified integrations page
        /*
        add_menu_page(
            'Airtable Sync', 
            'Airtable Sync', 
            'manage_options', 
            'hph-airtable-sync', 
            [$this, 'render_sync_page'],
            'dashicons-database-import',
            30
        );
        */
    }

    /**
     * Render sync admin page
     */
    public function render_sync_page(): void {
        // Get Airtable configuration
        $options = get_option('happy_place_options', []);
        ?>
        <div class="wrap">
            <h1>Airtable Two-Way Sync</h1>
            
            <div id="hph-airtable-sync-container">
                <div class="sync-configuration">
                    <h2>Sync Configuration</h2>
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
                                        name="airtable_base_id" 
                                        value="<?php echo esc_attr($options['airtable_base_id'] ?? ''); ?>" 
                                        class="regular-text"
                                    >
                                    <p class="description">Enter the Airtable Base ID for synchronization</p>
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
                                        name="airtable_table_name" 
                                        value="Listings" 
                                        class="regular-text"
                                    >
                                    <p class="description">Enter the Airtable table name to sync</p>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <div class="sync-actions">
                    <button 
                        id="hph-trigger-two-way-sync" 
                        class="button button-primary"
                    >
                        Trigger Two-Way Sync
                    </button>
                </div>

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
     * Enqueue scripts for sync page
     */
    public function enqueue_sync_scripts($hook): void {
        if ($hook !== 'toplevel_page_hph-airtable-sync') {
            return;
        }

        wp_enqueue_script(
            'happy-place-two-way-sync', 
            plugin_dir_url(__FILE__) . '../assets/js/airtable-two-way-sync.js', 
            ['jquery'], 
            '1.0.0', 
            true
        );

        wp_localize_script('happy-place-two-way-sync', 'happyPlaceAirtable', [
            'nonce' => wp_create_nonce('happy_place_airtable_two_way_sync'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);

        // Inline styles for sync page
        wp_add_inline_style('admin-style', '
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
            .sync-status {
                margin-top: 15px;
                padding: 10px;
                border-radius: 5px;
            }
            .status-info { background-color: #f1f1f1; }
            .status-success { background-color: #dff0d8; color: #3c763d; }
            .status-error { background-color: #f2dede; color: #a94442; }
        ');
    }

    /**
     * AJAX handler for two-way sync
     */
    public function handle_two_way_sync(): void {
        // Verify nonce for security
        check_ajax_referer('happy_place_airtable_two_way_sync', 'nonce');

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions', 403);
        }

        try {
            // Get configuration from options
            $options = get_option('happy_place_options', []);
            $base_id = $options['airtable_base_id'] ?? null;
            $table_name = 'Listings'; // Hardcoded for this example

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
}

// Initialize the AJAX handler
Airtable_Sync_Ajax_Handler::get_instance();