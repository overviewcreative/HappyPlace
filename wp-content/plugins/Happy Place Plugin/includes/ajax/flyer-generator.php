<?php
/**
 * Flyer Generator AJAX Handlers
 * 
 * @package HappyPlace
 * @subpackage Ajax
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get listing data for flyer generation
 */
function hph_ajax_get_listing_data_for_flyer() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'flyer_generator_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Check permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $listing_id = intval($_POST['listing_id'] ?? 0);
    
    if (!$listing_id || get_post_type($listing_id) !== 'listing') {
        wp_send_json_error('Invalid listing ID');
    }
    
    try {
        // Get comprehensive listing data using bridge functions
        $listing_data = [
            // Basic listing info
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'listing_price' => hph_bridge_get_price($listing_id, false), // Get raw numeric value
            'price' => hph_bridge_get_price($listing_id, false), // Fallback key
            
            // Address data
            'street_address' => hph_bridge_get_address($listing_id, 'street'),
            'address' => hph_bridge_get_address($listing_id, 'full'), // Fallback key
            'city' => hph_bridge_get_address($listing_id, 'city'),
            'state' => hph_bridge_get_address($listing_id, 'state'),
            'zip_code' => hph_bridge_get_address($listing_id, 'zip'),
            'zip' => hph_bridge_get_address($listing_id, 'zip'), // Fallback key
            
            // Property details using bridge functions
            'bedrooms' => hph_bridge_get_bedrooms($listing_id),
            'beds' => hph_bridge_get_bedrooms($listing_id), // Fallback key
            'bathrooms' => hph_bridge_get_bathrooms($listing_id),
            'baths' => hph_bridge_get_bathrooms($listing_id), // Fallback key
            'square_footage' => hph_bridge_get_sqft($listing_id),
            'sqft' => hph_bridge_get_sqft($listing_id), // Fallback key
            'living_area' => hph_bridge_get_sqft($listing_id), // Fallback key
            'lot_size' => hph_bridge_get_features($listing_id, 'lot_size'),
            
            // Description using ACF field
            'listing_description' => get_field('listing_description', $listing_id),
            'description' => get_field('listing_description', $listing_id), // Fallback key
            'short_description' => get_field('listing_description', $listing_id), // Fallback key
            
            // Media using bridge functions
            'gallery' => hph_bridge_get_gallery($listing_id),
            'photo_gallery' => hph_bridge_get_gallery($listing_id), // Fallback key
            'featured_image' => hph_get_main_image($listing_id),
            'main_photo' => hph_get_main_image($listing_id), // Fallback key
            
            // Agent data using bridge function
            'agent' => hph_get_listing_agent($listing_id),
            'listing_agent' => hph_get_listing_agent($listing_id), // Fallback key
            
            // Additional details that might be useful
            'property_type' => hph_bridge_get_property_type($listing_id),
            'listing_status' => hph_bridge_get_status($listing_id),
            'status' => hph_bridge_get_status($listing_id), // Fallback key
            'mls_number' => hph_bridge_get_mls_number($listing_id),
            'year_built' => hph_bridge_get_features($listing_id, 'year_built'),
        ];
        
        // Create a structured format that matches what JavaScript expects
        $structured_data = [
            'listing' => [
                'id'               => $listing_id,
                'title'            => get_the_title($listing_id),
                'price'            => $listing_data['price'],
                'listing_price'    => $listing_data['listing_price'],
                'bedrooms'         => $listing_data['bedrooms'],
                'bathrooms'        => $listing_data['bathrooms'],
                'square_footage'   => $listing_data['square_footage'],
                'street_address'   => $listing_data['street_address'],
                'city'             => $listing_data['city'],
                'state'            => $listing_data['state'],
                'zip_code'         => $listing_data['zip_code'],
                'property_type'    => $listing_data['property_type'],
                'short_description'=> $listing_data['description'],
                'listing_description' => $listing_data['description'],
                'main_photo'       => $listing_data['main_photo'],
                'photo_gallery'    => $listing_data['gallery'],
                'mls_number'       => $listing_data['mls_number'],
                'status'           => $listing_data['status'],
                'year_built'       => $listing_data['year_built'],
                'lot_size'         => $listing_data['lot_size'],
            ],
            'agent' => $listing_data['agent'] ?: [
                'display_name' => 'Agent Name Not Available',
                'email' => 'info@theparkergroup.com',
                'phone' => '302.217.6692'
            ],
            'listing_title' => get_the_title($listing_id),
            'listing_url' => get_permalink($listing_id),
            
            // Also include root-level data for backward compatibility
            'id' => $listing_id,
            'price' => $listing_data['price'],
            'bedrooms' => $listing_data['bedrooms'],
            'bathrooms' => $listing_data['bathrooms'],
            'square_footage' => $listing_data['square_footage'],
            'address' => $listing_data['address'],
            'city' => $listing_data['city'],
            'description' => $listing_data['description'],
            'gallery' => $listing_data['gallery'],
        ];
        
        // Ensure gallery is properly formatted for JavaScript
        if (empty($structured_data['gallery']) || !is_array($structured_data['gallery'])) {
            $structured_data['gallery'] = [];
            $structured_data['listing']['photo_gallery'] = [];
        }
        
        // Ensure agent data is properly formatted
        if (empty($structured_data['agent']) || !is_array($structured_data['agent'])) {
            $structured_data['agent'] = [
                'display_name' => 'Agent Name Not Available',
                'email' => 'info@theparkergroup.com',
                'phone' => '302.217.6692'
            ];
        }
        
        // Format price for display
        if ($structured_data['listing']['price']) {
            // Handle price formatting - check if it's already formatted or numeric
            if (is_numeric($structured_data['listing']['price'])) {
                $structured_data['formatted_price'] = '$' . number_format($structured_data['listing']['price']);
                $structured_data['listing']['formatted_price'] = '$' . number_format($structured_data['listing']['price']);
            } else {
                // Price is already formatted, use as-is
                $structured_data['formatted_price'] = $structured_data['listing']['price'];
                $structured_data['listing']['formatted_price'] = $structured_data['listing']['price'];
            }
        }
        
        // Clean up any null values that might cause JavaScript issues
        $structured_data = array_map(function($value) {
            if (is_array($value)) {
                return array_map(function($subvalue) {
                    return $subvalue ?? '';
                }, $value);
            }
            return $value ?? '';
        }, $structured_data);
        
        wp_send_json_success($structured_data);
        
    } catch (Exception $e) {
        error_log('HPH Flyer Generator Error: ' . $e->getMessage());
        wp_send_json_error('Error retrieving listing data: ' . $e->getMessage());
    }
}

// Register AJAX handlers
add_action('wp_ajax_get_listing_data_for_flyer', 'hph_ajax_get_listing_data_for_flyer');
add_action('wp_ajax_nopriv_get_listing_data_for_flyer', 'hph_ajax_get_listing_data_for_flyer');

/**
 * Enqueue flyer generator assets with proper localization
 */
function hph_enqueue_flyer_generator_assets() {
    // Only load on flyer generator page
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'happy-place_page_flyer-generator') {
        return;
    }
    
    // Enqueue Fabric.js
    wp_enqueue_script(
        'fabric-js',
        'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js',
        [],
        '5.3.0',
        true
    );
    
    // Enqueue jsPDF for PDF generation
    wp_enqueue_script(
        'jspdf',
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
        [],
        '2.5.1',
        true
    );
    
    // Enqueue our flyer generator script
    wp_enqueue_script(
        'flyer-generator',
        plugin_dir_url(__FILE__) . '../assets/js/flyer-generator.js',
        ['jquery', 'fabric-js'],
        '1.0.0',
        true
    );
    
    // Localize script with AJAX data
    wp_localize_script('flyer-generator', 'flyerGenerator', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('flyer_generator_nonce'),
        'listings' => hph_get_listings_for_select(),
        'templates' => [
            'parker_group' => 'Parker Group Standard',
            'luxury' => 'Luxury Template', 
            'modern' => 'Modern Template'
        ]
    ]);
}
add_action('admin_enqueue_scripts', 'hph_enqueue_flyer_generator_assets');

/**
 * Get listings for select dropdown
 */
function hph_get_listings_for_select() {
    $listings = get_posts([
        'post_type' => 'listing',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    $options = [];
    foreach ($listings as $listing) {
        $address = hph_bridge_get_address($listing->ID, 'full');
        $price = hph_bridge_get_price($listing->ID);
        
        $label = $listing->post_title;
        if ($address) {
            $label = $address;
        }
        if ($price) {
            // Handle price formatting - check if it's already formatted or numeric
            if (is_numeric($price)) {
                $label .= ' - $' . number_format($price);
            } else {
                // Price is already formatted, use as-is
                $label .= ' - ' . $price;
            }
        }
        
        $options[] = [
            'value' => $listing->ID,
            'label' => $label
        ];
    }
    
    return $options;
}

// Register AJAX handlers
add_action('wp_ajax_get_listing_data_for_flyer', 'hph_ajax_get_listing_data_for_flyer');
add_action('wp_ajax_nopriv_get_listing_data_for_flyer', 'hph_ajax_get_listing_data_for_flyer');
add_action('wp_ajax_get_listings_for_flyer', 'hph_ajax_get_listings_for_flyer');
add_action('wp_ajax_nopriv_get_listings_for_flyer', 'hph_ajax_get_listings_for_flyer');
