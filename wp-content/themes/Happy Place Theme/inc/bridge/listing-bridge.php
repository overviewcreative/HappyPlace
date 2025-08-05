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
    $data['bathrooms_total'] = get_field('bathrooms_total', $listing_id) ?: 0;
    $data['square_feet'] = get_field('square_footage', $listing_id) ?: '';
    $data['square_footage'] = get_field('square_footage', $listing_id) ?: '';
    $data['lot_size'] = get_field('lot_size', $listing_id) ?: '';
    $data['year_built'] = get_field('year_built', $listing_id) ?: '';
    $data['garage_spaces'] = get_field('garage_spaces', $listing_id) ?: 0;
    $data['stories'] = get_field('stories', $listing_id) ?: 0;
    
    // Address information
    $data['address'] = get_field('street_address', $listing_id) ?: '';
    $data['street_address'] = get_field('street_address', $listing_id) ?: '';
    $data['unit_number'] = get_field('unit_number', $listing_id) ?: '';
    $data['city'] = get_field('city', $listing_id) ?: '';
    $data['state'] = get_field('state', $listing_id) ?: '';
    $data['zip_code'] = get_field('zip_code', $listing_id) ?: '';
    $data['full_address'] = get_field('full_address', $listing_id) ?: '';
    $data['latitude'] = get_field('latitude', $listing_id) ?: '';
    $data['longitude'] = get_field('longitude', $listing_id) ?: '';
    $data['county'] = get_field('county', $listing_id) ?: '';
    $data['parcel_number'] = get_field('parcel_number', $listing_id) ?: '';
    $data['zoning'] = get_field('zoning', $listing_id) ?: '';
    
    // Property type and status (now taxonomies)
    $property_types = wp_get_post_terms($listing_id, 'property_type');
    $data['property_type'] = !empty($property_types) ? $property_types[0]->name : 'Single Family Home';
    
    $property_status = wp_get_post_terms($listing_id, 'property_status');
    $data['status'] = !empty($property_status) ? $property_status[0]->name : 'Active';
    
    // Neighborhood and city taxonomies
    $neighborhoods = wp_get_post_terms($listing_id, 'neighborhood');
    $data['neighborhood'] = !empty($neighborhoods) ? $neighborhoods[0]->name : '';
    
    $cities = wp_get_post_terms($listing_id, 'city');
    $data['city_taxonomy'] = !empty($cities) ? $cities[0]->name : '';
    
    // MLS information
    $data['mls_number'] = get_field('mls_number', $listing_id) ?: '';
    $data['listing_date'] = get_field('listing_date', $listing_id) ?: get_the_date('Y-m-d', $listing_id);
    
    // Features and amenities
    $property_features = wp_get_post_terms($listing_id, 'property_features');
    $data['features'] = array_map(function($term) { return $term->name; }, $property_features);
    
    $data['custom_features'] = get_field('custom_features', $listing_id) ?: [];
    $data['additional_features_text'] = get_field('additional_features_text', $listing_id) ?: '';
    
    // Interior features
    $data['basement'] = get_field('basement', $listing_id) ?: '';
    $data['heating'] = get_field('heating', $listing_id) ?: [];
    $data['cooling'] = get_field('cooling', $listing_id) ?: [];
    $data['fireplace_count'] = get_field('fireplace_count', $listing_id) ?: 0;
    $data['flooring'] = get_field('flooring', $listing_id) ?: [];
    $data['kitchen_features'] = get_field('kitchen_features', $listing_id) ?: [];
    
    // Exterior features
    $data['pool'] = get_field('pool', $listing_id) ?: false;
    $data['hot_tub'] = get_field('hot_tub', $listing_id) ?: false;
    $data['waterfront'] = get_field('waterfront', $listing_id) ?: false;
    $data['view'] = get_field('view', $listing_id) ?: false;
    $data['corner_lot'] = get_field('corner_lot', $listing_id) ?: false;
    $data['lot_features'] = get_field('lot_features', $listing_id) ?: [];
    
    // Smart home and energy features
    $data['smart_home_features'] = get_field('smart_home_features', $listing_id) ?: [];
    $data['energy_features'] = get_field('energy_features', $listing_id) ?: [];
    
    // Images - Updated for new photo gallery structure
    $data['featured_image'] = get_the_post_thumbnail_url($listing_id, 'full');
    
    // New photo gallery with room tagging
    $photos = get_field('listing_photos', $listing_id);
    $data['gallery'] = [];
    $data['photos_by_room'] = [];
    $data['featured_photo'] = null;
    
    if ($photos) {
        // Sort by display order
        usort($photos, function($a, $b) {
            return ($a['photo_order'] ?? 999) - ($b['photo_order'] ?? 999);
        });
        
        foreach ($photos as $photo_data) {
            $photo_info = [
                'image' => $photo_data['photo_image'],
                'room_type' => $photo_data['photo_room_type'],
                'caption' => $photo_data['photo_caption'],
                'order' => $photo_data['photo_order'],
                'featured' => $photo_data['photo_featured']
            ];
            
            $data['gallery'][] = $photo_info;
            
            // Group by room type
            $room_type = $photo_data['photo_room_type'] ?? 'general';
            if (!isset($data['photos_by_room'][$room_type])) {
                $data['photos_by_room'][$room_type] = [];
            }
            $data['photos_by_room'][$room_type][] = $photo_info;
            
            // Set featured photo
            if ($photo_data['photo_featured'] && !$data['featured_photo']) {
                $data['featured_photo'] = $photo_info;
            }
        }
    }
    
    // Agent information - Updated for relationships
    $listing_agent = get_field('listing_agent', $listing_id);
    $data['agent_id'] = $listing_agent ? $listing_agent->ID : null;
    $data['agent'] = $listing_agent ? hph_bridge_get_agent_data($listing_agent->ID) : [];
    
    $co_agent = get_field('co_listing_agent', $listing_id);
    $data['co_agent_id'] = $co_agent ? $co_agent->ID : null;
    $data['co_agent'] = $co_agent ? hph_bridge_get_agent_data($co_agent->ID) : [];
    
    $listing_office = get_field('listing_office', $listing_id);
    $data['office_id'] = $listing_office ? $listing_office->ID : null;
    $data['office'] = $listing_office ? hph_bridge_get_office_data($listing_office->ID) : [];
    
    $data['buyer_agent_commission'] = get_field('buyer_agent_commission', $listing_id) ?: 0;
    
    // Additional fields - Updated field names and new fields
    $data['virtual_tour_url'] = get_field('virtual_tour_url', $listing_id) ?: '';
    $data['listing_virtual_tour'] = get_field('listing_virtual_tour', $listing_id) ?: '';
    $data['property_website'] = get_field('property_website', $listing_id) ?: '';
    
    // Financial data with new field names
    $data['hoa_fees'] = get_field('hoa_fee', $listing_id) ?: 0;
    $data['hoa_fee'] = get_field('hoa_fee', $listing_id) ?: 0;
    $data['hoa_fee_includes'] = get_field('hoa_fee_includes', $listing_id) ?: [];
    $data['property_taxes'] = get_field('annual_taxes', $listing_id) ?: 0;
    $data['annual_taxes'] = get_field('annual_taxes', $listing_id) ?: 0;
    $data['tax_year'] = get_field('tax_year', $listing_id) ?: date('Y');
    
    // Calculated financial fields
    $data['price_per_sqft'] = get_field('price_per_sqft', $listing_id) ?: 0;
    $data['estimated_monthly_taxes'] = get_field('estimated_monthly_taxes', $listing_id) ?: 0;
    $data['days_on_market'] = get_field('days_on_market', $listing_id) ?: 0;
    
    // Mortgage calculation fields
    $data['estimated_down_payment'] = get_field('estimated_down_payment', $listing_id) ?: 20;
    $data['estimated_down_payment_amount'] = get_field('estimated_down_payment_amount', $listing_id) ?: 0;
    $data['interest_rate'] = get_field('interest_rate', $listing_id) ?: 6.5;
    $data['loan_term'] = get_field('loan_term', $listing_id) ?: 30;
    $data['estimated_loan_amount'] = get_field('estimated_loan_amount', $listing_id) ?: 0;
    $data['estimated_monthly_payment'] = get_field('estimated_monthly_payment', $listing_id) ?: 0;
    $data['estimated_monthly_insurance'] = get_field('estimated_monthly_insurance', $listing_id) ?: 0;
    $data['total_monthly_cost'] = get_field('total_monthly_cost', $listing_id) ?: 0;
    
    // Investment analysis
    $data['estimated_monthly_rent'] = get_field('estimated_monthly_rent', $listing_id) ?: 0;
    $data['gross_rental_yield'] = get_field('gross_rental_yield', $listing_id) ?: 0;
    $data['one_percent_rule_ratio'] = get_field('one_percent_rule_ratio', $listing_id) ?: 0;
    
    // Description fields
    $data['listing_description_full'] = get_field('listing_description_full', $listing_id) ?: '';
    $data['listing_description_short'] = get_field('listing_description_short', $listing_id) ?: '';
    $data['listing_highlights'] = get_field('listing_highlights', $listing_id) ?: [];
    
    // Media fields
    $data['listing_video_tour'] = get_field('listing_video_tour', $listing_id) ?: '';
    $data['property_documents'] = get_field('property_documents', $listing_id) ?: [];
    
    // Community relationship
    $community = get_field('community', $listing_id);
    $data['community_id'] = $community ? $community->ID : null;
    $data['community'] = $community ? hph_bridge_get_community_data($community->ID) : [];
    
    // Community HOA data auto-population based on property type
    if ($community && !empty($property_types)) {
        $property_type_slug = $property_types[0]->slug;
        
        switch ($property_type_slug) {
            case 'single-family-home':
                $data['community_hoa_fee'] = get_field('community_hoa_fee_single_family', $community->ID);
                break;
            case 'townhouse':
                $data['community_hoa_fee'] = get_field('community_hoa_fee_townhouse', $community->ID);
                break;
            case 'condo':
                $data['community_hoa_fee'] = get_field('community_hoa_fee_condo', $community->ID);
                break;
        }
    }
    
    // Location scores
    $data['walk_score'] = get_field('walk_score', $listing_id) ?: 0;
    $data['transit_score'] = get_field('transit_score', $listing_id) ?: 0;
    $data['bike_score'] = get_field('bike_score', $listing_id) ?: 0;
    $data['school_district'] = get_field('school_district', $listing_id) ?: '';
    
    // Status flags
    $data['is_featured'] = get_field('is_featured', $listing_id) ?: false;
    $data['price_reduced'] = get_field('price_reduced', $listing_id) ?: false;
    $data['original_price'] = get_field('original_price', $listing_id) ?: 0;
    $data['expiration_date'] = get_field('expiration_date', $listing_id) ?: '';
    $data['address_visibility'] = get_field('address_visibility', $listing_id) ?: 'full';
    
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
    $data['square_feet'] = get_post_meta($listing_id, 'square_footage', true) ?: get_post_meta($listing_id, 'square_feet', true) ?: '';
    $data['square_footage'] = get_post_meta($listing_id, 'square_footage', true) ?: '';
    $data['lot_size'] = get_post_meta($listing_id, 'lot_size', true) ?: '';
    $data['year_built'] = get_post_meta($listing_id, 'year_built', true) ?: '';
    
    // Address
    $data['address'] = get_post_meta($listing_id, 'street_address', true) ?: get_post_meta($listing_id, 'address', true) ?: '';
    $data['street_address'] = get_post_meta($listing_id, 'street_address', true) ?: '';
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
    
    // Price per square foot - updated for new field name
    $square_footage = $raw_data['square_footage'] ?? $raw_data['square_feet'] ?? null;
    if (!empty($raw_data['price']) && !empty($square_footage) && is_numeric($raw_data['price']) && is_numeric($square_footage)) {
        $computed['price_per_sqft'] = round($raw_data['price'] / $square_footage, 2);
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
    
    // Square footage formatting - updated for new field name
    $square_footage = $raw_data['square_footage'] ?? $raw_data['square_feet'] ?? null;
    if (!empty($square_footage)) {
        $computed['square_feet_formatted'] = number_format_i18n($square_footage) . ' sq ft';
        $computed['square_footage_formatted'] = number_format_i18n($square_footage) . ' sq ft';
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
    
    // Try new listing_photos field first
    if (function_exists('get_field')) {
        $listing_photos = get_field('listing_photos', $listing_id);
        if (is_array($listing_photos) && !empty($listing_photos)) {
            // Sort by display order
            usort($listing_photos, function($a, $b) {
                return ($a['photo_order'] ?? 999) - ($b['photo_order'] ?? 999);
            });
            
            foreach ($listing_photos as $photo_data) {
                $image = $photo_data['photo_image'];
                if ($image) {
                    $gallery[] = [
                        'ID' => $image['ID'],
                        'url' => $image['sizes']['full'] ?? $image['url'],
                        'alt' => $image['alt'] ?: $photo_data['photo_caption'] ?: get_the_title($listing_id),
                        'sizes' => $image['sizes'] ?? [],
                        'room_type' => $photo_data['photo_room_type'] ?? 'general',
                        'caption' => $photo_data['photo_caption'] ?? '',
                        'order' => $photo_data['photo_order'] ?? 999,
                        'featured' => $photo_data['photo_featured'] ?? false
                    ];
                }
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
    
    // Get features from taxonomy and ACF custom features
    $all_features = [];
    
    // Get taxonomy-based features
    $property_features = wp_get_post_terms($listing_id, 'property_features');
    if (!empty($property_features)) {
        $all_features['property_features'] = array_map(function($term) { return $term->name; }, $property_features);
    }
    
    // Get custom features from ACF
    if (function_exists('get_field')) {
        $custom_features = get_field('custom_features', $listing_id);
        if (!empty($custom_features)) {
            $all_features['custom_features'] = is_array($custom_features) ? $custom_features : [$custom_features];
        }
        
        // Get additional features text
        $additional_features = get_field('additional_features_text', $listing_id);
        if (!empty($additional_features)) {
            $all_features['additional_features'] = $additional_features;
        }
    }
    
    // Fallback to meta if no features found
    if (empty($all_features)) {
        $raw_features = get_post_meta($listing_id, 'features', true);
        if (is_array($raw_features)) {
            $all_features = $raw_features;
        } else {
            // Default features based on property data
            $listing_data = hph_bridge_get_listing_data($listing_id);
            $all_features = hph_generate_default_features($listing_data);
        }
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
    
    $square_footage = $listing_data['square_footage'] ?? $listing_data['square_feet'] ?? null;
    if (!empty($square_footage)) {
        $features['interior'][] = number_format_i18n($square_footage) . ' Square Feet';
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
    
    // Similar property type (now using taxonomy)
    if (!empty($current_listing['property_type'])) {
        $query_args['tax_query'] = ['relation' => 'AND'];
        $query_args['tax_query'][] = [
            'taxonomy' => 'property_type',
            'field' => 'name',
            'terms' => $current_listing['property_type']
        ];
    }
    
    // Similar city (check if it's a taxonomy or meta field)
    if (!empty($current_listing['city'])) {
        // Try taxonomy first, fallback to meta
        $city_terms = get_terms(['taxonomy' => 'city', 'hide_empty' => false, 'name' => $current_listing['city']]);
        if (!empty($city_terms)) {
            if (!isset($query_args['tax_query'])) {
                $query_args['tax_query'] = ['relation' => 'AND'];
            }
            $query_args['tax_query'][] = [
                'taxonomy' => 'city',
                'field' => 'name',
                'terms' => $current_listing['city']
            ];
        } else {
            // Fallback to meta query
            $query_args['meta_query'][] = [
                'key' => 'city',
                'value' => $current_listing['city'],
                'compare' => '='
            ];
        }
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
                'square_feet' => get_post_meta($similar_id, 'square_footage', true) ?: get_post_meta($similar_id, 'square_feet', true)
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
                    'square_feet' => get_post_meta($random_id, 'square_footage', true) ?: get_post_meta($random_id, 'square_feet', true)
                ];
            }
            wp_reset_postdata();
        }
    }
    
    wp_cache_set($cache_key, $similar_listings, 'hph_listings', 1800); // 30 minutes
    
    return $similar_listings;
}
}

// Financial data functions are handled in financial-bridge.php

/**
 * Get agent data for listings
 * 
 * @param int $agent_id Agent post ID
 * @return array Agent data
 */
if (!function_exists('hph_bridge_get_agent_data')) {
    function hph_bridge_get_agent_data($agent_id) {
        $cache_key = "hph_agent_data_{$agent_id}";
        $cached_data = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $agent_data = [];
        
        // Basic post data
        $post = get_post($agent_id);
        if (!$post || $post->post_type !== 'agent') {
            return [];
        }
        
        $agent_data['id'] = $agent_id;
        $agent_data['title'] = get_the_title($agent_id);
        $agent_data['url'] = get_permalink($agent_id);
        
        // Get ACF data if available
        if (function_exists('get_field')) {
            $agent_data['first_name'] = get_field('agent_first_name', $agent_id) ?: '';
            $agent_data['last_name'] = get_field('agent_last_name', $agent_id) ?: '';
            $agent_data['email'] = get_field('agent_email', $agent_id) ?: '';
            $agent_data['phone'] = get_field('agent_phone', $agent_id) ?: '';
            $agent_data['mobile'] = get_field('agent_mobile', $agent_id) ?: '';
            $agent_data['license_number'] = get_field('agent_license_number', $agent_id) ?: '';
            $agent_data['license_state'] = get_field('agent_license_state', $agent_id) ?: '';
            $agent_data['title_professional'] = get_field('agent_title', $agent_id) ?: '';
            $agent_data['bio'] = get_field('agent_bio', $agent_id) ?: '';
            $agent_data['years_experience'] = get_field('agent_years_experience', $agent_id) ?: 0;
            $agent_data['specialties'] = get_field('agent_specialties', $agent_id) ?: [];
            $agent_data['languages'] = get_field('agent_languages', $agent_id) ?: '';
            
            // Online presence
            $agent_data['website'] = get_field('agent_website', $agent_id) ?: '';
            $agent_data['linkedin'] = get_field('agent_linkedin', $agent_id) ?: '';
            $agent_data['facebook'] = get_field('agent_facebook', $agent_id) ?: '';
            $agent_data['instagram'] = get_field('agent_instagram', $agent_id) ?: '';
            $agent_data['youtube'] = get_field('agent_youtube', $agent_id) ?: '';
            $agent_data['twitter'] = get_field('agent_twitter', $agent_id) ?: '';
            
            // Office relationship
            $office = get_field('agent_office', $agent_id);
            $agent_data['office_id'] = $office ? $office->ID : null;
            $agent_data['office_name'] = $office ? get_the_title($office->ID) : '';
            
            // Statistics
            $agent_data['total_sales_volume'] = get_field('agent_total_sales_volume', $agent_id) ?: 0;
            $agent_data['total_transactions'] = get_field('agent_total_transactions', $agent_id) ?: 0;
            $agent_data['average_sale_price'] = get_field('agent_average_sale_price', $agent_id) ?: 0;
            $agent_data['active_listings_count'] = get_field('agent_active_listings_count', $agent_id) ?: 0;
            $agent_data['sold_listings_count'] = get_field('agent_sold_listings_count', $agent_id) ?: 0;
            $agent_data['average_dom'] = get_field('agent_average_dom', $agent_id) ?: 0;
            $agent_data['rating'] = get_field('agent_rating', $agent_id) ?: 0;
        }
        
        // Image
        $agent_data['image'] = get_the_post_thumbnail_url($agent_id, 'medium');
        
        wp_cache_set($cache_key, $agent_data, 'hph_agents', 3600);
        
        return $agent_data;
    }
}

/**
 * Get office data for listings
 * 
 * @param int $office_id Office post ID
 * @return array Office data
 */
if (!function_exists('hph_bridge_get_office_data')) {
    function hph_bridge_get_office_data($office_id) {
        $cache_key = "hph_office_data_{$office_id}";
        $cached_data = wp_cache_get($cache_key, 'hph_offices');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $office_data = [];
        
        // Basic post data
        $post = get_post($office_id);
        if (!$post || $post->post_type !== 'office') {
            return [];
        }
        
        $office_data['id'] = $office_id;
        $office_data['title'] = get_the_title($office_id);
        $office_data['url'] = get_permalink($office_id);
        
        // Get ACF data if available
        if (function_exists('get_field')) {
            $office_data['name'] = get_field('office_name', $office_id) ?: get_the_title($office_id);
            $office_data['phone'] = get_field('office_phone', $office_id) ?: '';
            $office_data['email'] = get_field('office_email', $office_id) ?: '';
            $office_data['fax'] = get_field('office_fax', $office_id) ?: '';
            $office_data['license'] = get_field('office_license', $office_id) ?: '';
            $office_data['established'] = get_field('office_established', $office_id) ?: '';
            
            // Location
            $office_data['address'] = get_field('office_address', $office_id) ?: '';
            $office_data['suite'] = get_field('office_suite', $office_id) ?: '';
            $office_data['city'] = get_field('office_city', $office_id) ?: '';
            $office_data['state'] = get_field('office_state', $office_id) ?: '';
            $office_data['zip'] = get_field('office_zip', $office_id) ?: '';
            $office_data['latitude'] = get_field('office_latitude', $office_id) ?: '';
            $office_data['longitude'] = get_field('office_longitude', $office_id) ?: '';
            
            // Details
            $office_data['description'] = get_field('office_description', $office_id) ?: '';
            $office_data['hours'] = get_field('office_hours', $office_id) ?: [];
            $office_data['services'] = get_field('office_services', $office_id) ?: [];
            
            // Management
            $broker = get_field('office_broker', $office_id);
            $office_data['broker_id'] = $broker ? $broker->ID : null;
            $office_data['broker_name'] = $broker ? get_the_title($broker->ID) : '';
            $office_data['manager'] = get_field('office_manager', $office_id) ?: '';
            $office_data['franchise'] = get_field('office_franchise', $office_id) ?: '';
            
            // Statistics
            $office_data['agent_count'] = get_field('office_agent_count', $office_id) ?: 0;
            $office_data['active_listings'] = get_field('office_active_listings', $office_id) ?: 0;
            $office_data['total_sales_volume'] = get_field('office_total_sales_volume', $office_id) ?: 0;
            $office_data['average_sale_price'] = get_field('office_average_sale_price', $office_id) ?: 0;
            
            // Online presence
            $office_data['website'] = get_field('office_website', $office_id) ?: '';
            $office_data['facebook'] = get_field('office_facebook', $office_id) ?: '';
            $office_data['linkedin'] = get_field('office_linkedin', $office_id) ?: '';
            $office_data['google_business'] = get_field('office_google_business', $office_id) ?: '';
        }
        
        // Image
        $office_data['image'] = get_the_post_thumbnail_url($office_id, 'medium');
        
        wp_cache_set($cache_key, $office_data, 'hph_offices', 3600);
        
        return $office_data;
    }
}

/**
 * Get community data for listings
 * 
 * @param int $community_id Community post ID
 * @return array Community data
 */
if (!function_exists('hph_bridge_get_community_data')) {
    function hph_bridge_get_community_data($community_id) {
        $cache_key = "hph_community_data_{$community_id}";
        $cached_data = wp_cache_get($cache_key, 'hph_communities');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $community_data = [];
        
        // Basic post data
        $post = get_post($community_id);
        if (!$post || $post->post_type !== 'community') {
            return [];
        }
        
        $community_data['id'] = $community_id;
        $community_data['title'] = get_the_title($community_id);
        $community_data['url'] = get_permalink($community_id);
        
        // Get ACF data if available
        if (function_exists('get_field')) {
            $community_data['name'] = get_field('community_name', $community_id) ?: get_the_title($community_id);
            $community_data['developer'] = get_field('community_developer', $community_id) ?: '';
            $community_data['established'] = get_field('community_established', $community_id) ?: '';
            $community_data['total_homes'] = get_field('community_total_homes', $community_id) ?: 0;
            $community_data['total_acres'] = get_field('community_total_acres', $community_id) ?: 0;
            $community_data['description'] = get_field('community_description', $community_id) ?: '';
            
            // HOA Information
            $community_data['hoa_name'] = get_field('community_hoa_name', $community_id) ?: '';
            $community_data['hoa_management_company'] = get_field('community_hoa_management_company', $community_id) ?: '';
            $community_data['hoa_phone'] = get_field('community_hoa_phone', $community_id) ?: '';
            $community_data['hoa_email'] = get_field('community_hoa_email', $community_id) ?: '';
            $community_data['hoa_website'] = get_field('community_hoa_website', $community_id) ?: '';
            $community_data['hoa_fee_single_family'] = get_field('community_hoa_fee_single_family', $community_id) ?: 0;
            $community_data['hoa_fee_townhouse'] = get_field('community_hoa_fee_townhouse', $community_id) ?: 0;
            $community_data['hoa_fee_condo'] = get_field('community_hoa_fee_condo', $community_id) ?: 0;
            $community_data['hoa_fee_special_assessment'] = get_field('community_hoa_fee_special_assessment', $community_id) ?: 0;
            $community_data['hoa_includes'] = get_field('community_hoa_includes', $community_id) ?: [];
            
            // Amenities
            $community_data['pool_count'] = get_field('community_pool_count', $community_id) ?: 0;
            $community_data['pool_types'] = get_field('community_pool_types', $community_id) ?: [];
            $community_data['tennis_courts'] = get_field('community_tennis_courts', $community_id) ?: 0;
            $community_data['sport_courts'] = get_field('community_sport_courts', $community_id) ?: [];
            $community_data['clubhouse_features'] = get_field('community_clubhouse_features', $community_id) ?: [];
            $community_data['outdoor_amenities'] = get_field('community_outdoor_amenities', $community_id) ?: [];
            $community_data['additional_amenities'] = get_field('community_additional_amenities', $community_id) ?: '';
            
            // Buildings
            $community_data['buildings'] = get_field('community_buildings', $community_id) ?: [];
            
            // Rules & Restrictions
            $community_data['pet_policy'] = get_field('community_pet_policy', $community_id) ?: '';
            $community_data['rental_restrictions'] = get_field('community_rental_restrictions', $community_id) ?: '';
            $community_data['age_restrictions'] = get_field('community_age_restrictions', $community_id) ?: '';
            $community_data['architectural_guidelines'] = get_field('community_architectural_guidelines', $community_id) ?: '';
            
            // Statistics
            $community_data['active_listings'] = get_field('community_active_listings', $community_id) ?: 0;
            $community_data['sold_last_12_months'] = get_field('community_sold_last_12_months', $community_id) ?: 0;
            $community_data['average_sale_price'] = get_field('community_average_sale_price', $community_id) ?: 0;
            $community_data['average_dom'] = get_field('community_average_dom', $community_id) ?: 0;
        }
        
        // Image
        $community_data['image'] = get_the_post_thumbnail_url($community_id, 'medium');
        
        wp_cache_set($cache_key, $community_data, 'hph_communities', 3600);
        
        return $community_data;
    }
}