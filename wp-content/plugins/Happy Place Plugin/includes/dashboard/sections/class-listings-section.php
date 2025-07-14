<?php

/**
 * Listings Section Handler
 * 
 * Handles all data operations and business logic for the listings dashboard section.
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
 * Listings Section Class
 * 
 * Manages:
 * - Listing data retrieval and manipulation
 * - Status changes and bulk operations
 * - Search and filtering
 * - Statistics and analytics for listings
 */
class Listings_Section
{
    /**
     * @var Listings_Section|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array Allowed listing statuses
     */
    private array $allowed_statuses = ['active', 'pending', 'sold', 'withdrawn', 'draft'];

    /**
     * @var array Default listings per page
     */
    private int $default_per_page = 10;

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
        add_action('hph_listings_status_changed', [$this, 'handle_status_change'], 10, 3);
        add_action('hph_listing_deleted', [$this, 'handle_listing_deletion'], 10, 2);
    }

    /**
     * Get listings section data
     */
    public function get_section_data(array $default, string $section): array
    {
        if ($section !== 'listings') {
            return $default;
        }

        $user_id = get_current_user_id();
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';

        return [
            'listings' => $this->get_user_listings($user_id, $page, $search, $status),
            'pagination' => $this->get_pagination_data($user_id, $page, $search, $status),
            'stats' => $this->get_listings_stats($user_id),
            'filters' => $this->get_filter_options(),
            'bulk_actions' => $this->get_bulk_actions(),
            'can_add_new' => current_user_can('edit_posts'),
            'search_term' => $search,
            'current_status' => $status,
            'statuses' => $this->get_status_counts($user_id)
        ];
    }

    /**
     * Get user's listings with filtering
     */
    public function get_user_listings(int $user_id, int $page = 1, string $search = '', string $status = 'all'): array
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => $this->default_per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => []
        ];

        // Add status filter
        if ($status !== 'all' && in_array($status, $this->allowed_statuses)) {
            $args['meta_query'][] = [
                'key' => '_listing_status',
                'value' => $status,
                'compare' => '='
            ];
        }

        // Add search filter
        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new \WP_Query($args);
        $listings = [];

        foreach ($query->posts as $post) {
            $listings[] = $this->format_listing_data($post);
        }

        return $listings;
    }

    /**
     * Get pagination data
     */
    public function get_pagination_data(int $user_id, int $page, string $search, string $status): array
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => $this->default_per_page,
            'fields' => 'ids'
        ];

        if ($status !== 'all' && in_array($status, $this->allowed_statuses)) {
            $args['meta_query'] = [[
                'key' => '_listing_status',
                'value' => $status,
                'compare' => '='
            ]];
        }

        if (!empty($search)) {
            $args['s'] = $search;
        }

        $query = new \WP_Query($args);
        $total_posts = $query->found_posts;
        $total_pages = ceil($total_posts / $this->default_per_page);

        return [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_items' => $total_posts,
            'per_page' => $this->default_per_page,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ];
    }

    /**
     * Get listings statistics
     */
    public function get_listings_stats(int $user_id): array
    {
        $stats = [];
        
        foreach ($this->allowed_statuses as $status) {
            $stats[$status] = $this->count_listings_by_status($user_id, $status);
        }

        $stats['total'] = array_sum($stats);
        $stats['active_percentage'] = $stats['total'] > 0 ? round(($stats['active'] / $stats['total']) * 100, 1) : 0;
        $stats['pending_percentage'] = $stats['total'] > 0 ? round(($stats['pending'] / $stats['total']) * 100, 1) : 0;

        // Additional metrics
        $stats['this_month'] = $this->count_listings_this_month($user_id);
        $stats['last_30_days'] = $this->count_listings_last_30_days($user_id);
        $stats['average_price'] = $this->get_average_listing_price($user_id);
        $stats['total_value'] = $this->get_total_listings_value($user_id);

        return $stats;
    }

    /**
     * Get filter options
     */
    public function get_filter_options(): array
    {
        return [
            'property_types' => $this->get_property_types(),
            'price_ranges' => $this->get_price_ranges(),
            'locations' => $this->get_user_locations(get_current_user_id())
        ];
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions(): array
    {
        $actions = [
            'activate' => __('Mark as Active', 'happy-place'),
            'deactivate' => __('Mark as Pending', 'happy-place'),
            'export' => __('Export Selected', 'happy-place')
        ];

        if (current_user_can('delete_posts')) {
            $actions['delete'] = __('Delete Selected', 'happy-place');
        }

        return apply_filters('hph_listings_bulk_actions', $actions);
    }

    /**
     * Get status counts for tabs
     */
    public function get_status_counts(int $user_id): array
    {
        $counts = ['all' => 0];
        
        foreach ($this->allowed_statuses as $status) {
            $count = $this->count_listings_by_status($user_id, $status);
            $counts[$status] = $count;
            $counts['all'] += $count;
        }

        return $counts;
    }

    /**
     * Format listing data for display
     */
    private function format_listing_data(\WP_Post $post): array
    {
        $meta = get_post_meta($post->ID);
        
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'address' => $meta['_listing_address'][0] ?? '',
            'city' => $meta['_listing_city'][0] ?? '',
            'state' => $meta['_listing_state'][0] ?? '',
            'zip_code' => $meta['_listing_zip_code'][0] ?? '',
            'price' => floatval($meta['_listing_price'][0] ?? 0),
            'formatted_price' => $this->format_price(floatval($meta['_listing_price'][0] ?? 0)),
            'property_type' => $meta['_listing_property_type'][0] ?? '',
            'bedrooms' => intval($meta['_listing_bedrooms'][0] ?? 0),
            'bathrooms' => floatval($meta['_listing_bathrooms'][0] ?? 0),
            'square_feet' => intval($meta['_listing_square_feet'][0] ?? 0),
            'status' => $meta['_listing_status'][0] ?? 'draft',
            'status_label' => $this->get_status_label($meta['_listing_status'][0] ?? 'draft'),
            'date_created' => $post->post_date,
            'date_modified' => $post->post_modified,
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'edit_url' => $this->get_edit_url($post->ID),
            'view_url' => get_permalink($post->ID),
            'can_edit' => current_user_can('edit_post', $post->ID),
            'can_delete' => current_user_can('delete_post', $post->ID),
            'gallery_count' => $this->count_listing_images($post->ID),
            'views_count' => intval($meta['_listing_views'][0] ?? 0),
            'inquiries_count' => $this->count_listing_inquiries($post->ID)
        ];
    }

    /**
     * Count listings by status
     */
    private function count_listings_by_status(int $user_id, string $status): int
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => '_listing_status',
                'value' => $status,
                'compare' => '='
            ]]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Count listings created this month
     */
    private function count_listings_this_month(int $user_id): int
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [[
                'year' => date('Y'),
                'month' => date('n')
            ]]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Count listings in last 30 days
     */
    private function count_listings_last_30_days(int $user_id): int
    {
        $args = [
            'post_type' => 'hph_listing',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
            'date_query' => [[
                'after' => '30 days ago'
            ]]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get average listing price
     */
    private function get_average_listing_price(int $user_id): float
    {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'hph_listing'
            AND p.post_author = %d
            AND pm.meta_key = '_listing_price'
            AND pm.meta_value != ''
        ", $user_id));

        return floatval($result ?? 0);
    }

    /**
     * Get total listings value
     */
    private function get_total_listings_value(int $user_id): float
    {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'hph_listing'
            AND p.post_author = %d
            AND pm.meta_key = '_listing_price'
            AND pm.meta_value != ''
            AND pm2.meta_key = '_listing_status'
            AND pm2.meta_value = 'active'
        ", $user_id));

        return floatval($result ?? 0);
    }

    /**
     * Format price for display
     */
    private function format_price(float $price): string
    {
        return '$' . number_format($price, 0);
    }

    /**
     * Get status label
     */
    private function get_status_label(string $status): string
    {
        $labels = [
            'active' => __('Active', 'happy-place'),
            'pending' => __('Pending', 'happy-place'),
            'sold' => __('Sold', 'happy-place'),
            'withdrawn' => __('Withdrawn', 'happy-place'),
            'draft' => __('Draft', 'happy-place')
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Get edit URL for listing
     */
    private function get_edit_url(int $listing_id): string
    {
        $dashboard_url = $this->get_dashboard_url();
        return add_query_arg([
            'section' => 'listings',
            'action' => 'edit-listing',
            'listing_id' => $listing_id
        ], $dashboard_url);
    }

    /**
     * Get dashboard URL
     */
    private function get_dashboard_url(): string
    {
        $dashboard_page = get_page_by_path('agent-dashboard');
        if ($dashboard_page) {
            return get_permalink($dashboard_page->ID);
        }
        return home_url('/agent-dashboard/');
    }

    /**
     * Get property types for filters
     */
    private function get_property_types(): array
    {
        return apply_filters('hph_property_types', [
            'single-family' => __('Single Family', 'happy-place'),
            'condo' => __('Condo', 'happy-place'),
            'townhouse' => __('Townhouse', 'happy-place'),
            'multi-family' => __('Multi Family', 'happy-place'),
            'land' => __('Land', 'happy-place'),
            'commercial' => __('Commercial', 'happy-place')
        ]);
    }

    /**
     * Get price ranges for filters
     */
    private function get_price_ranges(): array
    {
        return [
            '0-100000' => __('Under $100K', 'happy-place'),
            '100000-250000' => __('$100K - $250K', 'happy-place'),
            '250000-500000' => __('$250K - $500K', 'happy-place'),
            '500000-750000' => __('$500K - $750K', 'happy-place'),
            '750000-1000000' => __('$750K - $1M', 'happy-place'),
            '1000000+' => __('$1M+', 'happy-place')
        ];
    }

    /**
     * Get user's listing locations
     */
    private function get_user_locations(int $user_id): array
    {
        global $wpdb;
        
        $locations = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT CONCAT(pm1.meta_value, ', ', pm2.meta_value) as location
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_listing_city'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_listing_state'
            WHERE p.post_type = 'hph_listing'
            AND p.post_author = %d
            AND pm1.meta_value != ''
            AND pm2.meta_value != ''
            ORDER BY location
        ", $user_id));

        return array_column($locations, 'location');
    }

    /**
     * Count listing images
     */
    private function count_listing_images(int $listing_id): int
    {
        $gallery = get_post_meta($listing_id, '_listing_gallery', true);
        return is_array($gallery) ? count($gallery) : 0;
    }

    /**
     * Count listing inquiries
     */
    private function count_listing_inquiries(int $listing_id): int
    {
        $args = [
            'post_type' => 'hph_lead',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [[
                'key' => '_lead_listing_id',
                'value' => $listing_id,
                'compare' => '='
            ]]
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Handle status change
     */
    public function handle_status_change(int $listing_id, string $old_status, string $new_status): void
    {
        // Log the status change
        do_action('hph_log_listing_status_change', [
            'listing_id' => $listing_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('timestamp')
        ]);

        // Send notifications if needed
        if ($new_status === 'sold') {
            do_action('hph_listing_sold', $listing_id);
        }
    }

    /**
     * Handle listing deletion
     */
    public function handle_listing_deletion(int $listing_id, int $user_id): void
    {
        // Clean up related data
        do_action('hph_cleanup_listing_data', $listing_id);
        
        // Log the deletion
        do_action('hph_log_listing_deletion', [
            'listing_id' => $listing_id,
            'user_id' => $user_id,
            'timestamp' => current_time('timestamp')
        ]);
    }
}

// Initialize
Listings_Section::instance();