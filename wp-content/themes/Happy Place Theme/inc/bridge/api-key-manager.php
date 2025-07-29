<?php
/**
 * API Key Manager for Bridge Functions
 * Centralized access to plugin-stored API keys
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_API_Key_Manager {
    private static $instance = null;
    private $api_settings = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_api_settings();
    }
    
    /**
     * Load API settings from plugin
     */
    private function load_api_settings() {
        // Try to get settings from plugin first
        $this->api_settings = get_option('hph_external_api_settings', []);
        
        // Fallback to individual options for backward compatibility
        if (empty($this->api_settings)) {
            $this->api_settings = [
                'google_maps_api_key' => get_option('hph_google_maps_api_key', ''),
                'walkscore_api_key' => get_option('hph_walkscore_api_key', ''),
                'google_places_enabled' => get_option('hph_google_places_enabled', true),
                'zillow_api_key' => get_option('hph_zillow_api_key', ''),
                'school_api_key' => get_option('hph_school_api_key', ''),
                'auto_populate_on_save' => get_option('hph_auto_populate_on_save', true),
                'cache_duration' => get_option('hph_cache_duration', 24)
            ];
        }
    }
    
    /**
     * Get Google Maps API key
     */
    public function get_google_maps_key() {
        return $this->get_api_key('google_maps_api_key');
    }
    
    /**
     * Get Walk Score API key
     */
    public function get_walkscore_key() {
        return $this->get_api_key('walkscore_api_key');
    }
    
    /**
     * Get Zillow API key
     */
    public function get_zillow_key() {
        return $this->get_api_key('zillow_api_key');
    }
    
    /**
     * Get School API key
     */
    public function get_school_key() {
        return $this->get_api_key('school_api_key');
    }
    
    /**
     * Get any API key by name
     */
    public function get_api_key($key_name) {
        return $this->api_settings[$key_name] ?? '';
    }
    
    /**
     * Check if an API key is configured
     */
    public function has_api_key($key_name) {
        return !empty($this->get_api_key($key_name));
    }
    
    /**
     * Check if Google Maps integration is available
     */
    public function is_google_maps_available() {
        return $this->has_api_key('google_maps_api_key');
    }
    
    /**
     * Check if Google Places is enabled
     */
    public function is_google_places_enabled() {
        return $this->has_api_key('google_maps_api_key') && 
               ($this->api_settings['google_places_enabled'] ?? true);
    }
    
    /**
     * Check if Walk Score is available
     */
    public function is_walkscore_available() {
        return $this->has_api_key('walkscore_api_key');
    }
    
    /**
     * Check if Zillow integration is available
     */
    public function is_zillow_available() {
        return $this->has_api_key('zillow_api_key');
    }
    
    /**
     * Check if school data integration is available
     */
    public function is_school_data_available() {
        return $this->has_api_key('school_api_key');
    }
    
    /**
     * Get API setting value
     */
    public function get_setting($setting_name, $default = null) {
        return $this->api_settings[$setting_name] ?? $default;
    }
    
    /**
     * Get cache duration setting
     */
    public function get_cache_duration($type = 'default') {
        $durations = [
            'coordinates' => 7 * 24 * 3600, // 7 days
            'places' => 24 * 3600,         // 24 hours
            'walkscore' => 7 * 24 * 3600,  // 7 days
            'schools' => 30 * 24 * 3600,   // 30 days
            'default' => 24 * 3600         // 24 hours
        ];
        
        $setting_duration = $this->get_setting('cache_duration', 24) * 3600;
        return $durations[$type] ?? $setting_duration;
    }
    
    /**
     * Check if auto-population is enabled
     */
    public function is_auto_populate_enabled() {
        return (bool) $this->get_setting('auto_populate_on_save', true);
    }
    
    /**
     * Refresh settings cache
     */
    public function refresh_settings() {
        $this->load_api_settings();
    }
    
    /**
     * Log API errors for debugging
     */
    public function log_api_error($service, $error_message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'HPH API Error [%s]: %s %s',
                $service,
                $error_message,
                !empty($context) ? '| Context: ' . json_encode($context) : ''
            ));
        }
    }
    
    /**
     * Get API usage statistics (for admin dashboard)
     */
    public function get_api_usage_stats() {
        return [
            'google_maps' => [
                'available' => $this->is_google_maps_available(),
                'places_enabled' => $this->is_google_places_enabled(),
                'last_used' => get_option('hph_google_maps_last_used', 'Never')
            ],
            'walkscore' => [
                'available' => $this->is_walkscore_available(),
                'last_used' => get_option('hph_walkscore_last_used', 'Never')
            ],
            'zillow' => [
                'available' => $this->is_zillow_available(),
                'last_used' => get_option('hph_zillow_last_used', 'Never')
            ],
            'schools' => [
                'available' => $this->is_school_data_available(),
                'last_used' => get_option('hph_schools_last_used', 'Never')
            ]
        ];
    }
}
