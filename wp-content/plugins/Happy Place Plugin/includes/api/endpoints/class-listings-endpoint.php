<?php

/**
 * Listings Endpoint
 *
 * @package HappyPlace
 * @subpackage API\Endpoints
 */

namespace HappyPlace\API\Endpoints;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Listings Endpoint Class
 */
class Listings_Endpoint
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
    private $rest_base = 'listings';

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
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
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
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
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
        return true; // Public access for listings
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
            'post_type'      => 'listing',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
        );

        $query = new \WP_Query($args);
        $posts = array();

        foreach ($query->posts as $post) {
            $posts[] = $this->prepare_item_for_response($post, $request);
        }

        return rest_ensure_response($posts);
    }

    /**
     * Prepare the item for the REST response
     *
     * @param \WP_Post         $post    WordPress representation of the item.
     * @param \WP_REST_Request $request Request object.
     * @return array
     */
    private function prepare_item_for_response($post, $request)
    {
        return array(
            'id'           => $post->ID,
            'title'        => $post->post_title,
            'content'      => $post->post_content,
            'status'      => $post->post_status,
            'date'        => $post->post_date,
            'modified'    => $post->post_modified,
        );
    }
}
