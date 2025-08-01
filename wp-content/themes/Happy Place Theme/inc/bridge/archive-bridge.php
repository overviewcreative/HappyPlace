<?php
/**
 * Archive-specific bridge functions
 * 
 * @package Happy_Place_Theme
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get archive data for a specific post type
 * 
 * @param string $post_type The post type
 * @return array Archive data
 */
if (!function_exists('hph_bridge_get_archive_data')) {
    function hph_bridge_get_archive_data($post_type) {
        $cache_key = "hph_archive_data_{$post_type}";
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $data = [];
        
        switch ($post_type) {
            case 'listing':
                $data = [
                    'title' => 'Property Listings',
                    'description' => 'Browse our available properties',
                    'post_type' => 'listing'
                ];
                break;
                
            case 'agent':
                $data = [
                    'title' => 'Our Agents',
                    'description' => 'Meet our professional real estate agents',
                    'post_type' => 'agent'
                ];
                break;
                
            default:
                $post_type_obj = get_post_type_object($post_type);
                $data = [
                    'title' => $post_type_obj ? $post_type_obj->labels->name : ucfirst($post_type),
                    'description' => '',
                    'post_type' => $post_type
                ];
                break;
        }
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, '', HOUR_IN_SECONDS);
        
        return $data;
    }
}

/**
 * Get listings with enhanced querying
 * 
 * @param array $args Query arguments
 * @return array Array of listing posts
 */
if (!function_exists('hph_bridge_get_listings')) {
    function hph_bridge_get_listings($args = []) {
        $defaults = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page', 12),
            'meta_query' => [],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Add cache key based on arguments
        $cache_key = 'hph_listings_' . md5(serialize($args));
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Add meta query for active listings if not specified
        if (empty($args['meta_query'])) {
            $args['meta_query'] = [
                [
                    'key' => 'listing_status',
                    'value' => 'active',
                    'compare' => '='
                ]
            ];
        }
        
        $listings = get_posts($args);
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $listings, '', 30 * MINUTE_IN_SECONDS);
        
        return $listings;
    }
}

/**
 * Get pagination data for current query
 * 
 * @return array Pagination data
 */
if (!function_exists('hph_bridge_get_pagination_data')) {
    function hph_bridge_get_pagination_data() {
        global $wp_query;
        
        if (!$wp_query) {
            return [];
        }
        
        $total_pages = $wp_query->max_num_pages;
        $current_page = max(1, get_query_var('paged'));
        
        return [
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'prev_text' => '← Previous',
            'next_text' => 'Next →',
            'show_numbers' => true,
            'has_pagination' => $total_pages > 1
        ];
    }
}

/**
 * Get filter options for listings
 * 
 * @return array Available filter options
 */
if (!function_exists('hph_bridge_get_filter_options')) {
    function hph_bridge_get_filter_options() {
        $cache_key = 'hph_filter_options';
        $cached = wp_cache_get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $options = [
            'property_types' => [
                'single_family' => 'Single Family',
                'condo' => 'Condo',
                'townhouse' => 'Townhouse',
                'multi_family' => 'Multi Family',
                'land' => 'Land',
                'commercial' => 'Commercial'
            ],
            'bedrooms' => [
                '1' => '1 Bedroom',
                '2' => '2 Bedrooms', 
                '3' => '3 Bedrooms',
                '4' => '4 Bedrooms',
                '5+' => '5+ Bedrooms'
            ],
            'bathrooms' => [
                '1' => '1 Bathroom',
                '2' => '2 Bathrooms',
                '3' => '3 Bathrooms',
                '4+' => '4+ Bathrooms'
            ],
            'price_ranges' => [
                '0-200000' => 'Under $200K',
                '200000-400000' => '$200K - $400K',
                '400000-600000' => '$400K - $600K',
                '600000-800000' => '$600K - $800K',
                '800000-1000000' => '$800K - $1M',
                '1000000+' => '$1M+'
            ]
        ];
        
        // Cache for 1 day
        wp_cache_set($cache_key, $options, '', DAY_IN_SECONDS);
        
        return $options;
    }
}

/**
 * Get sort options for listings
 * 
 * @return array Available sort options
 */
if (!function_exists('hph_bridge_get_sort_options')) {
    function hph_bridge_get_sort_options() {
        return [
            'date_desc' => 'Newest First',
            'date_asc' => 'Oldest First',
            'price_desc' => 'Price High to Low',
            'price_asc' => 'Price Low to High',
            'sqft_desc' => 'Largest First',
            'sqft_asc' => 'Smallest First',
            'title_asc' => 'A to Z',
            'title_desc' => 'Z to A'
        ];
    }
}

/**
 * Archive-specific listing data wrapper
 * 
 * @param int $listing_id The listing ID
 * @return array Listing data
 */
if (!function_exists('hph_bridge_get_listing_data_archive')) {
    function hph_bridge_get_listing_data_archive($listing_id) {
        // Call the main function from listing-bridge.php
        if (function_exists('hph_bridge_get_listing_data')) {
            return hph_bridge_get_listing_data($listing_id);
        }
        
        // Fallback for when main bridge function isn't available
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'status' => 'available',
            'price' => 0,
            'price_formatted' => 'Contact for Price',
            'bedrooms' => get_post_meta($listing_id, '_listing_bedrooms', true) ?: 0,
            'bathrooms' => get_post_meta($listing_id, '_listing_bathrooms', true) ?: 0,
            'square_footage' => get_post_meta($listing_id, '_listing_square_footage', true) ?: 0,
            'address' => get_post_meta($listing_id, '_listing_address', true) ?: '',
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'medium'),
            'url' => get_permalink($listing_id)
        ];
    }
}
