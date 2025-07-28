<?php
/**
 * Template Bridge Functions
 * 
 * Global functions that work with or without the Happy Place Plugin.
 * These functions provide safe bridges between theme and plugin functionality.
 * 
 * @package HappyPlace
 * @subpackage TemplateBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// ASSET ENQUEUING BRIDGE FUNCTIONS
// =============================================================================

/**
 * Enhanced template asset enqueuing that works with webpack system
 */
if (!function_exists('hph_bridge_enqueue_template_assets')) {
    function hph_bridge_enqueue_template_assets($template_name) {
        // Ensure constants are defined
        if (!defined('HPH_THEME_DIR')) {
            define('HPH_THEME_DIR', get_template_directory());
        }
        if (!defined('HPH_ASSETS_URI')) {
            define('HPH_ASSETS_URI', get_template_directory_uri() . '/assets');
        }
        
        // Map template names to webpack entries
        $asset_map = [
            'single-listing' => 'single-listing',
            'listing-archive' => 'listing-archive', 
            'agent-profile' => 'agent-profile',
            'search-results' => 'search-results',
            'dashboard' => 'dashboard',
            'listing' => 'single-listing', // Backward compatibility
            'agent' => 'agent-profile',     // Backward compatibility
        ];
        
        $webpack_entry = $asset_map[$template_name] ?? $template_name;
        
        // Check if we have a webpack manifest
        $manifest_path = HPH_THEME_DIR . '/assets/dist/manifest.json';
        $manifest = file_exists($manifest_path) ? json_decode(file_get_contents($manifest_path), true) : [];
        
        // Enqueue template-specific JavaScript
        $js_file = isset($manifest[$webpack_entry . '.js']) ? $manifest[$webpack_entry . '.js'] : "js/{$webpack_entry}.js";
        if (file_exists(HPH_THEME_DIR . "/assets/dist/{$js_file}")) {
            wp_enqueue_script(
                'hph-template-' . $webpack_entry,
                HPH_ASSETS_URI . "/dist/{$js_file}",
                ['happyplace-components'],
                HPH_THEME_VERSION,
                true
            );
        }
        
        // Enqueue template-specific CSS (if separate from main bundle)
        $css_file = isset($manifest[$webpack_entry . '.css']) ? $manifest[$webpack_entry . '.css'] : "css/{$webpack_entry}.css";
        if (file_exists(HPH_THEME_DIR . "/assets/dist/{$css_file}")) {
            wp_enqueue_style(
                'hph-template-' . $webpack_entry,
                HPH_ASSETS_URI . "/dist/{$css_file}",
                ['happyplace-main'],
                HPH_THEME_VERSION
            );
        }
        
        // Localize with template-specific data
        wp_localize_script('hph-template-' . $webpack_entry, 'hphTemplateData', [
            'template' => $template_name,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hph_template_nonce'),
            'userId' => get_current_user_id(),
            'isLoggedIn' => is_user_logged_in(),
            'strings' => [
                'loading' => __('Loading...', 'happy-place'),
                'error' => __('An error occurred', 'happy-place'),
                'success' => __('Success!', 'happy-place'),
            ]
        ]);
    }
}

// =============================================================================
// COMPATIBILITY BRIDGE FUNCTIONS
// =============================================================================
/**
 * These bridge functions provide a compatibility layer between the plugin and theme.
 * They first attempt to use the plugin's native functions when available,
 * then fall back to direct ACF field access for theme-only installations.
 * 
 * Benefits:
 * - Seamless integration when plugin is active
 * - Graceful degradation when plugin is inactive
 * - Consistent data access patterns across templates
 * - Future-proof architecture for plugin updates
 */

/**
 * Price retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_price')) {
    function hph_bridge_get_price($listing_id, $formatted = true) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_price')) {
            $result = hph_get_listing_price($listing_id, $formatted);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("Plugin price for ID {$listing_id}: " . print_r($result, true));
            }
            return $result;
        }
        
        // Fallback to direct ACF field access
        $price = get_field('price', $listing_id) ?: 0;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("ACF price for ID {$listing_id}: " . print_r($price, true));
        }
        
        // If no ACF data either, provide test data for development
        if (!$price || $price <= 0) {
            // Check if this is the lewes-colonial listing for demo
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $price = 850000;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Using demo price for lewes-colonial: $price");
                }
            }
        }
        
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
 * Address retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_address')) {
    function hph_bridge_get_address($listing_id, $format = 'full') {
        // Use plugin function if available
        if (function_exists('hph_get_listing_address')) {
            return hph_get_listing_address($listing_id, $format);
        }
        
        // Fallback using theme bridge function
        if ($format === 'full') {
            return hph_get_formatted_address($listing_id);
        }
        
        // Handle specific format requests
        switch ($format) {
            case 'street_number':
                $street_number = get_field('street_number', $listing_id);
                
                // Demo data for lewes-colonial if no ACF data
                if (empty($street_number)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $street_number = '100';
                    }
                }
                return $street_number;
                
            case 'street_name':
                $street_name = get_field('street_name', $listing_id);
                
                // Demo data for lewes-colonial if no ACF data
                if (empty($street_name)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $street_name = 'McFee St';
                    }
                }
                return $street_name;
                
            case 'street':
                $street_parts = array_filter([
                    get_field('street_number', $listing_id),
                    get_field('street_name', $listing_id)
                ]);
                $street = implode(' ', $street_parts);
                
                // Demo data for lewes-colonial if no ACF data
                if (empty($street)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $street = '100 McFee St';
                    }
                }
                return $street;
            case 'city':
                $city = get_field('city', $listing_id) ?: '';
                if (empty($city)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $city = 'Lewes';
                    }
                }
                return $city;
            case 'state':
                $state = get_field('state', $listing_id) ?: '';
                if (empty($state)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $state = 'DE';
                    }
                }
                return $state;
            case 'zip':
                $zip = get_field('zip_code', $listing_id) ?: '';
                if (empty($zip)) {
                    $post = get_post($listing_id);
                    if ($post && $post->post_name === 'lewes-colonial') {
                        $zip = '19958';
                    }
                }
                return $zip;
            default:
                return hph_get_formatted_address($listing_id);
        }
    }
}

/**
 * Gallery retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_gallery')) {
    function hph_bridge_get_gallery($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_gallery')) {
            return hph_get_listing_gallery($listing_id);
        }
        
        // Fallback to direct ACF access
        $gallery = get_field('property_gallery', $listing_id) ?: [];
        
        if (!is_array($gallery)) {
            $gallery = [];
        }
        
        // Demo data for lewes-colonial if no ACF data
        if (empty($gallery)) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                // Create demo gallery with placeholder images
                $gallery = [
                    [
                        'url' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800',
                        'alt' => 'Beautiful colonial home exterior',
                        'ID' => 0,
                        'sizes' => [
                            'large' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800',
                            'medium' => 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=400'
                        ]
                    ],
                    [
                        'url' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800',
                        'alt' => 'Spacious living room',
                        'ID' => 0,
                        'sizes' => [
                            'large' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800',
                            'medium' => 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=400'
                        ]
                    ],
                    [
                        'url' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
                        'alt' => 'Modern kitchen',
                        'ID' => 0,
                        'sizes' => [
                            'large' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800',
                            'medium' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=400'
                        ]
                    ]
                ];
            }
        }
        
        // Normalize gallery data structure
        $normalized_gallery = [];
        foreach ($gallery as $image) {
            if (is_array($image)) {
                $normalized_gallery[] = [
                    'url' => $image['url'] ?? '',
                    'alt' => $image['alt'] ?? '',
                    'ID' => $image['ID'] ?? 0,
                    'sizes' => $image['sizes'] ?? []
                ];
            } elseif (is_numeric($image)) {
                // Handle attachment IDs
                $image_url = wp_get_attachment_image_url($image, 'large');
                $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true);
                if ($image_url) {
                    $normalized_gallery[] = [
                        'url' => $image_url,
                        'alt' => $image_alt ?: '',
                        'ID' => $image,
                        'sizes' => [
                            'large' => $image_url,
                            'medium' => wp_get_attachment_image_url($image, 'medium') ?: $image_url
                        ]
                    ];
                }
            }
        }
        
        return $normalized_gallery;
    }
}

/**
 * Property details with plugin fallback
 */
if (!function_exists('hph_bridge_get_bedrooms')) {
    function hph_bridge_get_bedrooms($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_bedrooms')) {
            return hph_get_listing_bedrooms($listing_id);
        }
        
        $bedrooms = (int) get_field('bedrooms', $listing_id) ?: 0;
        
        // Demo data for lewes-colonial if no ACF data
        if (!$bedrooms) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $bedrooms = 4;
            }
        }
        
        return $bedrooms;
    }
}

if (!function_exists('hph_bridge_get_bathrooms')) {
    function hph_bridge_get_bathrooms($listing_id, $include_half = true) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_bathrooms')) {
            return hph_get_listing_bathrooms($listing_id, $include_half);
        }
        
        $full_baths = (float) get_field('bathrooms', $listing_id) ?: 0;
        
        if ($include_half) {
            $half_baths = (float) get_field('half_baths', $listing_id) ?: 0;
            $total = $full_baths + ($half_baths * 0.5);
        } else {
            $total = $full_baths;
        }
        
        // Demo data for lewes-colonial if no ACF data
        if (!$total) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $total = 3;
            }
        }
        
        return $total;
    }
}

if (!function_exists('hph_bridge_get_sqft')) {
    function hph_bridge_get_sqft($listing_id, $formatted = false) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_sqft')) {
            return hph_get_listing_sqft($listing_id, $formatted);
        }
        
        $sqft = (int) get_field('square_footage', $listing_id) ?: 0;
        
        // Demo data for lewes-colonial if no ACF data
        if (!$sqft) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $sqft = 1800;
            }
        }
        
        if ($formatted && $sqft > 0) {
            return number_format($sqft) . ' sq ft';
        }
        
        return $sqft;
    }
}

/**
 * Status retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_status')) {
    function hph_bridge_get_status($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_status')) {
            return hph_get_listing_status($listing_id);
        }
        
        $status = get_field('status', $listing_id) ?: '';
        
        // Demo data for lewes-colonial if no ACF data
        if (empty($status)) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $status = 'Active';
            }
        }
        
        return $status ?: 'Active';
    }
}

/**
 * Property type retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_property_type')) {
    function hph_bridge_get_property_type($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_field')) {
            return hph_get_listing_field($listing_id, 'property_type', '');
        }
        
        $property_type = get_field('property_type', $listing_id) ?: '';
        
        // Demo data for lewes-colonial if no ACF data
        if (empty($property_type)) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $property_type = 'Single Family Home';
            }
        }
        
        return $property_type;
    }
}

/**
 * MLS Number retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_mls_number')) {
    function hph_bridge_get_mls_number($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_listing_field')) {
            $mls = hph_get_listing_field($listing_id, 'mls_number', '');
            if (!empty($mls)) {
                return $mls;
            }
        }
        
        $mls = get_field('mls_number', $listing_id) ?: '';
        
        // Demo data for lewes-colonial if no ACF data
        if (empty($mls)) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $mls = 'DESU2151478';
            }
        }
        
        return $mls;
    }
}

/**
 * Property features retrieval with plugin fallback
 */
if (!function_exists('hph_bridge_get_features')) {
    function hph_bridge_get_features($listing_id, $type = 'all') {
        // Use plugin function if available
        if (function_exists('hph_get_listing_features')) {
            return hph_get_listing_features($listing_id, $type);
        }
        
        // Fallback using theme bridge function
        return hph_get_property_features($listing_id, $type);
    }
}

// =============================================================================
// DATA RETRIEVAL BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get formatted status display with enhanced mapping
 */
if (!function_exists('hph_get_status_display')) {
    function hph_get_status_display($listing_id) {
        // Use exact ACF field name
        $status = get_field('status', $listing_id) ?: 'active';
        
        $status_map = [
            'active' => ['display' => __('Active', 'happy-place'), 'class' => 'active'],
            'pending' => ['display' => __('Pending', 'happy-place'), 'class' => 'pending'],
            'sold' => ['display' => __('Sold', 'happy-place'), 'class' => 'sold'],
            'off_market' => ['display' => __('Off Market', 'happy-place'), 'class' => 'off-market'],
            'coming_soon' => ['display' => __('Coming Soon', 'happy-place'), 'class' => 'coming-soon'],
            'under_contract' => ['display' => __('Under Contract', 'happy-place'), 'class' => 'pending'],
            'withdrawn' => ['display' => __('Withdrawn', 'happy-place'), 'class' => 'off-market'],
            'expired' => ['display' => __('Expired', 'happy-place'), 'class' => 'expired'],
        ];
        
        return $status_map[strtolower($status)] ?? $status_map['active'];
    }
}

/**
 * Get comprehensive formatted address
 */
if (!function_exists('hph_get_formatted_address')) {
    function hph_get_formatted_address($listing_id) {
        // Use exact ACF field names from plugin
        $address_parts = [
            'street_number' => get_field('street_number', $listing_id) ?: '',
            'street_name' => get_field('street_name', $listing_id) ?: '',
            'unit_number' => get_field('unit_number', $listing_id) ?: '',
            'city' => get_field('city', $listing_id) ?: '',
            'state' => get_field('state', $listing_id) ?: '',
            'zip_code' => get_field('zip_code', $listing_id) ?: '',
        ];
        
        // Demo data for lewes-colonial if no ACF data
        $post = get_post($listing_id);
        if ($post && $post->post_name === 'lewes-colonial') {
            if (empty($address_parts['street_number']) && empty($address_parts['street_name'])) {
                $address_parts = [
                    'street_number' => '100',
                    'street_name' => 'McFee St',
                    'unit_number' => '',
                    'city' => 'Lewes',
                    'state' => 'DE',
                    'zip_code' => '19958',
                ];
            }
        }
        
        // Build street address
        $street_parts = array_filter([$address_parts['street_number'], $address_parts['street_name']]);
        $street_address = implode(' ', $street_parts);
        if ($address_parts['unit_number']) {
            $street_address .= ' Unit ' . $address_parts['unit_number'];
        }
        
        // Build full address
        $full_parts = array_filter([
            $street_address,
            $address_parts['city'],
            trim($address_parts['state'] . ' ' . $address_parts['zip_code'])
        ]);
        
        $full_address = implode(', ', $full_parts);
        
        // Final fallback to post title if still empty
        if (empty($full_address)) {
            $full_address = get_the_title($listing_id);
        }
        
        return $full_address;
    }
}

/**
 * Get comprehensive property details with caching
 */
if (!function_exists('hph_get_property_details')) {
    function hph_get_property_details($listing_id) {
        $cache_key = 'hph_property_details_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $details = [
            'bedrooms' => get_field('bedrooms', $listing_id) ?: '',
            'bathrooms' => get_field('bathrooms', $listing_id) ?: '',
            'square_feet' => get_field('square_footage', $listing_id) ?: '', // Use exact ACF field name
            'lot_size' => get_field('lot_size', $listing_id) ?: '',
            'year_built' => get_field('year_built', $listing_id) ?: '',
            'garage' => get_field('garage_spaces', $listing_id) ?: '', // Use exact ACF field name
            'mls_number' => get_field('mls_number', $listing_id) ?: '',
            'property_type' => get_field('property_type', $listing_id) ?: hph_get_property_type_display($listing_id),
            'property_style' => get_field('property_style', $listing_id) ?: '', // From features group
            'full_baths' => get_field('full_baths', $listing_id) ?: '', // From features group
            'half_baths' => get_field('half_baths', $listing_id) ?: '', // From features group
            'stories' => get_field('stories', $listing_id) ?: '',
            'foundation' => get_field('foundation', $listing_id) ?: '',
            'roof' => get_field('roof', $listing_id) ?: '',
            'flooring' => get_field('flooring', $listing_id) ?: '',
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $details, 'hph_listings', HOUR_IN_SECONDS);
        
        return $details;
    }
}

/**
 * Get organized property features
 */
if (!function_exists('hph_get_property_features')) {
    function hph_get_property_features($listing_id) {
        $cache_key = 'hph_property_features_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Get features from ACF field groups using exact field names
        $interior = get_field('interior_features', $listing_id) ?: [];
        $exterior = get_field('exterior_features', $listing_id) ?: [];
        $utility = get_field('utility_features', $listing_id) ?: [];
        $custom = get_field('custom_features', $listing_id) ?: []; // From features group
        
        // Demo data for lewes-colonial if no ACF data
        $post = get_post($listing_id);
        if ($post && $post->post_name === 'lewes-colonial') {
            if (empty($interior) && empty($exterior) && empty($utility)) {
                $interior = ['Hardwood Floors', 'Updated Kitchen', 'Fireplace', 'Crown Molding'];
                $exterior = ['Garage', 'Deck', 'Landscaped Yard', 'Driveway'];
                $utility = ['Central Air', 'Gas Heat', 'Washer/Dryer Hookup'];
            }
        }
        
        // Handle different field formats (string, array, repeater)
        $features = [
            'interior' => hph_normalize_features($interior),
            'exterior' => hph_normalize_features($exterior),
            'utility' => hph_normalize_features($utility),
            'custom' => hph_normalize_features($custom),
        ];
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $features, 'hph_listings', HOUR_IN_SECONDS);
        
        return $features;
    }
}

/**
 * Normalize features from different field types
 */
if (!function_exists('hph_normalize_features')) {
    function hph_normalize_features($features) {
        if (!$features) {
            return [];
        }
        
        // Handle string (comma-separated)
        if (is_string($features)) {
            return array_filter(array_map('trim', explode(',', $features)));
        }
        
        // Handle array of values
        if (is_array($features)) {
            $normalized = [];
            foreach ($features as $feature) {
                if (is_string($feature)) {
                    $normalized[] = trim($feature);
                } elseif (is_array($feature) && isset($feature['value'])) {
                    $normalized[] = trim($feature['value']);
                } elseif (is_array($feature) && isset($feature['label'])) {
                    $normalized[] = trim($feature['label']);
                }
            }
            return array_filter($normalized);
        }
        
        return [];
    }
}

/**
 * Get main listing image with fallbacks
 */
if (!function_exists('hph_get_main_image')) {
    function hph_get_main_image($listing_id) {
        $cache_key = 'hph_main_image_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $image_url = '';
        
        // Try various image field names
        $image_fields = [
            'featured_image',
            'main_image', 
            'primary_image',
            'hero_image',
            'listing_image',
            'property_image'
        ];
        
        foreach ($image_fields as $field) {
            $image = get_field($field, $listing_id);
            if ($image) {
                $image_url = is_array($image) ? ($image['url'] ?? '') : $image;
                break;
            }
        }
        
        // Fallback to WordPress featured image
        if (!$image_url && has_post_thumbnail($listing_id)) {
            $image_url = get_the_post_thumbnail_url($listing_id, 'listing-hero');
        }
        
        // Try first gallery image using exact ACF field name
        if (!$image_url) {
            $gallery = get_field('property_gallery', $listing_id); // Use exact ACF field name
            if ($gallery && is_array($gallery) && !empty($gallery)) {
                $first_image = $gallery[0];
                $image_url = is_array($first_image) ? ($first_image['url'] ?? '') : $first_image;
            }
        }
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $image_url, 'hph_listings', HOUR_IN_SECONDS);
        
        return $image_url;
    }
}

/**
 * Get comprehensive listing agent information
 */
if (!function_exists('hph_get_listing_agent')) {
    function hph_get_listing_agent($listing_id) {
        $cache_key = 'hph_listing_agent_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $agent_id = null;
        
        // Try different agent field names
        $agent_fields = ['listing_agent', 'agent', 'assigned_agent', 'primary_agent'];
        foreach ($agent_fields as $field) {
            $agent_id = get_field($field, $listing_id);
            if ($agent_id) {
                break;
            }
        }
        
        // Fallback: find agent by managed listings
        if (!$agent_id) {
            $agents = get_posts([
                'post_type' => 'agent',
                'posts_per_page' => 1,
                'meta_query' => [
                    [
                        'key' => 'managed_listings',
                        'value' => '"' . $listing_id . '"',
                        'compare' => 'LIKE'
                    ]
                ]
            ]);
            
            if ($agents) {
                $agent_id = $agents[0]->ID;
            }
        }
        
        if (!$agent_id) {
            wp_cache_set($cache_key, null, 'hph_listings', HOUR_IN_SECONDS);
            return null;
        }
        
        // Get agent data
        $agent_data = hph_get_agent_data($agent_id);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $agent_data, 'hph_listings', HOUR_IN_SECONDS);
        
        return $agent_data;
    }
}

/**
 * Get comprehensive agent data
 */
if (!function_exists('hph_get_agent_data')) {
    function hph_get_agent_data($agent_id) {
        $agent_image = get_field('profile_photo', $agent_id) ?: get_field('agent_photo', $agent_id) ?: get_field('photo', $agent_id);
        $agent_image_url = '';
        
        if ($agent_image) {
            $agent_image_url = is_array($agent_image) ? ($agent_image['url'] ?? '') : $agent_image;
        }
        
        // Fallback to WordPress featured image
        if (!$agent_image_url && has_post_thumbnail($agent_id)) {
            $agent_image_url = get_the_post_thumbnail_url($agent_id, 'agent-profile');
        }
        
        return [
            'id' => $agent_id,
            'name' => get_the_title($agent_id),
            'title' => get_field('agent_title', $agent_id) ?: get_field('job_title', $agent_id) ?: get_field('title', $agent_id) ?: __('Real Estate Agent', 'happy-place'),
            'image' => $agent_image_url,
            'photo' => $agent_image, // Keep original ACF object for backward compatibility
            'phone' => get_field('phone', $agent_id) ?: get_field('phone_number', $agent_id) ?: '',
            'email' => get_field('email', $agent_id) ?: get_field('email_address', $agent_id) ?: '',
            'license' => get_field('license_number', $agent_id) ?: get_field('license', $agent_id) ?: '',
            'bio' => get_field('bio', $agent_id) ?: get_field('agent_bio', $agent_id) ?: get_field('biography', $agent_id) ?: '',
            'website' => get_field('website', $agent_id) ?: get_field('website_url', $agent_id) ?: '',
            'social_links' => get_field('social_links', $agent_id) ?: [],
            'specialties' => get_field('specialties', $agent_id) ?: get_field('agent_specialties', $agent_id) ?: [],
            'service_areas' => get_field('service_areas', $agent_id) ?: [],
            'listings_count' => hph_get_agent_listings_count($agent_id),
            'sales_count' => hph_get_agent_sales_count($agent_id),
            'url' => get_permalink($agent_id),
            'experience_years' => get_field('experience_years', $agent_id) ?: get_field('years_experience', $agent_id) ?: 0,
            'languages' => get_field('languages', $agent_id) ?: [],
        ];
    }
}

/**
 * Check if listing is favorited by current user
 */
if (!function_exists('hph_is_favorite')) {
    function hph_is_favorite($listing_id) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $favorites = get_user_meta($user_id, 'favorite_listings', true) ?: [];
        
        return in_array($listing_id, $favorites);
    }
}

/**
 * Get coordinates with geocoding fallback
 */
if (!function_exists('hph_get_coordinates')) {
    function hph_get_coordinates($listing_id) {
        $cache_key = 'hph_coordinates_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $lat = get_field('latitude', $listing_id) ?: get_field('lat', $listing_id);
        $lng = get_field('longitude', $listing_id) ?: get_field('lng', $listing_id) ?: get_field('lon', $listing_id);
        
        if ($lat && $lng) {
            $coordinates = [
                'lat' => floatval($lat),
                'lng' => floatval($lng)
            ];
        } else {
            // Try geocoding from address
            $address = hph_get_formatted_address($listing_id);
            $coordinates = hph_geocode_address($address);
        }
        
        // Cache for 24 hours
        wp_cache_set($cache_key, $coordinates, 'hph_listings', DAY_IN_SECONDS);
        
        return $coordinates;
    }
}

/**
 * Geocode address using Google Maps API
 */
if (!function_exists('hph_geocode_address')) {
    function hph_geocode_address($address) {
        if (!$address) {
            return [];
        }
        
        $api_key = get_option('hph_google_maps_api_key');
        if (!$api_key) {
            return [];
        }
        
        $cache_key = 'hph_geocode_' . md5($address);
        $cached = wp_cache_get($cache_key, 'hph_geocoding');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
            'address' => $address,
            'key' => $api_key
        ]);
        
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return [];
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $location = $data['results'][0]['geometry']['location'];
            $coordinates = [
                'lat' => $location['lat'],
                'lng' => $location['lng']
            ];
            
            // Cache for 30 days
            wp_cache_set($cache_key, $coordinates, 'hph_geocoding', 30 * DAY_IN_SECONDS);
            
            return $coordinates;
        }
        
        return [];
    }
}

/**
 * Enhanced price formatting with shorthand
 */
if (!function_exists('hph_format_price')) {
    function hph_format_price($price, $shorthand = false) {
        if (!$price || $price == 0) {
            return __('Contact for Price', 'happy-place');
        }
        
        $price = floatval($price);
        
        if ($shorthand) {
            if ($price >= 1000000) {
                return '$' . number_format($price / 1000000, 1) . 'M';
            } elseif ($price >= 1000) {
                return '$' . number_format($price / 1000, 0) . 'K';
            }
        }
        
        return '$' . number_format($price);
    }
}

/**
 * Get property type display name
 */
if (!function_exists('hph_get_property_type_display')) {
    function hph_get_property_type_display($listing_id) {
        // Try ACF field first
        $type = get_field('property_type', $listing_id);
        
        if ($type) {
            if (is_array($type)) {
                return $type['label'] ?? $type['name'] ?? $type['value'] ?? __('Residential', 'happy-place');
            }
            return $type;
        }
        
        // Try taxonomy
        $types = get_the_terms($listing_id, 'property_type');
        if ($types && !is_wp_error($types)) {
            return $types[0]->name;
        }
        
        // Try custom taxonomy
        $types = get_the_terms($listing_id, 'listing_type');
        if ($types && !is_wp_error($types)) {
            return $types[0]->name;
        }
        
        return __('Residential', 'happy-place');
    }
}

/**
 * Get agent listings count with caching
 */
if (!function_exists('hph_get_agent_listings_count')) {
    function hph_get_agent_listings_count($agent_id) {
        $cache_key = 'hph_agent_listings_count_' . $agent_id;
        $cached = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ],
                [
                    'key' => 'agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids'
        ];
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        wp_cache_set($cache_key, $count, 'hph_agents', HOUR_IN_SECONDS);
        
        return $count;
    }
}

/**
 * Get agent sales count with caching
 */
if (!function_exists('hph_get_agent_sales_count')) {
    function hph_get_agent_sales_count($agent_id) {
        $cache_key = 'hph_agent_sales_count_' . $agent_id;
        $cached = wp_cache_get($cache_key, 'hph_agents');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key' => 'listing_agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ],
                    [
                        'key' => 'agent',
                        'value' => $agent_id,
                        'compare' => '='
                    ]
                ],
                [
                    'key' => 'status',
                    'value' => 'sold',
                    'compare' => '='
                ]
            ],
            'fields' => 'ids'
        ];
        
        $query = new WP_Query($args);
        $count = $query->found_posts;
        
        wp_cache_set($cache_key, $count, 'hph_agents', HOUR_IN_SECONDS);
        
        return $count;
    }
}

/**
 * Get related listings with smart matching
 */
if (!function_exists('hph_get_related_listings')) {
    function hph_get_related_listings($listing_id, $limit = 3) {
        $cache_key = 'hph_related_listings_' . $listing_id . '_' . $limit;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false) {
            return $cached;
        }
        
        $current_price = get_field('price', $listing_id) ?: 0;
        $current_bedrooms = get_field('bedrooms', $listing_id) ?: 0;
        $current_city = get_field('city', $listing_id) ?: '';
        $current_type = hph_get_property_type_display($listing_id);
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => $limit,
            'post__not_in' => [$listing_id],
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                // Same city
                [
                    'key' => 'city',
                    'value' => $current_city,
                    'compare' => '='
                ],
                // Same bedrooms
                [
                    'key' => 'bedrooms',
                    'value' => $current_bedrooms,
                    'compare' => '='
                ],
                // Similar price range (±20%)
                [
                    'key' => 'price',
                    'value' => [$current_price * 0.8, $current_price * 1.2],
                    'compare' => 'BETWEEN',
                    'type' => 'NUMERIC'
                ]
            ],
            'orderby' => 'meta_value_num',
            'meta_key' => 'price',
            'order' => 'ASC'
        ];
        
        $query = new WP_Query($args);
        
        // Cache for 1 hour
        wp_cache_set($cache_key, $query, 'hph_listings', HOUR_IN_SECONDS);
        
        return $query;
    }
}

// =============================================================================
// COMPONENT RENDERING FUNCTIONS
// =============================================================================

/**
 * Render listing card component with options
 */
if (!function_exists('hph_render_listing_card')) {
    function hph_render_listing_card($listing_id, $options = []) {
        $defaults = [
            'variant' => 'default',
            'context' => 'grid',
            'features' => ['price', 'beds', 'baths', 'sqft'],
            'interactions' => ['favorite', 'contact', 'share'],
            'show_agent' => false,
            'lazy_load' => true,
            'schema_markup' => true,
            'cache_duration' => 3600,
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Check cache first
        $cache_key = 'hph_listing_card_' . $listing_id . '_' . md5(serialize($options));
        $cached = wp_cache_get($cache_key, 'hph_components');
        
        if ($cached !== false && !WP_DEBUG) {
            return $cached;
        }
        
        // Get listing data
        $listing_data = [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'url' => get_permalink($listing_id),
            'price' => get_field('price', $listing_id) ?: 0,
            'status' => hph_get_status_display($listing_id),
            'image' => hph_get_main_image($listing_id),
            'details' => hph_get_property_details($listing_id),
            'agent' => $options['show_agent'] ? hph_get_listing_agent($listing_id) : null,
            'is_favorite' => hph_is_favorite($listing_id),
            'address' => hph_get_formatted_address($listing_id),
        ];
        
        ob_start();
        ?>
        <div class="listing-swipe-card listing-swipe-card--<?php echo esc_attr($options['variant']); ?> listing-swipe-card--context-<?php echo esc_attr($options['context']); ?>"
             data-listing-id="<?php echo esc_attr($listing_id); ?>"
             data-component="listing-swipe-card">
            
            <div class="card-image-container">
                <?php if ($listing_data['image']) : ?>
                    <img src="<?php echo esc_url($listing_data['image']); ?>" 
                         alt="<?php echo esc_attr($listing_data['title']); ?>"
                         class="card-image"
                         <?php echo $options['lazy_load'] ? 'loading="lazy"' : ''; ?>>
                <?php else : ?>
                    <div class="card-image demo-image">
                        <i class="fas fa-home"></i>
                        <span><?php _e('No Image', 'happy-place'); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="status-badge status-badge--<?php echo esc_attr($listing_data['status']['class']); ?>">
                    <?php echo esc_html($listing_data['status']['display']); ?>
                </div>
                
                <?php if (in_array('favorite', $options['interactions'])) : ?>
                    <button class="favorite-button <?php echo $listing_data['is_favorite'] ? 'favorite-button--active' : ''; ?>"
                            data-listing-id="<?php echo esc_attr($listing_id); ?>"
                            data-nonce="<?php echo wp_create_nonce('hph_favorite_nonce'); ?>"
                            aria-label="<?php echo $listing_data['is_favorite'] ? __('Remove from favorites', 'happy-place') : __('Add to favorites', 'happy-place'); ?>">
                        <?php echo $listing_data['is_favorite'] ? '♥' : '♡'; ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="card-content">
                <div class="card-header">
                    <h3 class="card-title">
                        <a href="<?php echo esc_url($listing_data['url']); ?>">
                            <?php echo esc_html($listing_data['title']); ?>
                        </a>
                    </h3>
                    
                    <?php if ($listing_data['address']) : ?>
                        <p class="card-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($listing_data['address']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if (in_array('price', $options['features'])) : ?>
                        <div class="card-price">
                            <?php echo hph_format_price($listing_data['price']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-features">
                    <?php if (in_array('beds', $options['features']) && $listing_data['details']['bedrooms']) : ?>
                        <div class="feature-item">
                            <span class="feature-value"><?php echo esc_html($listing_data['details']['bedrooms']); ?></span>
                            <span class="feature-label"><?php _e('Beds', 'happy-place'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('baths', $options['features']) && $listing_data['details']['bathrooms']) : ?>
                        <div class="feature-item">
                            <span class="feature-value"><?php echo esc_html($listing_data['details']['bathrooms']); ?></span>
                            <span class="feature-label"><?php _e('Baths', 'happy-place'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (in_array('sqft', $options['features']) && $listing_data['details']['square_feet']) : ?>
                        <div class="feature-item">
                            <span class="feature-value"><?php echo number_format($listing_data['details']['square_feet']); ?></span>
                            <span class="feature-label"><?php _e('Sq Ft', 'happy-place'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($listing_data['agent'] && $options['show_agent']) : ?>
                    <div class="card-agent">
                        <?php if ($listing_data['agent']['image']) : ?>
                            <img src="<?php echo esc_url($listing_data['agent']['image']); ?>" 
                                 alt="<?php echo esc_attr($listing_data['agent']['name']); ?>"
                                 class="agent-avatar">
                        <?php endif; ?>
                        
                        <div class="agent-info">
                            <div class="agent-name"><?php echo esc_html($listing_data['agent']['name']); ?></div>
                            <div class="agent-title"><?php echo esc_html($listing_data['agent']['title']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card-actions">
                    <a href="<?php echo esc_url($listing_data['url']); ?>" 
                       class="action-button action-button--primary">
                        <?php _e('View Details', 'happy-place'); ?>
                    </a>
                    
                    <?php if (in_array('contact', $options['interactions'])) : ?>
                        <button class="action-button action-button--secondary contact-agent-btn"
                                data-listing-id="<?php echo esc_attr($listing_id); ?>"
                                data-nonce="<?php echo wp_create_nonce('hph_contact_nonce'); ?>">
                            <?php _e('Contact Agent', 'happy-place'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($options['schema_markup']) : ?>
                <script type="application/ld+json">
                {
                    "@context": "https://schema.org/",
                    "@type": "RealEstateListing",
                    "name": "<?php echo esc_js($listing_data['title']); ?>",
                    "url": "<?php echo esc_js($listing_data['url']); ?>",
                    "price": <?php echo intval($listing_data['price']); ?>,
                    "priceCurrency": "USD",
                    "address": "<?php echo esc_js($listing_data['address']); ?>",
                    "numberOfRooms": <?php echo intval($listing_data['details']['bedrooms']); ?>,
                    "numberOfBathroomsTotal": <?php echo intval($listing_data['details']['bathrooms']); ?>,
                    "floorSize": {
                        "@type": "QuantitativeValue",
                        "value": <?php echo intval($listing_data['details']['square_feet']); ?>,
                        "unitCode": "FTK"
                    }
                }
                </script>
            <?php endif; ?>
        </div>
        <?php
        
        $output = ob_get_clean();
        
        // Cache the output
        wp_cache_set($cache_key, $output, 'hph_components', $options['cache_duration']);
        
        return $output;
    }
}

// =============================================================================
// AJAX HANDLERS
// =============================================================================

/**
 * Initialize AJAX handlers
 */
add_action('wp_ajax_toggle_favorite', 'hph_ajax_toggle_favorite');
add_action('wp_ajax_nopriv_toggle_favorite', 'hph_ajax_toggle_favorite');
add_action('wp_ajax_property_inquiry', 'hph_ajax_property_inquiry');
add_action('wp_ajax_nopriv_property_inquiry', 'hph_ajax_property_inquiry');
add_action('wp_ajax_contact_agent', 'hph_ajax_contact_agent');
add_action('wp_ajax_nopriv_contact_agent', 'hph_ajax_contact_agent');

/**
 * AJAX handler for toggle favorite
 */
function hph_ajax_toggle_favorite() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_favorite_nonce')) {
        wp_send_json_error(__('Security check failed', 'happy-place'));
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(__('Please log in to save favorites', 'happy-place'));
    }
    
    $listing_id = intval($_POST['listing_id'] ?? 0);
    if (!$listing_id) {
        wp_send_json_error(__('Invalid listing ID', 'happy-place'));
    }
    
    $user_id = get_current_user_id();
    $favorites = get_user_meta($user_id, 'favorite_listings', true) ?: [];
    
    if (in_array($listing_id, $favorites)) {
        $favorites = array_diff($favorites, [$listing_id]);
        $action = 'removed';
        $message = __('Removed from favorites', 'happy-place');
    } else {
        $favorites[] = $listing_id;
        $action = 'added';
        $message = __('Added to favorites', 'happy-place');
    }
    
    update_user_meta($user_id, 'favorite_listings', array_values($favorites));
    
    // Clear related caches
    wp_cache_delete('hph_user_favorites_' . $user_id, 'hph_users');
    
    wp_send_json_success([
        'action' => $action,
        'message' => $message,
        'count' => count($favorites)
    ]);
}

/**
 * AJAX handler for property inquiry
 */
function hph_ajax_property_inquiry() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'property_inquiry_nonce')) {
        wp_send_json_error(__('Security check failed', 'happy-place'));
    }
    
    $property_id = intval($_POST['property_id'] ?? 0);
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    
    // Validate required fields
    if (!$property_id || !$name || !$email || !$message) {
        wp_send_json_error(__('Please fill in all required fields', 'happy-place'));
    }
    
    // Validate email
    if (!is_email($email)) {
        wp_send_json_error(__('Please enter a valid email address', 'happy-place'));
    }
    
    // Get property and agent info
    $property_title = get_the_title($property_id);
    $agent = hph_get_listing_agent($property_id);
    
    // Create inquiry post
    $inquiry_id = wp_insert_post([
        'post_type' => 'inquiry',
        'post_title' => sprintf(__('Inquiry: %s', 'happy-place'), $property_title),
        'post_status' => 'private',
        'meta_input' => [
            'property_id' => $property_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'agent_id' => $agent['id'] ?? '',
            'inquiry_date' => current_time('mysql'),
            'inquiry_type' => 'property_inquiry'
        ]
    ]);
    
    if (is_wp_error($inquiry_id)) {
        wp_send_json_error(__('Failed to send message. Please try again.', 'happy-place'));
    }
    
    // Send email notification
    $email_sent = hph_send_inquiry_notification($inquiry_id);
    
    if ($email_sent) {
        wp_send_json_success(__('Message sent successfully!', 'happy-place'));
    } else {
        wp_send_json_success(__('Message saved but email notification failed.', 'happy-place'));
    }
}

/**
 * AJAX handler for contact agent
 */
function hph_ajax_contact_agent() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'hph_contact_nonce')) {
        wp_send_json_error(__('Security check failed', 'happy-place'));
    }
    
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    
    if (!$listing_id || !$name || !$email || !$message) {
        wp_send_json_error(__('Please fill in all required fields', 'happy-place'));
    }
    
    $agent = hph_get_listing_agent($listing_id);
    if (!$agent) {
        wp_send_json_error(__('Agent information not found', 'happy-place'));
    }
    
    // Create contact record
    $contact_id = wp_insert_post([
        'post_type' => 'contact',
        'post_title' => sprintf(__('Contact: %s', 'happy-place'), $agent['name']),
        'post_status' => 'private',
        'meta_input' => [
            'listing_id' => $listing_id,
            'agent_id' => $agent['id'],
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'contact_date' => current_time('mysql'),
            'contact_type' => 'agent_contact'
        ]
    ]);
    
    if (is_wp_error($contact_id)) {
        wp_send_json_error(__('Failed to send message. Please try again.', 'happy-place'));
    }
    
    // Send email to agent
    $email_sent = hph_send_agent_contact_email($contact_id);
    
    wp_send_json_success([
        'message' => __('Message sent to agent successfully!', 'happy-place'),
        'agent_name' => $agent['name']
    ]);
}

/**
 * Send inquiry notification email
 */
function hph_send_inquiry_notification($inquiry_id) {
    $property_id = get_post_meta($inquiry_id, 'property_id', true);
    $name = get_post_meta($inquiry_id, 'name', true);
    $email = get_post_meta($inquiry_id, 'email', true);
    $phone = get_post_meta($inquiry_id, 'phone', true);
    $message = get_post_meta($inquiry_id, 'message', true);
    
    $property_title = get_the_title($property_id);
    $property_url = get_permalink($property_id);
    
    $agent = hph_get_listing_agent($property_id);
    $to_email = $agent['email'] ?? get_option('admin_email');
    
    $subject = sprintf(__('New Property Inquiry: %s', 'happy-place'), $property_title);
    
    $body = sprintf(
        __('New Property Inquiry

Property: %s
View Property: %s

Contact Information:
Name: %s
Email: %s
Phone: %s

Message:
%s

---
This email was sent from %s', 'happy-place'),
        $property_title,
        $property_url,
        $name,
        $email,
        $phone,
        $message,
        get_bloginfo('name')
    );
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
    ];
    
    return wp_mail($to_email, $subject, $body, $headers);
}

/**
 * Send agent contact email
 */
function hph_send_agent_contact_email($contact_id) {
    $listing_id = get_post_meta($contact_id, 'listing_id', true);
    $agent_id = get_post_meta($contact_id, 'agent_id', true);
    $name = get_post_meta($contact_id, 'name', true);
    $email = get_post_meta($contact_id, 'email', true);
    $phone = get_post_meta($contact_id, 'phone', true);
    $message = get_post_meta($contact_id, 'message', true);
    
    $property_title = get_the_title($listing_id);
    $property_url = get_permalink($listing_id);
    $agent = hph_get_agent_data($agent_id);
    
    $to_email = $agent['email'] ?? get_option('admin_email');
    
    $subject = sprintf(__('New Contact Request via %s', 'happy-place'), $property_title);
    
    $body = sprintf(
        __('New Contact Request

Property: %s
View Property: %s

Contact Information:
Name: %s
Email: %s
Phone: %s

Message:
%s

---
This email was sent from %s', 'happy-place'),
        $property_title,
        $property_url,
        $name,
        $email,
        $phone,
        $message,
        get_bloginfo('name')
    );
    
    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        'Reply-To: ' . $name . ' <' . $email . '>'
    ];
    
    return wp_mail($to_email, $subject, $body, $headers);
}

// =============================================================================
// CACHE MANAGEMENT
// =============================================================================

/**
 * Clear listing-related caches when posts are updated
 */
add_action('save_post', 'hph_clear_listing_caches');
add_action('delete_post', 'hph_clear_listing_caches');

function hph_clear_listing_caches($post_id) {
    $post_type = get_post_type($post_id);
    
    if ($post_type === 'listing') {
        // Clear listing-specific caches
        wp_cache_delete('hph_property_details_' . $post_id, 'hph_listings');
        wp_cache_delete('hph_property_features_' . $post_id, 'hph_listings');
        wp_cache_delete('hph_main_image_' . $post_id, 'hph_listings');
        wp_cache_delete('hph_listing_agent_' . $post_id, 'hph_listings');
        wp_cache_delete('hph_coordinates_' . $post_id, 'hph_listings');
        
        // Clear related listings caches
        wp_cache_flush_group('hph_listings');
        
        // Clear component caches
        wp_cache_flush_group('hph_components');
    }
    
    if ($post_type === 'agent') {
        // Clear agent-specific caches
        wp_cache_delete('hph_agent_listings_count_' . $post_id, 'hph_agents');
        wp_cache_delete('hph_agent_sales_count_' . $post_id, 'hph_agents');
        
        // Clear agent group cache
        wp_cache_flush_group('hph_agents');
    }
}

/**
 * Clear template caches when theme is updated
 */
add_action('after_switch_theme', function() {
    wp_cache_flush_group('hph_components');
    wp_cache_flush_group('hph_listings');
    wp_cache_flush_group('hph_agents');
});

// =============================================================================
// BACKWARD COMPATIBILITY FUNCTIONS
// =============================================================================

/**
 * Legacy function aliases for backward compatibility
 */
if (!function_exists('hph_get_listing_data')) {
    function hph_get_listing_data($listing_id) {
        return [
            'details' => hph_get_property_details($listing_id),
            'features' => hph_get_property_features($listing_id),
            'agent' => hph_get_listing_agent($listing_id),
            'status' => hph_get_status_display($listing_id),
            'coordinates' => hph_get_coordinates($listing_id)
        ];
    }
}

/**
 * Legacy template loading support
 */
if (!function_exists('hph_load_template_part')) {
    function hph_load_template_part($template_name, $args = []) {
        $template_path = locate_template("templates/template-parts/{$template_name}.php");
        
        if ($template_path) {
            if ($args) {
                extract($args);
            }
            include $template_path;
        } else {
            error_log("HPH Theme: Template part not found: {$template_name}");
        }
    }
}

/**
 * Legacy asset enqueueing
 */
if (!function_exists('hph_enqueue_template_assets')) {
    function hph_enqueue_template_assets($template_name) {
        return hph_bridge_enqueue_template_assets($template_name);
    }
}

// =============================================================================
// INTEGRATION WITH EXISTING THEME FUNCTIONS
// =============================================================================

/**
 * Integrate with existing dashboard detection
 */
add_filter('hph_is_dashboard_page', function($is_dashboard) {
    return $is_dashboard || (function_exists('hph_is_dashboard') && hph_is_dashboard());
});

/**
 * Integrate with existing template loading
 */
add_filter('template_include', function($template) {
    // Let the existing HPH_Theme class handle template loading
    return $template;
}, 5);

/**
 * Ensure bridge functions are available globally
 */
add_action('init', function() {
    // Make sure all bridge functions are loaded
    if (!function_exists('hph_get_status_display')) {
        error_log('HPH Theme: Bridge functions not loaded properly');
    }
}, 20);

/**
 * Add bridge function status to debug info
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        if (current_user_can('manage_options')) {
            echo "\n<!-- HPH Bridge Functions Debug -->\n";
            echo "<!-- Bridge functions loaded: " . (function_exists('hph_get_status_display') ? 'Yes' : 'No') . " -->\n";
            echo "<!-- Template assets function: " . (function_exists('hph_bridge_enqueue_template_assets') ? 'Yes' : 'No') . " -->\n";
            echo "<!-- Listing card renderer: " . (function_exists('hph_render_listing_card') ? 'Yes' : 'No') . " -->\n";
        }
    });
}

// =============================================================================
// EXTENDED BRIDGE FUNCTIONS - Complete ACF Field Coverage
// =============================================================================

/**
 * Property Style
 */
if (!function_exists('hph_bridge_get_property_style')) {
    function hph_bridge_get_property_style($listing_id, $formatted = false) {
        if (function_exists('hph_get_listing_property_style')) {
            return hph_get_listing_property_style($listing_id, $formatted);
        }
        
        $style = get_field('property_style', $listing_id) ?: '';
        
        if ($formatted && $style) {
            $style_labels = [
                'ranch' => 'Ranch',
                'colonial' => 'Colonial',
                'contemporary' => 'Contemporary',
                'cape_cod' => 'Cape Cod',
                'split_level' => 'Split Level',
                'victorian' => 'Victorian',
                'tudor' => 'Tudor',
                'craftsman' => 'Craftsman',
                'mediterranean' => 'Mediterranean',
                'modern' => 'Modern',
                'traditional' => 'Traditional',
                'other' => 'Other'
            ];
            return $style_labels[$style] ?? ucfirst(str_replace('_', ' ', $style));
        }
        
        return $style;
    }
}

/**
 * Year Built
 */
if (!function_exists('hph_bridge_get_year_built')) {
    function hph_bridge_get_year_built($listing_id) {
        if (function_exists('hph_get_listing_year_built')) {
            return hph_get_listing_year_built($listing_id);
        }
        
        $year = (int) get_field('year_built', $listing_id) ?: 0;
        
        // Demo data for lewes-colonial
        if (!$year) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $year = 1995;
            }
        }
        
        return $year;
    }
}

/**
 * Lot Size
 */
if (!function_exists('hph_bridge_get_lot_size')) {
    function hph_bridge_get_lot_size($listing_id, $formatted = false) {
        if (function_exists('hph_get_listing_lot_size')) {
            return hph_get_listing_lot_size($listing_id, $formatted);
        }
        
        // ACF NOW stores lot_size in ACRES (updated from square feet)
        $lot_size_acres = (float) get_field('lot_size', $listing_id) ?: 0;
        
        // Demo data (in acres)
        if (!$lot_size_acres) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $lot_size_acres = 0.2; // Demo: 0.2 acres
            }
        }
        
        if ($formatted && $lot_size_acres > 0) {
            // Always display as acres since that's how it's stored
            return number_format($lot_size_acres, 2) . ' acres';
        }
        
        return $lot_size_acres; // Always return acres as raw value
    }
}

/**
 * Garage Spaces
 */
if (!function_exists('hph_bridge_get_garage_spaces')) {
    function hph_bridge_get_garage_spaces($listing_id) {
        if (function_exists('hph_get_listing_garage_spaces')) {
            return hph_get_listing_garage_spaces($listing_id);
        }
        
        $garage = (int) get_field('garage_spaces', $listing_id) ?: 0;
        
        // Demo data
        if (!$garage) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $garage = 2;
            }
        }
        
        return $garage;
    }
}

/**
 * Full Bathrooms
 */
if (!function_exists('hph_bridge_get_full_baths')) {
    function hph_bridge_get_full_baths($listing_id) {
        if (function_exists('hph_get_listing_full_baths')) {
            return hph_get_listing_full_baths($listing_id);
        }
        
        return (int) get_field('full_baths', $listing_id) ?: 0;
    }
}

/**
 * Half Bathrooms
 */
if (!function_exists('hph_bridge_get_half_baths')) {
    function hph_bridge_get_half_baths($listing_id) {
        if (function_exists('hph_get_listing_half_baths')) {
            return hph_get_listing_half_baths($listing_id);
        }
        
        return (int) get_field('half_baths', $listing_id) ?: 0;
    }
}

/**
 * Listing Date with enhanced formatting
 */
if (!function_exists('hph_bridge_get_list_date')) {
    function hph_bridge_get_list_date($listing_id, $format = 'F j, Y') {
        if (function_exists('hph_get_listing_date')) {
            return hph_get_listing_date($listing_id, $format);
        }
        
        $date = get_field('list_date', $listing_id) ?: '';
        if (!$date) {
            return '';
        }
        
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '';
        }
        
        // Handle special formats
        switch ($format) {
            case 'relative':
                $days_ago = floor((time() - $timestamp) / DAY_IN_SECONDS);
                if ($days_ago <= 0) {
                    return 'Listed today';
                } elseif ($days_ago == 1) {
                    return 'Listed 1 day ago';
                } elseif ($days_ago < 7) {
                    return "Listed {$days_ago} days ago";
                } elseif ($days_ago < 30) {
                    $weeks = floor($days_ago / 7);
                    return $weeks == 1 ? 'Listed 1 week ago' : "Listed {$weeks} weeks ago";
                } else {
                    return date('M j, Y', $timestamp);
                }
                
            case 'formal':
                return date('F j, Y', $timestamp);
                
            case 'days_on_market':
                return max(0, floor((time() - $timestamp) / DAY_IN_SECONDS));
                
            default:
                return date($format, $timestamp);
        }
    }
}

/**
 * County
 */
if (!function_exists('hph_bridge_get_county')) {
    function hph_bridge_get_county($listing_id) {
        if (function_exists('hph_get_listing_county')) {
            return hph_get_listing_county($listing_id);
        }
        
        $county = get_field('county', $listing_id) ?: '';
        
        // Demo data
        if (!$county) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $county = 'Sussex County';
            }
        }
        
        return $county;
    }
}

/**
 * Unit Number
 */
if (!function_exists('hph_bridge_get_unit_number')) {
    function hph_bridge_get_unit_number($listing_id) {
        if (function_exists('hph_get_listing_unit')) {
            return hph_get_listing_unit($listing_id);
        }
        
        return get_field('unit_number', $listing_id) ?: '';
    }
}

/**
 * Virtual Tour URL
 */
if (!function_exists('hph_bridge_get_virtual_tour')) {
    function hph_bridge_get_virtual_tour($listing_id) {
        if (function_exists('hph_get_listing_virtual_tour')) {
            return hph_get_listing_virtual_tour($listing_id);
        }
        
        return get_field('virtual_tour_url', $listing_id) ?: '';
    }
}

/**
 * Virtual Tour Embed Code
 */
if (!function_exists('hph_bridge_get_virtual_tour_embed')) {
    function hph_bridge_get_virtual_tour_embed($listing_id) {
        if (function_exists('hph_get_listing_virtual_tour_embed')) {
            return hph_get_listing_virtual_tour_embed($listing_id);
        }
        
        return get_field('virtual_tour_embed', $listing_id) ?: '';
    }
}

/**
 * Video Tour URL
 */
if (!function_exists('hph_bridge_get_video_tour')) {
    function hph_bridge_get_video_tour($listing_id) {
        if (function_exists('hph_get_listing_video_tour')) {
            return hph_get_listing_video_tour($listing_id);
        }
        
        return get_field('video_tour_url', $listing_id) ?: '';
    }
}

/**
 * Drone Video URL
 */
if (!function_exists('hph_bridge_get_drone_video')) {
    function hph_bridge_get_drone_video($listing_id) {
        if (function_exists('hph_get_listing_drone_video')) {
            return hph_get_listing_drone_video($listing_id);
        }
        
        return get_field('drone_video_url', $listing_id) ?: '';
    }
}

/**
 * Photo Categories
 */
if (!function_exists('hph_bridge_get_photo_categories')) {
    function hph_bridge_get_photo_categories($listing_id) {
        if (function_exists('hph_get_listing_photo_categories')) {
            return hph_get_listing_photo_categories($listing_id);
        }
        
        $categories = get_field('photo_categories', $listing_id) ?: [];
        
        if (!is_array($categories)) {
            return [];
        }
        
        // Normalize and sort
        $normalized = [];
        foreach ($categories as $category) {
            if (is_array($category)) {
                $normalized[] = [
                    'name' => $category['category_name'] ?? '',
                    'photos' => $category['category_photos'] ?? [],
                    'order' => (int) ($category['category_order'] ?? 1)
                ];
            }
        }
        
        usort($normalized, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $normalized;
    }
}

/**
 * Listing Date with enhanced formatting
 */
if (!function_exists('hph_bridge_get_list_date')) {
    function hph_bridge_get_list_date($listing_id, $format = 'F j, Y') {
        if (function_exists('hph_get_listing_date')) {
            return hph_get_listing_date($listing_id, $format);
        }
        
        $date = get_field('list_date', $listing_id) ?: get_field('listing_date', $listing_id) ?: '';
        if (!$date) {
            // Fallback to post date
            $date = get_the_date('Y-m-d', $listing_id);
        }
        
        if (!$date) {
            return '';
        }
        
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return '';
        }
        
        // Handle special formats
        switch ($format) {
            case 'relative':
                $days_ago = floor((time() - $timestamp) / DAY_IN_SECONDS);
                if ($days_ago <= 0) {
                    return 'Listed today';
                } elseif ($days_ago == 1) {
                    return 'Listed 1 day ago';
                } elseif ($days_ago < 7) {
                    return "Listed {$days_ago} days ago";
                } elseif ($days_ago < 30) {
                    $weeks = floor($days_ago / 7);
                    return $weeks == 1 ? 'Listed 1 week ago' : "Listed {$weeks} weeks ago";
                } else {
                    return date('M j, Y', $timestamp);
                }
                
            case 'formal':
                return date('F j, Y', $timestamp);
                
            case 'days_on_market':
                return max(0, floor((time() - $timestamp) / DAY_IN_SECONDS));
                
            default:
                return date($format, $timestamp);
        }
    }
}

/**
 * Interior Features
 */
if (!function_exists('hph_bridge_get_interior_features')) {
    function hph_bridge_get_interior_features($listing_id) {
        if (function_exists('hph_get_listing_interior_features')) {
            return hph_get_listing_interior_features($listing_id);
        }
        
        return get_field('interior_features', $listing_id) ?: [];
    }
}

/**
 * Exterior Features
 */
if (!function_exists('hph_bridge_get_exterior_features')) {
    function hph_bridge_get_exterior_features($listing_id) {
        if (function_exists('hph_get_listing_exterior_features')) {
            return hph_get_listing_exterior_features($listing_id);
        }
        
        return get_field('exterior_features', $listing_id) ?: [];
    }
}

/**
 * Utility Features
 */
if (!function_exists('hph_bridge_get_utility_features')) {
    function hph_bridge_get_utility_features($listing_id) {
        if (function_exists('hph_get_listing_utility_features')) {
            return hph_get_listing_utility_features($listing_id);
        }
        
        return get_field('utility_features', $listing_id) ?: [];
    }
}

/**
 * Listing Agent
 */
if (!function_exists('hph_bridge_get_listing_agent')) {
    function hph_bridge_get_listing_agent($listing_id) {
        if (function_exists('hph_get_listing_agent')) {
            return hph_get_listing_agent($listing_id);
        }
        
        $agent_post = get_field('listing_agent', $listing_id);
        
        if (!$agent_post || !is_object($agent_post)) {
            return null;
        }
        
        $agent_id = $agent_post->ID;
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
 * Co-Listing Agent
 */
if (!function_exists('hph_bridge_get_co_listing_agent')) {
    function hph_bridge_get_co_listing_agent($listing_id) {
        if (function_exists('hph_get_listing_co_agent')) {
            return hph_get_listing_co_agent($listing_id);
        }
        
        $agent_post = get_field('co_listing_agent', $listing_id);
        
        if (!$agent_post || !is_object($agent_post)) {
            return null;
        }
        
        $agent_id = $agent_post->ID;
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
 * Buyer's Agent
 */
if (!function_exists('hph_bridge_get_buyer_agent')) {
    function hph_bridge_get_buyer_agent($listing_id) {
        if (function_exists('hph_get_listing_buyer_agent')) {
            return hph_get_listing_buyer_agent($listing_id);
        }
        
        $agent_post = get_field('buyer_agent', $listing_id);
        
        if (!$agent_post || !is_object($agent_post)) {
            return null;
        }
        
        $agent_id = $agent_post->ID;
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
 * Related City
 */
if (!function_exists('hph_bridge_get_related_city')) {
    function hph_bridge_get_related_city($listing_id) {
        if (function_exists('hph_get_listing_city_relation')) {
            return hph_get_listing_city_relation($listing_id);
        }
        
        $city_post = get_field('related_city', $listing_id);
        
        if (!$city_post || !is_object($city_post)) {
            return null;
        }
        
        return array(
            'id' => $city_post->ID,
            'name' => $city_post->post_title,
            'slug' => $city_post->post_name,
            'url' => get_permalink($city_post->ID)
        );
    }
}

/**
 * Related Community
 */
if (!function_exists('hph_bridge_get_related_community')) {
    function hph_bridge_get_related_community($listing_id) {
        if (function_exists('hph_get_listing_community_relation')) {
            return hph_get_listing_community_relation($listing_id);
        }
        
        $community_post = get_field('related_community', $listing_id);
        
        if (!$community_post || !is_object($community_post)) {
            return null;
        }
        
        return array(
            'id' => $community_post->ID,
            'name' => $community_post->post_title,
            'slug' => $community_post->post_name,
            'url' => get_permalink($community_post->ID)
        );
    }
}

/**
 * Coordinates
 */
if (!function_exists('hph_bridge_get_coordinates')) {
    function hph_bridge_get_coordinates($listing_id) {
        if (function_exists('hph_get_listing_coordinates')) {
            return hph_get_listing_coordinates($listing_id);
        }
        
        $latitude = get_field('latitude', $listing_id) ?: '';
        $longitude = get_field('longitude', $listing_id) ?: '';
        
        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'formatted' => $latitude . ',' . $longitude
        ];
    }
}

/**
 * School Information
 */
if (!function_exists('hph_bridge_get_schools')) {
    function hph_bridge_get_schools($listing_id) {
        if (function_exists('hph_get_listing_schools')) {
            return hph_get_listing_schools($listing_id);
        }
        
        return [
            'district' => get_field('school_district', $listing_id) ?: '',
            'elementary' => get_field('elementary_school', $listing_id) ?: '',
            'middle' => get_field('middle_school', $listing_id) ?: '',
            'high' => get_field('high_school', $listing_id) ?: ''
        ];
    }
}

/**
 * Mortgage Calculator Data
 */
if (!function_exists('hph_bridge_get_mortgage_data')) {
    function hph_bridge_get_mortgage_data($listing_id) {
        if (function_exists('hph_get_listing_mortgage_data')) {
            return hph_get_listing_mortgage_data($listing_id);
        }
        
        $price = hph_bridge_get_price($listing_id, false);
        
        return [
            'price' => $price,
            'down_payment_percent' => (float) (get_field('estimated_down_payment', $listing_id) ?: 20),
            'interest_rate' => (float) (get_field('estimated_interest_rate', $listing_id) ?: 6.5),
            'loan_term' => (int) (get_field('estimated_loan_term', $listing_id) ?: 30),
            'pmi_rate' => (float) (get_field('estimated_pmi_rate', $listing_id) ?: 0.5),
            'calculated_payment' => get_field('calculated_monthly_payment', $listing_id) ?: 0
        ];
    }
}

/**
 * Days on Market (calculated)
 */
if (!function_exists('hph_bridge_get_days_on_market')) {
    function hph_bridge_get_days_on_market($listing_id) {
        if (function_exists('hph_get_listing_days_on_market')) {
            return hph_get_listing_days_on_market($listing_id);
        }
        
        $list_date = get_field('list_date', $listing_id) ?: '';
        if (!$list_date) {
            return 0;
        }
        
        $list_timestamp = strtotime($list_date);
        $current_timestamp = current_time('timestamp');
        
        return max(0, floor(($current_timestamp - $list_timestamp) / DAY_IN_SECONDS));
    }
}

/**
 * Price per Square Foot (calculated)
 */
if (!function_exists('hph_bridge_get_price_per_sqft')) {
    function hph_bridge_get_price_per_sqft($listing_id, $formatted = false) {
        if (function_exists('hph_get_listing_price_per_sqft')) {
            return hph_get_listing_price_per_sqft($listing_id, $formatted);
        }
        
        $price = hph_bridge_get_price($listing_id, false);
        $sqft = hph_bridge_get_sqft($listing_id, false);
        
        if (!$price || !$sqft || $sqft <= 0) {
            return $formatted ? 'N/A' : 0;
        }
        
        $price_per_sqft = round($price / $sqft, 2);
        
        if ($formatted) {
            return '$' . number_format($price_per_sqft, 2) . '/sq ft';
        }
        
        return $price_per_sqft;
    }
}

/**
 * Get comprehensive listing data for templates
 */
if (!function_exists('hph_bridge_get_all_data')) {
    function hph_bridge_get_all_data($listing_id) {
        // Use plugin function if available
        if (function_exists('hph_get_all_listing_data')) {
            return hph_get_all_listing_data($listing_id);
        }
        
        // Fallback: build data array using bridge functions
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'price' => hph_bridge_get_price($listing_id, false),
            'formatted_price' => hph_bridge_get_price($listing_id, true),
            'status' => hph_bridge_get_status($listing_id),
            'mls_number' => hph_bridge_get_mls_number($listing_id),
            'address' => [
                'street' => hph_bridge_get_address($listing_id, 'street'),
                'city' => hph_bridge_get_address($listing_id, 'city'),
                'state' => hph_bridge_get_address($listing_id, 'state'),
                'zip' => hph_bridge_get_address($listing_id, 'zip'),
                'county' => hph_bridge_get_county($listing_id),
                'unit' => hph_bridge_get_unit_number($listing_id),
                'full' => hph_bridge_get_address($listing_id, 'full'),
            ],
            'property' => [
                'type' => hph_bridge_get_property_type($listing_id),
                'style' => hph_bridge_get_property_style($listing_id),
                'year_built' => hph_bridge_get_year_built($listing_id),
                'bedrooms' => hph_bridge_get_bedrooms($listing_id),
                'bathrooms' => hph_bridge_get_bathrooms($listing_id),
                'full_baths' => hph_bridge_get_full_baths($listing_id),
                'half_baths' => hph_bridge_get_half_baths($listing_id),
                'sqft' => hph_bridge_get_sqft($listing_id),
                'lot_size' => hph_bridge_get_lot_size($listing_id),
                'garage_spaces' => hph_bridge_get_garage_spaces($listing_id),
            ],
            'features' => [
                'interior' => hph_bridge_get_interior_features($listing_id),
                'exterior' => hph_bridge_get_exterior_features($listing_id),
                'utility' => hph_bridge_get_utility_features($listing_id),
            ],
            'media' => [
                'gallery' => hph_bridge_get_gallery($listing_id),
                'photo_categories' => hph_bridge_get_photo_categories($listing_id),
                'virtual_tour' => hph_bridge_get_virtual_tour($listing_id),
                'video_tour' => hph_bridge_get_video_tour($listing_id),
                'drone_video' => hph_bridge_get_drone_video($listing_id),
            ],
            'agents' => [
                'listing_agent' => hph_bridge_get_listing_agent($listing_id),
                'co_listing_agent' => hph_bridge_get_co_listing_agent($listing_id),
                'buyer_agent' => hph_bridge_get_buyer_agent($listing_id),
            ],
            'location' => [
                'coordinates' => hph_bridge_get_coordinates($listing_id),
                'schools' => hph_bridge_get_schools($listing_id),
                'related_city' => hph_bridge_get_related_city($listing_id),
                'related_community' => hph_bridge_get_related_community($listing_id),
            ],
            'calculated' => [
                'days_on_market' => hph_bridge_get_days_on_market($listing_id),
                'price_per_sqft' => hph_bridge_get_price_per_sqft($listing_id),
                'mortgage_data' => hph_bridge_get_mortgage_data($listing_id),
            ]
        ];
    }
}

// =============================================================================
// ENHANCED FORMATTING BRIDGE FUNCTIONS
// =============================================================================

/**
 * Bridge for enhanced price formatting
 */
if (!function_exists('hph_bridge_get_price_formatted')) {
    function hph_bridge_get_price_formatted($listing_id, $format = 'standard') {
        if (function_exists('hph_get_listing_price')) {
            return hph_get_listing_price($listing_id, true, $format);
        }
        
        // Fallback using existing bridge function
        $price = hph_bridge_get_price($listing_id, false);
        
        if (!$price || $price <= 0) {
            return 'Price on Request';
        }
        
        switch ($format) {
            case 'short':
                if ($price >= 1000000) {
                    return '$' . number_format($price / 1000000, 1) . 'M';
                } elseif ($price >= 1000) {
                    return '$' . number_format($price / 1000, 0) . 'K';
                }
                break;
            case 'standard':
            default:
                return '$' . number_format($price);
        }
        
        return '$' . number_format($price);
    }
}

/**
 * Bridge for enhanced square footage formatting
 */
if (!function_exists('hph_bridge_get_sqft_formatted')) {
    function hph_bridge_get_sqft_formatted($listing_id, $format_type = 'standard') {
        if (function_exists('hph_get_listing_sqft')) {
            return hph_get_listing_sqft($listing_id, true, $format_type);
        }
        
        // Fallback to basic ACF
        $sqft = (int) get_field('square_footage', $listing_id);
        
        // Demo data for lewes-colonial if no ACF data
        if (!$sqft) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $sqft = 1800;
            }
        }
        
        if ($sqft > 0) {
            switch ($format_type) {
                case 'short':
                    if ($sqft >= 1000) {
                        return number_format($sqft / 1000, 1) . 'K';
                    }
                    return number_format($sqft);
                case 'standard':
                default:
                    return number_format($sqft) . ' sq ft';
            }
        }
        return '';
    }
}

/**
 * Bridge for enhanced lot size formatting
 */
if (!function_exists('hph_bridge_get_lot_size_formatted')) {
    function hph_bridge_get_lot_size_formatted($listing_id, $format = 'auto') {
        if (function_exists('hph_get_listing_lot_size')) {
            return hph_get_listing_lot_size($listing_id, true, $format);
        }
        
        // ACF NOW stores lot_size in ACRES
        $lot_size_acres = (float) get_field('lot_size', $listing_id) ?: 0;
        
        // Demo data for lewes-colonial if no ACF data
        if (!$lot_size_acres) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $lot_size_acres = 0.2; // 0.2 acres
            }
        }
        
        if ($lot_size_acres > 0) {
            switch ($format) {
                case 'acres':
                    // Display as acres
                    return number_format($lot_size_acres, 2) . ' acres';
                    
                case 'sqft':
                    // Convert acres to square feet for display
                    $sqft = $lot_size_acres * 43560;
                    return number_format($sqft) . ' sq ft';
                    
                case 'auto':
                default:
                    // Auto: Show acres for all lots since data is stored in acres
                    // Only show sq ft if lot is very small (less than 0.1 acres)
                    if ($lot_size_acres < 0.1) {
                        $sqft = $lot_size_acres * 43560;
                        return number_format($sqft) . ' sq ft';
                    } else {
                        return number_format($lot_size_acres, 2) . ' acres';
                    }
            }
        }
        return '';
    }
}

/**
 * Bridge for enhanced bathroom formatting
 */
if (!function_exists('hph_bridge_get_bathrooms_formatted')) {
    function hph_bridge_get_bathrooms_formatted($listing_id, $style = 'combined') {
        if (function_exists('hph_get_listing_bathrooms')) {
            return hph_get_listing_bathrooms($listing_id, true, true);
        }
        
        // Fallback using existing bridge function
        $total_baths = hph_bridge_get_bathrooms($listing_id, true);
        
        if ($total_baths > 0) {
            if ($total_baths == floor($total_baths)) {
                $count = (int) $total_baths;
                return $count . ' ' . _n('Bath', 'Baths', $count, 'happy-place');
            } else {
                return $total_baths . ' Baths';
            }
        }
        
        return '';
    }
}

/**
 * Bridge for enhanced features with formatting
 */
if (!function_exists('hph_bridge_get_features_formatted')) {
    function hph_bridge_get_features_formatted($listing_id, $type = 'all') {
        if (function_exists('hph_get_listing_features')) {
            return hph_get_listing_features($listing_id, $type, true);
        }
        
        // Fallback to basic features
        $features = hph_bridge_get_features($listing_id, $type);
        
        if (!is_array($features)) {
            return [];
        }
        
        $formatted = [];
        
        // Handle different types of feature data
        if ($type === 'all') {
            // If features is a nested array by category
            foreach ($features as $category => $category_features) {
                if (is_array($category_features)) {
                    foreach ($category_features as $feature) {
                        $formatted[] = [
                            'name' => ucwords(str_replace(['_', '-'], ' ', $feature)),
                            'category' => ucwords(str_replace(['_', '-'], ' ', $category)),
                            'icon' => hph_get_feature_icon($feature)
                        ];
                    }
                }
            }
        } else {
            // Single category
            foreach ($features as $feature) {
                $formatted[] = [
                    'name' => ucwords(str_replace(['_', '-'], ' ', $feature)),
                    'category' => ucwords(str_replace(['_', '-'], ' ', $type)),
                    'icon' => hph_get_feature_icon($feature)
                ];
            }
        }
        
        return $formatted;
    }
}

/**
 * Get icon for feature
 */
if (!function_exists('hph_get_feature_icon')) {
    function hph_get_feature_icon($feature) {
        $icons = [
            'hardwood_floors' => 'fas fa-tree',
            'updated_kitchen' => 'fas fa-utensils',
            'fireplace' => 'fas fa-fire',
            'crown_molding' => 'fas fa-border-style',
            'garage' => 'fas fa-warehouse',
            'deck' => 'fas fa-th-large',
            'landscaped_yard' => 'fas fa-seedling',
            'driveway' => 'fas fa-road',
            'central_air' => 'fas fa-snowflake',
            'gas_heat' => 'fas fa-fire',
            'washer_dryer_hookup' => 'fas fa-tshirt',
            'pool' => 'fas fa-swimming-pool',
            'patio' => 'fas fa-th-large',
            'basement' => 'fas fa-layer-group',
            'attic' => 'fas fa-home',
        ];
        
        $feature_key = strtolower(str_replace([' ', '-'], '_', $feature));
        return $icons[$feature_key] ?? 'fas fa-check';
    }
}

/**
 * Bridge for zip code with city integration
 */
if (!function_exists('hph_bridge_get_zip_code')) {
    function hph_bridge_get_zip_code($listing_id, $include_city = false, $format = 'standard') {
        if (function_exists('hph_get_listing_zip_code')) {
            return hph_get_listing_zip_code($listing_id, $include_city, $format);
        }
        
        // Fallback to basic ACF
        $zip = get_field('zip_code', $listing_id);
        
        // Demo data for lewes-colonial if no ACF data
        if (!$zip) {
            $post = get_post($listing_id);
            if ($post && $post->post_name === 'lewes-colonial') {
                $zip = '19958';
            }
        }
        
        return $zip ?: '';
    }
}

/**
 * Bridge for city information from zip code
 */
if (!function_exists('hph_bridge_get_city_from_zip')) {
    function hph_bridge_get_city_from_zip($zip_code) {
        if (function_exists('hph_get_city_from_zip')) {
            return hph_get_city_from_zip($zip_code);
        }
        
        // Fallback - return null if plugin function not available
        return null;
    }
}

/**
 * Bridge for enhanced price formatting
 */
if (!function_exists('hph_bridge_get_price_formatted')) {
    function hph_bridge_get_price_formatted($listing_id, $format = 'standard') {
        if (function_exists('hph_get_listing_price')) {
            return hph_get_listing_price($listing_id, true, $format);
        }
        
        // Fallback using existing bridge function
        return hph_bridge_get_price($listing_id, true);
    }
}

/**
 * Bridge for enhanced lot size formatting
 */
if (!function_exists('hph_bridge_get_lot_size_formatted')) {
    function hph_bridge_get_lot_size_formatted($listing_id, $format = 'auto') {
        if (function_exists('hph_get_listing_lot_size')) {
            return hph_get_listing_lot_size($listing_id, true, $format);
        }
        
        // Fallback to basic ACF
        $lot_size = (float) get_field('lot_size', $listing_id);
        if ($lot_size > 0) {
            return number_format($lot_size, 2) . ' acres';
        }
        return '';
    }
}

/**
 * Bridge for enhanced bathroom formatting
 */
if (!function_exists('hph_bridge_get_bathrooms_formatted')) {
    function hph_bridge_get_bathrooms_formatted($listing_id, $style = 'combined') {
        if (function_exists('hph_get_listing_bathrooms')) {
            return hph_get_listing_bathrooms($listing_id, true, true);
        }
        
        // Fallback using existing bridge function
        return hph_bridge_get_bathrooms($listing_id, true);
    }
}

/**
 * Bridge for enhanced features with formatting
 */
if (!function_exists('hph_bridge_get_features_formatted')) {
    function hph_bridge_get_features_formatted($listing_id, $type = 'all') {
        if (function_exists('hph_get_listing_features')) {
            return hph_get_listing_features($listing_id, $type, true);
        }
        
        // Fallback to basic features
        $features = hph_bridge_get_features($listing_id, $type);
        if (is_array($features)) {
            // Apply basic formatting
            return array_map(function($feature) {
                return ucwords(str_replace('_', ' ', $feature));
            }, $features);
        }
        
        return [];
    }
}

// =============================================================================
// CITY DATA BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get city data with Google API integration
 */
if (!function_exists('hph_bridge_get_city_data')) {
    function hph_bridge_get_city_data($city_id) {
        if (function_exists('hph_get_city_data')) {
            return hph_get_city_data($city_id);
        }
        
        // Fallback to basic ACF data
        $data = [];
        
        if ($city_id) {
            $data = [
                'id' => $city_id,
                'title' => get_the_title($city_id),
                'tagline' => get_field('city_tagline', $city_id),
                'intro_text' => get_field('city_intro_text', $city_id),
                'gallery' => get_field('city_gallery', $city_id),
                'map' => get_field('city_google_map', $city_id),
                'facts' => get_field('city_facts', $city_id),
                'highlights' => get_field('city_highlights', $city_id),
                'places' => hph_bridge_get_city_places($city_id),
                'nearby_cities' => get_field('nearby_cities', $city_id)
            ];
        }
        
        return $data;
    }
}

/**
 * Get city places (manual or API)
 */
if (!function_exists('hph_bridge_get_city_places')) {
    function hph_bridge_get_city_places($city_id) {
        if (function_exists('hph_get_city_places')) {
            return hph_get_city_places($city_id);
        }
        
        $places_source = get_field('places_source', $city_id);
        
        if ($places_source === 'api') {
            return get_field('places_api', $city_id) ?: [];
        } else {
            return get_field('places_manual', $city_id) ?: [];
        }
    }
}

/**
 * Get formatted city places by category
 */
if (!function_exists('hph_bridge_get_city_places_by_category')) {
    function hph_bridge_get_city_places_by_category($city_id) {
        if (function_exists('hph_get_city_places_by_category')) {
            return hph_get_city_places_by_category($city_id);
        }
        
        $places = hph_bridge_get_city_places($city_id);
        $categorized = [];
        
        if (!empty($places)) {
            foreach ($places as $place) {
                $category = '';
                
                // Handle both API and manual places
                if (isset($place['place_category'])) {
                    $category = $place['place_category'];
                } elseif (isset($place['post_type']) && $place['post_type'] === 'local-place') {
                    $category = get_field('place_category', $place['ID']) ?: 'General';
                } else {
                    $category = 'General';
                }
                
                if (!isset($categorized[$category])) {
                    $categorized[$category] = [];
                }
                
                $categorized[$category][] = $place;
            }
        }
        
        return $categorized;
    }
}

/**
 * Get city coordinates for mapping
 */
if (!function_exists('hph_bridge_get_city_coordinates')) {
    function hph_bridge_get_city_coordinates($city_id) {
        if (function_exists('hph_get_city_coordinates')) {
            return hph_get_city_coordinates($city_id);
        }
        
        $map_data = get_field('city_google_map', $city_id);
        
        if (!empty($map_data) && isset($map_data['lat']) && isset($map_data['lng'])) {
            return [
                'lat' => (float)$map_data['lat'],
                'lng' => (float)$map_data['lng'],
                'zoom' => $map_data['zoom'] ?? 12
            ];
        }
        
        return null;
    }
}

/**
 * Get city API status and last update
 */
if (!function_exists('hph_bridge_get_city_api_status')) {
    function hph_bridge_get_city_api_status($city_id) {
        if (function_exists('hph_get_city_api_status')) {
            return hph_get_city_api_status($city_id);
        }
        
        return [
            'status' => get_field('city_api_status', $city_id) ?: 'pending',
            'last_updated' => get_field('city_data_last_updated', $city_id),
            'google_place_id' => get_field('city_google_place_id', $city_id)
        ];
    }
}

/**
 * Format city highlights with icons
 */
if (!function_exists('hph_bridge_get_city_highlights_formatted')) {
    function hph_bridge_get_city_highlights_formatted($city_id) {
        if (function_exists('hph_get_city_highlights_formatted')) {
            return hph_get_city_highlights_formatted($city_id);
        }
        
        $highlights = get_field('city_highlights', $city_id);
        $formatted = [];
        
        if (!empty($highlights)) {
            foreach ($highlights as $highlight) {
                $icon_html = '';
                
                if (!empty($highlight['icon'])) {
                    $icon_html = wp_get_attachment_image($highlight['icon']['ID'], 'thumbnail', false, [
                        'class' => 'city-highlight-icon',
                        'alt' => $highlight['label'] ?? ''
                    ]);
                }
                
                $formatted[] = [
                    'label' => $highlight['label'] ?? '',
                    'description' => $highlight['description'] ?? '',
                    'icon' => $highlight['icon'] ?? null,
                    'icon_html' => $icon_html
                ];
            }
        }
        
        return $formatted;
    }
}

// =============================================================================
// HERO TEMPLATE BRIDGE FUNCTIONS
// =============================================================================

/**
 * Enhanced hero data function with compact optimizations
 */
if (!function_exists('hph_get_hero_data')) {
    function hph_get_hero_data($listing_id) {
        // Performance cache
        $cache_key = 'hph_hero_compact_' . $listing_id;
        $cached = wp_cache_get($cache_key, 'hph_listings');
        
        if ($cached !== false && !WP_DEBUG) {
            return $cached;
        }
        
        // Get optimized gallery (limit for performance)
        $gallery = hph_hero_get_gallery($listing_id, 10); // Max 10 images for hero
        
        // Get essential address data using bridge functions
        $full_address = hph_bridge_get_address($listing_id, 'full') ?: get_the_title($listing_id);
        $street_full = hph_bridge_get_address($listing_id, 'street');
        $city = hph_bridge_get_address($listing_id, 'city');
        $state = hph_bridge_get_address($listing_id, 'state');
        $zip = hph_bridge_get_zip_code($listing_id);
        
        // Build display addresses
        $main_address = $street_full ?: get_the_title($listing_id);
        $location_parts = array_filter([$city, $state, $zip]);
        $sub_address = !empty($location_parts) ? implode(', ', $location_parts) : '';
        
        $address = [
            'full' => $full_address,
            'street' => $street_full,
            'main_display' => $main_address,
            'sub_display' => $sub_address,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'city_state' => trim($city . ', ' . $state, ', ') ?: 'Prime Location',
            'property_type' => hph_bridge_get_property_type($listing_id) ?: 'Single Family Home'
        ];
        
        // Get price data using bridge functions
        $price_raw = hph_bridge_get_price($listing_id, false);
        $price = [
            'raw' => $price_raw,
            'formatted' => hph_bridge_get_price($listing_id, true) ?: 'Price on Request'
        ];
        
        // Calculate price per sqft using bridge functions
        $sqft = hph_bridge_get_sqft($listing_id, false);
        if ($price_raw > 0 && $sqft > 0) {
            $price_per_sqft = round($price_raw / $sqft);
            $price['per_sqft_formatted'] = '$' . number_format($price_per_sqft) . '/sq ft';
        }
        
        // Get essential stats using bridge functions
        $bedrooms = hph_bridge_get_bedrooms($listing_id);
        $bathrooms = hph_bridge_get_bathrooms($listing_id, false);
        
        $stats = [];
        
        if ($bedrooms > 0) {
            $stats['bedrooms'] = [
                'value' => $bedrooms,
                'label' => _n('Bedroom', 'Bedrooms', $bedrooms, 'happy-place'),
                'icon' => 'fas fa-bed'
            ];
        }
        
        if ($bathrooms > 0) {
            $stats['bathrooms'] = [
                'value' => $bathrooms == floor($bathrooms) ? (int) $bathrooms : $bathrooms,
                'label' => _n('Bathroom', 'Bathrooms', $bathrooms, 'happy-place'),
                'icon' => 'fas fa-bath'
            ];
        }
        
        if ($sqft > 0) {
            $stats['sqft'] = [
                'value' => number_format($sqft),
                'label' => 'Square Feet',
                'icon' => 'fas fa-ruler-combined'
            ];
        }
        
        // Add additional stats if available
        $lot_size = hph_bridge_get_lot_size($listing_id, false);
        if ($lot_size) {
            $stats['lot_size'] = [
                'value' => hph_bridge_get_lot_size($listing_id, true),
                'label' => 'Lot Size',
                'icon' => 'fas fa-expand-arrows-alt'
            ];
        }
        
        $year_built = hph_bridge_get_year_built($listing_id);
        if ($year_built) {
            $stats['year_built'] = [
                'value' => $year_built,
                'label' => 'Year Built',
                'icon' => 'fas fa-calendar-alt'
            ];
        }
        
        // Status using bridge functions
        $status_value = hph_bridge_get_status($listing_id) ?: 'active';
        $status = [
            'value' => $status_value,
            'display' => ucfirst(str_replace(['_', '-'], ' ', $status_value)),
            'class' => strtolower(str_replace(['_', ' '], '-', $status_value))
        ];
        
        // Meta data
        $meta = [
            'mls_number' => hph_bridge_get_mls_number($listing_id),
            'days_on_market' => hph_calculate_days_on_market($listing_id)
        ];
        
        $hero_data = [
            'id' => $listing_id,
            'images' => $gallery,
            'address' => $address,
            'price' => $price,
            'stats' => $stats,
            'status' => $status,
            'meta' => $meta
        ];
        
        // Cache for 30 minutes
        wp_cache_set($cache_key, $hero_data, 'hph_listings', 30 * MINUTE_IN_SECONDS);
        
        return $hero_data;
    }
}

/**
 * Helper function for days on market calculation
 */
if (!function_exists('hph_calculate_days_on_market')) {
    function hph_calculate_days_on_market($listing_id) {
        $list_date = hph_bridge_get_list_date($listing_id, 'Y-m-d');
        if (!$list_date) {
            // Fallback to direct ACF check
            $list_date = get_field('list_date', $listing_id);
        }
        
        if (!$list_date) {
            return 0;
        }
        
        $list_timestamp = is_numeric($list_date) ? $list_date : strtotime($list_date);
        return max(0, floor((time() - $list_timestamp) / DAY_IN_SECONDS));
    }
}

/**
 * Enhanced gallery function with limit for hero
 */
if (!function_exists('hph_hero_get_gallery')) {
    function hph_hero_get_gallery($listing_id, $limit = 10) {
        // Use bridge function first
        $gallery = hph_bridge_get_gallery($listing_id);
        
        // Limit for performance
        if (!empty($gallery) && $limit > 0) {
            $gallery = array_slice($gallery, 0, $limit);
        }
        
        return $gallery;
    }
}

/**
 * Get listing price per square foot with enhanced calculation
 */
if (!function_exists('hph_get_listing_price_per_sqft')) {
    function hph_get_listing_price_per_sqft($listing_id, $formatted = false) {
        return hph_bridge_get_price_per_sqft($listing_id, $formatted);
    }
}

/**
 * Generic listing field getter with fallback
 */
if (!function_exists('hph_get_listing_field')) {
    function hph_get_listing_field($listing_id, $field_name, $default = '') {
        // Use plugin function if available
        if (function_exists('hph_get_field_value')) {
            return hph_get_field_value($listing_id, $field_name, $default);
        }
        
        // Fallback to ACF
        $value = get_field($field_name, $listing_id);
        
        // Handle specific field mappings and transformations
        switch ($field_name) {
            case 'description':
                return $value ?: get_the_content($listing_id) ?: $default;
                
            case 'property_type':
                return hph_bridge_get_property_type($listing_id) ?: $default;
                
            case 'year_built':
                return hph_bridge_get_year_built($listing_id) ?: $default;
                
            case 'virtual_tour_url':
                return hph_bridge_get_virtual_tour($listing_id) ?: $default;
                
            case 'coordinates':
                return hph_bridge_get_coordinates($listing_id) ?: $default;
                
            case 'walk_score':
                return (int) ($value ?: $default);
                
            case 'nearby_places':
                // Try various field names for nearby places
                $places = $value ?: get_field('local_places', $listing_id) ?: get_field('points_of_interest', $listing_id);
                return is_array($places) ? $places : ($default ?: []);
                
            case 'tour_preview':
                return $value ?: get_field('virtual_tour_preview', $listing_id) ?: $default;
                
            case 'tour_type':
                return $value ?: get_field('virtual_tour_type', $listing_id) ?: $default;
                
            case 'tour_title':
                return $value ?: get_field('virtual_tour_title', $listing_id) ?: $default;
                
            default:
                return $value ?: $default;
        }
    }
}

/**
 * Get nearby cities with distances
 */
if (!function_exists('hph_bridge_get_nearby_cities')) {
    function hph_bridge_get_nearby_cities($city_id, $limit = 5) {
        if (function_exists('hph_get_nearby_cities')) {
            return hph_get_nearby_cities($city_id, $limit);
        }
        
        $nearby = get_field('nearby_cities', $city_id);
        
        if (!empty($nearby)) {
            // Sort by distance if available
            usort($nearby, function($a, $b) {
                $dist_a = $a['distance_miles'] ?? 999;
                $dist_b = $b['distance_miles'] ?? 999;
                return $dist_a <=> $dist_b;
            });
            
            if ($limit > 0) {
                $nearby = array_slice($nearby, 0, $limit);
            }
        }
        
        return $nearby ?: [];
    }
}

/**
 * Get scheduled open houses for a listing
 */
if (!function_exists('hph_bridge_get_open_houses')) {
    function hph_bridge_get_open_houses($listing_id, $future_only = true) {
        if (function_exists('hph_get_listing_open_houses')) {
            return hph_get_listing_open_houses($listing_id, $future_only);
        }
        
        // Query open house posts
        $args = array(
            'post_type' => 'open-house',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'related_listing',
                    'value' => $listing_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'open_house_status',
                    'value' => 'scheduled',
                    'compare' => '='
                )
            ),
            'post_status' => 'publish'
        );
        
        if ($future_only) {
            $args['meta_query'][] = array(
                'key' => 'open_house_date',
                'value' => date('Y-m-d'),
                'compare' => '>='
            );
        }
        
        $args['meta_key'] = 'open_house_date';
        $args['orderby'] = 'meta_value';
        $args['order'] = 'ASC';
        
        $open_houses = get_posts($args);
        $formatted_open_houses = array();
        
        foreach ($open_houses as $open_house) {
            $date = get_field('open_house_date', $open_house->ID);
            $start_time = get_field('start_time', $open_house->ID);
            $end_time = get_field('end_time', $open_house->ID);
            $hosting_agent = get_field('hosting_agent', $open_house->ID);
            $special_instructions = get_field('special_instructions', $open_house->ID);
            $rsvp_required = get_field('rsvp_required', $open_house->ID);
            $max_attendees = get_field('max_attendees', $open_house->ID);
            $contact_info = get_field('contact_info', $open_house->ID);
            $virtual_tour_link = get_field('virtual_tour_link', $open_house->ID);
            
            $formatted_open_houses[] = array(
                'id' => $open_house->ID,
                'title' => $open_house->post_title,
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'hosting_agent' => $hosting_agent,
                'special_instructions' => $special_instructions,
                'rsvp_required' => $rsvp_required,
                'max_attendees' => $max_attendees,
                'contact_info' => $contact_info,
                'virtual_tour_link' => $virtual_tour_link,
                'formatted_date' => $date ? date('F j, Y', strtotime($date)) : '',
                'formatted_start_time' => $start_time ? date('g:i A', strtotime($start_time)) : '',
                'formatted_end_time' => $end_time ? date('g:i A', strtotime($end_time)) : '',
                'datetime' => $date && $start_time ? strtotime($date . ' ' . $start_time) : 0
            );
        }
        
        return $formatted_open_houses;
    }
}

/**
 * Get next scheduled open house for a listing
 */
if (!function_exists('hph_bridge_get_next_open_house')) {
    function hph_bridge_get_next_open_house($listing_id) {
        if (function_exists('hph_get_next_listing_open_house')) {
            return hph_get_next_listing_open_house($listing_id);
        }
        
        $open_houses = hph_bridge_get_open_houses($listing_id, true);
        
        if (empty($open_houses)) {
            return null;
        }
        
        // Find the next upcoming open house
        $now = time();
        foreach ($open_houses as $open_house) {
            if ($open_house['datetime'] > $now) {
                return $open_house;
            }
        }
        
        return null;
    }
}

/**
 * Check if listing has open houses
 */
if (!function_exists('hph_bridge_has_open_houses')) {
    function hph_bridge_has_open_houses($listing_id) {
        if (function_exists('hph_listing_has_open_houses')) {
            return hph_listing_has_open_houses($listing_id);
        }
        
        $open_houses = hph_bridge_get_open_houses($listing_id, true);
        return !empty($open_houses);
    }
}

// =============================================================================
// AGENT BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get agent basic information
 */
if (!function_exists('hph_bridge_get_agent_name')) {
    function hph_bridge_get_agent_name($agent_id) {
        if (function_exists('hph_get_agent_name')) {
            return hph_get_agent_name($agent_id);
        }
        
        if (!$agent_id) return '';
        
        $agent = get_post($agent_id);
        return $agent ? $agent->post_title : '';
    }
}

/**
 * Get agent phone number
 */
if (!function_exists('hph_bridge_get_agent_phone')) {
    function hph_bridge_get_agent_phone($agent_id) {
        if (function_exists('hph_get_agent_phone')) {
            return hph_get_agent_phone($agent_id);
        }
        
        if (!$agent_id) return '';
        
        // Try multiple possible field names
        $phone_fields = ['phone', 'agent_phone', 'contact_phone', 'phone_number'];
        foreach ($phone_fields as $field) {
            $phone = get_field($field, $agent_id);
            if ($phone) return $phone;
        }
        
        return '';
    }
}

/**
 * Get agent email
 */
if (!function_exists('hph_bridge_get_agent_email')) {
    function hph_bridge_get_agent_email($agent_id) {
        if (function_exists('hph_get_agent_email')) {
            return hph_get_agent_email($agent_id);
        }
        
        if (!$agent_id) return '';
        
        // Try multiple possible field names
        $email_fields = ['email', 'agent_email', 'contact_email', 'email_address'];
        foreach ($email_fields as $field) {
            $email = get_field($field, $agent_id);
            if ($email) return $email;
        }
        
        return '';
    }
}

/**
 * Get agent bio/description
 */
if (!function_exists('hph_bridge_get_agent_bio')) {
    function hph_bridge_get_agent_bio($agent_id) {
        if (function_exists('hph_get_agent_bio')) {
            return hph_get_agent_bio($agent_id);
        }
        
        if (!$agent_id) return '';
        
        // Try bio field first, then post content
        $bio = get_field('bio', $agent_id) ?: get_field('agent_bio', $agent_id);
        if ($bio) return $bio;
        
        $agent = get_post($agent_id);
        return $agent ? $agent->post_content : '';
    }
}

/**
 * Get agent photo/avatar
 */
if (!function_exists('hph_bridge_get_agent_photo')) {
    function hph_bridge_get_agent_photo($agent_id, $size = 'medium') {
        if (function_exists('hph_get_agent_photo')) {
            return hph_get_agent_photo($agent_id, $size);
        }
        
        if (!$agent_id) return '';
        
        // Try various photo field names
        $photo_fields = ['photo', 'agent_photo', 'profile_photo', 'headshot', 'avatar'];
        foreach ($photo_fields as $field) {
            $photo = get_field($field, $agent_id);
            if ($photo) {
                if (is_array($photo)) {
                    return $photo['sizes'][$size] ?? $photo['url'];
                }
                return wp_get_attachment_image_url($photo, $size);
            }
        }
        
        // Fallback to featured image
        return get_the_post_thumbnail_url($agent_id, $size);
    }
}

/**
 * Get agent title/position
 */
if (!function_exists('hph_bridge_get_agent_title')) {
    function hph_bridge_get_agent_title($agent_id) {
        if (function_exists('hph_get_agent_title')) {
            return hph_get_agent_title($agent_id);
        }
        
        if (!$agent_id) return '';
        
        $title_fields = ['title', 'agent_title', 'job_title', 'position'];
        foreach ($title_fields as $field) {
            $title = get_field($field, $agent_id);
            if ($title) return $title;
        }
        
        return '';
    }
}

/**
 * Get agent license number
 */
if (!function_exists('hph_bridge_get_agent_license')) {
    function hph_bridge_get_agent_license($agent_id) {
        if (function_exists('hph_get_agent_license')) {
            return hph_get_agent_license($agent_id);
        }
        
        if (!$agent_id) return '';
        
        $license_fields = ['license_number', 'agent_license', 'license', 'real_estate_license'];
        foreach ($license_fields as $field) {
            $license = get_field($field, $agent_id);
            if ($license) return $license;
        }
        
        return '';
    }
}

/**
 * Get agent years of experience
 */
if (!function_exists('hph_bridge_get_agent_experience')) {
    function hph_bridge_get_agent_experience($agent_id) {
        if (function_exists('hph_get_agent_experience')) {
            return hph_get_agent_experience($agent_id);
        }
        
        if (!$agent_id) return 0;
        
        $experience_fields = ['years_experience', 'experience', 'years_in_business'];
        foreach ($experience_fields as $field) {
            $experience = get_field($field, $agent_id);
            if ($experience) return intval($experience);
        }
        
        return 0;
    }
}

/**
 * Get agent specialties
 */
if (!function_exists('hph_bridge_get_agent_specialties')) {
    function hph_bridge_get_agent_specialties($agent_id) {
        if (function_exists('hph_get_agent_specialties')) {
            return hph_get_agent_specialties($agent_id);
        }
        
        if (!$agent_id) return [];
        
        // Try taxonomy first
        $specialties = wp_get_post_terms($agent_id, 'agent_specialty', ['fields' => 'names']);
        if (!is_wp_error($specialties) && !empty($specialties)) {
            return $specialties;
        }
        
        // Fallback to ACF field
        $specialties_field = get_field('specialties', $agent_id);
        if (is_array($specialties_field)) {
            return $specialties_field;
        }
        
        return [];
    }
}

/**
 * Get agent social media links
 */
if (!function_exists('hph_bridge_get_agent_social')) {
    function hph_bridge_get_agent_social($agent_id) {
        if (function_exists('hph_get_agent_social')) {
            return hph_get_agent_social($agent_id);
        }
        
        if (!$agent_id) return [];
        
        $social = [];
        $social_fields = [
            'facebook' => 'facebook_url',
            'twitter' => 'twitter_url', 
            'instagram' => 'instagram_url',
            'linkedin' => 'linkedin_url',
            'youtube' => 'youtube_url'
        ];
        
        foreach ($social_fields as $platform => $field) {
            $url = get_field($field, $agent_id) ?: get_field($platform, $agent_id);
            if ($url) {
                $social[$platform] = $url;
            }
        }
        
        return $social;
    }
}

/**
 * Get agent website URL
 */
if (!function_exists('hph_bridge_get_agent_website')) {
    function hph_bridge_get_agent_website($agent_id) {
        if (function_exists('hph_get_agent_website')) {
            return hph_get_agent_website($agent_id);
        }
        
        if (!$agent_id) return '';
        
        $website_fields = ['website', 'website_url', 'personal_website'];
        foreach ($website_fields as $field) {
            $website = get_field($field, $agent_id);
            if ($website) return $website;
        }
        
        return '';
    }
}

/**
 * Get agent office/brokerage information
 */
if (!function_exists('hph_bridge_get_agent_office')) {
    function hph_bridge_get_agent_office($agent_id) {
        if (function_exists('hph_get_agent_office')) {
            return hph_get_agent_office($agent_id);
        }
        
        if (!$agent_id) return [];
        
        return [
            'name' => get_field('office_name', $agent_id) ?: get_field('brokerage', $agent_id),
            'address' => get_field('office_address', $agent_id),
            'phone' => get_field('office_phone', $agent_id),
            'website' => get_field('office_website', $agent_id)
        ];
    }
}

/**
 * Get agent listings count
 */
if (!function_exists('hph_bridge_get_agent_listings_count')) {
    function hph_bridge_get_agent_listings_count($agent_id, $status = 'active') {
        if (function_exists('hph_get_agent_listings_count')) {
            return hph_get_agent_listings_count($agent_id, $status);
        }
        
        if (!$agent_id) return 0;
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids'
        ];
        
        if ($status !== 'all') {
            $args['meta_query'][] = [
                'key' => 'listing_status',
                'value' => $status,
                'compare' => '='
            ];
        }
        
        $listings = get_posts($args);
        return count($listings);
    }
}

/**
 * Get agent recent listings
 */
if (!function_exists('hph_bridge_get_agent_listings')) {
    function hph_bridge_get_agent_listings($agent_id, $limit = 10, $status = 'active') {
        if (function_exists('hph_get_agent_listings')) {
            return hph_get_agent_listings($agent_id, $limit, $status);
        }
        
        if (!$agent_id) return [];
        
        $args = [
            'post_type' => 'listing',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        if ($status !== 'all') {
            $args['meta_query'][] = [
                'key' => 'listing_status',
                'value' => $status,
                'compare' => '='
            ];
        }
        
        return get_posts($args);
    }
}

/**
 * Get agent testimonials/reviews
 */
if (!function_exists('hph_bridge_get_agent_testimonials')) {
    function hph_bridge_get_agent_testimonials($agent_id, $limit = 5) {
        if (function_exists('hph_get_agent_testimonials')) {
            return hph_get_agent_testimonials($agent_id, $limit);
        }
        
        if (!$agent_id) return [];
        
        // Try testimonials post type first
        $args = [
            'post_type' => 'testimonial',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'related_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $testimonials = get_posts($args);
        
        // Fallback to ACF repeater field
        if (empty($testimonials)) {
            $testimonials_field = get_field('testimonials', $agent_id);
            if (is_array($testimonials_field)) {
                return array_slice($testimonials_field, 0, $limit);
            }
        }
        
        return $testimonials;
    }
}

/**
 * Get comprehensive agent data
 */
if (!function_exists('hph_bridge_get_agent_data')) {
    function hph_bridge_get_agent_data($agent_id) {
        if (function_exists('hph_get_agent_data')) {
            return hph_get_agent_data($agent_id);
        }
        
        if (!$agent_id) return null;
        
        return [
            'id' => $agent_id,
            'name' => hph_bridge_get_agent_name($agent_id),
            'title' => hph_bridge_get_agent_title($agent_id),
            'phone' => hph_bridge_get_agent_phone($agent_id),
            'email' => hph_bridge_get_agent_email($agent_id),
            'bio' => hph_bridge_get_agent_bio($agent_id),
            'photo' => hph_bridge_get_agent_photo($agent_id),
            'license' => hph_bridge_get_agent_license($agent_id),
            'experience' => hph_bridge_get_agent_experience($agent_id),
            'specialties' => hph_bridge_get_agent_specialties($agent_id),
            'social' => hph_bridge_get_agent_social($agent_id),
            'website' => hph_bridge_get_agent_website($agent_id),
            'office' => hph_bridge_get_agent_office($agent_id),
            'listings_count' => hph_bridge_get_agent_listings_count($agent_id),
            'recent_listings' => hph_bridge_get_agent_listings($agent_id, 5),
            'testimonials' => hph_bridge_get_agent_testimonials($agent_id, 3)
        ];
    }
}

// =============================================================================
// FINANCIAL BRIDGE FUNCTIONS  
// =============================================================================

/**
 * Get property taxes
 */
if (!function_exists('hph_bridge_get_property_tax')) {
    function hph_bridge_get_property_tax($listing_id) {
        if (function_exists('hph_get_property_tax')) {
            return hph_get_property_tax($listing_id);
        }
        
        if (!$listing_id) return 0;
        
        $tax_fields = ['property_tax', 'annual_property_tax', 'yearly_taxes', 'tax_amount'];
        foreach ($tax_fields as $field) {
            $tax = get_field($field, $listing_id);
            if ($tax) return floatval($tax);
        }
        
        return 0;
    }
}

/**
 * Get HOA fees
 */
if (!function_exists('hph_bridge_get_hoa_fees')) {
    function hph_bridge_get_hoa_fees($listing_id) {
        if (function_exists('hph_get_hoa_fees')) {
            return hph_get_hoa_fees($listing_id);
        }
        
        if (!$listing_id) return 0;
        
        $hoa_fields = ['hoa_fees', 'hoa_monthly', 'monthly_hoa', 'hoa_amount'];
        foreach ($hoa_fields as $field) {
            $hoa = get_field($field, $listing_id);
            if ($hoa) return floatval($hoa);
        }
        
        return 0;
    }
}

/**
 * Get insurance estimate
 */
if (!function_exists('hph_bridge_get_insurance_estimate')) {
    function hph_bridge_get_insurance_estimate($listing_id) {
        if (function_exists('hph_get_insurance_estimate')) {
            return hph_get_insurance_estimate($listing_id);
        }
        
        if (!$listing_id) return 0;
        
        // Try field first
        $insurance = get_field('insurance_estimate', $listing_id) ?: get_field('annual_insurance', $listing_id);
        if ($insurance) return floatval($insurance);
        
        // Calculate estimate based on property value
        $price = hph_bridge_get_price($listing_id);
        if ($price) {
            // Rough estimate: 0.3% of property value annually
            return $price * 0.003;
        }
        
        return 0;
    }
}

/**
 * Calculate monthly mortgage payment
 */
if (!function_exists('hph_bridge_calculate_mortgage')) {
    function hph_bridge_calculate_mortgage($listing_id, $down_payment = 0, $interest_rate = 6.5, $term_years = 30) {
        if (function_exists('hph_calculate_mortgage')) {
            return hph_calculate_mortgage($listing_id, $down_payment, $interest_rate, $term_years);
        }
        
        $price = hph_bridge_get_price($listing_id);
        if (!$price) return 0;
        
        $loan_amount = $price - $down_payment;
        if ($loan_amount <= 0) return 0;
        
        $monthly_rate = ($interest_rate / 100) / 12;
        $num_payments = $term_years * 12;
        
        if ($monthly_rate == 0) {
            return $loan_amount / $num_payments;
        }
        
        $monthly_payment = $loan_amount * (
            ($monthly_rate * pow(1 + $monthly_rate, $num_payments)) /
            (pow(1 + $monthly_rate, $num_payments) - 1)
        );
        
        return round($monthly_payment, 2);
    }
}

/**
 * Get total monthly costs (PITI + HOA)
 */
if (!function_exists('hph_bridge_get_monthly_costs')) {
    function hph_bridge_get_monthly_costs($listing_id, $down_payment = 0, $interest_rate = 6.5, $term_years = 30) {
        if (function_exists('hph_get_monthly_costs')) {
            return hph_get_monthly_costs($listing_id, $down_payment, $interest_rate, $term_years);
        }
        
        $mortgage = hph_bridge_calculate_mortgage($listing_id, $down_payment, $interest_rate, $term_years);
        $property_tax = hph_bridge_get_property_tax($listing_id) / 12; // Monthly
        $insurance = hph_bridge_get_insurance_estimate($listing_id) / 12; // Monthly
        $hoa = hph_bridge_get_hoa_fees($listing_id);
        
        return [
            'mortgage' => $mortgage,
            'property_tax' => $property_tax,
            'insurance' => $insurance,
            'hoa' => $hoa,
            'total' => $mortgage + $property_tax + $insurance + $hoa
        ];
    }
}

/**
 * Get price per square foot
 */
if (!function_exists('hph_bridge_get_price_per_sqft')) {
    function hph_bridge_get_price_per_sqft($listing_id) {
        if (function_exists('hph_get_price_per_sqft')) {
            return hph_get_price_per_sqft($listing_id);
        }
        
        $price = hph_bridge_get_price($listing_id);
        $sqft = hph_bridge_get_square_footage($listing_id);
        
        if ($price && $sqft && $sqft > 0) {
            return round($price / $sqft, 2);
        }
        
        return 0;
    }
}

/**
 * Get cost per year estimates
 */
if (!function_exists('hph_bridge_get_annual_costs')) {
    function hph_bridge_get_annual_costs($listing_id) {
        if (function_exists('hph_get_annual_costs')) {
            return hph_get_annual_costs($listing_id);
        }
        
        return [
            'property_tax' => hph_bridge_get_property_tax($listing_id),
            'insurance' => hph_bridge_get_insurance_estimate($listing_id),
            'hoa' => hph_bridge_get_hoa_fees($listing_id) * 12,
            'maintenance' => hph_bridge_get_price($listing_id) * 0.01, // 1% of home value estimate
        ];
    }
}

// =============================================================================
// ADDITIONAL LISTING BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get listing virtual tour URL
 */
if (!function_exists('hph_bridge_get_virtual_tour')) {
    function hph_bridge_get_virtual_tour($listing_id) {
        if (function_exists('hph_get_virtual_tour')) {
            return hph_get_virtual_tour($listing_id);
        }
        
        if (!$listing_id) return '';
        
        $tour_fields = ['virtual_tour', 'virtual_tour_url', '3d_tour', 'tour_link'];
        foreach ($tour_fields as $field) {
            $tour = get_field($field, $listing_id);
            if ($tour) return $tour;
        }
        
        return '';
    }
}

/**
 * Get listing video tour URL
 */
if (!function_exists('hph_bridge_get_video_tour')) {
    function hph_bridge_get_video_tour($listing_id) {
        if (function_exists('hph_get_video_tour')) {
            return hph_get_video_tour($listing_id);
        }
        
        if (!$listing_id) return '';
        
        $video_fields = ['video_tour', 'video_tour_url', 'listing_video', 'video_link'];
        foreach ($video_fields as $field) {
            $video = get_field($field, $listing_id);
            if ($video) return $video;
        }
        
        return '';
    }
}

/**
 * Get listing floor plan images
 */
if (!function_exists('hph_bridge_get_floor_plans')) {
    function hph_bridge_get_floor_plans($listing_id) {
        if (function_exists('hph_get_floor_plans')) {
            return hph_get_floor_plans($listing_id);
        }
        
        if (!$listing_id) return [];
        
        $floor_plans = get_field('floor_plans', $listing_id);
        if (is_array($floor_plans)) {
            return $floor_plans;
        }
        
        return [];
    }
}

/**
 * Get listing disclosure documents
 */
if (!function_exists('hph_bridge_get_disclosures')) {
    function hph_bridge_get_disclosures($listing_id) {
        if (function_exists('hph_get_disclosures')) {
            return hph_get_disclosures($listing_id);
        }
        
        if (!$listing_id) return [];
        
        $disclosures = get_field('disclosures', $listing_id) ?: get_field('disclosure_documents', $listing_id);
        if (is_array($disclosures)) {
            return $disclosures;
        }
        
        return [];
    }
}

/**
 * Check if listing allows pets
 */
if (!function_exists('hph_bridge_allows_pets')) {
    function hph_bridge_allows_pets($listing_id) {
        if (function_exists('hph_allows_pets')) {
            return hph_allows_pets($listing_id);
        }
        
        if (!$listing_id) return false;
        
        $pet_fields = ['pets_allowed', 'allows_pets', 'pet_friendly'];
        foreach ($pet_fields as $field) {
            $pets = get_field($field, $listing_id);
            if ($pets !== null) return (bool) $pets;
        }
        
        return false;
    }
}

/**
 * Get listing pet policy details
 */
if (!function_exists('hph_bridge_get_pet_policy')) {
    function hph_bridge_get_pet_policy($listing_id) {
        if (function_exists('hph_get_pet_policy')) {
            return hph_get_pet_policy($listing_id);
        }
        
        if (!$listing_id) return '';
        
        return get_field('pet_policy', $listing_id) ?: get_field('pet_details', $listing_id) ?: '';
    }
}

/**
 * Get listing appliances included
 */
if (!function_exists('hph_bridge_get_appliances')) {
    function hph_bridge_get_appliances($listing_id) {
        if (function_exists('hph_get_appliances')) {
            return hph_get_appliances($listing_id);
        }
        
        if (!$listing_id) return [];
        
        $appliances = get_field('appliances', $listing_id) ?: get_field('included_appliances', $listing_id);
        if (is_array($appliances)) {
            return $appliances;
        }
        
        return [];
    }
}

/**
 * Get listing utilities information
 */
if (!function_exists('hph_bridge_get_utilities')) {
    function hph_bridge_get_utilities($listing_id) {
        if (function_exists('hph_get_utilities')) {
            return hph_get_utilities($listing_id);
        }
        
        if (!$listing_id) return [];
        
        return [
            'electric' => get_field('electric_utility', $listing_id),
            'gas' => get_field('gas_utility', $listing_id),
            'water' => get_field('water_utility', $listing_id),
            'sewer' => get_field('sewer_utility', $listing_id),
            'internet' => get_field('internet_utility', $listing_id),
            'trash' => get_field('trash_utility', $listing_id)
        ];
    }
}

/**
 * Get listing construction details
 */
if (!function_exists('hph_bridge_get_construction_details')) {
    function hph_bridge_get_construction_details($listing_id) {
        if (function_exists('hph_get_construction_details')) {
            return hph_get_construction_details($listing_id);
        }
        
        if (!$listing_id) return [];
        
        return [
            'style' => get_field('architectural_style', $listing_id),
            'materials' => get_field('construction_materials', $listing_id),
            'foundation' => get_field('foundation_type', $listing_id),
            'roof' => get_field('roof_type', $listing_id),
            'heating' => get_field('heating_type', $listing_id),
            'cooling' => get_field('cooling_type', $listing_id)
        ];
    }
}