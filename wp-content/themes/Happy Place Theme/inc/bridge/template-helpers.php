<?php
/**
 * Format price for display
 * 
 * @param mixed $price Price value
 * @return string Formatted price
 */
function hph_format_price($price) {
    if (empty($price) || !is_numeric($price)) {
        return 'Contact for Price';
    }
    
    $price = floatval($price);
    
    if ($price >= 1000000) {
        return '$' . number_format_i18n($price / 1000000, 1) . 'M';
    } elseif ($price >= 1000) {
        return '$' . number_format_i18n($price / 1000, 0) . 'K';
    }
    
    return '$' . number_format_i18n($price, 0);
}

/**
 * Get template-specific listing data (optimized for display)
 * 
 * @param int $listing_id Listing ID
 * @return array Template-ready data
 */
function hph_get_template_listing_data($listing_id) {
    $raw_data = hph_bridge_get_listing_data($listing_id);
    
    // Return sanitized, display-ready data
    return [
        'id' => $listing_id,
        'title' => esc_html($raw_data['title']),
        'url' => esc_url($raw_data['url']),
        'description' => wp_kses_post($raw_data['description']),
        'price' => esc_html($raw_data['price_formatted']),
        'address' => esc_html($raw_data['full_address']),
        'status' => esc_html($raw_data['status_formatted']),
        'status_class' => esc_attr($raw_data['status_class']),
        'bedrooms' => esc_html($raw_data['bedrooms']),
        'bathrooms' => esc_html($raw_data['bathrooms_formatted']),
        'square_feet' => esc_html($raw_data['square_feet_formatted']),
        'lot_size' => esc_html($raw_data['lot_size_formatted']),
        'property_type' => esc_html($raw_data['property_type']),
        'mls_number' => esc_html($raw_data['mls_number']),
        'days_on_market' => intval($raw_data['days_on_market'] ?? 0),
        'is_new' => (bool) $raw_data['is_new'],
        'is_featured' => (bool) $raw_data['is_featured'],
        'featured_image' => esc_url($raw_data['featured_image']),
        'gallery' => $raw_data['gallery'] ?? [],
        'features' => $raw_data['features'] ?? [],
        'agent' => $raw_data['agent'] ?? []
    ];
}

/**
 * Fallback listing data when plugin is inactive
 * 
 * @param int $listing_id Listing ID
 * @return array Fallback data
 */
function hph_fallback_get_listing_data($listing_id) {
    $post = get_post($listing_id);
    
    if (!$post) {
        return [];
    }
    
    return [
        'id' => $listing_id,
        'title' => get_the_title($listing_id),
        'url' => get_permalink($listing_id),
        'description' => get_the_excerpt($listing_id) ?: wp_trim_words(get_the_content(null, false, $listing_id), 25),
        'price' => 0,
        'price_formatted' => 'Contact for Price',
        'address' => '',
        'full_address' => '',
        'status' => 'Available',
        'status_formatted' => 'Available',
        'status_class' => 'available',
        'bedrooms' => '',
        'bathrooms' => '',
        'bathrooms_formatted' => '',
        'square_feet' => '',
        'square_feet_formatted' => '',
        'lot_size' => '',
        'lot_size_formatted' => '',
        'property_type' => 'Single Family Home',
        'mls_number' => '',
        'featured_image' => get_the_post_thumbnail_url($listing_id, 'full'),
        'gallery' => has_post_thumbnail($listing_id) ? [[
            'url' => get_the_post_thumbnail_url($listing_id, 'full'),
            'alt' => get_the_title($listing_id)
        ]] : [],
        'features' => [],
        'agent' => [],
        'days_on_market' => 0,
        'is_new' => false,
        'is_featured' => false
    ];
}
?>