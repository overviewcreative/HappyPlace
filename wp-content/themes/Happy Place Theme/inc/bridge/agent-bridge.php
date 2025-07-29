<?php
/**
 * Agent Bridge Functions
 * 
 * Handles all agent-related data access with caching and fallbacks
 *
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get agent data with caching
 */
function hph_get_agent_data($agent_id, $fields = []) {
    if (empty($agent_id)) {
        return false;
    }

    $cache_key = 'agent_data_' . $agent_id . '_' . md5(serialize($fields));
    $cached_data = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached_data !== false) {
        return $cached_data;
    }

    // Use data provider contract system
    $provider = hph_get_data_provider();
    $data = $provider->get_agent_data($agent_id);

    // Cache for 2 hours
    wp_cache_set($cache_key, $data, 'hph_agents', 7200);
    
    return $data;
}

/**
 * Get agent contact information
 */
function hph_get_agent_contact($agent_id) {
    $cache_key = 'agent_contact_' . $agent_id;
    $cached = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $contact = [
        'phone' => get_field('agent_phone', $agent_id),
        'email' => get_field('agent_email', $agent_id),
        'office_phone' => get_field('agent_office_phone', $agent_id),
        'mobile_phone' => get_field('agent_mobile_phone', $agent_id)
    ];
    
    // Cache for 4 hours
    wp_cache_set($cache_key, $contact, 'hph_agents', 14400);
    
    return $contact;
}

/**
 * Get agent bio and description
 */
function hph_get_agent_bio($agent_id) {
    $bio = get_field('agent_bio', $agent_id);
    return $bio ? wpautop($bio) : '';
}

/**
 * Get agent specialties
 */
function hph_get_agent_specialties($agent_id) {
    $specialties = get_field('agent_specialties', $agent_id);
    return is_array($specialties) ? $specialties : [];
}

/**
 * Get agent photo
 */
function hph_get_agent_photo($agent_id, $size = 'medium') {
    $photo = get_field('agent_photo', $agent_id);
    
    if (!$photo) {
        return hph_get_default_agent_photo($size);
    }
    
    if (is_array($photo)) {
        return [
            'url' => $photo['sizes'][$size] ?? $photo['url'],
            'alt' => $photo['alt'] ?? 'Agent Photo',
            'title' => $photo['title'] ?? ''
        ];
    }
    
    return wp_get_attachment_image_src($photo, $size);
}

/**
 * Get agent's listings
 */
function hph_get_agent_listings($agent_id, $limit = 12) {
    $cache_key = 'agent_listings_' . $agent_id . '_' . $limit;
    $cached = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $args = [
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ]
        ]
    ];
    
    $query = new WP_Query($args);
    $listings = $query->posts;
    
    // Cache for 1 hour
    wp_cache_set($cache_key, $listings, 'hph_agents', 3600);
    
    return $listings;
}

/**
 * Get agent stats
 */
function hph_get_agent_stats($agent_id) {
    $cache_key = 'agent_stats_' . $agent_id;
    $cached = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Count active listings
    $active_listings = new WP_Query([
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ],
            [
                'key' => 'listing_status',
                'value' => 'active',
                'compare' => '='
            ]
        ]
    ]);
    
    // Count sold listings
    $sold_listings = new WP_Query([
        'post_type' => 'listing',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'listing_agent',
                'value' => $agent_id,
                'compare' => '='
            ],
            [
                'key' => 'listing_status',
                'value' => 'sold',
                'compare' => '='
            ]
        ]
    ]);
    
    $stats = [
        'active_listings' => $active_listings->found_posts,
        'sold_listings' => $sold_listings->found_posts,
        'total_listings' => $active_listings->found_posts + $sold_listings->found_posts
    ];
    
    // Cache for 2 hours
    wp_cache_set($cache_key, $stats, 'hph_agents', 7200);
    
    return $stats;
}

/**
 * ACF Fallback for agent data
 */
function hph_get_agent_acf_fallback($agent_id, $fields = []) {
    if (empty($fields)) {
        // Get all common fields
        $fields = [
            'agent_phone',
            'agent_email',
            'agent_bio',
            'agent_photo',
            'agent_specialties',
            'agent_office_phone',
            'agent_mobile_phone'
        ];
    }
    
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = get_field($field, $agent_id);
    }
    
    return $data;
}

/**
 * Get default agent photo
 */
function hph_get_default_agent_photo($size = 'medium') {
    return [
        'url' => HPH_THEME_URI . '/assets/dist/images/default-agent.jpg',
        'alt' => 'Default Agent Photo',
        'title' => 'Agent Photo'
    ];
}

/**
 * Search agents
 */
function hph_search_agents($args = []) {
    $cache_key = 'agent_search_' . md5(serialize($args));
    $cached = wp_cache_get($cache_key, 'hph_agents');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $defaults = [
        'post_type' => 'agent',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'meta_query' => []
    ];
    
    $query_args = wp_parse_args($args, $defaults);
    $query = new WP_Query($query_args);
    
    $results = [
        'agents' => $query->posts,
        'total' => $query->found_posts,
        'pages' => $query->max_num_pages
    ];
    
    // Cache for 30 minutes
    wp_cache_set($cache_key, $results, 'hph_agents', 1800);
    
    return $results;
}
