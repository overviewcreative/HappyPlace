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
        // Use the existing webpack asset system from HPH_Theme
        $hph_theme = HPH_Theme::instance();
        
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
// DATA RETRIEVAL BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get formatted status display with enhanced mapping
 */
if (!function_exists('hph_get_status_display')) {
    function hph_get_status_display($listing_id) {
        $status = get_field('status', $listing_id) ?: get_field('listing_status', $listing_id) ?: 'active';
        
        $status_map = [
            'active' => ['display' => __('Active', 'happy-place'), 'class' => 'active'],
            'pending' => ['display' => __('Pending', 'happy-place'), 'class' => 'pending'],
            'sold' => ['display' => __('Sold', 'happy-place'), 'class' => 'sold'],
            'off_market' => ['display' => __('Off Market', 'happy-place'), 'class' => 'off-market'],
            'coming_soon' => ['display' => __('Coming Soon', 'happy-place'), 'class' => 'coming-soon'],
            'under_contract' => ['display' => __('Under Contract', 'happy-place'), 'class' => 'pending'],
            'withdrawn' => ['display' => __('Withdrawn', 'happy-place'), 'class' => 'off-market'],
        ];
        
        return $status_map[strtolower($status)] ?? $status_map['active'];
    }
}

/**
 * Get comprehensive formatted address
 */
if (!function_exists('hph_get_formatted_address')) {
    function hph_get_formatted_address($listing_id) {
        $address_parts = [
            'street' => get_field('address', $listing_id) ?: get_field('street_address', $listing_id) ?: '',
            'city' => get_field('city', $listing_id) ?: '',
            'state' => get_field('state', $listing_id) ?: '',
            'zip' => get_field('zip', $listing_id) ?: get_field('zip_code', $listing_id) ?: get_field('postal_code', $listing_id) ?: '',
        ];
        
        $formatted_parts = array_filter($address_parts);
        
        if (count($formatted_parts) >= 2) {
            // Standard US format: Street, City, State ZIP
            return trim(implode(', ', array_filter([
                $formatted_parts['street'],
                $formatted_parts['city'],
                trim($formatted_parts['state'] . ' ' . $formatted_parts['zip'])
            ])));
        }
        
        return implode(', ', $formatted_parts);
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
            'bedrooms' => get_field('bedrooms', $listing_id) ?: get_field('beds', $listing_id) ?: '',
            'bathrooms' => get_field('bathrooms', $listing_id) ?: get_field('baths', $listing_id) ?: '',
            'square_feet' => get_field('square_feet', $listing_id) ?: get_field('sqft', $listing_id) ?: get_field('square_footage', $listing_id) ?: '',
            'lot_size' => get_field('lot_size', $listing_id) ?: get_field('lot_square_feet', $listing_id) ?: get_field('lot_acres', $listing_id) ?: '',
            'year_built' => get_field('year_built', $listing_id) ?: get_field('built_year', $listing_id) ?: '',
            'garage' => get_field('garage', $listing_id) ?: get_field('garage_spaces', $listing_id) ?: get_field('parking', $listing_id) ?: '',
            'mls_number' => get_field('mls_number', $listing_id) ?: get_field('mls_id', $listing_id) ?: get_field('mls', $listing_id) ?: '',
            'property_type' => get_field('property_type', $listing_id) ?: hph_get_property_type_display($listing_id),
            'stories' => get_field('stories', $listing_id) ?: get_field('levels', $listing_id) ?: '',
            'foundation' => get_field('foundation', $listing_id) ?: '',
            'roof' => get_field('roof', $listing_id) ?: get_field('roof_type', $listing_id) ?: '',
            'flooring' => get_field('flooring', $listing_id) ?: get_field('floor_type', $listing_id) ?: '',
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
        
        // Get features from different field types
        $interior = get_field('interior_features', $listing_id) ?: get_field('features_interior', $listing_id) ?: [];
        $exterior = get_field('exterior_features', $listing_id) ?: get_field('features_exterior', $listing_id) ?: [];
        $utility = get_field('utility_features', $listing_id) ?: get_field('features_utility', $listing_id) ?: [];
        
        // Handle different field formats (string, array, repeater)
        $features = [
            'interior' => hph_normalize_features($interior),
            'exterior' => hph_normalize_features($exterior),
            'utility' => hph_normalize_features($utility),
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
        
        // Try first gallery image
        if (!$image_url) {
            $gallery = get_field('gallery', $listing_id) ?: get_field('photo_gallery', $listing_id) ?: get_field('images', $listing_id);
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