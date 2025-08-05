<?php
/**
 * Overview Section - Dashboard overview with stats, activity, and quick actions
 * 
 * Provides a comprehensive overview of the agent's current status including
 * key statistics, recent activity, notifications, and quick action buttons.
 * 
 * @package HappyPlace
 * @subpackage Dashboard\Sections
 */

namespace HappyPlace\Dashboard\Sections;

if (!defined('ABSPATH')) {
    exit;
}

class Overview_Section extends Base_Dashboard_Section {
    
    /**
     * @var int Number of recent activities to show
     */
    private int $recent_activities_limit = 10;

    /**
     * @var int Number of days for recent stats
     */
    private int $recent_days = 30;

    /**
     * Get section identifier
     *
     * @return string Section ID
     */
    protected function get_section_id(): string {
        return 'overview';
    }
    
    /**
     * Get section title
     *
     * @return string Section title
     */
    protected function get_section_title(): string {
        return __('Dashboard Overview', 'happy-place');
    }
    
    /**
     * Get default section configuration
     *
     * @return array Default config
     */
    protected function get_default_config(): array {
        $config = parent::get_default_config();
        
        return array_merge($config, [
            'description' => __('Overview of your real estate activity and performance', 'happy-place'),
            'icon' => 'fas fa-tachometer-alt',
            'priority' => 5, // Show first
            'widgets_enabled' => true,
            'max_widgets' => 8
        ]);
    }

    /**
     * Setup additional hooks for overview section
     */
    protected function init_hooks(): void {
        parent::init_hooks();
        
        add_action('hph_daily_stats_update', [$this, 'update_daily_stats']);
        add_action('wp_ajax_hph_dismiss_notification', [$this, 'dismiss_notification']);
    }

    /**
     * Load section data
     *
     * @param array $args Data arguments
     * @return array Section data
     */
    protected function load_section_data(array $args = []): array {
        if (!$this->data_provider) {
            return $this->get_fallback_data();
        }
        
        $user_id = get_current_user_id();
        
        return [
            'overview' => $this->data_provider->get_overview_data($user_id),
            'user_info' => $this->get_user_info($user_id),
            'dashboard_config' => $this->get_dashboard_config()
        ];
    }

    /**
     * Render section content
     *
     * @param array $args Rendering arguments
     */
    public function render(array $args = []): void {
        // Check access
        if (!$this->can_access()) {
            $this->render_error_state(__('You do not have permission to access this section.', 'happy-place'));
            return;
        }
        
        // Get section data
        $data = $this->get_data($args);
        
        if (empty($data['overview'])) {
            $this->render_loading_state();
            return;
        }
        
        $overview = $data['overview'];
        
        ?>
        <div class="hph-overview-section hph-dashboard-container">
            
            <!-- Welcome Header -->
            <div class="hph-section-modern hph-section-modern--glass">
                <div class="hph-section-header hph-section-header--primary">
                    <?php $this->render_welcome_header($data['user_info']); ?>
                </div>
            </div>
            
            <!-- Key Statistics -->
            <div class="hph-stats-grid">
                <?php $this->render_stats_widgets($overview['stats']); ?>
            </div>
            
            <!-- Main Dashboard Content -->
            <div class="hph-content-grid hph-content-grid--3-col">
                
                <!-- Quick Actions Card -->
                <div class="hph-content-card hph-content-card--interactive">
                    <div class="hph-content-header">
                        <div class="content-title">
                            <span class="title-text">
                                <i class="fas fa-bolt"></i>
                                <?php esc_html_e('Quick Actions', 'happy-place'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="hph-content-body">
                        <?php $this->render_quick_actions($overview['quick_actions']); ?>
                    </div>
                </div>
                
                <!-- Recent Activity Card -->
                <div class="hph-content-card">
                    <div class="hph-content-header">
                        <div class="content-title">
                            <span class="title-text">
                                <i class="fas fa-clock"></i>
                                <?php esc_html_e('Recent Activity', 'happy-place'); ?>
                            </span>
                            <span class="title-badge"><?php echo count($overview['recent_activity']); ?></span>
                        </div>
                    </div>
                    <div class="hph-content-body hph-content-body--compact">
                        <?php $this->render_recent_activity($overview['recent_activity']); ?>
                    </div>
                    <div class="hph-content-footer">
                        <div class="footer-actions">
                            <a href="<?php echo esc_url($this->get_dashboard_url(['section' => 'activity'])); ?>" class="hph-btn hph-btn--ghost hph-btn--sm">
                                <?php esc_html_e('View All', 'happy-place'); ?>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Summary Card -->
                <div class="hph-content-card">
                    <div class="hph-content-header">
                        <div class="content-title">
                            <span class="title-text">
                                <i class="fas fa-chart-line"></i>
                                <?php esc_html_e('Performance', 'happy-place'); ?>
                            </span>
                        </div>
                        <div class="content-subtitle">
                            <?php esc_html_e('This month overview', 'happy-place'); ?>
                        </div>
                    </div>
                    <div class="hph-content-body">
                        <?php $this->render_performance_summary($overview['performance']); ?>
                    </div>
                    <div class="hph-content-footer">
                        <div class="footer-actions">
                            <a href="<?php echo esc_url($this->get_dashboard_url(['section' => 'analytics'])); ?>" class="hph-btn hph-btn--ghost hph-btn--sm">
                                <?php esc_html_e('View Analytics', 'happy-place'); ?>
                                <i class="fas fa-chart-bar"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
            
            <?php if (!empty($overview['notifications'])): ?>
            <!-- Important Notifications -->
            <div class="hph-section-modern">
                <div class="hph-section-header">
                    <div class="hph-section-title">
                        <div class="title-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="title-text"><?php esc_html_e('Important Notifications', 'happy-place'); ?></h3>
                    </div>
                    <div class="hph-section-subtitle">
                        <?php esc_html_e('Stay updated with important alerts and reminders', 'happy-place'); ?>
                    </div>
                </div>
                <div class="hph-section-body">
                    <?php $this->render_notifications($overview['notifications']); ?>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        <?php
    }

    /**
     * Get fallback data when data provider is not available
     *
     * @return array Fallback section data
     */
    private function get_fallback_data(): array {
        $user_id = get_current_user_id();
        $current_time = current_time('timestamp');

        return [
            'overview' => [
                'stats' => $this->get_dashboard_stats($user_id),
                'recent_activity' => $this->get_recent_activity($user_id),
                'quick_actions' => $this->get_quick_actions(),
                'upcoming_events' => $this->get_upcoming_events($user_id),
                'notifications' => $this->get_user_notifications($user_id),
                'performance' => $this->get_performance_summary($user_id),
                'goals' => $this->get_user_goals($user_id),
                'market_insights' => $this->get_market_insights(),
                'greeting' => $this->get_time_based_greeting(),
                'weather' => $this->get_weather_data()
            ],
            'user_info' => $this->get_user_info($user_id),
            'dashboard_config' => $this->get_dashboard_config(),
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

    /**
     * Render welcome header
     *
     * @param array $user_info User information
     */
    private function render_welcome_header(array $user_info): void {
        $current_time = current_time('timestamp');
        $hour = date('H', $current_time);
        
        // Determine greeting based on time
        if ($hour < 12) {
            $greeting = __('Good morning', 'happy-place');
        } elseif ($hour < 17) {
            $greeting = __('Good afternoon', 'happy-place');
        } else {
            $greeting = __('Good evening', 'happy-place');
        }
        
        $user_name = $user_info['display_name'] ?? $user_info['first_name'] ?? __('Agent', 'happy-place');
        
        ?>
        <div class="hph-welcome-header">
            <div class="hph-welcome-content">
                <h1 class="hph-welcome-title">
                    <?php echo esc_html($greeting); ?>, <?php echo esc_html($user_name); ?>!
                </h1>
                <p class="hph-welcome-subtitle">
                    <?php 
                    printf(
                        esc_html__('Here\'s what\'s happening with your real estate business on %s', 'happy-place'),
                        date_i18n(get_option('date_format'), $current_time)
                    ); 
                    ?>
                </p>
            </div>
            
            <div class="hph-welcome-avatar">
                <?php 
                $avatar = get_avatar(get_current_user_id(), 60);
                if ($avatar) {
                    echo $avatar;
                } else {
                    echo '<i class="fas fa-user-circle fa-3x"></i>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render statistics widgets
     *
     * @param array $stats Statistics data
     */
    private function render_stats_widgets(array $stats): void {
        if (empty($stats)) {
            return;
        }
        
        $listings = $stats['listings'] ?? [];
        $leads = $stats['leads'] ?? [];
        $performance = $stats['performance'] ?? [];
        
        ?>
        <!-- Total Listings Card -->
        <div class="hph-stat-card hph-stat-card--primary">
            <div class="hph-stat-content">
                <div class="hph-stat-data">
                    <div class="hph-stat-label"><?php esc_html_e('Total Listings', 'happy-place'); ?></div>
                    <div class="hph-stat-value">
                        <?php echo esc_html($listings['total'] ?? 0); ?>
                    </div>
                    <div class="hph-stat-change hph-stat-change--positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php echo esc_html(($listings['active'] ?? 0) . ' Active'); ?>
                    </div>
                </div>
                <div class="hph-stat-icon">
                    <i class="fas fa-home"></i>
                </div>
            </div>
        </div>
        
        <!-- Sales This Month Card -->
        <div class="hph-stat-card hph-stat-card--success">
            <div class="hph-stat-content">
                <div class="hph-stat-data">
                    <div class="hph-stat-label"><?php esc_html_e('Sales This Month', 'happy-place'); ?></div>
                    <div class="hph-stat-value">
                        <?php echo esc_html($listings['sold_this_month'] ?? 0); ?>
                    </div>
                    <div class="hph-stat-change hph-stat-change--positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php esc_html_e('+12% vs last month', 'happy-place'); ?>
                    </div>
                </div>
                <div class="hph-stat-icon">
                    <i class="fas fa-handshake"></i>
                </div>
            </div>
        </div>
        
        <!-- New Leads Card -->
        <div class="hph-stat-card hph-stat-card--warning">
            <div class="hph-stat-content">
                <div class="hph-stat-data">
                    <div class="hph-stat-label"><?php esc_html_e('New Leads', 'happy-place'); ?></div>
                    <div class="hph-stat-value">
                        <?php echo esc_html($leads['total'] ?? 0); ?>
                    </div>
                    <div class="hph-stat-change hph-stat-change--positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php 
                        printf(
                            esc_html__('%d This Week', 'happy-place'),
                            $leads['new_this_week'] ?? 0
                        );
                        ?>
                    </div>
                </div>
                <div class="hph-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <!-- Performance Score Card -->
        <div class="hph-stat-card hph-stat-card--info">
            <div class="hph-stat-content">
                <div class="hph-stat-data">
                    <div class="hph-stat-label"><?php esc_html_e('Performance Score', 'happy-place'); ?></div>
                    <div class="hph-stat-value">
                        <?php echo esc_html(($performance['score'] ?? 85)); ?>
                        <span class="stat-unit">%</span>
                    </div>
                    <div class="hph-stat-change hph-stat-change--positive">
                        <i class="fas fa-arrow-up"></i>
                        <?php esc_html_e('+5% this month', 'happy-place'); ?>
                    </div>
                </div>
                <div class="hph-stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render quick actions
     *
     * @param array $actions Quick actions data
     */
    private function render_quick_actions(array $actions): void {
        if (empty($actions)) {
            return;
        }
        
        ?>
        <ul class="hph-list-modern">
            <?php foreach ($actions as $action_id => $action): ?>
                <li class="list-item list-item--clickable">
                    <a href="<?php echo esc_url($action['url']); ?>" 
                       class="item-link <?php echo esc_attr($action['class'] ?? ''); ?>"
                       <?php if (!empty($action['modal'])): ?>data-modal="<?php echo esc_attr($action['form_id']); ?>"<?php endif; ?>
                       <?php if (!empty($action['section_link'])): ?>data-section-link="true"<?php endif; ?>>
                        
                        <div class="item-icon item-icon--primary">
                            <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                        </div>
                        
                        <div class="item-content">
                            <div class="item-title"><?php echo esc_html($action['title']); ?></div>
                            <?php if (!empty($action['description'])): ?>
                            <div class="item-subtitle"><?php echo esc_html($action['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-actions">
                            <i class="fas fa-chevron-right" style="color: var(--hph-gray-400);"></i>
                        </div>
                        
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    /**
     * Render notifications
     *
     * @param array $notifications Notifications data
     */
    private function render_notifications(array $notifications): void {
        if (empty($notifications)) {
            ?>
            <div class="hph-content-card">
                <div class="hph-content-header">
                    <div class="content-title">
                        <div class="title-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3 class="title-text"><?php esc_html_e('Notifications', 'happy-place'); ?></h3>
                    </div>
                </div>
                
                <div class="hph-content-body">
                    <div class="hph-empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4 class="empty-title"><?php esc_html_e('All Caught Up!', 'happy-place'); ?></h4>
                        <p class="empty-description"><?php esc_html_e('You have no new notifications.', 'happy-place'); ?></p>
                    </div>
                </div>
            </div>
            <?php
            return;
        }
        
        ?>
        <div class="hph-content-card">
            <div class="hph-content-header">
                <div class="content-title">
                    <div class="title-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="title-text"><?php esc_html_e('Notifications', 'happy-place'); ?></h3>
                    <span class="title-badge"><?php echo count($notifications); ?></span>
                </div>
                <p class="content-subtitle">Important updates and reminders</p>
            </div>
            
            <div class="hph-content-body">
                <div class="hph-list-modern">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-item list-item--clickable">
                            
                            <div class="item-icon item-icon--<?php 
                                echo $notification['type'] === 'warning' ? 'warning' : 
                                     ($notification['type'] === 'error' ? 'warning' : 
                                     ($notification['type'] === 'success' ? 'success' : 'primary'));
                            ?>">
                                <?php
                                switch ($notification['type']) {
                                    case 'error':
                                        echo '<i class="fas fa-exclamation-circle"></i>';
                                        break;
                                    case 'warning':
                                        echo '<i class="fas fa-exclamation-triangle"></i>';
                                        break;
                                    case 'success':
                                        echo '<i class="fas fa-check-circle"></i>';
                                        break;
                                    default:
                                        echo '<i class="fas fa-info-circle"></i>';
                                        break;
                                }
                                ?>
                            </div>
                            
                            <div class="item-content">
                                <div class="item-title"><?php echo esc_html($notification['title']); ?></div>
                                <div class="item-subtitle"><?php echo esc_html($notification['message']); ?></div>
                                
                                <?php if (!empty($notification['action_url']) && !empty($notification['action_text'])): ?>
                                    <div class="item-meta">
                                        <a href="<?php echo esc_url($notification['action_url']); ?>" 
                                           class="hph-btn hph-btn--modern hph-btn--sm">
                                            <?php echo esc_html($notification['action_text']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-actions">
                                <?php if (!empty($notification['dismissible'])): ?>
                                <button type="button" class="hph-btn hph-btn--ghost hph-btn--sm" 
                                        data-notification="<?php echo esc_attr($notification['id'] ?? ''); ?>"
                                        title="<?php esc_attr_e('Dismiss', 'happy-place'); ?>">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render recent activity
     *
     * @param array $activity Recent activity data
     */
    private function render_recent_activity(array $activity): void {
        ?>
        <?php if (!empty($activity)): ?>
            <ul class="hph-list-modern">
                <?php foreach (array_slice($activity, 0, 5) as $item): ?>
                    <li class="list-item">
                        <div class="item-icon item-icon--<?php echo esc_attr($item['type'] ?? 'primary'); ?>">
                            <i class="fas <?php echo esc_attr($item['icon'] ?? 'fa-circle'); ?>"></i>
                        </div>
                        
                        <div class="item-content">
                            <div class="item-title">
                                <?php if (!empty($item['url'])): ?>
                                    <a href="<?php echo esc_url($item['url']); ?>" class="item-link">
                                        <?php echo esc_html($item['description']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo esc_html($item['description']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="item-meta">
                                <span class="meta-item">
                                    <?php echo esc_html(human_time_diff(strtotime($item['date']))); ?> ago
                                </span>
                                <?php if (!empty($item['category'])): ?>
                                <span class="meta-item">
                                    <?php echo esc_html($item['category']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($item['status'])): ?>
                        <div class="item-actions">
                            <span class="item-status item-status--<?php echo esc_attr($item['status']); ?>">
                                <?php echo esc_html(ucfirst($item['status'])); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="hph-empty-state">
                <div class="empty-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="empty-title"><?php esc_html_e('No Recent Activity', 'happy-place'); ?></div>
                <div class="empty-description">
                    <?php esc_html_e('Your recent activity will appear here once you start using the dashboard.', 'happy-place'); ?>
                </div>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Render performance summary
     *
     * @param array $performance Performance data
     */
    private function render_performance_summary(array $performance): void {
        if (empty($performance)) {
            return;
        }
        
        ?>
        <div class="hph-content-card">
            <div class="hph-content-header">
                <div class="content-title">
                    <div class="title-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="title-text"><?php esc_html_e('Performance', 'happy-place'); ?></h3>
                </div>
                <p class="content-subtitle">Your monthly performance metrics</p>
            </div>
            
            <div class="hph-content-body">
                <div class="hph-list-modern">
                    
                    <div class="list-item">
                        <div class="item-icon item-icon--primary">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title">$<?php echo esc_html(number_format($performance['this_month']['sales_volume'] ?? 0)); ?></div>
                            <div class="item-subtitle"><?php esc_html_e('Sales This Month', 'happy-place'); ?></div>
                        </div>
                        <div class="item-actions">
                            <span class="hph-stat-change hph-stat-change--positive">
                                <i class="fas fa-arrow-up"></i> 15%
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-item">
                        <div class="item-icon item-icon--success">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title"><?php echo esc_html($performance['this_month']['deals_closed'] ?? 0); ?> Deals</div>
                            <div class="item-subtitle"><?php esc_html_e('Closed This Month', 'happy-place'); ?></div>
                        </div>
                        <div class="item-actions">
                            <span class="hph-stat-change hph-stat-change--positive">
                                <i class="fas fa-arrow-up"></i> 3
                            </span>
                        </div>
                    </div>
                    
                    <div class="list-item">
                        <div class="item-icon item-icon--warning">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="item-content">
                            <div class="item-title">$<?php echo esc_html(number_format($performance['this_month']['commission'] ?? 0)); ?></div>
                            <div class="item-subtitle"><?php esc_html_e('Commission Earned', 'happy-place'); ?></div>
                        </div>
                        <div class="item-actions">
                            <span class="hph-stat-change hph-stat-change--positive">
                                <i class="fas fa-arrow-up"></i> 8%
                            </span>
                        </div>
                    </div>
                    
                </div>
            </div>
            
            <div class="hph-content-footer">
                <div class="footer-actions">
                    <a href="<?php echo esc_url($this->get_dashboard_url(['section' => 'analytics'])); ?>" class="hph-btn hph-btn--modern hph-btn--ghost">
                        <?php esc_html_e('View Detailed Analytics', 'happy-place'); ?>
                        <i class="fas fa-chart-bar"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get user information
     *
     * @param int $user_id User ID
     * @return array User info
     */
    private function get_user_info(int $user_id): array {
        $user = get_userdata($user_id);
        if (!$user) {
            return [];
        }
        
        return [
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user_id, 'first_name', true),
            'last_name' => get_user_meta($user_id, 'last_name', true),
            'email' => $user->user_email,
            'roles' => $user->roles,
            'agent_id' => get_user_meta($user_id, 'agent_post_id', true)
        ];
    }
    
    /**
     * Get dashboard configuration
     *
     * @return array Dashboard config
     */
    private function get_dashboard_config(): array {
        return [
            'refresh_interval' => $this->config['cache_duration'] ?? 15 * MINUTE_IN_SECONDS,
            'ajax_enabled' => $this->config['ajax_enabled'] ?? true,
            'mobile_breakpoint' => 768
        ];
    }

    /**
     * Handle custom AJAX actions
     *
     * @param string $action Action name
     * @param array $data Request data
     */
    protected function handle_custom_ajax_action(string $action, array $data): void {
        switch ($action) {
            case 'dismiss_notification':
                $this->handle_dismiss_notification($data);
                break;
                
            case 'get_stats':
                $this->handle_get_stats($data);
                break;
                
            default:
                parent::handle_custom_ajax_action($action, $data);
                break;
        }
    }
    
    /**
     * Handle dismiss notification
     *
     * @param array $data Request data
     */
    private function handle_dismiss_notification(array $data): void {
        $notification_id = sanitize_text_field($data['notification_id'] ?? '');
        
        if (empty($notification_id)) {
            wp_send_json_error(['message' => __('Notification ID required', 'happy-place')]);
        }
        
        // Store dismissed notification
        $user_id = get_current_user_id();
        $dismissed = get_user_meta($user_id, 'hph_dismissed_notifications', true);
        if (!is_array($dismissed)) {
            $dismissed = [];
        }
        
        $dismissed[] = $notification_id;
        update_user_meta($user_id, 'hph_dismissed_notifications', $dismissed);
        
        wp_send_json_success(['message' => __('Notification dismissed', 'happy-place')]);
    }
    
    /**
     * Handle get stats request
     *
     * @param array $data Request data
     */
    private function handle_get_stats(array $data): void {
        $user_id = get_current_user_id();
        $stats = $this->get_dashboard_stats($user_id);
        
        wp_send_json_success(['stats' => $stats]);
    }

    // Add more helper methods as needed for specific data calculations...
    // These would include methods like:
    // - count_sold_listings_this_month()
    // - get_average_listing_price()
    // - calculate_lead_conversion_rate()
    // - get_commission_this_month()
    // etc.
}