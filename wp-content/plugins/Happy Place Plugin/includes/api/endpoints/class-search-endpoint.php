<?php

/**
 * Search Endpoint
 *
 * @package HappyPlace
 * @subpackage API\Endpoints
 */

namespace HappyPlace\API\Endpoints;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Search Endpoint Class
 */
class Search_Endpoint
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
    private $rest_base = 'search';

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
                    'callback'            => array($this, 'search_items'),
                    'permission_callback' => array($this, 'search_items_permissions_check'),
                    'args'                => $this->get_search_params(),
                ),
            )
        );
    }

    /**
     * Get search parameters
     *
     * @return array
     */
    private function get_search_params()
    {
        return array(
            'type' => array(
                'description' => 'Type of content to search (listings, agents, etc.)',
                'type'        => 'string',
                'enum'        => array('listings', 'agents', 'communities'),
                'required'    => true,
            ),
            'query' => array(
                'description' => 'Search query string',
                'type'        => 'string',
            ),
            'price_min' => array(
                'description' => 'Minimum price',
                'type'        => 'number',
            ),
            'price_max' => array(
                'description' => 'Maximum price',
                'type'        => 'number',
            ),
            'bedrooms' => array(
                'description' => 'Number of bedrooms',
                'type'        => 'integer',
            ),
            'bathrooms' => array(
                'description' => 'Number of bathrooms',
                'type'        => 'integer',
            ),
            'property_type' => array(
                'description' => 'Property type',
                'type'        => 'string',
            ),
            'page' => array(
                'description' => 'Current page of results',
                'type'        => 'integer',
                'default'     => 1,
            ),
            'per_page' => array(
                'description' => 'Number of results per page',
                'type'        => 'integer',
                'default'     => 10,
            ),
        );
    }

    /**
     * Check if a given request has access to search items
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|bool
     */
    public function search_items_permissions_check($request)
    {
        return true; // Public access for search
    }

    /**
     * Search items based on parameters
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    public function search_items($request)
    {
        $type = $request->get_param('type');
        $method = 'search_' . $type;

        if (method_exists($this, $method)) {
            return $this->$method($request);
        }

        return new \WP_Error(
            'invalid_search_type',
            'Invalid search type specified.',
            array('status' => 400)
        );
    }

    /**
     * Search listings
     *
     * @param \WP_REST_Request $request Full data about the request.
     * @return \WP_Error|\WP_REST_Response
     */
    private function search_listings($request)
    {
        $args = array(
            'post_type'      => 'listing',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param('per_page'),
            'paged'          => $request->get_param('page'),
        );

        // Add meta query for price range
        $meta_query = array();

        if ($request->get_param('price_min') || $request->get_param('price_max')) {
            $price_query = array('relation' => 'AND');

            if ($request->get_param('price_min')) {
                $price_query[] = array(
                    'key'     => 'listing_price',
                    'value'   => $request->get_param('price_min'),
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                );
            }

            if ($request->get_param('price_max')) {
                $price_query[] = array(
                    'key'     => 'listing_price',
                    'value'   => $request->get_param('price_max'),
                    'type'    => 'NUMERIC',
                    'compare' => '<=',
                );
            }

            $meta_query[] = $price_query;
        }

        // Add other meta queries
        if ($request->get_param('bedrooms')) {
            $meta_query[] = array(
                'key'     => 'listing_bedrooms',
                'value'   => $request->get_param('bedrooms'),
                'type'    => 'NUMERIC',
                'compare' => '=',
            );
        }

        if ($request->get_param('bathrooms')) {
            $meta_query[] = array(
                'key'     => 'listing_bathrooms',
                'value'   => $request->get_param('bathrooms'),
                'type'    => 'NUMERIC',
                'compare' => '=',
            );
        }

        if (! empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        // Add property type taxonomy query
        if ($request->get_param('property_type')) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'property_type',
                    'field'    => 'slug',
                    'terms'    => $request->get_param('property_type'),
                ),
            );
        }

        $query = new \WP_Query($args);
        $posts = array();

        foreach ($query->posts as $post) {
            $posts[] = $this->prepare_listing_for_response($post, $request);
        }

        $response = rest_ensure_response($posts);

        // Add pagination headers
        $total_posts = $query->found_posts;
        $max_pages = ceil($total_posts / (int) $request->get_param('per_page'));

        $response->header('X-WP-Total', (int) $total_posts);
        $response->header('X-WP-TotalPages', (int) $max_pages);

        return $response;
    }

    /**
     * Prepare listing for response
     *
     * @param \WP_Post         $post    Post object.
     * @param \WP_REST_Request $request Request object.
     * @return array
     */
    private function prepare_listing_for_response($post, $request)
    {
        return array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'content'     => $post->post_content,
            'price'       => get_post_meta($post->ID, 'listing_price', true),
            'bedrooms'    => get_post_meta($post->ID, 'listing_bedrooms', true),
            'bathrooms'   => get_post_meta($post->ID, 'listing_bathrooms', true),
            'square_feet' => get_post_meta($post->ID, 'listing_square_feet', true),
            'address'     => get_post_meta($post->ID, 'listing_address', true),
            'city'        => get_post_meta($post->ID, 'listing_city', true),
            'state'       => get_post_meta($post->ID, 'listing_state', true),
            'zip'         => get_post_meta($post->ID, 'listing_zip', true),
            'images'      => $this->get_listing_images($post->ID),
        );
    }

    /**
     * Get listing images
     *
     * @param int $post_id Post ID.
     * @return array
     */
    private function get_listing_images($post_id)
    {
        $images = array();

        if (has_post_thumbnail($post_id)) {
            $images[] = array(
                'id'  => get_post_thumbnail_id($post_id),
                'url' => get_the_post_thumbnail_url($post_id, 'full'),
            );
        }

        $gallery_images = get_post_meta($post_id, 'listing_gallery', true);
        if (! empty($gallery_images)) {
            foreach ($gallery_images as $image_id) {
                $images[] = array(
                    'id'  => $image_id,
                    'url' => wp_get_attachment_url($image_id),
                );
            }
        }

        return $images;
    }
}
