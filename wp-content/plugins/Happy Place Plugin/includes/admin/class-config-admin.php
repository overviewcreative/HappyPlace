<?php
/**
 * Configuration Management Admin Page
 * 
 * Provides a unified interface for managing all plugin configurations
 *
 * @package HappyPlace
 * @subpackage Admin
 */

namespace HappyPlace\Admin;

if (!defined('ABSPATH')) {
    exit;
}

use HappyPlace\Core\Config_Manager;
use HappyPlace\Core\Environment_Config;

class Config_Admin {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Config Manager
     */
    private Config_Manager $config_manager;
    
    /**
     * Environment Config
     */
    private Environment_Config $env_config;
    
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
        $this->config_manager = Config_Manager::get_instance();
        $this->env_config = Environment_Config::get_instance();
        
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_submenu_page(
            'happy-place-admin',
            __('Configuration Manager', 'happy-place'),
            __('Config Manager', 'happy-place'),
            'manage_options',
            'hph-config-manager',
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook): void {
        if (strpos($hook, 'hph-config-manager') === false) {
            return;
        }
        
        wp_enqueue_script('hph-config-admin', plugins_url('assets/js/config-admin.js', HPH_PLUGIN_FILE), ['jquery'], '1.0.0', true);
        wp_localize_script('hph-config-admin', 'hphConfigAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_config_nonce'),
            'strings' => [
                'confirmReset' => __('Are you sure you want to reset this configuration group? This cannot be undone.', 'happy-place'),
                'saved' => __('Configuration saved successfully!', 'happy-place'),
                'error' => __('Error saving configuration. Please try again.', 'happy-place')
            ]
        ]);
        
        wp_enqueue_style('hph-config-admin', plugins_url('assets/css/config-admin.css', HPH_PLUGIN_FILE), [], '1.0.0');
    }
    
    /**
     * Render admin page
     */
    public function render_page(): void {
        $current_tab = $_GET['tab'] ?? 'general';
        $all_config = $this->config_manager->get_all_config();
        $environment = $this->env_config->get_environment();
        $recommendations = $this->env_config->get_recommended_settings();
        
        ?>
        <div class="wrap hph-config-manager">
            <h1><?php echo esc_html__('Configuration Manager', 'happy-place'); ?></h1>
            
            <!-- Environment Status -->
            <div class="notice notice-info">
                <p>
                    <strong><?php echo esc_html__('Current Environment:', 'happy-place'); ?></strong> 
                    <span class="env-badge env-<?php echo esc_attr($environment); ?>"><?php echo esc_html(ucfirst($environment)); ?></span>
                </p>
                <?php if (!empty($recommendations)): ?>
                    <details>
                        <summary><?php echo esc_html__('Environment Recommendations', 'happy-place'); ?></summary>
                        <ul>
                            <?php foreach ($recommendations as $recommendation): ?>
                                <li><?php echo esc_html($recommendation); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
            
            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper">
                <?php foreach ($all_config as $group => $config): ?>
                    <a href="?page=hph-config-manager&tab=<?php echo esc_attr($group); ?>" 
                       class="nav-tab <?php echo $current_tab === $group ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $group))); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <?php if (isset($all_config[$current_tab])): ?>
                    <form id="config-form" data-group="<?php echo esc_attr($current_tab); ?>">
                        <?php wp_nonce_field('hph_config_nonce', 'nonce'); ?>
                        
                        <table class="form-table">
                            <?php foreach ($all_config[$current_tab] as $setting => $value): ?>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo esc_attr($setting); ?>">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $setting))); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <?php $this->render_setting_field($current_tab, $setting, $value); ?>
                                        <p class="description">
                                            <?php echo esc_html($this->get_setting_description($current_tab, $setting)); ?>
                                        </p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button-primary">
                                <?php echo esc_html__('Save Configuration', 'happy-place'); ?>
                            </button>
                            <button type="button" class="button-secondary" id="reset-config" data-group="<?php echo esc_attr($current_tab); ?>">
                                <?php echo esc_html__('Reset to Defaults', 'happy-place'); ?>
                            </button>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
        .hph-config-manager .env-badge {
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .env-development { background: #fff3cd; color: #856404; }
        .env-staging { background: #d1ecf1; color: #0c5460; }
        .env-production { background: #d4edda; color: #155724; }
        
        .tab-content {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-top: none;
        }
        
        .form-table th {
            width: 200px;
        }
        </style>
        <?php
    }
    
    /**
     * Render setting field
     */
    private function render_setting_field(string $group, string $setting, $value): void {
        $field_id = "{$group}_{$setting}";
        $field_name = "config[{$setting}]";
        
        // Determine field type based on setting and value
        $field_type = $this->get_field_type($group, $setting, $value);
        
        switch ($field_type) {
            case 'boolean':
                ?>
                <label>
                    <input type="checkbox" id="<?php echo esc_attr($field_id); ?>" 
                           name="<?php echo esc_attr($field_name); ?>" 
                           value="1" <?php checked($value); ?>>
                    <?php echo esc_html__('Enable', 'happy-place'); ?>
                </label>
                <?php
                break;
                
            case 'select':
                $options = $this->get_select_options($group, $setting);
                ?>
                <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($field_name); ?>">
                    <?php foreach ($options as $option_value => $option_label): ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                            <?php echo esc_html($option_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;
                
            case 'number':
                ?>
                <input type="number" id="<?php echo esc_attr($field_id); ?>" 
                       name="<?php echo esc_attr($field_name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       class="small-text">
                <?php
                break;
                
            case 'textarea':
                ?>
                <textarea id="<?php echo esc_attr($field_id); ?>" 
                          name="<?php echo esc_attr($field_name); ?>" 
                          rows="4" cols="50"><?php echo esc_textarea($value); ?></textarea>
                <?php
                break;
                
            default:
                ?>
                <input type="text" id="<?php echo esc_attr($field_id); ?>" 
                       name="<?php echo esc_attr($field_name); ?>" 
                       value="<?php echo esc_attr($value); ?>" 
                       class="regular-text">
                <?php
                break;
        }
    }
    
    /**
     * Get field type
     */
    private function get_field_type(string $group, string $setting, $value): string {
        // Boolean fields
        if (is_bool($value) || in_array($setting, ['enabled', 'debug_mode', 'auto_cleanup'])) {
            return 'boolean';
        }
        
        // Select fields
        $select_fields = [
            'cache_strategy' => ['minimal', 'balanced', 'aggressive'],
            'map_provider' => ['google', 'openstreet', 'mapbox'],
            'sync_frequency' => ['hourly', 'daily', 'weekly']
        ];
        
        if (isset($select_fields[$setting])) {
            return 'select';
        }
        
        // Number fields
        if (is_numeric($value) || in_array($setting, ['per_page', 'zoom', 'duration'])) {
            return 'number';
        }
        
        // Textarea fields
        if (in_array($setting, ['custom_css', 'custom_js'])) {
            return 'textarea';
        }
        
        return 'text';
    }
    
    /**
     * Get select options
     */
    private function get_select_options(string $group, string $setting): array {
        $options = [
            'cache_strategy' => [
                'minimal' => __('Minimal', 'happy-place'),
                'balanced' => __('Balanced', 'happy-place'),
                'aggressive' => __('Aggressive', 'happy-place')
            ],
            'map_provider' => [
                'google' => __('Google Maps', 'happy-place'),
                'openstreet' => __('OpenStreetMap', 'happy-place'),
                'mapbox' => __('Mapbox', 'happy-place')
            ],
            'sync_frequency' => [
                'hourly' => __('Hourly', 'happy-place'),
                'daily' => __('Daily', 'happy-place'),
                'weekly' => __('Weekly', 'happy-place')
            ]
        ];
        
        return $options[$setting] ?? [];
    }
    
    /**
     * Get setting description
     */
    private function get_setting_description(string $group, string $setting): string {
        $descriptions = [
            'general.plugin_enabled' => __('Enable or disable the Happy Place plugin functionality.', 'happy-place'),
            'general.debug_mode' => __('Enable debug mode for troubleshooting. Only use in development.', 'happy-place'),
            'performance.caching_enabled' => __('Enable caching to improve site performance.', 'happy-place'),
            'performance.cache_strategy' => __('Choose the caching strategy based on your needs.', 'happy-place'),
            'api.google_maps_api_key' => __('Your Google Maps API key for location services.', 'happy-place'),
            'integrations.airtable_api_key' => __('Your Airtable API key for data synchronization.', 'happy-place'),
        ];
        
        $key = "{$group}.{$setting}";
        return $descriptions[$key] ?? __('Configuration setting for the Happy Place plugin.', 'happy-place');
    }
}
