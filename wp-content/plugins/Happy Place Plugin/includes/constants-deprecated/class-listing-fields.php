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
        
        // Return structured data array with all available fields
        return array(
            'id' => $listing_id,
            'title' => get_the_title($listing_id),
            
            // Core pricing and status
            'price' => hph_get_listing_price($listing_id, false),
            'formatted_price' => hph_get_listing_price($listing_id, true),
            'status' => hph_get_listing_status($listing_id),
            'mls_number' => hph_get_listing_mls($listing_id),
            'list_date' => hph_get_listing_date($listing_id),
            'days_on_market' => hph_get_listing_days_on_market($listing_id),
            
            // Address components
            'address' => array(
                'street' => hph_get_listing_address($listing_id, 'street'),
                'city' => hph_get_listing_address($listing_id, 'city'),
                'state' => hph_get_listing_address($listing_id, 'state'),
                'zip' => hph_get_listing_address($listing_id, 'zip'),
                'county' => hph_get_listing_county($listing_id),
                'unit' => hph_get_listing_unit($listing_id),
                'full' => hph_get_listing_address($listing_id, 'full'),
            ),
            
            // Property details
            'property_type' => hph_get_listing_property_type($listing_id),
            'property_style' => hph_get_listing_property_style($listing_id),
            'year_built' => hph_get_listing_year_built($listing_id),
            'bedrooms' => hph_get_listing_bedrooms($listing_id),
            'bathrooms' => hph_get_listing_bathrooms($listing_id),
            'full_baths' => hph_get_listing_full_baths($listing_id),
            'half_baths' => hph_get_listing_half_baths($listing_id),
            'sqft' => hph_get_listing_sqft($listing_id),
            'lot_size' => hph_get_listing_lot_size($listing_id),
            'garage_spaces' => hph_get_listing_garage_spaces($listing_id),
            'price_per_sqft' => hph_get_listing_price_per_sqft($listing_id, false),
            
            // Features
            'interior_features' => hph_get_listing_interior_features($listing_id),
            'exterior_features' => hph_get_listing_exterior_features($listing_id),
            'utility_features' => hph_get_listing_utility_features($listing_id),
            'all_features' => hph_get_listing_features($listing_id),
            
            // Agents and relationships
            'listing_agent' => hph_get_listing_agent($listing_id),
            'co_listing_agent' => hph_get_listing_co_agent($listing_id),
            'buyer_agent' => hph_get_listing_buyer_agent($listing_id),
            'related_city' => hph_get_listing_city_relation($listing_id),
            'related_community' => hph_get_listing_community_relation($listing_id),
            
            // Media
            'gallery' => hph_get_listing_gallery($listing_id),
            'photo_categories' => hph_get_listing_photo_categories($listing_id),
            'primary_photo' => hph_get_listing_photo($listing_id),
            'virtual_tour' => hph_get_listing_virtual_tour($listing_id),
            'virtual_tour_embed' => hph_get_listing_virtual_tour_embed($listing_id),
            'video_tour' => hph_get_listing_video_tour($listing_id),
            'drone_video' => hph_get_listing_drone_video($listing_id),
            
            // Location intelligence
            'coordinates' => hph_get_listing_coordinates($listing_id),
            'schools' => hph_get_listing_schools($listing_id),
            
            // Mortgage calculator
            'mortgage_data' => hph_get_listing_mortgage_data($listing_id),
            
            // Raw ACF fields for edge cases
            'raw_fields' => $fields
        );
    }
}

// =============================================================================
// FORMATTED GETTERS - Using Cached Data
// =============================================================================

/**
 * Get listing price with enhanced formatting options
 */
if (!function_exists('hph_get_listing_price')) {
    function hph_get_listing_price($listing_id, $formatted = true, $format_type = 'standard') {
        $price = hph_get_listing_field($listing_id, 'price', 0);
        
        if (!$price || $price <= 0) {
            return $formatted ? 'Price on Request' : 0;
        }
        
        if (!$formatted) {
            return (int) $price;
        }
        
        // Different formatting options
        switch ($format_type) {
            case 'short':
                // $1.2M, $850K, $75K format
                if ($price >= 1000000) {
                    $millions = $price / 1000000;
                    return '$' . number_format($millions, ($millions >= 10 ? 0 : 1)) . 'M';
                } elseif ($price >= 100000) {
                    $thousands = $price / 1000;
                    return '$' . number_format($thousands, 0) . 'K';
                } else {
                    return '$' . number_format($price);
                }
                
            case 'words':
                // Convert to words for contracts/documents
                return hph_price_to_words($price);
                
            case 'range':
                // Price range for estimates
                $lower = $price * 0.95;
                $upper = $price * 1.05;
                return '$' . number_format($lower) . ' - $' . number_format($upper);
                
            case 'standard':
            default:
                return apply_filters('hph_formatted_price', '$' . number_format($price), $price);
        }
    }
}

/**
 * Convert price to words (for contracts)
 */
if (!function_exists('hph_price_to_words')) {
    function hph_price_to_words($price) {
        if ($price >= 1000000) {
            $millions = floor($price / 1000000);
            $remainder = $price % 1000000;
            
            $words = hph_number_to_words($millions) . ' million';
            
            if ($remainder >= 100000) {
                $hundreds = floor($remainder / 100000);
                $words .= ' ' . hph_number_to_words($hundreds) . ' hundred';
                $remainder = $remainder % 100000;
            }
            
            if ($remainder >= 1000) {
                $thousands = floor($remainder / 1000);
                $words .= ' ' . hph_number_to_words($thousands) . ' thousand';
            }
            
            return ucfirst($words) . ' dollars';
        } elseif ($price >= 1000) {
            $thousands = floor($price / 1000);
            $remainder = $price % 1000;
            
            $words = hph_number_to_words($thousands) . ' thousand';
            
            if ($remainder > 0) {
                $words .= ' ' . hph_number_to_words($remainder);
            }
            
            return ucfirst($words) . ' dollars';
        } else {
            return ucfirst(hph_number_to_words($price)) . ' dollars';
        }
    }
}

/**
 * Helper function to convert numbers to words
 */
if (!function_exists('hph_number_to_words')) {
    function hph_number_to_words($number) {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
        $teens = ['ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        
        if ($number < 10) {
            return $ones[$number];
        } elseif ($number < 20) {
            return $teens[$number - 10];
        } elseif ($number < 100) {
            $ten = floor($number / 10);
            $one = $number % 10;
            return $tens[$ten] . ($one > 0 ? '-' . $ones[$one] : '');
        } elseif ($number < 1000) {
            $hundred = floor($number / 100);
            $remainder = $number % 100;
            return $ones[$hundred] . ' hundred' . ($remainder > 0 ? ' ' . hph_number_to_words($remainder) : '');
        }
        
        return (string) $number; // Fallback for larger numbers
    }
}

/**
 * Get listing address with format options and intelligent city lookup
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
                $street = implode(' ', $street_parts);
                
                // Include unit if available
                if (!empty($fields['unit_number'])) {
                    $street .= ', ' . $fields['unit_number'];
                }
                
                return $street;
                
            case 'city':
                // First try direct ACF field
                $city = $fields['city'] ?? '';
                
                // If empty, try to get from related city post
                if (empty($city)) {
                    $related_city = hph_get_listing_city_relation($listing_id);
                    if ($related_city && !empty($related_city['name'])) {
                        $city = $related_city['name'];
                    }
                }
                
                // If still empty, try zip code lookup
                if (empty($city)) {
                    $zip = $fields['zip_code'] ?? '';
                    if (!empty($zip)) {
                        $city_info = hph_get_city_from_zip($zip);
                        if ($city_info) {
                            $city = $city_info['name'];
                        }
                    }
                }
                
                return $city;
                
            case 'state':
                return $fields['state'] ?? '';
                
            case 'zip':
                return $fields['zip_code'] ?? '';
                
            case 'county':
                return $fields['county'] ?? '';
                
            case 'city_state':
                $city = hph_get_listing_address($listing_id, 'city');
                $state = hph_get_listing_address($listing_id, 'state');
                
                $parts = array_filter([$city, $state]);
                return implode(', ', $parts);
                
            case 'city_state_zip':
                $city = hph_get_listing_address($listing_id, 'city');
                $state = hph_get_listing_address($listing_id, 'state');
                $zip = hph_get_listing_address($listing_id, 'zip');
                
                $city_state = array_filter([$city, $state]);
                $city_state_str = implode(', ', $city_state);
                
                $parts = array_filter([$city_state_str, $zip]);
                return implode(' ', $parts);
                
            case 'full':
            default:
                $street = hph_get_listing_address($listing_id, 'street');
                $city_state_zip = hph_get_listing_address($listing_id, 'city_state_zip');
                
                $parts = array_filter([$street, $city_state_zip]);
                return implode(', ', $parts);
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
 * Get listing bathrooms with enhanced formatting
 */
if (!function_exists('hph_get_listing_bathrooms')) {
    function hph_get_listing_bathrooms($listing_id, $include_half = true, $formatted = false) {
        $full_baths = (float) hph_get_listing_field($listing_id, 'bathrooms', 0);
        $half_baths = (float) hph_get_listing_field($listing_id, 'half_baths', 0);
        
        if (!$include_half) {
            return $formatted ? hph_format_bathroom_count($full_baths, 'full') : $full_baths;
        }
        
        $total = $full_baths + ($half_baths * 0.5);
        
        if ($formatted) {
            return hph_format_bathroom_display($full_baths, $half_baths);
        }
        
        return $total;
    }
}

/**
 * Get full bathrooms with formatting
 */
if (!function_exists('hph_get_listing_full_baths')) {
    function hph_get_listing_full_baths($listing_id, $formatted = false) {
        $full_baths = (int) hph_get_listing_field($listing_id, 'full_baths', 0);
        
        if ($formatted) {
            return hph_format_bathroom_count($full_baths, 'full');
        }
        
        return $full_baths;
    }
}

/**
 * Get half bathrooms with formatting
 */
if (!function_exists('hph_get_listing_half_baths')) {
    function hph_get_listing_half_baths($listing_id, $formatted = false) {
        $half_baths = (int) hph_get_listing_field($listing_id, 'half_baths', 0);
        
        if ($formatted) {
            return hph_format_bathroom_count($half_baths, 'half');
        }
        
        return $half_baths;
    }
}

/**
 * Format bathroom count with proper pluralization
 */
if (!function_exists('hph_format_bathroom_count')) {
    function hph_format_bathroom_count($count, $type = 'full') {
        if ($count <= 0) {
            return '';
        }
        
        $count = (int) $count;
        
        if ($type === 'half') {
            return $count === 1 ? '1 Half Bath' : $count . ' Half Baths';
        } else {
            return $count === 1 ? '1 Full Bath' : $count . ' Full Baths';
        }
    }
}

/**
 * Smart bathroom display formatting
 */
if (!function_exists('hph_format_bathroom_display')) {
    function hph_format_bathroom_display($full_baths, $half_baths) {
        $full = (int) $full_baths;
        $half = (int) $half_baths;
        
        if ($full <= 0 && $half <= 0) {
            return '';
        }
        
        $parts = [];
        
        if ($full > 0) {
            $parts[] = $full === 1 ? '1 Full' : $full . ' Full';
        }
        
        if ($half > 0) {
            $parts[] = $half === 1 ? '1 Half' : $half . ' Half';
        }
        
        if (count($parts) === 1) {
            return $parts[0] . ' Bath' . ($full > 1 || $half > 1 ? 's' : '');
        } else {
            return implode(' & ', $parts) . ' Baths';
        }
    }
}

/**
 * Get listing square footage with enhanced formatting
 */
if (!function_exists('hph_get_listing_sqft')) {
    function hph_get_listing_sqft($listing_id, $formatted = false, $format_type = 'standard') {
        $sqft = (int) hph_get_listing_field($listing_id, 'square_footage', 0);
        
        if (!$formatted) {
            return $sqft;
        }
        
        if ($sqft <= 0) {
            return '';
        }
        
        switch ($format_type) {
            case 'short':
                // 2.4K sq ft format
                if ($sqft >= 1000) {
                    $thousands = $sqft / 1000;
                    return number_format($thousands, 1) . 'K sq ft';
                } else {
                    return $sqft . ' sq ft';
                }
                
            case 'approximate':
                // Round to nearest 50 or 100
                if ($sqft >= 1000) {
                    $rounded = round($sqft / 100) * 100;
                } else {
                    $rounded = round($sqft / 50) * 50;
                }
                return '~' . number_format($rounded) . ' sq ft';
                
            case 'range':
                // Show as range (+/- 5%)
                $lower = $sqft * 0.95;
                $upper = $sqft * 1.05;
                return number_format($lower, 0) . '-' . number_format($upper, 0) . ' sq ft';
                
            case 'standard':
            default:
                return number_format($sqft) . ' sq ft';
        }
    }
}

/**
 * Get listing date listed with enhanced formatting
 */
if (!function_exists('hph_get_listing_list_date')) {
    function hph_get_listing_list_date($listing_id, $format = 'F j, Y') {
        $date = hph_get_listing_field($listing_id, 'list_date', '');
        
        if (empty($date)) {
            return '';
        }
        
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return $date; // Return original if can't parse
        }
        
        switch ($format) {
            case 'relative':
                $diff = time() - $timestamp;
                $days = floor($diff / DAY_IN_SECONDS);
                
                if ($days === 0) {
                    return 'Listed today';
                } elseif ($days === 1) {
                    return 'Listed yesterday';
                } elseif ($days < 7) {
                    return 'Listed ' . $days . ' days ago';
                } elseif ($days < 30) {
                    $weeks = floor($days / 7);
                    return 'Listed ' . $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
                } elseif ($days < 365) {
                    $months = floor($days / 30);
                    return 'Listed ' . $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                } else {
                    return 'Listed over a year ago';
                }
                
            case 'short':
                return date('M j', $timestamp);
                
            case 'formal':
                return date('l, F jS, Y', $timestamp);
                
            case 'days_on_market':
                $days = floor((time() - $timestamp) / DAY_IN_SECONDS);
                return $days . ' day' . ($days !== 1 ? 's' : '') . ' on market';
                
            default:
                return date($format, $timestamp);
        }
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
 * Get listing lot size with intelligent formatting
 */
if (!function_exists('hph_get_listing_lot_size')) {
    function hph_get_listing_lot_size($listing_id, $formatted = false) {
        $lot_size = (int) hph_get_listing_field($listing_id, 'lot_size', 0);
        
        if (!$formatted) {
            return $lot_size;
        }
        
        if ($lot_size <= 0) {
            return '';
        }
        
        // Smart formatting based on size
        if ($lot_size >= 43560) {
            // 1+ acres - show in acres
            $acres = $lot_size / 43560;
            if ($acres >= 10) {
                return number_format($acres, 0) . ' acres';
            } elseif ($acres >= 1) {
                return number_format($acres, 1) . ' acres';
            } else {
                return number_format($acres, 2) . ' acres';
            }
        } elseif ($lot_size >= 10000) {
            // 10,000+ sq ft - show with commas
            return number_format($lot_size) . ' sq ft';
        } else {
            // Under 10,000 sq ft - simple format
            return $lot_size . ' sq ft';
        }
    }
}

/**
 * Get listing features with proper formatting
 */
if (!function_exists('hph_get_listing_features')) {
    function hph_get_listing_features($listing_id, $type = 'all', $formatted = false) {
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
        
        // Utility features
        if ($type === 'all' || $type === 'utility') {
            $utility = $fields['utility_features'] ?? [];
            if (is_array($utility)) {
                $features = array_merge($features, $utility);
            }
        }
        
        // Format feature labels if requested
        if ($formatted) {
            $features = array_map('hph_format_feature_label', $features);
        }
        
        return $features;
    }
}

/**
 * Format feature labels from field values to human readable
 */
if (!function_exists('hph_format_feature_label')) {
    function hph_format_feature_label($feature_key) {
        // Feature label mapping
        $feature_labels = [
            // Interior Features
            'vaulted_ceilings' => 'Vaulted Ceilings',
            'hardwood_floors' => 'Hardwood Floors',
            'tile_floors' => 'Tile Floors',
            'carpet_floors' => 'Carpet Floors',
            'granite_counters' => 'Granite Countertops',
            'stainless_appliances' => 'Stainless Steel Appliances',
            'updated_kitchen' => 'Updated Kitchen',
            'updated_bathrooms' => 'Updated Bathrooms',
            'walk_in_closet' => 'Walk-in Closet',
            'fireplace' => 'Fireplace',
            'ceiling_fans' => 'Ceiling Fans',
            'crown_molding' => 'Crown Molding',
            'recessed_lighting' => 'Recessed Lighting',
            'breakfast_bar' => 'Breakfast Bar',
            'kitchen_island' => 'Kitchen Island',
            'pantry' => 'Pantry',
            'laundry_room' => 'Laundry Room',
            'basement' => 'Basement',
            'finished_basement' => 'Finished Basement',
            'wine_cellar' => 'Wine Cellar',
            
            // Exterior Features
            'deck' => 'Deck',
            'patio' => 'Patio',
            'balcony' => 'Balcony',
            'pool' => 'Swimming Pool',
            'hot_tub' => 'Hot Tub',
            'fenced_yard' => 'Fenced Yard',
            'landscaped' => 'Professionally Landscaped',
            'garden' => 'Garden',
            'sprinkler_system' => 'Sprinkler System',
            'outdoor_kitchen' => 'Outdoor Kitchen',
            'fire_pit' => 'Fire Pit',
            'gazebo' => 'Gazebo',
            'shed' => 'Storage Shed',
            'workshop' => 'Workshop',
            
            // Utility Features
            'central_air' => 'Central Air Conditioning',
            'forced_air_heat' => 'Forced Air Heating',
            'radiant_heat' => 'Radiant Heating',
            'heat_pump' => 'Heat Pump',
            'solar_panels' => 'Solar Panels',
            'generator' => 'Generator',
            'security_system' => 'Security System',
            'smart_home' => 'Smart Home Features',
            'high_speed_internet' => 'High-Speed Internet Ready',
            'updated_electrical' => 'Updated Electrical',
            'updated_plumbing' => 'Updated Plumbing',
            'new_roof' => 'New Roof',
            'new_windows' => 'New Windows',
            'insulation' => 'Updated Insulation',
            
            // Parking & Garage
            'attached_garage' => 'Attached Garage',
            'detached_garage' => 'Detached Garage',
            'carport' => 'Carport',
            'driveway' => 'Paved Driveway',
            'circular_drive' => 'Circular Driveway',
            
            // Special Features
            'water_view' => 'Water View',
            'mountain_view' => 'Mountain View',
            'city_view' => 'City View',
            'golf_course_view' => 'Golf Course View',
            'waterfront' => 'Waterfront Property',
            'corner_lot' => 'Corner Lot',
            'cul_de_sac' => 'Cul-de-sac Location',
            'new_construction' => 'New Construction',
            'historic' => 'Historic Property',
            'green_certified' => 'Green Certified',
        ];
        
        // Return formatted label or title case the key if not found
        return $feature_labels[$feature_key] ?? ucwords(str_replace('_', ' ', $feature_key));
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
        // Try various gallery field names - updated to match actual ACF field names
        $gallery_fields = ['property_gallery', 'photo_gallery', 'gallery', 'images'];
        $gallery = [];
        
        foreach ($gallery_fields as $field_name) {
            $gallery = hph_get_listing_field($listing_id, $field_name, []);
            if (!empty($gallery) && is_array($gallery)) {
                break;
            }
        }
        
        if (!is_array($gallery)) {
            return [];
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
                $image_url = wp_get_attachment_image_url($image, 'listing-hero');
                $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true);
                if ($image_url) {
                    $normalized_gallery[] = [
                        'url' => $image_url,
                        'alt' => $image_alt ?: '',
                        'ID' => $image
                    ];
                }
            }
        }
        
        return $normalized_gallery;
    }
}

/**
 * Get listing primary photo with size options
 */
if (!function_exists('hph_get_listing_photo')) {
    function hph_get_listing_photo($listing_id, $size = 'large') {
        // First try main_photo ACF field
        $main_photo = hph_get_listing_field($listing_id, 'main_photo');
        if ($main_photo) {
            if (is_array($main_photo)) {
                return $main_photo['url'] ?? '';
            }
            return wp_get_attachment_image_url($main_photo, $size) ?: $main_photo;
        }
        
        // Then try featured image
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
        return get_template_directory_uri() . '/assets/images/property-placeholder.jpg';
    }
}

/**
 * Get property style (architectural style)
 */
if (!function_exists('hph_get_listing_property_style')) {
    function hph_get_listing_property_style($listing_id, $formatted = false) {
        $style = hph_get_listing_field($listing_id, 'property_style', '');
        
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
 * Get year built
 */
if (!function_exists('hph_get_listing_year_built')) {
    function hph_get_listing_year_built($listing_id) {
        return (int) hph_get_listing_field($listing_id, 'year_built', 0);
    }
}

/**
 * Get garage spaces
 */
if (!function_exists('hph_get_listing_garage_spaces')) {
    function hph_get_listing_garage_spaces($listing_id) {
        return (int) hph_get_listing_field($listing_id, 'garage_spaces', 0);
    }
}

/**
 * Get listing date
 */
if (!function_exists('hph_get_listing_date')) {
    function hph_get_listing_date($listing_id, $format = 'F j, Y') {
        $date = hph_get_listing_field($listing_id, 'list_date', '');
        if ($date) {
            return date($format, strtotime($date));
        }
        return '';
    }
}

/**
 * Get MLS number
 */
if (!function_exists('hph_get_listing_mls')) {
    function hph_get_listing_mls($listing_id) {
        return hph_get_listing_field($listing_id, 'mls_number', '');
    }
}

/**
 * Get county
 */
if (!function_exists('hph_get_listing_county')) {
    function hph_get_listing_county($listing_id) {
        return hph_get_listing_field($listing_id, 'county', '');
    }
}

/**
 * Get unit number
 */
if (!function_exists('hph_get_listing_unit')) {
    function hph_get_listing_unit($listing_id) {
        return hph_get_listing_field($listing_id, 'unit_number', '');
    }
}

/**
 * Get property type
 */
if (!function_exists('hph_get_listing_property_type')) {
    function hph_get_listing_property_type($listing_id, $formatted = false) {
        $type = hph_get_listing_field($listing_id, 'property_type', '');
        
        if ($formatted && $type) {
            $type_labels = [
                'single_family' => 'Single Family Home',
                'condo' => 'Condominium',
                'townhome' => 'Townhome',
                'multi_family' => 'Multi-Family',
                'land' => 'Land/Lot',
                'commercial' => 'Commercial',
                'mobile_home' => 'Mobile Home',
                'other' => 'Other'
            ];
            return $type_labels[$type] ?? ucfirst(str_replace('_', ' ', $type));
        }
        
        return $type;
    }
}



/**
 * Get virtual tour URL
 */
if (!function_exists('hph_get_listing_virtual_tour')) {
    function hph_get_listing_virtual_tour($listing_id) {
        return hph_get_listing_field($listing_id, 'virtual_tour_url', '');
    }
}

/**
 * Get virtual tour embed code
 */
if (!function_exists('hph_get_listing_virtual_tour_embed')) {
    function hph_get_listing_virtual_tour_embed($listing_id) {
        return hph_get_listing_field($listing_id, 'virtual_tour_embed', '');
    }
}

/**
 * Get video tour URL
 */
if (!function_exists('hph_get_listing_video_tour')) {
    function hph_get_listing_video_tour($listing_id) {
        return hph_get_listing_field($listing_id, 'video_tour_url', '');
    }
}

/**
 * Get drone video URL
 */
if (!function_exists('hph_get_listing_drone_video')) {
    function hph_get_listing_drone_video($listing_id) {
        return hph_get_listing_field($listing_id, 'drone_video_url', '');
    }
}

/**
 * Get photo categories (organized gallery)
 */
if (!function_exists('hph_get_listing_photo_categories')) {
    function hph_get_listing_photo_categories($listing_id) {
        $categories = hph_get_listing_field($listing_id, 'photo_categories', []);
        
        if (!is_array($categories)) {
            return [];
        }
        
        // Normalize and sort by order
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
        
        // Sort by order field
        usort($normalized, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $normalized;
    }
}

/**
 * Get coordinates
 */
if (!function_exists('hph_get_listing_coordinates')) {
    function hph_get_listing_coordinates($listing_id) {
        $fields = hph_get_listing_fields($listing_id);
        return [
            'latitude' => $fields['latitude'] ?? '',
            'longitude' => $fields['longitude'] ?? '',
            'formatted' => ($fields['latitude'] ?? '') . ',' . ($fields['longitude'] ?? '')
        ];
    }
}

/**
 * Get school information
 */
if (!function_exists('hph_get_listing_schools')) {
    function hph_get_listing_schools($listing_id) {
        $fields = hph_get_listing_fields($listing_id);
        return [
            'district' => $fields['school_district'] ?? '',
            'elementary' => $fields['elementary_school'] ?? '',
            'middle' => $fields['middle_school'] ?? '',
            'high' => $fields['high_school'] ?? ''
        ];
    }
}

/**
 * Get mortgage calculator data
 */
if (!function_exists('hph_get_listing_mortgage_data')) {
    function hph_get_listing_mortgage_data($listing_id) {
        $fields = hph_get_listing_fields($listing_id);
        $price = hph_get_listing_price($listing_id, false);
        
        return [
            'price' => $price,
            'down_payment_percent' => (float) ($fields['estimated_down_payment'] ?? 20),
            'interest_rate' => (float) ($fields['estimated_interest_rate'] ?? 6.5),
            'loan_term' => (int) ($fields['estimated_loan_term'] ?? 30),
            'pmi_rate' => (float) ($fields['estimated_pmi_rate'] ?? 0.5),
            'calculated_payment' => $fields['calculated_monthly_payment'] ?? 0
        ];
    }
}

/**
 * Get interior features specifically
 */
if (!function_exists('hph_get_listing_interior_features')) {
    function hph_get_listing_interior_features($listing_id) {
        return hph_get_listing_field($listing_id, 'interior_features', []);
    }
}

/**
 * Get exterior features specifically
 */
if (!function_exists('hph_get_listing_exterior_features')) {
    function hph_get_listing_exterior_features($listing_id) {
        return hph_get_listing_field($listing_id, 'exterior_features', []);
    }
}

/**
 * Get utility features specifically
 */
if (!function_exists('hph_get_listing_utility_features')) {
    function hph_get_listing_utility_features($listing_id) {
        return hph_get_listing_field($listing_id, 'utility_features', []);
    }
}

/**
 * Get co-listing agent
 */
if (!function_exists('hph_get_listing_co_agent')) {
    function hph_get_listing_co_agent($listing_id) {
        $agent_post = hph_get_listing_field($listing_id, 'co_listing_agent');
        
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
 * Get buyer's agent
 */
if (!function_exists('hph_get_listing_buyer_agent')) {
    function hph_get_listing_buyer_agent($listing_id) {
        $agent_post = hph_get_listing_field($listing_id, 'buyer_agent');
        
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
 * Get related city
 */
if (!function_exists('hph_get_listing_city_relation')) {
    function hph_get_listing_city_relation($listing_id) {
        $city_post = hph_get_listing_field($listing_id, 'related_city');
        
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
 * Get related community
 */
if (!function_exists('hph_get_listing_community_relation')) {
    function hph_get_listing_community_relation($listing_id) {
        $community_post = hph_get_listing_field($listing_id, 'related_community');
        
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
 * Calculate days on market
 */
if (!function_exists('hph_get_listing_days_on_market')) {
    function hph_get_listing_days_on_market($listing_id) {
        $list_date = hph_get_listing_field($listing_id, 'list_date', '');
        if (!$list_date) {
            return 0;
        }
        
        $list_timestamp = strtotime($list_date);
        $current_timestamp = current_time('timestamp');
        
        return max(0, floor(($current_timestamp - $list_timestamp) / DAY_IN_SECONDS));
    }
}

/**
 * Calculate price per square foot
 */
if (!function_exists('hph_get_listing_price_per_sqft')) {
    function hph_get_listing_price_per_sqft($listing_id, $formatted = false) {
        $price = hph_get_listing_price($listing_id, false);
        $sqft = hph_get_listing_sqft($listing_id, false);
        
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

/**
 * Get listing zip code with city integration
 */
if (!function_exists('hph_get_listing_zip_code')) {
    function hph_get_listing_zip_code($listing_id, $include_city = false, $format = 'standard') {
        $zip = hph_get_listing_field($listing_id, 'zip_code', '');
        
        if (empty($zip)) {
            return '';
        }
        
        if (!$include_city) {
            return $zip;
        }
        
        // Try to find city post type matching this zip code
        $city_query = new WP_Query([
            'post_type' => 'city',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'zip_codes',
                    'value' => $zip,
                    'compare' => 'LIKE'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        if ($city_query->have_posts()) {
            $city_id = $city_query->posts[0];
            $city_name = get_the_title($city_id);
            $state = get_field('state', $city_id);
            
            switch ($format) {
                case 'city_only':
                    return $city_name;
                    
                case 'city_state':
                    return $city_name . ($state ? ', ' . $state : '');
                    
                case 'zip_city':
                    return $zip . ' (' . $city_name . ')';
                    
                case 'full':
                    return $city_name . ($state ? ', ' . $state : '') . ' ' . $zip;
                    
                case 'standard':
                default:
                    return $zip;
            }
        }
        
        // Fallback if no city found
        return $zip;
    }
}

/**
 * Get city information from zip code
 */
if (!function_exists('hph_get_city_from_zip')) {
    function hph_get_city_from_zip($zip_code) {
        if (empty($zip_code)) {
            return null;
        }
        
        $city_query = new WP_Query([
            'post_type' => 'city',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'zip_codes',
                    'value' => $zip_code,
                    'compare' => 'LIKE'
                ]
            ]
        ]);
        
        if ($city_query->have_posts()) {
            $city = $city_query->posts[0];
            
            return [
                'id' => $city->ID,
                'name' => $city->post_title,
                'slug' => $city->post_name,
                'state' => get_field('state', $city->ID),
                'county' => get_field('county', $city->ID),
                'description' => get_field('description', $city->ID),
                'link' => get_permalink($city->ID)
            ];
        }
        
        return null;
    }
}
