<?php
/**
 * Enhanced Listing Bridge Functions (Phase 1 Day 5-7)
 * 
 * Updated bridge functions for new field structure while maintaining backward compatibility
 *
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get listing price - Updated for v2 field structure
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to format the price for display
 * @return string|float Formatted price string or raw price value
 */
if (!function_exists('hph_get_listing_price')) {
    function hph_get_listing_price($listing_id, $formatted = true) {
        // Try new v2 field first, fallback to old field
        $price = get_field('price', $listing_id) ?: get_field('listing_price', $listing_id);
        
        if (empty($price)) {
            return '';
        }
        
        return $formatted ? hph_format_price($price) : $price;
    }
}

/**
 * Get original listing price (NEW - v2 only)
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to format the price for display
 * @return string|float Formatted price string or raw price value
 */
if (!function_exists('hph_get_original_price')) {
    function hph_get_original_price($listing_id, $formatted = true) {
        $price = get_field('original_price', $listing_id);
        
        if (empty($price)) {
            return '';
        }
        
        return $formatted ? hph_format_price($price) : $price;
    }
}

/**
 * Get calculated price per square foot (NEW - v2 only)
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to format for display
 * @return string|float Formatted price per sqft or raw value
 */
if (!function_exists('hph_get_price_per_sqft')) {
    function hph_get_price_per_sqft($listing_id, $formatted = true) {
        $price_per_sqft = get_field('price_per_sqft', $listing_id);
        
        if (empty($price_per_sqft)) {
            return '';
        }
        
        return $formatted ? '$' . number_format($price_per_sqft, 2) . '/sqft' : $price_per_sqft;
    }
}

/**
 * Get calculated days on market (NEW - v2 only)
 * @param int $listing_id Listing post ID
 * @return int Days on market
 */
if (!function_exists('hph_get_days_on_market')) {
    function hph_get_days_on_market($listing_id) {
        $days = get_field('days_on_market', $listing_id);
        return $days ? intval($days) : 0;
    }
}

/**
 * Get listing status - Updated for v2 field structure
 * @param int $listing_id Listing post ID
 * @return string Listing status (Active, Pending, Sold, etc.)
 */
if (!function_exists('hph_get_listing_status')) {
    function hph_get_listing_status($listing_id) {
        // Try new v2 field first, fallback to old field
        $status = get_field('listing_status', $listing_id) ?: get_field('listing_status_old', $listing_id);
        return $status ? $status : 'Active';
    }
}

/**
 * Get listing address components - Enhanced for v2 address parsing
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to return formatted address string
 * @return string|array Formatted address string or array of components
 */
if (!function_exists('hph_get_listing_address')) {
    function hph_get_listing_address($listing_id, $formatted = true) {
        // Try new v2 fields first
        $street = get_field('street_address', $listing_id) ?: get_field('listing_street_address', $listing_id);
        $city = get_field('city', $listing_id) ?: get_field('listing_city', $listing_id);
        $state = get_field('state', $listing_id) ?: get_field('listing_state', $listing_id);
        $zip = get_field('zip_code', $listing_id) ?: get_field('listing_zip_code', $listing_id);
        $unit = get_field('unit_number', $listing_id);
        
        if ($formatted) {
            return hph_format_address($street, $city, $state, $zip, $unit);
        }
        
        return [
            'street' => $street,
            'unit' => $unit,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'full_address' => get_field('full_address', $listing_id), // v2 compatibility field
            'unparsed_address' => get_field('unparsed_address', $listing_id) // v2 MLS format
        ];
    }
}

/**
 * Get address components (NEW - v2 address parsing)
 * @param int $listing_id Listing post ID
 * @return array Parsed address components
 */
if (!function_exists('hph_get_address_components')) {
    function hph_get_address_components($listing_id) {
        return [
            'street_number' => get_field('street_number', $listing_id),
            'street_dir_prefix' => get_field('street_dir_prefix', $listing_id),
            'street_name' => get_field('street_name', $listing_id),
            'street_suffix' => get_field('street_suffix', $listing_id),
            'street_dir_suffix' => get_field('street_dir_suffix', $listing_id),
            'unit_number' => get_field('unit_number', $listing_id)
        ];
    }
}

/**
 * Get listing features - Updated for v2 bathroom calculations
 * @param int $listing_id Listing post ID
 * @return array Listing features with calculated values
 */
if (!function_exists('hph_get_listing_features')) {
    function hph_get_listing_features($listing_id) {
        $cache_key = 'listing_features_v2_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $features = [
            // Core features with v2 field names and fallbacks
            'bedrooms' => get_field('bedrooms', $listing_id) ?: get_field('listing_bedrooms', $listing_id),
            'bathrooms_full' => get_field('bathrooms_full', $listing_id),
            'bathrooms_half' => get_field('bathrooms_half', $listing_id),
            'bathrooms_total' => get_field('bathrooms_total', $listing_id), // v2 calculated field
            'bathrooms' => get_field('bathrooms_total', $listing_id) ?: get_field('listing_bathrooms', $listing_id), // backward compatibility
            'square_feet' => get_field('square_footage', $listing_id) ?: get_field('listing_square_feet', $listing_id),
            'square_footage' => get_field('square_footage', $listing_id), // v2 field name
            'lot_size' => get_field('lot_size', $listing_id) ?: get_field('listing_lot_size', $listing_id),
            'lot_sqft' => get_field('lot_sqft', $listing_id), // v2 calculated field
            'year_built' => get_field('year_built', $listing_id) ?: get_field('listing_year_built', $listing_id),
            
            // v2 additional features
            'property_type' => get_field('property_type', $listing_id),
            'property_style' => get_field('property_style', $listing_id),
            'property_condition' => get_field('property_condition', $listing_id),
            'stories' => get_field('stories', $listing_id),
            'garage_spaces' => get_field('garage_spaces', $listing_id),
            'parking_spaces' => get_field('parking_spaces', $listing_id),
            'basement' => get_field('basement', $listing_id),
            'fireplace_count' => get_field('fireplace_count', $listing_id),
            'pool' => get_field('pool', $listing_id)
        ];
        
        // Cache for 2 hours
        wp_cache_set($cache_key, $features, 'hph_listings', 7200);
        
        return $features;
    }
}

/**
 * Get property type (NEW - v2 field)
 * @param int $listing_id Listing post ID
 * @return string Property type
 */
if (!function_exists('hph_get_property_type')) {
    function hph_get_property_type($listing_id) {
        return get_field('property_type', $listing_id) ?: 'Single Family';
    }
}

/**
 * Get bedrooms count - Updated for v2
 * @param int $listing_id Listing post ID
 * @return int Number of bedrooms
 */
if (!function_exists('hph_get_bedrooms')) {
    function hph_get_bedrooms($listing_id) {
        $bedrooms = get_field('bedrooms', $listing_id) ?: get_field('listing_bedrooms', $listing_id);
        return $bedrooms ? intval($bedrooms) : 0;
    }
}

/**
 * Get bathrooms count - Updated for v2 calculated total
 * @param int $listing_id Listing post ID
 * @param string $type 'total'|'full'|'half' - type of bathroom count
 * @return float Bathroom count
 */
if (!function_exists('hph_get_bathrooms')) {
    function hph_get_bathrooms($listing_id, $type = 'total') {
        switch ($type) {
            case 'full':
                return floatval(get_field('bathrooms_full', $listing_id));
            case 'half':
                return floatval(get_field('bathrooms_half', $listing_id));
            case 'total':
            default:
                // Use v2 calculated field first, fallback to old field
                $total = get_field('bathrooms_total', $listing_id);
                if ($total) {
                    return floatval($total);
                }
                return floatval(get_field('listing_bathrooms', $listing_id));
        }
    }
}

/**
 * Get lot details (NEW - v2 with auto-conversion)
 * @param int $listing_id Listing post ID
 * @return array Lot size in both acres and square feet
 */
if (!function_exists('hph_get_lot_details')) {
    function hph_get_lot_details($listing_id) {
        return [
            'acres' => get_field('lot_size', $listing_id),
            'square_feet' => get_field('lot_sqft', $listing_id), // v2 calculated field
            'sqft_source' => get_field('sqft_source', $listing_id)
        ];
    }
}

/**
 * Get comprehensive room summary (NEW - v2)
 * @param int $listing_id Listing post ID
 * @return array Complete room information
 */
if (!function_exists('hph_get_room_summary')) {
    function hph_get_room_summary($listing_id) {
        return [
            'bedrooms' => get_field('bedrooms', $listing_id),
            'bathrooms_full' => get_field('bathrooms_full', $listing_id),
            'bathrooms_half' => get_field('bathrooms_half', $listing_id),
            'bathrooms_total' => get_field('bathrooms_total', $listing_id),
            'rooms_total' => get_field('rooms_total', $listing_id),
            'stories' => get_field('stories', $listing_id)
        ];
    }
}

/**
 * Get market metrics (NEW - v2 calculated fields)
 * @param int $listing_id Listing post ID
 * @return array Market analysis data
 */
if (!function_exists('hph_get_market_metrics')) {
    function hph_get_market_metrics($listing_id) {
        return [
            'current_price' => get_field('price', $listing_id),
            'original_price' => get_field('original_price', $listing_id),
            'price_per_sqft' => get_field('price_per_sqft', $listing_id),
            'days_on_market' => get_field('days_on_market', $listing_id),
            'price_changes' => get_field('price_change_count', $listing_id),
            'last_price_change' => get_field('last_price_change_date', $listing_id),
            'status_change_date' => get_field('status_change_date', $listing_id)
        ];
    }
}

/**
 * Get county information (NEW - v2 auto-populated)
 * @param int $listing_id Listing post ID
 * @return string County name
 */
if (!function_exists('hph_get_county')) {
    function hph_get_county($listing_id) {
        return get_field('county', $listing_id) ?: '';
    }
}

/**
 * Get listing coordinates - Enhanced for v2
 * @param int $listing_id Listing post ID
 * @return array Latitude and longitude
 */
if (!function_exists('hph_bridge_get_coordinates')) {
    function hph_bridge_get_coordinates($listing_id) {
        // Try v2 fields first, fallback to old fields
        $lat = get_field('latitude', $listing_id) ?: get_field('listing_latitude', $listing_id);
        $lng = get_field('longitude', $listing_id) ?: get_field('listing_longitude', $listing_id);
        
        return [
            'latitude' => $lat ? floatval($lat) : null,
            'longitude' => $lng ? floatval($lng) : null,
            'accuracy' => get_field('geocoding_accuracy', $listing_id) // v2 field
        ];
    }
}

/**
 * Get listing data with enhanced v2 support
 * @param int $listing_id Listing post ID
 * @param array $fields Specific fields to retrieve
 * @return array Comprehensive listing data
 */
if (!function_exists('hph_get_listing_data')) {
    function hph_get_listing_data($listing_id, $fields = []) {
        if (empty($listing_id)) {
            return false;
        }

        $cache_key = 'listing_data_v2_' . $listing_id . '_' . md5(serialize($fields));
        $cached_data = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Get comprehensive listing data
        $data = [
            'id' => $listing_id,
            'price' => hph_get_listing_price($listing_id, false),
            'price_formatted' => hph_get_listing_price($listing_id, true),
            'original_price' => hph_get_original_price($listing_id, false),
            'price_per_sqft' => hph_get_price_per_sqft($listing_id, false),
            'status' => hph_get_listing_status($listing_id),
            'days_on_market' => hph_get_days_on_market($listing_id),
            'address' => hph_get_listing_address($listing_id, false),
            'address_formatted' => hph_get_listing_address($listing_id, true),
            'address_components' => hph_get_address_components($listing_id),
            'features' => hph_get_listing_features($listing_id),
            'coordinates' => hph_bridge_get_coordinates($listing_id),
            'market_metrics' => hph_get_market_metrics($listing_id),
            'county' => hph_get_county($listing_id)
        ];

        // Filter to specific fields if requested
        if (!empty($fields)) {
            $data = array_intersect_key($data, array_flip($fields));
        }

        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'hph_listings', 3600);
        
        return $data;
    }
}

/**
 * Get listing images (maintained for compatibility)
 * @param int $listing_id Listing post ID
 * @param string $size Image size
 * @return array Image data
 */
if (!function_exists('hph_get_listing_images')) {
    function hph_get_listing_images($listing_id, $size = 'medium') {
        $cache_key = 'listing_images_v2_' . $listing_id . '_' . $size;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Try v2 field first, fallback to old field
        $images = get_field('listing_photos', $listing_id) ?: get_field('listing_images', $listing_id);
        $processed_images = [];
        
        if ($images && is_array($images)) {
            foreach ($images as $image) {
                if (is_array($image)) {
                    $processed_images[] = [
                        'url' => $image['sizes'][$size] ?? $image['url'],
                        'alt' => $image['alt'] ?? '',
                        'title' => $image['title'] ?? ''
                    ];
                }
            }
        }
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $processed_images, 'hph_listings', 3600);
        
        return $processed_images;
    }
}

/**
 * Helper function to format address
 * @param string $street Street address
 * @param string $city City
 * @param string $state State
 * @param string $zip ZIP code
 * @param string $unit Unit number (optional)
 * @return string Formatted address
 */
if (!function_exists('hph_format_address')) {
    function hph_format_address($street, $city, $state, $zip, $unit = '') {
        $parts = array_filter([
            $street,
            $unit ? "Unit {$unit}" : '',
            $city,
            $state,
            $zip
        ]);
        
        return implode(', ', $parts);
    }
}

/**
 * Helper function to format price
 * @param float $price Price value
 * @return string Formatted price
 */
if (!function_exists('hph_format_price')) {
    function hph_format_price($price) {
        if (empty($price)) {
            return '';
        }
        
        return '$' . number_format($price, 0);
    }
}
