<?php
/**
 * Geocoding functionality for listings
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Geocoding {
    private static ?self $instance = null;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into ACF save actions
        add_action('acf/save_post', [$this, 'maybe_geocode_listing'], 20);
    }

    /**
     * Geocode listing when address fields are updated
     */
    public function maybe_geocode_listing($post_id) {
        // Only run for listings
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Get the formatted address
        $address = hph_listing()->format_address($post_id);
        if (empty($address)) {
            return;
        }

        // Check if we need to geocode
        $current_lat = get_field('latitude', $post_id);
        $current_lng = get_field('longitude', $post_id);
        
        if (!empty($current_lat) && !empty($current_lng)) {
            return; // Coordinates already exist
        }

        // Geocode the address
        $coordinates = $this->geocode_address($address);
        if (!empty($coordinates)) {
            update_field('latitude', $coordinates['lat'], $post_id);
            update_field('longitude', $coordinates['lng'], $post_id);
        }
    }

    /**
     * Geocode an address using Google Maps API
     */
    private function geocode_address(string $address): ?array {
        $api_key = get_option('hph_google_maps_api_key');
        if (empty($api_key) || empty($address)) {
            return null;
        }

        // Build the API URL
        $url = add_query_arg([
            'address' => urlencode($address),
            'key' => $api_key
        ], 'https://maps.googleapis.com/maps/api/geocode/json');

        // Make the request
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return null;
        }

        // Parse the response
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['results'][0]['geometry']['location'])) {
            return null;
        }

        $location = $data['results'][0]['geometry']['location'];
        return [
            'lat' => $location['lat'],
            'lng' => $location['lng']
        ];
    }

    /**
     * Force geocode a listing
     */
    public function force_geocode_listing($post_id): bool {
        // Get the formatted address
        $address = hph_listing()->format_address($post_id);
        if (empty($address)) {
            return false;
        }

        // Geocode the address
        $coordinates = $this->geocode_address($address);
        if (empty($coordinates)) {
            return false;
        }

        // Update the coordinates
        update_field('latitude', $coordinates['lat'], $post_id);
        update_field('longitude', $coordinates['lng'], $post_id);

        return true;
    }
}

// Initialize the geocoding handler
function hph_geocoding(): HPH_Geocoding {
    return HPH_Geocoding::instance();
}
