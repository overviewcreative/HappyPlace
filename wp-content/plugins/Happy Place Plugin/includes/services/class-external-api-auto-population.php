<?php
/**
 * External API Auto-Population Service
 *
 * Handles auto-population of ACF fields from external APIs including:
 * - Google Places API for nearby amenities
 * - Walk Score API for walkability, transit, and bike scores
 * - School district data from various sources
 * - Property tax data from local assessment APIs
 *
 * @package HappyPlace
 * @subpackage Services
 */

namespace HappyPlace\Services;

if (!defined('ABSPATH')) {
    exit;
}

class External_API_Auto_Population {
    
    /**
     * Instance
     */
    private static ?self $instance = null;
    
    /**
     * Google Maps API Key
     */
    private string $google_api_key;
    
    /**
     * Walk Score API Key
     */
    private string $walkscore_api_key;
    
    /**
     * Cache duration in seconds (24 hours)
     */
    private int $cache_duration = 86400;
    
    /**
     * Circuit breaker settings for API failure management
     */
    private array $circuit_breaker_config = [
        'failure_threshold' => 5,      // Number of failures before opening circuit
        'recovery_timeout' => 300,     // 5 minutes before attempting recovery
        'rate_limit_window' => 60,     // 1 minute rate limiting window
        'max_requests_per_window' => 100 // Max requests per window
    ];
    
    /**
     * Retry configuration for API calls
     */
    private array $retry_config = [
        'max_attempts' => 3,
        'base_delay' => 1000,          // 1 second base delay (milliseconds)
        'max_delay' => 8000,           // 8 seconds max delay
        'backoff_multiplier' => 2      // Exponential backoff multiplier
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
        $this->google_api_key = get_option('hph_google_maps_api_key', '');
        $this->walkscore_api_key = get_option('hph_walkscore_api_key', '');
        
        // Hook into listing save to auto-populate data
        add_action('acf/save_post', [$this, 'auto_populate_listing_data'], 25);
        
        // Add admin settings for API keys
        add_action('admin_init', [$this, 'register_api_settings']);
        
        // Add AJAX handlers for manual refresh
        add_action('wp_ajax_hph_refresh_location_data', [$this, 'ajax_refresh_location_data']);
        add_action('wp_ajax_hph_refresh_walkability_data', [$this, 'ajax_refresh_walkability_data']);
        add_action('wp_ajax_hph_refresh_nearby_amenities', [$this, 'ajax_refresh_nearby_amenities']);
        add_action('wp_ajax_hph_geocode_listing', [$this, 'ajax_geocode_listing']);
    }
    
    /**
     * Circuit breaker for API reliability
     */
    private function is_circuit_open(string $api_name): bool {
        $circuit_data = get_option("hph_circuit_breaker_{$api_name}", [
            'failure_count' => 0,
            'last_failure_time' => 0,
            'state' => 'closed' // closed, open, half-open
        ]);
        
        // If circuit is open, check if recovery timeout has passed
        if ($circuit_data['state'] === 'open') {
            $time_since_failure = time() - $circuit_data['last_failure_time'];
            if ($time_since_failure >= $this->circuit_breaker_config['recovery_timeout']) {
                // Move to half-open state for testing
                $circuit_data['state'] = 'half-open';
                \update_option("hph_circuit_breaker_{$api_name}", $circuit_data);
                return false; // Allow one test request
            }
            return true; // Circuit is still open
        }
        
        return false; // Circuit is closed or half-open
    }
    
    /**
     * Record API success for circuit breaker
     */
    private function record_api_success(string $api_name): void {
        $circuit_data = get_option("hph_circuit_breaker_{$api_name}", [
            'failure_count' => 0,
            'last_failure_time' => 0,
            'state' => 'closed'
        ]);
        
        // Reset circuit breaker on success
        \update_option("hph_circuit_breaker_{$api_name}", [
            'failure_count' => 0,
            'last_failure_time' => 0,
            'state' => 'closed'
        ]);
        
        // Log success for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH: API success recorded for {$api_name}, circuit breaker reset");
        }
    }
    
    /**
     * Record API failure for circuit breaker
     */
    private function record_api_failure(string $api_name, string $error_message): void {
        $circuit_data = get_option("hph_circuit_breaker_{$api_name}", [
            'failure_count' => 0,
            'last_failure_time' => 0,
            'state' => 'closed'
        ]);
        
        $circuit_data['failure_count']++;
        $circuit_data['last_failure_time'] = time();
        
        // Open circuit if failure threshold exceeded
        if ($circuit_data['failure_count'] >= $this->circuit_breaker_config['failure_threshold']) {
            $circuit_data['state'] = 'open';
            
            // Log circuit breaker opening
            error_log("HPH: Circuit breaker opened for {$api_name} after {$circuit_data['failure_count']} failures. Error: {$error_message}");
            
            // Log to Plugin Manager for admin visibility
            if (class_exists('\HappyPlace\Core\Plugin_Manager')) {
                $plugin_manager = \HappyPlace\Core\Plugin_Manager::get_instance();
                $plugin_manager->log_integration_error($api_name, "Circuit breaker opened after {$circuit_data['failure_count']} failures: {$error_message}");
            }
        }
        
        update_option("hph_circuit_breaker_{$api_name}", $circuit_data);
    }
    
    /**
     * Check rate limiting for API
     */
    private function is_rate_limited(string $api_name): bool {
        $rate_limit_key = "hph_rate_limit_{$api_name}";
        $current_window = floor(time() / $this->circuit_breaker_config['rate_limit_window']);
        
        $rate_data = get_transient($rate_limit_key);
        if ($rate_data === false) {
            $rate_data = ['window' => $current_window, 'count' => 0];
        }
        
        // Reset counter if we're in a new window
        if ($rate_data['window'] !== $current_window) {
            $rate_data = ['window' => $current_window, 'count' => 0];
        }
        
        // Check if we've exceeded the rate limit
        if ($rate_data['count'] >= $this->circuit_breaker_config['max_requests_per_window']) {
            return true;
        }
        
        // Increment counter and save
        $rate_data['count']++;
        set_transient($rate_limit_key, $rate_data, $this->circuit_breaker_config['rate_limit_window']);
        
        return false;
    }
    
    /**
     * Make API request with circuit breaker and retry logic
     */
    private function make_api_request_with_retries(string $api_name, string $url, array $args = []): ?array {
        // Check circuit breaker
        if ($this->is_circuit_open($api_name)) {
            error_log("HPH: API request blocked by circuit breaker for {$api_name}");
            return null;
        }
        
        // Check rate limiting
        if ($this->is_rate_limited($api_name)) {
            error_log("HPH: API request blocked by rate limiting for {$api_name}");
            return null;
        }
        
        $default_args = [
            'timeout' => 15,
            'sslverify' => true,
            'headers' => [
                'User-Agent' => 'Happy Place Real Estate Plugin v1.0'
            ]
        ];
        
        $args = wp_parse_args($args, $default_args);
        $last_error = null;
        
        // Retry logic with exponential backoff
        for ($attempt = 1; $attempt <= $this->retry_config['max_attempts']; $attempt++) {
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response)) {
                $response_code = wp_remote_retrieve_response_code($response);
                $body = wp_remote_retrieve_body($response);
                
                // Check for successful response
                if ($response_code >= 200 && $response_code < 300) {
                    $data = json_decode($body, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->record_api_success($api_name);
                        return $data;
                    }
                }
                
                // Handle specific error codes
                if ($response_code === 429) { // Too Many Requests
                    $last_error = "Rate limited by {$api_name} API";
                } elseif ($response_code >= 500) { // Server error - retry
                    $last_error = "Server error from {$api_name} API (HTTP {$response_code})";
                } else { // Client error - don't retry
                    $last_error = "Client error from {$api_name} API (HTTP {$response_code})";
                    break;
                }
            } else {
                $last_error = $response->get_error_message();
            }
            
            // Wait before retry (except on last attempt)
            if ($attempt < $this->retry_config['max_attempts']) {
                $delay = min(
                    $this->retry_config['base_delay'] * pow($this->retry_config['backoff_multiplier'], $attempt - 1),
                    $this->retry_config['max_delay']
                );
                usleep($delay * 1000); // Convert to microseconds
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("HPH: API retry attempt {$attempt} for {$api_name} after {$delay}ms delay. Error: {$last_error}");
                }
            }
        }
        
        // All attempts failed - record failure
        $final_error = "All {$this->retry_config['max_attempts']} attempts failed for {$api_name}. Last error: {$last_error}";
        $this->record_api_failure($api_name, $final_error);
        
        return null;
    }
    
    /**
     * Register API settings
     */
    public function register_api_settings(): void {
        register_setting('hph_external_apis', 'hph_google_maps_api_key');
        register_setting('hph_external_apis', 'hph_walkscore_api_key');
        register_setting('hph_external_apis', 'hph_school_api_key');
        register_setting('hph_external_apis', 'hph_property_tax_api_key');
    }
    
    /**
     * Auto-populate listing data when a listing is saved
     */
    public function auto_populate_listing_data($post_id): void {
        // Only process listings
        if (get_post_type($post_id) !== 'listing') {
            return;
        }
        
        // Skip during autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Get coordinates
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        // If coordinates are missing, try to geocode from address
        if (!$lat || !$lng) {
            $this->auto_geocode_listing($post_id);
            // Get the newly geocoded coordinates
            $lat = get_field('latitude', $post_id);
            $lng = get_field('longitude', $post_id);
        }
        
        // If we still don't have coordinates, can't proceed
        if (!$lat || !$lng) {
            return;
        }
        
        // Schedule background processing to avoid timeouts
        wp_schedule_single_event(time() + 10, 'hph_process_location_intelligence', [$post_id, $lat, $lng]);
    }
    
    /**
     * Process location intelligence data in background
     */
    public function process_location_intelligence($post_id, $lat, $lng): void {
        try {
            // Populate school data
            $this->populate_school_data($post_id, $lat, $lng);
            
            // Populate walkability scores
            $this->populate_walkability_data($post_id, $lat, $lng);
            
            // Populate nearby amenities
            $this->populate_nearby_amenities($post_id, $lat, $lng);
            
            // Populate property tax data
            $this->populate_property_tax_data($post_id);
            
            // Update last refresh timestamp
            update_field('location_intelligence_last_updated', current_time('timestamp'), $post_id);
            
        } catch (\Exception $e) {
            error_log('HPH Location Intelligence Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Auto-geocode listing from address fields
     */
    private function auto_geocode_listing($post_id): void {
        if (empty($this->google_api_key)) {
            return;
        }
        
        // Build address from ACF fields
        $address_parts = [];
        
        // Get address components - try multiple field name variations
        $street_address = get_field('street_address', $post_id) ?: get_field('address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id) ?: get_field('region', $post_id);
        $zip = get_field('zip_code', $post_id) ?: get_field('zip', $post_id) ?: get_field('postal_code', $post_id);
        
        if ($street_address) $address_parts[] = $street_address;
        if ($city) $address_parts[] = $city;
        if ($state) $address_parts[] = $state;
        if ($zip) $address_parts[] = $zip;
        
        $address = implode(', ', $address_parts);
        
        if (empty($address)) {
            return;
        }
        
        // Geocode the address
        $coordinates = $this->geocode_address($address);
        
        if ($coordinates && isset($coordinates['lat']) && isset($coordinates['lng'])) {
            // Update the coordinate fields
            update_field('latitude', $coordinates['lat'], $post_id);
            update_field('longitude', $coordinates['lng'], $post_id);
            
            // Update additional geocoding data if available
            if (isset($coordinates['formatted_address'])) {
                update_field('full_address', $coordinates['formatted_address'], $post_id);
            }
            
            if (isset($coordinates['place_id'])) {
                update_field('google_place_id', $coordinates['place_id'], $post_id);
            }
            
            error_log("HPH: Successfully geocoded address '{$address}' for listing #{$post_id} -> {$coordinates['lat']}, {$coordinates['lng']}");
        }
    }
    
    /**
     * Geocode an address using Google Maps API with circuit breaker protection
     */
    private function geocode_address($address): ?array {
        if (empty($this->google_api_key) || empty($address)) {
            return null;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $this->google_api_key
        ]);
        
        $data = $this->make_api_request_with_retries('google_geocoding', $url);
        
        if (!$data) {
            return null;
        }
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown geocoding error';
            error_log("HPH Geocoding failed for address '{$address}': {$error_message}");
            $this->record_api_failure('google_geocoding', $error_message);
            return null;
        }
        
        if (empty($data['results'])) {
            return null;
        }
        
        $result = $data['results'][0];
        $location = $result['geometry']['location'];
        
        return [
            'lat' => round((float)$location['lat'], 6),
            'lng' => round((float)$location['lng'], 6),
            'formatted_address' => $result['formatted_address'] ?? $address,
            'place_id' => $result['place_id'] ?? null
        ];
    }
    
    /**
     * Populate school data using Google Places API and external school APIs
     */
    private function populate_school_data($post_id, $lat, $lng): void {
        if (empty($this->google_api_key)) {
            return;
        }
        
        $cache_key = "school_data_{$post_id}_{$lat}_{$lng}";
        $cached_data = wp_cache_get($cache_key, 'hph_location_intelligence');
        
        if ($cached_data !== false) {
            $this->update_school_fields($post_id, $cached_data);
            return;
        }
        
        $school_data = [];
        
        // Find nearby schools using Google Places API
        $school_types = ['elementary_school', 'secondary_school', 'university'];
        
        foreach ($school_types as $type) {
            $schools = $this->find_nearby_places($lat, $lng, $type, 5000); // 5km radius
            
            if (!empty($schools)) {
                switch ($type) {
                    case 'elementary_school':
                        $school_data['elementary_school'] = $schools[0]['name'] ?? '';
                        break;
                    case 'secondary_school':
                        if (empty($school_data['middle_school'])) {
                            $school_data['middle_school'] = $schools[0]['name'] ?? '';
                        }
                        if (count($schools) > 1) {
                            $school_data['high_school'] = $schools[1]['name'] ?? $schools[0]['name'] ?? '';
                        }
                        break;
                }
            }
        }
        
        // Get school district from address
        $school_data['school_district'] = $this->get_school_district_from_address($post_id, $lat, $lng);
        
        // Cache the data
        wp_cache_set($cache_key, $school_data, 'hph_location_intelligence', $this->cache_duration);
        
        // Update fields
        $this->update_school_fields($post_id, $school_data);
    }
    
    /**
     * Update school-related ACF fields
     */
    private function update_school_fields($post_id, $school_data): void {
        if (!empty($school_data['school_district'])) {
            update_field('school_district', $school_data['school_district'], $post_id);
        }
        
        // Update assigned schools group structure
        $assigned_schools = [];
        if (!empty($school_data['elementary_school'])) {
            $assigned_schools['elementary_school'] = $school_data['elementary_school'];
            $assigned_schools['elementary_rating'] = $school_data['elementary_rating'] ?? null;
        }
        
        if (!empty($school_data['middle_school'])) {
            $assigned_schools['middle_school'] = $school_data['middle_school'];
            $assigned_schools['middle_rating'] = $school_data['middle_rating'] ?? null;
        }
        
        if (!empty($school_data['high_school'])) {
            $assigned_schools['high_school'] = $school_data['high_school'];
            $assigned_schools['high_rating'] = $school_data['high_rating'] ?? null;
        }
        
        if (!empty($assigned_schools)) {
            update_field('assigned_schools', $assigned_schools, $post_id);
        }
        
        // Update private schools if available
        if (!empty($school_data['private_schools']) && is_array($school_data['private_schools'])) {
            update_field('private_schools', $school_data['private_schools'], $post_id);
        }
    }
    
    /**
     * Get school district from address using geocoding and local data sources
     */
    private function get_school_district_from_address($post_id, $lat, $lng): string {
        // Try to get from cached address data first
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        
        // Delaware school district mapping (hardcoded for now)
        if (strtoupper($state) === 'DE') {
            $delaware_districts = [
                'Wilmington' => 'Red Clay Consolidated School District',
                'Newark' => 'Christina School District',
                'Dover' => 'Capital School District',
                'Middletown' => 'Appoquinimink School District',
                'Smyrna' => 'Smyrna School District',
                'Milford' => 'Milford School District',
                'Georgetown' => 'Indian River School District',
                'Lewes' => 'Cape Henlopen School District',
                'Rehoboth Beach' => 'Cape Henlopen School District',
                'Bethany Beach' => 'Indian River School District',
                'Ocean View' => 'Indian River School District',
                'Millsboro' => 'Indian River School District',
                'Seaford' => 'Seaford School District',
                'Laurel' => 'Laurel School District',
                'Delmar' => 'Delmar School District',
            ];
            
            if (isset($delaware_districts[$city])) {
                return $delaware_districts[$city];
            }
        }
        
        // If we can't find a match, try Google Places API for "school district" nearby
        $districts = $this->find_nearby_places($lat, $lng, 'school', 10000, 'school district');
        
        return !empty($districts) ? $districts[0]['name'] : '';
    }
    
    /**
     * Populate walkability data using Walk Score API
     */
    private function populate_walkability_data($post_id, $lat, $lng): void {
        $cache_key = "walkability_data_{$post_id}_{$lat}_{$lng}";
        $cached_data = wp_cache_get($cache_key, 'hph_location_intelligence');
        
        if ($cached_data !== false) {
            $this->update_walkability_fields($post_id, $cached_data);
            return;
        }
        
        $walkability_data = [];
        
        // Get Walk Score data
        if (!empty($this->walkscore_api_key)) {
            $walkability_data = $this->get_walkscore_data($lat, $lng);
        } else {
            // Fallback to estimated scores based on nearby amenities
            $walkability_data = $this->estimate_walkability_scores($lat, $lng);
        }
        
        // Cache the data
        wp_cache_set($cache_key, $walkability_data, 'hph_location_intelligence', $this->cache_duration);
        
        // Update fields
        $this->update_walkability_fields($post_id, $walkability_data);
    }
    
    /**
     * Get Walk Score data from API with circuit breaker protection
     */
    private function get_walkscore_data($lat, $lng): array {
        $url = "https://api.walkscore.com/score?format=json&lat={$lat}&lon={$lng}&wsapikey={$this->walkscore_api_key}";
        
        $data = $this->make_api_request_with_retries('walkscore', $url);
        
        if (!$data) {
            return [];
        }
        
        if (!isset($data['walkscore'])) {
            $error_message = isset($data['error']) ? $data['error'] : 'Walk Score API error';
            $this->record_api_failure('walkscore', $error_message);
            return [];
        }
        
        return [
            'walk_score' => $data['walkscore'],
            'transit_score' => $data['transit']['score'] ?? 0,
            'bike_score' => $data['bike']['score'] ?? 0,
        ];
    }
    
    /**
     * Estimate walkability scores based on nearby amenities
     */
    private function estimate_walkability_scores($lat, $lng): array {
        // Count nearby amenities to estimate walkability
        $amenity_types = [
            'grocery_or_supermarket',
            'restaurant',
            'pharmacy',
            'bank',
            'gas_station',
            'hospital',
            'school',
            'park'
        ];
        
        $total_amenities = 0;
        foreach ($amenity_types as $type) {
            $places = $this->find_nearby_places($lat, $lng, $type, 1600); // 1 mile radius
            $total_amenities += count($places);
        }
        
        // Estimate scores based on amenity density
        $walk_score = min(100, $total_amenities * 10);
        $transit_score = max(0, $walk_score - 20); // Transit usually lower than walk
        $bike_score = max(0, $walk_score - 10); // Bike usually similar to walk
        
        return [
            'walk_score' => $walk_score,
            'transit_score' => $transit_score,
            'bike_score' => $bike_score,
        ];
    }
    
    /**
     * Update walkability ACF fields
     */
    private function update_walkability_fields($post_id, $walkability_data): void {
        if (isset($walkability_data['walk_score'])) {
            update_field('walk_score', $walkability_data['walk_score'], $post_id);
        }
        
        if (isset($walkability_data['transit_score'])) {
            update_field('transit_score', $walkability_data['transit_score'], $post_id);
        }
        
        if (isset($walkability_data['bike_score'])) {
            update_field('bike_score', $walkability_data['bike_score'], $post_id);
        }
    }
    
    /**
     * Populate nearby amenities using Google Places API
     */
    private function populate_nearby_amenities($post_id, $lat, $lng): void {
        if (empty($this->google_api_key)) {
            return;
        }
        
        $cache_key = "nearby_amenities_{$post_id}_{$lat}_{$lng}";
        $cached_data = wp_cache_get($cache_key, 'hph_location_intelligence');
        
        if ($cached_data !== false) {
            $this->update_amenities_field($post_id, $cached_data);
            return;
        }
        
        $amenity_types = [
            'grocery_or_supermarket' => 'Grocery Store',
            'restaurant' => 'Restaurant',
            'gas_station' => 'Gas Station',
            'bank' => 'Bank',
            'pharmacy' => 'Pharmacy',
            'hospital' => 'Hospital',
            'park' => 'Park',
            'gym' => 'Gym',
            'shopping_mall' => 'Shopping Center',
            'movie_theater' => 'Movie Theater',
            'library' => 'Library',
            'post_office' => 'Post Office',
        ];
        
        $all_amenities = [];
        $category_counts = [
            'restaurants_count' => 0,
            'shopping_count' => 0,
            'healthcare_count' => 0,
            'parks_count' => 0,
            'entertainment_count' => 0
        ];
        
        foreach ($amenity_types as $type => $display_name) {
            $places = $this->find_nearby_places($lat, $lng, $type, 3200, '', 5); // 2 miles, max 5 per type
            
            foreach ($places as $place) {
                $amenity_address = '';
                if (isset($place['vicinity'])) {
                    $amenity_address = $place['vicinity'];
                } elseif (isset($place['formatted_address'])) {
                    $amenity_address = $place['formatted_address'];
                }
                
                $all_amenities[] = [
                    'amenity_name' => $place['name'],
                    'amenity_type' => $display_name,
                    'amenity_distance' => $this->calculate_distance($lat, $lng, $place['geometry']['location']['lat'], $place['geometry']['location']['lng']),
                    'amenity_rating' => $place['rating'] ?? 0,
                    'amenity_address' => $amenity_address,
                ];
                
                // Count by category
                $this->increment_category_count($category_counts, $type);
            }
        }
        
        // Sort by distance and limit to 30
        usort($all_amenities, function($a, $b) {
            return $a['amenity_distance'] <=> $b['amenity_distance'];
        });
        
        $all_amenities = array_slice($all_amenities, 0, 30);
        
        $amenities_data = [
            'amenities' => $all_amenities,
            'category_counts' => $category_counts
        ];
        
        // Cache the data
        wp_cache_set($cache_key, $amenities_data, 'hph_location_intelligence', $this->cache_duration);
        
        // Update field
        $this->update_amenities_field($post_id, $amenities_data);
    }
    
    /**
     * Increment category count based on amenity type
     */
    private function increment_category_count(&$category_counts, $type): void {
        switch ($type) {
            case 'restaurant':
                $category_counts['restaurants_count']++;
                break;
            case 'grocery_or_supermarket':
            case 'shopping_mall':
                $category_counts['shopping_count']++;
                break;
            case 'hospital':
            case 'pharmacy':
                $category_counts['healthcare_count']++;
                break;
            case 'park':
                $category_counts['parks_count']++;
                break;
            case 'movie_theater':
            case 'gym':
                $category_counts['entertainment_count']++;
                break;
        }
    }
    
    /**
     * Update amenities ACF fields
     */
    private function update_amenities_field($post_id, $amenities_data): void {
        if (is_array($amenities_data) && isset($amenities_data['amenities'])) {
            // New structure with category counts
            update_field('nearby_amenities', $amenities_data['amenities'], $post_id);
            
            if (isset($amenities_data['category_counts'])) {
                update_field('amenities_by_category', $amenities_data['category_counts'], $post_id);
            }
        } else {
            // Legacy structure - just amenities array
            update_field('nearby_amenities', $amenities_data, $post_id);
        }
    }
    
    /**
     * Find nearby places using Google Places API with circuit breaker protection
     */
    private function find_nearby_places($lat, $lng, $type, $radius = 1600, $keyword = '', $limit = 5): array {
        if (empty($this->google_api_key)) {
            return [];
        }
        
        $params = [
            'location' => "{$lat},{$lng}",
            'radius' => $radius,
            'type' => $type,
            'key' => $this->google_api_key,
        ];
        
        if (!empty($keyword)) {
            $params['keyword'] = $keyword;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?' . http_build_query($params);
        
        $data = $this->make_api_request_with_retries('google_places', $url);
        
        if (!$data) {
            return [];
        }
        
        if (!isset($data['results']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Places API error';
            $this->record_api_failure('google_places', $error_message);
            return [];
        }
        
        return array_slice($data['results'], 0, $limit);
    }
    
    /**
     * Populate property tax data
     */
    private function populate_property_tax_data($post_id): void {
        // Get listing price and location data
        $price = get_field('listing_price', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $county = get_field('county', $post_id);
        
        if (!$price || !$city || !$state) {
            return;
        }
        
        // Delaware property tax rates (approximate)
        $tax_rates = [
            'New Castle County' => 0.0054,
            'Kent County' => 0.0051,
            'Sussex County' => 0.0043,
        ];
        
        // Default rate if county not found
        $tax_rate = 0.005;
        
        if ($county && isset($tax_rates[$county])) {
            $tax_rate = $tax_rates[$county];
        } elseif (strtoupper($state) === 'DE') {
            // Estimate by city for Delaware
            $sussex_cities = ['Lewes', 'Rehoboth Beach', 'Bethany Beach', 'Ocean View', 'Millsboro', 'Georgetown', 'Seaford', 'Laurel', 'Delmar'];
            $kent_cities = ['Dover', 'Smyrna', 'Milford'];
            
            if (in_array($city, $sussex_cities)) {
                $tax_rate = $tax_rates['Sussex County'];
            } elseif (in_array($city, $kent_cities)) {
                $tax_rate = $tax_rates['Kent County'];
            } else {
                $tax_rate = $tax_rates['New Castle County'];
            }
        }
        
        // Calculate annual property tax
        $annual_tax = round($price * $tax_rate);
        
        // Update fields
        update_field('property_tax_rate', round($tax_rate * 100, 2), $post_id); // Store as percentage
        update_field('annual_property_taxes', $annual_tax, $post_id);
    }
    
    /**
     * Calculate distance between two points in miles
     */
    private function calculate_distance($lat1, $lng1, $lat2, $lng2): float {
        $earth_radius = 3959; // miles
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earth_radius * $c, 2);
    }
    
    /**
     * AJAX handler to refresh location data
     */
    public function ajax_refresh_location_data(): void {
        check_ajax_referer('hph_location_intelligence', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        if (!$lat || !$lng) {
            wp_send_json_error('Missing coordinates');
        }
        
        // Clear cache
        $cache_keys = [
            "school_data_{$post_id}_{$lat}_{$lng}",
            "walkability_data_{$post_id}_{$lat}_{$lng}",
            "nearby_amenities_{$post_id}_{$lat}_{$lng}",
        ];
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'hph_location_intelligence');
        }
        
        // Process location intelligence
        $this->process_location_intelligence($post_id, $lat, $lng);
        
        wp_send_json_success('Location intelligence data refreshed successfully');
    }
    
    /**
     * AJAX handler to refresh walkability data
     */
    public function ajax_refresh_walkability_data(): void {
        check_ajax_referer('hph_location_intelligence', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        if (!$lat || !$lng) {
            wp_send_json_error('Missing coordinates');
        }
        
        // Clear cache and refresh
        wp_cache_delete("walkability_data_{$post_id}_{$lat}_{$lng}", 'hph_location_intelligence');
        $this->populate_walkability_data($post_id, $lat, $lng);
        
        wp_send_json_success('Walkability data refreshed successfully');
    }
    
    /**
     * AJAX handler to refresh nearby amenities
     */
    public function ajax_refresh_nearby_amenities(): void {
        check_ajax_referer('hph_location_intelligence', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        if (!$lat || !$lng) {
            wp_send_json_error('Missing coordinates');
        }
        
        // Clear cache and refresh
        wp_cache_delete("nearby_amenities_{$post_id}_{$lat}_{$lng}", 'hph_location_intelligence');
        $this->populate_nearby_amenities($post_id, $lat, $lng);
        
        wp_send_json_success('Nearby amenities data refreshed successfully');
    }
    
    /**
     * AJAX handler to manually geocode a listing
     */
    public function ajax_geocode_listing(): void {
        check_ajax_referer('hph_location_intelligence', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Force geocoding
        $this->auto_geocode_listing($post_id);
        
        // Check if we got coordinates
        $lat = get_field('latitude', $post_id);
        $lng = get_field('longitude', $post_id);
        
        if ($lat && $lng) {
            wp_send_json_success([
                'message' => 'Address geocoded successfully',
                'coordinates' => [
                    'lat' => $lat,
                    'lng' => $lng
                ]
            ]);
        } else {
            wp_send_json_error('Failed to geocode address. Please check that the address fields are filled out correctly.');
        }
    }
    
    /**
     * Get circuit breaker status for all APIs
     */
    public function get_circuit_breaker_status(): array {
        $apis = ['google_geocoding', 'google_places', 'walkscore'];
        $status = [];
        
        foreach ($apis as $api_name) {
            $circuit_data = get_option("hph_circuit_breaker_{$api_name}", [
                'failure_count' => 0,
                'last_failure_time' => 0,
                'state' => 'closed'
            ]);
            
            $status[$api_name] = [
                'state' => $circuit_data['state'],
                'failure_count' => $circuit_data['failure_count'],
                'last_failure_time' => $circuit_data['last_failure_time'],
                'last_failure_ago' => $circuit_data['last_failure_time'] ? 
                    human_time_diff($circuit_data['last_failure_time']) . ' ago' : 'Never',
                'recovery_time_remaining' => 0
            ];
            
            // Calculate recovery time remaining for open circuits
            if ($circuit_data['state'] === 'open') {
                $time_since_failure = time() - $circuit_data['last_failure_time'];
                $recovery_time_remaining = max(0, $this->circuit_breaker_config['recovery_timeout'] - $time_since_failure);
                $status[$api_name]['recovery_time_remaining'] = $recovery_time_remaining;
            }
        }
        
        return $status;
    }
    
    /**
     * Reset circuit breaker for specific API
     */
    public function reset_circuit_breaker(string $api_name): bool {
        if (!in_array($api_name, ['google_geocoding', 'google_places', 'walkscore'])) {
            return false;
        }
        
        update_option("hph_circuit_breaker_{$api_name}", [
            'failure_count' => 0,
            'last_failure_time' => 0,
            'state' => 'closed'
        ]);
        
        // Also clear rate limiting
        delete_transient("hph_rate_limit_{$api_name}");
        
        error_log("HPH: Circuit breaker manually reset for {$api_name}");
        
        return true;
    }
    
    /**
     * Reset all circuit breakers
     */
    public function reset_all_circuit_breakers(): void {
        $apis = ['google_geocoding', 'google_places', 'walkscore'];
        
        foreach ($apis as $api_name) {
            $this->reset_circuit_breaker($api_name);
        }
        
        error_log("HPH: All circuit breakers manually reset");
    }
    
    /**
     * Get API health summary
     */
    public function get_api_health_summary(): array {
        $circuit_status = $this->get_circuit_breaker_status();
        $summary = [
            'overall_health' => 'healthy',
            'apis' => [],
            'issues' => []
        ];
        
        foreach ($circuit_status as $api_name => $status) {
            $api_health = 'healthy';
            
            if ($status['state'] === 'open') {
                $api_health = 'down';
                $summary['overall_health'] = 'degraded';
                $summary['issues'][] = "API {$api_name} is down (circuit breaker open)";
            } elseif ($status['state'] === 'half-open') {
                $api_health = 'recovering';
                if ($summary['overall_health'] === 'healthy') {
                    $summary['overall_health'] = 'degraded';
                }
                $summary['issues'][] = "API {$api_name} is recovering";
            } elseif ($status['failure_count'] > 2) {
                $api_health = 'unstable';
                if ($summary['overall_health'] === 'healthy') {
                    $summary['overall_health'] = 'degraded';
                }
                $summary['issues'][] = "API {$api_name} has recent failures ({$status['failure_count']})";
            }
            
            $summary['apis'][$api_name] = $api_health;
        }
        
        return $summary;
    }
}

// Register the background processing hook
add_action('hph_process_location_intelligence', [External_API_Auto_Population::class, 'process_location_intelligence'], 10, 3);

// Initialize the service
External_API_Auto_Population::get_instance();
