<?php
/**
 * Get comprehensive agent data
 * 
 * @param int $agent_id Agent post ID
 * @return array Agent data
 */
function hph_bridge_get_agent_data($agent_id) {
    if (empty($agent_id)) {
        return [];
    }
    
    $cache_key = "hph_agent_data_{$agent_id}";
    $cached_data = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $post = get_post($agent_id);
    if (!$post || $post->post_type !== 'agent') {
        return [];
    }
    
    $agent_data = [
        'id' => $agent_id,
        'name' => get_the_title($agent_id),
        'bio' => get_the_content(null, false, $agent_id),
        'excerpt' => get_the_excerpt($agent_id),
        'photo' => get_the_post_thumbnail_url($agent_id, 'medium'),
        'url' => get_permalink($agent_id)
    ];
    
    // Get contact information
    if (function_exists('get_field')) {
        $agent_data['email'] = get_field('email', $agent_id) ?: '';
        $agent_data['phone'] = get_field('phone', $agent_id) ?: '';
        $agent_data['mobile'] = get_field('mobile', $agent_id) ?: '';
        $agent_data['office_phone'] = get_field('office_phone', $agent_id) ?: '';
        $agent_data['fax'] = get_field('fax', $agent_id) ?: '';
        $agent_data['license_number'] = get_field('license_number', $agent_id) ?: '';
        $agent_data['years_experience'] = get_field('years_experience', $agent_id) ?: 0;
        $agent_data['specialties'] = get_field('specialties', $agent_id) ?: [];
        $agent_data['social_media'] = get_field('social_media', $agent_id) ?: [];
    } else {
        // Fallback to post meta
        $agent_data['email'] = get_post_meta($agent_id, 'email', true) ?: '';
        $agent_data['phone'] = get_post_meta($agent_id, 'phone', true) ?: '';
        $agent_data['mobile'] = get_post_meta($agent_id, 'mobile', true) ?: '';
        $agent_data['license_number'] = get_post_meta($agent_id, 'license_number', true) ?: '';
        $agent_data['years_experience'] = get_post_meta($agent_id, 'years_experience', true) ?: 0;
    }
    
    // Get agent statistics
    $agent_data['stats'] = hph_get_agent_stats($agent_id);
    
    wp_cache_set($cache_key, $agent_data, 'hph_agents', 3600);
    
    return $agent_data;
}

/**
 * Get agent statistics
 * 
 * @param int $agent_id Agent ID
 * @return array Agent stats
 */
function hph_get_agent_stats($agent_id) {
    $cache_key = "hph_agent_stats_{$agent_id}";
    $cached_stats = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached_stats !== false) {
        return $cached_stats;
    }
    
    // Count listings by this agent
    $active_listings = new WP_Query([
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ],
            [
                'key' => 'status',
                'value' => ['active', 'available', 'for_sale'],
                'compare' => 'IN'
            ]
        ],
        'fields' => 'ids'
    ]);
    
    $sold_listings = new WP_Query([
        'post_type' => 'listing',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ],
            [
                'key' => 'status',
                'value' => 'sold',
                'compare' => '='
            ]
        ],
        'fields' => 'ids'
    ]);
    
    $stats = [
        'active_listings' => $active_listings->found_posts,
        'sold_listings' => $sold_listings->found_posts,
        'total_listings' => $active_listings->found_posts + $sold_listings->found_posts
    ];
    
    wp_cache_set($cache_key, $stats, 'hph_agents', 1800);
    
    return $stats;
}

/**
 * Get agent's listings
 * 
 * @param int $agent_id Agent ID
 * @param string $status Listing status filter
 * @param int $count Number of listings to return
 * @return array Agent listings
 */
function hph_bridge_get_agent_listings($agent_id, $status = 'active', $count = 10) {
    $cache_key = "hph_agent_listings_{$agent_id}_{$status}_{$count}";
    $cached_data = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached_data !== false) {
        return $cached_data;
    }
    
    $query_args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ]
        ]
    ];
    
    if ($status !== 'all') {
        $status_values = $status === 'active' 
            ? ['active', 'available', 'for_sale'] 
            : [$status];
            
        $query_args['meta_query'][] = [
            'key' => 'status',
            'value' => $status_values,
            'compare' => 'IN'
        ];
    }
    
    $listings_query = new WP_Query($query_args);
    $listings = [];
    
    if ($listings_query->have_posts()) {
        while ($listings_query->have_posts()) {
            $listings_query->the_post();
            $listing_id = get_the_ID();
            
            $listings[] = [
                'id' => $listing_id,
                'title' => get_the_title(),
                'url' => get_permalink(),
                'price' => hph_format_price(get_post_meta($listing_id, 'price', true)),
                'image' => get_the_post_thumbnail_url($listing_id, 'medium_large'),
                'status' => get_post_meta($listing_id, 'status', true),
                'bedrooms' => get_post_meta($listing_id, 'bedrooms', true),
                'bathrooms' => get_post_meta($listing_id, 'bathrooms', true),
                'square_feet' => get_post_meta($listing_id, 'square_feet', true)
            ];
        }
        wp_reset_postdata();
    }
    
    wp_cache_set($cache_key, $listings, 'hph_agents', 1800);
    
    return $listings;
}

