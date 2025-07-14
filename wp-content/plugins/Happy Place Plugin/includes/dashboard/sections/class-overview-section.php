<?php

/**
 * Overview Section Handler
 * 
 * Handles all data operations and business logic for the dashboard overview section.
 * Provides quick stats, recent activity, and actionable insights for agents.
 * 
 * @package HappyPlace
 * @since 2.0.0
 */

namespace HappyPlace\Dashboard\Sections;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Overview Section Class
 * 
 * Manages:
 * - Dashboard statistics and KPIs
 * - Recent activity feeds
 * - Quick actions and shortcuts
 * - Performance summaries
 * - Goal tracking and progress
 */
class Overview_Section
{
    /**
     * @var Overview_Section|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var int Number of recent activities to show
     */
    private int $recent_activities_limit = 10;

    /**
     * @var int Number of days for recent stats
     */
    private int $recent_days = 30;

    /**
     * Get singleton instance
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->setup_hooks();
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks(): void
    {
        add_filter('hph_get_dashboard_section_data', [$this, 'get_section_data'], 10, 2);
        add_action('hph_daily_stats_update', [$this, 'update_daily_stats']);
        add_action('wp_ajax_hph_dismiss_notification', [$this, 'dismiss_notification']);
    }

    /**
     * Get overview section data
     */
    public function get_section_data(array $default, string $section): array
    {
        if ($section !== 'overview') {
            return $default;
        }

        $user_id = get_current_user_id();
        $current_time = current_time('timestamp');

        return [
            'stats' => $this->get_dashboard_stats($user_id),
            'recent_activity' => $this->get_recent_activity($user_id),
            'quick_actions' => $this->get_quick_actions(),
            'upcoming_events' => $this->get_upcoming_events($user_id),
            'notifications' => $this->get_user_notifications($user_id),
            'performance_summary' => $this->get_performance_summary($user_id),
            'goals' => $this->get_user_goals($user_id),
            'market_insights' => $this->get_market_insights(),
            'greeting' => $this->get_time_based_greeting(),
            'weather' => $this->get_weather_data(),
            'cache_info' => [
                'last_updated' => $current_time,
                'next_refresh' => $current_time + (15 * MINUTE_IN_SECONDS)
            ]
        ];
    }

    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats(int $user_id): array
    {
        $stats = [
            // Listings stats
            'listings' => [
                'total' => $this->count_user_listings($user_id),
                'active' => $this->count_user_listings($user_id, 'active'),
                'pending' => $this->count_user_listings($user_id, 'pending'),
                'sold_this_month' => $this->count_sold_listings_this_month($user_id),
                'sold_this_year' => $this->count_sold_listings_this_year($user_id),
                'average_price' => $this->get_average_listing_price($user_id),
                'total_value' => $this->get_total_active_listings_value($user_id)
            ],

            // Leads stats
            'leads' => [
                'total' => $this->count_user_leads($user_id),
                'new_this_week' => $this->count_new_leads_this_week($user_id),
                'hot_leads' => $this->count_hot_leads($user_id),
                'conversion_rate' => $this->calculate_lead_conversion_rate($user_id),
                'follow_ups_due' => $this->count_follow_ups_due($user_id),
                'contacted_today' => $this->count_leads_contacted_today($user_id)
            ],

            // Open houses stats
            'open_houses' => [
                'upcoming' => $this->count_upcoming_open_houses($user_id),
                'this_month' => $this->count_open_houses_this_month($user_id),
                'total_attendees_this_month' => $this->count_total_attendees_this_month($user_id),
                'average_attendance' => $this->get_average_open_house_attendance($user_id),
                'leads_generated' => $this->count_open_house_leads_this_month($user_id)
            ],

            // Performance stats
            'performance' => [
                'sales_volume_this_month' => $this->get_sales_volume_this_month($user_id),
                'sales_volume_this_year' => $this->get_sales_volume_this_year($user_id),
                'commission_this_month' => $this->get_commission_this_month($user_id),
                'commission_this_year' => $this->get_commission_this_year($user_id),
                'deals_closed_this_month' => $this->count_deals_closed_this_month($user_id),
                'deals_closed_this_year' => $this->count_deals_closed_this_year($user_id),
                'average_days_on_market' => $this->get_average_days_on_market($user_id)
            ]
        ];

        // Add percentage changes compared to previous periods
        $stats['listings']['change_from_last_month'] = $this->calculate_listings_change($user_id);
        $stats['leads']['change_from_last_week'] = $this->calculate_leads_change($user_id);
        $stats['performance']['change_from_last_month'] = $this->calculate_performance_change($user_id);

        return apply_filters('hph_overview_stats', $stats, $user_id);
    }

    /**
     * Get recent activity feed
     */
    public function get_recent_activity(int $user_id): array
    {
        $activities = [];

        // Recent listings
        $recent_listings = $this->get_recent_listings($user_id, 5);
        foreach ($recent_listings as $listing) {
            $activities[] = [
                'type' => 'listing_created',
                'title' => sprintf(__('New listing: %s', 'happy-place'), $listing['title']),
                'description' => sprintf(__('Listed at %s', 'happy-place'), $listing['formatted_price']),
                'date' => $listing['date_created'],
                'icon' => 'fa-home',
                'url' => $listing['edit_url'],
                'priority' => 'medium'
            ];
        }

        // Recent leads
        $recent_leads = $this->get_recent_leads($user_id, 5);
        foreach ($recent_leads as $lead) {
            $activities[] = [
                'type' => 'lead_received',
                'title' => sprintf(__('New lead from %s', 'happy-place'), $lead['name']),
                'description' => sprintf(__('Interested in %s', 'happy-place'), $lead['interest']),
                'date' => $lead['date_created'],
                'icon' => 'fa-user-plus',
                'url' => $lead['edit_url'],
                'priority' => $lead['priority'] === 'hot' ? 'high' : 'medium'
            ];
        }

        // Recent sales
        $recent_sales = $this->get_recent_sales($user_id, 3);
        foreach ($recent_sales as $sale) {
            $activities[] = [
                'type' => 'sale_completed',
                'title' => sprintf(__('Sale completed: %s', 'happy-place'), $sale['title']),
                'description' => sprintf(__('Sold for %s', 'happy-place'), $sale['formatted_price']),
                'date' => $sale['sale_date'],
                'icon' => 'fa-handshake',
                'url' => $sale['view_url'],
                'priority' => 'high'
            ];
        }

        // Recent open houses
        $recent_open_houses = $this->get_recent_open_houses($user_id, 3);
        foreach ($recent_open_houses as $open_house) {
            $activities[] = [
                'type' => 'open_house_completed',
                'title' => sprintf(__('Open house completed: %s', 'happy-place'), $open_house['listing_title']),
                'description' => sprintf(__('%d attendees', 'happy-place'), $open_house['attendee_count']),
                'date' => $open_house['date'],
                'icon' => 'fa-calendar-check',
                'url' => $open_house['edit_url'],
                'priority' => 'medium'
            ];
        }

        // Sort by date (newest first)
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, $this->recent_activities_limit);
    }

    /**
     * Get quick actions
     */
    public function get_quick_actions(): array
    {
        $actions = [
            'new_listing' => [
                'title' => __('Add New Listing', 'happy-place'),
                'description' => __('Create a new property listing', 'happy-place'),
                'icon' => 'fa-plus-circle',
                'url' => $this->get_dashboard_url(['section' => 'listings', 'action' => 'new-listing']),
                'color' => 'primary',
                'permission' => 'edit_posts'
            ],
            'new_lead' => [
                'title' => __('Add New Lead', 'happy-place'),
                'description' => __('Add a new potential client', 'happy-place'),
                'icon' => 'fa-user-plus',
                'url' => $this->get_dashboard_url(['section' => 'leads', 'action' => 'new-lead']),
                'color' => 'success',
                'permission' => 'manage_leads'
            ],
            'schedule_open_house' => [
                'title' => __('Schedule Open House', 'happy-place'),
                'description' => __('Plan your next open house event', 'happy-place'),
                'icon' => 'fa-calendar-plus',
                'url' => $this->get_dashboard_url(['section' => 'open-houses', 'action' => 'new-open-house']),
                'color' => 'info',
                'permission' => 'edit_posts'
            ],
            'view_performance' => [
                'title' => __('View Performance', 'happy-place'),
                'description' => __('Check your sales analytics', 'happy-place'),
                'icon' => 'fa-chart-line',
                'url' => $this->get_dashboard_url(['section' => 'performance']),
                'color' => 'warning',
                'permission' => 'read'
            ]
        ];

        // Filter actions based on user permissions
        $filtered_actions = [];
        foreach ($actions as $key => $action) {
            if (current_user_can($action['permission'])) {
                $filtered_actions[$key] = $action;
            }
        }

        return apply_filters('hph_overview_quick_actions', $filtered_actions);
    }

    /**
     * Get upcoming events
     */
    public function get_upcoming_events(int $user_id): array
    {
        $events = [];

        // Upcoming open houses
        $open_houses = $this->get_upcoming_open_houses($user_id, 5);
        foreach ($open_houses as $open_house) {
            $events[] = [
                'type' => 'open_house',
                'title' => sprintf(__('Open House: %s', 'happy-place'), $open_house['listing_title']),
                'date' => $open_house['date'],
                'time' => $open_house['start_time'],
                'location' => $open_house['address'],
                'icon' => 'fa-home',
                'url' => $open_house['edit_url'],
                'priority' => 'high'
            ];
        }

        // Follow-up reminders
        $follow_ups = $this->get_follow_up_reminders($user_id, 5);
        foreach ($follow_ups as $follow_up) {
            $events[] = [
                'type' => 'follow_up',
                'title' => sprintf(__('Follow up with %s', 'happy-place'), $follow_up['lead_name']),
                'date' => $follow_up['due_date'],
                'time' => $follow_up['due_time'] ?? '09:00',
                'location' => '',
                'icon' => 'fa-phone',
                'url' => $follow_up['lead_url'],
                'priority' => $follow_up['priority'] === 'hot' ? 'high' : 'medium'
            ];
        }

        // Sort by date (soonest first)
        usort($events, function($a, $b) {
            return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
        });

        return array_slice($events, 0, 10);
    }

    /**
     * Get user notifications
     */
    public function get_user_notifications(int $user_id): array
    {
        $notifications = [];

        // Check for overdue follow-ups
        $overdue_follow_ups = $this->count_overdue_follow_ups($user_id);
        if ($overdue_follow_ups > 0) {
            $notifications[] = [
                'type' => 'warning',
                'title' => __('Overdue Follow-ups', 'happy-place'),
                'message' => sprintf(
                    _n('You have %d overdue follow-up', 'You have %d overdue follow-ups', $overdue_follow_ups, 'happy-place'),
                    $overdue_follow_ups
                ),
                'action_text' => __('View Leads', 'happy-place'),
                'action_url' => $this->get_dashboard_url(['section' => 'leads', 'filter' => 'overdue']),
                'dismissible' => false
            ];
        }

        // Check for listings without photos
        $listings_without_photos = $this->count_listings_without_photos($user_id);
        if ($listings_without_photos > 0) {
            $notifications[] = [
                'type' => 'info',
                'title' => __('Listings Need Photos', 'happy-place'),
                'message' => sprintf(
                    _n('%d listing needs photos', '%d listings need photos', $listings_without_photos, 'happy-place'),
                    $listings_without_photos
                ),
                'action_text' => __('Add Photos', 'happy-place'),
                'action_url' => $this->get_dashboard_url(['section' => 'listings', 'filter' => 'no-photos']),
                'dismissible' => true,
                'id' => 'listings_without_photos'
            ];
        }

        // Check for goal achievements
        $achieved_goals = $this->get_recently_achieved_goals($user_id);
        foreach ($achieved_goals as $goal) {
            $notifications[] = [
                'type' => 'success',
                'title' => __('Goal Achieved!', 'happy-place'),
                'message' => sprintf(__('Congratulations! You\'ve achieved your %s goal.', 'happy-place'), $goal['name']),
                'action_text' => __('View Performance', 'happy-place'),
                'action_url' => $this->get_dashboard_url(['section' => 'performance']),
                'dismissible' => true,
                'id' => 'goal_achieved_' . $goal['id']
            ];
        }

        return apply_filters('hph_overview_notifications', $notifications, $user_id);
    }

    /**
     * Get performance summary
     */
    public function get_performance_summary(int $user_id): array
    {
        $current_month = date('n');
        $current_year = date('Y');
        $last_month = $current_month === 1 ? 12 : $current_month - 1;
        $last_month_year = $current_month === 1 ? $current_year - 1 : $current_year;

        return [
            'this_month' => [
                'sales_volume' => $this->get_sales_volume_this_month($user_id),
                'deals_closed' => $this->count_deals_closed_this_month($user_id),
                'commission' => $this->get_commission_this_month($user_id),
                'new_leads' => $this->count_new_leads_this_month($user_id)
            ],
            'last_month' => [
                'sales_volume' => $this->get_sales_volume_by_month($user_id, $last_month, $last_month_year),
                'deals_closed' => $this->count_deals_closed_by_month($user_id, $last_month, $last_month_year),
                'commission' => $this->get_commission_by_month($user_id, $last_month, $last_month_year),
                'new_leads' => $this->count_new_leads_by_month($user_id, $last_month, $last_month_year)
            ],
            'year_to_date' => [
                'sales_volume' => $this->get_sales_volume_this_year($user_id),
                'deals_closed' => $this->count_deals_closed_this_year($user_id),
                'commission' => $this->get_commission_this_year($user_id),
                'new_leads' => $this->count_new_leads_this_year($user_id)
            ],
            'trends' => [
                'sales_trend' => $this->calculate_sales_trend($user_id),
                'leads_trend' => $this->calculate_leads_trend($user_id),
                'conversion_trend' => $this->calculate_conversion_trend($user_id)
            ]
        ];
    }

    /**
     * Get user goals and progress
     */
    public function get_user_goals(int $user_id): array
    {
        $goals = get_user_meta($user_id, '_hph_goals', true) ?: [];
        $current_stats = $this->get_dashboard_stats($user_id);

        foreach ($goals as &$goal) {
            switch ($goal['type']) {
                case 'sales_volume':
                    $current_value = $current_stats['performance']['sales_volume_this_year'];
                    break;
                case 'deals_closed':
                    $current_value = $current_stats['performance']['deals_closed_this_year'];
                    break;
                case 'new_leads':
                    $current_value = $current_stats['leads']['total'];
                    break;
                case 'listings_sold':
                    $current_value = $current_stats['listings']['sold_this_year'];
                    break;
                default:
                    $current_value = 0;
            }

            $goal['current_value'] = $current_value;
            $goal['progress_percentage'] = $goal['target_value'] > 0 ? min(100, ($current_value / $goal['target_value']) * 100) : 0;
            $goal['remaining'] = max(0, $goal['target_value'] - $current_value);
            $goal['status'] = $goal['progress_percentage'] >= 100 ? 'achieved' : ($goal['progress_percentage'] >= 75 ? 'on_track' : 'behind');
        }

        return $goals;
    }

    /**
     * Get market insights
     */
    public function get_market_insights(): array
    {
        // This could integrate with external APIs or local market data
        return [
            'average_price_trend' => 'up',
            'average_price_change' => '+3.2%',
            'inventory_level' => 'low',
            'days_on_market' => 28,
            'market_temperature' => 'hot',
            'best_performing_price_range' => '$300K - $500K',
            'top_property_type' => 'Single Family',
            'seasonal_insight' => $this->get_seasonal_insight()
        ];
    }

    /**
     * Get time-based greeting
     */
    public function get_time_based_greeting(): string
    {
        $hour = (int)current_time('H');
        $user_name = wp_get_current_user()->display_name;
        $first_name = explode(' ', $user_name)[0];

        if ($hour < 12) {
            return sprintf(__('Good morning, %s!', 'happy-place'), $first_name);
        } elseif ($hour < 17) {
            return sprintf(__('Good afternoon, %s!', 'happy-place'), $first_name);
        } else {
            return sprintf(__('Good evening, %s!', 'happy-place'), $first_name);
        }
    }

    /**
     * Get weather data (optional feature)
     */
    public function get_weather_data(): ?array
    {
        // This could integrate with weather APIs for open house planning
        $weather_enabled = get_option('hph_weather_enabled', false);
        
        if (!$weather_enabled) {
            return null;
        }

        // Placeholder for weather integration
        return [
            'current_temp' => 72,
            'condition' => 'sunny',
            'icon' => 'fa-sun',
            'good_for_open_house' => true
        ];
    }

    // =========================================================================
    // HELPER METHODS FOR DATA RETRIEVAL
    // =========================================================================

    /**
     * Count user listings
     */
    private function count_user_listings(int $user_id, string $status = ''): int
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        if (!empty($status)) {
            $args['meta_query'] = [[
                'key' => '_listing_status',
                'value' => $status,
                'compare' => '='
            ]];
        }

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Count user leads
     */
    private function count_user_leads(int $user_id, string $status = ''): int
    {
        $args = [
            'post_type' => 'hph_lead',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        if (!empty($status)) {
            $args['meta_query'] = [[
                'key' => '_lead_status',
                'value' => $status,
                'compare' => '='
            ]];
        }

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Count upcoming open houses
     */
    private function count_upcoming_open_houses(int $user_id): int
    {
        $args = [
            'post_type' => 'hph_open_house',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => '_open_house_date',
                'value' => current_time('Y-m-d'),
                'compare' => '>='
            ]]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get dashboard URL with parameters
     */
    private function get_dashboard_url(array $params = []): string
    {
        $dashboard_page = get_page_by_path('agent-dashboard');
        $base_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/agent-dashboard/');
        
        return add_query_arg($params, $base_url);
    }

    /**
     * Get seasonal insight
     */
    private function get_seasonal_insight(): string
    {
        $month = (int)date('n');
        
        if (in_array($month, [3, 4, 5])) {
            return __('Spring is peak buying season - great time for open houses!', 'happy-place');
        } elseif (in_array($month, [6, 7, 8])) {
            return __('Summer market is active - families are moving before school starts.', 'happy-place');
        } elseif (in_array($month, [9, 10, 11])) {
            return __('Fall market slows down but serious buyers remain active.', 'happy-place');
        } else {
            return __('Winter is a great time to prepare listings for spring market.', 'happy-place');
        }
    }

    /**
     * Dismiss notification AJAX handler
     */
    public function dismiss_notification(): void
    {
        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'happy-place'));
        }

        $notification_id = sanitize_key($_POST['notification_id'] ?? '');
        $user_id = get_current_user_id();

        if (!empty($notification_id)) {
            $dismissed = get_user_meta($user_id, '_hph_dismissed_notifications', true) ?: [];
            $dismissed[] = $notification_id;
            update_user_meta($user_id, '_hph_dismissed_notifications', array_unique($dismissed));
        }

        wp_send_json_success();
    }

    // Add more helper methods as needed for specific data calculations...
    // These would include methods like:
    // - count_sold_listings_this_month()
    // - get_average_listing_price()
    // - calculate_lead_conversion_rate()
    // - get_commission_this_month()
    // etc.
}

// Initialize
Overview_Section::instance();