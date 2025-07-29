<?php
/**
 * API Enhanced Bridge Functions
 * 
 * Enhanced bridge functions with API integrations for coordinates, places, and walk scores
 *
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get listing coordinates with Google Maps API integration
 */
if (!function_exists('hph_bridge_get_coordinates')) {
    function hph_bridge_get_coordinates($listing_id, $force_refresh = false) {
        if (!$listing_id) return null;
        
        $cache_key = "hph_coordinates_{$listing_id}";
        
        if (!$force_refresh) {
            $cached = wp_cache_get($cache_key, 'hph_listings');
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Try to get from ACF fields first
        $lat = get_field('latitude', $listing_id);
        $lng = get_field('longitude', $listing_id);
        
        if ($lat && $lng) {
            $coordinates = ['lat' => floatval($lat), 'lng' => floatval($lng)];
            $api_manager = HPH_API_Key_Manager::get_instance();
            wp_cache_set($cache_key, $coordinates, 'hph_listings', $api_manager->get_cache_duration('coordinates'));
            return $coordinates;
        }
        
        // If no coordinates, try geocoding with API
        $api_manager = HPH_API_Key_Manager::get_instance();
        if ($api_manager->is_google_maps_available()) {
            $coordinates = hph_geocode_address($listing_id, $api_manager->get_google_maps_key());
            if ($coordinates) {
                // Save coordinates back to listing
                update_field('latitude', $coordinates['lat'], $listing_id);
                update_field('longitude', $coordinates['lng'], $listing_id);
                
                wp_cache_set($cache_key, $coordinates, 'hph_listings', $api_manager->get_cache_duration('coordinates'));
                
                // Update last used timestamp
                update_option('hph_google_maps_last_used', current_time('mysql'));
                
                return $coordinates;
            }
        }
        
        return null;
    }
}

/**
 * Get listing address for API calls
 */
if (!function_exists('hph_bridge_get_address')) {
    function hph_bridge_get_address($listing_id, $format = 'full') {
        $address_components = hph_get_listing_address($listing_id, false);
        
        if (empty($address_components['street']) || empty($address_components['city'])) {
            return null;
        }
        
        switch ($format) {
            case 'full':
                return trim(implode(', ', array_filter([
                    $address_components['street'],
                    $address_components['city'],
                    $address_components['state'],
                    $address_components['zip']
                ])));
            case 'street':
                return $address_components['street'];
            case 'city_state':
                return trim($address_components['city'] . ', ' . $address_components['state']);
            default:
                return $address_components;
        }
    }
}

/**
 * Geocode address using Google Maps API
 */
if (!function_exists('hph_geocode_address')) {
    function hph_geocode_address($listing_id, $api_key) {
        if (!$api_key) return null;
        
        $address = hph_bridge_get_address($listing_id, 'full');
        if (!$address) return null;
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            $api_manager = HPH_API_Key_Manager::get_instance();
            $api_manager->log_api_error('Google Maps Geocoding', $response->get_error_message(), [
                'listing_id' => $listing_id,
                'address' => $address
            ]);
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $location = $data['results'][0]['geometry']['location'];
            return [
                'lat' => $location['lat'],
                'lng' => $location['lng']
            ];
        }
        
        $api_manager = HPH_API_Key_Manager::get_instance();
        $api_manager->log_api_error('Google Maps Geocoding', 'No results found', [
            'listing_id' => $listing_id,
            'address' => $address,
            'api_status' => $data['status'] ?? 'unknown'
        ]);
        
        return null;
    }
}

/**
 * Get nearby places using Google Places API
 */
if (!function_exists('hph_bridge_get_nearby_places')) {
    function hph_bridge_get_nearby_places($listing_id, $type = 'restaurant', $radius = 1000) {
        $api_manager = HPH_API_Key_Manager::get_instance();
        
        if (!$api_manager->is_google_places_enabled()) {
            return [];
        }
        
        $coordinates = hph_bridge_get_coordinates($listing_id);
        if (!$coordinates) return [];
        
        $cache_key = "hph_places_{$listing_id}_{$type}_{$radius}";
        $cached = wp_cache_get($cache_key, 'hph_places');
        if ($cached !== false) {
            return $cached;
        }
        
        $api_key = $api_manager->get_google_maps_key();
        $url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?' . http_build_query([
            'location' => $coordinates['lat'] . ',' . $coordinates['lng'],
            'radius' => $radius,
            'type' => $type,
            'key' => $api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 15]);
        
        if (is_wp_error($response)) {
            $api_manager->log_api_error('Google Places', $response->get_error_message(), [
                'listing_id' => $listing_id,
                'type' => $type,
                'radius' => $radius
            ]);
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        $places = [];
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            foreach ($data['results'] as $place) {
                $places[] = [
                    'name' => $place['name'],
                    'rating' => $place['rating'] ?? null,
                    'price_level' => $place['price_level'] ?? null,
                    'distance' => hph_calculate_distance(
                        $coordinates['lat'], $coordinates['lng'],
                        $place['geometry']['location']['lat'],
                        $place['geometry']['location']['lng']
                    ),
                    'types' => $place['types'] ?? [],
                    'vicinity' => $place['vicinity'] ?? ''
                ];
            }
            
            // Update last used timestamp
            update_option('hph_google_maps_last_used', current_time('mysql'));
        } else {
            $api_manager->log_api_error('Google Places', 'No results or API error', [
                'listing_id' => $listing_id,
                'api_status' => $data['status'] ?? 'unknown'
            ]);
        }
        
        // Cache for duration specified in settings
        wp_cache_set($cache_key, $places, 'hph_places', $api_manager->get_cache_duration('places'));
        return $places;
    }
}

/**
 * Calculate distance between two coordinates
 */
if (!function_exists('hph_calculate_distance')) {
    function hph_calculate_distance($lat1, $lng1, $lat2, $lng2, $unit = 'miles') {
        $earth_radius = ($unit === 'km') ? 6371 : 3959; // Earth radius in km or miles
        
        $lat_delta = deg2rad($lat2 - $lat1);
        $lng_delta = deg2rad($lng2 - $lng1);
        
        $a = sin($lat_delta/2) * sin($lat_delta/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lng_delta/2) * sin($lng_delta/2);
        
        $c = 2 * asin(sqrt($a));
        $distance = $earth_radius * $c;
        
        return round($distance, 2);
    }
}

/**
 * Get Walk Score data using API or estimation
 */
if (!function_exists('hph_bridge_get_walk_score')) {
    function hph_bridge_get_walk_score($listing_id) {
        $api_manager = HPH_API_Key_Manager::get_instance();
        $coordinates = hph_bridge_get_coordinates($listing_id);
        
        if (!$coordinates) return null;
        
        $cache_key = "hph_walkscore_{$listing_id}";
        $cached = wp_cache_get($cache_key, 'hph_walkscore');
        if ($cached !== false) {
            return $cached;
        }
        
        // Try Walk Score API first
        if ($api_manager->is_walkscore_available()) {
            $score_data = hph_get_walkscore_api_data($listing_id, $coordinates, $api_manager->get_walkscore_key());
            if ($score_data) {
                wp_cache_set($cache_key, $score_data, 'hph_walkscore', $api_manager->get_cache_duration('walkscore'));
                return $score_data;
            }
        }
        
        // Fallback to estimation based on nearby amenities
        if ($api_manager->is_google_places_enabled()) {
            $estimated_score = hph_estimate_walk_score($listing_id);
            wp_cache_set($cache_key, $estimated_score, 'hph_walkscore', $api_manager->get_cache_duration('walkscore'));
            return $estimated_score;
        }
        
        return null;
    }
}

/**
 * Get Walk Score data from API
 */
if (!function_exists('hph_get_walkscore_api_data')) {
    function hph_get_walkscore_api_data($listing_id, $coordinates, $api_key) {
        $address = hph_bridge_get_address($listing_id, 'full');
        
        $url = 'https://api.walkscore.com/score?' . http_build_query([
            'format' => 'json',
            'lat' => $coordinates['lat'],
            'lon' => $coordinates['lng'],
            'address' => $address,
            'wsapikey' => $api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            $api_manager = HPH_API_Key_Manager::get_instance();
            $api_manager->log_api_error('Walk Score', $response->get_error_message(), [
                'listing_id' => $listing_id,
                'address' => $address
            ]);
            return null;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] == 1) { // Success
            // Update last used timestamp
            update_option('hph_walkscore_last_used', current_time('mysql'));
            
            return [
                'walk_score' => $data['walkscore'],
                'description' => $data['description'],
                'updated' => current_time('mysql'),
                'source' => 'walkscore_api'
            ];
        }
        
        $api_manager = HPH_API_Key_Manager::get_instance();
        $api_manager->log_api_error('Walk Score', 'API returned error status', [
            'listing_id' => $listing_id,
            'api_status' => $data['status'] ?? 'unknown',
            'api_response' => $data
        ]);
        
        return null;
    }
}

/**
 * Estimate walk score based on nearby amenities
 */
if (!function_exists('hph_estimate_walk_score')) {
    function hph_estimate_walk_score($listing_id) {
        $api_manager = HPH_API_Key_Manager::get_instance();
        
        if (!$api_manager->is_google_places_enabled()) {
            return null;
        }
        
        // Define walkability factors
        $walkability_types = [
            'restaurant' => ['weight' => 0.2, 'max_distance' => 0.5],
            'grocery_or_supermarket' => ['weight' => 0.25, 'max_distance' => 0.8],
            'school' => ['weight' => 0.15, 'max_distance' => 1.0],
            'hospital' => ['weight' => 0.1, 'max_distance' => 2.0],
            'bank' => ['weight' => 0.1, 'max_distance' => 1.0],
            'transit_station' => ['weight' => 0.2, 'max_distance' => 0.5]
        ];
        
        $total_score = 0;
        $max_possible_score = 0;
        
        foreach ($walkability_types as $type => $config) {
            $places = hph_bridge_get_nearby_places($listing_id, $type, $config['max_distance'] * 1609); // Convert miles to meters
            
            $type_score = 0;
            $place_count = count($places);
            
            if ($place_count > 0) {
                // Score based on count and proximity
                $proximity_bonus = 0;
                foreach ($places as $place) {
                    if ($place['distance'] <= $config['max_distance']) {
                        $proximity_bonus += (1 - ($place['distance'] / $config['max_distance'])) * 10;
                    }
                }
                
                $type_score = min(100, ($place_count * 20) + $proximity_bonus);
            }
            
            $weighted_score = $type_score * $config['weight'];
            $total_score += $weighted_score;
            $max_possible_score += 100 * $config['weight'];
        }
        
        $final_score = min(100, ($total_score / $max_possible_score) * 100);
        
        // Determine description based on score
        $description = 'Car-Dependent';
        if ($final_score >= 90) $description = "Walker's Paradise";
        elseif ($final_score >= 70) $description = 'Very Walkable';
        elseif ($final_score >= 50) $description = 'Somewhat Walkable';
        elseif ($final_score >= 25) $description = 'Car-Dependent';
        
        return [
            'walk_score' => round($final_score),
            'description' => $description,
            'updated' => current_time('mysql'),
            'source' => 'estimated'
        ];
    }
}

/**
 * Get comprehensive neighborhood data
 */
if (!function_exists('hph_bridge_get_neighborhood_data')) {
    function hph_bridge_get_neighborhood_data($listing_id) {
        $api_manager = HPH_API_Key_Manager::get_instance();
        
        $cache_key = "hph_neighborhood_{$listing_id}";
        $cached = wp_cache_get($cache_key, 'hph_neighborhoods');
        if ($cached !== false) {
            return $cached;
        }
        
        $neighborhood_data = [
            'coordinates' => hph_bridge_get_coordinates($listing_id),
            'walk_score' => hph_bridge_get_walk_score($listing_id),
            'nearby_amenities' => []
        ];
        
        // Get various types of nearby places
        if ($api_manager->is_google_places_enabled()) {
            $amenity_types = ['restaurant', 'grocery_or_supermarket', 'school', 'hospital', 'park', 'shopping_mall'];
            
            foreach ($amenity_types as $type) {
                $neighborhood_data['nearby_amenities'][$type] = hph_bridge_get_nearby_places($listing_id, $type, 2000);
            }
        }
        
        // Cache for 24 hours
        wp_cache_set($cache_key, $neighborhood_data, 'hph_neighborhoods', $api_manager->get_cache_duration('places'));
        
        return $neighborhood_data;
    }
}
