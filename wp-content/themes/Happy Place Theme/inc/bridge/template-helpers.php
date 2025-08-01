<?php
/**
 * Format price for display
 * 
 * @param mixed $price Price value
 * @return string Formatted price
 */
if (!function_exists('hph_format_price')) {
    function hph_format_price($price) {
        if (empty($price) || !is_numeric($price)) {
            return 'Contact for Price';
        }
        
        $price = floatval($price);
        
        if ($price >= 1000000) {
            return '$' . number_format_i18n($price / 1000000, 1) . 'M';
        } elseif ($price >= 1000) {
            return '$' . number_format_i18n($price / 1000, 0) . 'K';
        }
        
        return '$' . number_format_i18n($price, 0);
    }
}

/**
 * Get template-specific listing data (optimized for display)
 * 
 * @param int $listing_id Listing ID
 * @return array Template-ready data
 */
if (!function_exists('hph_get_template_listing_data')) {
    function hph_get_template_listing_data($listing_id) {
        $raw_data = hph_bridge_get_listing_data($listing_id);
        
        // Return sanitized, display-ready data
        return [
            'id' => $listing_id,
            'title' => esc_html($raw_data['title']),
            'url' => esc_url($raw_data['url']),
            'description' => wp_kses_post($raw_data['description']),
        'price' => esc_html($raw_data['price_formatted']),
        'address' => esc_html($raw_data['full_address']),
        'status' => esc_html($raw_data['status_formatted']),
        'status_class' => esc_attr($raw_data['status_class']),
        'bedrooms' => esc_html($raw_data['bedrooms']),
        'bathrooms' => esc_html($raw_data['bathrooms_formatted']),
        'square_feet' => esc_html($raw_data['square_feet_formatted']),
        'lot_size' => esc_html($raw_data['lot_size_formatted']),
        'property_type' => esc_html($raw_data['property_type']),
        'mls_number' => esc_html($raw_data['mls_number']),
        'days_on_market' => intval($raw_data['days_on_market'] ?? 0),
        'is_new' => (bool) $raw_data['is_new'],
        'is_featured' => (bool) $raw_data['is_featured'],
        'featured_image' => esc_url($raw_data['featured_image']),
        'gallery' => $raw_data['gallery'] ?? [],
        'features' => $raw_data['features'] ?? [],
        'agent' => $raw_data['agent'] ?? []
    ];
    }
}

/**
 * Fallback listing data when plugin is inactive
 * 
 * @param int $listing_id Listing ID
 * @return array Fallback data
 */
if (!function_exists('hph_fallback_get_listing_data')) {
    function hph_fallback_get_listing_data($listing_id) {
        $post = get_post($listing_id);
        
        if (!$post) {
            return [];
        }
        
        return [
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            'url' => get_permalink($listing_id),
            'description' => get_the_excerpt($listing_id) ?: wp_trim_words(get_the_content(null, false, $listing_id), 25),
            'price' => 0,
            'price_formatted' => 'Contact for Price',
            'address' => '',
            'full_address' => '',
        'status' => 'Available',
        'status_formatted' => 'Available',
        'status_class' => 'available',
        'bedrooms' => '',
        'bathrooms' => '',
        'bathrooms_formatted' => '',
        'square_feet' => '',
        'square_feet_formatted' => '',
        'lot_size' => '',
        'lot_size_formatted' => '',
        'property_type' => 'Single Family Home',
        'mls_number' => '',
        'featured_image' => get_the_post_thumbnail_url($listing_id, 'full'),
        'gallery' => has_post_thumbnail($listing_id) ? [[
            'url' => get_the_post_thumbnail_url($listing_id, 'full'),
            'alt' => get_the_title($listing_id)
        ]] : [],
        'features' => [],
        'agent' => [],
        'days_on_market' => 0,
        'is_new' => false,
        'is_featured' => false
    ];
    }
}

if (!function_exists('hph_fallback_get_hero_data')) {
    function hph_fallback_get_hero_data($listing_id) {
        return [
            'title' => get_the_title($listing_id),
            'price' => get_post_meta($listing_id, 'price', true) ?: 'Contact for Price',
            'address' => get_post_meta($listing_id, 'address', true) ?: '',
            'featured_image' => get_the_post_thumbnail_url($listing_id, 'full')
        ];
    }
}

if (!function_exists('hph_fallback_get_gallery_data')) {
    function hph_fallback_get_gallery_data($listing_id) {
        $images = [];
        if (has_post_thumbnail($listing_id)) {
            $images[] = [
                'url' => get_the_post_thumbnail_url($listing_id, 'full'),
                'alt' => get_the_title($listing_id)
            ];
        }
        return $images;
    }
}

if (!function_exists('hph_fallback_get_property_details')) {
    function hph_fallback_get_property_details($listing_id) {
        return [
            'bedrooms' => get_post_meta($listing_id, 'bedrooms', true) ?: 'N/A',
            'bathrooms' => get_post_meta($listing_id, 'bathrooms', true) ?: 'N/A',
            'square_feet' => get_post_meta($listing_id, 'square_feet', true) ?: 'N/A',
            'lot_size' => get_post_meta($listing_id, 'lot_size', true) ?: 'N/A',
            'year_built' => get_post_meta($listing_id, 'year_built', true) ?: 'N/A',
            'property_type' => get_post_meta($listing_id, 'property_type', true) ?: 'Single Family Home'
        ];
    }
}

if (!function_exists('hph_fallback_get_features')) {
    function hph_fallback_get_features($listing_id) {
        return [
            'interior' => ['Hardwood Floors', 'Updated Kitchen', 'Walk-in Closets'],
            'exterior' => ['Private Yard', 'Garage', 'Patio'],
            'community' => ['Swimming Pool', 'Fitness Center', 'Clubhouse']
        ];
    }
}

if (!function_exists('hph_fallback_get_agent_data')) {
    function hph_fallback_get_agent_data($listing_id) {
        return [
            'id' => 1,
            'name' => 'Real Estate Agent',
            'email' => 'agent@example.com',
            'phone' => '(555) 123-4567',
            'bio' => 'Experienced real estate professional.',
            'photo' => get_template_directory_uri() . '/assets/images/default-agent.jpg'
        ];
    }
}

if (!function_exists('hph_fallback_get_financial_data')) {
    function hph_fallback_get_financial_data($listing_id) {
        $price = get_post_meta($listing_id, 'price', true);
        $price_numeric = is_numeric($price) ? intval($price) : 350000;
        
        return [
            'price' => $price_numeric,
            'down_payment_percent' => 20,
            'interest_rate' => 6.5,
            'loan_term' => 30,
            'property_taxes' => round($price_numeric * 0.012 / 12),
            'insurance' => round($price_numeric * 0.003 / 12),
            'hoa_fees' => 0
        ];
    }
}

if (!function_exists('hph_fallback_get_similar_listings')) {
    function hph_fallback_get_similar_listings($listing_id, $count = 3) {
        $similar = get_posts([
            'post_type' => 'listing',
            'posts_per_page' => $count,
            'exclude' => [$listing_id],
            'meta_key' => 'price',
            'orderby' => 'rand'
        ]);
        
        return array_map(function($post) {
            return [
                'id' => $post->ID,
                'title' => $post->post_title,
                'price' => get_post_meta($post->ID, 'price', true) ?: 'Contact for Price',
                'image' => get_the_post_thumbnail_url($post->ID, 'medium'),
                'url' => get_permalink($post->ID)
            ];
        }, $similar);
    }
}

if (!function_exists('hph_emergency_fallback_data')) {
    function hph_emergency_fallback_data($listing_id) {
        return [
            'id' => $listing_id,
            'title' => 'Property Listing',
            'description' => 'Property details will be available soon.',
            'price' => 'Contact for Price',
            'status' => 'Available'
        ];
    }
}

/**
 * Simple template part loader for fallback
 * 
 * @param string $slug Template slug
 * @param string $name Template name variation
 * @param array $args Template arguments
 */
if (!function_exists('hph_get_template_part')) {
    function hph_get_template_part($slug, $name = null, $args = []) {
        // Simple fallback that just uses WordPress get_template_part
        if (!empty($args)) {
            set_query_var('template_args', $args);
        }
        
        if ($name) {
            get_template_part($slug, $name);
        } else {
            get_template_part($slug);
        }
    }
}

/**
 * Missing function fallbacks for template-bridge.php compatibility
 */
if (!function_exists('hph_format_features')) {
    function hph_format_features($features) {
        if (!is_array($features)) return [];
        return array_map('esc_html', $features);
    }
}

if (!function_exists('hph_get_listing_address')) {
    function hph_get_listing_address($listing_id, $formatted = false) {
        $address = get_post_meta($listing_id, 'address', true);
        return $formatted ? esc_html($address) : $address;
    }
}

if (!function_exists('hph_get_listing_data')) {
    function hph_get_listing_data($listing_id) {
        // Use our existing bridge function
        return function_exists('hph_bridge_get_listing_data') 
            ? hph_bridge_get_listing_data($listing_id)
            : hph_fallback_get_listing_data($listing_id);
    }
}

if (!function_exists('hph_get_listing_bedrooms')) {
    function hph_get_listing_bedrooms($listing_id) {
        return get_post_meta($listing_id, 'bedrooms', true) ?: 'N/A';
    }
}

if (!function_exists('hph_get_listing_bathrooms')) {
    function hph_get_listing_bathrooms($listing_id) {
        return get_post_meta($listing_id, 'bathrooms', true) ?: 'N/A';
    }
}

if (!function_exists('hph_get_listing_sqft')) {
    function hph_get_listing_sqft($listing_id, $formatted = false) {
        $sqft = get_post_meta($listing_id, 'square_feet', true);
        return $formatted && $sqft ? number_format($sqft) . ' sq ft' : $sqft;
    }
}

if (!function_exists('hph_get_listing_gallery')) {
    function hph_get_listing_gallery($listing_id) {
        $gallery = get_field('property_gallery', $listing_id);
        return is_array($gallery) ? $gallery : [];
    }
}

if (!function_exists('hph_get_agent_contact')) {
    function hph_get_agent_contact($agent_id) {
        return [
            'email' => get_post_meta($agent_id, 'email', true),
            'phone' => get_post_meta($agent_id, 'phone', true),
        ];
    }
}

if (!function_exists('hph_get_agent_name')) {
    function hph_get_agent_name($agent_id) {
        return get_the_title($agent_id);
    }
}

if (!function_exists('hph_get_agent_bio')) {
    function hph_get_agent_bio($agent_id, $excerpt = false) {
        return $excerpt ? get_the_excerpt($agent_id) : get_the_content(null, false, $agent_id);
    }
}

if (!function_exists('hph_get_agent_photo')) {
    function hph_get_agent_photo($agent_id) {
        return get_the_post_thumbnail_url($agent_id, 'medium');
    }
}

if (!function_exists('hph_get_agent_specialties')) {
    function hph_get_agent_specialties($agent_id) {
        $terms = get_the_terms($agent_id, 'agent_specialty');
        return is_array($terms) ? wp_list_pluck($terms, 'name') : [];
    }
}

if (!function_exists('hph_get_agent_listings_count')) {
    function hph_get_agent_listings_count($agent_id) {
        $count = get_posts([
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => 'listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        return count($count);
    }
}

if (!function_exists('hph_asset_manager')) {
    function hph_asset_manager() {
        return class_exists('HappyPlace\\Core\\Asset_Manager') 
            ? HappyPlace\Core\Asset_Manager::class 
            : null;
    }
}

/**
 * Get property types for forms and filters
 * 
 * @return array Property types
 */
if (!function_exists('hph_get_property_types')) {
    function hph_get_property_types() {
        $terms = get_terms([
            'taxonomy' => 'property_type',
            'hide_empty' => false,
        ]);
        
        if (is_wp_error($terms)) {
            return [];
        }
        
        $types = [];
        foreach ($terms as $term) {
            $types[$term->slug] = $term->name;
        }
        
        return $types;
    }
}

/**
 * Missing fallback functions for component integration
 */

if (!function_exists('hph_fallback_get_hero_data')) {
    function hph_fallback_get_hero_data($listing_id) {
        return [
            'title' => get_the_title($listing_id),
            'price' => 'Contact for Price',
            'address' => get_post_meta($listing_id, '_listing_address', true) ?: 'Address not available',
            'bedrooms' => get_post_meta($listing_id, '_listing_bedrooms', true) ?: 0,
            'bathrooms' => get_post_meta($listing_id, '_listing_bathrooms', true) ?: 0,
            'square_footage' => get_post_meta($listing_id, '_listing_square_footage', true) ?: 0,
            'status' => 'Available',
            'status_class' => 'available',
            'images' => has_post_thumbnail($listing_id) ? [
                [
                    'id' => get_post_thumbnail_id($listing_id),
                    'url' => get_the_post_thumbnail_url($listing_id, 'full'),
                    'alt' => get_the_title($listing_id)
                ]
            ] : []
        ];
    }
}

if (!function_exists('hph_fallback_get_financial_data')) {
    function hph_fallback_get_financial_data($listing_id) {
        $price = get_post_meta($listing_id, '_listing_price', true) ?: 0;
        
        return [
            'price' => $price,
            'down_payment_percent' => 20,
            'interest_rate' => 6.5,
            'loan_term_years' => 30,
            'property_tax_rate' => 1.2,
            'insurance_annual' => 1200,
            'hoa_monthly' => get_post_meta($listing_id, '_listing_hoa_fee', true) ?: 0,
            'pmi_rate' => 0.5
        ];
    }
}

if (!function_exists('hph_fallback_get_gallery_data')) {
    function hph_fallback_get_gallery_data($listing_id) {
        $images = [];
        
        // Featured image
        if (has_post_thumbnail($listing_id)) {
            $images[] = [
                'id' => get_post_thumbnail_id($listing_id),
                'url' => get_the_post_thumbnail_url($listing_id, 'full'),
                'thumbnail' => get_the_post_thumbnail_url($listing_id, 'thumbnail'),
                'alt' => get_the_title($listing_id)
            ];
        }
        
        // Try to get gallery from ACF or post meta
        $gallery = get_post_meta($listing_id, '_listing_gallery', true);
        if (is_array($gallery)) {
            foreach ($gallery as $image_id) {
                $images[] = [
                    'id' => $image_id,
                    'url' => wp_get_attachment_image_url($image_id, 'full'),
                    'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true)
                ];
            }
        }
        
        return [
            'images' => $images,
            'total_count' => count($images),
            'featured_image' => !empty($images) ? $images[0] : null
        ];
    }
}

if (!function_exists('hph_fallback_get_property_details')) {
    function hph_fallback_get_property_details($listing_id) {
        return [
            'bedrooms' => get_post_meta($listing_id, '_listing_bedrooms', true) ?: 0,
            'bathrooms' => get_post_meta($listing_id, '_listing_bathrooms', true) ?: 0,
            'half_bathrooms' => get_post_meta($listing_id, '_listing_half_bathrooms', true) ?: 0,
            'square_footage' => get_post_meta($listing_id, '_listing_square_footage', true) ?: 0,
            'lot_size' => get_post_meta($listing_id, '_listing_lot_size', true) ?: '',
            'year_built' => get_post_meta($listing_id, '_listing_year_built', true) ?: '',
            'garage_spaces' => get_post_meta($listing_id, '_listing_garage_spaces', true) ?: 0,
            'property_type' => get_post_meta($listing_id, '_listing_property_type', true) ?: 'House',
            'mls_number' => get_post_meta($listing_id, '_listing_mls_number', true) ?: '',
            'hoa_fee' => get_post_meta($listing_id, '_listing_hoa_fee', true) ?: 0
        ];
    }
}

if (!function_exists('hph_fallback_get_features')) {
    function hph_fallback_get_features($listing_id) {
        $features = get_post_meta($listing_id, '_listing_features', true);
        
        if (!is_array($features)) {
            $features = [];
        }
        
        return [
            'interior_features' => $features,
            'exterior_features' => get_post_meta($listing_id, '_listing_exterior_features', true) ?: [],
            'appliances' => get_post_meta($listing_id, '_listing_appliances', true) ?: [],
            'amenities' => get_post_meta($listing_id, '_listing_amenities', true) ?: []
        ];
    }
}

if (!function_exists('hph_fallback_get_agent_data')) {
    function hph_fallback_get_agent_data($listing_id) {
        $author_id = get_post_field('post_author', $listing_id);
        $author = get_userdata($author_id);
        
        return [
            'id' => $author_id,
            'name' => $author ? $author->display_name : 'Unknown Agent',
            'email' => $author ? $author->user_email : '',
            'phone' => get_user_meta($author_id, 'phone', true) ?: '',
            'license_number' => get_user_meta($author_id, 'license_number', true) ?: '',
            'bio' => get_user_meta($author_id, 'bio', true) ?: '',
            'photo' => get_avatar_url($author_id),
            'listings_count' => count_user_posts($author_id, 'listing')
        ];
    }
}

if (!function_exists('hph_fallback_get_agent_stats')) {
    function hph_fallback_get_agent_stats($agent_id) {
        // Get agent's listings
        $listings = get_posts([
            'post_type' => 'listing',
            'meta_query' => [
                [
                    'key' => '_listing_agent',
                    'value' => $agent_id,
                    'compare' => '='
                ]
            ],
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);
        
        // Calculate stats
        $active_listings = count($listings);
        $sales_this_year = get_post_meta($agent_id, '_agent_sales_count', true) ?: 0;
        $experience_years = get_post_meta($agent_id, '_agent_experience_years', true) ?: 0;
        $avg_price = 0;
        
        // Calculate average listing price
        if (!empty($listings)) {
            $total_price = 0;
            $price_count = 0;
            
            foreach ($listings as $listing) {
                $price = get_post_meta($listing->ID, '_listing_price', true);
                if ($price && is_numeric($price)) {
                    $total_price += $price;
                    $price_count++;
                }
            }
            
            if ($price_count > 0) {
                $avg_price = $total_price / $price_count;
            }
        }
        
        return [
            'active_listings' => $active_listings,
            'sales_this_year' => $sales_this_year,
            'experience_years' => $experience_years,
            'average_price' => $avg_price,
            'total_value' => $active_listings * $avg_price,
            'client_satisfaction' => get_post_meta($agent_id, '_agent_satisfaction_rating', true) ?: 0
        ];
    }
}
?>