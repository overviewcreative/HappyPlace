<?php
/**
 * Agent Bridge Functions
 * 
 * Specialized bridge functions for agent profiles, directories, and agent-centric views.
 * These functions focus on agent data as the primary entity rather than listings.
 * Used for agent pages, agent directories, team pages, and agent-focused components.
 * 
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get comprehensive agent profile data
 * 
 * @param int $agent_id Agent post ID
 * @return array Complete agent profile data
 */
if (!function_exists('hph_agent_bridge_get_profile')) {
    function hph_agent_bridge_get_profile($agent_id) {
        // Handle object input
        if (is_object($agent_id) && isset($agent_id->ID)) {
            $agent_id = $agent_id->ID;
        }
        
        $agent_id = intval($agent_id);
        if (empty($agent_id)) {
            return [];
        }
        
        $cache_key = "hph_agent_profile_{$agent_id}";
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
            'slug' => $post->post_name,
            'url' => get_permalink($agent_id),
            'bio' => get_the_content(null, false, $agent_id),
            'excerpt' => get_the_excerpt($agent_id),
            'featured_image' => get_the_post_thumbnail_url($agent_id, 'full'),
            'profile_photo' => get_the_post_thumbnail_url($agent_id, 'medium'),
            'thumbnail' => get_the_post_thumbnail_url($agent_id, 'thumbnail'),
            'date_added' => get_the_date('Y-m-d', $agent_id),
            'last_modified' => get_the_modified_date('Y-m-d', $agent_id)
        ];
        
        // Get ACF profile data
        if (function_exists('get_field')) {
            // Basic Information
            $agent_data['first_name'] = get_field('agent_first_name', $agent_id) ?: '';
            $agent_data['last_name'] = get_field('agent_last_name', $agent_id) ?: '';
            $agent_data['full_name'] = trim($agent_data['first_name'] . ' ' . $agent_data['last_name']) ?: $agent_data['name'];
            $agent_data['display_name'] = $agent_data['full_name'] ?: $agent_data['name'];
            
            // Contact Information
            $agent_data['email'] = get_field('agent_email', $agent_id) ?: '';
            $agent_data['phone'] = get_field('agent_phone', $agent_id) ?: '';
            $agent_data['mobile'] = get_field('agent_mobile', $agent_id) ?: '';
            $agent_data['primary_phone'] = $agent_data['mobile'] ?: $agent_data['phone'];
            
            // Professional Information
            $agent_data['license_number'] = get_field('agent_license_number', $agent_id) ?: '';
            $agent_data['license_state'] = get_field('agent_license_state', $agent_id) ?: '';
            $agent_data['title'] = get_field('agent_title', $agent_id) ?: '';
            $agent_data['years_experience'] = get_field('agent_years_experience', $agent_id) ?: 0;
            $agent_data['specialties'] = get_field('agent_specialties', $agent_id) ?: [];
            $agent_data['languages'] = get_field('agent_languages', $agent_id) ?: '';
            
            // Online Presence
            $agent_data['website'] = get_field('agent_website', $agent_id) ?: '';
            $agent_data['social_media'] = [
                'linkedin' => get_field('agent_linkedin', $agent_id) ?: '',
                'facebook' => get_field('agent_facebook', $agent_id) ?: '',
                'instagram' => get_field('agent_instagram', $agent_id) ?: '',
                'youtube' => get_field('agent_youtube', $agent_id) ?: '',
                'twitter' => get_field('agent_twitter', $agent_id) ?: ''
            ];
            
            // Office & Team
            $office = get_field('agent_office', $agent_id);
            $agent_data['office'] = null;
            if ($office) {
                $agent_data['office'] = [
                    'id' => $office->ID,
                    'name' => get_the_title($office->ID),
                    'url' => get_permalink($office->ID)
                ];
                
                // Get office contact info if available
                if (function_exists('get_field')) {
                    $agent_data['office']['phone'] = get_field('office_phone', $office->ID) ?: '';
                    $agent_data['office']['address'] = get_field('office_address', $office->ID) ?: '';
                    $agent_data['office']['city'] = get_field('office_city', $office->ID) ?: '';
                    $agent_data['office']['state'] = get_field('office_state', $office->ID) ?: '';
                }
            }
            
            $agent_data['team'] = get_field('agent_team', $agent_id) ?: '';
            $team_members = get_field('agent_team_members', $agent_id);
            $agent_data['team_members'] = [];
            if ($team_members) {
                foreach ($team_members as $team_member) {
                    $agent_data['team_members'][] = [
                        'id' => $team_member->ID,
                        'name' => get_the_title($team_member->ID),
                        'url' => get_permalink($team_member->ID),
                        'photo' => get_the_post_thumbnail_url($team_member->ID, 'thumbnail')
                    ];
                }
            }
            
            // Statistics (manual entry)
            $agent_data['stats'] = [
                'total_sales_volume' => get_field('agent_total_sales_volume', $agent_id) ?: 0,
                'total_transactions' => get_field('agent_total_transactions', $agent_id) ?: 0,
                'average_sale_price' => get_field('agent_average_sale_price', $agent_id) ?: 0,
                'rating' => get_field('agent_rating', $agent_id) ?: 0
            ];
        }
        
        // Get calculated statistics
        $calculated_stats = hph_agent_bridge_get_calculated_stats($agent_id);
        $agent_data['calculated_stats'] = $calculated_stats;
        
        // Merge manual and calculated stats
        $agent_data['combined_stats'] = array_merge($agent_data['stats'] ?? [], $calculated_stats);
        
        wp_cache_set($cache_key, $agent_data, 'hph_agents', 3600);
        
        return $agent_data;
    }
}

/**
 * Get agent's calculated statistics from actual listing data
 * 
 * @param int $agent_id Agent ID
 * @return array Calculated statistics
 */
if (!function_exists('hph_agent_bridge_get_calculated_stats')) {
    function hph_agent_bridge_get_calculated_stats($agent_id) {
        $cache_key = "hph_agent_calc_stats_{$agent_id}";
        $cached_stats = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_stats !== false) {
            return $cached_stats;
        }
        
        $stats = [
            'active_listings_count' => 0,
            'sold_listings_count' => 0,
            'pending_listings_count' => 0,
            'total_listings_count' => 0,
            'average_days_on_market' => 0,
            'recent_sales_count' => 0, // Last 12 months
            'price_range' => [
                'min' => 0,
                'max' => 0,
                'average' => 0
            ]
        ];
        
        // Query active listings
        $active_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => ['active', 'available', 'for-sale'],
                    'operator' => 'IN'
                ]
            ]
        ]);
        
        $stats['active_listings_count'] = $active_query->found_posts;
        
        // Query sold listings
        $sold_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => ['sold', 'closed'],
                    'operator' => 'IN'
                ]
            ]
        ]);
        
        $stats['sold_listings_count'] = $sold_query->found_posts;
        
        // Query pending listings
        $pending_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => ['pending', 'under-contract'],
                    'operator' => 'IN'
                ]
            ]
        ]);
        
        $stats['pending_listings_count'] = $pending_query->found_posts;
        $stats['total_listings_count'] = $stats['active_listings_count'] + $stats['sold_listings_count'] + $stats['pending_listings_count'];
        
        // Calculate price ranges and averages from recent sold listings
        if ($stats['sold_listings_count'] > 0) {
            $sold_listings_detailed = new WP_Query([
                'post_type' => 'listing',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'property_status',
                        'field' => 'slug',
                        'terms' => ['sold', 'closed'],
                        'operator' => 'IN'
                    ]
                ]
            ]);
            
            $prices = [];
            $dom_values = [];
            
            while ($sold_listings_detailed->have_posts()) {
                $sold_listings_detailed->the_post();
                $listing_id = get_the_ID();
                
                $price = get_field('price', $listing_id) ?: 0;
                if ($price > 0) {
                    $prices[] = $price;
                }
                
                $dom = get_field('days_on_market', $listing_id) ?: 0;
                if ($dom > 0) {
                    $dom_values[] = $dom;
                }
            }
            wp_reset_postdata();
            
            if (!empty($prices)) {
                $stats['price_range']['min'] = min($prices);
                $stats['price_range']['max'] = max($prices);
                $stats['price_range']['average'] = round(array_sum($prices) / count($prices));
            }
            
            if (!empty($dom_values)) {
                $stats['average_days_on_market'] = round(array_sum($dom_values) / count($dom_values));
            }
        }
        
        // Recent sales (last 12 months)
        $recent_sales_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [
                [
                    'after' => '1 year ago',
                    'inclusive' => true
                ]
            ],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => ['sold', 'closed'],
                    'operator' => 'IN'
                ]
            ]
        ]);
        
        $stats['recent_sales_count'] = $recent_sales_query->found_posts;
        
        wp_cache_set($cache_key, $stats, 'hph_agents', 1800); // 30 minutes
        
        return $stats;
    }
}

/**
 * Get agent's listings with detailed filtering options
 * 
 * @param int $agent_id Agent ID
 * @param array $args Query arguments
 * @return array Agent's listings
 */
if (!function_exists('hph_agent_bridge_get_listings')) {
    function hph_agent_bridge_get_listings($agent_id, $args = []) {
        $defaults = [
            'status' => 'all', // all, active, sold, pending
            'count' => 12,
            'orderby' => 'date',
            'order' => 'DESC',
            'include_stats' => false
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $cache_key = "hph_agent_listings_{$agent_id}_" . md5(serialize($args));
        $cached_data = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $query_args = [
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $args['count'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ]
        ];
        
        // Add status filtering
        if ($args['status'] !== 'all') {
            $status_terms = [];
            switch ($args['status']) {
                case 'active':
                    $status_terms = ['active', 'available', 'for-sale'];
                    break;
                case 'sold':
                    $status_terms = ['sold', 'closed'];
                    break;
                case 'pending':
                    $status_terms = ['pending', 'under-contract'];
                    break;
                default:
                    $status_terms = [$args['status']];
            }
            
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => $status_terms,
                    'operator' => 'IN'
                ]
            ];
        }
        
        $listings_query = new WP_Query($query_args);
        $listings = [];
        
        if ($listings_query->have_posts()) {
            while ($listings_query->have_posts()) {
                $listings_query->the_post();
                $listing_id = get_the_ID();
                
                $listing_data = [
                    'id' => $listing_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'featured_image' => get_the_post_thumbnail_url($listing_id, 'large'),
                    'thumbnail' => get_the_post_thumbnail_url($listing_id, 'medium'),
                    'excerpt' => get_the_excerpt(),
                    'date' => get_the_date('Y-m-d'),
                    'modified' => get_the_modified_date('Y-m-d')
                ];
                
                // Get key listing data
                if (function_exists('get_field')) {
                    $listing_data['price'] = get_field('price', $listing_id) ?: 0;
                    $listing_data['price_formatted'] = hph_format_price($listing_data['price']);
                    $listing_data['bedrooms'] = get_field('bedrooms', $listing_id) ?: 0;
                    $listing_data['bathrooms'] = get_field('bathrooms', $listing_id) ?: 0;
                    $listing_data['bathrooms_total'] = get_field('bathrooms_total', $listing_id) ?: $listing_data['bathrooms'];
                    $listing_data['square_footage'] = get_field('square_footage', $listing_id) ?: 0;
                    $listing_data['street_address'] = get_field('street_address', $listing_id) ?: '';
                    $listing_data['city'] = get_field('city', $listing_id) ?: '';
                    $listing_data['state'] = get_field('state', $listing_id) ?: '';
                    $listing_data['zip_code'] = get_field('zip_code', $listing_id) ?: '';
                    $listing_data['days_on_market'] = get_field('days_on_market', $listing_id) ?: 0;
                }
                
                // Get property status
                $status_terms = wp_get_post_terms($listing_id, 'property_status');
                $listing_data['status'] = !empty($status_terms) ? $status_terms[0]->name : 'Active';
                $listing_data['status_slug'] = !empty($status_terms) ? $status_terms[0]->slug : 'active';
                
                // Get property type
                $type_terms = wp_get_post_terms($listing_id, 'property_type');
                $listing_data['property_type'] = !empty($type_terms) ? $type_terms[0]->name : 'Single Family Home';
                
                // Format address
                $address_parts = array_filter([
                    $listing_data['street_address'],
                    $listing_data['city'],
                    $listing_data['state'],
                    $listing_data['zip_code']
                ]);
                $listing_data['full_address'] = implode(', ', $address_parts);
                
                if ($args['include_stats']) {
                    $listing_data['stats'] = [
                        'views' => get_post_meta($listing_id, 'listing_views', true) ?: 0,
                        'inquiries' => get_post_meta($listing_id, 'listing_inquiries', true) ?: 0,
                        'showings' => get_post_meta($listing_id, 'listing_showings', true) ?: 0
                    ];
                }
                
                $listings[] = $listing_data;
            }
            wp_reset_postdata();
        }
        
        $result = [
            'listings' => $listings,
            'total_found' => $listings_query->found_posts,
            'total_pages' => $listings_query->max_num_pages,
            'current_page' => max(1, get_query_var('paged')),
            'query_args' => $args
        ];
        
        wp_cache_set($cache_key, $result, 'hph_agents', 1800); // 30 minutes
        
        return $result;
    }
}

/**
 * Get agent directory data for agent archive pages
 * 
 * @param array $args Query arguments
 * @return array Agent directory data
 */
if (!function_exists('hph_agent_bridge_get_directory')) {
    function hph_agent_bridge_get_directory($args = []) {
        $defaults = [
            'posts_per_page' => 12,
            'orderby' => 'title',
            'order' => 'ASC',
            'office_id' => 0,
            'specialties' => [],
            'exclude_inactive' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $cache_key = "hph_agent_directory_" . md5(serialize($args));
        $cached_data = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $query_args = [
            'post_type' => 'agent',
            'post_status' => 'publish',
            'posts_per_page' => $args['posts_per_page'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'meta_query' => ['relation' => 'AND']
        ];
        
        // Filter by office
        if (!empty($args['office_id'])) {
            $query_args['meta_query'][] = [
                'key' => 'agent_office',
                'value' => $args['office_id'],
                'compare' => '='
            ];
        }
        
        // Filter by specialties
        if (!empty($args['specialties'])) {
            $query_args['meta_query'][] = [
                'key' => 'agent_specialties',
                'value' => $args['specialties'],
                'compare' => 'LIKE'
            ];
        }
        
        $agents_query = new WP_Query($query_args);
        $agents = [];
        
        if ($agents_query->have_posts()) {
            while ($agents_query->have_posts()) {
                $agents_query->the_post();
                $agent_id = get_the_ID();
                
                // Get basic agent data (lighter version for directory)
                $agent_data = [
                    'id' => $agent_id,
                    'name' => get_the_title(),
                    'url' => get_permalink(),
                    'photo' => get_the_post_thumbnail_url($agent_id, 'medium'),
                    'excerpt' => get_the_excerpt()
                ];
                
                if (function_exists('get_field')) {
                    $agent_data['first_name'] = get_field('agent_first_name', $agent_id) ?: '';
                    $agent_data['last_name'] = get_field('agent_last_name', $agent_id) ?: '';
                    $agent_data['email'] = get_field('agent_email', $agent_id) ?: '';
                    $agent_data['phone'] = get_field('agent_phone', $agent_id) ?: '';
                    $agent_data['mobile'] = get_field('agent_mobile', $agent_id) ?: '';
                    $agent_data['title'] = get_field('agent_title', $agent_id) ?: '';
                    $agent_data['specialties'] = get_field('agent_specialties', $agent_id) ?: [];
                    $agent_data['years_experience'] = get_field('agent_years_experience', $agent_id) ?: 0;
                    
                    // Office info
                    $office = get_field('agent_office', $agent_id);
                    if ($office) {
                        $agent_data['office'] = [
                            'id' => $office->ID,
                            'name' => get_the_title($office->ID)
                        ];
                    }
                }
                
                // Get quick stats
                $agent_data['quick_stats'] = [
                    'active_listings' => 0,
                    'total_sales' => 0
                ];
                
                // Quick active listings count
                $active_count = new WP_Query([
                    'post_type' => 'listing',
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                    'meta_query' => [
                        [
                            'key' => 'listing_agent',
                            'value' => $agent_id,
                            'compare' => '='
                        ]
                    ],
                    'tax_query' => [
                        [
                            'taxonomy' => 'property_status',
                            'field' => 'slug',
                            'terms' => ['active', 'available', 'for-sale'],
                            'operator' => 'IN'
                        ]
                    ]
                ]);
                
                $agent_data['quick_stats']['active_listings'] = $active_count->found_posts;
                
                $agents[] = $agent_data;
            }
            wp_reset_postdata();
        }
        
        $result = [
            'agents' => $agents,
            'total_found' => $agents_query->found_posts,
            'total_pages' => $agents_query->max_num_pages,
            'current_page' => max(1, get_query_var('paged')),
            'query_args' => $args
        ];
        
        wp_cache_set($cache_key, $result, 'hph_agents', 3600); // 1 hour
        
        return $result;
    }
}

/**
 * Get agent's recent activity and updates
 * 
 * @param int $agent_id Agent ID
 * @param int $count Number of activities to return
 * @return array Recent activity
 */
if (!function_exists('hph_agent_bridge_get_recent_activity')) {
    function hph_agent_bridge_get_recent_activity($agent_id, $count = 10) {
        $cache_key = "hph_agent_activity_{$agent_id}_{$count}";
        $cached_data = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $activities = [];
        
        // Recent listings (new, price changes, status changes)
        $recent_listings = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'modified',
            'order' => 'DESC',
            'date_query' => [
                [
                    'after' => '30 days ago',
                    'inclusive' => true
                ]
            ],
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        if ($recent_listings->have_posts()) {
            while ($recent_listings->have_posts()) {
                $recent_listings->the_post();
                $listing_id = get_the_ID();
                
                $activities[] = [
                    'type' => 'listing_update',
                    'date' => get_the_modified_date('Y-m-d H:i:s'),
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'description' => 'Updated listing',
                    'listing_id' => $listing_id
                ];
            }
            wp_reset_postdata();
        }
        
        // Sort activities by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Limit to requested count
        $activities = array_slice($activities, 0, $count);
        
        wp_cache_set($cache_key, $activities, 'hph_agents', 1800); // 30 minutes
        
        return $activities;
    }
}

/**
 * Get agent's featured/spotlight listings
 * 
 * @param int $agent_id Agent ID
 * @param int $count Number of featured listings
 * @return array Featured listings
 */
if (!function_exists('hph_agent_bridge_get_featured_listings')) {
    function hph_agent_bridge_get_featured_listings($agent_id, $count = 6) {
        $cache_key = "hph_agent_featured_{$agent_id}_{$count}";
        $cached_data = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // First try to get listings marked as featured
        $featured_query = new WP_Query([
            'post_type' => 'listing',
            'post_status' => 'publish',
            'posts_per_page' => $count,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'is_featured',
                    'value' => true,
                    'compare' => '='
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'property_status',
                    'field' => 'slug',
                    'terms' => ['active', 'available', 'for-sale'],
                    'operator' => 'IN'
                ]
            ]
        ]);
        
        $featured_listings = [];
        
        if ($featured_query->have_posts()) {
            while ($featured_query->have_posts()) {
                $featured_query->the_post();
                $listing_id = get_the_ID();
                
                $featured_listings[] = [
                    'id' => $listing_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'featured_image' => get_the_post_thumbnail_url($listing_id, 'large'),
                    'price' => get_field('price', $listing_id) ?: 0,
                    'price_formatted' => hph_format_price(get_field('price', $listing_id) ?: 0),
                    'bedrooms' => get_field('bedrooms', $listing_id) ?: 0,
                    'bathrooms' => get_field('bathrooms_total', $listing_id) ?: 0,
                    'square_footage' => get_field('square_footage', $listing_id) ?: 0,
                    'city' => get_field('city', $listing_id) ?: '',
                    'state' => get_field('state', $listing_id) ?: '',
                    'is_featured' => true
                ];
            }
            wp_reset_postdata();
        }
        
        // If we don't have enough featured listings, fill with recent active listings
        if (count($featured_listings) < $count) {
            $remaining = $count - count($featured_listings);
            $recent_query = new WP_Query([
                'post_type' => 'listing',
                'post_status' => 'publish',
                'posts_per_page' => $remaining,
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'property_status',
                        'field' => 'slug',
                        'terms' => ['active', 'available', 'for-sale'],
                        'operator' => 'IN'
                    ]
                ],
                'post__not_in' => array_column($featured_listings, 'id')
            ]);
            
            if ($recent_query->have_posts()) {
                while ($recent_query->have_posts()) {
                    $recent_query->the_post();
                    $listing_id = get_the_ID();
                    
                    $featured_listings[] = [
                        'id' => $listing_id,
                        'title' => get_the_title(),
                        'url' => get_permalink(),
                        'featured_image' => get_the_post_thumbnail_url($listing_id, 'large'),
                        'price' => get_field('price', $listing_id) ?: 0,
                        'price_formatted' => hph_format_price(get_field('price', $listing_id) ?: 0),
                        'bedrooms' => get_field('bedrooms', $listing_id) ?: 0,
                        'bathrooms' => get_field('bathrooms_total', $listing_id) ?: 0,
                        'square_footage' => get_field('square_footage', $listing_id) ?: 0,
                        'city' => get_field('city', $listing_id) ?: '',
                        'state' => get_field('state', $listing_id) ?: '',
                        'is_featured' => false
                    ];
                }
                wp_reset_postdata();
            }
        }
        
        wp_cache_set($cache_key, $featured_listings, 'hph_agents', 3600); // 1 hour
        
        return $featured_listings;
    }
}

/**
 * Clear agent-related cache when agent data is updated
 * 
 * @param int $agent_id Agent ID
 */
if (!function_exists('hph_agent_bridge_clear_cache')) {
    function hph_agent_bridge_clear_cache($agent_id) {
        // Clear all agent-related cache keys
        $cache_keys = [
            "hph_agent_profile_{$agent_id}",
            "hph_agent_calc_stats_{$agent_id}",
            "hph_agent_activity_{$agent_id}",
            "hph_agent_featured_{$agent_id}"
        ];
        
        foreach ($cache_keys as $key_prefix) {
            wp_cache_delete($key_prefix, 'hph_agents');
        }
        
        // Clear wildcard caches by flushing the group
        wp_cache_flush_group('hph_agents');
        
        error_log("🧹 Cleared agent cache for agent {$agent_id}");
    }
}

// Hook to clear cache when agent posts are updated
add_action('acf/save_post', function($post_id) {
    if (get_post_type($post_id) === 'agent') {
        hph_agent_bridge_clear_cache($post_id);
    }
});

// Hook to clear agent cache when listings are updated (affects agent stats)
add_action('acf/save_post', function($post_id) {
    if (get_post_type($post_id) === 'listing') {
        $agent_id = get_field('listing_agent', $post_id);
        if ($agent_id) {
            $agent_id = is_object($agent_id) ? $agent_id->ID : $agent_id;
            hph_agent_bridge_clear_cache($agent_id);
        }
    }
});