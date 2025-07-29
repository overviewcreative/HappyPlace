<?php
/**
 * Enhanced Bridge Functions for New BrightMLS Fields
 * 
 * Extends your existing bridge function pattern with the new fields.
 * These work seamlessly with your current 770+ functions.
 * 
 * Save this as: wp-content/themes/Happy Place Theme/inc/bridge/brightmls-bridge.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// ESSENTIAL INFORMATION BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get original listing price
 */
if (!function_exists('hph_bridge_get_original_price')) {
    function hph_bridge_get_original_price($listing_id, $formatted = true) {
        $original_price = get_field('original_price', $listing_id) ?: 0;
        
        if (!$original_price) {
            // Fallback to current price if original not set
            $original_price = get_field('price', $listing_id) ?: 0;
        }
        
        return $formatted ? hph_format_price($original_price) : $original_price;
    }
}

/**
 * Get calculated price per square foot
 */
if (!function_exists('hph_bridge_get_price_per_sqft_calc')) {
    function hph_bridge_get_price_per_sqft_calc($listing_id, $formatted = true) {
        $price_per_sqft = get_field('price_per_sqft', $listing_id) ?: 0;
        
        return $formatted ? '$' . number_format($price_per_sqft, 2) : $price_per_sqft;
    }
}

/**
 * Get listing agreement type
 */
if (!function_exists('hph_bridge_get_agreement_type')) {
    function hph_bridge_get_agreement_type($listing_id) {
        $agreement_type = get_field('listing_agreement_type', $listing_id);
        
        $types = [
            'exclusive_right' => 'Exclusive Right to Sell',
            'exclusive_agency' => 'Exclusive Agency',
            'open_listing' => 'Open Listing',
            'net_listing' => 'Net Listing'
        ];
        
        return $types[$agreement_type] ?? $agreement_type;
    }
}

/**
 * Get service level
 */
if (!function_exists('hph_bridge_get_service_level')) {
    function hph_bridge_get_service_level($listing_id) {
        $service_level = get_field('listing_service_level', $listing_id);
        
        $levels = [
            'full_service' => 'Full Service',
            'limited_service' => 'Limited Service',
            'flat_fee' => 'Flat Fee',
            'fsbo_assist' => 'FSBO Assistance'
        ];
        
        return $levels[$service_level] ?? $service_level;
    }
}

/**
 * Get calculated days on market
 */
if (!function_exists('hph_bridge_get_days_on_market_calc')) {
    function hph_bridge_get_days_on_market_calc($listing_id) {
        $days = get_field('days_on_market', $listing_id) ?: 0;
        return intval($days);
    }
}

/**
 * Get expiration date
 */
if (!function_exists('hph_bridge_get_expiration_date')) {
    function hph_bridge_get_expiration_date($listing_id, $format = 'display') {
        $expiration_date = get_field('expiration_date', $listing_id);
        
        if (!$expiration_date) {
            return '';
        }
        
        if ($format === 'display') {
            return date('M j, Y', strtotime($expiration_date));
        }
        
        return $expiration_date;
    }
}

// =============================================================================
// PROPERTY DETAILS BRIDGE FUNCTIONS  
// =============================================================================

/**
 * Get property sub-type
 */
if (!function_exists('hph_bridge_get_property_sub_type')) {
    function hph_bridge_get_property_sub_type($listing_id) {
        $sub_type = get_field('property_sub_type', $listing_id);
        
        $types = [
            'single_family_detached' => 'Single Family Detached',
            'single_family_attached' => 'Single Family Attached',
            'condo_high_rise' => 'Condominium High-Rise',
            'condo_mid_rise' => 'Condominium Mid-Rise',
            'condo_low_rise' => 'Condominium Low-Rise',
            'townhouse' => 'Townhouse',
            'duplex' => 'Duplex',
            'triplex' => 'Triplex',
            'quadruplex' => 'Quadruplex',
            'manufactured_home' => 'Manufactured Home',
            'mobile_home' => 'Mobile Home'
        ];
        
        return $types[$sub_type] ?? $sub_type;
    }
}

/**
 * Get property condition
 */
if (!function_exists('hph_bridge_get_property_condition')) {
    function hph_bridge_get_property_condition($listing_id) {
        $condition = get_field('property_condition', $listing_id);
        
        $conditions = [
            'excellent' => 'Excellent',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor',
            'under_construction' => 'Under Construction',
            'new_construction' => 'New Construction'
        ];
        
        return $conditions[$condition] ?? $condition;
    }
}

/**
 * Get occupancy status
 */
if (!function_exists('hph_bridge_get_occupancy_status')) {
    function hph_bridge_get_occupancy_status($listing_id) {
        $occupancy = get_field('occupancy_status', $listing_id);
        
        $statuses = [
            'owner' => 'Owner Occupied',
            'tenant' => 'Tenant Occupied',
            'vacant' => 'Vacant',
            'unknown' => 'Unknown'
        ];
        
        return $statuses[$occupancy] ?? $occupancy;
    }
}

/**
 * Get full street address (generated)
 */
if (!function_exists('hph_bridge_get_full_street_address')) {
    function hph_bridge_get_full_street_address($listing_id) {
        $full_address = get_field('full_street_address', $listing_id);
        
        if (!$full_address) {
            // Generate on the fly if not calculated yet
            $street_number = get_field('street_number', $listing_id);
            $street_name = get_field('street_name', $listing_id);
            $street_suffix = get_field('street_suffix', $listing_id);
            $unit_number = get_field('unit_number', $listing_id);
            
            $address_parts = array_filter([
                $street_number,
                $street_name,
                $street_suffix,
                $unit_number ? "Unit {$unit_number}" : null
            ]);
            
            $full_address = implode(' ', $address_parts);
        }
        
        return $full_address;
    }
}

/**
 * Get square footage source
 */
if (!function_exists('hph_bridge_get_sqft_source')) {
    function hph_bridge_get_sqft_source($listing_id) {
        $source = get_field('sqft_source', $listing_id);
        
        $sources = [
            'tax_assessor' => 'Tax Assessor',
            'builder' => 'Builder',
            'owner' => 'Owner',
            'public_records' => 'Public Records',
            'appraiser' => 'Appraiser',
            'floor_plan' => 'Floor Plan',
            'estimated' => 'Estimated'
        ];
        
        return $sources[$source] ?? $source;
    }
}

/**
 * Get living area separate from total square footage
 */
if (!function_exists('hph_bridge_get_living_area')) {
    function hph_bridge_get_living_area($listing_id, $formatted = true) {
        $living_area = get_field('living_area', $listing_id) ?: 0;
        
        if (!$living_area) {
            // Fallback to square footage if living area not specified
            $living_area = get_field('square_footage', $listing_id) ?: 0;
        }
        
        return $formatted ? number_format($living_area) . ' sq ft' : $living_area;
    }
}

/**
 * Get total bathrooms (calculated)
 */
if (!function_exists('hph_bridge_get_bathrooms_total_calc')) {
    function hph_bridge_get_bathrooms_total_calc($listing_id) {
        $total = get_field('bathrooms_total', $listing_id) ?: 0;
        return floatval($total);
    }
}

/**
 * Get full bathroom count
 */
if (!function_exists('hph_bridge_get_bathrooms_full')) {
    function hph_bridge_get_bathrooms_full($listing_id) {
        return intval(get_field('bathrooms_full', $listing_id) ?: 0);
    }
}

/**
 * Get half bathroom count
 */
if (!function_exists('hph_bridge_get_bathrooms_half')) {
    function hph_bridge_get_bathrooms_half($listing_id) {
        return intval(get_field('bathrooms_half', $listing_id) ?: 0);
    }
}

/**
 * Get lot dimensions
 */
if (!function_exists('hph_bridge_get_lot_dimensions')) {
    function hph_bridge_get_lot_dimensions($listing_id) {
        return get_field('lot_dimensions', $listing_id) ?: '';
    }
}

/**
 * Get lot size source
 */
if (!function_exists('hph_bridge_get_lot_size_source')) {
    function hph_bridge_get_lot_size_source($listing_id) {
        $source = get_field('lot_size_source', $listing_id);
        
        $sources = [
            'tax_assessor' => 'Tax Assessor',
            'public_records' => 'Public Records',
            'survey' => 'Survey',
            'owner' => 'Owner',
            'estimated' => 'Estimated'
        ];
        
        return $sources[$source] ?? $source;
    }
}

/**
 * Get lot size in square feet (calculated)
 */
if (!function_exists('hph_bridge_get_lot_sqft_calc')) {
    function hph_bridge_get_lot_sqft_calc($listing_id, $formatted = true) {
        $lot_sqft = get_field('lot_sqft_calc', $listing_id) ?: get_field('lot_sqft', $listing_id) ?: 0;
        
        return $formatted ? number_format($lot_sqft) . ' sq ft' : $lot_sqft;
    }
}

/**
 * Get lot features array
 */
if (!function_exists('hph_bridge_get_lot_features')) {
    function hph_bridge_get_lot_features($listing_id, $formatted = true) {
        $features = get_field('lot_features', $listing_id) ?: [];
        
        if (!$formatted) {
            return $features;
        }
        
        $feature_labels = [
            'corner_lot' => 'Corner Lot',
            'cul_de_sac' => 'Cul-de-sac',
            'flag_lot' => 'Flag Lot',
            'interior_lot' => 'Interior Lot',
            'irregular_lot' => 'Irregular Lot',
            'rectangular' => 'Rectangular',
            'water_access' => 'Water Access',
            'waterfront' => 'Waterfront',
            'wooded' => 'Wooded',
            'cleared' => 'Cleared',
            'level' => 'Level',
            'sloped' => 'Sloped',
            'fenced' => 'Fenced'
        ];
        
        $formatted_features = [];
        foreach ($features as $feature) {
            $formatted_features[] = $feature_labels[$feature] ?? $feature;
        }
        
        return $formatted_features;
    }
}

/**
 * Get stories total
 */
if (!function_exists('hph_bridge_get_stories_total')) {
    function hph_bridge_get_stories_total($listing_id) {
        return intval(get_field('stories_total', $listing_id) ?: 1);
    }
}

/**
 * Get basement type
 */
if (!function_exists('hph_bridge_get_basement_type')) {
    function hph_bridge_get_basement_type($listing_id) {
        $basement = get_field('basement_type', $listing_id);
        
        $types = [
            'none' => 'None',
            'full' => 'Full Basement',
            'partial' => 'Partial Basement',
            'crawl_space' => 'Crawl Space',
            'slab' => 'Slab Foundation'
        ];
        
        return $types[$basement] ?? $basement;
    }
}

/**
 * Get garage spaces
 */
if (!function_exists('hph_bridge_get_garage_spaces')) {
    function hph_bridge_get_garage_spaces($listing_id) {
        return intval(get_field('garage_spaces', $listing_id) ?: 0);
    }
}

/**
 * Get total parking spaces
 */
if (!function_exists('hph_bridge_get_parking_total')) {
    function hph_bridge_get_parking_total($listing_id) {
        return intval(get_field('parking_total', $listing_id) ?: 0);
    }
}

/**
 * Get construction materials
 */
if (!function_exists('hph_bridge_get_construction_materials')) {
    function hph_bridge_get_construction_materials($listing_id, $formatted = true) {
        $materials = get_field('construction_materials', $listing_id) ?: [];
        
        if (!$formatted) {
            return $materials;
        }
        
        $material_labels = [
            'brick' => 'Brick',
            'vinyl_siding' => 'Vinyl Siding',
            'wood_siding' => 'Wood Siding',
            'stone' => 'Stone',
            'stucco' => 'Stucco',
            'aluminum_siding' => 'Aluminum Siding',
            'fiber_cement' => 'Fiber Cement',
            'log' => 'Log',
            'block' => 'Block'
        ];
        
        $formatted_materials = [];
        foreach ($materials as $material) {
            $formatted_materials[] = $material_labels[$material] ?? $material;
        }
        
        return $formatted_materials;
    }
}

/**
 * Get roof material
 */
if (!function_exists('hph_bridge_get_roof_material')) {
    function hph_bridge_get_roof_material($listing_id) {
        $roof = get_field('roof_material', $listing_id);
        
        $materials = [
            'asphalt_shingle' => 'Asphalt Shingle',
            'metal' => 'Metal',
            'tile' => 'Tile',
            'slate' => 'Slate',
            'wood_shake' => 'Wood Shake',
            'rubber' => 'Rubber',
            'flat' => 'Flat/Built-up'
        ];
        
        return $materials[$roof] ?? $roof;
    }
}

// =============================================================================
// UTILITIES & SYSTEMS BRIDGE FUNCTIONS
// =============================================================================

/**
 * Get heating system
 */
if (!function_exists('hph_bridge_get_heating_system')) {
    function hph_bridge_get_heating_system($listing_id, $formatted = true) {
        $heating = get_field('heating_system', $listing_id) ?: [];
        
        if (!$formatted) {
            return $heating;
        }
        
        $heating_labels = [
            'forced_air' => 'Forced Air',
            'heat_pump' => 'Heat Pump',
            'baseboard' => 'Baseboard',
            'radiant' => 'Radiant',
            'geothermal' => 'Geothermal',
            'solar' => 'Solar',
            'fireplace' => 'Fireplace',
            'wood_stove' => 'Wood Stove'
        ];
        
        $formatted_heating = [];
        foreach ($heating as $system) {
            $formatted_heating[] = $heating_labels[$system] ?? $system;
        }
        
        return $formatted_heating;
    }
}

/**
 * Get cooling system
 */
if (!function_exists('hph_bridge_get_cooling_system')) {
    function hph_bridge_get_cooling_system($listing_id, $formatted = true) {
        $cooling = get_field('cooling_system', $listing_id) ?: [];
        
        if (!$formatted) {
            return $cooling;
        }
        
        $cooling_labels = [
            'central_air' => 'Central Air',
            'heat_pump' => 'Heat Pump',
            'window_units' => 'Window Units',
            'ductless' => 'Ductless Mini-Split',
            'evaporative' => 'Evaporative',
            'none' => 'None'
        ];
        
        $formatted_cooling = [];
        foreach ($cooling as $system) {
            $formatted_cooling[] = $cooling_labels[$system] ?? $system;
        }
        
        return $formatted_cooling;
    }
}

/**
 * Get water system
 */
if (!function_exists('hph_bridge_get_water_system')) {
    function hph_bridge_get_water_system($listing_id) {
        $water = get_field('water_system', $listing_id);
        
        $systems = [
            'public' => 'Public Water',
            'well' => 'Private Well',
            'community' => 'Community Well',
            'other' => 'Other'
        ];
        
        return $systems[$water] ?? $water;
    }
}

/**
 * Get sewer system
 */
if (!function_exists('hph_bridge_get_sewer_system')) {
    function hph_bridge_get_sewer_system($listing_id) {
        $sewer = get_field('sewer_system', $listing_id);
        
        $systems = [
            'public_sewer' => 'Public Sewer',
            'septic' => 'Septic System',
            'community' => 'Community System',
            'other' => 'Other'
        ];
        
        return $systems[$sewer] ?? $sewer;
    }
}

/**
 * Get electrical service
 */
if (!function_exists('hph_bridge_get_electric_service')) {
    function hph_bridge_get_electric_service($listing_id) {
        $electric = get_field('electric_service', $listing_id);
        return $electric ? $electric . ' amp' : '';
    }
}

/**
 * Get parcel number
 */
if (!function_exists('hph_bridge_get_parcel_number')) {
    function hph_bridge_get_parcel_number($listing_id) {
        return get_field('parcel_number', $listing_id) ?: '';
    }
}

// =============================================================================
// HELPER FUNCTIONS FOR EXISTING BRIDGE COMPATIBILITY
// =============================================================================

/**
 * Enhanced address formatting that uses new address components
 */
if (!function_exists('hph_bridge_get_address_enhanced')) {
    function hph_bridge_get_address_enhanced($listing_id, $format = 'full') {
        switch ($format) {
            case 'street':
                return hph_bridge_get_full_street_address($listing_id);
                
            case 'city_state_zip':
                $city = get_field('city', $listing_id);
                $state = get_field('state', $listing_id);
                $zip = get_field('zip_code', $listing_id);
                return trim("{$city}, {$state} {$zip}");
                
            case 'full':
            default:
                $street = hph_bridge_get_full_street_address($listing_id);
                $city = get_field('city', $listing_id);
                $state = get_field('state', $listing_id);
                $zip = get_field('zip_code', $listing_id);
                
                $parts = array_filter([$street, "{$city}, {$state} {$zip}"]);
                return implode(', ', $parts);
        }
    }
}

/**
 * Get property features summary (combines all feature types)
 */
if (!function_exists('hph_bridge_get_property_features_summary')) {
    function hph_bridge_get_property_features_summary($listing_id) {
        $features = [];
        
        // Add construction materials
        $materials = hph_bridge_get_construction_materials($listing_id, true);
        if (!empty($materials)) {
            $features[] = 'Materials: ' . implode(', ', $materials);
        }
        
        // Add heating/cooling
        $heating = hph_bridge_get_heating_system($listing_id, true);
        if (!empty($heating)) {
            $features[] = 'Heating: ' . implode(', ', $heating);
        }
        
        $cooling = hph_bridge_get_cooling_system($listing_id, true);
        if (!empty($cooling)) {
            $features[] = 'Cooling: ' . implode(', ', $cooling);
        }
        
        // Add lot features
        $lot_features = hph_bridge_get_lot_features($listing_id, true);
        if (!empty($lot_features)) {
            $features[] = 'Lot: ' . implode(', ', $lot_features);
        }
        
        return $features;
    }
}

/**
 * Backward compatibility - enhanced bathroom formatting
 */
if (!function_exists('hph_bridge_get_bathrooms_enhanced')) {
    function hph_bridge_get_bathrooms_enhanced($listing_id, $detailed = false) {
        $full = hph_bridge_get_bathrooms_full($listing_id);
        $half = hph_bridge_get_bathrooms_half($listing_id);
        $total = hph_bridge_get_bathrooms_total_calc($listing_id);
        
        if ($detailed && ($full > 0 || $half > 0)) {
            $parts = [];
            if ($full > 0) $parts[] = "{$full} full";
            if ($half > 0) $parts[] = "{$half} half";
            return implode(', ', $parts) . " ({$total} total)";
        }
        
        return $total;
    }
}