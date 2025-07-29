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

/**
 * Get agent profile URL
 *
 * @param int $agent_id
 * @return string
 */
if (!function_exists('hph_get_agent_url')) {
    function hph_get_agent_url($agent_id) {
        // Check if custom post type exists for agents
        $agent_post = get_post($agent_id);
        if ($agent_post && $agent_post->post_type === 'hph_agent') {
            return get_permalink($agent_id);
        }
        
        // Otherwise construct URL based on plugin settings
        $base_url = get_option('hph_agent_base_url', '/agents/');
        $agent = hph_get_agent_data($agent_id);
        
        if (!$agent) {
            return home_url($base_url);
        }
        
        // Create SEO-friendly slug
        $slug = !empty($agent['slug']) ? $agent['slug'] : sanitize_title($agent['display_name']);
        
        return home_url($base_url . $slug . '/');
    }
}

/**
 * Get agent statistics
 *
 * @param int $agent_id
 * @return array
 */
if (!function_exists('hph_get_agent_stats')) {
    function hph_get_agent_stats($agent_id) {
        $cache_key = 'hph_agent_stats_' . $agent_id;
        $stats = wp_cache_get($cache_key, 'hph_agents');
        
        if ($stats !== false) {
            return $stats;
        }
        
        global $wpdb;
        
        $current_year = date('Y');
        
        // Get active listings count
        $active_listings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hph_listings WHERE listing_agent_id = %d AND status = 'active'",
            $agent_id
        ));
        
        // Get sales this year
        $sales_ytd = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hph_listings WHERE listing_agent_id = %d AND status = 'sold' AND YEAR(sold_date) = %d",
            $agent_id,
            $current_year
        ));
        
        // Get average days on market for sold listings
        $avg_days_on_market = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(DATEDIFF(sold_date, created_at)) FROM {$wpdb->prefix}hph_listings WHERE listing_agent_id = %d AND status = 'sold' AND sold_date IS NOT NULL",
            $agent_id
        ));
        
        // Get total volume this year
        $total_volume_ytd = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(price) FROM {$wpdb->prefix}hph_listings WHERE listing_agent_id = %d AND status = 'sold' AND YEAR(sold_date) = %d",
            $agent_id,
            $current_year
        ));
        
        $stats = [
            'listings_active' => (int) $active_listings,
            'sales_ytd' => (int) $sales_ytd,
            'avg_days_on_market' => $avg_days_on_market ? round($avg_days_on_market) : 0,
            'total_volume_ytd' => (float) $total_volume_ytd,
            'avg_sale_price_ytd' => $sales_ytd > 0 ? round($total_volume_ytd / $sales_ytd) : 0
        ];
        
        wp_cache_set($cache_key, $stats, 'hph_agents', 3600); // 1 hour cache
        
        return $stats;
    }
}

/**
 * Get agent reviews
 *
 * @param int $agent_id
 * @param int $limit
 * @return array
 */
if (!function_exists('hph_get_agent_reviews')) {
    function hph_get_agent_reviews($agent_id, $limit = 5) {
        $cache_key = 'hph_agent_reviews_' . $agent_id . '_' . $limit;
        $reviews = wp_cache_get($cache_key, 'hph_agents');
        
        if ($reviews !== false) {
            return $reviews;
        }
        
        global $wpdb;
        
        // Get reviews from dedicated reviews table if it exists
        $reviews_table = $wpdb->prefix . 'hph_agent_reviews';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$reviews_table}'") === $reviews_table;
        
        if ($table_exists) {
            $reviews = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$reviews_table} WHERE agent_id = %d AND status = 'approved' ORDER BY created_at DESC LIMIT %d",
                $agent_id,
                $limit
            ), ARRAY_A);
        } else {
            // Fallback to WordPress comments/custom fields
            $reviews = [];
            
            // Check for reviews stored as custom fields or comments
            $review_data = get_post_meta($agent_id, '_agent_reviews', true);
            if (is_array($review_data)) {
                $reviews = array_slice($review_data, 0, $limit);
            }
        }
        
        wp_cache_set($cache_key, $reviews, 'hph_agents', 1800); // 30 minute cache
        
        return $reviews ?: [];
    }
}
