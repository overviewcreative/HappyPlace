<?php
/**
 * Centralized Configuration Manager for Happy Place Plugin
 * 
 * Single source of truth for all plugin settings and configurations
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Config_Manager {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Configuration cache
     */
    private array $config_cache = [];
    
    /**
     * Cache expiration time (in seconds)
     */
    private int $cache_expiration = 3600; // 1 hour
    
    /**
     * Environment config handler
     */
    private Environment_Config $env_config;
    
    /**
     * Default configurations
     */
    private array $defaults = [];
    
    /**
     * Configuration groups
     */
    private array $config_groups = [
        'general',
        'display', 
        'integrations',
        'email',
        'performance',
        'api',
        'security',
        'advanced'
    ];
    
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
        $this->env_config = Environment_Config::get_instance();
        $this->init_defaults();
        $this->init_hooks();
        $this->apply_environment_overrides();
    }
    
    /**
     * Initialize default configurations
     */
    private function init_defaults(): void {
        $this->defaults = [
            'general' => [
                'plugin_enabled' => true,
                'debug_mode' => false,
                'auto_cleanup' => false,
                'version' => '1.0.0'
            ],
            'display' => [
                'listings_per_page' => 12,
                'default_sort' => 'date_desc',
                'default_map_zoom' => 13,
                'map_provider' => 'google',
                'show_map' => true,
                'default_view' => 'grid'
            ],
            'integrations' => [
                'airtable_enabled' => false,
                'airtable_api_key' => '',
                'airtable_base_id' => '',
                'airtable_table_name' => 'Listings',
                'airtable_sync_direction' => 'bidirectional',
                'airtable_auto_sync' => false,
                'airtable_sync_frequency' => 'hourly',
                'mls_provider' => '',
                'mls_enabled' => false
            ],
            'email' => [
                'notifications_enabled' => true,
                'admin_email' => '',
                'from_name' => '',
                'new_inquiry' => true,
                'new_review' => true,
                'listing_update' => true,
                'daily_summary' => false,
                'weekly_report' => true
            ],
            'performance' => [
                'caching_enabled' => true,
                'cache_duration' => 24,
                'cache_strategy' => 'balanced',
                'lazy_loading' => true,
                'minify_assets' => false,
                'cdn_enabled' => false,
                'cdn_url' => ''
            ],
            'api' => [
                'google_maps_api_key' => '',
                'google_places_enabled' => true,
                'walkscore_api_key' => '',
                'school_api_enabled' => true,
                'property_tax_enabled' => true,
                'auto_populate_on_save' => true,
                'rate_limiting_enabled' => true,
                'cache_duration' => 24
            ],
            'security' => [
                'api_rate_limiting' => true,
                'secure_uploads' => true,
                'sanitize_inputs' => true,
                'validate_api_keys' => true,
                'log_api_requests' => false
            ],
            'advanced' => [
                'custom_css' => '',
                'custom_js' => '',
                'database_optimization' => false,
                'error_logging' => true,
                'performance_monitoring' => false
            ]
        ];
    }
    
    /**
     * Apply environment-specific overrides
     */
    private function apply_environment_overrides(): void {
        $this->env_config->apply_env_overrides($this);
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        \add_action('init', [$this, 'migrate_legacy_settings'], 5);
        \add_action('wp_ajax_hph_get_config', [$this, 'ajax_get_config']);
        \add_action('wp_ajax_hph_update_config', [$this, 'ajax_update_config']);
        \add_action('wp_ajax_hph_reset_config', [$this, 'ajax_reset_config']);
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key, $default = null) {
        // Parse dot notation (e.g., 'general.plugin_enabled')
        $keys = explode('.', $key);
        $group = $keys[0];
        $setting = $keys[1] ?? null;
        
        // Load group configuration if not cached
        if (!isset($this->config_cache[$group])) {
            $this->load_group_config($group);
        }
        
        // Return specific setting or entire group
        if ($setting) {
            return $this->config_cache[$group][$setting] ?? $default ?? $this->get_default($key);
        }
        
        return $this->config_cache[$group] ?? $default ?? $this->get_default($key);
    }
    
    /**
     * Set configuration value
     */
    public function set(string $key, $value): bool {
        $keys = explode('.', $key);
        $group = $keys[0];
        $setting = $keys[1] ?? null;
        
        if (!$setting) {
            return false;
        }
        
        // Load group if not cached
        if (!isset($this->config_cache[$group])) {
            $this->load_group_config($group);
        }
        
        // Update cache
        $this->config_cache[$group][$setting] = $value;
        
        // Save to database
        return $this->save_group_config($group);
    }
    
    /**
     * Get default value
     */
    public function get_default(string $key) {
        $keys = explode('.', $key);
        $group = $keys[0];
        $setting = $keys[1] ?? null;
        
        if ($setting) {
            return $this->defaults[$group][$setting] ?? null;
        }
        
        return $this->defaults[$group] ?? null;
    }
    
    /**
     * Get entire configuration group
     */
    public function get_group(string $group): array {
        if (!isset($this->config_cache[$group])) {
            $this->load_group_config($group);
        }
        
        return $this->config_cache[$group] ?? [];
    }
    
    /**
     * Update entire configuration group
     */
    public function set_group(string $group, array $config): bool {
        if (!in_array($group, $this->config_groups)) {
            return false;
        }
        
        // Merge with defaults to ensure all keys exist
        $this->config_cache[$group] = wp_parse_args($config, $this->defaults[$group] ?? []);
        
        return $this->save_group_config($group);
    }
    
    /**
     * Reset configuration group to defaults
     */
    public function reset_group(string $group): bool {
        if (!in_array($group, $this->config_groups)) {
            return false;
        }
        
        $this->config_cache[$group] = $this->defaults[$group] ?? [];
        return $this->save_group_config($group);
    }
    
    /**
     * Load group configuration from database
     */
    private function load_group_config(string $group): void {
        // Try to get from object cache first
        $cache_key = "hph_config_{$group}";
        $cached_config = \wp_cache_get($cache_key, 'hph_config');
        
        if ($cached_config !== false) {
            $this->config_cache[$group] = $cached_config;
            return;
        }
        
        // Get from database
        $option_name = "hph_config_{$group}";
        $config = \get_option($option_name, []);
        
        // Merge with defaults
        $this->config_cache[$group] = \wp_parse_args($config, $this->defaults[$group] ?? []);
        
        // Cache the result
        \wp_cache_set($cache_key, $this->config_cache[$group], 'hph_config', $this->cache_expiration);
    }
    
    /**
     * Save group configuration to database
     */
    private function save_group_config(string $group): bool {
        $option_name = "hph_config_{$group}";
        $result = \update_option($option_name, $this->config_cache[$group]);
        
        if ($result) {
            // Update cache
            $cache_key = "hph_config_{$group}";
            \wp_cache_set($cache_key, $this->config_cache[$group], 'hph_config', $this->cache_expiration);
            
            // Clear any related transients
            $this->clear_config_transients($group);
        }
        
        return $result;
    }
    
    /**
     * Clear configuration caches
     */
    public function clear_config_cache(string $group = ''): void {
        if ($group) {
            // Clear specific group cache
            \wp_cache_delete("hph_config_{$group}", 'hph_config');
            unset($this->config_cache[$group]);
            $this->clear_config_transients($group);
        } else {
            // Clear all config caches
            foreach ($this->config_groups as $config_group) {
                \wp_cache_delete("hph_config_{$config_group}", 'hph_config');
                unset($this->config_cache[$config_group]);
                $this->clear_config_transients($config_group);
            }
        }
    }
    
    /**
     * Clear configuration-related transients
     */
    private function clear_config_transients(string $group): void {
        // Clear related transients based on group
        $transients_to_clear = [
            'api' => ['hph_api_rates', 'hph_api_status'],
            'performance' => ['hph_performance_stats', 'hph_cache_stats'],
            'integrations' => ['hph_integration_status', 'hph_airtable_connection']
        ];
        
        if (isset($transients_to_clear[$group])) {
            foreach ($transients_to_clear[$group] as $transient) {
                delete_transient($transient);
            }
        }
    }
    
    /**
     * Get all configuration groups
     */
    public function get_all_config(): array {
        $all_config = [];
        
        foreach ($this->config_groups as $group) {
            $all_config[$group] = $this->get_group($group);
        }
        
        return $all_config;
    }
    
    /**
     * Validate configuration value
     */
    public function validate(string $key, $value): bool {
        $keys = explode('.', $key);
        $group = $keys[0];
        $setting = $keys[1] ?? null;
        
        if (!$setting) {
            return false;
        }
        
        // Enhanced validation rules
        $validation_rules = [
            'general.plugin_enabled' => ['type' => 'boolean'],
            'general.debug_mode' => ['type' => 'boolean'],
            'display.listings_per_page' => ['type' => 'integer', 'min' => 1, 'max' => 100],
            'display.default_map_zoom' => ['type' => 'integer', 'min' => 1, 'max' => 20],
            'api.cache_duration' => ['type' => 'integer', 'min' => 1, 'max' => 168],
            'email.admin_email' => ['type' => 'email'],
            'api.google_maps_api_key' => ['type' => 'api_key', 'pattern' => '/^[A-Za-z0-9_-]+$/'],
            'api.walkscore_api_key' => ['type' => 'api_key', 'pattern' => '/^[A-Za-z0-9_-]+$/'],
            'integrations.airtable_api_key' => ['type' => 'api_key', 'pattern' => '/^key[A-Za-z0-9]+$/'],
            'integrations.airtable_base_id' => ['type' => 'string', 'pattern' => '/^app[A-Za-z0-9]+$/'],
            'performance.cache_strategy' => ['type' => 'enum', 'values' => ['minimal', 'balanced', 'aggressive']],
            'display.map_provider' => ['type' => 'enum', 'values' => ['google', 'openstreet', 'mapbox']],
        ];
        
        $rule = $validation_rules[$key] ?? null;
        
        if (!$rule) {
            return true; // No specific validation rule
        }
        
        return $this->apply_validation_rule($rule, $value);
    }
    
    /**
     * Apply validation rule
     */
    private function apply_validation_rule(array $rule, $value): bool {
        $type = $rule['type'] ?? 'string';
        
        switch ($type) {
            case 'boolean':
                return is_bool($value) || in_array($value, [0, 1, '0', '1']);
                
            case 'integer':
                if (!is_numeric($value)) {
                    return false;
                }
                $num_value = (int) $value;
                if (isset($rule['min']) && $num_value < $rule['min']) {
                    return false;
                }
                if (isset($rule['max']) && $num_value > $rule['max']) {
                    return false;
                }
                return true;
                
            case 'email':
                return is_email($value);
                
            case 'api_key':
                if (empty($value)) {
                    return true; // Allow empty API keys
                }
                if (isset($rule['pattern'])) {
                    return preg_match($rule['pattern'], $value) === 1;
                }
                return is_string($value) && strlen($value) > 0;
                
            case 'enum':
                return isset($rule['values']) && in_array($value, $rule['values']);
                
            case 'string':
                if (isset($rule['pattern'])) {
                    return preg_match($rule['pattern'], $value) === 1;
                }
                return is_string($value);
                
            default:
                return true;
        }
    }
    
    /**
     * Migrate legacy settings to new config structure
     */
    public function migrate_legacy_settings(): void {
        if (\get_option('hph_config_migration_completed')) {
            return;
        }
        
        // Legacy option mappings
        $legacy_mappings = [
            'hph_plugin_enabled' => 'general.plugin_enabled',
            'hph_debug_mode' => 'general.debug_mode',
            'hph_listings_per_page' => 'display.listings_per_page',
            'hph_default_sort' => 'display.default_sort',
            'hph_default_map_zoom' => 'display.default_map_zoom',
            'hph_map_provider' => 'display.map_provider',
            'hph_airtable_token' => 'integrations.airtable_api_key',
            'hph_airtable_base' => 'integrations.airtable_base_id',
            'hph_email_notifications' => 'email.notifications_enabled',
            'hph_admin_email' => 'email.admin_email',
            'hph_google_maps_api_key' => 'api.google_maps_api_key',
            'hph_enable_caching' => 'performance.caching_enabled',
            'hph_cache_duration' => 'performance.cache_duration'
        ];
        
        foreach ($legacy_mappings as $legacy_key => $new_key) {
            $legacy_value = \get_option($legacy_key);
            if ($legacy_value !== false) {
                $this->set($new_key, $legacy_value);
            }
        }
        
        \update_option('hph_config_migration_completed', true);
    }
    
    /**
     * AJAX: Get configuration
     */
    public function ajax_get_config(): void {
        check_ajax_referer('hph_config_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error('Insufficient permissions');
        }
        
        $group = sanitize_text_field($_POST['group'] ?? '');
        
        if ($group && in_array($group, $this->config_groups)) {
            \wp_send_json_success($this->get_group($group));
        } else {
            \wp_send_json_success($this->get_all_config());
        }
    }
    
    /**
     * AJAX: Update configuration
     */
    public function ajax_update_config(): void {
        check_ajax_referer('hph_config_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error('Insufficient permissions');
        }
        
        $group = sanitize_text_field($_POST['group'] ?? '');
        $config = $_POST['config'] ?? [];
        
        if (!$group || !in_array($group, $this->config_groups)) {
            \wp_send_json_error('Invalid configuration group');
        }
        
        // Validate and sanitize config
        $sanitized_config = [];
        foreach ($config as $key => $value) {
            $full_key = "{$group}.{$key}";
            if ($this->validate($full_key, $value)) {
                $sanitized_config[$key] = $this->sanitize_value($full_key, $value);
            }
        }
        
        if ($this->set_group($group, $sanitized_config)) {
            \wp_send_json_success(['message' => 'Configuration updated successfully']);
        } else {
            \wp_send_json_error('Failed to update configuration');
        }
    }
    
    /**
     * AJAX: Reset configuration
     */
    public function ajax_reset_config(): void {
        check_ajax_referer('hph_config_nonce', 'nonce');
        
        if (!\current_user_can('manage_options')) {
            \wp_send_json_error('Insufficient permissions');
        }
        
        $group = sanitize_text_field($_POST['group'] ?? '');
        
        if (!$group || !in_array($group, $this->config_groups)) {
            \wp_send_json_error('Invalid configuration group');
        }
        
        if ($this->reset_group($group)) {
            \wp_send_json_success(['message' => 'Configuration reset to defaults']);
        } else {
            \wp_send_json_error('Failed to reset configuration');
        }
    }
    
    /**
     * Sanitize configuration value
     */
    private function sanitize_value(string $key, $value) {
        $keys = explode('.', $key);
        $setting = $keys[1] ?? null;
        
        // Define sanitization rules
        switch ($setting) {
            case 'admin_email':
            case 'from_email':
                return sanitize_email($value);
                
            case 'api_key':
            case 'airtable_api_key':
            case 'google_maps_api_key':
            case 'walkscore_api_key':
                return sanitize_text_field($value);
                
            case 'custom_css':
                return wp_strip_all_tags($value);
                
            case 'custom_js':
                return wp_kses($value, []);
                
            default:
                if (is_bool($value) || in_array($value, [0, 1, '0', '1'])) {
                    return (bool) $value;
                }
                
                if (is_numeric($value)) {
                    return is_float($value) ? (float) $value : (int) $value;
                }
                
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Export configuration
     */
    public function export_config(): array {
        return [
            'version' => '1.0.0',
            'timestamp' => time(),
            'config' => $this->get_all_config()
        ];
    }
    
    /**
     * Import configuration
     */
    public function import_config(array $import_data): bool {
        if (!isset($import_data['config']) || !is_array($import_data['config'])) {
            return false;
        }
        
        foreach ($import_data['config'] as $group => $config) {
            if (in_array($group, $this->config_groups)) {
                $this->set_group($group, $config);
            }
        }
        
        return true;
    }
}
