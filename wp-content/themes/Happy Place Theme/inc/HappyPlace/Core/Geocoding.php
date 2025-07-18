<?php
/**
 * Geocoding Handler Class
 *
 * Handles geocoding operations for listings
 *
 * @package HappyPlace
 * @subpackage Core
 */

namespace HappyPlace\Core;

if (!defined('ABSPATH')) {
    exit;
}

class Geocoding {
    private static ?self $instance = null;
    private string $api_key;

    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->api_key = get_option('hph_google_maps_api_key', '');
        add_action('acf/save_post', [$this, 'maybe_geocode_listing'], 20);
    }

    public function maybe_geocode_listing(int $post_id): void {
        // Only run for listings
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Get the formatted address
        $address = $this->get_formatted_address($post_id);
        if (empty($address)) {
            return;
        }

        // Check if we need to geocode
        $current_lat = get_field('latitude', $post_id);
        $current_lng = get_field('longitude', $post_id);
        
        if (!empty($current_lat) && !empty($current_lng)) {
            return;
        }

        // Geocode the address
        $coordinates = $this->geocode_address($address);
        if (!empty($coordinates)) {
            update_field('latitude', $coordinates['lat'], $post_id);
            update_field('longitude', $coordinates['lng'], $post_id);
        }
    }

    private function get_formatted_address(int $post_id): string {
        $address_parts = [];

        // Get address components
        $street = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip = get_field('zip_code', $post_id);

        if ($street) $address_parts[] = $street;
        if ($city) $address_parts[] = $city;
        if ($state) $address_parts[] = $state;
        if ($zip) $address_parts[] = $zip;

        return implode(', ', $address_parts);
    }

    public function geocode_address(string $address): ?array {
        if (empty($this->api_key) || empty($address)) {
            return null;
        }

        $url = add_query_arg([
            'address' => urlencode($address),
            'key' => $this->api_key
        ], 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data['results'][0]['geometry']['location'])) {
            return null;
        }

        return [
            'lat' => $data['results'][0]['geometry']['location']['lat'],
            'lng' => $data['results'][0]['geometry']['location']['lng']
        ];
    }

    public function force_geocode_listing(int $post_id): bool {
        if (get_post_type($post_id) !== 'listing') {
            return false;
        }

        $address = $this->get_formatted_address($post_id);
        if (empty($address)) {
            return false;
        }

        $coordinates = $this->geocode_address($address);
        if (empty($coordinates)) {
            return false;
        }

        update_field('latitude', $coordinates['lat'], $post_id);
        update_field('longitude', $coordinates['lng'], $post_id);

        return true;
    }
}
