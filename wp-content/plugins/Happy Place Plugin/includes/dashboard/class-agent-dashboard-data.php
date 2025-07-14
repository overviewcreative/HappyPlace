<?php

/**
 * Agent Dashboard Data Handler
 * Handles all data operations for the agent dashboard
 */

namespace HappyPlace\Dashboard;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Dashboard_Data
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('init', [$this, 'register_dashboard_endpoints']);
    }

    /**
     * Register REST API endpoints
     */
    public function register_rest_routes(): void
    {
        register_rest_route('happy-place/v1', '/dashboard/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'get_dashboard_stats'],
            'permission_callback' => [$this, 'check_dashboard_access'],
        ]);

        register_rest_route('happy-place/v1', '/dashboard/listings', [
            'methods' => 'GET',
            'callback' => [$this, 'get_agent_listings'],
            'permission_callback' => [$this, 'check_dashboard_access'],
        ]);
    }

    /**
     * Register dynamic endpoints for the theme
     */
    public function register_dashboard_endpoints(): void
    {
        add_action('wp_ajax_hph_get_dashboard_data', [$this, 'ajax_get_dashboard_data']);
    }

    /**
     * Check if user has dashboard access
     */
    public function check_dashboard_access(): bool
    {
        return current_user_can('agent') || current_user_can('administrator');
    }

    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats(): array
    {
        $user_id = get_current_user_id();

        return [
            'active_listings' => $this->count_agent_listings($user_id, 'publish'),
            'pending_listings' => $this->count_agent_listings($user_id, 'pending'),
            'total_leads' => $this->count_agent_leads($user_id),
            'views_this_month' => $this->get_listing_views_this_month($user_id),
        ];
    }

    /**
     * Get agent's listings
     */
    public function get_agent_listings($request): array
    {
        $user_id = get_current_user_id();
        $status = $request->get_param('status') ?: 'publish';

        $args = [
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => $status,
            'posts_per_page' => -1,
        ];

        $listings = get_posts($args);
        return array_map([$this, 'format_listing_data'], $listings);
    }

    /**
     * Format listing data for API response
     */
    private function format_listing_data($post): array
    {
        return [
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $post->post_status,
            'price' => get_field('price', $post->ID),
            'address' => get_field('address', $post->ID),
            'bedrooms' => get_field('bedrooms', $post->ID),
            'bathrooms' => get_field('bathrooms', $post->ID),
            'square_feet' => get_field('square_feet', $post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'medium'),
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
            'view_url' => get_permalink($post->ID),
        ];
    }

    /**
     * Count agent's listings by status
     */
    private function count_agent_listings($user_id, $status): int
    {
        $args = [
            'post_type' => 'listing',
            'author' => $user_id,
            'post_status' => $status,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ];

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Count agent's leads
     */
    private function count_agent_leads($user_id): int
    {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}hph_leads WHERE agent_id = %d",
            $user_id
        ));

        return (int) $count;
    }

    /**
     * Get listing views for this month
     */
    private function get_listing_views_this_month($user_id): int
    {
        global $wpdb;

        $first_of_month = date('Y-m-01 00:00:00');

        $views = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(view_count) FROM {$wpdb->prefix}hph_listing_views 
            WHERE agent_id = %d AND view_date >= %s",
            $user_id,
            $first_of_month
        ));

        return (int) $views;
    }

    /**
     * AJAX handler for dashboard data
     */
    public function ajax_get_dashboard_data(): void
    {
        if (!check_ajax_referer('hph_dashboard_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }

        if (!$this->check_dashboard_access()) {
            wp_send_json_error('Unauthorized');
        }

        $section = sanitize_text_field($_POST['section'] ?? 'overview');
        $data = [];

        switch ($section) {
            case 'overview':
                $data = $this->get_dashboard_stats();
                break;
            case 'listings':
                $data = $this->get_agent_listings((object) ['status' => 'any']);
                break;
                // Add more sections as needed
        }

        wp_send_json_success($data);
    }
}
