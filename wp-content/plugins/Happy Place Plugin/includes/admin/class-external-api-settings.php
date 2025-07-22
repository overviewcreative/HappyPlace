<?php
/**
 * External API Settings Page
 *
 * Admin interface for configuring external API keys and settings
 *
 * @package HappyPlace
 * @subpackage Admin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class External_API_Settings {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Option name for API settings
     */
    private string $option_name = 'hph_external_api_settings';
    
    /**
     * Get instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu'], 20); // Later priority to ensure parent menu exists
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_hph_test_api_connection', [$this, 'test_api_connection']);
        
        // Debug: Add admin notice to confirm class is loading
        add_action('admin_notices', function() {
            if (current_user_can('manage_options') && isset($_GET['page']) && $_GET['page'] === 'happy-place') {
                echo '<div class="notice notice-info"><p>External API Settings class is loaded and ready!</p></div>';
            }
        });
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'happy-place',
            __('External APIs', 'happy-place'),
            __('External APIs', 'happy-place'),
            'manage_options',
            'hph-external-apis',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings(): void {
        register_setting(
            'hph_external_api_settings',
            $this->option_name,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => $this->get_default_settings()
            ]
        );
        
        // Add settings sections
        add_settings_section(
            'google_apis',
            __('Google APIs', 'happy-place'),
            [$this, 'render_google_apis_section'],
            'hph_external_api_settings'
        );
        
        add_settings_section(
            'walkability_apis',
            __('Walkability & Transit APIs', 'happy-place'),
            [$this, 'render_walkability_apis_section'],
            'hph_external_api_settings'
        );
        
        add_settings_section(
            'location_data_apis',
            __('Location Data APIs', 'happy-place'),
            [$this, 'render_location_data_section'],
            'hph_external_api_settings'
        );
        
        add_settings_section(
            'auto_population_settings',
            __('Auto-Population Settings', 'happy-place'),
            [$this, 'render_auto_population_section'],
            'hph_external_api_settings'
        );
        
        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields(): void {
        // Google APIs
        add_settings_field(
            'google_maps_api_key',
            __('Google Maps API Key', 'happy-place'),
            [$this, 'render_api_key_field'],
            'hph_external_api_settings',
            'google_apis',
            [
                'field_id' => 'google_maps_api_key',
                'description' => __('Required for geocoding, Places API, and map functionality.', 'happy-place'),
                'test_endpoint' => 'google_maps'
            ]
        );
        
        add_settings_field(
            'google_places_enabled',
            __('Google Places API', 'happy-place'),
            [$this, 'render_checkbox_field'],
            'hph_external_api_settings',
            'google_apis',
            [
                'field_id' => 'google_places_enabled',
                'description' => __('Enable Google Places API for nearby amenities auto-population.', 'happy-place')
            ]
        );
        
        // Walk Score API
        add_settings_field(
            'walkscore_api_key',
            __('Walk Score API Key', 'happy-place'),
            [$this, 'render_api_key_field'],
            'hph_external_api_settings',
            'walkability_apis',
            [
                'field_id' => 'walkscore_api_key',
                'description' => __('Optional: Get accurate Walk Score, Transit Score, and Bike Score data. <a href="https://www.walkscore.com/professional/api.php" target="_blank">Get API Key</a>', 'happy-place'),
                'test_endpoint' => 'walkscore'
            ]
        );
        
        // School District APIs
        add_settings_field(
            'school_api_enabled',
            __('School District Data', 'happy-place'),
            [$this, 'render_checkbox_field'],
            'hph_external_api_settings',
            'location_data_apis',
            [
                'field_id' => 'school_api_enabled',
                'description' => __('Enable school district and school information auto-population.', 'happy-place')
            ]
        );
        
        // Property Tax APIs
        add_settings_field(
            'property_tax_enabled',
            __('Property Tax Data', 'happy-place'),
            [$this, 'render_checkbox_field'],
            'hph_external_api_settings',
            'location_data_apis',
            [
                'field_id' => 'property_tax_enabled',
                'description' => __('Enable automatic property tax estimation based on location and price.', 'happy-place')
            ]
        );
        
        // Auto-population settings
        add_settings_field(
            'auto_populate_on_save',
            __('Auto-populate on Save', 'happy-place'),
            [$this, 'render_checkbox_field'],
            'hph_external_api_settings',
            'auto_population_settings',
            [
                'field_id' => 'auto_populate_on_save',
                'description' => __('Automatically populate location intelligence data when listings are saved.', 'happy-place')
            ]
        );
        
        add_settings_field(
            'cache_duration',
            __('Cache Duration (hours)', 'happy-place'),
            [$this, 'render_number_field'],
            'hph_external_api_settings',
            'auto_population_settings',
            [
                'field_id' => 'cache_duration',
                'description' => __('How long to cache API responses to reduce API calls.', 'happy-place'),
                'min' => 1,
                'max' => 168
            ]
        );
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings(): array {
        return [
            'google_maps_api_key' => get_option('hph_google_maps_api_key', ''),
            'google_places_enabled' => true,
            'walkscore_api_key' => '',
            'school_api_enabled' => true,
            'property_tax_enabled' => true,
            'auto_populate_on_save' => true,
            'cache_duration' => 24,
        ];
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input): array {
        $sanitized = [];
        
        $sanitized['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key'] ?? '');
        $sanitized['google_places_enabled'] = !empty($input['google_places_enabled']);
        $sanitized['walkscore_api_key'] = sanitize_text_field($input['walkscore_api_key'] ?? '');
        $sanitized['school_api_enabled'] = !empty($input['school_api_enabled']);
        $sanitized['property_tax_enabled'] = !empty($input['property_tax_enabled']);
        $sanitized['auto_populate_on_save'] = !empty($input['auto_populate_on_save']);
        $sanitized['cache_duration'] = max(1, min(168, intval($input['cache_duration'] ?? 24)));
        
        // Update individual option values for backwards compatibility
        update_option('hph_google_maps_api_key', $sanitized['google_maps_api_key']);
        update_option('hph_walkscore_api_key', $sanitized['walkscore_api_key']);
        
        return $sanitized;
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        ?>
        <div class="wrap">
            <h1><?php _e('External API Settings', 'happy-place'); ?></h1>
            <p><?php _e('Configure external API integrations for auto-populating location intelligence data.', 'happy-place'); ?></p>
            
            <form method="post" action="options.php" id="hph-external-api-form">
                <?php
                settings_fields('hph_external_api_settings');
                do_settings_sections('hph_external_api_settings');
                submit_button();
                ?>
            </form>
            
            <div id="hph-api-test-results" style="margin-top: 20px;"></div>
        </div>
        
        <style>
        .hph-api-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin: 20px 0;
            padding: 20px;
        }
        
        .hph-api-key-field {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .hph-api-key-field input[type="text"],
        .hph-api-key-field input[type="password"] {
            min-width: 400px;
        }
        
        .hph-test-api-btn {
            background: #2271b1;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .hph-test-api-btn:hover {
            background: #135e96;
        }
        
        .hph-test-result {
            margin-left: 10px;
            padding: 5px 10px;
            border-radius: 3px;
        }
        
        .hph-test-success {
            background: #d1edff;
            color: #0073aa;
            border: 1px solid #0073aa;
        }
        
        .hph-test-error {
            background: #fbeaea;
            color: #d63638;
            border: 1px solid #d63638;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.hph-test-api-btn').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const endpoint = button.data('endpoint');
                const resultDiv = button.siblings('.hph-test-result');
                
                button.prop('disabled', true).text('Testing...');
                resultDiv.remove();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'hph_test_api_connection',
                        endpoint: endpoint,
                        nonce: '<?php echo wp_create_nonce('hph_test_api'); ?>',
                        api_key: button.siblings('input').val()
                    },
                    success: function(response) {
                        const resultClass = response.success ? 'hph-test-success' : 'hph-test-error';
                        button.after('<span class="hph-test-result ' + resultClass + '">' + response.data + '</span>');
                    },
                    error: function() {
                        button.after('<span class="hph-test-result hph-test-error">Test failed - please try again</span>');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test Connection');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render Google APIs section
     */
    public function render_google_apis_section(): void {
        echo '<div class="hph-api-section">';
        echo '<p>' . __('Configure Google APIs for geocoding, places data, and mapping functionality.', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render walkability APIs section
     */
    public function render_walkability_apis_section(): void {
        echo '<div class="hph-api-section">';
        echo '<p>' . __('Optional APIs for enhanced walkability and transit scoring.', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render location data section
     */
    public function render_location_data_section(): void {
        echo '<div class="hph-api-section">';
        echo '<p>' . __('Configure location-based data sources for schools, taxes, and demographics.', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render auto-population section
     */
    public function render_auto_population_section(): void {
        echo '<div class="hph-api-section">';
        echo '<p>' . __('Control when and how location intelligence data is automatically populated.', 'happy-place') . '</p>';
        echo '</div>';
    }
    
    /**
     * Render API key field
     */
    public function render_api_key_field($args): void {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = $settings[$args['field_id']] ?? '';
        $field_name = $this->option_name . '[' . $args['field_id'] . ']';
        
        echo '<div class="hph-api-key-field">';
        echo '<input type="password" id="' . $args['field_id'] . '" name="' . $field_name . '" value="' . esc_attr($value) . '" style="min-width: 400px;" />';
        
        if (isset($args['test_endpoint'])) {
            echo '<button type="button" class="hph-test-api-btn" data-endpoint="' . $args['test_endpoint'] . '">Test Connection</button>';
        }
        
        echo '</div>';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args): void {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = $settings[$args['field_id']] ?? false;
        $field_name = $this->option_name . '[' . $args['field_id'] . ']';
        
        echo '<label>';
        echo '<input type="checkbox" id="' . $args['field_id'] . '" name="' . $field_name . '" value="1" ' . checked(1, $value, false) . ' />';
        echo ' ' . ($args['description'] ?? '');
        echo '</label>';
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args): void {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = $settings[$args['field_id']] ?? 24;
        $field_name = $this->option_name . '[' . $args['field_id'] . ']';
        
        echo '<input type="number" id="' . $args['field_id'] . '" name="' . $field_name . '" value="' . esc_attr($value) . '"';
        
        if (isset($args['min'])) {
            echo ' min="' . $args['min'] . '"';
        }
        
        if (isset($args['max'])) {
            echo ' max="' . $args['max'] . '"';
        }
        
        echo ' />';
        
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection(): void {
        check_ajax_referer('hph_test_api', 'nonce');
        
        $endpoint = sanitize_text_field($_POST['endpoint']);
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error('API key is required');
        }
        
        switch ($endpoint) {
            case 'google_maps':
                $result = $this->test_google_maps_api($api_key);
                break;
                
            case 'walkscore':
                $result = $this->test_walkscore_api($api_key);
                break;
                
            default:
                wp_send_json_error('Unknown API endpoint');
        }
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Test Google Maps API
     */
    private function test_google_maps_api($api_key): array {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&key={$api_key}";
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'OK') {
            return [
                'success' => true,
                'message' => 'Google Maps API connection successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API Error: ' . ($data['error_message'] ?? $data['status'])
            ];
        }
    }
    
    /**
     * Test Walk Score API
     */
    private function test_walkscore_api($api_key): array {
        $url = "https://api.walkscore.com/score?format=json&lat=47.6085&lon=-122.3295&wsapikey={$api_key}";
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            ];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['walkscore']) && is_numeric($data['walkscore'])) {
            return [
                'success' => true,
                'message' => 'Walk Score API connection successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'API Error: Invalid response or API key'
            ];
        }
    }
}

// Initialize the settings page
External_API_Settings::get_instance();
