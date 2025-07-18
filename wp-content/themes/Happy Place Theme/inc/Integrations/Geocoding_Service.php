<?php

namespace HappyPlace\Integration;

/**
 * Geocoding Service
 *
 * Handles address geocoding with multiple provider support
 *
 * @package HappyPlace\Integration
 * @since 2.0.0
 */
class Geocoding_Service {
    
    /**
     * Google Maps API key
     * @var string
     */
    protected $google_api_key;
    
    /**
     * Default provider
     * @var string
     */
    protected $default_provider = 'google';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->google_api_key = get_option('hph_google_maps_api_key', '');
    }
    
    /**
     * Geocode an address
     * 
     * @param string $address Address to geocode
     * @param string $provider Geocoding provider
     * @return array|false Geocoding result or false
     */
    public function geocode($address, $provider = null) {
        $provider = $provider ?: $this->default_provider;
        
        try {
            switch ($provider) {
                case 'google':
                    return $this->geocode_google($address);
                    
                case 'nominatim':
                    return $this->geocode_nominatim($address);
                    
                default:
                    throw new Integration_Exception("Unsupported geocoding provider: {$provider}");
            }
        } catch (\Exception $e) {
            error_log("Geocoding failed for address '{$address}': " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Geocode using Google Maps API
     * 
     * @param string $address Address to geocode
     * @return array|false Geocoding result
     */
    protected function geocode_google($address) {
        if (empty($this->google_api_key)) {
            throw new Integration_Exception('Google Maps API key not configured');
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $this->google_api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('Google geocoding request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown error';
            throw new Integration_Exception("Google geocoding failed: {$error_message}");
        }
        
        if (empty($data['results'])) {
            return false;
        }
        
        $result = $data['results'][0];
        $location = $result['geometry']['location'];
        
        return [
            'lat' => $location['lat'],
            'lng' => $location['lng'],
            'formatted_address' => $result['formatted_address'],
            'address_components' => $result['address_components'] ?? [],
            'place_id' => $result['place_id'] ?? '',
            'provider' => 'google'
        ];
    }
    
    /**
     * Geocode using OpenStreetMap Nominatim (free alternative)
     * 
     * @param string $address Address to geocode
     * @return array|false Geocoding result
     */
    protected function geocode_nominatim($address) {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1
        ]);
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Happy Place WordPress Plugin'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('Nominatim geocoding request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return false;
        }
        
        $result = $data[0];
        
        return [
            'lat' => floatval($result['lat']),
            'lng' => floatval($result['lon']),
            'formatted_address' => $result['display_name'],
            'address_components' => $result['address'] ?? [],
            'place_id' => $result['place_id'] ?? '',
            'provider' => 'nominatim'
        ];
    }
    
    /**
     * Reverse geocode coordinates to address
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param string $provider Geocoding provider
     * @return array|false Reverse geocoding result
     */
    public function reverse_geocode($lat, $lng, $provider = null) {
        $provider = $provider ?: $this->default_provider;
        
        try {
            switch ($provider) {
                case 'google':
                    return $this->reverse_geocode_google($lat, $lng);
                    
                case 'nominatim':
                    return $this->reverse_geocode_nominatim($lat, $lng);
                    
                default:
                    throw new Integration_Exception("Unsupported geocoding provider: {$provider}");
            }
        } catch (\Exception $e) {
            error_log("Reverse geocoding failed for coordinates {$lat},{$lng}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reverse geocode using Google Maps API
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array|false Reverse geocoding result
     */
    protected function reverse_geocode_google($lat, $lng) {
        if (empty($this->google_api_key)) {
            throw new Integration_Exception('Google Maps API key not configured');
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'latlng' => "{$lat},{$lng}",
            'key' => $this->google_api_key
        ]);
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('Google reverse geocoding request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['status']) || $data['status'] !== 'OK') {
            $error_message = isset($data['error_message']) ? $data['error_message'] : 'Unknown error';
            throw new Integration_Exception("Google reverse geocoding failed: {$error_message}");
        }
        
        if (empty($data['results'])) {
            return false;
        }
        
        $result = $data['results'][0];
        
        return [
            'formatted_address' => $result['formatted_address'],
            'address_components' => $result['address_components'] ?? [],
            'place_id' => $result['place_id'] ?? '',
            'provider' => 'google'
        ];
    }
    
    /**
     * Reverse geocode using Nominatim
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return array|false Reverse geocoding result
     */
    protected function reverse_geocode_nominatim($lat, $lng) {
        $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
            'lat' => $lat,
            'lon' => $lng,
            'format' => 'json',
            'addressdetails' => 1
        ]);
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Happy Place WordPress Plugin'
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new Integration_Exception('Nominatim reverse geocoding request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data)) {
            return false;
        }
        
        return [
            'formatted_address' => $data['display_name'],
            'address_components' => $data['address'] ?? [],
            'place_id' => $data['place_id'] ?? '',
            'provider' => 'nominatim'
        ];
    }
    
    /**
     * Validate coordinates
     * 
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool Valid coordinates
     */
    public function validate_coordinates($lat, $lng) {
        return is_numeric($lat) && 
               is_numeric($lng) && 
               $lat >= -90 && 
               $lat <= 90 && 
               $lng >= -180 && 
               $lng <= 180;
    }
    
    /**
     * Calculate distance between two points
     * 
     * @param float $lat1 First point latitude
     * @param float $lng1 First point longitude
     * @param float $lat2 Second point latitude
     * @param float $lng2 Second point longitude
     * @param string $unit Distance unit (miles or km)
     * @return float Distance
     */
    public function calculate_distance($lat1, $lng1, $lat2, $lng2, $unit = 'miles') {
        $earth_radius = $unit === 'km' ? 6371 : 3959; // km or miles
        
        $delta_lat = deg2rad($lat2 - $lat1);
        $delta_lng = deg2rad($lng2 - $lng1);
        
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($delta_lng / 2) * sin($delta_lng / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }
}
