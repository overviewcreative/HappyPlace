<?php
/**
 * Listing Fields Bridge Functions - Complete Version
 * 
 * Bridge functions to access ACF fields without direct get_field() calls in templates.
 * These functions provide a layer of abstraction between the plugin and theme.
 * 
 * @package HappyPlace
 * @subpackage Constants
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// CORE DATA RETRIEVAL - Single Query Strategy
// =============================================================================

/**
 * Get all listing fields in one query with caching
 */
if (!function_exists('hph_get_listing_fields')) {
    function hph_get_listing_fields($listing_id, $force_refresh = false) {
        static $cache = array();
        
        // Check static cache
        if (!$force_refresh && isset($cache[$listing_id])) {
            return $cache[$listing_id];
        }
        
        // Check transient cache
        $cache_key = "hph_listing_fields_{$listing_id}";
        $fields = get_transient($cache_key);
        
        if ($force_refresh || $fields === false) {
            // Single database query for all fields
            $fields = get_fields($listing_id) ?: array();
            
            // Cache for 1 hour
            set_transient($cache_key, $fields, HOUR_IN_SECONDS);
        }
        
        // Store in static cache
        $cache[$listing_id] = $fields;
        
        return $fields;
    }
}

/**
 * Get specific field with fallback and caching
 */
if (!function_exists('hph_get_listing_field')) {
    function hph_get_listing_field($listing_id, $field_name, $default = null) {
        $fields = hph_get_listing_fields($listing_id);
        return $fields[$field_name] ?? $default;
    }
}

/**
 * Get ALL listing data - Main function expected by theme templates
 */
if (!function_exists('hph_get_all_listing_data')) {
    function hph_get_all_listing_data($listing_id) {
        $fields = hph_get_listing_fields($listing_id);
        
        if (empty($fields)) {
            return null;
        }
        
        // Return structured data array
        return array(
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => hph_get_listing_price($listing_id, false),
            'formatted_price' => hph_get_listing_price($listing_id, true),
            'address' => array(
                'street' => hph_get_listing_address($listing_id, 'street'),
                'city' => hph_get_listing_address($listing_id, 'city'),
                'state' => hph_get_listing_address($listing_id, 'state'),
                'zip' => hph_get_listing_address($listing_id, 'zip'),
                'full' => hph_get_listing_address($listing_id, 'full'),
            ),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'sqft' => hph_get_listing_sqft($listing_id),
            'lot_size' => hph_get_listing_lot_size($listing_id),
            'status' => hph_get_listing_status($listing_id),
            'features' => hph_get_listing_features($listing_id),
            'agent' => hph_get_listing_agent($listing_id),
            'gallery' => hph_get_listing_gallery($listing_id),
            'primary_photo' => hph_get_listing_photo($listing_id),
            'virtual_tour' => $fields['virtual_tour_url'] ?? '',
            'coordinates' => $fields['coordinates'] ?? array('latitude' => '', 'longitude' => ''),
            'raw_fields' => $fields
        );
    }
}

// =============================================================================
// FORMATTED GETTERS - Using Cached Data
// =============================================================================

/**
 * Get listing price with formatting options
 */
if (!function_exists('hph_get_listing_price')) {
    function hph_get_listing_price($listing_id, $formatted = true) {
        $price = hph_get_listing_field($listing_id, 'price', 0);
        
        if (!$price || $price <= 0) {
            return $formatted ? 'Price on Request' : 0;
        }
        
        if ($formatted) {
            return apply_filters('hph_formatted_price', '$' . number_format($price), $price);
        }
        
        return (int) $price;
    }
}

/**
 * Get listing address with format options - Fixed field names
 */
if (!function_exists('hph_get_listing_address')) {
    function hph_get_listing_address($listing_id, $format = 'full') {
        $fields = hph_get_listing_fields($listing_id);
        
        switch ($format) {
            case 'street':
                $street_parts = array_filter([
                    $fields['street_number'] ?? '',
                    $fields['street_name'] ?? ''
                ]);
                return implode(' ', $street_parts);
                
            case 'city':
                return $fields['city'] ?? '';
                
            case 'state':
                return $fields['state'] ?? '';
                
            case 'zip':
                return $fields['zip'] ?? '';
                
            case 'full':
            default:
                $parts = array_filter([
                    $fields['street_number'] ?? '',
                    $fields['street_name'] ?? '',
                    $fields['city'] ?? '',
                    $fields['state'] ?? '',
                    $fields['zip'] ?? ''
                ]);
                return implode(' ', $parts);
        }
    }
}

/**
 * Get listing bedrooms
 */
if (!function_exists('hph_get_listing_bedrooms')) {
    function hph_get_listing_bedrooms($listing_id) {
        return (int) hph_get_listing_field($listing_id, 'bedrooms', 0);
    }
}

/**
 * Get listing bathrooms
 */
if (!function_exists('hph_get_listing_bathrooms')) {
    function hph_get_listing_bathrooms($listing_id, $include_half = true) {
        $full_baths = (float) hph_get_listing_field($listing_id, 'bathrooms', 0);
        
        if ($include_half) {
            $half_baths = (float) hph_get_listing_field($listing_id, 'half_bathrooms', 0);
            return $full_baths + ($half_baths * 0.5);
        }
        
        return $full_baths;
    }
}

/**
 * Get listing square footage
 */
if (!function_exists('hph_get_listing_sqft')) {
    function hph_get_listing_sqft($listing_id, $formatted = false) {
        $sqft = (int) hph_get_listing_field($listing_id, 'square_footage', 0);
        
        if ($formatted && $sqft > 0) {
            return number_format($sqft) . ' sq ft';
        }
        
        return $sqft;
    }
}

/**
 * Get listing status
 */
if (!function_exists('hph_get_listing_status')) {
    function hph_get_listing_status($listing_id, $formatted = false) {
        $status = hph_get_listing_field($listing_id, 'status', 'active');
        
        if ($formatted) {
            $status_labels = [
                'coming_soon' => 'Coming Soon',
                'active' => 'Active',
                'pending' => 'Pending',
                'sold' => 'Sold',
                'withdrawn' => 'Withdrawn',
                'expired' => 'Expired'
            ];
            return $status_labels[$status] ?? ucfirst($status);
        }
        
        return $status;
    }
}

/**
 * Get listing lot size
 */
if (!function_exists('hph_get_listing_lot_size')) {
    function hph_get_listing_lot_size($listing_id, $formatted = false) {
        $lot_size = (int) hph_get_listing_field($listing_id, 'lot_size', 0);
        
        if ($formatted && $lot_size > 0) {
            if ($lot_size >= 43560) {
                $acres = round($lot_size / 43560, 2);
                return $acres . ' acres';
            } else {
                return number_format($lot_size) . ' sq ft';
            }
        }
        
        return $lot_size;
    }
}

/**
 * Get listing features
 */
if (!function_exists('hph_get_listing_features')) {
    function hph_get_listing_features($listing_id, $type = 'all') {
        $fields = hph_get_listing_fields($listing_id);
        
        $features = [];
        
        // Interior features
        if ($type === 'all' || $type === 'interior') {
            $interior = $fields['interior_features'] ?? [];
            if (is_array($interior)) {
                $features = array_merge($features, $interior);
            }
        }
        
        // Exterior features
        if ($type === 'all' || $type === 'exterior') {
            $exterior = $fields['exterior_features'] ?? [];
            if (is_array($exterior)) {
                $features = array_merge($features, $exterior);
            }
        }
        
        return $features;
    }
}

/**
 * Get listing agent information
 */
if (!function_exists('hph_get_listing_agent')) {
    function hph_get_listing_agent($listing_id) {
        $agent_post = hph_get_listing_field($listing_id, 'listing_agent');
        
        if (!$agent_post || !is_object($agent_post)) {
            return null;
        }
        
        $agent_id = $agent_post->ID;
        
        // Get agent fields in one query too
        $agent_fields = get_fields($agent_id) ?: array();
        
        return array(
            'id' => $agent_id,
            'name' => $agent_post->post_title,
            'phone' => $agent_fields['phone'] ?? '',
            'email' => $agent_fields['email'] ?? '',
            'license' => $agent_fields['license_number'] ?? '',
            'photo' => $agent_fields['profile_photo'] ?? get_the_post_thumbnail_url($agent_id),
            'title' => $agent_fields['title'] ?? '',
            'bio' => $agent_fields['bio'] ?? ''
        );
    }
}

/**
 * Get listing photo gallery
 */
if (!function_exists('hph_get_listing_gallery')) {
    function hph_get_listing_gallery($listing_id) {
        $gallery = hph_get_listing_field($listing_id, 'property_gallery', []);
        
        if (!is_array($gallery)) {
            return [];
        }
        
        return $gallery;
    }
}

/**
 * Get listing primary photo with size options
 */
if (!function_exists('hph_get_listing_photo')) {
    function hph_get_listing_photo($listing_id, $size = 'large') {
        // First try featured image
        $featured_image = get_the_post_thumbnail_url($listing_id, $size);
        if ($featured_image) {
            return $featured_image;
        }
        
        // Then try first gallery image
        $gallery = hph_get_listing_gallery($listing_id);
        if (!empty($gallery) && isset($gallery[0])) {
            $image = $gallery[0];
            if (is_array($image) && isset($image['sizes'][$size])) {
                return $image['sizes'][$size];
            } elseif (is_array($image) && isset($image['url'])) {
                return $image['url'];
            }
        }
        
        // Fallback to placeholder
        return apply_filters('hph_listing_placeholder_image', '');
    }
}

// =============================================================================
// TEMPLATE HELPER FUNCTIONS
// =============================================================================

/**
 * Template part loader - Expected by theme templates
 */
if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part($slug, $name = '', $args = []) {
        // Extract args to make them available in template
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        $template_path = '';
        
        // Build template paths to search
        $templates = [];
        
        if ($name) {
            $templates[] = "templates/template-parts/listing/{$slug}-{$name}.php";
        }
        $templates[] = "templates/template-parts/listing/{$slug}.php";
        
        // Look for template in theme
        foreach ($templates as $template) {
            $located = locate_template($template);
            if ($located) {
                $template_path = $located;
                break;
            }
        }
        
        // Load template if found
        if ($template_path) {
            include $template_path;
        }
    }
}

// =============================================================================
// DASHBOARD/PERFORMANCE FUNCTIONS (Used by dashboard templates)
// =============================================================================

/**
 * Get agent performance data (placeholder - implement based on your needs)
 */
if (!function_exists('hph_get_agent_performance')) {
    function hph_get_agent_performance($agent_id, $start_date, $end_date) {
        // Placeholder implementation
        return [
            'listings_sold' => 0,
            'total_volume' => 0,
            'avg_days_on_market' => 0,
            'commission_earned' => 0
        ];
    }
}

/**
 * Get top performing listings (placeholder)
 */
if (!function_exists('hph_get_top_performing_listings')) {
    function hph_get_top_performing_listings($agent_id, $start_date, $end_date, $limit = 5) {
        return [];
    }
}

/**
 * Get traffic sources (placeholder)
 */
if (!function_exists('hph_get_traffic_sources')) {
    function hph_get_traffic_sources($agent_id, $start_date, $end_date) {
        return [];
    }
}

/**
 * Get lead sources (placeholder)
 */
if (!function_exists('hph_get_lead_sources')) {
    function hph_get_lead_sources($agent_id, $start_date, $end_date) {
        return [];
    }
}
