<?php
/**
 * Complete Bridge Functions System
 * 
 * These functions provide clean data access between the Happy Place plugin
 * and theme, with comprehensive fallback support when the plugin is inactive.
 */

// =============================================================================
// FILE: inc/bridge/listing-bridge.php
// =============================================================================

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get comprehensive listing data for display
 * 
 * @param int $listing_id Listing post ID
 * @return array Formatted listing data
 */
if (!function_exists('hph_bridge_get_listing_data')) {
    function hph_bridge_get_listing_data($listing_id) {
        $cache_key = "hph_listing_data_{$listing_id}";
        $cached_data = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
    
    $listing_data = [];
    
    // Basic post data
    $post = get_post($listing_id);
    if (!$post || $post->post_type !== 'listing') {
        return hph_fallback_get_listing_data($listing_id);
    }
    
    $listing_data['id'] = $listing_id;
    $listing_data['title'] = get_the_title($listing_id);
    $listing_data['url'] = get_permalink($listing_id);
    $listing_data['description'] = get_the_excerpt($listing_id) ?: wp_trim_words(get_the_content(null, false, $listing_id), 25);
    
    // Try to get ACF data first (if available)
    if (function_exists('get_field')) {
        $listing_data = array_merge($listing_data, hph_get_acf_listing_data($listing_id));
    } else {
        // Fall back to post meta
        $listing_data = array_merge($listing_data, hph_get_meta_listing_data($listing_id));
    }
    
    // Get computed/formatted data
    $listing_data = array_merge($listing_data, hph_get_computed_listing_data($listing_id, $listing_data));
    
    // Cache the result
    wp_cache_set($cache_key, $listing_data, 'hph_listings', 3600);
    
    return $listing_data;
}

/**
 * Get ACF-based listing data
 * 
 * @param int $listing_id Listing ID
 * @return array ACF data
 */
function hph_get_acf_listing_data($listing_id) {
    $data = [];
    
    // Price information
    $data['price'] = get_field('price', $listing_id) ?: 0;
    $data['price_formatted'] = hph_format_price($data['price']);
    
    // Property details
    $data['bedrooms'] = get_field('bedrooms', $listing_id) ?: '';
    $data['bathrooms'] = get_field('bathrooms', $listing_id) ?: '';
    $data['half_bathrooms'] = get_field('half_bathrooms', $listing_id) ?: 0;
    $data['square_feet'] = get_field('square_feet', $listing_id) ?: '';
    $data['lot_size'] = get_field('lot_size', $listing_id) ?: '';
    $data['year_built'] = get_field('year_built', $listing_id) ?: '';
    $data['garage_spaces'] = get_field('garage_spaces', $listing_id) ?: 0;
    
    // Address information
    $data['address'] = get_field('address', $listing_id) ?: '';
    $data['city'] = get_field('city', $listing_id) ?: '';
    $data['state'] = get_field('state', $listing_id) ?: '';
    $data['zip_code'] = get_field('zip_code', $listing_id) ?: '';
    $data['neighborhood'] = get_field('neighborhood', $listing_id) ?: '';
    
    // Property type and status
    $data['property_type'] = get_field('property_type', $listing_id) ?: 'Single Family Home';
    $data['status'] = get_field('status', $listing_id) ?: 'Available';
    
    // MLS information
    $data['mls_number'] = get_field('mls_number', $listing_id) ?: '';
    $data['listing_date'] = get_field('listing_date', $listing_id) ?: get_the_date('Y-m-d', $listing_id);
    
    // Features and amenities
    $data['features'] = get_field('features', $listing_id) ?: [];
    $data['amenities'] = get_field('amenities', $listing_id) ?: [];
    $data['appliances'] = get_field('appliances', $listing_id) ?: [];
    
    // Images
    $data['featured_image'] = get_the_post_thumbnail_url($listing_id, 'full');
    $gallery = get_field('property_gallery', $listing_id);
    $data['gallery'] = is_array($gallery) ? $gallery : [];
    
    // Agent information
    $agent_field = get_field('listing_agent', $listing_id);
    $agent_id = is_object($agent_field) ? $agent_field->ID : $agent_field;
    $data['agent_id'] = $agent_id;
    $data['agent'] = $agent_id ? hph_bridge_get_agent_data($agent_id) : [];
    
    // Additional fields
    $data['virtual_tour_url'] = get_field('virtual_tour_url', $listing_id) ?: '';
    $data['hoa_fees'] = get_field('hoa_fees', $listing_id) ?: 0;
    $data['property_taxes'] = get_field('property_taxes', $listing_id) ?: 0;
    $data['is_featured'] = get_field('is_featured', $listing_id) ?: false;
    $data['price_reduced'] = get_field('price_reduced', $listing_id) ?: false;
    
    return $data;
}

/**
 * Get post meta-based listing data (fallback)
 * 
 * @param int $listing_id Listing ID
 * @return array Meta data
 */
function hph_get_meta_listing_data($listing_id) {
    $data = [];
    
    // Price
    $data['price'] = get_post_meta($listing_id, 'price', true) ?: 0;
    $data['price_formatted'] = hph_format_price($data['price']);
    
    // Basic details
    $data['bedrooms'] = get_post_meta($listing_id, 'bedrooms', true) ?: '';
    $data['bathrooms'] = get_post_meta($listing_id, 'bathrooms', true) ?: '';
    $data['square_feet'] = get_post_meta($listing_id, 'square_feet', true) ?: '';
    $data['lot_size'] = get_post_meta($listing_id, 'lot_size', true) ?: '';
    $data['year_built'] = get_post_meta($listing_id, 'year_built', true) ?: '';
    
    // Address
    $data['address'] = get_post_meta($listing_id, 'address', true) ?: '';
    $data['city'] = get_post_meta($listing_id, 'city', true) ?: '';
    $data['state'] = get_post_meta($listing_id, 'state', true) ?: '';
    $data['zip_code'] = get_post_meta($listing_id, 'zip_code', true) ?: '';
    
    // Type and status
    $data['property_type'] = get_post_meta($listing_id, 'property_type', true) ?: 'Single Family Home';
    $data['status'] = get_post_meta($listing_id, 'status', true) ?: 'Available';
    
    // MLS
    $data['mls_number'] = get_post_meta($listing_id, 'mls_number', true) ?: '';
    $data['listing_date'] = get_post_meta($listing_id, 'listing_date', true) ?: get_the_date('Y-m-d', $listing_id);
    
    // Images
    $data['featured_image'] = get_the_post_thumbnail_url($listing_id, 'full');
    $data['gallery'] = hph_get_gallery_from_meta($listing_id);
    
    // Agent
    $agent_id = get_post_meta($listing_id, 'agent_id', true);
    $data['agent_id'] = $agent_id;
    $data['agent'] = $agent_id ? hph_bridge_get_agent_data($agent_id) : [];
    
    // Features (stored as serialized or JSON)
    $features = get_post_meta($listing_id, 'features', true);
    $data['features'] = is_array($features) ? $features : [];
    
    // Flags
    $data['is_featured'] = (bool) get_post_meta($listing_id, 'is_featured', true);
    $data['price_reduced'] = (bool) get_post_meta($listing_id, 'price_reduced', true);
    
    return $data;
}

/**
 * Get computed/formatted listing data
 * 
 * @param int $listing_id Listing ID
 * @param array $raw_data Raw listing data
 * @return array Computed data
 */
function hph_get_computed_listing_data($listing_id, $raw_data) {
    $computed = [];
    
    // Full address
    $address_parts = array_filter([
        $raw_data['address'] ?? '',
        $raw_data['city'] ?? '',
        $raw_data['state'] ?? '',
        $raw_data['zip_code'] ?? ''
    ]);
    $computed['full_address'] = implode(', ', $address_parts);
    
    // Price per square foot
    if (!empty($raw_data['price']) && !empty($raw_data['square_feet']) && is_numeric($raw_data['price']) && is_numeric($raw_data['square_feet'])) {
        $computed['price_per_sqft'] = round($raw_data['price'] / $raw_data['square_feet'], 2);
        $computed['price_per_sqft_formatted'] = '$' . number_format_i18n($computed['price_per_sqft']);
    }
    
    // Days on market
    $listing_date = $raw_data['listing_date'] ?? get_the_date('Y-m-d', $listing_id);
    if ($listing_date) {
        $date_diff = strtotime('now') - strtotime($listing_date);
        $computed['days_on_market'] = max(0, floor($date_diff / (60 * 60 * 24)));
    }
    
    // New listing flag (less than 7 days)
    $computed['is_new'] = ($computed['days_on_market'] ?? 999) <= 7;
    
    // Status formatting
    $status = $raw_data['status'] ?? 'Available';
    $computed['status_formatted'] = ucwords(str_replace(['_', '-'], ' ', $status));
    $computed['status_class'] = strtolower(str_replace([' ', '_'], '-', $status));
    
    // Bathroom formatting
    $bathrooms = $raw_data['bathrooms'] ?? 0;
    $half_baths = $raw_data['half_bathrooms'] ?? 0;
    if ($half_baths > 0) {
        $computed['bathrooms_formatted'] = $bathrooms . '.' . $half_baths;
    } else {
        $computed['bathrooms_formatted'] = $bathrooms;
    }
    
    // Square footage formatting
    if (!empty($raw_data['square_feet'])) {
        $computed['square_feet_formatted'] = number_format_i18n($raw_data['square_feet']) . ' sq ft';
    }
    
    // Lot size formatting
    if (!empty($raw_data['lot_size'])) {
        $lot_size = $raw_data['lot_size'];
        if (is_numeric($lot_size)) {
            // Assume acres if numeric
            $computed['lot_size_formatted'] = $lot_size . ' acres';
        } else {
            $computed['lot_size_formatted'] = $lot_size;
        }
    }
    
    return $computed;
}

/**
 * Get hero data for single listing pages
 * 
 * @param int $listing_id Listing ID
 * @return array Hero data
 */
function hph_bridge_get_hero_data($listing_id) {
    $cache_key = "hph_hero_data_{$listing_id}";
    $cached_data = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    // Get base listing data
    $listing_data = hph_bridge_get_listing_data($listing_id);
    
    // Extract hero-specific data
    $hero_data = [
        'title' => $listing_data['title'],
        'price' => $listing_data['price_formatted'],
        'address' => $listing_data['full_address'],
        'status' => $listing_data['status_formatted'],
        'status_class' => $listing_data['status_class'],
        'featured_image' => $listing_data['featured_image'],
        'mls_number' => $listing_data['mls_number'],
        'days_on_market' => $listing_data['days_on_market'] ?? 0,
        'bedrooms' => $listing_data['bedrooms'],
        'bathrooms' => $listing_data['bathrooms_formatted'],
        'square_feet' => $listing_data['square_feet_formatted'],
        'lot_size' => $listing_data['lot_size_formatted'],
        'images' => hph_bridge_get_gallery_data($listing_id)
    ];
    
    wp_cache_set($cache_key, $hero_data, 'hph_listings', 3600);
    
    return $hero_data;
}

/**
 * Get gallery data for listing
 * 
 * @param int $listing_id Listing ID
 * @return array Gallery images
 */
function hph_bridge_get_gallery_data($listing_id) {
    $cache_key = "hph_gallery_data_{$listing_id}";
    $cached_data = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $gallery = [];
    
    // Try ACF gallery field first
    if (function_exists('get_field')) {
        $acf_gallery = get_field('property_gallery', $listing_id);
        if (is_array($acf_gallery) && !empty($acf_gallery)) {
            foreach ($acf_gallery as $image) {
                $gallery[] = [
                    'ID' => $image['ID'],
                    'url' => $image['sizes']['full'] ?? $image['url'],
                    'alt' => $image['alt'] ?: get_the_title($listing_id),
                    'sizes' => $image['sizes'] ?? []
                ];
            }
        }
    }
    
    // Fallback to post meta or featured image
    if (empty($gallery)) {
        $gallery = hph_get_gallery_from_meta($listing_id);
    }
    
    // Final fallback to featured image
    if (empty($gallery) && has_post_thumbnail($listing_id)) {
        $featured_id = get_post_thumbnail_id($listing_id);
        $gallery[] = [
            'ID' => $featured_id,
            'url' => get_the_post_thumbnail_url($listing_id, 'full'),
            'alt' => get_post_meta($featured_id, '_wp_attachment_image_alt', true) ?: get_the_title($listing_id),
            'sizes' => [
                'full' => get_the_post_thumbnail_url($listing_id, 'full'),
                'large' => get_the_post_thumbnail_url($listing_id, 'large'),
                'medium_large' => get_the_post_thumbnail_url($listing_id, 'medium_large')
            ]
        ];
    }
    
    wp_cache_set($cache_key, $gallery, 'hph_listings', 3600);
    
    return $gallery;
}

/**
 * Get gallery from post meta (fallback)
 * 
 * @param int $listing_id Listing ID
 * @return array Gallery images
 */
function hph_get_gallery_from_meta($listing_id) {
    $gallery = [];
    $gallery_ids = get_post_meta($listing_id, 'gallery_ids', true);
    
    if (is_string($gallery_ids)) {
        $gallery_ids = explode(',', $gallery_ids);
    }
    
    if (is_array($gallery_ids)) {
        foreach ($gallery_ids as $image_id) {
            $image_id = intval($image_id);
            if ($image_id > 0) {
                $gallery[] = [
                    'ID' => $image_id,
                    'url' => wp_get_attachment_image_url($image_id, 'full'),
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($listing_id),
                    'sizes' => [
                        'full' => wp_get_attachment_image_url($image_id, 'full'),
                        'large' => wp_get_attachment_image_url($image_id, 'large'),
                        'medium_large' => wp_get_attachment_image_url($image_id, 'medium_large')
                    ]
                ];
            }
        }
    }
    
    return $gallery;
}

/**
 * Get property features organized by category
 * 
 * @param int $listing_id Listing ID
 * @param string $category Optional category filter
 * @return array Features by category
 */
function hph_bridge_get_features($listing_id, $category = 'all') {
    $cache_key = "hph_features_{$listing_id}_{$category}";
    $cached_data = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $all_features = [];
    
    // Get features from ACF or meta
    $raw_features = function_exists('get_field') ? get_field('features', $listing_id) : get_post_meta($listing_id, 'features', true);
    
    if (is_array($raw_features)) {
        $all_features = $raw_features;
    } else {
        // Default features based on property data
        $listing_data = hph_bridge_get_listing_data($listing_id);
        $all_features = hph_generate_default_features($listing_data);
    }
    
    // Filter by category if specified
    if ($category !== 'all' && isset($all_features[$category])) {
        $result = [$category => $all_features[$category]];
    } else {
        $result = $all_features;
    }
    
    wp_cache_set($cache_key, $result, 'hph_listings', 3600);
    
    return $result;
}

/**
 * Generate default features based on listing data
 * 
 * @param array $listing_data Listing data
 * @return array Default features
 */
function hph_generate_default_features($listing_data) {
    $features = [
        'interior' => [],
        'exterior' => [],
        'community' => []
    ];
    
    // Interior features based on data
    if (!empty($listing_data['bedrooms'])) {
        $features['interior'][] = $listing_data['bedrooms'] . ' Bedrooms';
    }
    
    if (!empty($listing_data['bathrooms'])) {
        $features['interior'][] = $listing_data['bathrooms'] . ' Bathrooms';
    }
    
    if (!empty($listing_data['square_feet'])) {
        $features['interior'][] = number_format_i18n($listing_data['square_feet']) . ' Square Feet';
    }
    
    // Exterior features
    if (!empty($listing_data['garage_spaces'])) {
        $features['exterior'][] = $listing_data['garage_spaces'] . ' Car Garage';
    }
    
    if (!empty($listing_data['lot_size'])) {
        $features['exterior'][] = $listing_data['lot_size'] . ' Lot';
    }
    
    if (!empty($listing_data['year_built'])) {
        $features['exterior'][] = 'Built in ' . $listing_data['year_built'];
    }
    
    return array_filter($features);
}

/**
 * Get similar listings based on location, price, and property type
 * 
 * @param int $listing_id Current listing ID
 * @param int $count Number of similar listings to return
 * @return array Similar listings data
 */
function hph_bridge_get_similar_listings($listing_id, $count = 3) {
    $cache_key = "hph_similar_{$listing_id}_{$count}";
    $cached_data = wp_cache_get($cache_key, 'hph_listings');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $current_listing = hph_bridge_get_listing_data($listing_id);
    
    // Build query for similar listings
    $query_args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => $count * 2, // Get more to filter and randomize
        'post__not_in' => [$listing_id],
        'meta_query' => ['relation' => 'AND']
    ];
    
    // Similar property type
    if (!empty($current_listing['property_type'])) {
        $query_args['meta_query'][] = [
            'key' => 'property_type',
            'value' => $current_listing['property_type'],
            'compare' => '='
        ];
    }
    
    // Similar city
    if (!empty($current_listing['city'])) {
        $query_args['meta_query'][] = [
            'key' => 'city',
            'value' => $current_listing['city'],
            'compare' => '='
        ];
    }
    
    // Similar price range (+/- 25%)
    if (!empty($current_listing['price']) && is_numeric($current_listing['price'])) {
        $price = floatval($current_listing['price']);
        $min_price = $price * 0.75;
        $max_price = $price * 1.25;
        
        $query_args['meta_query'][] = [
            'key' => 'price',
            'value' => [$min_price, $max_price],
            'type' => 'NUMERIC',
            'compare' => 'BETWEEN'
        ];
    }
    
    $similar_query = new WP_Query($query_args);
    $similar_listings = [];
    
    if ($similar_query->have_posts()) {
        while ($similar_query->have_posts() && count($similar_listings) < $count) {
            $similar_query->the_post();
            $similar_id = get_the_ID();
            
            $similar_listings[] = [
                'id' => $similar_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'price' => hph_format_price(get_post_meta($similar_id, 'price', true)),
                'image' => get_the_post_thumbnail_url($similar_id, 'medium_large'),
                'bedrooms' => get_post_meta($similar_id, 'bedrooms', true),
                'bathrooms' => get_post_meta($similar_id, 'bathrooms', true),
                'square_feet' => get_post_meta($similar_id, 'square_feet', true)
            ];
        }
        wp_reset_postdata();
    }
    
    // If we don't have enough similar listings, get random listings
    if (count($similar_listings) < $count) {
        $additional_needed = $count - count($similar_listings);
        $existing_ids = array_merge([$listing_id], array_column($similar_listings, 'id'));
        
        $random_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $additional_needed,
            'post__not_in' => $existing_ids,
            'orderby' => 'rand'
        ]);
        
        if ($random_query->have_posts()) {
            while ($random_query->have_posts()) {
                $random_query->the_post();
                $random_id = get_the_ID();
                
                $similar_listings[] = [
                    'id' => $random_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'price' => hph_format_price(get_post_meta($random_id, 'price', true)),
                    'image' => get_the_post_thumbnail_url($random_id, 'medium_large'),
                    'bedrooms' => get_post_meta($random_id, 'bedrooms', true),
                    'bathrooms' => get_post_meta($random_id, 'bathrooms', true),
                    'square_feet' => get_post_meta($random_id, 'square_feet', true)
                ];
            }
            wp_reset_postdata();
        }
    }
    
    wp_cache_set($cache_key, $similar_listings, 'hph_listings', 1800); // 30 minutes
    
    return $similar_listings;
}
}

/**
 * Get financial data for mortgage calculator
 */
if (!function_exists('hph_bridge_get_financial_data')) {
    function hph_bridge_get_financial_data($listing_id) {
        $cache_key = "hph_financial_data_{$listing_id}";
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $financial_data = [
            'price' => get_post_meta($listing_id, '_listing_price', true) ?: 0,
            'down_payment_percent' => 20,
            'interest_rate' => 6.5,
            'loan_term_years' => 30,
            'property_tax_rate' => get_post_meta($listing_id, '_listing_tax_rate', true) ?: 1.2,
            'insurance_annual' => 1200,
            'hoa_monthly' => get_post_meta($listing_id, '_listing_hoa_fee', true) ?: 0,
            'pmi_rate' => 0.5
        ];
        
        wp_cache_set($cache_key, $financial_data, 'hph_listings', 3600);
        return $financial_data;
    }
}