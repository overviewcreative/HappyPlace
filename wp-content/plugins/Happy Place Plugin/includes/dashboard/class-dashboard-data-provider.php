<?php
/**
 * Dashboard Data Provider - Unified data source for dashboard components
 * 
 * Provides cached, optimized data access for dashboard sections and widgets.
 * Integrates with bridge functions and ACF fields for consistent data formatting.
 * 
 * @package HappyPlace
 * @subpackage Dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Data_Provider {
    
    /**
     * @var array Dashboard configuration
     */
    private array $config = [];
    
    /**
     * @var array Data cache
     */
    private array $data_cache = [];
    
    /**
     * @var int Current user ID
     */
    private int $user_id = 0;
    
    /**
     * Constructor
     *
     * @param array $config Dashboard configuration
     */
    public function __construct(array $config = []) {
        $this->config = $config;
        $this->user_id = get_current_user_id();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Clear cache when posts are updated
        add_action('save_post', [$this, 'clear_post_cache'], 10, 2);
        add_action('delete_post', [$this, 'clear_post_cache'], 10, 2);
        
        // Clear cache when user data changes
        add_action('profile_update', [$this, 'clear_user_cache']);
        add_action('user_meta_updated', [$this, 'clear_user_cache']);
        
        // Clear cache when ACF fields are updated
        add_action('acf/save_post', [$this, 'clear_acf_cache']);
    }
    
    /**
     * Get dashboard overview data
     *
     * @param int $user_id User ID (optional)
     * @return array Overview data
     */
    public function get_overview_data(int $user_id = 0): array {
        if (!$user_id) {
            $user_id = $this->user_id;
        }
        
        $cache_key = "overview_data_{$user_id}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        // Check WordPress cache
        $cached_data = wp_cache_get($cache_key, 'hph_dashboard_data');
        if ($cached_data !== false) {
            $this->data_cache[$cache_key] = $cached_data;
            return $cached_data;
        }
        
        // Generate overview data
        $overview_data = [
            'stats' => $this->get_user_stats($user_id),
            'recent_activity' => $this->get_recent_activity($user_id),
            'quick_actions' => $this->get_quick_actions($user_id),
            'notifications' => $this->get_notifications($user_id),
            'performance' => $this->get_performance_data($user_id)
        ];
        
        // Cache the data
        $cache_duration = $this->config['cache_duration'] ?? 15 * MINUTE_IN_SECONDS;
        wp_cache_set($cache_key, $overview_data, 'hph_dashboard_data', $cache_duration);
        $this->data_cache[$cache_key] = $overview_data;
        
        return apply_filters('hph_dashboard_overview_data', $overview_data, $user_id);
    }
    
    /**
     * Get user statistics
     *
     * @param int $user_id User ID
     * @return array User stats
     */
    public function get_user_stats(int $user_id): array {
        $cache_key = "user_stats_{$user_id}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        // Get agent post associated with user
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        
        $stats = [
            'total_listings' => 0,
            'active_listings' => 0,
            'sold_listings' => 0,
            'pending_listings' => 0,
            'total_leads' => 0,
            'active_leads' => 0,
            'converted_leads' => 0,
            'scheduled_showings' => 0
        ];
        
        if ($agent_id) {
            // Get listings for this agent
            $listing_args = [
                'post_type' => 'listing',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ]
            ];
            
            $listings = get_posts($listing_args);
            $stats['total_listings'] = count($listings);
            
            // Count by status
            foreach ($listings as $listing) {
                $status = get_field('listing_status', $listing->ID);
                switch ($status) {
                    case 'active':
                        $stats['active_listings']++;
                        break;
                    case 'sold':
                        $stats['sold_listings']++;
                        break;
                    case 'pending':
                        $stats['pending_listings']++;
                        break;
                }
            }
            
            // Get leads data (if lead tracking exists)
            $stats = array_merge($stats, $this->get_lead_stats($agent_id));
            
            // Get showing data (if showing system exists)
            $stats = array_merge($stats, $this->get_showing_stats($agent_id));
        }
        
        $this->data_cache[$cache_key] = $stats;
        
        return apply_filters('hph_dashboard_user_stats', $stats, $user_id, $agent_id);
    }
    
    /**
     * Get recent activity
     *
     * @param int $user_id User ID
     * @param int $limit Activity limit
     * @return array Recent activity
     */
    public function get_recent_activity(int $user_id, int $limit = 10): array {
        $cache_key = "recent_activity_{$user_id}_{$limit}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        $activity = [];
        
        if ($agent_id) {
            // Get recent listings
            $recent_listings = get_posts([
                'post_type' => 'listing',
                'posts_per_page' => $limit,
                'orderby' => 'modified',
                'order' => 'DESC',
                'meta_query' => [
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ]
            ]);
            
            foreach ($recent_listings as $listing) {
                $activity[] = [
                    'type' => 'listing_updated',
                    'title' => $listing->post_title,
                    'description' => sprintf(__('Listing "%s" was updated', 'happy-place'), $listing->post_title),
                    'date' => $listing->post_modified,
                    'url' => get_permalink($listing->ID),
                    'icon' => 'fas fa-home',
                    'priority' => 'medium'
                ];
            }
            
            // Get recent form submissions (if available)
            $activity = array_merge($activity, $this->get_recent_form_activity($agent_id, $limit));
            
            // Get recent open houses (if available)
            $activity = array_merge($activity, $this->get_recent_openhouse_activity($agent_id, $limit));
        }
        
        // Sort by date
        usort($activity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Limit results
        $activity = array_slice($activity, 0, $limit);
        
        $this->data_cache[$cache_key] = $activity;
        
        return apply_filters('hph_dashboard_recent_activity', $activity, $user_id, $limit);
    }
    
    /**
     * Get quick actions for user
     *
     * @param int $user_id User ID
     * @return array Quick actions
     */
    public function get_quick_actions(int $user_id): array {
        $cache_key = "quick_actions_{$user_id}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        $user = get_userdata($user_id);
        $actions = [];
        
        // Actions based on user role
        if (in_array('estate_agent', $user->roles) || in_array('broker', $user->roles)) {
            $actions = [
                'add_listing' => [
                    'title' => __('Add New Listing', 'happy-place'),
                    'description' => __('Create a new property listing', 'happy-place'),
                    'url' => '#',
                    'icon' => 'fas fa-plus',
                    'class' => 'button button-primary',
                    'capability' => 'edit_posts',
                    'modal' => true,
                    'form_id' => 'new-listing-form'
                ],
                'schedule_openhouse' => [
                    'title' => __('Schedule Open House', 'happy-place'),
                    'description' => __('Schedule a new open house event', 'happy-place'),
                    'url' => '#',
                    'icon' => 'fas fa-calendar-plus',
                    'class' => 'button',
                    'capability' => 'edit_posts',
                    'modal' => true,
                    'form_id' => 'open-house-form'
                ],
                'add_agent' => [
                    'title' => __('Add Agent', 'happy-place'),
                    'description' => __('Add a new agent profile', 'happy-place'),
                    'url' => '#',
                    'icon' => 'fas fa-user-plus',
                    'class' => 'button',
                    'capability' => 'edit_posts',
                    'modal' => true,
                    'form_id' => 'new-agent-form'
                ],
                'view_analytics' => [
                    'title' => __('View Analytics', 'happy-place'),
                    'description' => __('View performance analytics', 'happy-place'),
                    'url' => '#analytics',
                    'icon' => 'fas fa-chart-line',
                    'class' => 'button',
                    'capability' => 'read',
                    'section_link' => true
                ]
            ];
        }
        
        // Filter actions by user capabilities
        $actions = array_filter($actions, function($action) use ($user_id) {
            return empty($action['capability']) || user_can($user_id, $action['capability']);
        });
        
        $this->data_cache[$cache_key] = $actions;
        
        return apply_filters('hph_dashboard_quick_actions', $actions, $user_id);
    }
    
    /**
     * Get notifications for user
     *
     * @param int $user_id User ID
     * @return array Notifications
     */
    public function get_notifications(int $user_id): array {
        $cache_key = "notifications_{$user_id}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        $notifications = [];
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        
        if ($agent_id) {
            // Check for pending listings
            $pending_listings = get_posts([
                'post_type' => 'listing',
                'post_status' => 'pending',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ]
            ]);
            
            if (!empty($pending_listings)) {
                $notifications[] = [
                    'type' => 'warning',
                    'title' => __('Pending Listings', 'happy-place'),
                    'message' => sprintf(
                        _n(
                            'You have %d listing waiting for approval',
                            'You have %d listings waiting for approval',
                            count($pending_listings),
                            'happy-place'
                        ),
                        count($pending_listings)
                    ),
                    'action_url' => '#listings?status=pending',
                    'action_text' => __('Review Listings', 'happy-place'),
                    'dismissible' => false,
                    'priority' => 'high'
                ];
            }
            
            // Check for upcoming open houses
            $upcoming_openhouses = $this->get_upcoming_openhouses($agent_id);
            if (!empty($upcoming_openhouses)) {
                $notifications[] = [
                    'type' => 'info',
                    'title' => __('Upcoming Open Houses', 'happy-place'),
                    'message' => sprintf(
                        _n(
                            'You have %d open house scheduled for this week',
                            'You have %d open houses scheduled for this week',
                            count($upcoming_openhouses),
                            'happy-place'
                        ),
                        count($upcoming_openhouses)
                    ),
                    'action_url' => '#calendar',
                    'action_text' => __('View Calendar', 'happy-place'),
                    'dismissible' => true,
                    'priority' => 'medium'
                ];
            }
            
            // Check for expired listings
            $expired_listings = $this->get_expired_listings($agent_id);
            if (!empty($expired_listings)) {
                $notifications[] = [
                    'type' => 'error',
                    'title' => __('Expired Listings', 'happy-place'),
                    'message' => sprintf(
                        _n(
                            'You have %d listing that has expired',
                            'You have %d listings that have expired',
                            count($expired_listings),
                            'happy-place'
                        ),
                        count($expired_listings)
                    ),
                    'action_url' => '#listings?status=expired',
                    'action_text' => __('Update Listings', 'happy-place'),
                    'dismissible' => false,
                    'priority' => 'high'
                ];
            }
        }
        
        $this->data_cache[$cache_key] = $notifications;
        
        return apply_filters('hph_dashboard_notifications', $notifications, $user_id);
    }
    
    /**
     * Get performance data
     *
     * @param int $user_id User ID
     * @return array Performance data
     */
    public function get_performance_data(int $user_id): array {
        $cache_key = "performance_data_{$user_id}";
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        $performance = [
            'listings_this_month' => 0,
            'listings_last_month' => 0,
            'sales_this_month' => 0,
            'sales_last_month' => 0,
            'average_days_on_market' => 0,
            'total_commission' => 0,
            'conversion_rate' => 0,
            'trending' => []
        ];
        
        if ($agent_id) {
            $current_month = date('Y-m');
            $last_month = date('Y-m', strtotime('-1 month'));
            
            // Get listings for current month
            $current_month_listings = get_posts([
                'post_type' => 'listing',
                'posts_per_page' => -1,
                'date_query' => [
                    [
                        'year' => date('Y'),
                        'month' => date('n')
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
            
            $performance['listings_this_month'] = count($current_month_listings);
            
            // Get listings for last month
            $last_month_listings = get_posts([
                'post_type' => 'listing',
                'posts_per_page' => -1,
                'date_query' => [
                    [
                        'year' => date('Y', strtotime('-1 month')),
                        'month' => date('n', strtotime('-1 month'))
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
            
            $performance['listings_last_month'] = count($last_month_listings);
            
            // Calculate sales data
            $performance = array_merge($performance, $this->calculate_sales_performance($agent_id));
            
            // Calculate trending data
            $performance['trending'] = $this->calculate_trending_data($agent_id);
        }
        
        $this->data_cache[$cache_key] = $performance;
        
        return apply_filters('hph_dashboard_performance_data', $performance, $user_id);
    }
    
    /**
     * Get listings data for user
     *
     * @param int $user_id User ID
     * @param array $args Query arguments
     * @return array Listings data
     */
    public function get_listings_data(int $user_id, array $args = []): array {
        $default_args = [
            'posts_per_page' => 20,
            'orderby' => 'modified',
            'order' => 'DESC',
            'status' => 'any'
        ];
        
        $args = wp_parse_args($args, $default_args);
        $cache_key = "listings_data_{$user_id}_" . md5(serialize($args));
        
        if (isset($this->data_cache[$cache_key])) {
            return $this->data_cache[$cache_key];
        }
        
        $agent_id = get_user_meta($user_id, 'agent_post_id', true);
        $listings_data = [
            'listings' => [],
            'total' => 0,
            'by_status' => [],
            'pagination' => []
        ];
        
        if ($agent_id) {
            $query_args = [
                'post_type' => 'listing',
                'posts_per_page' => $args['posts_per_page'],
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
            
            // Add status filter if specified
            if ($args['status'] !== 'any') {
                $query_args['meta_query'][] = [
                    'key' => 'listing_status',
                    'value' => $args['status'],
                    'compare' => '='
                ];
            }
            
            $listings_query = new \WP_Query($query_args);
            
            // Process listings
            foreach ($listings_query->posts as $listing) {
                $listings_data['listings'][] = $this->format_listing_data($listing);
            }
            
            $listings_data['total'] = $listings_query->found_posts;
            $listings_data['pagination'] = [
                'current_page' => max(1, get_query_var('paged')),
                'total_pages' => $listings_query->max_num_pages,
                'posts_per_page' => $args['posts_per_page']
            ];
            
            // Get status counts
            $listings_data['by_status'] = $this->get_listings_by_status($agent_id);
        }
        
        $this->data_cache[$cache_key] = $listings_data;
        
        return apply_filters('hph_dashboard_listings_data', $listings_data, $user_id, $args);
    }
    
    /**
     * Format listing data for dashboard
     *
     * @param \WP_Post $listing Listing post object
     * @return array Formatted listing data
     */
    private function format_listing_data(\WP_Post $listing): array {
        $listing_data = [
            'id' => $listing->ID,
            'title' => $listing->post_title,
            'status' => get_field('listing_status', $listing->ID),
            'price' => get_field('price', $listing->ID),
            'bedrooms' => get_field('bedrooms', $listing->ID),
            'bathrooms' => get_field('bathrooms', $listing->ID),
            'square_feet' => get_field('square_feet', $listing->ID),
            'listing_date' => $listing->post_date,
            'modified_date' => $listing->post_modified,
            'permalink' => get_permalink($listing->ID),
            'edit_link' => get_edit_post_link($listing->ID),
            'featured_image' => get_the_post_thumbnail_url($listing->ID, 'medium'),
            'days_on_market' => $this->calculate_days_on_market($listing->ID)
        ];
        
        return apply_filters('hph_dashboard_format_listing_data', $listing_data, $listing);
    }
    
    /**
     * Get lead statistics
     *
     * @param int $agent_id Agent post ID
     * @return array Lead stats
     */
    private function get_lead_stats(int $agent_id): array {
        // Placeholder for lead system integration
        return [
            'total_leads' => 0,
            'active_leads' => 0,
            'converted_leads' => 0
        ];
    }
    
    /**
     * Get showing statistics
     *
     * @param int $agent_id Agent post ID
     * @return array Showing stats
     */
    private function get_showing_stats(int $agent_id): array {
        // Get scheduled open houses
        $openhouses = get_posts([
            'post_type' => 'open_house',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'hosting_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'open_house_date',
                    'value' => date('Y-m-d'),
                    'compare' => '>='
                ]
            ]
        ]);
        
        return [
            'scheduled_showings' => count($openhouses)
        ];
    }
    
    /**
     * Get recent form activity
     *
     * @param int $agent_id Agent post ID
     * @param int $limit Activity limit
     * @return array Form activity
     */
    private function get_recent_form_activity(int $agent_id, int $limit): array {
        // Placeholder for form system integration
        return [];
    }
    
    /**
     * Get recent open house activity
     *
     * @param int $agent_id Agent post ID
     * @param int $limit Activity limit
     * @return array Open house activity
     */
    private function get_recent_openhouse_activity(int $agent_id, int $limit): array {
        $activity = [];
        
        $recent_openhouses = get_posts([
            'post_type' => 'open_house',
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'hosting_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($recent_openhouses as $openhouse) {
            $listing_id = get_field('listing', $openhouse->ID);
            $listing_title = get_the_title($listing_id);
            
            $activity[] = [
                'type' => 'openhouse_scheduled',
                'title' => $openhouse->post_title,
                'description' => sprintf(__('Open house scheduled for "%s"', 'happy-place'), $listing_title),
                'date' => $openhouse->post_modified,
                'url' => get_edit_post_link($openhouse->ID),
                'icon' => 'fas fa-calendar',
                'priority' => 'medium'
            ];
        }
        
        return $activity;
    }
    
    /**
     * Get upcoming open houses
     *
     * @param int $agent_id Agent post ID
     * @return array Upcoming open houses
     */
    private function get_upcoming_openhouses(int $agent_id): array {
        $week_from_now = date('Y-m-d', strtotime('+7 days'));
        
        return get_posts([
            'post_type' => 'open_house',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'hosting_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'open_house_date',
                    'value' => [date('Y-m-d'), $week_from_now],
                    'compare' => 'BETWEEN'
                ]
            ]
        ]);
    }
    
    /**
     * Get expired listings
     *
     * @param int $agent_id Agent post ID
     * @return array Expired listings
     */
    private function get_expired_listings(int $agent_id): array {
        return get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'listing_status',
                    'value' => 'expired',
                    'compare' => '='
                ]
            ]
        ]);
    }
    
    /**
     * Calculate sales performance
     *
     * @param int $agent_id Agent post ID
     * @return array Sales performance data
     */
    private function calculate_sales_performance(int $agent_id): array {
        // Get sold listings for current month
        $current_month_sales = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'year' => date('Y'),
                    'month' => date('n')
                ]
            ],
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
        
        // Get sold listings for last month
        $last_month_sales = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'date_query' => [
                [
                    'year' => date('Y', strtotime('-1 month')),
                    'month' => date('n', strtotime('-1 month'))
                ]
            ],
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
        
        // Calculate average days on market
        $total_days = 0;
        $sold_count = 0;
        
        foreach ($current_month_sales as $sale) {
            $days = $this->calculate_days_on_market($sale->ID);
            if ($days > 0) {
                $total_days += $days;
                $sold_count++;
            }
        }
        
        $average_days = $sold_count > 0 ? round($total_days / $sold_count) : 0;
        
        return [
            'sales_this_month' => count($current_month_sales),
            'sales_last_month' => count($last_month_sales),
            'average_days_on_market' => $average_days
        ];
    }
    
    /**
     * Calculate trending data
     *
     * @param int $agent_id Agent post ID
     * @return array Trending data
     */
    private function calculate_trending_data(int $agent_id): array {
        return [
            'listings_trend' => 'up', // up, down, stable
            'sales_trend' => 'stable',
            'performance_trend' => 'up'
        ];
    }
    
    /**
     * Calculate days on market
     *
     * @param int $listing_id Listing post ID
     * @return int Days on market
     */
    private function calculate_days_on_market(int $listing_id): int {
        $listing_date = get_the_date('Y-m-d', $listing_id);
        $current_date = date('Y-m-d');
        
        $date1 = new \DateTime($listing_date);
        $date2 = new \DateTime($current_date);
        $interval = $date1->diff($date2);
        
        return $interval->days;
    }
    
    /**
     * Get listings by status count
     *
     * @param int $agent_id Agent post ID
     * @return array Status counts
     */
    private function get_listings_by_status(int $agent_id): array {
        $statuses = ['active', 'pending', 'sold', 'expired', 'withdrawn'];
        $counts = [];
        
        foreach ($statuses as $status) {
            $count = get_posts([
                'post_type' => 'listing',
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
                        'value' => $status,
                        'compare' => '='
                    ]
                ]
            ]);
            
            $counts[$status] = count($count);
        }
        
        return $counts;
    }
    
    /**
     * Clear cache when posts are updated
     *
     * @param int $post_id Post ID
     * @param \WP_Post $post Post object
     */
    public function clear_post_cache(int $post_id, \WP_Post $post): void {
        // Clear relevant caches based on post type
        if (in_array($post->post_type, ['listing', 'agent', 'open_house'])) {
            wp_cache_flush_group('hph_dashboard_data');
            $this->data_cache = [];
        }
    }
    
    /**
     * Clear cache when user data changes
     *
     * @param int $user_id User ID
     */
    public function clear_user_cache(int $user_id): void {
        $cache_keys = [
            "overview_data_{$user_id}",
            "user_stats_{$user_id}",
            "recent_activity_{$user_id}",
            "quick_actions_{$user_id}",
            "notifications_{$user_id}",
            "performance_data_{$user_id}"
        ];
        
        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'hph_dashboard_data');
            unset($this->data_cache[$key]);
        }
    }
    
    /**
     * Clear cache when ACF fields are updated
     *
     * @param int $post_id Post ID
     */
    public function clear_acf_cache(int $post_id): void {
        $post = get_post($post_id);
        if ($post && in_array($post->post_type, ['listing', 'agent', 'open_house'])) {
            wp_cache_flush_group('hph_dashboard_data');
            $this->data_cache = [];
        }
    }
    
    /**
     * Get current user ID
     *
     * @return int User ID
     */
    public function get_user_id(): int {
        return $this->user_id;
    }
    
    /**
     * Set user ID
     *
     * @param int $user_id User ID
     */
    public function set_user_id(int $user_id): void {
        $this->user_id = $user_id;
    }
    
    /**
     * Clear all cache
     */
    public function clear_all_cache(): void {
        wp_cache_flush_group('hph_dashboard_data');
        $this->data_cache = [];
    }
}