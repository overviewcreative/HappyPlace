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
 * Get listing price
 * @param int $listing_id Listing post ID
 * @param bool $formatted Whether to format the price for display
 * @return string|float Formatted price string or raw price value
 */
if (!function_exists('hph_get_listing_price')) {
    function hph_get_listing_price($listing_id, $formatted = true) {
        $price = get_field('listing_price', $listing_id);
        
        if (empty($price)) {
            return '';
        }
        
        return $formatted ? hph_format_price($price) : $price;
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
 * Get listing status
 * @param int $listing_id Listing post ID
 * @return string Listing status (active, sold, pending, etc.)
 */
if (!function_exists('hph_get_listing_status')) {
    function hph_get_listing_status($listing_id) {
        $status = get_field('listing_status', $listing_id);
        return $status ? $status : 'active';
    }
}

/**
 * Get listing address components
 */
function hph_get_listing_address($listing_id, $formatted = true) {
    $street = get_field('listing_street_address', $listing_id);
    $city = get_field('listing_city', $listing_id);
    $state = get_field('listing_state', $listing_id);
    $zip = get_field('listing_zip_code', $listing_id);
    
    if ($formatted) {
        return hph_format_address($street, $city, $state, $zip);
    }
    
    return [
        'street' => $street,
        'city' => $city,
        'state' => $state,
        'zip' => $zip
    ];
}

/**
 * Get listing features
 */
function hph_get_listing_features($listing_id) {
    $cache_key = 'listing_features_' . $listing_id;
    $cached = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $features = [
        'bedrooms' => get_field('listing_bedrooms', $listing_id),
        'bathrooms' => get_field('listing_bathrooms', $listing_id),
        'square_feet' => get_field('listing_square_feet', $listing_id),
        'lot_size' => get_field('listing_lot_size', $listing_id),
        'year_built' => get_field('listing_year_built', $listing_id)
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
