<?php
/**
 * AJAX Handler for Happy Place Plugin
 * 
 * @package HappyPlace
 * @subpackage API
 */

if (!defined('ABSPATH')) {
    exit;
}

class HPH_Ajax_Handler {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // Flyer generator AJAX actions
        add_action('wp_ajax_get_listing_data_for_flyer', array($this, 'get_listing_data_for_flyer'));
        add_action('wp_ajax_nopriv_get_listing_data_for_flyer', array($this, 'get_listing_data_for_flyer'));
    }
    
    /**
     * Get listing data for flyer generation
     */
    public function get_listing_data_for_flyer() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'flyer_generator_nonce')) {
            wp_die('Security check failed');
        }
        
        $listing_id = intval($_POST['listing_id']);
        
        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
            return;
        }
        
        // Check if post exists and is a listing
        $post = get_post($listing_id);
        if (!$post || $post->post_type !== 'listing') {
            wp_send_json_error('Listing not found');
            return;
        }
        
        // Use bridge functions to get listing data
        $listing_data = array(
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => hph_get_listing_price($listing_id, false),
            'address' => hph_get_listing_address($listing_id, 'full'),
            'street_address' => hph_get_listing_address($listing_id, 'street'),
            'city' => hph_get_listing_address($listing_id, 'city'),
            'state' => hph_get_listing_address($listing_id, 'state'),
            'zip' => hph_get_listing_address($listing_id, 'zip'),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'sqft' => hph_get_listing_sqft($listing_id, false),
            'status' => hph_get_listing_status($listing_id),
            'description' => hph_get_listing_field($listing_id, 'description', ''),
            'property_type' => hph_get_listing_field($listing_id, 'property_type', ''),
            'mls' => hph_get_listing_field($listing_id, 'mls_number', ''),
            'lot_size' => hph_get_listing_lot_size($listing_id, false),
            'year_built' => hph_get_listing_field($listing_id, 'year_built', ''),
            'features' => hph_get_listing_features($listing_id),
            'coordinates' => hph_get_listing_field($listing_id, 'coordinates', array())
        );
        
        // Get featured image
        $featured_image_id = get_post_thumbnail_id($listing_id);
        if ($featured_image_id) {
            $listing_data['featured_image'] = wp_get_attachment_image_url($featured_image_id, 'large');
            $listing_data['featured_image_thumb'] = wp_get_attachment_image_url($featured_image_id, 'medium');
        }
        
        // Get gallery images
        $gallery = hph_get_listing_field($listing_id, 'property_gallery', array());
        $listing_data['gallery'] = $gallery;
        
        // Get agent data
        $agent_data = hph_get_listing_agent($listing_id);
        $listing_data['agent'] = $agent_data;
        
        // Add debug info if in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $listing_data['debug'] = array(
                'raw_fields' => hph_get_listing_fields($listing_id),
                'bridge_functions_loaded' => function_exists('hph_get_listing_price')
            );
        }
        
        wp_send_json_success($listing_data);
    }
}

// Initialize the AJAX handler
new HPH_Ajax_Handler();