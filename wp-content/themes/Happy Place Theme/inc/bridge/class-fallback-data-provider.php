<?php
/**
 * HPH Fallback Data Provider
 *
 * Provides basic functionality when plugin is inactive
 * Implements HPH_Data_Contract interface with WordPress fallbacks
 *
 * @package HappyPlace
 * @subpackage Bridge
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fallback Data Provider Class
 * 
 * Provides basic functionality when plugin is inactive
 */
class HPH_Fallback_Data_Provider implements HPH_Data_Contract {
    
    /**
     * Get listing data with WordPress fallbacks
     *
     * @param int $listing_id Listing post ID
     * @return array Listing data array
     */
    public function get_listing_data($listing_id) {
        if (!$listing_id || !get_post($listing_id)) {
            return [];
        }
        
        return [
            'title' => get_the_title($listing_id),
            'content' => get_the_content(null, false, $listing_id),
            'thumbnail' => get_the_post_thumbnail_url($listing_id),
            'price' => 'Contact for Price',
            'status' => 'Available',
            'address' => get_post_meta($listing_id, 'address', true) ?: 'Address Not Available',
            'features' => [
                'bedrooms' => get_post_meta($listing_id, 'bedrooms', true) ?: 'N/A',
                'bathrooms' => get_post_meta($listing_id, 'bathrooms', true) ?: 'N/A',
                'square_feet' => get_post_meta($listing_id, 'square_feet', true) ?: 'N/A'
            ],
            'url' => get_permalink($listing_id)
        ];
    }
    
    /**
     * Get agent data with WordPress fallbacks
     *
     * @param int $agent_id Agent post ID
     * @return array Agent data array
     */
    public function get_agent_data($agent_id) {
        if (!$agent_id || !get_post($agent_id)) {
            return [];
        }
        
        return [
            'name' => get_the_title($agent_id),
            'bio' => get_the_content(null, false, $agent_id),
            'avatar' => get_the_post_thumbnail_url($agent_id),
            'contact' => 'Contact for Information',
            'email' => get_post_meta($agent_id, 'email', true) ?: '',
            'phone' => get_post_meta($agent_id, 'phone', true) ?: '',
            'url' => get_permalink($agent_id)
        ];
    }
    
    /**
     * Get dashboard data with WordPress fallbacks
     *
     * @param int $user_id User ID
     * @return array Dashboard data array
     */
    public function get_dashboard_data($user_id) {
        if (!$user_id) {
            return [];
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return [];
        }
        
        return [
            'user_name' => $user->display_name,
            'user_email' => $user->user_email,
            'listings' => [],
            'favorites' => [],
            'messages' => [],
            'stats' => [
                'total_listings' => 0,
                'active_listings' => 0,
                'pending_listings' => 0
            ]
        ];
    }
    
    /**
     * Search listings with basic WordPress search
     *
     * @param array $criteria Search criteria
     * @return array Search results
     */
    public function search_listings($criteria) {
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $criteria['per_page'] ?? 12,
            'paged' => $criteria['page'] ?? 1
        ];
        
        // Basic search term
        if (!empty($criteria['search'])) {
            $args['s'] = sanitize_text_field($criteria['search']);
        }
        
        // Price range (if meta fields exist)
        if (!empty($criteria['min_price']) || !empty($criteria['max_price'])) {
            $args['meta_query'] = [];
            
            if (!empty($criteria['min_price'])) {
                $args['meta_query'][] = [
                    'key' => 'price',
                    'value' => floatval($criteria['min_price']),
                    'compare' => '>='
                ];
            }
            
            if (!empty($criteria['max_price'])) {
                $args['meta_query'][] = [
                    'key' => 'price', 
                    'value' => floatval($criteria['max_price']),
                    'compare' => '<='
                ];
            }
        }
        
        $query = new WP_Query($args);
        
        return [
            'listings' => $query->posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
            'current_page' => $args['paged']
        ];
    }
    
    /**
     * Get financial data with basic fallbacks
     *
     * @param int $listing_id Listing post ID
     * @return array Financial data array
     */
    public function get_financial_data($listing_id) {
        if (!$listing_id) {
            return [];
        }
        
        return [
            'price' => get_post_meta($listing_id, 'price', true) ?: 0,
            'mortgage_estimate' => 'Contact for Details',
            'property_taxes' => 'Contact for Details',
            'hoa_fees' => 'Contact for Details',
            'insurance_estimate' => 'Contact for Details'
        ];
    }
    
    /**
     * Get agent listings with basic query
     *
     * @param int $agent_id Agent post ID
     * @return array Agent's listings
     */
    public function get_agent_listings($agent_id) {
        if (!$agent_id) {
            return [];
        }
        
        $args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ];
        
        $query = new WP_Query($args);
        
        return [
            'listings' => $query->posts,
            'total' => $query->found_posts
        ];
    }
}
