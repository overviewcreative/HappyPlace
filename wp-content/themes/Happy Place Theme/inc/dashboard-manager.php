<?php

/**
 * Dashboard Manager
 * 
 * Handles agent dashboard functionality including rewrite rules,
 * template loading, and dashboard-specific features.
 * 
 * @package HappyPlace
 * @since 1.0.0
 */

class HPH_Dashboard_Manager
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_dashboard_access']);
        add_filter('template_include', [$this, 'load_dashboard_template']);
        add_action('wp', [$this, 'maybe_create_dashboard_page']);
    }

    /**
     * Add rewrite rules for dashboard URLs
     */
    public function add_rewrite_rules(): void
    {
        add_rewrite_rule(
            '^agent-dashboard/?$',
            'index.php?dashboard=1',
            'top'
        );

        add_rewrite_rule(
            '^agent-dashboard/([^/]+)/?$',
            'index.php?dashboard=1&section=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^agent-dashboard/([^/]+)/([^/]+)/?$',
            'index.php?dashboard=1&section=$matches[1]&subsection=$matches[2]',
            'top'
        );
    }

    /**
     * Add custom query variables
     */
    public function add_query_vars(array $vars): array
    {
        $vars[] = 'dashboard';
        $vars[] = 'section';
        $vars[] = 'subsection';
        return $vars;
    }

    /**
     * Handle dashboard access and permissions
     */
    public function handle_dashboard_access(): void
    {
        if (!get_query_var('dashboard')) {
            return;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(home_url('agent-dashboard'));
            wp_redirect($login_url);
            exit;
        }

        // Check user permissions
        if (!$this->user_can_access_dashboard()) {
            wp_redirect(home_url());
            exit;
        }

        // Set up dashboard environment
        $this->setup_dashboard_environment();
    }

    /**
     * Check if current user can access dashboard
     */
    private function user_can_access_dashboard(): bool
    {
        return current_user_can('agent') ||
            current_user_can('administrator') ||
            current_user_can('editor');
    }

    /**
     * Setup dashboard-specific environment
     */
    private function setup_dashboard_environment(): void
    {
        // Add dashboard body class
        add_filter('body_class', function ($classes) {
            $classes[] = 'hph-dashboard';
            $classes[] = 'hph-dashboard-' . $this->get_current_section();
            return $classes;
        });

        // Remove admin bar for cleaner dashboard
        add_filter('show_admin_bar', '__return_false');
    }

    /**
     * Load dashboard template
     */
    public function load_dashboard_template(string $template): string
    {
        if (!get_query_var('dashboard')) {
            return $template;
        }

        $dashboard_template = locate_template([
            'templates/dashboard/agent-dashboard.php',
            'templates/dashboard.php',
            'page-dashboard.php'
        ]);

        return $dashboard_template ?: $template;
    }

    /**
     * Create dashboard page if it doesn't exist
     */
    public function maybe_create_dashboard_page(): void
    {
        if ($this->get_dashboard_page_id()) {
            return;
        }

        $page_id = wp_insert_post([
            'post_title' => __('Agent Dashboard', 'happy-place'),
            'post_name' => 'agent-dashboard',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_content' => '[hph_dashboard]',
            'meta_input' => [
                '_wp_page_template' => 'templates/dashboard/agent-dashboard.php'
            ]
        ]);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('hph_dashboard_page_id', $page_id);
            flush_rewrite_rules();
        }
    }

    /**
     * Get dashboard page ID
     */
    public function get_dashboard_page_id(): ?int
    {
        $page_id = get_option('hph_dashboard_page_id');

        if (!$page_id) {
            $page = get_page_by_path('agent-dashboard');
            if ($page) {
                $page_id = $page->ID;
                update_option('hph_dashboard_page_id', $page_id);
            }
        }

        return $page_id ? (int) $page_id : null;
    }

    /**
     * Get current dashboard section
     */
    public function get_current_section(): string
    {
        return get_query_var('section', 'overview');
    }

    /**
     * Get current dashboard subsection
     */
    public function get_current_subsection(): string
    {
        return get_query_var('subsection', '');
    }

    /**
     * Get dashboard navigation items
     */
    public function get_dashboard_navigation(): array
    {
        $sections = [
            'overview' => [
                'title' => __('Overview', 'happy-place'),
                'icon' => 'fas fa-tachometer-alt',
                'url' => $this->get_dashboard_url('overview')
            ],
            'listings' => [
                'title' => __('My Listings', 'happy-place'),
                'icon' => 'fas fa-home',
                'url' => $this->get_dashboard_url('listings'),
                'count' => $this->get_agent_listing_count()
            ],
            'open-houses' => [
                'title' => __('Open Houses', 'happy-place'),
                'icon' => 'fas fa-calendar-alt',
                'url' => $this->get_dashboard_url('open-houses'),
                'count' => $this->get_agent_open_house_count()
            ],
            'inquiries' => [
                'title' => __('Inquiries', 'happy-place'),
                'icon' => 'fas fa-envelope',
                'url' => $this->get_dashboard_url('inquiries'),
                'count' => $this->get_agent_inquiry_count()
            ],
            'clients' => [
                'title' => __('Clients', 'happy-place'),
                'icon' => 'fas fa-users',
                'url' => $this->get_dashboard_url('clients')
            ],
            'analytics' => [
                'title' => __('Analytics', 'happy-place'),
                'icon' => 'fas fa-chart-bar',
                'url' => $this->get_dashboard_url('analytics')
            ],
            'profile' => [
                'title' => __('Profile', 'happy-place'),
                'icon' => 'fas fa-user',
                'url' => $this->get_dashboard_url('profile')
            ]
        ];

        return apply_filters('hph_dashboard_navigation', $sections);
    }

    /**
     * Get dashboard URL for a section
     */
    public function get_dashboard_url(string $section = '', string $subsection = ''): string
    {
        $url = home_url('agent-dashboard');

        if ($section && $section !== 'overview') {
            $url .= '/' . $section;
        }

        if ($subsection) {
            $url .= '/' . $subsection;
        }

        return $url;
    }

    /**
     * Get agent statistics
     */
    public function get_agent_stats(): array
    {
        $agent_id = get_current_user_id();

        return [
            'listings' => [
                'active' => $this->get_agent_listing_count('active'),
                'pending' => $this->get_agent_listing_count('pending'),
                'sold' => $this->get_agent_listing_count('sold'),
                'total_views' => $this->get_agent_total_views()
            ],
            'performance' => [
                'avg_days_on_market' => $this->get_avg_days_on_market($agent_id),
                'avg_sale_price' => $this->get_avg_sale_price($agent_id),
                'total_volume' => $this->get_total_sales_volume($agent_id)
            ],
            'engagement' => [
                'inquiries' => $this->get_agent_inquiry_count(),
                'open_houses' => $this->get_agent_open_house_count(),
                'profile_views' => $this->get_agent_profile_views()
            ]
        ];
    }

    /**
     * Get chart data for dashboard analytics
     */
    public function get_chart_data(string $period = '30d'): array
    {
        $agent_id = get_current_user_id();
        $days = $this->get_days_from_period($period);

        return [
            'labels' => $this->get_date_labels($days),
            'views' => $this->get_daily_views_data($agent_id, $days),
            'inquiries' => $this->get_daily_inquiries_data($agent_id, $days),
            'showings' => $this->get_daily_showings_data($agent_id, $days)
        ];
    }

    /**
     * Helper methods for statistics
     */

    private function get_agent_listing_count(string $status = ''): int
    {
        $args = [
            'post_type' => 'listing',
            'author' => get_current_user_id(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];

        if ($status) {
            $args['meta_query'] = [
                [
                    'key' => 'status',
                    'value' => $status
                ]
            ];
        }

        return count(get_posts($args));
    }

    private function get_agent_open_house_count(): int
    {
        $args = [
            'post_type' => 'open-house',
            'author' => get_current_user_id(),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>='
                ]
            ]
        ];

        return count(get_posts($args));
    }

    private function get_agent_inquiry_count(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'hph_inquiries';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE agent_id = %d 
             AND inquiry_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            get_current_user_id()
        ));
    }

    private function get_agent_total_views(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'hph_listing_views';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} v
             JOIN {$wpdb->posts} p ON v.listing_id = p.ID
             WHERE p.post_author = %d
             AND v.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            get_current_user_id()
        ));
    }

    private function get_avg_days_on_market(int $agent_id): int
    {
        $sold_listings = get_posts([
            'post_type' => 'listing',
            'author' => $agent_id,
            'meta_query' => [
                [
                    'key' => 'status',
                    'value' => 'sold'
                ]
            ],
            'posts_per_page' => -1
        ]);

        if (empty($sold_listings)) return 0;

        $total_days = 0;
        $count = 0;

        foreach ($sold_listings as $listing) {
            $list_date = get_field('date_listed', $listing->ID);
            $sold_date = get_field('date_sold', $listing->ID);

            if ($list_date && $sold_date) {
                $days = (strtotime($sold_date) - strtotime($list_date)) / (24 * 60 * 60);
                $total_days += $days;
                $count++;
            }
        }

        return $count > 0 ? round($total_days / $count) : 0;
    }

    private function get_avg_sale_price(int $agent_id): float
    {
        global $wpdb;

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(CAST(pm.meta_value AS DECIMAL(12,2)))
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_author = %d
             AND p.post_type = 'listing'
             AND pm.meta_key = 'sale_price'
             AND pm.meta_value > 0",
            $agent_id
        ));
    }

    private function get_total_sales_volume(int $agent_id): float
    {
        global $wpdb;

        return (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(12,2)))
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_author = %d
             AND p.post_type = 'listing'
             AND pm.meta_key = 'sale_price'
             AND pm.meta_value > 0",
            $agent_id
        ));
    }

    private function get_agent_profile_views(): int
    {
        global $wpdb;

        $table = $wpdb->prefix . 'hph_profile_views';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table}
             WHERE agent_id = %d
             AND view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            get_current_user_id()
        ));
    }

    private function get_days_from_period(string $period): int
    {
        $periods = [
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365
        ];

        return $periods[$period] ?? 30;
    }

    private function get_date_labels(int $days): array
    {
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = date('M j', strtotime("-{$i} days"));
        }
        return $labels;
    }

    private function get_daily_views_data(int $agent_id, int $days): array
    {
        global $wpdb;

        $data = array_fill(0, $days, 0);
        $table = $wpdb->prefix . 'hph_listing_views';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return $data;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(v.view_date) as date, COUNT(*) as count
             FROM {$table} v
             JOIN {$wpdb->posts} p ON v.listing_id = p.ID
             WHERE p.post_author = %d
             AND v.view_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(v.view_date)
             ORDER BY date ASC",
            $agent_id,
            $days
        ));

        foreach ($results as $result) {
            $day_index = $days - 1 - floor((strtotime('today') - strtotime($result->date)) / (24 * 60 * 60));
            if ($day_index >= 0 && $day_index < $days) {
                $data[$day_index] = intval($result->count);
            }
        }

        return $data;
    }

    private function get_daily_inquiries_data(int $agent_id, int $days): array
    {
        global $wpdb;

        $data = array_fill(0, $days, 0);
        $table = $wpdb->prefix . 'hph_inquiries';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") !== $table) {
            return $data;
        }

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(inquiry_date) as date, COUNT(*) as count
             FROM {$table}
             WHERE agent_id = %d
             AND inquiry_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(inquiry_date)
             ORDER BY date ASC",
            $agent_id,
            $days
        ));

        foreach ($results as $result) {
            $day_index = $days - 1 - floor((strtotime('today') - strtotime($result->date)) / (24 * 60 * 60));
            if ($day_index >= 0 && $day_index < $days) {
                $data[$day_index] = intval($result->count);
            }
        }

        return $data;
    }

    private function get_daily_showings_data(int $agent_id, int $days): array
    {
        // Placeholder - implement based on your showing tracking system
        return array_fill(0, $days, 0);
    }
}

// Initialize
HPH_Dashboard_Manager::instance();

/**
 * Get agent statistics
 * 
 * @param int|null $agent_id Optional agent ID, defaults to current user
 * @return array Statistics data
 */
function hph_get_agent_stats(?int $agent_id = null): array
{
    if (!$agent_id) {
        $agent_id = get_current_user_id();
    }

    // Get listing counts
    $args_active = [
        'post_type' => 'listing',
        'author' => $agent_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'status',
                'value' => 'active',
                'compare' => '='
            ]
        ]
    ];

    $args_sold = [
        'post_type' => 'listing',
        'author' => $agent_id,
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'status',
                'value' => 'sold',
                'compare' => '='
            ]
        ]
    ];

    $active_listings = get_posts($args_active);
    $sold_listings = get_posts($args_sold);

    // Calculate performance metrics
    $avg_days_on_market = 0;
    $total_sale_price = 0;
    $total_volume = 0;

    if (!empty($sold_listings)) {
        $total_days = 0;
        $sale_count = 0;

        foreach ($sold_listings as $listing_id) {
            $list_date = get_field('date_listed', $listing_id);
            $sold_date = get_field('date_sold', $listing_id);
            $sale_price = get_field('sale_price', $listing_id);

            if ($list_date && $sold_date) {
                $days = (strtotime($sold_date) - strtotime($list_date)) / (24 * 60 * 60);
                $total_days += $days;
                $sale_count++;
            }

            if ($sale_price) {
                $total_sale_price += floatval($sale_price);
                $total_volume += floatval($sale_price);
            }
        }

        if ($sale_count > 0) {
            $avg_days_on_market = round($total_days / $sale_count);
        }
    }

    // Get views and inquiries from custom tables
    global $wpdb;
    $views = 0;
    $inquiries = 0;

    $views_table = $wpdb->prefix . 'hph_listing_views';
    if ($wpdb->get_var("SHOW TABLES LIKE '$views_table'") === $views_table) {
        $views = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $views_table v
             JOIN {$wpdb->posts} p ON v.listing_id = p.ID
             WHERE p.post_author = %d
             AND v.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $agent_id
        ));
    }

    $inquiries_table = $wpdb->prefix . 'hph_inquiries';
    if ($wpdb->get_var("SHOW TABLES LIKE '$inquiries_table'") === $inquiries_table) {
        $inquiries = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $inquiries_table
             WHERE agent_id = %d
             AND inquiry_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            $agent_id
        ));
    }

    // Get chart data for the last 30 days
    $chart_data = hph_get_chart_data('30d');

    return [
        'listings' => [
            'active' => count($active_listings),
            'sold' => count($sold_listings),
            'views' => $views
        ],
        'engagement' => [
            'inquiries' => $inquiries
        ],
        'performance' => [
            'averageDaysOnMarket' => $avg_days_on_market,
            'averageSalePrice' => $total_sale_price > 0 ? $total_sale_price / count($sold_listings) : 0,
            'totalVolume' => $total_volume
        ],
        'chart' => $chart_data
    ];
}

/**
 * Get chart data for the dashboard
 * 
 * @param string $period The time period to get data for (7d, 30d, 90d, 1y)
 * @return array Chart data with labels and datasets
 */
function hph_get_chart_data(string $period = '30d'): array
{
    // Convert period to number of days
    $days = match ($period) {
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        '1y' => 365,
        default => 30
    };

    // Generate date labels
    $labels = [];
    $views_data = array_fill(0, $days, 0);
    $inquiries_data = array_fill(0, $days, 0);

    // Fill the date labels array
    for ($i = $days - 1; $i >= 0; $i--) {
        $labels[] = date('M j', strtotime("-$i days"));
    }

    // Get views data
    global $wpdb;
    $views_table = $wpdb->prefix . 'hph_listing_views';
    if ($wpdb->get_var("SHOW TABLES LIKE '$views_table'") === $views_table) {
        $views_results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(view_date) as date, COUNT(*) as count
             FROM $views_table v
             JOIN {$wpdb->posts} p ON v.listing_id = p.ID
             WHERE p.post_author = %d
             AND view_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(view_date)",
            get_current_user_id(),
            $days
        ));

        foreach ($views_results as $result) {
            $day_index = $days - 1 - floor((strtotime('today') - strtotime($result->date)) / (24 * 60 * 60));
            if ($day_index >= 0 && $day_index < $days) {
                $views_data[$day_index] = intval($result->count);
            }
        }
    }

    // Get inquiries data
    $inquiries_table = $wpdb->prefix . 'hph_inquiries';
    if ($wpdb->get_var("SHOW TABLES LIKE '$inquiries_table'") === $inquiries_table) {
        $inquiries_results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(inquiry_date) as date, COUNT(*) as count
             FROM $inquiries_table
             WHERE agent_id = %d
             AND inquiry_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(inquiry_date)",
            get_current_user_id(),
            $days
        ));

        foreach ($inquiries_results as $result) {
            $day_index = $days - 1 - floor((strtotime('today') - strtotime($result->date)) / (24 * 60 * 60));
            if ($day_index >= 0 && $day_index < $days) {
                $inquiries_data[$day_index] = intval($result->count);
            }
        }
    }

    return [
        'labels' => $labels,
        'views' => $views_data,
        'inquiries' => $inquiries_data
    ];
}
