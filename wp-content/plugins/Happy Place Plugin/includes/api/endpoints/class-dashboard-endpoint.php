<?php

/**
 * Dashboard Endpoint
 *
 * @package HappyPlace
 * @subpackage API\Endpoints
 */

namespace HappyPlace\API\Endpoints;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Dashboard Endpoint Class
 */
class Dashboard_Endpoint
{
    /**
     * The namespace for this endpoint
     *
     * @var string
     */
    private $namespace = 'happy-place/v1';

    /**
     * The base for this endpoint
     *
     * @var string
     */
    private $rest_base = 'dashboard';

    /**
     * Register routes
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/stats',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_dashboard_stats'),
                    'permission_callback' => array($this, 'get_stats_permissions_check'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/performance',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_performance_data'),
                    'permission_callback' => array($this, 'get_stats_permissions_check'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/leads',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_leads'),
                    'permission_callback' => array($this, 'get_stats_permissions_check'),
                ),
            )
        );
    }

    /**
     * Check if a given request has access to get stats
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function get_stats_permissions_check($request)
    {
        return current_user_can('access_happy_place_dashboard');
    }

    /**
     * Get dashboard statistics
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_dashboard_stats($request)
    {
        $user_id = get_current_user_id();

        $stats = array(
            'active_listings'    => $this->get_active_listings_count($user_id),
            'pending_listings'   => $this->get_pending_listings_count($user_id),
            'total_leads'        => $this->get_total_leads_count($user_id),
            'recent_inquiries'   => $this->get_recent_inquiries($user_id),
            'upcoming_showings'  => $this->get_upcoming_showings($user_id),
        );

        return rest_ensure_response($stats);
    }

    /**
     * Get performance data
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_performance_data($request)
    {
        $user_id = get_current_user_id();
        $period = $request->get_param('period') ?: '30days';

        $data = array(
            'views'              => $this->get_listing_views($user_id, $period),
            'inquiries'          => $this->get_inquiry_stats($user_id, $period),
            'conversion_rate'    => $this->get_conversion_rate($user_id, $period),
            'popular_listings'   => $this->get_popular_listings($user_id, $period),
        );

        return rest_ensure_response($data);
    }

    /**
     * Get leads data
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_leads($request)
    {
        $user_id = get_current_user_id();

        $args = array(
            'post_type'      => 'lead',
            'author'         => $user_id,
            'posts_per_page' => 10,
            'post_status'    => 'any',
        );

        $query = new \WP_Query($args);
        $leads = array();

        foreach ($query->posts as $post) {
            $leads[] = $this->prepare_lead_for_response($post);
        }

        return rest_ensure_response($leads);
    }

    /**
     * Get active listings count
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_active_listings_count($user_id)
    {
        $args = array(
            'post_type'      => 'listing',
            'author'         => $user_id,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get pending listings count
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_pending_listings_count($user_id)
    {
        $args = array(
            'post_type'      => 'listing',
            'author'         => $user_id,
            'post_status'    => 'pending',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get total leads count
     *
     * @param int $user_id User ID.
     * @return int
     */
    private function get_total_leads_count($user_id)
    {
        $args = array(
            'post_type'      => 'lead',
            'author'         => $user_id,
            'post_status'    => 'any',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get recent inquiries
     *
     * @param int $user_id User ID.
     * @return array
     */
    private function get_recent_inquiries($user_id)
    {
        $args = array(
            'post_type'      => 'inquiry',
            'author'         => $user_id,
            'posts_per_page' => 5,
            'post_status'    => 'any',
        );

        $query = new \WP_Query($args);
        $inquiries = array();

        foreach ($query->posts as $post) {
            $inquiries[] = array(
                'id'         => $post->ID,
                'title'      => $post->post_title,
                'date'       => $post->post_date,
                'status'     => $post->post_status,
                'listing_id' => get_post_meta($post->ID, 'inquiry_listing_id', true),
            );
        }

        return $inquiries;
    }

    /**
     * Prepare lead for response
     *
     * @param \WP_Post $post Post object.
     * @return array
     */
    private function prepare_lead_for_response($post)
    {
        return array(
            'id'           => $post->ID,
            'name'         => get_post_meta($post->ID, 'lead_name', true),
            'email'        => get_post_meta($post->ID, 'lead_email', true),
            'phone'        => get_post_meta($post->ID, 'lead_phone', true),
            'status'       => get_post_meta($post->ID, 'lead_status', true),
            'source'       => get_post_meta($post->ID, 'lead_source', true),
            'created_date' => $post->post_date,
            'notes'        => get_post_meta($post->ID, 'lead_notes', true),
        );
    }
}
