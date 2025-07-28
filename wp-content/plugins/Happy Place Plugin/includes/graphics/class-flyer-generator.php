<?php

namespace HappyPlace\Graphics;

use function \wp_enqueue_script;
use function \wp_enqueue_style;
use function \wp_localize_script;
use function \get_field;
use function \get_the_title;
use function \get_permalink;
use function \plugin_dir_url;
use function \plugin_dir_path;
use const \HPH_VERSION;
use const \HPH_ASSETS_URL;

/**
 * Flyer Generator Class
 * Handles real estate flyer generation using Fabric.js
 */
class Flyer_Generator {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_action('wp_ajax_nopriv_generate_flyer', [$this, 'ajax_generate_flyer']);
        add_shortcode('listing_flyer_generator', [$this, 'render_flyer_generator']);
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts(): void {
    // Enqueue Fabric.js from CDN
    wp_enqueue_script(
        'fabric-js',
        'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
        [],
        '5.3.0',
        true
    );

    // Enqueue QR Code library from CDN
    wp_enqueue_script(
        'qrcode-js',
        'https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js',
        [],
        '1.5.3',
        true
    );

    // Enqueue jsPDF library for PDF generation
    wp_enqueue_script(
        'jspdf',
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
        [],
        '2.5.1',
        true
    );

    // Enqueue custom flyer generator script
    wp_enqueue_script(
        'flyer-generator',
        HPH_ASSETS_URL . 'js/flyer-generator.js',
        ['fabric-js', 'qrcode-js', 'jspdf', 'jquery'],
        HPH_VERSION,
        true
    );

    // Localize AJAX URL and nonce
    wp_localize_script('flyer-generator', 'flyerGenerator', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('flyer_generator_nonce')
    ]);

    // Enqueue flyer-specific styles
    wp_enqueue_style(
        'flyer-generator-styles',
        HPH_ASSETS_URL . 'css/flyer-generator.css',
        [],
        HPH_VERSION
    );
}


    /**
     * AJAX handler for generating flyer content
     */
    public function ajax_generate_flyer(): void {
        check_ajax_referer('flyer_generator_nonce', 'nonce');

        $listing_id = intval($_POST['listing_id'] ?? 0);
        $flyer_type = sanitize_text_field($_POST['flyer_type'] ?? 'listing');
        
        if (!$listing_id) {
            wp_send_json_error('Invalid listing ID');
        }

        $data = $this->get_listing_data($listing_id, $flyer_type);
        wp_send_json_success($data);
    }

    /**
     * Gather listing, agent, and community data using bridge functions
     */
    private function get_listing_data(int $listing_id, string $flyer_type = 'listing'): array {
        // Ensure bridge functions are available
        if (!function_exists('hph_bridge_get_lot_size')) {
            // Include theme bridge functions if not already loaded
            $theme_bridge_path = get_template_directory() . '/inc/template-bridge.php';
            if (file_exists($theme_bridge_path)) {
                include_once $theme_bridge_path;
            }
        }
        
        // Use bridge functions for consistent data access
        $listing_fields = [
            'id'               => $listing_id,
            'title'            => get_the_title($listing_id),
            'url'              => get_permalink($listing_id),
            'listing_url'      => get_permalink($listing_id),
            'permalink'        => get_permalink($listing_id),
            'price'            => hph_bridge_get_price($listing_id, false), // Get raw numeric value
            'listing_price'    => hph_bridge_get_price($listing_id, false),
            'bedrooms'         => hph_bridge_get_bedrooms($listing_id),
            'beds'             => hph_bridge_get_bedrooms($listing_id),
            'bathrooms'        => hph_bridge_get_bathrooms($listing_id),
            'baths'            => hph_bridge_get_bathrooms($listing_id),
            'square_footage'   => hph_bridge_get_sqft($listing_id),
            'sqft'             => hph_bridge_get_sqft($listing_id),
            'living_area'      => hph_bridge_get_sqft($listing_id),
            'street_address'   => hph_bridge_get_address($listing_id, 'street'),
            'address'          => hph_bridge_get_address($listing_id, 'full'),
            'full_address'     => hph_bridge_get_address($listing_id, 'full'),
            'city'             => hph_bridge_get_address($listing_id, 'city'),
            'state'            => hph_bridge_get_address($listing_id, 'state'),
            'region'           => hph_bridge_get_address($listing_id, 'state'),
            'zip_code'         => hph_bridge_get_address($listing_id, 'zip'),
            'zip'              => hph_bridge_get_address($listing_id, 'zip'),
            'property_type'    => hph_bridge_get_property_type($listing_id),
            'short_description'=> get_field('property_description', $listing_id),
            'description'      => get_field('property_description', $listing_id),
            'listing_description' => get_field('property_description', $listing_id),
            'property_description' => get_field('property_description', $listing_id),
            'public_remarks'   => get_field('property_description', $listing_id),
            'marketing_remarks'=> get_field('property_description', $listing_id),
            'brief_description'=> get_field('property_description', $listing_id),
            'remarks'          => get_field('property_description', $listing_id),
            'main_photo'       => hph_get_main_image($listing_id),
            'featured_image'   => hph_get_main_image($listing_id),
            'photo_gallery'    => hph_bridge_get_gallery($listing_id),
            'gallery'          => hph_bridge_get_gallery($listing_id),
            'mls_number'       => hph_bridge_get_mls_number($listing_id),
            'listing_status'   => hph_bridge_get_status($listing_id),
            'status'           => hph_bridge_get_status($listing_id),
            'year_built'       => hph_bridge_get_features($listing_id, 'year_built'),
            'lot_size'         => function_exists('hph_bridge_get_lot_size') 
                                    ? hph_bridge_get_lot_size($listing_id) 
                                    : get_field('lot_size', $listing_id),
            'lot_acres'        => function_exists('hph_bridge_get_lot_size') 
                                    ? hph_bridge_get_lot_size($listing_id, true) 
                                    : get_field('lot_size', $listing_id),
        ];

        // Debug: Log lot size data
        $lot_size_raw = function_exists('hph_bridge_get_lot_size') 
                        ? hph_bridge_get_lot_size($listing_id) 
                        : get_field('lot_size', $listing_id);
        $lot_size_formatted = function_exists('hph_bridge_get_lot_size') 
                              ? hph_bridge_get_lot_size($listing_id, true) 
                              : get_field('lot_size', $listing_id);
        
        error_log('Flyer Generator - Lot size for listing ' . $listing_id . ': ' . print_r([
            'raw' => $lot_size_raw,
            'formatted' => $lot_size_formatted,
            'direct_field' => get_field('lot_size', $listing_id),
            'bridge_function_exists' => function_exists('hph_bridge_get_lot_size')
        ], true));

        // Get agent data using bridge functions
        $agent_data = hph_get_listing_agent($listing_id);
        
        // Get hosting agent data if this is an open house flyer
        $hosting_agent_data = null;
        if ($flyer_type === 'open_house') {
            // Use the Open House Bridge to get hosting agent data
            if (class_exists('HappyPlace\Core\Open_House_Bridge')) {
                $bridge = new \HappyPlace\Core\Open_House_Bridge();
                $open_house_data = $bridge->get_open_house_data($listing_id);
                
                if ($open_house_data && isset($open_house_data['hosting_agent_id'])) {
                    $hosting_agent_data = $bridge->get_hosting_agent_data($open_house_data['hosting_agent_id']);
                    error_log('Flyer Generator - Hosting agent data for open house: ' . print_r($hosting_agent_data, true));
                }
            }
        }
        
        // Debug: Log what agent data we're getting
        error_log('Flyer Generator - Agent data for listing ' . $listing_id . ': ' . print_r($agent_data, true));
        
        // Check for agent image in multiple possible field names
        $agent_image_url = '';
        if ($agent_data) {
            // Check all possible image field names from bridge function
            if (isset($agent_data['image'])) {
                $agent_image_url = $agent_data['image'];
            } elseif (isset($agent_data['photo']) && is_array($agent_data['photo']) && isset($agent_data['photo']['url'])) {
                $agent_image_url = $agent_data['photo']['url'];
            } elseif (isset($agent_data['photo']) && is_string($agent_data['photo'])) {
                $agent_image_url = $agent_data['photo'];
            }
            
            error_log('Flyer Generator - Agent image URL resolved: ' . $agent_image_url);
            
            // If we found an image URL, map it to all the possible JavaScript field names
            if ($agent_image_url) {
                $agent_data['image'] = $agent_image_url;
                $agent_data['profile_photo'] = $agent_image_url;
                $agent_data['photo'] = $agent_image_url;
                $agent_data['agent_photo'] = $agent_image_url;
                $agent_data['headshot'] = $agent_image_url;
                $agent_data['profile_image'] = $agent_image_url;
                $agent_data['profile_pic'] = $agent_image_url;
                $agent_data['user_photo'] = $agent_image_url;
                $agent_data['avatar'] = $agent_image_url;
                $agent_data['picture'] = $agent_image_url;
            }
        }
        
        // Ensure agent data has all the fields the JavaScript expects
        if ($agent_data && is_array($agent_data)) {
            // Add fallback fields that JavaScript might look for
            $agent_data['name'] = $agent_data['display_name'] ?? $agent_data['name'] ?? 'Agent Name Not Available';
            $agent_data['display_name'] = $agent_data['display_name'] ?? $agent_data['name'] ?? 'Agent Name Not Available';
            $agent_data['email'] = $agent_data['email'] ?? $agent_data['contact_email'] ?? 'info@theparkergroup.com';
            $agent_data['phone'] = $agent_data['phone'] ?? $agent_data['mobile_phone'] ?? $agent_data['office_phone'] ?? '302.217.6692';
            
            // The hph_get_listing_agent function returns the photo in the 'image' field
            // Map this to all the expected field names for JavaScript
            $agent_photo = $agent_data['image'] ?? null;
            $agent_data['profile_photo'] = $agent_photo;
            $agent_data['photo'] = $agent_photo;
            $agent_data['agent_photo'] = $agent_photo;
            $agent_data['headshot'] = $agent_photo;
            $agent_data['profile_image'] = $agent_photo;
            $agent_data['profile_pic'] = $agent_photo;
            $agent_data['user_photo'] = $agent_photo;
            $agent_data['avatar'] = $agent_photo;
            $agent_data['picture'] = $agent_photo;
        } else {
            // Fallback agent data if none found
            $agent_data = [
                'name'           => 'Agent Name Not Available',
                'display_name'   => 'Agent Name Not Available',
                'phone'          => '302.217.6692',
                'email'          => 'info@theparkergroup.com',
                'image'          => null,
                'profile_photo'  => null,
                'photo'          => null,
                'agent_photo'    => null,
                'headshot'       => null,
                'profile_image'  => null,
                'profile_pic'    => null,
                'user_photo'     => null,
                'avatar'         => null,
                'picture'        => null,
            ];
        }

        // Process hosting agent data similarly to regular agent data
        if ($hosting_agent_data && is_array($hosting_agent_data)) {
            // Check for hosting agent image in multiple possible field names
            $hosting_agent_image_url = '';
            if (isset($hosting_agent_data['image'])) {
                $hosting_agent_image_url = $hosting_agent_data['image'];
            } elseif (isset($hosting_agent_data['photo']) && is_array($hosting_agent_data['photo']) && isset($hosting_agent_data['photo']['url'])) {
                $hosting_agent_image_url = $hosting_agent_data['photo']['url'];
            } elseif (isset($hosting_agent_data['photo']) && is_string($hosting_agent_data['photo'])) {
                $hosting_agent_image_url = $hosting_agent_data['photo'];
            }
            
            // Map image to all possible field names
            if ($hosting_agent_image_url) {
                $hosting_agent_data['image'] = $hosting_agent_image_url;
                $hosting_agent_data['profile_photo'] = $hosting_agent_image_url;
                $hosting_agent_data['photo'] = $hosting_agent_image_url;
                $hosting_agent_data['agent_photo'] = $hosting_agent_image_url;
                $hosting_agent_data['headshot'] = $hosting_agent_image_url;
                $hosting_agent_data['profile_image'] = $hosting_agent_image_url;
                $hosting_agent_data['profile_pic'] = $hosting_agent_image_url;
                $hosting_agent_data['user_photo'] = $hosting_agent_image_url;
                $hosting_agent_data['avatar'] = $hosting_agent_image_url;
                $hosting_agent_data['picture'] = $hosting_agent_image_url;
            }
            
            // Ensure hosting agent data has all the fields the JavaScript expects
            $hosting_agent_data['name'] = $hosting_agent_data['display_name'] ?? $hosting_agent_data['name'] ?? 'Hosting Agent Not Available';
            $hosting_agent_data['display_name'] = $hosting_agent_data['display_name'] ?? $hosting_agent_data['name'] ?? 'Hosting Agent Not Available';
            $hosting_agent_data['email'] = $hosting_agent_data['email'] ?? $hosting_agent_data['contact_email'] ?? 'info@theparkergroup.com';
            $hosting_agent_data['phone'] = $hosting_agent_data['phone'] ?? $hosting_agent_data['mobile_phone'] ?? $hosting_agent_data['office_phone'] ?? '302.217.6692';
        }

        // Get community data if available
        $community = get_field('community', $listing_id);
        $community_data = [];
        if ($community) {
            $community_data = [
                'name'        => get_the_title($community->ID),
                'description' => get_field('community_description', $community->ID),
                'amenities'   => get_field('amenities', $community->ID),
                'hoa_fees'    => get_field('hoa_fees', $community->ID),
            ];
        }

        return [
            'listing'       => $listing_fields,
            'agent'         => $agent_data,
            'hosting_agent' => $hosting_agent_data, // Add hosting agent data for open house flyers
            'community'     => $community_data,
            'listing_title' => get_the_title($listing_id),
            'listing_url'   => get_permalink($listing_id),
            
            // Also include data at root level for backward compatibility
            'id'            => $listing_id,
            'price'         => $listing_fields['price'],
            'bedrooms'      => $listing_fields['bedrooms'],
            'bathrooms'     => $listing_fields['bathrooms'],
            'square_footage'=> $listing_fields['square_footage'],
            'address'       => $listing_fields['address'],
            'city'          => $listing_fields['city'],
            'description'   => $listing_fields['description'],
            'gallery'       => $listing_fields['gallery'],
            'lot_size'      => $listing_fields['lot_size'],
            'lot_acres'     => $listing_fields['lot_acres'],
        ];
    }

    /**
     * Shortcode output
     */
    public function render_flyer_generator($atts): string {
        $atts = shortcode_atts([
            'listing_id' => 0,
            'template'   => 'parker_group'
        ], $atts);

        // Always enqueue scripts with the shortcode
        $this->enqueue_scripts();

        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/flyer-generator.php';
        return ob_get_clean();
    }
}

// Boot it up
Flyer_Generator::get_instance();
