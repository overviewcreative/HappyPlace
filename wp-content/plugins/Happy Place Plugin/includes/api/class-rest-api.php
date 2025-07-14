<?php

/**
 * REST API Setup
 *
 * @package HappyPlace
 * @subpackage API
 */

namespace HappyPlace\API;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * REST API Setup Class
 */
class REST_API
{
    /**
     * Initialize the REST API
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register API routes
     */
    public function register_routes()
    {
        // Register routes for each endpoint
        $this->register_listings_endpoint();
        $this->register_agents_endpoint();
        $this->register_search_endpoint();
        $this->register_dashboard_endpoint();
    }

    /**
     * Register listings endpoint
     */
    private function register_listings_endpoint()
    {
        $endpoint = new Endpoints\Listings_Endpoint();
        $endpoint->register_routes();
    }

    /**
     * Register agents endpoint
     */
    private function register_agents_endpoint()
    {
        $endpoint = new Endpoints\Agents_Endpoint();
        $endpoint->register_routes();
    }

    /**
     * Register search endpoint
     */
    private function register_search_endpoint()
    {
        $endpoint = new Endpoints\Search_Endpoint();
        $endpoint->register_routes();
    }

    /**
     * Register dashboard endpoint
     */
    private function register_dashboard_endpoint()
    {
        $endpoint = new Endpoints\Dashboard_Endpoint();
        $endpoint->register_routes();
    }
}
