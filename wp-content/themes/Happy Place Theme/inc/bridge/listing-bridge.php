<?php
/**
 * Listing Bridge Functions
 * 
 * Provides listing-related data access with caching and fallbacks
 *
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get listing price with v1/v2 compatibility
 * @param int $listing_id Listing post ID
 * @param string $format Format type: 'display', 'raw', 'short'
 * @return string|float Formatted price string or raw price value
 */
if (!function_exists('hph_get_listing_price')) {
    function hph_get_listing_price($listing_id, $format = 'display') {
        // Try v2 field first (from Essential Listing Information group)
        $price = get_field('price', $listing_id);
        
        // Fallback to v1 field
        if (empty($price)) {
            $price = get_field('listing_price', $listing_id);
        }
        
        if (empty($price)) {
            return $format === 'raw' ? 0 : '';
        }
        
        // Clean and validate price
        $price = preg_replace('/[^0-9.]/', '', $price);
        $price = floatval($price);
        
        switch ($format) {
            case 'raw':
                return $price;
            case 'short':
                return $price >= 1000000 ? 
                    '$' . number_format($price / 1000000, 1) . 'M' : 
                    '$' . number_format($price / 1000, 0) . 'K';
            case 'display':
            default:
                return hph_format_price($price);
        }
    }
}

/**
 * Get listing data with fallback support
 * @param int $listing_id Listing post ID
 * @param array $fields Specific fields to retrieve
 * @return array Listing data
 */
if (!function_exists('hph_get_listing_data')) {
    function hph_get_listing_data($listing_id, $fields = []) {
    if (empty($listing_id)) {
        return false;
    }

    $cache_key = 'listing_data_' . $listing_id . '_' . md5(serialize($fields));
    $cached_data = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached_data !== false) {
        return $cached_data;
    }

    // Use data provider contract system
    $provider = hph_get_data_provider();
    $data = $provider->get_listing_data($listing_id);

    // Cache for 1 hour
    wp_cache_set($cache_key, $data, 'hph_listings', 3600);
    
    return $data;
    }
}

/**
 * Get listing status with v1/v2 compatibility
 * @param int $listing_id Listing post ID
 * @return array Status information with additional details
 */
if (!function_exists('hph_get_listing_status')) {
    function hph_get_listing_status($listing_id) {
        // Try v2 field first
        $status = get_field('listing_status', $listing_id);
        
        // Keep v1 fallback for backward compatibility
        if (empty($status)) {
            $status = get_field('old_listing_status', $listing_id);
        }
        
        // Default status
        if (empty($status)) {
            $status = 'active';
        }
        
        $status = strtolower(trim($status));
        
        // Status mapping for consistent display
        $status_map = [
            'active' => ['label' => 'Active', 'class' => 'status-active'],
            'pending' => ['label' => 'Pending', 'class' => 'status-pending'],
            'sold' => ['label' => 'Sold', 'class' => 'status-sold'],
            'withdrawn' => ['label' => 'Withdrawn', 'class' => 'status-withdrawn'],
            'expired' => ['label' => 'Expired', 'class' => 'status-expired']
        ];
        
        $mapped = isset($status_map[$status]) ? $status_map[$status] : $status_map['active'];
        
        return [
            'raw' => $status,
            'label' => $mapped['label'],
            'class' => $mapped['class'],
            'is_available' => in_array($status, ['active', 'pending'])
        ];
    }
}

/**
 * Get listing address components with v1/v2 compatibility and enhanced parsing
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to return formatted address or components
 * @return string|array Formatted address or address components
 */
function hph_get_listing_address($listing_id, $formatted = true) {
    // Try v2 fields first (from Location & Address Intelligence group - Phase 2)
    $street = get_field('street_address', $listing_id);
    $city = get_field('city', $listing_id);
    $state = get_field('state', $listing_id);
    $zip = get_field('zip_code', $listing_id);
    
    // Fallback to v1 fields
    if (empty($street) && empty($city)) {
        $street = get_field('listing_street_address', $listing_id);
        $city = get_field('listing_city', $listing_id);
        $state = get_field('listing_state', $listing_id);
        $zip = get_field('listing_zip_code', $listing_id);
    }
    
    // Additional fallback for full address field
    if (empty($street) && empty($city)) {
        $full_address = get_field('listing_address', $listing_id);
        if (!empty($full_address)) {
            // Basic address parsing
            $parts = array_map('trim', explode(',', $full_address));
            $street = isset($parts[0]) ? $parts[0] : '';
            $city_state_zip = isset($parts[1]) ? $parts[1] : '';
            
            if (!empty($city_state_zip)) {
                // Try to extract city, state, zip
                if (preg_match('/^(.+?)\s+([A-Z]{2})\s+(\d{5}(-\d{4})?)$/', $city_state_zip, $matches)) {
                    $city = $matches[1];
                    $state = $matches[2];
                    $zip = $matches[3];
                }
            }
        }
    }
    
    if ($formatted) {
        return hph_format_address($street, $city, $state, $zip);
    }
    
    return [
        'street' => $street ?: '',
        'city' => $city ?: '',
        'state' => $state ?: '',
        'zip' => $zip ?: '',
        'full' => trim(sprintf('%s, %s %s %s', $street, $city, $state, $zip), ', ')
    ];
}

/**
 * Get listing features with v1/v2 compatibility and enhanced calculations
 * @param int $listing_id Listing post ID
 * @return array Property features and room details
 */
function hph_get_listing_features($listing_id) {
    $cache_key = 'listing_features_' . $listing_id;
    $cached = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Try v2 fields first (from Property Details & Classification group - Phase 2)
    $bedrooms = get_field('bedrooms', $listing_id);
    $bathrooms_full = get_field('bathrooms_full', $listing_id);
    $bathrooms_half = get_field('bathrooms_half', $listing_id);
    $bathrooms_total = get_field('bathrooms_total', $listing_id); // calculated field
    $square_feet = get_field('square_footage', $listing_id);
    $lot_size = get_field('lot_size', $listing_id);
    $year_built = get_field('year_built', $listing_id);
    
    // Fallback to v1 fields
    if (empty($bedrooms)) $bedrooms = get_field('listing_bedrooms', $listing_id);
    if (empty($square_feet)) $square_feet = get_field('listing_square_feet', $listing_id);
    if (empty($lot_size)) $lot_size = get_field('listing_lot_size', $listing_id);
    if (empty($year_built)) $year_built = get_field('listing_year_built', $listing_id);
    
    // Handle bathrooms - try to get calculated total first, then calculate if needed
    if (empty($bathrooms_total)) {
        $bathrooms_v1 = get_field('listing_bathrooms', $listing_id);
        if (!empty($bathrooms_v1)) {
            $bathrooms_total = $bathrooms_v1;
        } elseif (!empty($bathrooms_full) || !empty($bathrooms_half)) {
            // Calculate from full + half if we have v2 data
            $bathrooms_total = floatval($bathrooms_full) + (floatval($bathrooms_half) * 0.5);
        }
    }
    
    $features = [
        'bedrooms' => intval($bedrooms),
        'bathrooms' => floatval($bathrooms_total),
        'bathrooms_full' => intval($bathrooms_full),
        'bathrooms_half' => intval($bathrooms_half),
        'square_feet' => intval($square_feet),
        'lot_size' => floatval($lot_size),
        'year_built' => intval($year_built),
        'formatted_bathrooms' => hph_format_bathrooms($bathrooms_total),
        'formatted_sqft' => $square_feet > 0 ? number_format($square_feet) . ' sq ft' : 'N/A'
    ];
    
    // Cache for 2 hours
    wp_cache_set($cache_key, $features, 'hph_listings', 7200);
    
    return $features;
}

/**
 * Get listing images
 */
function hph_get_listing_images($listing_id, $size = 'medium') {
    $cache_key = 'listing_images_' . $listing_id . '_' . $size;
    $cached = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $images = get_field('listing_images', $listing_id);
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

/**
 * ACF Fallback for listing data
 */
function hph_get_listing_acf_fallback($listing_id, $fields = []) {
    if (empty($fields)) {
        // Get all common fields
        $fields = [
            'listing_price',
            'listing_status',
            'listing_street_address',
            'listing_city',
            'listing_state',
            'listing_zip_code',
            'listing_bedrooms',
            'listing_bathrooms',
            'listing_square_feet'
        ];
    }
    
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = get_field($field, $listing_id);
    }
    
    return $data;
}

/**
 * Search listings with caching
 */
function hph_search_listings($args = []) {
    $cache_key = 'listing_search_' . md5(serialize($args));
    $cached = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $defaults = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'meta_query' => []
    ];
    
    $query_args = wp_parse_args($args, $defaults);
    $query = new WP_Query($query_args);
    
    $results = [
        'listings' => $query->posts,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages
    ];
    
    // Cache for 30 minutes
    wp_cache_set($cache_key, $results, 'hph_listings', 1800);
    
    return $results;
}

/**
 * Get days on market with v2 compatibility
 * @param int $listing_id Listing post ID
 * @return int Days on market
 */
if (!function_exists('hph_get_days_on_market')) {
    function hph_get_days_on_market($listing_id) {
        // Try v2 calculated field first
        $days = get_field('days_on_market', $listing_id);
        
        if (!empty($days) && is_numeric($days)) {
            return intval($days);
        }
        
        // Fallback: calculate from list_date
        $list_date = get_field('list_date', $listing_id);
        
        // v1 fallback
        if (empty($list_date)) {
            $list_date = get_field('listing_date', $listing_id);
        }
        
        if (!empty($list_date)) {
            $list_timestamp = is_string($list_date) ? strtotime($list_date) : $list_date;
            if ($list_timestamp) {
                return floor((time() - $list_timestamp) / (24 * 60 * 60));
            }
        }
        
        return 0;
    }
}

/**
 * Get price per square foot with v2 compatibility
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to format for display
 * @return float|string Price per sqft
 */
if (!function_exists('hph_get_price_per_sqft')) {
    function hph_get_price_per_sqft($listing_id, $formatted = true) {
        // Try v2 calculated field first
        $price_per_sqft = get_field('price_per_sqft', $listing_id);
        
        if (!empty($price_per_sqft) && is_numeric($price_per_sqft)) {
            return $formatted ? '$' . number_format($price_per_sqft, 0) . '/sqft' : floatval($price_per_sqft);
        }
        
        // Calculate from price and square footage
        $price = hph_get_listing_price($listing_id, 'raw');
        $features = hph_get_listing_features($listing_id);
        $sqft = $features['square_feet'];
        
        if ($price > 0 && $sqft > 0) {
            $calculated = $price / $sqft;
            return $formatted ? '$' . number_format($calculated, 0) . '/sqft' : $calculated;
        }
        
        return $formatted ? 'N/A' : 0;
    }
}

/**
 * Get original listing price with v2 compatibility
 * @param int $listing_id Listing post ID
 * @param string $format Format type: 'display', 'raw', 'short'
 * @return string|float Original price
 */
if (!function_exists('hph_get_original_price')) {
    function hph_get_original_price($listing_id, $format = 'display') {
        // Try v2 field first
        $original_price = get_field('original_price', $listing_id);
        
        // Fallback to current price if no original price recorded
        if (empty($original_price)) {
            $original_price = hph_get_listing_price($listing_id, 'raw');
        }
        
        if (empty($original_price)) {
            return $format === 'raw' ? 0 : '';
        }
        
        $price = floatval($original_price);
        
        switch ($format) {
            case 'raw':
                return $price;
            case 'short':
                return $price >= 1000000 ? 
                    '$' . number_format($price / 1000000, 1) . 'M' : 
                    '$' . number_format($price / 1000, 0) . 'K';
            case 'display':
            default:
                return hph_format_price($price);
        }
    }
}

/**
 * Get market metrics summary with v2 compatibility
 * @param int $listing_id Listing post ID
 * @return array Market metrics and calculated values
 */
if (!function_exists('hph_get_market_metrics')) {
    function hph_get_market_metrics($listing_id) {
        $cache_key = 'market_metrics_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $current_price = hph_get_listing_price($listing_id, 'raw');
        $original_price = hph_get_original_price($listing_id, 'raw');
        $price_per_sqft = hph_get_price_per_sqft($listing_id, false);
        $days_on_market = hph_get_days_on_market($listing_id);
        $features = hph_get_listing_features($listing_id);
        
        $metrics = [
            'current_price' => $current_price,
            'original_price' => $original_price,
            'price_change' => $current_price - $original_price,
            'price_change_percent' => $original_price > 0 ? 
                round((($current_price - $original_price) / $original_price) * 100, 1) : 0,
            'price_per_sqft' => $price_per_sqft,
            'days_on_market' => $days_on_market,
            'bedrooms' => $features['bedrooms'],
            'bathrooms' => $features['bathrooms'],
            'square_feet' => $features['square_feet'],
            'is_price_reduced' => $current_price < $original_price,
            'is_new_listing' => $days_on_market <= 7,
            'is_stale_listing' => $days_on_market > 90
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $metrics, 'hph_listings', 3600);
        
        return $metrics;
    }
}

/**
 * Get listing summary for cards/previews with v2 compatibility
 * @param int $listing_id Listing post ID
 * @return array Complete listing summary data
 */
if (!function_exists('hph_get_listing_summary')) {
    function hph_get_listing_summary($listing_id) {
        $cache_key = 'listing_summary_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $price = hph_get_listing_price($listing_id, 'display');
        $address = hph_get_listing_address($listing_id, false);
        $features = hph_get_listing_features($listing_id);
        $status = hph_get_listing_status($listing_id);
        $images = hph_get_listing_images($listing_id, 'medium');
        
        $summary = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => $price,
            'address' => $address['full'],
            'street' => $address['street'],
            'city' => $address['city'],
            'state' => $address['state'],
            'zip' => $address['zip'],
            'bedrooms' => $features['bedrooms'],
            'bathrooms' => $features['bathrooms'],
            'square_feet' => $features['square_feet'],
            'formatted_bathrooms' => $features['formatted_bathrooms'],
            'formatted_sqft' => $features['formatted_sqft'],
            'status' => $status['label'],
            'status_class' => $status['class'],
            'status_raw' => $status['raw'],
            'is_available' => $status['is_available'],
            'featured_image' => !empty($images) ? $images[0]['url'] : '',
            'permalink' => get_permalink($listing_id),
            'days_on_market' => hph_get_days_on_market($listing_id),
            'price_per_sqft' => hph_get_price_per_sqft($listing_id, true)
        ];
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $summary, 'hph_listings', 1800);
        
        return $summary;
    }
}

/**
 * Get property details and classification with Phase 2 compatibility
 * @param int $listing_id Listing post ID
 * @return array Property classification and details
 */
if (!function_exists('hph_get_property_details')) {
    function hph_get_property_details($listing_id) {
        $cache_key = 'property_details_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Try Phase 2 fields first (Property Details & Classification group)
        $property_type = get_field('property_type', $listing_id);
        $property_style = get_field('property_style', $listing_id);
        $year_built = get_field('year_built', $listing_id);
        $property_condition = get_field('property_condition', $listing_id);
        $occupancy_type = get_field('occupancy_type', $listing_id);
        $square_footage = get_field('square_footage', $listing_id);
        $living_area = get_field('living_area', $listing_id);
        $sqft_source = get_field('sqft_source', $listing_id);
        $lot_size = get_field('lot_size', $listing_id);
        $lot_sqft = get_field('lot_sqft', $listing_id);
        $lot_size_source = get_field('lot_size_source', $listing_id);
        $stories = get_field('stories', $listing_id);
        $rooms_total = get_field('rooms_total', $listing_id);
        $parking_spaces = get_field('parking_spaces', $listing_id);
        $garage_spaces = get_field('garage_spaces', $listing_id);
        $basement = get_field('basement', $listing_id);
        $fireplace_count = get_field('fireplace_count', $listing_id);
        $pool = get_field('pool', $listing_id);
        $hot_tub_spa = get_field('hot_tub_spa', $listing_id);
        $waterfront = get_field('waterfront', $listing_id);
        
        // Fallback to v1 fields where applicable
        if (empty($property_type)) $property_type = get_field('listing_property_type', $listing_id);
        if (empty($year_built)) $year_built = get_field('listing_year_built', $listing_id);
        if (empty($square_footage)) $square_footage = get_field('listing_square_feet', $listing_id);
        if (empty($lot_size)) $lot_size = get_field('listing_lot_size', $listing_id);
        
        // Property type categorization
        $residential_types = ['Single Family Home', 'Townhouse', 'Condo', 'Multi-Family', 'Mobile Home'];
        $commercial_types = ['Commercial'];
        $land_types = ['Land'];
        
        $category = 'Other';
        if (in_array($property_type, $residential_types)) $category = 'Residential';
        if (in_array($property_type, $commercial_types)) $category = 'Commercial';
        if (in_array($property_type, $land_types)) $category = 'Land';
        
        $details = [
            // Classification
            'property_type' => $property_type ?: '',
            'property_style' => $property_style ?: '',
            'category' => $category,
            'is_residential' => $category === 'Residential',
            'is_commercial' => $category === 'Commercial',
            'is_land' => $category === 'Land',
            'year_built' => intval($year_built),
            'property_condition' => $property_condition ?: '',
            'occupancy_type' => $occupancy_type ?: '',
            
            // Size & Space
            'square_footage' => intval($square_footage),
            'living_area' => intval($living_area),
            'sqft_source' => $sqft_source ?: '',
            'lot_size_acres' => floatval($lot_size),
            'lot_size_sqft' => intval($lot_sqft),
            'lot_size_source' => $lot_size_source ?: '',
            'stories' => intval($stories),
            
            // Rooms & Spaces
            'rooms_total' => intval($rooms_total),
            'parking_spaces' => intval($parking_spaces),
            'garage_spaces' => intval($garage_spaces),
            
            // Features
            'basement' => $basement ?: '',
            'fireplace_count' => intval($fireplace_count),
            'has_pool' => (bool) $pool,
            'has_hot_tub_spa' => (bool) $hot_tub_spa,
            'is_waterfront' => (bool) $waterfront,
            
            // Formatted values
            'formatted_sqft' => $square_footage > 0 ? number_format($square_footage) . ' sq ft' : 'N/A',
            'formatted_lot_size' => $lot_size > 0 ? number_format($lot_size, 2) . ' acres' : 'N/A',
            'formatted_lot_sqft' => $lot_sqft > 0 ? number_format($lot_sqft) . ' sq ft' : 'N/A',
            'age_years' => $year_built > 0 ? (date('Y') - $year_built) : null,
            'formatted_age' => $year_built > 0 ? (date('Y') - $year_built) . ' years old' : 'N/A'
        ];
        
        // Cache for 2 hours
        wp_cache_set($cache_key, $details, 'hph_listings', 7200);
        
        return $details;
    }
}

// Phase 2 Day 4-7: Location & Address Intelligence Bridge Function
if (!function_exists('hph_get_location_intelligence')) {
    function hph_get_location_intelligence($listing_id) {
        static $cache = [];
        
        if (isset($cache[$listing_id])) {
            return $cache[$listing_id];
        }
        
        $location_data = [
            // Primary Address
            'street_address' => get_field('street_address', $listing_id),
            'unit_number' => get_field('unit_number', $listing_id),
            'city' => get_field('city', $listing_id),
            'state' => get_field('state', $listing_id),
            'zip_code' => get_field('zip_code', $listing_id),
            'county' => get_field('county', $listing_id),
            'address_visibility' => get_field('address_visibility', $listing_id),
            
            // Address Components
            'street_number' => get_field('street_number', $listing_id),
            'street_dir_prefix' => get_field('street_dir_prefix', $listing_id),
            'street_name' => get_field('street_name', $listing_id),
            'street_suffix' => get_field('street_suffix', $listing_id),
            'street_dir_suffix' => get_field('street_dir_suffix', $listing_id),
            
            // Geographic Data
            'latitude' => get_field('latitude', $listing_id),
            'longitude' => get_field('longitude', $listing_id),
            'geocoding_accuracy' => get_field('geocoding_accuracy', $listing_id),
            'geocoding_source' => get_field('geocoding_source', $listing_id),
            'parcel_number' => get_field('parcel_number', $listing_id),
            'walkability_score' => get_field('walkability_score', $listing_id),
            'transit_score' => get_field('transit_score', $listing_id),
            
            // Neighborhood & Community
            'neighborhood' => get_field('neighborhood', $listing_id),
            'community_relation' => get_field('community_relation', $listing_id),
            'city_relation' => get_field('city_relation', $listing_id),
            'school_district' => get_field('school_district', $listing_id),
            'mls_area_code' => get_field('mls_area_code', $listing_id),
            'zoning' => get_field('zoning', $listing_id),
            'flood_zone' => get_field('flood_zone', $listing_id),
            'hoa_name' => get_field('hoa_name', $listing_id),
            'address_notes' => get_field('address_notes', $listing_id)
        ];
        
        // Generate formatted address based on visibility settings
        $location_data['formatted_address'] = hph_format_address_by_visibility($location_data);
        
        // Generate full address for internal use
        $location_data['full_address'] = hph_build_full_address($location_data);
        
        // V1 compatibility fallbacks
        if (!$location_data['street_address']) {
            $location_data['street_address'] = get_field('address', $listing_id);
        }
        
        $cache[$listing_id] = $location_data;
        return $location_data;
    }
}

// Helper function for address formatting based on visibility
if (!function_exists('hph_format_address_by_visibility')) {
    function hph_format_address_by_visibility($location_data) {
        $visibility = $location_data['address_visibility'] ?? 'full';
        
        switch ($visibility) {
            case 'full':
                return hph_build_full_address($location_data);
                
            case 'street_only':
                $street_parts = array_filter([
                    $location_data['street_name'],
                    $location_data['street_suffix']
                ]);
                return implode(' ', $street_parts);
                
            case 'neighborhood':
                return $location_data['neighborhood'] ?: $location_data['city'];
                
            case 'city_only':
                return $location_data['city'];
                
            case 'hidden':
                return 'Address Available Upon Request';
                
            default:
                return hph_build_full_address($location_data);
        }
    }
}

// Helper function to build full address
if (!function_exists('hph_build_full_address')) {
    function hph_build_full_address($location_data) {
        $address_parts = [];
        
        // Street address with unit
        $street_address = trim($location_data['street_address'] ?? '');
        $unit_number = trim($location_data['unit_number'] ?? '');
        
        if ($street_address) {
            $address_parts[] = $unit_number ? "$street_address, Unit $unit_number" : $street_address;
        }
        
        // City, State ZIP
        $city = trim($location_data['city'] ?? '');
        $state = trim($location_data['state'] ?? '');
        $zip = trim($location_data['zip_code'] ?? '');
        
        if ($city && $state) {
            $city_state_zip = "$city, $state";
            if ($zip) {
                $city_state_zip .= " $zip";
            }
            $address_parts[] = $city_state_zip;
        }
        
        return implode(', ', $address_parts);
    }
}

/**
 * Get relationship and team information for a listing
 * Phase 3: Relationships & Team Management
 * @param int $listing_id Listing post ID
 * @param string $component Which component to return: 'all', 'agents', 'office', 'commission', 'performance'
 * @return array|string|null Relationship data
 */
if (!function_exists('hph_get_relationship_info')) {
    function hph_get_relationship_info($listing_id, $component = 'all') {
        $relationship_data = [
            'agents' => [
                'primary' => get_field('listing_agent_primary', $listing_id),
                'secondary' => get_field('listing_agent_secondary', $listing_id),
            ],
            'office' => [
                'primary' => get_field('listing_office_primary', $listing_id),
                'secondary' => get_field('listing_office_secondary', $listing_id),
                'phone' => get_field('listing_office_phone', $listing_id),
                'email' => get_field('listing_office_email', $listing_id),
                'website' => get_field('listing_office_website', $listing_id),
                'mls_office_id' => get_field('mls_listing_office_id', $listing_id),
                'mls_agent_id' => get_field('mls_listing_agent_id', $listing_id),
            ],
            'commission' => [
                'primary_agent' => get_field('listing_agent_commission_primary', $listing_id),
                'secondary_agent' => get_field('listing_agent_commission_secondary', $listing_id),
                'buyer_agent' => get_field('buyer_agent_commission', $listing_id),
                'total' => get_field('total_commission', $listing_id),
            ],
            'agreement' => [
                'type' => get_field('listing_agreement_type', $listing_id),
                'expiration' => get_field('listing_agreement_expiration', $listing_id),
            ],
            'performance' => [
                'views_total' => get_field('listing_views_total', $listing_id),
                'views_weekly' => get_field('listing_views_this_week', $listing_id),
                'inquiries' => get_field('inquiries_count_total', $listing_id),
                'showings' => get_field('showings_count_total', $listing_id),
                'performance_score' => get_field('listing_performance_score', $listing_id),
                'lead_source' => get_field('lead_source_primary', $listing_id),
                'marketing_status' => get_field('marketing_status', $listing_id),
            ]
        ];
        
        if ($component === 'all') {
            return $relationship_data;
        }
        
        return $relationship_data[$component] ?? null;
    }
}

/**
 * Get agent information with fallback formatting
 * Phase 3: Relationships & Team Management
 * @param int $listing_id Listing post ID
 * @param string $agent_type 'primary' or 'secondary'
 * @return array|null Agent data
 */
if (!function_exists('hph_get_listing_agent')) {
    function hph_get_listing_agent($listing_id, $agent_type = 'primary') {
        $field_name = $agent_type === 'primary' ? 'listing_agent_primary' : 'listing_agent_secondary';
        $agent_post = get_field($field_name, $listing_id);
        
        if (!$agent_post) {
            return null;
        }
        
        return [
            'id' => $agent_post->ID,
            'name' => $agent_post->post_title,
            'link' => get_permalink($agent_post->ID),
            'email' => get_field('agent_email', $agent_post->ID),
            'phone' => get_field('agent_phone', $agent_post->ID),
            'photo' => get_field('agent_photo', $agent_post->ID),
            'bio' => get_field('agent_bio', $agent_post->ID),
            'commission' => get_field("listing_agent_commission_{$agent_type}", $listing_id),
        ];
    }
}

/**
 * Get school ratings and location relationship information
 * Phase 3: Relationships & Team Management
 * @param int $listing_id Listing post ID
 * @param string $component Which component: 'all', 'schools', 'scores', 'places'
 * @return array|null Location relationship data
 */
if (!function_exists('hph_get_location_relationships')) {
    function hph_get_location_relationships($listing_id, $component = 'all') {
        $location_relationships = [
            'schools' => [
                'district' => get_field('school_district_relation', $listing_id),
                'elementary_rating' => get_field('elementary_school_rating', $listing_id),
                'middle_rating' => get_field('middle_school_rating', $listing_id),
                'high_rating' => get_field('high_school_rating', $listing_id),
                'overall_rating' => get_field('overall_school_rating', $listing_id),
            ],
            'scores' => [
                'walkability' => get_field('walkability_score', $listing_id),
                'transit' => get_field('transit_score', $listing_id),
                'bike' => get_field('bike_score', $listing_id),
                'lifestyle' => get_field('lifestyle_score', $listing_id),
            ],
            'places' => [
                'nearby' => get_field('nearby_places_relation', $listing_id),
            ]
        ];
        
        if ($component === 'all') {
            return $location_relationships;
        }
        
        return $location_relationships[$component] ?? null;
    }
}

/**
 * Format commission information for display
 * Phase 3: Relationships & Team Management
 * @param int $listing_id Listing post ID
 * @param string $format 'percentage', 'dollar', 'breakdown'
 * @return string|array Formatted commission data
 */
if (!function_exists('hph_format_commission')) {
    function hph_format_commission($listing_id, $format = 'percentage') {
        $commission_data = hph_get_relationship_info($listing_id, 'commission');
        $listing_price = hph_get_listing_price($listing_id, 'raw');
        
        switch ($format) {
            case 'dollar':
                $total_commission = (float) ($commission_data['total'] ?? 0);
                if ($total_commission > 0 && $listing_price > 0) {
                    $dollar_amount = ($listing_price * $total_commission) / 100;
                    return hph_format_price($dollar_amount);
                }
                return '';
                
            case 'breakdown':
                return [
                    'primary_agent' => [
                        'percentage' => $commission_data['primary_agent'] ?? 0,
                        'dollar' => $listing_price > 0 ? ($listing_price * (float)($commission_data['primary_agent'] ?? 0)) / 100 : 0
                    ],
                    'secondary_agent' => [
                        'percentage' => $commission_data['secondary_agent'] ?? 0,
                        'dollar' => $listing_price > 0 ? ($listing_price * (float)($commission_data['secondary_agent'] ?? 0)) / 100 : 0
                    ],
                    'buyer_agent' => [
                        'percentage' => $commission_data['buyer_agent'] ?? 0,
                        'dollar' => $listing_price > 0 ? ($listing_price * (float)($commission_data['buyer_agent'] ?? 0)) / 100 : 0
                    ],
                    'total' => [
                        'percentage' => $commission_data['total'] ?? 0,
                        'dollar' => $listing_price > 0 ? ($listing_price * (float)($commission_data['total'] ?? 0)) / 100 : 0
                    ]
                ];
                
            case 'percentage':
            default:
                $total_commission = (float) ($commission_data['total'] ?? 0);
                return $total_commission > 0 ? $total_commission . '%' : '';
        }
    }
}

/**
 * Get performance metrics summary for a listing
 * Phase 3: Relationships & Team Management
 * @param int $listing_id Listing post ID
 * @return array Performance summary
 */
if (!function_exists('hph_get_performance_summary')) {
    function hph_get_performance_summary($listing_id) {
        $performance = hph_get_relationship_info($listing_id, 'performance');
        $days_on_market = hph_get_days_on_market($listing_id);
        
        $views_total = (int) ($performance['views_total'] ?? 0);
        $inquiries = (int) ($performance['inquiries'] ?? 0);
        $showings = (int) ($performance['showings'] ?? 0);
        
        return [
            'score' => (int) ($performance['performance_score'] ?? 0),
            'views_total' => $views_total,
            'views_weekly' => (int) ($performance['views_weekly'] ?? 0),
            'inquiries' => $inquiries,
            'showings' => $showings,
            'days_on_market' => $days_on_market,
            'inquiry_rate' => $views_total > 0 ? round(($inquiries / $views_total) * 100, 1) : 0,
            'showing_rate' => $inquiries > 0 ? round(($showings / $inquiries) * 100, 1) : 0,
            'marketing_status' => $performance['marketing_status'] ?? 'planning',
            'lead_source' => $performance['lead_source'] ?? '',
        ];
    }
}

/**
 * Get financial and market analytics information for a listing
 * Phase 3 Day 4-7: Financial & Market Analytics
 * @param int $listing_id Listing post ID
 * @param string $component Which component to return: 'all', 'taxes', 'buyer', 'market', 'investment'
 * @return array|null Financial analytics data
 */
if (!function_exists('hph_get_financial_analytics')) {
    function hph_get_financial_analytics($listing_id, $component = 'all') {
        $financial_data = [
            'taxes' => [
                'property_tax_annual' => get_field('property_tax_annual', $listing_id),
                'property_tax_monthly' => get_field('property_tax_monthly', $listing_id),
                'hoa_fee_monthly' => get_field('hoa_fee_monthly', $listing_id),
                'hoa_fee_annual' => get_field('hoa_fee_annual', $listing_id),
                'insurance_annual' => get_field('insurance_estimated_annual', $listing_id),
                'insurance_monthly' => get_field('insurance_estimated_monthly', $listing_id),
                'utilities_monthly' => get_field('utilities_estimated_monthly', $listing_id),
                'special_assessments' => get_field('special_assessments', $listing_id),
            ],
            'buyer' => [
                'down_payment_percentage' => get_field('down_payment_percentage', $listing_id),
                'down_payment_amount' => get_field('down_payment_amount', $listing_id),
                'loan_amount' => get_field('loan_amount', $listing_id),
                'interest_rate' => get_field('interest_rate', $listing_id),
                'loan_term_years' => get_field('loan_term_years', $listing_id),
                'monthly_payment_pi' => get_field('estimated_monthly_payment', $listing_id),
                'monthly_payment_total' => get_field('total_monthly_payment', $listing_id),
                'income_required' => get_field('affordability_income_required', $listing_id),
            ],
            'market' => [
                'estimated_value' => get_field('estimated_market_value', $listing_id),
                'value_confidence' => get_field('market_value_confidence', $listing_id),
                'market_position' => get_field('market_position', $listing_id),
                'price_trend' => get_field('price_trend_direction', $listing_id),
                'comparable_count' => get_field('comparable_sales_count', $listing_id),
                'comparable_avg_price' => get_field('comparable_sales_avg_price', $listing_id),
                'comparable_avg_sqft_price' => get_field('comparable_sales_avg_sqft_price', $listing_id),
                'days_on_market_area_avg' => get_field('days_on_market_avg_area', $listing_id),
            ],
            'investment' => [
                'rental_potential' => get_field('rental_potential_monthly', $listing_id),
                'gross_yield' => get_field('rental_yield_gross', $listing_id),
                'cap_rate' => get_field('cap_rate_estimated', $listing_id),
                'cash_flow_monthly' => get_field('cash_flow_monthly', $listing_id),
                'appreciation_rate' => get_field('appreciation_rate_historical', $listing_id),
                'break_even_ratio' => get_field('break_even_ratio', $listing_id),
                'investment_grade' => get_field('investment_grade', $listing_id),
                'roi_5year' => get_field('roi_projected_5year', $listing_id),
            ]
        ];
        
        if ($component === 'all') {
            return $financial_data;
        }
        
        return $financial_data[$component] ?? null;
    }
}

/**
 * Format financial information for display
 * Phase 3 Day 4-7: Financial & Market Analytics
 * @param int $listing_id Listing post ID
 * @param string $format Format type: 'monthly_summary', 'buyer_summary', 'investment_summary'
 * @return array|string Formatted financial data
 */
if (!function_exists('hph_format_financial_summary')) {
    function hph_format_financial_summary($listing_id, $format = 'monthly_summary') {
        $financial_data = hph_get_financial_analytics($listing_id);
        
        switch ($format) {
            case 'monthly_summary':
                $monthly_costs = [];
                
                if (!empty($financial_data['buyer']['monthly_payment_pi'])) {
                    $monthly_costs[] = 'P&I: ' . hph_format_price($financial_data['buyer']['monthly_payment_pi']);
                }
                if (!empty($financial_data['taxes']['property_tax_monthly'])) {
                    $monthly_costs[] = 'Taxes: ' . hph_format_price($financial_data['taxes']['property_tax_monthly']);
                }
                if (!empty($financial_data['taxes']['insurance_monthly'])) {
                    $monthly_costs[] = 'Insurance: ' . hph_format_price($financial_data['taxes']['insurance_monthly']);
                }
                if (!empty($financial_data['taxes']['hoa_fee_monthly'])) {
                    $monthly_costs[] = 'HOA: ' . hph_format_price($financial_data['taxes']['hoa_fee_monthly']);
                }
                
                $total = $financial_data['buyer']['monthly_payment_total'] ?? 0;
                
                return [
                    'breakdown' => $monthly_costs,
                    'total' => $total > 0 ? hph_format_price($total) : '',
                    'formatted' => implode(' + ', $monthly_costs) . ($total > 0 ? ' = ' . hph_format_price($total) : '')
                ];
                
            case 'buyer_summary':
                $down_payment = $financial_data['buyer']['down_payment_amount'] ?? 0;
                $monthly_payment = $financial_data['buyer']['monthly_payment_total'] ?? 0;
                $income_required = $financial_data['buyer']['income_required'] ?? 0;
                
                return [
                    'down_payment' => $down_payment > 0 ? hph_format_price($down_payment) : '',
                    'monthly_payment' => $monthly_payment > 0 ? hph_format_price($monthly_payment) : '',
                    'income_required' => $income_required > 0 ? hph_format_price($income_required) : '',
                    'formatted' => ($down_payment > 0 ? hph_format_price($down_payment) . ' down, ' : '') .
                                 ($monthly_payment > 0 ? hph_format_price($monthly_payment) . '/mo, ' : '') .
                                 ($income_required > 0 ? hph_format_price($income_required) . ' income needed' : '')
                ];
                
            case 'investment_summary':
                $cash_flow = $financial_data['investment']['cash_flow_monthly'] ?? 0;
                $cap_rate = $financial_data['investment']['cap_rate'] ?? 0;
                $grade = $financial_data['investment']['investment_grade'] ?? '';
                $roi_5year = $financial_data['investment']['roi_5year'] ?? 0;
                
                return [
                    'cash_flow' => $cash_flow != 0 ? ($cash_flow > 0 ? '+' : '') . hph_format_price($cash_flow) . '/mo' : '',
                    'cap_rate' => $cap_rate > 0 ? number_format($cap_rate, 1) . '% cap rate' : '',
                    'grade' => $grade ? 'Grade: ' . $grade : '',
                    'roi_5year' => $roi_5year > 0 ? number_format($roi_5year, 1) . '% 5yr ROI' : '',
                    'formatted' => trim(implode(' | ', array_filter([
                        $cash_flow != 0 ? ($cash_flow > 0 ? '+' : '') . hph_format_price($cash_flow) . '/mo' : '',
                        $cap_rate > 0 ? number_format($cap_rate, 1) . '% cap' : '',
                        $grade ? 'Grade ' . $grade : ''
                    ])))
                ];
                
            default:
                return $financial_data;
        }
    }
}

/**
 * Get buyer affordability analysis
 * Phase 3 Day 4-7: Financial & Market Analytics
 * @param int $listing_id Listing post ID
 * @param float $buyer_income Optional buyer income for custom analysis
 * @return array Affordability analysis
 */
if (!function_exists('hph_get_buyer_affordability')) {
    function hph_get_buyer_affordability($listing_id, $buyer_income = null) {
        $financial_data = hph_get_financial_analytics($listing_id, 'buyer');
        $income_required = (float) ($financial_data['income_required'] ?? 0);
        $monthly_payment = (float) ($financial_data['monthly_payment_total'] ?? 0);
        $down_payment = (float) ($financial_data['down_payment_amount'] ?? 0);
        
        $analysis = [
            'income_required' => $income_required,
            'monthly_payment' => $monthly_payment,
            'down_payment_required' => $down_payment,
            'debt_to_income_ratio' => 28, // Standard 28% used in calculation
            'affordable' => null,
            'income_gap' => 0,
            'affordability_rating' => 'unknown'
        ];
        
        if ($buyer_income && $income_required > 0) {
            $analysis['affordable'] = $buyer_income >= $income_required;
            $analysis['income_gap'] = $buyer_income - $income_required;
            
            // Determine affordability rating
            $income_ratio = $buyer_income / $income_required;
            if ($income_ratio >= 1.3) {
                $analysis['affordability_rating'] = 'excellent';
            } elseif ($income_ratio >= 1.1) {
                $analysis['affordability_rating'] = 'good';
            } elseif ($income_ratio >= 1.0) {
                $analysis['affordability_rating'] = 'adequate';
            } elseif ($income_ratio >= 0.85) {
                $analysis['affordability_rating'] = 'challenging';
            } else {
                $analysis['affordability_rating'] = 'not_affordable';
            }
        }
        
        return $analysis;
    }
}

/**
 * Get market comparison analysis
 * Phase 3 Day 4-7: Financial & Market Analytics
 * @param int $listing_id Listing post ID
 * @return array Market comparison data
 */
if (!function_exists('hph_get_market_comparison')) {
    function hph_get_market_comparison($listing_id) {
        $listing_price = hph_get_listing_price($listing_id, 'raw');
        $market_data = hph_get_financial_analytics($listing_id, 'market');
        
        $comparison = [
            'listing_price' => $listing_price,
            'estimated_value' => (float) ($market_data['estimated_value'] ?? 0),
            'comparable_avg' => (float) ($market_data['comparable_avg_price'] ?? 0),
            'market_position' => $market_data['market_position'] ?? 'unknown',
            'price_vs_estimate' => 0,
            'price_vs_comps' => 0,
            'market_position_text' => '',
            'value_indicator' => 'neutral'
        ];
        
        // Calculate price differences
        if ($listing_price > 0 && $comparison['estimated_value'] > 0) {
            $comparison['price_vs_estimate'] = (($listing_price - $comparison['estimated_value']) / $comparison['estimated_value']) * 100;
        }
        
        if ($listing_price > 0 && $comparison['comparable_avg'] > 0) {
            $comparison['price_vs_comps'] = (($listing_price - $comparison['comparable_avg']) / $comparison['comparable_avg']) * 100;
        }
        
        // Set market position text and value indicator
        switch ($comparison['market_position']) {
            case 'underpriced':
                $comparison['market_position_text'] = 'Below Market Value';
                $comparison['value_indicator'] = 'good_value';
                break;
            case 'fair_value':
                $comparison['market_position_text'] = 'At Market Value';
                $comparison['value_indicator'] = 'neutral';
                break;
            case 'overpriced':
                $comparison['market_position_text'] = 'Above Market Value';
                $comparison['value_indicator'] = 'expensive';
                break;
            case 'premium':
                $comparison['market_position_text'] = 'Premium Pricing';
                $comparison['value_indicator'] = 'premium';
                break;
            default:
                $comparison['market_position_text'] = 'Market Position Unknown';
                $comparison['value_indicator'] = 'unknown';
        }
        
        return $comparison;
    }
}

/**
 * Get advanced search and filtering data for a listing
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param int $listing_id Listing post ID
 * @param string $component Which component to return: 'all', 'search', 'analytics', 'seo'
 * @return array|null Search and filtering data
 */
if (!function_exists('hph_get_search_data')) {
    function hph_get_search_data($listing_id, $component = 'all') {
        $search_data = [
            'search' => [
                'boost_score' => get_field('search_boost_score', $listing_id),
                'priority' => get_field('search_priority', $listing_id),
                'tags' => get_field('search_tags', $listing_id),
                'exclusions' => get_field('search_exclusions', $listing_id),
                'lifestyle_features' => get_field('lifestyle_features', $listing_id),
                'buyer_personas' => get_field('buyer_personas', $listing_id)
            ],
            'analytics' => [
                'total_views' => get_field('total_views', $listing_id),
                'unique_views' => get_field('unique_views', $listing_id),
                'views_last_30_days' => get_field('views_last_30_days', $listing_id),
                'average_time_on_page' => get_field('average_time_on_page', $listing_id),
                'favorites_count' => get_field('favorites_count', $listing_id),
                'shares_count' => get_field('shares_count', $listing_id),
                'contact_requests' => get_field('contact_requests', $listing_id),
                'tour_requests' => get_field('tour_requests', $listing_id),
                'search_ranking_avg' => get_field('search_ranking_avg', $listing_id),
                'click_through_rate' => get_field('click_through_rate', $listing_id),
                'search_impressions' => get_field('search_impressions', $listing_id),
                'conversion_rate' => get_field('conversion_rate', $listing_id)
            ],
            'seo' => [
                'title' => get_field('seo_title', $listing_id),
                'description' => get_field('seo_description', $listing_id),
                'keywords' => get_field('focus_keywords', $listing_id),
                'alt_text_auto' => get_field('alt_text_images', $listing_id),
                'schema_markup' => get_field('schema_markup', $listing_id)
            ],
            'filtering' => [
                'transit_access' => get_field('transit_access', $listing_id),
                'walkability_score' => get_field('walkability_score', $listing_id),
                'bikeability_score' => get_field('bikeability_score', $listing_id),
                'investment_type' => get_field('investment_type', $listing_id),
                'appreciation_potential' => get_field('appreciation_potential', $listing_id)
            ]
        ];
        
        if ($component === 'all') {
            return $search_data;
        }
        
        return $search_data[$component] ?? null;
    }
}

/**
 * Execute advanced search with parameters
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param array $search_params Search parameters
 * @return array Search results
 */
if (!function_exists('hph_execute_advanced_search')) {
    function hph_execute_advanced_search($search_params = []) {
        if (class_exists('\\HappyPlace\\Search\\Advanced_Search_Engine')) {
            $search_engine = \HappyPlace\Search\Advanced_Search_Engine::get_instance();
            return $search_engine->execute_advanced_search($search_params);
        }
        
        return [
            'listings' => [],
            'total' => 0,
            'pages' => 0,
            'execution_time' => 0,
            'error' => 'Search engine not available'
        ];
    }
}

/**
 * Get search suggestions for autocomplete
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param string $term Search term
 * @return array Suggestions array
 */
if (!function_exists('hph_get_search_suggestions')) {
    function hph_get_search_suggestions($term) {
        if (class_exists('\\HappyPlace\\Search\\Advanced_Search_Engine')) {
            $search_engine = \HappyPlace\Search\Advanced_Search_Engine::get_instance();
            return $search_engine->get_search_suggestions($term);
        }
        
        return [];
    }
}

/**
 * Get listing search performance metrics
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param int $listing_id Listing post ID
 * @return array Performance metrics
 */
if (!function_exists('hph_get_search_performance')) {
    function hph_get_search_performance($listing_id) {
        $analytics = hph_get_search_data($listing_id, 'analytics');
        
        if (!$analytics) {
            return null;
        }
        
        $total_views = (int) $analytics['total_views'];
        $contact_requests = (int) $analytics['contact_requests'];
        $tour_requests = (int) $analytics['tour_requests'];
        $favorites = (int) $analytics['favorites_count'];
        
        return [
            'engagement_score' => hph_calculate_engagement_score($analytics),
            'conversion_rate' => $total_views > 0 ? round((($contact_requests + $tour_requests) / $total_views) * 100, 2) : 0,
            'popularity_score' => hph_calculate_popularity_score($analytics),
            'search_effectiveness' => hph_calculate_search_effectiveness($analytics),
            'total_interactions' => $total_views + $contact_requests + $tour_requests + $favorites,
            'performance_grade' => hph_calculate_performance_grade($analytics)
        ];
    }
}

/**
 * Calculate engagement score for a listing
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param array $analytics Analytics data
 * @return float Engagement score (0-100)
 */
if (!function_exists('hph_calculate_engagement_score')) {
    function hph_calculate_engagement_score($analytics) {
        $views = (int) ($analytics['total_views'] ?? 0);
        $favorites = (int) ($analytics['favorites_count'] ?? 0);
        $shares = (int) ($analytics['shares_count'] ?? 0);
        $contacts = (int) ($analytics['contact_requests'] ?? 0);
        $tours = (int) ($analytics['tour_requests'] ?? 0);
        $time_on_page = (int) ($analytics['average_time_on_page'] ?? 0);
        
        if ($views === 0) {
            return 0;
        }
        
        // Weighted engagement calculation
        $engagement_points = 0;
        $engagement_points += $favorites * 5; // Favorites worth 5 points each
        $engagement_points += $shares * 8; // Shares worth 8 points each
        $engagement_points += $contacts * 15; // Contact requests worth 15 points each
        $engagement_points += $tours * 20; // Tour requests worth 20 points each
        
        // Time on page bonus (1 point per 10 seconds over 30 seconds)
        if ($time_on_page > 30) {
            $engagement_points += floor(($time_on_page - 30) / 10);
        }
        
        // Calculate score as percentage of views
        $score = ($engagement_points / $views) * 10; // Scale to reasonable range
        
        return min(100, max(0, round($score, 1)));
    }
}

/**
 * Calculate popularity score for a listing
 */
if (!function_exists('hph_calculate_popularity_score')) {
    function hph_calculate_popularity_score($analytics) {
        $views = (int) ($analytics['total_views'] ?? 0);
        $favorites = (int) ($analytics['favorites_count'] ?? 0);
        $shares = (int) ($analytics['shares_count'] ?? 0);
        
        if ($views === 0) {
            return 0;
        }
        
        // Simple popularity calculation based on views and social interactions
        $popularity_points = $views + ($favorites * 2) + ($shares * 3);
        
        // Scale to 0-100 range
        $score = min(100, ($popularity_points / max(1, $views)) * 10);
        
        return round($score, 1);
    }
}

/**
 * Calculate search effectiveness for a listing
 */
if (!function_exists('hph_calculate_search_effectiveness')) {
    function hph_calculate_search_effectiveness($analytics) {
        $search_views = (int) ($analytics['search_views'] ?? 0);
        $total_views = (int) ($analytics['total_views'] ?? 0);
        $search_contacts = (int) ($analytics['search_contact_requests'] ?? 0);
        $total_contacts = (int) ($analytics['contact_requests'] ?? 0);
        
        if ($total_views === 0) {
            return 0;
        }
        
        // Calculate search conversion rate vs total conversion rate
        $search_conversion = $search_views > 0 ? ($search_contacts / $search_views) : 0;
        $total_conversion = $total_contacts / $total_views;
        
        // Compare search effectiveness vs overall performance
        $effectiveness = $total_conversion > 0 ? ($search_conversion / $total_conversion) * 100 : 0;
        
        return min(100, max(0, round($effectiveness, 1)));
    }
}

/**
 * Calculate performance grade for a listing
 */
if (!function_exists('hph_calculate_performance_grade')) {
    function hph_calculate_performance_grade($analytics) {
        $engagement = hph_calculate_engagement_score($analytics);
        $popularity = hph_calculate_popularity_score($analytics);
        $effectiveness = hph_calculate_search_effectiveness($analytics);
        
        // Weighted average for overall grade
        $overall_score = ($engagement * 0.4) + ($popularity * 0.3) + ($effectiveness * 0.3);
        
        // Convert to letter grade
        if ($overall_score >= 90) return 'A+';
        if ($overall_score >= 85) return 'A';
        if ($overall_score >= 80) return 'A-';
        if ($overall_score >= 75) return 'B+';
        if ($overall_score >= 70) return 'B';
        if ($overall_score >= 65) return 'B-';
        if ($overall_score >= 60) return 'C+';
        if ($overall_score >= 55) return 'C';
        if ($overall_score >= 50) return 'C-';
        if ($overall_score >= 45) return 'D+';
        if ($overall_score >= 40) return 'D';
        if ($overall_score >= 35) return 'D-';
        
        return 'F';
    }
}

/**
 * Track search interaction for analytics
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param int $listing_id Listing post ID
 * @param string $interaction_type Type of interaction
 * @param string $search_query Optional search query context
 * @return bool Success status
 */
if (!function_exists('hph_track_search_interaction')) {
    function hph_track_search_interaction($listing_id, $interaction_type, $search_query = '') {
        if (class_exists('\\HappyPlace\\Search\\Advanced_Search_Engine')) {
            $search_engine = \HappyPlace\Search\Advanced_Search_Engine::get_instance();
            // This would typically be called via AJAX, but can be used programmatically
            return true;
        }
        
        return false;
    }
}

/**
 * Get popular search terms and trends
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param int $days Number of days to analyze (default 30)
 * @param int $limit Number of results to return (default 10)
 * @return array Popular search terms
 */
if (!function_exists('hph_get_popular_search_terms')) {
    function hph_get_popular_search_terms($days = 30, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'listing_search_analytics';
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return [];
        }
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                search_query,
                COUNT(*) as search_count,
                COUNT(DISTINCT user_ip) as unique_users
            FROM {$table_name}
            WHERE timestamp >= %s
            AND search_query != ''
            AND search_query IS NOT NULL
            GROUP BY search_query
            ORDER BY search_count DESC, unique_users DESC
            LIMIT %d
        ", $date_from, $limit));
        
        return $results ?: [];
    }
}

/**
 * Format search filters for display
 * Phase 4 Day 1-3: Advanced Search & Filtering
 * @param array $filters Filter parameters
 * @return array Formatted filter display
 */
if (!function_exists('hph_format_search_filters')) {
    function hph_format_search_filters($filters) {
        $formatted = [];
        
        // Price range
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $price_text = '';
            if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
                $price_text = '$' . number_format($filters['min_price']) . ' - $' . number_format($filters['max_price']);
            } elseif (!empty($filters['min_price'])) {
                $price_text = '$' . number_format($filters['min_price']) . '+';
            } elseif (!empty($filters['max_price'])) {
                $price_text = 'Up to $' . number_format($filters['max_price']);
            }
            
            if ($price_text) {
                $formatted[] = [
                    'label' => 'Price',
                    'value' => $price_text,
                    'type' => 'price'
                ];
            }
        }
        
        // Bedrooms
        if (!empty($filters['min_beds'])) {
            $beds_text = $filters['min_beds'] . '+ Bedrooms';
            $formatted[] = [
                'label' => 'Bedrooms',
                'value' => $beds_text,
                'type' => 'bedrooms'
            ];
        }
        
        // Bathrooms
        if (!empty($filters['min_baths'])) {
            $baths_text = $filters['min_baths'] . '+ Bathrooms';
            $formatted[] = [
                'label' => 'Bathrooms',
                'value' => $baths_text,
                'type' => 'bathrooms'
            ];
        }
        
        // Property type
        if (!empty($filters['property_type'])) {
            $formatted[] = [
                'label' => 'Property Type',
                'value' => $filters['property_type'],
                'type' => 'property_type'
            ];
        }
        
        // Square footage
        if (!empty($filters['min_sqft']) || !empty($filters['max_sqft'])) {
            $sqft_text = '';
            if (!empty($filters['min_sqft']) && !empty($filters['max_sqft'])) {
                $sqft_text = number_format($filters['min_sqft']) . ' - ' . number_format($filters['max_sqft']) . ' sq ft';
            } elseif (!empty($filters['min_sqft'])) {
                $sqft_text = number_format($filters['min_sqft']) . '+ sq ft';
            } elseif (!empty($filters['max_sqft'])) {
                $sqft_text = 'Up to ' . number_format($filters['max_sqft']) . ' sq ft';
            }
            
            if ($sqft_text) {
                $formatted[] = [
                    'label' => 'Square Footage',
                    'value' => $sqft_text,
                    'type' => 'square_footage'
                ];
            }
        }
        
        // Lifestyle features
        if (!empty($filters['lifestyle_features']) && is_array($filters['lifestyle_features'])) {
            foreach ($filters['lifestyle_features'] as $feature) {
                $formatted[] = [
                    'label' => 'Feature',
                    'value' => ucwords(str_replace('_', ' ', $feature)),
                    'type' => 'lifestyle_feature'
                ];
            }
        }
        
        return $formatted;
    }
}

// ================================================
// PHASE 4 DAY 4-7: API INTEGRATIONS & PERFORMANCE
// ================================================

/**
 * Get MLS integration status and data
 */
if (!function_exists("hph_get_mls_data")) {
    function hph_get_mls_data($listing_id) {
        if (empty($listing_id)) return null;
        
        $mls_enabled = get_field("api_mls_integration_enabled", "options");
        if (!$mls_enabled) return null;
        
        return [
            "mls_id" => get_post_meta($listing_id, "mls_id", true),
            "mls_source" => get_post_meta($listing_id, "mls_source", true),
            "mls_number" => get_field("mls_number", $listing_id),
            "mls_last_updated" => get_post_meta($listing_id, "mls_last_updated", true),
            "idx_compliant" => true,
            "listing_courtesy" => get_field("listing_office", $listing_id),
            "agent_info" => [
                "name" => get_field("listing_agent_name", $listing_id),
                "office" => get_field("listing_office", $listing_id)
            ]
        ];
    }
}

/**
 * Get performance optimized listing data
 */
if (!function_exists("hph_get_optimized_listing_data")) {
    function hph_get_optimized_listing_data($listing_id, $options = []) {
        if (empty($listing_id)) return null;
        
        if (class_exists("\HappyPlace\Performance\Performance_Optimization_Manager")) {
            $performance_manager = \HappyPlace\Performance\Performance_Optimization_Manager::get_instance();
            return $performance_manager->get_cached_data(
                "listing_data_{$listing_id}",
                function() use ($listing_id) {
                    return hph_get_listing_data($listing_id);
                },
                "listing"
            );
        }
        
        return hph_get_listing_data($listing_id);
    }
}

/**
 * Get enhanced Google Maps data
 */
if (!function_exists("hph_get_enhanced_map_data")) {
    function hph_get_enhanced_map_data($listing_id, $features = []) {
        if (empty($listing_id)) return null;
        
        $map_data = [
            "latitude" => get_field("latitude", $listing_id),
            "longitude" => get_field("longitude", $listing_id),
            "address" => get_field("street_address", $listing_id),
            "city" => get_field("city", $listing_id),
            "state" => get_field("state", $listing_id),
            "zip_code" => get_field("zip_code", $listing_id)
        ];
        
        $google_features = get_field("api_google_maps_enhanced", "options") ?: [];
        
        if (in_array("streetview", $google_features) && in_array("streetview", $features)) {
            $map_data["streetview_enabled"] = true;
        }
        
        if (in_array("traffic", $google_features) && in_array("traffic", $features)) {
            $map_data["traffic_enabled"] = true;
        }
        
        return $map_data;
    }
}

/**
 * Get CDN optimized image URL
 */
if (!function_exists("hph_get_cdn_image_url")) {
    function hph_get_cdn_image_url($image_url, $options = []) {
        if (empty($image_url)) return $image_url;
        
        $cdn_settings = get_field("api_cdn_integration", "options");
        if (!$cdn_settings["cdn_enabled"] || empty($cdn_settings["cdn_url"])) {
            return $image_url;
        }
        
        $upload_dir = wp_upload_dir();
        $upload_url = $upload_dir["baseurl"];
        
        if (strpos($image_url, $upload_url) === 0) {
            return str_replace($upload_url, rtrim($cdn_settings["cdn_url"], "/"), $image_url);
        }
        
        return $image_url;
    }
}

// =============================================================================
// PHASE 5 DAY 1-3: ENHANCED BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get address components with enhanced parsing
 * @param int $listing_id Listing post ID
 * @return array Parsed address components
 */
if (!function_exists('hph_get_address_components')) {
    function hph_get_address_components($listing_id) {
        $cache_key = 'address_components_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Get parsed components from Phase 2 fields
        $components = [
            'street_number' => get_field('street_number', $listing_id),
            'street_dir_prefix' => get_field('street_dir_prefix', $listing_id),
            'street_name' => get_field('street_name', $listing_id),
            'street_suffix' => get_field('street_suffix', $listing_id),
            'street_dir_suffix' => get_field('street_dir_suffix', $listing_id),
            'unit_number' => get_field('unit_number', $listing_id),
            'city' => get_field('city', $listing_id),
            'state' => get_field('state', $listing_id),
            'zip_code' => get_field('zip_code', $listing_id),
            'county' => get_field('county', $listing_id),
            'full_street_address' => get_field('street_address', $listing_id)
        ];
        
        // Build formatted versions
        $street_parts = array_filter([
            $components['street_number'],
            $components['street_dir_prefix'],
            $components['street_name'],
            $components['street_suffix'],
            $components['street_dir_suffix']
        ]);
        
        $components['formatted_street'] = implode(' ', $street_parts);
        
        $full_address_parts = array_filter([
            $components['formatted_street'],
            $components['unit_number'] ? 'Unit ' . $components['unit_number'] : '',
            $components['city'],
            $components['state'],
            $components['zip_code']
        ]);
        
        $components['formatted_full_address'] = implode(', ', $full_address_parts);
        
        // MLS compliance format
        $components['mls_address'] = $components['formatted_street'] . 
            ($components['unit_number'] ? ' Unit ' . $components['unit_number'] : '');
        
        // Cache for 4 hours
        wp_cache_set($cache_key, $components, 'hph_listings', 14400);
        
        return $components;
    }
}

/**
 * Get neighborhood context with nearby amenities and scores
 * @param int $listing_id Listing post ID
 * @return array Neighborhood context and nearby amenities
 */
if (!function_exists('hph_get_neighborhood_context')) {
    function hph_get_neighborhood_context($listing_id) {
        $cache_key = 'neighborhood_context_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $location_data = hph_get_location_intelligence($listing_id);
        
        $context = [
            // Basic neighborhood info
            'neighborhood' => $location_data['neighborhood'],
            'city' => $location_data['city'],
            'county' => $location_data['county'],
            'school_district' => $location_data['school_district'],
            
            // Scores and ratings
            'walkability_score' => $location_data['walkability_score'],
            'transit_score' => $location_data['transit_score'],
            
            // Enhanced with calculated ratings
            'neighborhood_rating' => hph_calculate_neighborhood_rating($location_data),
            'family_friendliness' => hph_calculate_family_score($location_data),
            'commuter_friendliness' => hph_calculate_commuter_score($location_data),
            
            // Nearby amenities (Phase 2 Day 4-7 integration)
            'nearby_schools' => hph_get_nearby_places($listing_id, 'school'),
            'nearby_shopping' => hph_get_nearby_places($listing_id, 'shopping'),
            'nearby_dining' => hph_get_nearby_places($listing_id, 'restaurant'),
            'nearby_healthcare' => hph_get_nearby_places($listing_id, 'healthcare'),
            'nearby_recreation' => hph_get_nearby_places($listing_id, 'recreation'),
            
            // Community relationships
            'community_relation' => $location_data['community_relation'],
            'city_relation' => $location_data['city_relation'],
            
            // Additional context
            'zoning' => $location_data['zoning'],
            'flood_zone' => $location_data['flood_zone'],
            'hoa_name' => $location_data['hoa_name']
        ];
        
        // Add convenience flags
        $context['has_walkability_data'] = !empty($context['walkability_score']);
        $context['has_school_info'] = !empty($context['nearby_schools']);
        $context['has_transit_access'] = !empty($context['transit_score']) && $context['transit_score'] > 50;
        $context['is_family_friendly'] = $context['family_friendliness'] > 70;
        
        // Cache for 6 hours
        wp_cache_set($cache_key, $context, 'hph_listings', 21600);
        
        return $context;
    }
}

/**
 * Enhanced bridge function for address parsing system
 * @param int $listing_id Listing post ID
 * @param string $format Format: 'full', 'street', 'mls', 'components'
 * @return string|array Address in requested format
 */
if (!function_exists('hph_bridge_get_address')) {
    function hph_bridge_get_address($listing_id, $format = 'full') {
        $components = hph_get_address_components($listing_id);
        
        if (empty($components)) {
            return $format === 'components' ? [] : '';
        }
        
        switch ($format) {
            case 'full':
                return $components['formatted_full_address'];
                
            case 'street':
                return $components['formatted_street'];
                
            case 'mls':
                return $components['mls_address'];
                
            case 'components':
                return $components;
                
            case 'short':
                return $components['city'] . ', ' . $components['state'];
                
            case 'display':
                // Privacy-aware display based on address visibility settings
                $location_data = hph_get_location_intelligence($listing_id);
                return hph_format_address_by_visibility($location_data);
                
            default:
                return $components['formatted_full_address'];
        }
    }
}

/**
 * Enhanced bridge function for coordinates with geocoding
 * @param int $listing_id Listing post ID
 * @param string $format Format: 'array', 'string', 'object'
 * @return array|string|object Coordinates in requested format
 */
if (!function_exists('hph_bridge_get_coordinates')) {
    function hph_bridge_get_coordinates($listing_id, $format = 'array') {
        $location_data = hph_get_location_intelligence($listing_id);
        
        $lat = floatval($location_data['latitude']);
        $lng = floatval($location_data['longitude']);
        
        if (empty($lat) || empty($lng)) {
            // Attempt to geocode if coordinates are missing
            $address = hph_bridge_get_address($listing_id, 'full');
            if (!empty($address)) {
                $coordinates = hph_geocode_address($address, $listing_id);
                if ($coordinates) {
                    $lat = $coordinates['lat'];
                    $lng = $coordinates['lng'];
                }
            }
        }
        
        $accuracy = $location_data['geocoding_accuracy'] ?: 'Unknown';
        $source = $location_data['geocoding_source'] ?: 'Manual';
        
        switch ($format) {
            case 'array':
                return [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'lat' => $lat,
                    'lng' => $lng,
                    'accuracy' => $accuracy,
                    'source' => $source,
                    'valid' => ($lat !== 0.0 && $lng !== 0.0)
                ];
                
            case 'string':
                return $lat . ',' . $lng;
                
            case 'object':
                return (object) [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy' => $accuracy,
                    'source' => $source,
                    'valid' => ($lat !== 0.0 && $lng !== 0.0)
                ];
                
            case 'google_maps':
                return [
                    'lat' => $lat,
                    'lng' => $lng
                ];
                
            default:
                return ['latitude' => $lat, 'longitude' => $lng];
        }
    }
}

// =============================================================================
// HELPER FUNCTIONS FOR NEIGHBORHOOD CONTEXT
// =============================================================================

/**
 * Calculate neighborhood rating based on available data
 */
if (!function_exists('hph_calculate_neighborhood_rating')) {
    function hph_calculate_neighborhood_rating($location_data) {
        $score = 50; // Base score
        
        // Walkability bonus
        if (!empty($location_data['walkability_score'])) {
            $score += ($location_data['walkability_score'] - 50) * 0.3;
        }
        
        // Transit bonus
        if (!empty($location_data['transit_score'])) {
            $score += ($location_data['transit_score'] - 50) * 0.2;
        }
        
        // School district bonus (if available)
        if (!empty($location_data['school_district'])) {
            $score += 10;
        }
        
        return max(0, min(100, round($score)));
    }
}

/**
 * Calculate family friendliness score
 */
if (!function_exists('hph_calculate_family_score')) {
    function hph_calculate_family_score($location_data) {
        $score = 50;
        
        // School district presence
        if (!empty($location_data['school_district'])) {
            $score += 20;
        }
        
        // Safe area indicators
        if (!empty($location_data['neighborhood'])) {
            $score += 10;
        }
        
        // Walkability for families
        if (!empty($location_data['walkability_score']) && $location_data['walkability_score'] > 60) {
            $score += 15;
        }
        
        return max(0, min(100, round($score)));
    }
}

/**
 * Calculate commuter friendliness score
 */
if (!function_exists('hph_calculate_commuter_score')) {
    function hph_calculate_commuter_score($location_data) {
        $score = 50;
        
        // Transit access
        if (!empty($location_data['transit_score'])) {
            $score += ($location_data['transit_score'] - 50) * 0.5;
        }
        
        // Highway access (simplified)
        if (!empty($location_data['city'])) {
            $score += 10;
        }
        
        return max(0, min(100, round($score)));
    }
}

/**
 * Get nearby places by type (placeholder for future API integration)
 */
if (!function_exists('hph_get_nearby_places')) {
    function hph_get_nearby_places($listing_id, $type = 'all') {
        // Future API integration placeholder
        // This would connect to Google Places API, Yelp, etc.
        return [];
    }
}

/**
 * Geocode address (placeholder for future API integration)
 */
if (!function_exists('hph_geocode_address')) {
    function hph_geocode_address($address, $listing_id = null) {
        // Future API integration placeholder
        // This would connect to Google Geocoding API, etc.
        return false;
    }
}

// ============================================================================
// MISSING TEMPLATE COMPONENT BRIDGE FUNCTIONS
// ============================================================================

/**
 * Get listing address with different format options
 * Enhanced version with multiple format types
 */
if (!function_exists('hph_get_listing_address')) {
    function hph_get_listing_address($listing_id, $format = 'full') {
        $street = get_field('street_address', $listing_id) ?: get_field('address', $listing_id);
        $city = get_field('city', $listing_id);
        $state = get_field('state', $listing_id);
        $zip = get_field('zip_code', $listing_id) ?: get_field('zip', $listing_id);
        
        switch ($format) {
            case 'short':
                return $city && $state ? $city . ', ' . $state : ($street ?: get_the_title($listing_id));
                
            case 'street':
                return $street ?: '';
                
            case 'city':
                return $city ?: '';
                
            case 'state':
                return $state ?: '';
                
            case 'zip':
                return $zip ?: '';
                
            case 'city_state':
                $parts = array_filter([$city, $state]);
                return implode(', ', $parts);
                
            case 'city_state_zip':
                $parts = array_filter([$city, $state, $zip]);
                return implode(', ', $parts);
                
            case 'full':
            default:
                $parts = array_filter([$street, $city, $state, $zip]);
                return implode(', ', $parts) ?: get_the_title($listing_id);
        }
    }
}

/**
 * Get listing bedrooms
 */
if (!function_exists('hph_get_listing_bedrooms')) {
    function hph_get_listing_bedrooms($listing_id) {
        $bedrooms = get_field('bedrooms', $listing_id);
        return $bedrooms ? intval($bedrooms) : 0;
    }
}

/**
 * Get listing bathrooms
 */
if (!function_exists('hph_get_listing_bathrooms')) {
    function hph_get_listing_bathrooms($listing_id) {
        $bathrooms = get_field('bathrooms', $listing_id) ?: get_field('bathrooms_full', $listing_id);
        $half_baths = get_field('half_bathrooms', $listing_id) ?: get_field('bathrooms_half', $listing_id);
        
        if ($bathrooms) {
            return $half_baths ? ($bathrooms + 0.5 * $half_baths) : floatval($bathrooms);
        }
        
        return 0;
    }
}

/**
 * Get listing square footage
 */
if (!function_exists('hph_get_listing_square_footage')) {
    function hph_get_listing_square_footage($listing_id) {
        $sqft = get_field('square_footage', $listing_id) ?: get_field('living_area', $listing_id);
        return $sqft ? intval($sqft) : 0;
    }
}

/**
 * Get listing lot size
 */
if (!function_exists('hph_get_listing_lot_size')) {
    function hph_get_listing_lot_size($listing_id) {
        $lot_size = get_field('lot_size', $listing_id);
        return $lot_size ?: '';
    }
}

/**
 * Get listing year built
 */
if (!function_exists('hph_get_listing_year_built')) {
    function hph_get_listing_year_built($listing_id) {
        $year = get_field('year_built', $listing_id);
        return $year ? intval($year) : 0;
    }
}

/**
 * Get listing property type
 */
if (!function_exists('hph_get_listing_property_type')) {
    function hph_get_listing_property_type($listing_id) {
        $type = get_field('property_type', $listing_id);
        return $type ?: '';
    }
}

/**
 * Get listing featured image with size options
 */
if (!function_exists('hph_get_listing_featured_image')) {
    function hph_get_listing_featured_image($listing_id, $size = 'large') {
        $image_id = get_post_thumbnail_id($listing_id);
        
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, $size);
            return $image ? $image[0] : '';
        }
        
        return '';
    }
}

/**
 * Get listing gallery count
 */
if (!function_exists('hph_get_listing_gallery_count')) {
    function hph_get_listing_gallery_count($listing_id) {
        $gallery = get_field('gallery', $listing_id) ?: get_field('property_gallery', $listing_id) ?: get_field('listing_photos', $listing_id);
        
        if (is_array($gallery)) {
            return count($gallery);
        }
        
        // Include featured image if exists
        return has_post_thumbnail($listing_id) ? 1 : 0;
    }
}

/**
 * Get listing gallery images
 */
if (!function_exists('hph_get_listing_gallery')) {
    function hph_get_listing_gallery($listing_id) {
        $gallery = get_field('gallery', $listing_id) ?: get_field('property_gallery', $listing_id) ?: get_field('listing_photos', $listing_id);
        
        if (is_array($gallery) && !empty($gallery)) {
            return $gallery;
        }
        
        // Fallback to featured image if no gallery
        $featured_image_id = get_post_thumbnail_id($listing_id);
        if ($featured_image_id) {
            return [get_field('_thumbnail_id', $listing_id, false)];
        }
        
        return [];
    }
}

/**
 * Get listing virtual tour URL
 */
if (!function_exists('hph_get_listing_virtual_tour')) {
    function hph_get_listing_virtual_tour($listing_id) {
        $tour = get_field('virtual_tour_url', $listing_id) ?: get_field('virtual_tour', $listing_id);
        return $tour ?: '';
    }
}

/**
 * Get listing description
 */
if (!function_exists('hph_get_listing_description')) {
    function hph_get_listing_description($listing_id) {
        $content = get_the_content(null, false, $listing_id);
        
        if (empty($content)) {
            $content = get_field('description', $listing_id) ?: get_field('property_description', $listing_id);
        }
        
        return $content ?: '';
    }
}

/**
 * Get listing excerpt
 */
if (!function_exists('hph_get_listing_excerpt')) {
    function hph_get_listing_excerpt($listing_id, $word_limit = 25) {
        $description = hph_get_listing_description($listing_id);
        
        if ($description) {
            return wp_trim_words(strip_tags($description), $word_limit, '...');
        }
        
        return '';
    }
}

/**
 * Get listing days on market
 */
if (!function_exists('hph_get_listing_days_on_market')) {
    function hph_get_listing_days_on_market($listing_id) {
        $list_date = get_field('list_date', $listing_id) ?: get_field('listing_date', $listing_id);
        
        if ($list_date) {
            $list_timestamp = is_numeric($list_date) ? $list_date : strtotime($list_date);
            if ($list_timestamp) {
                $days = floor((time() - $list_timestamp) / (24 * 60 * 60));
                return max(0, $days);
            }
        }
        
        // Fallback to post date
        $post_date = get_the_date('U', $listing_id);
        if ($post_date) {
            $days = floor((time() - $post_date) / (24 * 60 * 60));
            return max(0, $days);
        }
        
        return 0;
    }
}

/**
 * Check if listing is featured
 */
if (!function_exists('hph_is_listing_featured')) {
    function hph_is_listing_featured($listing_id) {
        $featured = get_field('is_featured', $listing_id) ?: get_field('featured', $listing_id);
        return !empty($featured);
    }
}

/**
 * Check if listing is new (less than 7 days old)
 */
if (!function_exists('hph_is_listing_new')) {
    function hph_is_listing_new($listing_id, $days_threshold = 7) {
        $days_on_market = hph_get_listing_days_on_market($listing_id);
        return $days_on_market <= $days_threshold;
    }
}

/**
 * Get listing MLS number
 */
if (!function_exists('hph_get_listing_mls_number')) {
    function hph_get_listing_mls_number($listing_id) {
        $mls = get_field('mls_number', $listing_id) ?: get_field('mls_id', $listing_id);
        return $mls ?: '';
    }
}

/**
 * Get comprehensive template listing data (for components)
 */
if (!function_exists('hph_get_template_listing_data')) {
    function hph_get_template_listing_data($listing_id) {
        $cache_key = 'template_listing_data_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $data = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'permalink' => get_permalink($listing_id),
            'price' => hph_get_listing_price($listing_id, 'display'),
            'price_raw' => hph_get_listing_price($listing_id, 'raw'),
            'status' => hph_get_listing_status($listing_id),
            'address' => hph_get_listing_address($listing_id, 'full'),
            'short_address' => hph_get_listing_address($listing_id, 'short'),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'square_footage' => hph_get_listing_square_footage($listing_id),
            'lot_size' => hph_get_listing_lot_size($listing_id),
            'year_built' => hph_get_listing_year_built($listing_id),
            'property_type' => hph_get_listing_property_type($listing_id),
            'featured_image' => hph_get_listing_featured_image($listing_id, 'large'),
            'gallery_count' => hph_get_listing_gallery_count($listing_id),
            'virtual_tour' => hph_get_listing_virtual_tour($listing_id),
            'description' => hph_get_listing_description($listing_id),
            'excerpt' => hph_get_listing_excerpt($listing_id, 25),
            'days_on_market' => hph_get_listing_days_on_market($listing_id),
            'is_featured' => hph_is_listing_featured($listing_id),
            'is_new' => hph_is_listing_new($listing_id),
            'mls_number' => hph_get_listing_mls_number($listing_id)
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'hph_listings', 3600);
        
        return $data;
    }
}

/**
 * Get listing agent data
 */
if (!function_exists('hph_get_listing_agent')) {
    function hph_get_listing_agent($listing_id) {
        $agent_id = get_field('listing_agent', $listing_id) ?: get_field('agent', $listing_id);
        
        if (!$agent_id) {
            return null;
        }
        
        // If it's an array (from relationship field), get the first one
        if (is_array($agent_id)) {
            $agent_id = $agent_id[0] ?? null;
        }
        
        // If it's an object, get the ID
        if (is_object($agent_id)) {
            $agent_id = $agent_id->ID ?? null;
        }
        
        if (!$agent_id) {
            return null;
        }
        
        return [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'permalink' => get_permalink($agent_id),
            'photo' => get_the_post_thumbnail_url($agent_id, 'thumbnail'),
            'phone' => get_field('phone', $agent_id) ?: get_field('phone_number', $agent_id),
            'email' => get_field('email', $agent_id) ?: get_field('email_address', $agent_id),
            'title' => get_field('job_title', $agent_id) ?: get_field('title', $agent_id),
            'bio' => get_field('bio', $agent_id) ?: get_field('biography', $agent_id)
        ];
    }
}

/**
 * Get template agent data
 */
if (!function_exists('hph_get_template_agent_data')) {
    function hph_get_template_agent_data($agent_id) {
        $cache_key = 'template_agent_data_' . $agent_id;
        $cached = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $data = [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'permalink' => get_permalink($agent_id),
            'photo' => get_the_post_thumbnail_url($agent_id, 'medium'),
            'phone' => get_field('phone', $agent_id) ?: get_field('phone_number', $agent_id),
            'email' => get_field('email', $agent_id) ?: get_field('email_address', $agent_id),
            'title' => get_field('job_title', $agent_id) ?: get_field('title', $agent_id),
            'bio' => get_field('bio', $agent_id) ?: get_field('biography', $agent_id),
            'bio_excerpt' => wp_trim_words(strip_tags(get_field('bio', $agent_id) ?: ''), 25),
            'specialties' => get_field('specialties', $agent_id) ?: [],
            'social_media' => [
                'facebook' => get_field('facebook_url', $agent_id),
                'instagram' => get_field('instagram_url', $agent_id),
                'linkedin' => get_field('linkedin_url', $agent_id),
                'twitter' => get_field('twitter_url', $agent_id)
            ]
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'hph_agents', 3600);
        
        return $data;
    }
}

// ============================================================================
// UTILITY FUNCTIONS FOR TEMPLATE COMPONENTS
// ============================================================================

/**
 * Format price for display
 */
if (!function_exists('hph_format_price')) {
    function hph_format_price($price, $format = 'standard') {
        if (empty($price) || !is_numeric($price)) {
            return 'Contact for Price';
        }
        
        $price = floatval($price);
        
        switch ($format) {
            case 'short':
                if ($price >= 1000000) {
                    return '$' . number_format($price / 1000000, 1) . 'M';
                } elseif ($price >= 1000) {
                    return '$' . number_format($price / 1000, 0) . 'K';
                } else {
                    return '$' . number_format($price, 0);
                }
                break;
                
            case 'standard':
            default:
                return '$' . number_format($price, 0);
        }
    }
}

/**
 * Track listing view for analytics
 */
if (!function_exists('hph_track_listing_view')) {
    function hph_track_listing_view($listing_id, $view_type = 'page-view', $context = '') {
        // Basic view tracking - you can enhance this with your analytics system
        $views = get_post_meta($listing_id, '_listing_views', true) ?: 0;
        update_post_meta($listing_id, '_listing_views', $views + 1);
        
        // Log the view with context for development
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("HPH: Listing {$listing_id} viewed: {$view_type} in {$context}");
        }
        
        // Add your analytics tracking here (Google Analytics, Mixpanel, etc.)
        if (function_exists('gtag')) {
            gtag('event', 'listing_view', [
                'listing_id' => $listing_id,
                'view_type' => $view_type,
                'context' => $context
            ]);
        }
        
        // Hook for custom analytics integration
        do_action('hph_listing_view_tracked', $listing_id, $view_type, $context);
    }
}

/**
 * Count listings matching search criteria
 *
 * @param array $args Search arguments
 * @return int Total count
 */
if (!function_exists('hph_count_listings')) {
    function hph_count_listings($args = []) {
        global $wpdb;
        
        $cache_key = 'hph_count_listings_' . md5(serialize($args));
        $count = wp_cache_get($cache_key, 'hph_listings');
        
        if ($count !== false) {
            return (int) $count;
        }
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Status filter
        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        } else {
            $where_conditions[] = 'status = %s';
            $where_values[] = 'active';
        }
        
        // Agent filter
        if (!empty($args['agent_id'])) {
            $where_conditions[] = 'listing_agent_id = %d';
            $where_values[] = $args['agent_id'];
        }
        
        // Property type filter
        if (!empty($args['property_type'])) {
            $where_conditions[] = 'property_type = %s';
            $where_values[] = $args['property_type'];
        }
        
        // Price filters
        if (!empty($args['price_min'])) {
            $where_conditions[] = 'price >= %d';
            $where_values[] = $args['price_min'];
        }
        
        if (!empty($args['price_max'])) {
            $where_conditions[] = 'price <= %d';
            $where_values[] = $args['price_max'];
        }
        
        // Bedroom filter
        if (!empty($args['bedrooms_min'])) {
            $where_conditions[] = 'bedrooms >= %d';
            $where_values[] = $args['bedrooms_min'];
        }
        
        // Bathroom filter
        if (!empty($args['bathrooms_min'])) {
            $where_conditions[] = 'bathrooms >= %f';
            $where_values[] = $args['bathrooms_min'];
        }
        
        // Location filters
        if (!empty($args['city'])) {
            $where_conditions[] = 'city = %s';
            $where_values[] = $args['city'];
        }
        
        if (!empty($args['state'])) {
            $where_conditions[] = 'state = %s';
            $where_values[] = $args['state'];
        }
        
        if (!empty($args['zip'])) {
            $where_conditions[] = 'zip_code = %s';
            $where_values[] = $args['zip'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}hph_listings WHERE {$where_clause}",
                ...$where_values
            );
        } else {
            $query = "SELECT COUNT(*) FROM {$wpdb->prefix}hph_listings WHERE {$where_clause}";
        }
        
        $count = (int) $wpdb->get_var($query);
        
        wp_cache_set($cache_key, $count, 'hph_listings', 300); // 5 minute cache
        
        return $count;
    }
}

/**
 * Get listing URL
 *
 * @param int $listing_id
 * @return string
 */
if (!function_exists('hph_get_listing_url')) {
    function hph_get_listing_url($listing_id) {
        // Check if custom post type exists for listings
        $listing_post = get_post($listing_id);
        if ($listing_post && $listing_post->post_type === 'hph_listing') {
            return get_permalink($listing_id);
        }
        
        // Otherwise construct URL based on plugin settings
        $base_url = get_option('hph_listing_base_url', '/listings/');
        $listing = hph_get_listing($listing_id);
        
        if (!$listing) {
            return home_url($base_url);
        }
        
        // Create SEO-friendly slug
        $slug = !empty($listing['slug']) ? $listing['slug'] : sanitize_title($listing['title']);
        
        return home_url($base_url . $slug . '/');
    }
}

/**
 * Get available property types
 *
 * @return array Array of property types with labels
 */
if (!function_exists('hph_get_property_types')) {
    function hph_get_property_types() {
        $cache_key = 'hph_property_types';
        $types = wp_cache_get($cache_key, 'hph_listings');
        
        if ($types !== false) {
            return $types;
        }
        
        // Default property types
        $types = [
            'single_family' => __('Single Family Home', 'happy-place'),
            'condo' => __('Condominium', 'happy-place'),
            'townhouse' => __('Townhouse', 'happy-place'),
            'multi_family' => __('Multi-Family', 'happy-place'),
            'land' => __('Land/Lot', 'happy-place'),
            'mobile_home' => __('Mobile/Manufactured Home', 'happy-place'),
            'farm_ranch' => __('Farm/Ranch', 'happy-place'),
            'commercial' => __('Commercial', 'happy-place'),
            'other' => __('Other', 'happy-place')
        ];
        
        // Allow filtering via hook
        $types = apply_filters('hph_property_types', $types);
        
        wp_cache_set($cache_key, $types, 'hph_listings', 3600); // 1 hour cache
        
        return $types;
    }
}

/**
 * Search listings with pagination and filtering
 *
 * @param array $args Search arguments
 * @return array Array of listings
 */
if (!function_exists('hph_search_listings')) {
    function hph_search_listings($args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 12,
            'page' => 1,
            'sort_by' => 'price_desc',
            'status' => 'active'
        ];
        
        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['limit'];
        
        $cache_key = 'hph_search_listings_' . md5(serialize($args));
        $listings = wp_cache_get($cache_key, 'hph_listings');
        
        if ($listings !== false) {
            return $listings;
        }
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Status filter
        if (!empty($args['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Agent filter
        if (!empty($args['agent_id'])) {
            $where_conditions[] = 'listing_agent_id = %d';
            $where_values[] = $args['agent_id'];
        }
        
        // Property type filter
        if (!empty($args['property_type'])) {
            $where_conditions[] = 'property_type = %s';
            $where_values[] = $args['property_type'];
        }
        
        // Price filters
        if (!empty($args['price_min'])) {
            $where_conditions[] = 'price >= %d';
            $where_values[] = $args['price_min'];
        }
        
        if (!empty($args['price_max'])) {
            $where_conditions[] = 'price <= %d';
            $where_values[] = $args['price_max'];
        }
        
        // Bedroom filter
        if (!empty($args['bedrooms_min'])) {
            $where_conditions[] = 'bedrooms >= %d';
            $where_values[] = $args['bedrooms_min'];
        }
        
        // Bathroom filter
        if (!empty($args['bathrooms_min'])) {
            $where_conditions[] = 'bathrooms >= %f';
            $where_values[] = $args['bathrooms_min'];
        }
        
        // Location filters
        if (!empty($args['city'])) {
            $where_conditions[] = 'city = %s';
            $where_values[] = $args['city'];
        }
        
        if (!empty($args['state'])) {
            $where_conditions[] = 'state = %s';
            $where_values[] = $args['state'];
        }
        
        if (!empty($args['zip'])) {
            $where_conditions[] = 'zip_code = %s';
            $where_values[] = $args['zip'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Sort clause
        $sort_clause = 'ORDER BY ';
        switch ($args['sort_by']) {
            case 'price_asc':
                $sort_clause .= 'price ASC';
                break;
            case 'price_desc':
            default:
                $sort_clause .= 'price DESC';
                break;
            case 'newest':
                $sort_clause .= 'created_at DESC';
                break;
            case 'sqft_desc':
                $sort_clause .= 'square_feet DESC';
                break;
            case 'bedrooms_desc':
                $sort_clause .= 'bedrooms DESC';
                break;
            case 'days_asc':
                $sort_clause .= 'DATEDIFF(NOW(), created_at) ASC';
                break;
        }
        
        $limit_clause = $wpdb->prepare("LIMIT %d OFFSET %d", $args['limit'], $offset);
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}hph_listings WHERE {$where_clause} {$sort_clause} {$limit_clause}",
                ...$where_values
            );
        } else {
            $query = "SELECT * FROM {$wpdb->prefix}hph_listings WHERE {$where_clause} {$sort_clause} {$limit_clause}";
        }
        
        $listings = $wpdb->get_results($query, ARRAY_A);
        
        wp_cache_set($cache_key, $listings, 'hph_listings', 300); // 5 minute cache
        
        return $listings ?: [];
    }
}
