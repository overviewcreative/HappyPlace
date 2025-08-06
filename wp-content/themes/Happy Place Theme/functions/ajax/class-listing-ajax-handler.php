<?php

/**
 * Listing Ajax Handler
 *
 * @package HappyPlace
 */

namespace HappyPlace\Ajax;

if (! defined('ABSPATH')) {
    exit;
}

class Listing_Ajax_Handler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_ajax_get_listing_data', array($this, 'get_listing_data'));
        add_action('wp_ajax_nopriv_get_listing_data', array($this, 'get_listing_data'));
    }

    /**
     * Get listing data via Ajax
     */
    public function get_listing_data()
    {
        check_ajax_referer('happy_place_nonce', 'nonce');

        $listing_id = isset($_POST['listing_id']) ? absint($_POST['listing_id']) : 0;

        if (! $listing_id) {
            wp_send_json_error(array('message' => 'Invalid listing ID'));
        }

        $listing_data = array(
            'id'    => $listing_id,
            'title' => get_the_title($listing_id),
            'data'  => get_post_meta($listing_id)
        );

        wp_send_json_success($listing_data);
    }
}
