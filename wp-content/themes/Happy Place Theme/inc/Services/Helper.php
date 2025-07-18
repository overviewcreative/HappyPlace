<?php

namespace HappyPlace\Listings;

/**
 * Listing Helper Class
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Helper {
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
     * Format listing address
     */
    public function format_address($listing_id): string {
        // Try full address text field first
        $full_address_text = get_field('full_address', $listing_id);
        if ($full_address_text) {
            return $full_address_text;
        }

        // Fall back to address components
        $address_group = get_field('full_address', $listing_id);
        if ($address_group && is_array($address_group)) {
            $components = [];
            if (!empty($address_group['street_address'])) {
                $components[] = $address_group['street_address'];
            }
            if (!empty($address_group['city'])) {
                $components[] = $address_group['city'];
            }
            if (!empty($address_group['region'])) {
                $components[] = $address_group['region'];
            }
            if (!empty($address_group['postal_code'])) {
                $components[] = $address_group['postal_code'];
            }
            return implode(', ', $components);
        }

        return '';
    }

    /**
     * Get listing bathrooms count
     */
    public function get_bathrooms($listing_id): float {
        $full_baths = get_field('full_bathrooms', $listing_id) ?: 0;
        $partial_baths = get_field('partial_bathrooms', $listing_id) ?: 0;
        return $full_baths + ($partial_baths * 0.5);
    }

    /**
     * Format bathrooms display
     */
    public function format_bathrooms($listing_id): string {
        $bathrooms = $this->get_bathrooms($listing_id);
        return $bathrooms ? number_format($bathrooms, 1) : '0';
    }

    /**
     * Get main photo URL
     */
    public function get_main_photo($listing_id, $size = 'medium'): string {
        $photos = get_field('photos', $listing_id);
        if ($photos && is_array($photos) && !empty($photos)) {
            $main_photo = $photos[0];
            if (is_array($main_photo) && isset($main_photo['sizes'][$size])) {
                return $main_photo['sizes'][$size];
            }
            if (is_array($main_photo) && isset($main_photo['url'])) {
                return $main_photo['url'];
            }
        }
        
        // Fallback to featured image
        if (has_post_thumbnail($listing_id)) {
            return get_the_post_thumbnail_url($listing_id, $size);
        }
        
        return '';
    }

    /**
     * Format price for display
     */
    public function format_price($price, $show_zero = false): string {
        if (!$price && !$show_zero) {
            return '';
        }
        
        $price = (float) $price;
        if ($price >= 1000000) {
            return '$' . number_format($price / 1000000, 1) . 'M';
        }
        if ($price >= 1000) {
            return '$' . number_format($price / 1000, 0) . 'K';
        }
        
        return '$' . number_format($price, 0);
    }

    /**
     * Format square footage
     */
    public function format_sqft($sqft): string {
        if (!$sqft) {
            return '';
        }
        return number_format((int) $sqft) . ' sq ft';
    }

    /**
     * Get listing status
     */
    public function get_status($listing_id): string {
        $status = get_field('listing_status', $listing_id);
        return $status ?: 'active';
    }

    /**
     * Get listing features
     */
    public function get_features($listing_id): array {
        $features = get_field('features', $listing_id);
        return is_array($features) ? $features : [];
    }

    /**
     * Get property types
     */
    public function get_property_types($listing_id): array {
        $terms = get_the_terms($listing_id, 'property_type');
        return is_array($terms) ? $terms : [];
    }

    /**
     * Get primary property type
     */
    public function get_primary_property_type($listing_id): string {
        $types = $this->get_property_types($listing_id);
        return !empty($types) ? $types[0]->name : '';
    }

    /**
     * Get highlight badges
     */
    public function get_highlight_badges($listing_id): array {
        $badges = get_field('highlight_badges', $listing_id);
        return is_array($badges) ? $badges : [];
    }

    /**
     * Get complete listing data array
     */
    public function get_listing_data($listing_id): array {
        $coords = $this->get_coordinates($listing_id);
        
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => get_field('price', $listing_id),
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => $this->get_bathrooms($listing_id),
            'square_footage' => $this->format_sqft(get_field('square_footage', $listing_id)),
            'status' => $this->get_status($listing_id),
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
            'permalink' => get_permalink($listing_id),
            'address' => $this->format_address($listing_id),
            'photo' => $this->get_main_photo($listing_id, 'medium'),
            'property_type' => $this->get_primary_property_type($listing_id),
            'highlight_badges' => $this->get_highlight_badges($listing_id)
        ];
    }

    /**
     * Get listing coordinates
     */
    public function get_coordinates($listing_id): array {
        $lat = get_field('latitude', $listing_id);
        $lng = get_field('longitude', $listing_id);
        
        return [
            'lat' => $lat ? (float) $lat : 0,
            'lng' => $lng ? (float) $lng : 0
        ];
    }

    /**
     * Check if listing has coordinates
     */
    public function has_coordinates($listing_id): bool {
        $coords = $this->get_coordinates($listing_id);
        return $coords['lat'] !== 0 && $coords['lng'] !== 0;
    }

    /**
     * Get map data for listing
     */
    public function get_map_data($listing_id): ?array {
        if (!$this->has_coordinates($listing_id)) {
            return null;
        }
        
        $coords = $this->get_coordinates($listing_id);
        
        return [
            'id' => $listing_id,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'title' => get_the_title($listing_id),
            'price' => $this->format_price(get_field('price', $listing_id)),
            'photo' => $this->get_main_photo($listing_id, 'thumbnail'),
            'url' => get_permalink($listing_id),
            'status' => $this->get_status($listing_id),
            'beds' => get_field('bedrooms', $listing_id),
            'baths' => $this->format_bathrooms($listing_id)
        ];
    }
}

// Backward compatibility is handled by Migration_Helper
