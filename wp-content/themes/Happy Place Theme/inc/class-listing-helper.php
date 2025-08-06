<?php
/**
 * Listing Helper Class
 * 
 * @package HappyPlace
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Listing_Helper {
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
            if (!empty($address_group['zip_code'])) {
                $components[] = $address_group['zip_code'];
            }
            return implode(', ', array_filter($components));
        }

        return '';
    }

    /**
     * Get listing bathrooms count
     */
    public function get_bathrooms($listing_id): float {
        $full_baths = get_field('full_bathrooms', $listing_id) ?: 0;
        $partial_baths = get_field('partial_bathrooms', $listing_id) ?: 0;
        return floatval($full_baths) + (floatval($partial_baths) * 0.5);
    }

    /**
     * Format bathrooms display
     */
    public function format_bathrooms($listing_id): string {
        $baths = $this->get_bathrooms($listing_id);
        return number_format($baths, 1) . ' ' . _n('Bath', 'Baths', $baths, 'happy-place');
    }

    /**
     * Get listing main photo URL
     */
    public function get_main_photo($listing_id, $size = 'medium'): string {
        // Try main photo field first
        $main_photo = get_field('main_photo', $listing_id);
        if ($main_photo) {
            return $main_photo;
        }

        // Try gallery
        $gallery = get_field('photo_gallery', $listing_id);
        if ($gallery && !empty($gallery)) {
            return $gallery[0]['sizes'][$size] ?? $gallery[0]['url'];
        }

        // Try featured image
        if (has_post_thumbnail($listing_id)) {
            return get_the_post_thumbnail_url($listing_id, $size);
        }

        // Fall back to placeholder
        return get_theme_file_uri('assets/images/property-placeholder.jpg');
    }

    /**
     * Format price display
     */
    public function format_price($price, $show_zero = false): string {
        if (!$price && !$show_zero) {
            return '';
        }
        return '$' . number_format(floatval($price));
    }

    /**
     * Format square footage display
     */
    public function format_sqft($sqft): string {
        if (!$sqft) {
            return '';
        }
        return number_format($sqft) . ' ' . __('sq ft', 'happy-place');
    }

    /**
     * Get listing status
     */
    public function get_status($listing_id): string {
        $status = get_field('status', $listing_id);
        return is_array($status) ? ($status[0] ?? '') : (string)$status;
    }

    /**
     * Get formatted property features
     */
    public function get_features($listing_id): array {
        $features = get_field('features', $listing_id);
        if (!$features || !is_array($features)) {
            return [];
        }

        $all_features = [];
        
        // Combine all feature types
        foreach (['utility_features', 'exterior_features', 'interior_features'] as $type) {
            if (!empty($features[$type]) && is_array($features[$type])) {
                $all_features = array_merge($all_features, $features[$type]);
            }
        }

        return array_filter($all_features);
    }

    /**
     * Get property type(s)
     */
    public function get_property_types($listing_id): array {
        $types = get_field('property_type', $listing_id);
        return is_array($types) ? $types : [];
    }

    /**
     * Get primary property type
     */
    public function get_primary_property_type($listing_id): string {
        $types = $this->get_property_types($listing_id);
        return reset($types) ?: '';
    }

    /**
     * Get highlight badges
     */
    public function get_highlight_badges($listing_id): array {
        $badges = get_field('highlight_badges', $listing_id);
        return is_array($badges) ? $badges : [];
    }

    /**
     * Get complete listing data
     */
    public function get_listing_data($listing_id): array {
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => get_field('price', $listing_id),
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms' => $this->get_bathrooms($listing_id),
            'square_footage' => get_field('square_footage', $listing_id),
            'lot_size' => get_field('lot_size', $listing_id),
            'year_built' => get_field('year_built', $listing_id),
            'status' => $this->get_status($listing_id),
            'property_types' => $this->get_property_types($listing_id),
            'address' => $this->format_address($listing_id),
            'photo' => $this->get_main_photo($listing_id),
            'gallery' => get_field('photo_gallery', $listing_id),
            'price_per_sqft' => get_field('price_per_sqft', $listing_id),
            'highlight_badges' => $this->get_highlight_badges($listing_id),
            'features' => $this->get_features($listing_id),
            'virtual_tour_link' => get_field('virtual_tour_link', $listing_id),
            'mls_number' => get_field('mls_number', $listing_id),
            'short_description' => get_field('short_description', $listing_id),
            'latitude' => floatval(get_field('latitude', $listing_id)),
            'longitude' => floatval(get_field('longitude', $listing_id)),
            'permalink' => get_permalink($listing_id),
            'agent' => get_field('agent', $listing_id),
            'community' => get_field('community', $listing_id)
        ];
    }

    /**
     * Get listing coordinates
     */
    public function get_coordinates($listing_id): array {
        return [
            'lat' => floatval(get_field('latitude', $listing_id)),
            'lng' => floatval(get_field('longitude', $listing_id))
        ];
    }

    /**
     * Check if listing has valid coordinates
     */
    public function has_coordinates($listing_id): bool {
        $coords = $this->get_coordinates($listing_id);
        return !empty($coords['lat']) && !empty($coords['lng']) &&
               $coords['lat'] != 0 && $coords['lng'] != 0;
    }

    /**
     * Get map marker data with guaranteed coordinates
     */
    public function get_map_data($listing_id): ?array {
        // Skip if no coordinates
        if (!$this->has_coordinates($listing_id)) {
            return null;
        }

        $coords = $this->get_coordinates($listing_id);
        
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => $this->format_price(get_field('price', $listing_id)),
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
}

// Initialize the helper
function hph_listing(): HPH_Listing_Helper {
    return HPH_Listing_Helper::instance();
}
