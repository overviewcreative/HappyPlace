<?php

/**
 * Listing Helper Functions
 *
 * @package HappyPlace
 */

namespace HappyPlace\Helpers;

if (! defined('ABSPATH')) {
    exit;
}

class Listing_Helper
{
    /**
     * Get formatted listing price
     *
     * @param int $listing_id The listing ID.
     * @return string
     */
    public static function get_formatted_price($listing_id)
    {
        $price = get_post_meta($listing_id, '_listing_price', true);
        return $price ? '$' . number_format($price, 2) : '';
    }

    /**
     * Get listing features
     *
     * @param int $listing_id The listing ID.
     * @return array
     */
    public static function get_listing_features($listing_id)
    {
        return get_post_meta($listing_id, '_listing_features', true);
    }

    /**
     * Get listing location
     *
     * @param int $listing_id The listing ID.
     * @return array
     */
    public static function get_listing_location($listing_id)
    {
        return array(
            'address' => get_post_meta($listing_id, '_listing_address', true),
            'city'    => get_post_meta($listing_id, '_listing_city', true),
            'state'   => get_post_meta($listing_id, '_listing_state', true),
            'zip'     => get_post_meta($listing_id, '_listing_zip', true),
        );
    }
}
