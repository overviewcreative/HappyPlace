<?php
/**
 * City API Integration Service
 *
 * Consolidates Google API functionality for city data management
 * including Places API auto-population and geocoding
 *
 * @package HappyPlace
 * @subpackage Services
 */

namespace HappyPlace\Services;

if (!defined('ABSPATH')) {
    exit;
}

class City_API_Integration {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Google Maps API Key
     */
    private string $google_api_key;
    
    /**
     * Cache duration in seconds (24 hours)
     */
    private int $cache_duration = 86400;
    
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
        $this->google_api_key = get_option('hph_google_maps_api_key', '');
        
        // Hook into city save to auto-populate places data
        add_action('acf/save_post', [$this, 'auto_populate_city_data'], 25);
        
        // Add AJAX handlers for manual refresh
        add_action('wp_ajax_hph_refresh_city_places', [$this, 'ajax_refresh_city_places']);
        add_action('wp_ajax_hph_geocode_city', [$this, 'ajax_geocode_city']);
        
        // Add admin enhancements for city editing
        add_action('admin_enqueue_scripts', [$this, 'enqueue_city_admin_scripts']);
    }
    
    /**
     * Auto-populate city data when a city is saved
     */
    public function auto_populate_city_data($post_id): void {
        // Only process cities
        if (get_post_type($post_id) !== 'city') {
            return;
        }
        
        // Skip during autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Get Google Map data
        $map_data = get_field('city_google_map', $post_id);
        
        if (empty($map_data) || empty($map_data['lat']) || empty($map_data['lng'])) {
            // Try to geocode from city name and location
            $this->auto_geocode_city($post_id);
            $map_data = get_field('city_google_map', $post_id);
        }
        
        if (!empty($map_data['lat']) && !empty($map_data['lng'])) {
            $lat = (float)$map_data['lat'];
            $lng = (float)$map_data['lng'];
            
            // Auto-populate places if API source is selected
            $places_source = get_field('places_source', $post_id);
            if ($places_source === 'api') {
                $this->populate_city_places_from_api($post_id, $lat, $lng);
            }
            
            // Update last refresh timestamp
            update_field('city_data_last_updated', current_time('timestamp'), $post_id);
        }
    }
    
    /**
     * Auto-geocode city from name and location context
     */
    private function auto_geocode_city($post_id): void {
        if (empty($this->google_api_key)) {
            return;
        }
        
        $city_title = get_the_title($post_id);
        $state = 'Delaware'; // Default state context
        
        // Try to get more specific location if available
        $city_facts = get_field('city_facts', $post_id);
        if (!empty($city_facts['state'])) {
            $state = $city_facts['state'];
        }
        
        $address = "{$city_title}, {$state}, USA";
        
        // Geocode the city
        $coordinates = $this->geocode_address($address);
        
        if ($coordinates) {
            // Update the Google Map field with coordinates
            $map_data = [
                'lat' => $coordinates['lat'],
                'lng' => $coordinates['lng'],
                'address' => $coordinates['formatted_address'] ?? $address,
                'zoom' => 12
            ];
            
            update_field('city_google_map', $map_data, $post_id);
            
            error_log("HPH: Successfully geocoded city '{$city_title}' -> {$coordinates['lat']}, {$coordinates['lng']}");
        }
    }
    
    /**
     * Geocode an address using Google Maps API
     */
    private function geocode_address($address): ?array {
        if (empty($this->google_api_key) || empty($address)) {
            return null;
        }
        
        // Check cache first
        $cache_key = 'hph_city_geocode_' . md5($address);
        $cached = wp_cache_get($cache_key, 'hph_city_geocoding');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $this->google_api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            error_log('HPH City Geocoding Error: ' . $response->get_error_message());
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown geocoding error';
            error_log("HPH City Geocoding failed for address '{$address}': {$error_message}");
            return null;
        }
        
        if (empty($data['results'])) {
            return null;
        }
        
        $result = $data['results'][0];
        $location = $result['geometry']['location'];
        
        $coordinates = [
            'lat' => round((float)$location['lat'], 6),
            'lng' => round((float)$location['lng'], 6),
            'formatted_address' => $result['formatted_address'] ?? $address,
            'place_id' => $result['place_id'] ?? null
        ];
        
        // Cache for 30 days
        wp_cache_set($cache_key, $coordinates, 'hph_city_geocoding', 30 * DAY_IN_SECONDS);
        
        return $coordinates;
    }
    
    /**
     * Populate city places from Google Places API
     */
    private function populate_city_places_from_api($post_id, $lat, $lng): void {
        if (empty($this->google_api_key)) {
            return;
        }
        
        $cache_key = "city_places_{$post_id}_{$lat}_{$lng}";
        $cached_data = wp_cache_get($cache_key, 'hph_city_places');
        
        if ($cached_data !== false) {
            $this->update_city_places_fields($post_id, $cached_data);
            return;
        }
        
        // Categories of places to find for cities
        $place_categories = [
            'restaurant' => ['name' => 'Restaurants', 'radius' => 5000, 'limit' => 10],
            'tourist_attraction' => ['name' => 'Attractions', 'radius' => 10000, 'limit' => 8],
            'park' => ['name' => 'Parks', 'radius' => 8000, 'limit' => 6],
            'shopping_mall' => ['name' => 'Shopping', 'radius' => 10000, 'limit' => 5],
            'hospital' => ['name' => 'Healthcare', 'radius' => 15000, 'limit' => 3],
            'school' => ['name' => 'Schools', 'radius' => 15000, 'limit' => 5],
            'bank' => ['name' => 'Banking', 'radius' => 8000, 'limit' => 4],
            'gas_station' => ['name' => 'Gas Stations', 'radius' => 5000, 'limit' => 5]
        ];
        
        $places_data = [];
        
        foreach ($place_categories as $type => $config) {
            $places = $this->find_nearby_places(
                $lat, 
                $lng, 
                $type, 
                $config['radius'], 
                '', 
                $config['limit']
            );
            
            if (!empty($places)) {
                foreach ($places as $place) {
                    $places_data[] = [
                        'place_id' => $place['place_id'] ?? '',
                        'place_name' => $place['name'] ?? '',
                        'place_category' => $config['name'],
                        'place_icon' => $this->get_place_icon_url($place),
                        'place_rating' => $place['rating'] ?? 0,
                        'place_address' => $place['vicinity'] ?? '',
                        'place_type' => $type
                    ];
                }
            }
        }
        
        // Cache the data
        wp_cache_set($cache_key, $places_data, 'hph_city_places', $this->cache_duration);
        
        // Update the fields
        $this->update_city_places_fields($post_id, $places_data);
    }
    
    /**
     * Find nearby places using Google Places API
     */
    private function find_nearby_places($lat, $lng, $type, $radius = 5000, $keyword = '', $limit = 10): array {
        if (empty($this->google_api_key)) {
            return [];
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?';
        
        $params = [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->google_api_key,
        ];
        
        if (!empty($keyword)) {
            $params['keyword'] = $keyword;
        }
        
        $url .= http_build_query($params);
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            error_log('HPH City Places API Error: ' . $response->get_error_message());
            return [];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($data['results']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown places error';
            error_log("HPH City Places API failed: {$error_message}");
            return [];
        }
        
        return array_slice($data['results'], 0, $limit);
    }
    
    /**
     * Get place icon URL from Google Places data
     */
    private function get_place_icon_url($place): string {
        if (!empty($place['photos']) && !empty($place['photos'][0]['photo_reference'])) {
            return "https://maps.googleapis.com/maps/api/place/photo?" . http_build_query([
                'photoreference' => $place['photos'][0]['photo_reference'],
                'maxwidth' => 200,
                'key' => $this->google_api_key
            ]);
        }
        
        // Fallback to default icon based on place type
        $icon_url = $place['icon'] ?? '';
        return $icon_url;
    }
    
    /**
     * Update city places fields
     */
    private function update_city_places_fields($post_id, $places_data): void {
        // Clear existing API places
        update_field('places_api', [], $post_id);
        
        // Add new places data
        if (!empty($places_data)) {
            update_field('places_api', $places_data, $post_id);
            
            error_log("HPH: Updated city #{$post_id} with " . count($places_data) . " places from Google API");
        }
    }
    
    /**
     * AJAX handler for refreshing city places
     */
    public function ajax_refresh_city_places(): void {
        check_ajax_referer('hph_city_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || get_post_type($post_id) !== 'city') {
            wp_send_json_error('Invalid city ID');
        }
        
        $map_data = get_field('city_google_map', $post_id);
        
        if (empty($map_data['lat']) || empty($map_data['lng'])) {
            wp_send_json_error('City coordinates not found');
        }
        
        // Clear cache
        $cache_key = "city_places_{$post_id}_{$map_data['lat']}_{$map_data['lng']}";
        wp_cache_delete($cache_key, 'hph_city_places');
        
        // Repopulate places
        $this->populate_city_places_from_api($post_id, (float)$map_data['lat'], (float)$map_data['lng']);
        
        wp_send_json_success('City places refreshed successfully');
    }
    
    /**
     * AJAX handler for geocoding city
     */
    public function ajax_geocode_city(): void {
        check_ajax_referer('hph_city_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id || get_post_type($post_id) !== 'city') {
            wp_send_json_error('Invalid city ID');
        }
        
        // Force geocoding
        $this->auto_geocode_city($post_id);
        
        $map_data = get_field('city_google_map', $post_id);
        
        if (!empty($map_data['lat']) && !empty($map_data['lng'])) {
            wp_send_json_success([
                'message' => 'City geocoded successfully',
                'coordinates' => [
                    'lat' => $map_data['lat'],
                    'lng' => $map_data['lng']
                ]
            ]);
        } else {
            wp_send_json_error('Geocoding failed');
        }
    }
    
    /**
     * Enqueue admin scripts for city editing
     */
    public function enqueue_city_admin_scripts($hook): void {
        global $post;
        
        // Only load on city edit screens
        if (($hook === 'post.php' || $hook === 'post-new.php') && 
            isset($post->post_type) && $post->post_type === 'city') {
            
            wp_enqueue_script(
                'hph-city-admin',
                plugin_dir_url(__FILE__) . '../../assets/js/city-admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_localize_script('hph-city-admin', 'hph_city_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hph_city_ajax'),
                'post_id' => $post->ID ?? 0
            ]);
        }
    }
}

// Initialize the service
City_API_Integration::get_instance();
