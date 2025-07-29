<?php
/**
 * Template Helper Functions
 * Pure template utilities and rendering helpers with caching
 * 
 * @package HappyPlace
 * @subpackage Bridge
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get formatted template data (display-ready)
 * @param int $listing_id Listing post ID
 * @return array Formatted template data ready for display
 */
function hph_get_template_listing_data($listing_id) {
    $raw_data = hph_get_listing_data($listing_id);
    
    if (!$raw_data) {
        return [];
    }
    
    return [
        'title' => esc_html($raw_data['title'] ?? get_the_title($listing_id)),
        'price' => hph_format_price($raw_data['price'] ?? ''),
        'features' => hph_format_features($raw_data['features'] ?? []),
        'url' => get_permalink($listing_id),
        'status' => esc_html($raw_data['status'] ?? 'active'),
        'address' => hph_get_listing_address($listing_id, true)
    ];
}

/**
 * Get template part with args and caching
 * 
 * @param string $slug Template slug
 * @param string $name Template name variation
 * @param array $args Variables to pass to template
 */
if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part($slug, $name = '', $args = []) {
        // Extract args to make them available in template
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        // Build template hierarchy
        $templates = [];
        
        if ($name) {
            $templates[] = "{$slug}-{$name}.php";
        }
        $templates[] = "{$slug}.php";
        
        // Look in template-parts directory first
        foreach ($templates as $template) {
            $template_path = locate_template("template-parts/{$template}");
            if ($template_path) {
                include $template_path;
                return;
            }
        }
        
        // Fallback to WordPress get_template_part
        get_template_part($slug, $name);
    }
}

/**
 * Render listing card with caching
 * 
 * @param int $listing_id Listing post ID
 * @param array $args Additional arguments
 */
if (!function_exists('hph_render_listing_card')) {
    function hph_render_listing_card($listing_id, $args = []) {
        $defaults = [
            'show_agent' => true,
            'show_status' => true,
            'image_size' => 'medium_large',
            'class' => 'listing-card'
        ];
        
        $args = array_merge($defaults, $args);
        $args['listing_id'] = $listing_id;
        
        // Cache the rendered output for performance
        $cache_key = "listing_card_{$listing_id}_" . md5(serialize($args));
        $cached_output = wp_cache_get($cache_key, 'hph_templates');
        
        if (false !== $cached_output) {
            echo $cached_output;
            return;
        }
        
        ob_start();
        hph_get_template_part('listing-card', '', $args);
        $output = ob_get_clean();
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $output, 'hph_templates', 1800);
        echo $output;
    }
}

/**
 * Render agent card with caching
 * 
 * @param int $agent_id Agent post ID
 * @param array $args Additional arguments
 */
if (!function_exists('hph_render_agent_card')) {
    function hph_render_agent_card($agent_id, $args = []) {
        $defaults = [
            'show_bio' => true,
            'show_contact' => true,
            'image_size' => 'medium',
            'class' => 'agent-card'
        ];
        
        $args = array_merge($defaults, $args);
        $args['agent_id'] = $agent_id;
        
        // Cache the rendered output for performance
        $cache_key = "agent_card_{$agent_id}_" . md5(serialize($args));
        $cached_output = wp_cache_get($cache_key, 'hph_templates');
        
        if (false !== $cached_output) {
            echo $cached_output;
            return;
        }
        
        ob_start();
        hph_get_template_part('agent-card', '', $args);
        $output = ob_get_clean();
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $output, 'hph_templates', 1800);
        echo $output;
    }
}

/**
 * Get complete listing data for templates with caching
 * 
 * @param int $listing_id Listing post ID
 * @return array Complete listing data
 */
if (!function_exists('hph_get_template_listing_data')) {
    function hph_get_template_listing_data($listing_id) {
        $cache_key = "template_listing_data_{$listing_id}";
        $data = wp_cache_get($cache_key, 'hph_listing_data');
        
        if (false !== $data) {
            return $data;
        }
        
        $data = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => hph_get_listing_price($listing_id, true),
            'address' => hph_get_listing_address($listing_id),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'sqft' => hph_get_listing_sqft($listing_id, true),
            'status' => hph_get_listing_status($listing_id),
            'gallery' => hph_get_listing_gallery($listing_id),
            'permalink' => get_permalink($listing_id),
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'large')
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'hph_listing_data', 3600);
        
        return $data;
    }
}

/**
 * Get complete agent data for templates with caching
 * 
 * @param int $agent_id Agent post ID
 * @return array Complete agent data
 */
if (!function_exists('hph_get_template_agent_data')) {
    function hph_get_template_agent_data($agent_id) {
        $cache_key = "template_agent_data_{$agent_id}";
        $data = wp_cache_get($cache_key, 'hph_agent_data');
        
        if (false !== $data) {
            return $data;
        }
        
        $contact = hph_get_agent_contact($agent_id);
        
        $data = [
            'id' => $agent_id,
            'name' => hph_get_agent_name($agent_id),
            'bio' => hph_get_agent_bio($agent_id),
            'bio_excerpt' => hph_get_agent_bio($agent_id, true),
            'photo' => hph_get_agent_photo($agent_id),
            'phone' => $contact['phone'],
            'email' => $contact['email'],
            'specialties' => hph_get_agent_specialties($agent_id),
            'listings_count' => hph_get_agent_listings_count($agent_id),
            'permalink' => get_permalink($agent_id)
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $data, 'hph_agent_data', 3600);
        
        return $data;
    }
}

/**
 * Check if current page is a listing
 * 
 * @return bool
 */
if (!function_exists('hph_is_listing_page')) {
    function hph_is_listing_page() {
        return is_singular('listing');
    }
}

/**
 * Check if current page is an agent
 * 
 * @return bool
 */
if (!function_exists('hph_is_agent_page')) {
    function hph_is_agent_page() {
        return is_singular('agent') || 
               is_post_type_archive('agent') || 
               is_tax(['agent_category']);
    }
}

/**
 * Generate breadcrumbs with caching
 * 
 * @return string Breadcrumb HTML
 */
if (!function_exists('hph_get_breadcrumbs')) {
    function hph_get_breadcrumbs() {
        if (is_front_page()) {
            return '';
        }
        
        // Cache breadcrumbs for current page
        $cache_key = 'breadcrumbs_' . get_queried_object_id() . '_' . md5($_SERVER['REQUEST_URI']);
        $breadcrumbs_html = wp_cache_get($cache_key, 'hph_breadcrumbs');
        
        if (false !== $breadcrumbs_html) {
            return $breadcrumbs_html;
        }
        
        $breadcrumbs = ['<a href="' . home_url('/') . '">Home</a>'];
        
        if (hph_is_listing_page()) {
            $breadcrumbs[] = '<a href="' . get_post_type_archive_link('listing') . '">Listings</a>';
            $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
        } elseif (hph_is_agent_page()) {
            $breadcrumbs[] = '<a href="' . get_post_type_archive_link('agent') . '">Agents</a>';
            $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
        } elseif (is_post_type_archive('listing')) {
            $breadcrumbs[] = '<span>Listings</span>';
        } elseif (is_post_type_archive('agent')) {
            $breadcrumbs[] = '<span>Agents</span>';
        } else {
            $breadcrumbs[] = '<span>' . get_the_title() . '</span>';
        }
        
        $breadcrumbs_html = '<nav class="breadcrumbs">' . implode(' / ', $breadcrumbs) . '</nav>';
        
        // Cache for 2 hours
        wp_cache_set($cache_key, $breadcrumbs_html, 'hph_breadcrumbs', 7200);
        
        return $breadcrumbs_html;
    }
}

/**
 * Clear template caches when posts are updated
 */
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) === 'listing') {
        wp_cache_delete("template_listing_data_{$post_id}", 'hph_listing_data');
        wp_cache_flush_group('hph_templates');
        wp_cache_flush_group('hph_breadcrumbs');
    } elseif (get_post_type($post_id) === 'agent') {
        wp_cache_delete("template_agent_data_{$post_id}", 'hph_agent_data');
        wp_cache_flush_group('hph_templates');
        wp_cache_flush_group('hph_breadcrumbs');
    }
});
