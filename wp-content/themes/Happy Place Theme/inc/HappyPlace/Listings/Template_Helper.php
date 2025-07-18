<?php

namespace HappyPlace\Listings;

/**
 * Template Helper Class
 * 
 * Provides helper functions for listing templates
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Template_Helper {
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Format price with proper currency formatting
     */
    public function format_price($price): string {
        if (!$price) {
            return '';
        }
        return '$' . number_format($price);
    }

    /**
     * Get formatted address from listing
     */
    public function get_formatted_address($listing_id): string {
        // Try full address field first
        $full_address = get_field('full_address', $listing_id);
        if ($full_address) {
            return $full_address;
        }

        // Build from components
        $components = [];
        $street = get_field('street_address', $listing_id);
        $city = get_field('city', $listing_id);
        $state = get_field('region', $listing_id);
        $zip = get_field('zip_code', $listing_id);

        if ($street) $components[] = $street;
        if ($city) $components[] = $city;
        if ($state) $components[] = $state;
        if ($zip) $components[] = $zip;

        return implode(', ', $components);
    }

    /**
     * Get property features as array
     */
    public function get_property_features($listing_id): array {
        $features = [];
        
        // Interior features
        $interior = get_field('features', $listing_id);
        if (is_array($interior)) {
            $features = array_merge($features, array_filter($interior));
        }

        // Exterior features
        $exterior = get_field('exterior_features', $listing_id);
        if (is_array($exterior)) {
            $features = array_merge($features, array_filter($exterior));
        }

        // Utility features
        $utilities = get_field('utility_features', $listing_id);
        if (is_array($utilities)) {
            $features = array_merge($features, array_filter($utilities));
        }

        return array_unique($features);
    }

    /**
     * Get property main image
     */
    public function get_main_image($listing_id): string {
        // Try main photo field
        $main_photo = get_field('main_photo', $listing_id);
        if ($main_photo) {
            return is_array($main_photo) ? $main_photo['url'] : $main_photo;
        }

        // Try gallery first image
        $gallery = get_field('photo_gallery', $listing_id);
        if ($gallery && is_array($gallery) && !empty($gallery)) {
            $first_image = reset($gallery);
            return is_array($first_image) ? $first_image['url'] : $first_image;
        }

        // Try featured image
        if (has_post_thumbnail($listing_id)) {
            return get_the_post_thumbnail_url($listing_id, 'large');
        }

        // Fallback to placeholder
        return get_theme_file_uri('assets/images/property-placeholder.jpg');
    }

    /**
     * Get property status with formatting
     */
    public function get_status_display($listing_id): array {
        $status = get_field('status', $listing_id) ?: 'Active';
        
        return [
            'status' => $status,
            'class' => sanitize_html_class(strtolower($status)),
            'display' => ucfirst($status)
        ];
    }

    /**
     * Get property type display
     */
    public function get_property_type_display($listing_id): string {
        $terms = get_the_terms($listing_id, 'property_type');
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        return '';
    }

    /**
     * Get property details for display
     */
    public function get_property_details($listing_id): array {
        return [
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => get_field('bathrooms', $listing_id),
            'square_feet' => get_field('square_footage', $listing_id),
            'lot_size' => get_field('lot_size', $listing_id),
            'year_built' => get_field('year_built', $listing_id),
            'garage' => get_field('garage', $listing_id),
            'mls_number' => get_field('mls_number', $listing_id),
            'price_per_sqft' => get_field('price_per_sqft', $listing_id)
        ];
    }

    /**
     * Check if listing is in user favorites
     */
    public function is_favorite($listing_id): bool {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'hph_favorites', true);
        
        if (empty($favorites)) {
            return false;
        }

        $favorites_array = explode(',', $favorites);
        return in_array($listing_id, $favorites_array);
    }

    /**
     * Get listing agent information
     */
    public function get_listing_agent($listing_id): ?array {
        // Try direct agent field
        $agent_id = get_field('agent', $listing_id);
        
        if (!$agent_id) {
            // Try finding agent who manages this listing
            $agents = get_posts([
                'post_type' => 'agent',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'managed_listings',
                        'value' => '"' . $listing_id . '"',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);

            if ($agents) {
                $agent_id = $agents[0]->ID;
            }
        }

        if (!$agent_id) {
            return null;
        }

        return [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'image' => get_field('profile_photo', $agent_id),
            'phone' => get_field('phone', $agent_id),
            'email' => get_field('email', $agent_id),
            'license' => get_field('license_number', $agent_id),
            'license_state' => get_field('license_state', $agent_id),
            'contact_prefs' => get_field('contact_preferences', $agent_id),
            'social_links' => get_field('social_links', $agent_id),
            'certifications' => get_field('certifications', $agent_id),
            'schedule_link' => get_field('schedule_link', $agent_id),
            'chat_link' => get_field('chat_link', $agent_id),
            'permalink' => get_permalink($agent_id)
        ];
    }

    /**
     * Get similar listings
     */
    public function get_similar_listings($listing_id, $limit = 3): array {
        $property_types = get_the_terms($listing_id, 'property_type');
        $price = get_field('price', $listing_id);
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => $limit,
            'post__not_in' => [$listing_id],
            'meta_query' => []
        ];

        // Add property type filter
        if ($property_types && !is_wp_error($property_types)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'property_type',
                    'field' => 'term_id',
                    'terms' => array_map(function($term) { return $term->term_id; }, $property_types)
                ]
            ];
        }

        // Add price range filter (±20%)
        if ($price) {
            $price_min = $price * 0.8;
            $price_max = $price * 1.2;
            
            $args['meta_query'][] = [
                'key' => 'price',
                'value' => [$price_min, $price_max],
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            ];
        }

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Get property coordinates for map display
     */
    public function get_coordinates($listing_id): ?array {
        $latitude = get_field('latitude', $listing_id);
        $longitude = get_field('longitude', $listing_id);

        if ($latitude && $longitude) {
            return [
                'lat' => floatval($latitude),
                'lng' => floatval($longitude)
            ];
        }

        return null;
    }

    /**
     * Format square footage display
     */
    public function format_square_feet($sqft): string {
        if (!$sqft) {
            return '';
        }
        return number_format($sqft) . ' sq ft';
    }

    /**
     * Format lot size display
     */
    public function format_lot_size($lot_size): string {
        if (!$lot_size) {
            return '';
        }
        return $lot_size . ' acres';
    }
}
