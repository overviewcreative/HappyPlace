<?php

/**
 * Agents Endpoint
 *
 * @package HappyPlace
 * @subpackage API\Endpoints
 */

namespace HappyPlace\API\Endpoints;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Agents Endpoint Class
 */
class Agents_Endpoint
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
    private $rest_base = 'agents';

    /**
     * Register routes
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                ),
            )
        );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function get_items_permissions_check($request)
    {
        return true; // Public access for agents list
    }

    /**
     * Get a collection of items
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function get_items($request)
    {
        $args = array(
            'role'    => 'agent',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        );

        $users = get_users($args);
        $data  = array();

        foreach ($users as $user) {
            $data[] = $this->prepare_item_for_response($user, $request);
        }

        return rest_ensure_response($data);
    }

    /**
     * Prepare the item for the REST response
     *
     * @param \WP_User         $user    User object.
     * @param \WP_REST_Request $request Request object.
     * @return array
     */
    private function prepare_item_for_response($user, $request)
    {
        return array(
            'id'           => $user->ID,
            'name'         => $user->display_name,
            'email'        => $user->user_email,
            'avatar_url'   => get_avatar_url($user->ID),
            'listings'     => $this->get_agent_listings_count($user->ID),
        );
    }

    /**
     * Get the number of listings for an agent
     *
     * @param int $agent_id The agent ID.
     * @return int
     */
    private function get_agent_listings_count($agent_id)
    {
        $args = array(
            'post_type'      => 'listing',
            'author'         => $agent_id,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $query = new \WP_Query($args);
        return $query->found_posts;
    }
}
