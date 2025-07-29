<?php
/**
 * Image and Media Functions
 * 
 * Utility functions for image handling, optimization, and media management
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image Optimization and WebP Support
 */
function happy_place_image_optimization() {
    // Enable native lazy loading for images
    add_filter('wp_lazy_loading_enabled', '__return_true');
    
    // Add WebP support
    add_filter('upload_mimes', function($mimes) {
        $mimes['webp'] = 'image/webp';
        return $mimes;
    });
    
    // Add srcset and sizes attributes for responsive images
    add_filter('wp_calculate_image_srcset', 'happy_place_custom_srcset', 10, 5);
    
    // Optimize image quality
    add_filter('jpeg_quality', function() { return 85; });
    add_filter('wp_editor_set_quality', function() { return 85; });
}
add_action('init', 'happy_place_image_optimization');

/**
 * Custom srcset for real estate images
 */
function happy_place_custom_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
    // Add WebP versions if they exist
    foreach ($sources as $width => $source) {
        $webp_url = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source['url']);
        $webp_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $webp_url);
        
        if (file_exists($webp_path) && isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false) {
            $sources[$width]['url'] = $webp_url;
        }
    }
    
    return $sources;
}

/**
 * Enhanced image rendering for listings
 */
function happy_place_get_listing_image($post_id = null, $size = 'listing-large', $attr = array()) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Default attributes
    $default_attr = array(
        'class' => 'hph-listing-image',
        'loading' => 'lazy',
        'decoding' => 'async'
    );
    
    $attr = array_merge($default_attr, $attr);
    
    // Try main_photo ACF field first
    $image_id = get_field('main_photo', $post_id);
    
    if ($image_id) {
        return wp_get_attachment_image($image_id, $size, false, $attr);
    }
    
    // Fallback to featured image
    if (has_post_thumbnail($post_id)) {
        return get_the_post_thumbnail($post_id, $size, $attr);
    }
    
    // Return placeholder
    return happy_place_get_image_placeholder($size, $attr);
}

/**
 * Generate image placeholder
 */
function happy_place_get_image_placeholder($size = 'listing-large', $attr = array()) {
    $sizes = array(
        'listing-thumbnail' => array(300, 200),
        'listing-large' => array(600, 400),
        'listing-full' => array(1200, 800),
        'listing-hero' => array(1920, 1080),
        'agent-avatar' => array(150, 150),
        'agent-thumbnail' => array(200, 200)
    );
    
    $dimensions = isset($sizes[$size]) ? $sizes[$size] : array(400, 300);
    $width = $dimensions[0];
    $height = $dimensions[1];
    
    $default_attr = array(
        'class' => 'hph-image-placeholder',
        'alt' => 'Property Image Coming Soon',
        'width' => $width,
        'height' => $height
    );
    
    $attr = array_merge($default_attr, $attr);
    
    // Create a simple SVG placeholder
    $svg_content = sprintf(
        '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder">
            <rect width="100%%" height="100%%" fill="#e2e8f0"/>
            <text x="50%%" y="50%%" fill="#64748b" font-family="sans-serif" font-size="14" text-anchor="middle" dy=".35em">
                📷 Property Photo
            </text>
        </svg>',
        $width,
        $height
    );
    
    $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
    
    $attr_string = '';
    foreach ($attr as $key => $value) {
        $attr_string .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
    }
    
    return sprintf('<img src="%s"%s>', $data_uri, $attr_string);
}

/**
 * Gallery function for listing images
 */
function happy_place_get_listing_gallery($post_id = null, $size = 'listing-gallery', $limit = 12) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    $gallery = get_field('photo_gallery', $post_id);
    if (!$gallery || !is_array($gallery)) {
        return '';
    }
    
    $images = array_slice($gallery, 0, $limit);
    $output = '<div class="hph-listing-gallery">';
    
    foreach ($images as $image) {
        if (is_array($image) && isset($image['ID'])) {
            $image_id = $image['ID'];
        } elseif (is_object($image) && isset($image->ID)) {
            $image_id = $image->ID;
        } else {
            continue;
        }
        
        $img_tag = wp_get_attachment_image($image_id, $size, false, [
            'class' => 'hph-gallery-image',
            'loading' => 'lazy',
            'decoding' => 'async'
        ]);
        
        $output .= sprintf('<div class="hph-gallery-item">%s</div>', $img_tag);
    }
    
    $output .= '</div>';
    
    return $output;
}
