<?php
/**
 * Helper Functions
 * 
 * General utility functions for the theme
 *
 * @package HappyPlace
 * @subpackage Utilities
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get current page context for asset loading
 *
 * @return string Page context
 */
function hph_get_page_context() {
    if (is_singular('listing')) {
        return 'single-listing';
    } elseif (is_post_type_archive('listing') || is_tax(['listing_category', 'listing_tag'])) {
        return 'listing-archive';
    } elseif (is_singular('agent')) {
        return 'agent-profile';
    } elseif (is_page_template(['agent-dashboard.php', 'dashboard.php'])) {
        return 'dashboard';
    } elseif (is_search() || is_page_template('search-listings.php')) {
        return 'search';
    } elseif (is_front_page()) {
        return 'home';
    } else {
        return 'default';
    }
}

/**
 * Debug function - only show in WP_DEBUG mode
 *
 * @param mixed $data Data to debug
 * @param string $label Debug label
 */
function hph_debug($data, $label = 'Debug') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($label . ': ' . print_r($data, true));
    }
}

/**
 * Check if plugin is active and function exists
 *
 * @param string $function_name Function name to check
 * @return bool
 */
function hph_plugin_function_exists($function_name) {
    return function_exists($function_name) && 
           is_plugin_active('happy-place-plugin/happy-place-plugin.php');
}

/**
 * Get SVG icon
 *
 * @param string $icon Icon name
 * @param array $args Icon arguments
 * @return string SVG icon HTML
 */
function hph_get_svg_icon($icon, $args = []) {
    $defaults = [
        'class' => 'hph-icon',
        'width' => 24,
        'height' => 24,
        'fill' => 'currentColor'
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    $icons = [
        'home' => '<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>',
        'bed' => '<path d="M7 14c1.66 0 3-1.34 3-3S8.66 8 7 8s-3 1.34-3 3 1.34 3 3 3zm0-4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM19 7h-8v7H3V6c0-.55-.45-1-1-1s-1 .45-1 1v13c0 .55.45 1 1 1s1-.45 1-1v-2h16v2c0 .55.45 1 1 1s1-.45 1-1V10c0-1.66-1.34-3-3-3z"/>',
        'bath' => '<path d="M9 1c0-.55-.45-1-1-1s-1 .45-1 1v3H6c-.55 0-1 .45-1 1v1c0 .55.45 1 1 1h1v2l-4.5 4.5c-.39.39-.39 1.02 0 1.41l7 7c.39.39 1.02.39 1.41 0L15.5 17H20c.55 0 1-.45 1-1V4c0-.55-.45-1-1-1H9V1z"/>',
        'area' => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.5-6L9 18H5v-3l2.5-3.21 1.79 2.15 3.21-4.06z"/>',
        'phone' => '<path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>',
        'email' => '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
        'location' => '<path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>'
    ];
    
    if (!isset($icons[$icon])) {
        return '';
    }
    
    return sprintf(
        '<svg class="%s" width="%d" height="%d" viewBox="0 0 24 24" fill="%s">%s</svg>',
        esc_attr($args['class']),
        (int) $args['width'],
        (int) $args['height'],
        esc_attr($args['fill']),
        $icons[$icon]
    );
}

/**
 * Check if current user can edit listings
 *
 * @return bool
 */
function hph_can_edit_listings() {
    return current_user_can('edit_posts') && 
           (current_user_can('administrator') || 
            in_array('agent', wp_get_current_user()->roles));
}

/**
 * Get pagination for listings
 *
 * @param WP_Query $query Query object
 * @return string Pagination HTML
 */
function hph_get_pagination($query = null) {
    global $wp_query;
    
    if (!$query) {
        $query = $wp_query;
    }
    
    $big = 999999999; // need an unlikely integer
    
    $pagination = paginate_links([
        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
        'format' => '?paged=%#%',
        'current' => max(1, get_query_var('paged')),
        'total' => $query->max_num_pages,
        'prev_text' => '&laquo; Previous',
        'next_text' => 'Next &raquo;',
        'type' => 'list',
        'class' => 'hph-pagination'
    ]);
    
    return $pagination;
}
