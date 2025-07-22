<?php
/**
 * Plugin Shortcodes
 * 
 * @package HappyPlace
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flyer Generator Shortcode
 * Usage: [flyer_generator]
 */
function hph_flyer_generator_shortcode($atts) {
    $atts = shortcode_atts(array(
        'listing_id' => '',
        'template' => 'parker_group'
    ), $atts);
    
    ob_start();
    
    // Include the flyer generator template
    $template_path = HPH_PLUGIN_DIR . '/templates/flyer-generator.php';
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>Flyer generator template not found.</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('flyer_generator', 'hph_flyer_generator_shortcode');

/**
 * Simple listing shortcode that works with bridge functions
 * Usage: [listing_info id="123" field="price"]
 */
function hph_listing_info_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => '',
        'field' => 'price',
        'format' => 'default'
    ), $atts);
    
    if (empty($atts['id'])) {
        return '';
    }
    
    $listing_id = intval($atts['id']);
    $field = sanitize_text_field($atts['field']);
    $format = sanitize_text_field($atts['format']);
    
    switch ($field) {
        case 'price':
            return hph_get_listing_price($listing_id, $format !== 'raw');
        case 'address':
            return hph_get_listing_address($listing_id, $format);
        case 'bedrooms':
            return hph_get_listing_bedrooms($listing_id);
        case 'bathrooms':
            return hph_get_listing_bathrooms($listing_id);
        case 'sqft':
            return hph_get_listing_sqft($listing_id, $format !== 'raw');
        case 'status':
            return hph_get_listing_status($listing_id);
        default:
            return hph_get_listing_field($listing_id, $field, '');
    }
}
add_shortcode('listing_info', 'hph_listing_info_shortcode');
