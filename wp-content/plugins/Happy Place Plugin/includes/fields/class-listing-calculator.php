<?php
/**
 * Listing Field Auto-Calculator
 * 
 * Handles auto-calculation of fields for BrightMLS compliance
 * without adding unnecessary complexity.
 * 
 * Save this as: wp-content/plugins/Happy Place Plugin/includes/fields/class-listing-calculator.php
 */

namespace HappyPlace\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class Listing_Calculator
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // Hook into ACF save to calculate fields
        add_action('acf/save_post', [$this, 'calculate_listing_fields'], 20);
        
        // Set original price on first save
        add_action('acf/save_post', [$this, 'set_original_price'], 15);
        
        // Track price changes
        add_action('acf/save_post', [$this, 'track_price_changes'], 16);
        
        // Update status change date when status changes
        add_action('acf/save_post', [$this, 'track_status_changes'], 25);
    }

    /**
     * Calculate auto-generated fields for listings
     */
    public function calculate_listing_fields($post_id): void
    {
        // Only process listing post type
        if (get_post_type($post_id) !== 'listing') {
            return;
        }

        // Skip if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Address processing (enhanced method)
        $this->process_address_fields($post_id);
        
        // Calculate price per square foot
        $this->calculate_price_per_sqft($post_id);
        
        // Calculate total bathrooms
        $this->calculate_total_bathrooms($post_id);
        
        // Calculate days on market
        $this->calculate_days_on_market($post_id);
        
        // Calculate lot size in square feet
        $this->calculate_lot_sqft($post_id);
        
        // Enhanced address intelligence processing
        $this->process_geocoding($post_id);
        
        // Auto-populate county from ZIP code
        $this->populate_county_from_zip($post_id);
        
        // Phase 3: Relationship & team calculations
        $this->calculate_commission_totals($post_id);
        $this->calculate_performance_score($post_id);
        $this->calculate_school_rating_average($post_id);
        $this->calculate_lifestyle_score($post_id);
        
        // Phase 3 Day 4-7: Financial & market analytics calculations
        $this->calculate_financial_monthly_amounts($post_id);
        $this->calculate_buyer_affordability($post_id);
        $this->calculate_investment_metrics($post_id);
        $this->calculate_market_position_analysis($post_id);
    }

    /**
     * Calculate price per square foot
     */
    public function calculate_price_per_sqft($post_id): ?float
    {
        $price = floatval(get_field('price', $post_id) ?: 0);
        $sqft = floatval(get_field('square_footage', $post_id) ?: 0);
        
        if ($price > 0 && $sqft > 0) {
            $price_per_sqft = round($price / $sqft, 2);
            update_field('price_per_sqft', $price_per_sqft, $post_id);
            return $price_per_sqft;
        }
        return null;
    }

    /**
     * Calculate total bathrooms (full + half/2)
     */
    public function calculate_total_bathrooms($post_id): ?float
    {
        $full_baths = floatval(get_field('bathrooms_full', $post_id) ?: 0);
        $half_baths = floatval(get_field('bathrooms_half', $post_id) ?: 0);
        
        $total_baths = $full_baths + ($half_baths * 0.5);
        update_field('bathrooms_total', $total_baths, $post_id);
        return $total_baths;
    }

    /**
     * Calculate days on market
     */
    public function calculate_days_on_market($post_id): ?int
    {
        $list_date = get_field('list_date', $post_id);
        
        if ($list_date) {
            $list_timestamp = strtotime($list_date);
            $current_timestamp = current_time('timestamp');
            
            $days = max(0, floor(($current_timestamp - $list_timestamp) / DAY_IN_SECONDS));
            update_field('days_on_market', $days, $post_id);
            return $days;
        }
        return null;
    }

    /**
     * Enhanced address processing - maintains compatibility with existing system
     */
    private function process_address_fields($post_id): void
    {
        $street_address = get_field('street_address', $post_id);
        $unit_number = get_field('unit_number', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip_code = get_field('zip_code', $post_id);

        // Parse street address into components for MLS compliance
        if ($street_address) {
            $this->parse_street_address($post_id, $street_address);
        }

        // Generate unparsed address for MLS compliance
        $this->generate_unparsed_address($post_id);

        // Maintain compatibility with existing bridge functions
        $this->ensure_address_compatibility($post_id);
        
        // Generate full street address (legacy compatibility)
        $this->generate_full_address($post_id);
    }

    /**
     * Parse street address into MLS-compliant components
     */
    private function parse_street_address($post_id, $street_address): void
    {
        // Clean up the address
        $address = trim($street_address);
        
        // Common patterns for address parsing
        $patterns = [
            // Pattern 1: 123 N Main Street
            '/^(\d+)\s*([NSEW]|NE|NW|SE|SW)?\s*(.+?)\s+(St|Street|Ave|Avenue|Blvd|Boulevard|Dr|Drive|Ln|Lane|Rd|Road|Ct|Court|Pl|Place|Way|Cir|Circle|Pkwy|Parkway|Ter|Terrace|Loop)\.?\s*([NSEW])?\s*$/i',
            
            // Pattern 2: 123 Main Street (no direction)
            '/^(\d+)\s*(.+?)\s+(St|Street|Ave|Avenue|Blvd|Boulevard|Dr|Drive|Ln|Lane|Rd|Road|Ct|Court|Pl|Place|Way|Cir|Circle|Pkwy|Parkway|Ter|Terrace|Loop)\.?\s*$/i',
            
            // Pattern 3: 123 Main (no suffix)
            '/^(\d+)\s*(.+)$/i'
        ];

        $parsed = [
            'street_number' => '',
            'street_dir_prefix' => '',
            'street_name' => '',
            'street_suffix' => '',
            'street_dir_suffix' => ''
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $address, $matches)) {
                $parsed['street_number'] = $matches[1] ?? '';
                
                if (count($matches) >= 6) {
                    // Pattern 1: Full address with directions
                    $parsed['street_dir_prefix'] = strtoupper($matches[2] ?? '');
                    $parsed['street_name'] = trim($matches[3] ?? '');
                    $parsed['street_suffix'] = $this->normalize_street_suffix($matches[4] ?? '');
                    $parsed['street_dir_suffix'] = strtoupper($matches[5] ?? '');
                } elseif (count($matches) >= 4) {
                    // Pattern 2: Address with suffix but no direction
                    $parsed['street_name'] = trim($matches[2] ?? '');
                    $parsed['street_suffix'] = $this->normalize_street_suffix($matches[3] ?? '');
                } else {
                    // Pattern 3: Simple address
                    $parsed['street_name'] = trim($matches[2] ?? '');
                }
                break;
            }
        }

        // Update the component fields
        foreach ($parsed as $component => $value) {
            if (!empty($value)) {
                update_field($component, $value, $post_id);
            }
        }
    }

    /**
     * Normalize street suffix to standard abbreviations
     */
    private function normalize_street_suffix($suffix): string
    {
        $suffix_map = [
            'street' => 'St',
            'avenue' => 'Ave',
            'boulevard' => 'Blvd',
            'drive' => 'Dr',
            'lane' => 'Ln',
            'road' => 'Rd',
            'court' => 'Ct',
            'place' => 'Pl',
            'way' => 'Way',
            'circle' => 'Cir',
            'parkway' => 'Pkwy',
            'terrace' => 'Ter',
            'loop' => 'Loop'
        ];

        $normalized = strtolower(trim($suffix, '.'));
        return $suffix_map[$normalized] ?? ucfirst($normalized);
    }

    /**
     * Generate unparsed address for MLS compliance
     */
    private function generate_unparsed_address($post_id): void
    {
        $street_address = get_field('street_address', $post_id);
        $unit_number = get_field('unit_number', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip_code = get_field('zip_code', $post_id);

        $address_parts = array_filter([
            $street_address,
            $unit_number ? "Unit {$unit_number}" : null,
            $city,
            $state,
            $zip_code
        ]);

        if (!empty($address_parts)) {
            $unparsed = implode(', ', $address_parts);
            update_field('unparsed_address', $unparsed, $post_id);
        }
    }

    /**
     * Ensure compatibility with existing bridge functions
     * This maintains the fields your current system expects
     */
    private function ensure_address_compatibility($post_id): void
    {
        $street_address = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip_code = get_field('zip_code', $post_id);

        // Ensure 'address' field exists for backward compatibility
        if ($street_address && !get_field('address', $post_id)) {
            update_field('address', $street_address, $post_id);
        }

        // Ensure 'full_address' field is populated for templates
        if ($street_address || $city) {
            $full_parts = array_filter([$street_address, $city, $state, $zip_code]);
            $full_address = implode(', ', $full_parts);
            update_field('full_address', $full_address, $post_id);
        }

        // Ensure region field exists if your system uses it instead of state
        if ($state && !get_field('region', $post_id)) {
            update_field('region', $state, $post_id);
        }

        // Ensure zip field exists if your system uses it instead of zip_code
        if ($zip_code && !get_field('zip', $post_id)) {
            update_field('zip', $zip_code, $post_id);
        }
    }

    /**
     * Generate full street address from components (legacy compatibility)
     */
    private function generate_full_address($post_id): void
    {
        $street_number = get_field('street_number', $post_id);
        $street_name = get_field('street_name', $post_id);
        $street_suffix = get_field('street_suffix', $post_id);
        $unit_number = get_field('unit_number', $post_id);
        
        $address_parts = array_filter([
            $street_number,
            $street_name,
            $street_suffix,
            $unit_number ? "Unit {$unit_number}" : null
        ]);
        
        if (!empty($address_parts)) {
            $full_address = implode(' ', $address_parts);
            update_field('full_street_address', $full_address, $post_id);
        }
    }

    /**
     * Calculate lot size in square feet from acres
     */
    public function calculate_lot_sqft($post_id): ?int
    {
        $lot_acres = floatval(get_field('lot_size', $post_id) ?: 0);
        
        if ($lot_acres > 0) {
            // 1 acre = 43,560 square feet
            $lot_sqft = round($lot_acres * 43560);
            update_field('lot_sqft', $lot_sqft, $post_id);
            update_field('lot_sqft_calc', $lot_sqft, $post_id);
            return $lot_sqft;
        }
        return null;
    }

    /**
     * Auto-populate county from ZIP code (Delaware specific)
     */
    private function populate_county_from_zip($post_id): void
    {
        $zip_code = get_field('zip_code', $post_id);
        
        if (!$zip_code) {
            return;
        }
        
        // Delaware county mappings by ZIP code
        $delaware_counties = [
            // New Castle County
            '19701' => 'New Castle', '19702' => 'New Castle', '19703' => 'New Castle',
            '19706' => 'New Castle', '19707' => 'New Castle', '19708' => 'New Castle',
            '19709' => 'New Castle', '19710' => 'New Castle', '19711' => 'New Castle',
            '19712' => 'New Castle', '19713' => 'New Castle', '19714' => 'New Castle',
            '19715' => 'New Castle', '19716' => 'New Castle', '19717' => 'New Castle',
            '19718' => 'New Castle', '19720' => 'New Castle', '19721' => 'New Castle',
            '19801' => 'New Castle', '19802' => 'New Castle', '19803' => 'New Castle',
            '19804' => 'New Castle', '19805' => 'New Castle', '19806' => 'New Castle',
            '19807' => 'New Castle', '19808' => 'New Castle', '19809' => 'New Castle',
            '19810' => 'New Castle', '19850' => 'New Castle', '19880' => 'New Castle',
            '19884' => 'New Castle', '19885' => 'New Castle', '19886' => 'New Castle',
            '19890' => 'New Castle', '19891' => 'New Castle', '19892' => 'New Castle',
            '19893' => 'New Castle', '19894' => 'New Castle', '19895' => 'New Castle',
            '19896' => 'New Castle', '19897' => 'New Castle', '19898' => 'New Castle',
            
            // Kent County
            '19901' => 'Kent', '19902' => 'Kent', '19903' => 'Kent', '19904' => 'Kent',
            '19905' => 'Kent', '19906' => 'Kent', '19930' => 'Kent', '19931' => 'Kent',
            '19933' => 'Kent', '19934' => 'Kent', '19936' => 'Kent', '19938' => 'Kent',
            '19939' => 'Kent', '19940' => 'Kent', '19941' => 'Kent', '19943' => 'Kent',
            '19944' => 'Kent', '19946' => 'Kent', '19947' => 'Kent', '19950' => 'Kent',
            '19951' => 'Kent', '19952' => 'Kent', '19953' => 'Kent', '19954' => 'Kent',
            '19955' => 'Kent', '19956' => 'Kent', '19958' => 'Kent', '19960' => 'Kent',
            '19962' => 'Kent', '19963' => 'Kent', '19964' => 'Kent', '19966' => 'Kent',
            '19967' => 'Kent', '19968' => 'Kent', '19969' => 'Kent', '19970' => 'Kent',
            '19971' => 'Kent', '19973' => 'Kent', '19975' => 'Kent', '19977' => 'Kent',
            
            // Sussex County  
            '19930' => 'Sussex', '19931' => 'Sussex', '19932' => 'Sussex', '19933' => 'Sussex',
            '19934' => 'Sussex', '19935' => 'Sussex', '19936' => 'Sussex', '19937' => 'Sussex',
            '19938' => 'Sussex', '19939' => 'Sussex', '19940' => 'Sussex', '19941' => 'Sussex',
            '19943' => 'Sussex', '19944' => 'Sussex', '19945' => 'Sussex', '19946' => 'Sussex',
            '19947' => 'Sussex', '19948' => 'Sussex', '19950' => 'Sussex', '19951' => 'Sussex',
            '19952' => 'Sussex', '19953' => 'Sussex', '19954' => 'Sussex', '19956' => 'Sussex',
            '19958' => 'Sussex', '19960' => 'Sussex', '19962' => 'Sussex', '19963' => 'Sussex',
            '19964' => 'Sussex', '19966' => 'Sussex', '19967' => 'Sussex', '19968' => 'Sussex',
            '19969' => 'Sussex', '19970' => 'Sussex', '19971' => 'Sussex', '19973' => 'Sussex',
            '19975' => 'Sussex', '19977' => 'Sussex', '19979' => 'Sussex', '19980' => 'Sussex'
        ];
        
        // Extract 5-digit ZIP code
        $zip_5 = substr($zip_code, 0, 5);
        
        if (isset($delaware_counties[$zip_5])) {
            update_field('county', $delaware_counties[$zip_5], $post_id);
        }
    }

    /**
     * Set original price on first save
     */
    public function set_original_price($post_id): void
    {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }
        
        $current_price = floatval(get_field('price', $post_id) ?: 0);
        $original_price = floatval(get_field('original_price', $post_id) ?: 0);
        
        // Set original price if not already set and we have a current price
        if ($current_price > 0 && $original_price == 0) {
            update_field('original_price', $current_price, $post_id);
        }
    }

    /**
     * Track price changes for market analysis
     */
    public function track_price_changes($post_id): void
    {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }
        
        $current_price = floatval(get_field('price', $post_id) ?: 0);
        $previous_price = floatval(get_post_meta($post_id, '_previous_price', true) ?: 0);
        
        // Track price changes
        if ($current_price > 0 && $current_price !== $previous_price && $previous_price > 0) {
            $change_count = intval(get_field('price_change_count', $post_id) ?: 0);
            update_field('price_change_count', $change_count + 1, $post_id);
            update_field('last_price_change_date', current_time('Y-m-d'), $post_id);
        }
        
        // Update previous price tracker
        if ($current_price > 0) {
            update_post_meta($post_id, '_previous_price', $current_price);
        }
    }

    /**
     * Track status changes for compliance
     */
    public function track_status_changes($post_id): void
    {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }
        
        $current_status = get_field('listing_status', $post_id);
        $previous_status = get_post_meta($post_id, '_previous_listing_status', true);
        
        // Update status change date if status has changed
        if ($current_status && $current_status !== $previous_status) {
            update_field('status_change_date', current_time('Y-m-d'), $post_id);
            update_post_meta($post_id, '_previous_listing_status', $current_status);
        }
    }

    /**
     * Enhanced geocoding processing for address intelligence
     */
    private function process_geocoding($post_id): void
    {
        // Only geocode if we have the required address fields
        $street_address = get_field('street_address', $post_id);
        $city = get_field('city', $post_id);
        $state = get_field('state', $post_id);
        $zip_code = get_field('zip_code', $post_id);
        
        if (!$street_address || !$city || !$state || !$zip_code) {
            return;
        }
        
        // Check if coordinates already exist and address hasn't changed
        $existing_lat = get_field('latitude', $post_id);
        $existing_lng = get_field('longitude', $post_id);
        $address_hash = md5($street_address . $city . $state . $zip_code);
        $stored_hash = get_post_meta($post_id, '_address_hash', true);
        
        if ($existing_lat && $existing_lng && $address_hash === $stored_hash) {
            return; // Address unchanged, skip geocoding
        }
        
        // Build full address for geocoding
        $full_address = trim("$street_address, $city, $state $zip_code");
        
        // Attempt geocoding with different providers
        $geocoding_result = $this->geocode_address($full_address);
        
        if ($geocoding_result) {
            // Update coordinate fields
            update_field('latitude', $geocoding_result['lat'], $post_id);
            update_field('longitude', $geocoding_result['lng'], $post_id);
            update_field('geocoding_accuracy', $geocoding_result['accuracy'], $post_id);
            update_field('geocoding_source', $geocoding_result['source'], $post_id);
            
            // Store address hash to prevent unnecessary re-geocoding
            update_post_meta($post_id, '_address_hash', $address_hash);
            
            // Try to get additional data like county if not set
            if (!get_field('county', $post_id) && !empty($geocoding_result['county'])) {
                update_field('county', $geocoding_result['county'], $post_id);
            }
        }
    }
    
    /**
     * Geocode an address using available APIs
     */
    private function geocode_address($address): ?array
    {
        // First try Google Maps API if key is available
        $google_key = get_option('hph_google_maps_api_key');
        if ($google_key) {
            $result = $this->geocode_with_google($address, $google_key);
            if ($result) {
                return $result;
            }
        }
        
        // Fallback to OpenCage if Google fails
        $opencage_key = get_option('hph_opencage_api_key');
        if ($opencage_key) {
            $result = $this->geocode_with_opencage($address, $opencage_key);
            if ($result) {
                return $result;
            }
        }
        
        // Final fallback to Nominatim (free, no key required)
        return $this->geocode_with_nominatim($address);
    }
    
    /**
     * Geocode using Google Maps API
     */
    private function geocode_with_google($address, $api_key): ?array
    {
        $url = add_query_arg([
            'address' => urlencode($address),
            'key' => $api_key
        ], 'https://maps.googleapis.com/maps/api/geocode/json');
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Happy Place Real Estate Plugin'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = $data['results'][0];
            $location = $result['geometry']['location'];
            
            // Extract county from address components
            $county = '';
            foreach ($result['address_components'] as $component) {
                if (in_array('administrative_area_level_2', $component['types'])) {
                    $county = str_replace(' County', '', $component['long_name']);
                    break;
                }
            }
            
            return [
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'accuracy' => strtolower($result['geometry']['location_type'] ?? 'approximate'),
                'source' => 'google',
                'county' => $county
            ];
        }
        
        return null;
    }
    
    /**
     * Geocode using OpenCage API
     */
    private function geocode_with_opencage($address, $api_key): ?array
    {
        $url = add_query_arg([
            'q' => urlencode($address),
            'key' => $api_key,
            'limit' => 1,
            'countrycode' => 'us'
        ], 'https://api.opencagedata.com/geocode/v1/json');
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Happy Place Real Estate Plugin'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data['results'])) {
            $result = $data['results'][0];
            $geometry = $result['geometry'];
            $components = $result['components'];
            
            return [
                'lat' => $geometry['lat'],
                'lng' => $geometry['lng'],
                'accuracy' => 'approximate',
                'source' => 'opencage',
                'county' => $components['county'] ?? ''
            ];
        }
        
        return null;
    }
    
    /**
     * Geocode using Nominatim (OpenStreetMap) - Free fallback
     */
    private function geocode_with_nominatim($address): ?array
    {
        $url = add_query_arg([
            'q' => urlencode($address),
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'us',
            'addressdetails' => 1
        ], 'https://nominatim.openstreetmap.org/search');
        
        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Happy Place Real Estate Plugin'
            ]
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($data)) {
            $result = $data[0];
            $address_details = $result['address'] ?? [];
            
            return [
                'lat' => (float) $result['lat'],
                'lng' => (float) $result['lon'],
                'accuracy' => 'approximate',
                'source' => 'nominatim',
                'county' => $address_details['county'] ?? ''
            ];
        }
        
        return null;
    }
    
    /**
     * Calculate total commission from individual agent commissions
     * Phase 3: Relationships & Team Management
     */
    private function calculate_commission_totals($post_id): void
    {
        $primary_commission = (float) get_field('listing_agent_commission_primary', $post_id, false) ?: 0;
        $secondary_commission = (float) get_field('listing_agent_commission_secondary', $post_id, false) ?: 0;
        $buyer_commission = (float) get_field('buyer_agent_commission', $post_id, false) ?: 0;
        
        // Calculate total commission
        $total_commission = $primary_commission + $secondary_commission + $buyer_commission;
        
        // Update the total commission field
        if ($total_commission > 0) {
            update_field('total_commission', $total_commission, $post_id);
        }
    }
    
    /**
     * Calculate listing performance score based on views, inquiries, and time on market
     * Phase 3: Relationships & Team Management
     */
    private function calculate_performance_score($post_id): void
    {
        $views_total = (int) get_field('listing_views_total', $post_id, false) ?: 0;
        $views_weekly = (int) get_field('listing_views_this_week', $post_id, false) ?: 0;
        $inquiries = (int) get_field('inquiries_count_total', $post_id, false) ?: 0;
        $showings = (int) get_field('showings_count_total', $post_id, false) ?: 0;
        $days_on_market = (int) get_field('days_on_market', $post_id, false) ?: 0;
        
        // Base score starts at 50
        $score = 50;
        
        // Views scoring (max 25 points)
        if ($views_total > 0) {
            $score += min(25, ($views_total / 100) * 15 + ($views_weekly / 20) * 10);
        }
        
        // Engagement scoring (max 20 points)
        if ($views_total > 0) {
            $inquiry_rate = ($inquiries / max($views_total, 1)) * 100;
            $showing_rate = ($showings / max($inquiries, 1)) * 100;
            $score += min(20, ($inquiry_rate * 0.15) + ($showing_rate * 0.05));
        }
        
        // Time on market penalty (max -15 points)
        if ($days_on_market > 30) {
            $market_penalty = min(15, ($days_on_market - 30) / 10);
            $score -= $market_penalty;
        }
        
        // Days on market bonus for quick activity (max +5 points)
        if ($days_on_market < 7 && ($inquiries > 0 || $showings > 0)) {
            $score += 5;
        }
        
        // Ensure score stays within 0-100 range
        $score = max(0, min(100, round($score)));
        
        update_field('listing_performance_score', $score, $post_id);
    }
    
    /**
     * Calculate average school rating from individual school ratings
     * Phase 3: Relationships & Team Management
     */
    private function calculate_school_rating_average($post_id): void
    {
        $elementary = (int) get_field('elementary_school_rating', $post_id, false) ?: 0;
        $middle = (int) get_field('middle_school_rating', $post_id, false) ?: 0;
        $high = (int) get_field('high_school_rating', $post_id, false) ?: 0;
        
        $ratings = array_filter([$elementary, $middle, $high]);
        
        if (!empty($ratings)) {
            $average = round(array_sum($ratings) / count($ratings), 1);
            update_field('overall_school_rating', $average, $post_id);
        }
    }
    
    /**
     * Calculate composite lifestyle score from walkability, transit, bike, and school scores
     * Phase 3: Relationships & Team Management
     */
    private function calculate_lifestyle_score($post_id): void
    {
        $walkability = (int) get_field('walkability_score', $post_id, false) ?: 0;
        $transit = (int) get_field('transit_score', $post_id, false) ?: 0;
        $bike = (int) get_field('bike_score', $post_id, false) ?: 0;
        $school_rating = (float) get_field('overall_school_rating', $post_id, false) ?: 0;
        
        // Convert school rating (1-10) to 0-100 scale
        $school_score = $school_rating * 10;
        
        $scores = array_filter([$walkability, $transit, $bike, $school_score]);
        
        if (!empty($scores)) {
            // Weighted average: walkability 30%, transit 20%, bike 20%, schools 30%
            $weights = [0.3, 0.2, 0.2, 0.3];
            $weighted_sum = 0;
            $total_weight = 0;
            
            for ($i = 0; $i < count($scores); $i++) {
                if (isset($weights[$i])) {
                    $weighted_sum += $scores[$i] * $weights[$i];
                    $total_weight += $weights[$i];
                }
            }
            
            if ($total_weight > 0) {
                $lifestyle_score = round($weighted_sum / $total_weight);
                update_field('lifestyle_score', $lifestyle_score, $post_id);
            }
        }
    }
    
    /**
     * Calculate monthly amounts from annual values
     * Phase 3 Day 4-7: Financial & Market Analytics
     */
    private function calculate_financial_monthly_amounts($post_id): void
    {
        // Calculate monthly property tax from annual
        $annual_tax = (float) get_field('property_tax_annual', $post_id, false) ?: 0;
        if ($annual_tax > 0) {
            $monthly_tax = round($annual_tax / 12, 2);
            update_field('property_tax_monthly', $monthly_tax, $post_id);
        }
        
        // Calculate annual HOA from monthly
        $monthly_hoa = (float) get_field('hoa_fee_monthly', $post_id, false) ?: 0;
        if ($monthly_hoa > 0) {
            $annual_hoa = round($monthly_hoa * 12, 2);
            update_field('hoa_fee_annual', $annual_hoa, $post_id);
        }
        
        // Calculate monthly insurance from annual
        $annual_insurance = (float) get_field('insurance_estimated_annual', $post_id, false) ?: 0;
        if ($annual_insurance > 0) {
            $monthly_insurance = round($annual_insurance / 12, 2);
            update_field('insurance_estimated_monthly', $monthly_insurance, $post_id);
        }
    }
    
    /**
     * Calculate buyer affordability metrics
     * Phase 3 Day 4-7: Financial & Market Analytics
     */
    private function calculate_buyer_affordability($post_id): void
    {
        $listing_price = (float) get_field('price', $post_id, false) ?: 0;
        $down_payment_percentage = (float) get_field('down_payment_percentage', $post_id, false) ?: 20;
        $interest_rate = (float) get_field('interest_rate', $post_id, false) ?: 6.5;
        $loan_term_years = (int) get_field('loan_term_years', $post_id, false) ?: 30;
        
        if ($listing_price <= 0) {
            return;
        }
        
        // Calculate down payment amount
        $down_payment_amount = ($listing_price * $down_payment_percentage) / 100;
        update_field('down_payment_amount', round($down_payment_amount, 2), $post_id);
        
        // Calculate loan amount
        $loan_amount = $listing_price - $down_payment_amount;
        update_field('loan_amount', round($loan_amount, 2), $post_id);
        
        // Calculate monthly mortgage payment (Principal & Interest only)
        if ($loan_amount > 0 && $interest_rate > 0) {
            $monthly_rate = ($interest_rate / 100) / 12;
            $number_of_payments = $loan_term_years * 12;
            
            $monthly_payment = $loan_amount * (
                ($monthly_rate * pow(1 + $monthly_rate, $number_of_payments)) /
                (pow(1 + $monthly_rate, $number_of_payments) - 1)
            );
            
            update_field('estimated_monthly_payment', round($monthly_payment, 2), $post_id);
            
            // Calculate total monthly payment (PITI + HOA)
            $monthly_tax = (float) get_field('property_tax_monthly', $post_id, false) ?: 0;
            $monthly_insurance = (float) get_field('insurance_estimated_monthly', $post_id, false) ?: 0;
            $monthly_hoa = (float) get_field('hoa_fee_monthly', $post_id, false) ?: 0;
            
            $total_monthly_payment = $monthly_payment + $monthly_tax + $monthly_insurance + $monthly_hoa;
            update_field('total_monthly_payment', round($total_monthly_payment, 2), $post_id);
            
            // Calculate required annual income (28% debt-to-income ratio)
            $required_annual_income = ($total_monthly_payment * 12) / 0.28;
            update_field('affordability_income_required', round($required_annual_income, 2), $post_id);
        }
    }
    
    /**
     * Calculate investment analysis metrics
     * Phase 3 Day 4-7: Financial & Market Analytics
     */
    private function calculate_investment_metrics($post_id): void
    {
        $listing_price = (float) get_field('price', $post_id, false) ?: 0;
        $rental_income_monthly = (float) get_field('rental_potential_monthly', $post_id, false) ?: 0;
        $total_monthly_payment = (float) get_field('total_monthly_payment', $post_id, false) ?: 0;
        $appreciation_rate = (float) get_field('appreciation_rate_historical', $post_id, false) ?: 3.0;
        
        if ($listing_price <= 0) {
            return;
        }
        
        // Calculate gross rental yield
        if ($rental_income_monthly > 0) {
            $annual_rental_income = $rental_income_monthly * 12;
            $gross_yield = ($annual_rental_income / $listing_price) * 100;
            update_field('rental_yield_gross', round($gross_yield, 2), $post_id);
            
            // Estimate operating expenses (25% of rental income)
            $operating_expenses = $annual_rental_income * 0.25;
            $net_operating_income = $annual_rental_income - $operating_expenses;
            
            // Calculate cap rate
            $cap_rate = ($net_operating_income / $listing_price) * 100;
            update_field('cap_rate_estimated', round($cap_rate, 2), $post_id);
            
            // Calculate monthly cash flow
            $monthly_expenses = $operating_expenses / 12;
            $cash_flow_monthly = $rental_income_monthly - $total_monthly_payment - $monthly_expenses;
            update_field('cash_flow_monthly', round($cash_flow_monthly, 2), $post_id);
            
            // Calculate break-even ratio
            $total_monthly_expenses = $total_monthly_payment + $monthly_expenses;
            if ($total_monthly_expenses > 0) {
                $break_even_ratio = $rental_income_monthly / $total_monthly_expenses;
                update_field('break_even_ratio', round($break_even_ratio, 2), $post_id);
            }
            
            // Calculate investment grade
            $investment_grade = $this->calculate_investment_grade($cap_rate, $cash_flow_monthly, $gross_yield);
            update_field('investment_grade', $investment_grade, $post_id);
            
            // Calculate projected 5-year ROI
            $down_payment = (float) get_field('down_payment_amount', $post_id, false) ?: ($listing_price * 0.20);
            if ($down_payment > 0) {
                $annual_cash_flow = $cash_flow_monthly * 12;
                $appreciation_value = $listing_price * pow(1 + ($appreciation_rate / 100), 5) - $listing_price;
                $total_return = ($annual_cash_flow * 5) + $appreciation_value;
                $roi_5year = ($total_return / $down_payment) * 100;
                update_field('roi_projected_5year', round($roi_5year, 2), $post_id);
            }
        }
    }
    
    /**
     * Calculate investment grade based on key metrics
     * Helper method for investment analysis
     */
    private function calculate_investment_grade($cap_rate, $cash_flow, $gross_yield): string
    {
        $score = 0;
        
        // Cap rate scoring (40% weight)
        if ($cap_rate >= 8) $score += 40;
        elseif ($cap_rate >= 6) $score += 30;
        elseif ($cap_rate >= 4) $score += 20;
        elseif ($cap_rate >= 2) $score += 10;
        
        // Cash flow scoring (40% weight)
        if ($cash_flow >= 500) $score += 40;
        elseif ($cash_flow >= 200) $score += 30;
        elseif ($cash_flow >= 0) $score += 20;
        elseif ($cash_flow >= -200) $score += 10;
        
        // Gross yield scoring (20% weight)
        if ($gross_yield >= 12) $score += 20;
        elseif ($gross_yield >= 10) $score += 15;
        elseif ($gross_yield >= 8) $score += 10;
        elseif ($gross_yield >= 6) $score += 5;
        
        // Convert score to grade
        if ($score >= 90) return 'A+';
        elseif ($score >= 80) return 'A';
        elseif ($score >= 70) return 'B+';
        elseif ($score >= 60) return 'B';
        elseif ($score >= 50) return 'C+';
        elseif ($score >= 40) return 'C';
        elseif ($score >= 30) return 'D';
        else return 'F';
    }
    
    /**
     * Calculate market position analysis
     * Phase 3 Day 4-7: Financial & Market Analytics
     */
    private function calculate_market_position_analysis($post_id): void
    {
        $listing_price = (float) get_field('price', $post_id, false) ?: 0;
        $estimated_market_value = (float) get_field('estimated_market_value', $post_id, false) ?: 0;
        $comparable_avg_price = (float) get_field('comparable_sales_avg_price', $post_id, false) ?: 0;
        
        // Auto-determine market position based on price vs estimates
        if ($listing_price > 0 && ($estimated_market_value > 0 || $comparable_avg_price > 0)) {
            $comparison_value = $estimated_market_value > 0 ? $estimated_market_value : $comparable_avg_price;
            $price_difference_percent = (($listing_price - $comparison_value) / $comparison_value) * 100;
            
            $market_position = 'fair_value'; // default
            
            if ($price_difference_percent <= -10) {
                $market_position = 'underpriced';
            } elseif ($price_difference_percent <= -5) {
                $market_position = 'fair_value';
            } elseif ($price_difference_percent <= 5) {
                $market_position = 'fair_value';
            } elseif ($price_difference_percent <= 15) {
                $market_position = 'overpriced';
            } else {
                $market_position = 'premium';
            }
            
            update_field('market_position', $market_position, $post_id);
        }
    }
    
    /**
     * Update status change date (called by Enhanced Airtable Sync)
     */
    public function update_status_change_date(int $listing_id): void {
        $current_status = get_field('listing_status', $listing_id);
        $previous_status = get_post_meta($listing_id, '_previous_listing_status', true);
        
        if ($current_status !== $previous_status) {
            $change_date = current_time('Y-m-d');
            update_field('status_change_date', $change_date, $listing_id);
            update_post_meta($listing_id, '_previous_listing_status', $current_status);
        }
    }
    
    /**
     * Track price change (called by Enhanced Airtable Sync)
     */
    public function track_price_change(int $listing_id): void {
        $current_price = get_field('price', $listing_id);
        $previous_price = get_post_meta($listing_id, '_previous_price', true);
        
        if ($current_price && $current_price !== $previous_price) {
            if ($previous_price) {
                // Increment price change count
                $change_count = get_field('price_change_count', $listing_id) ?: 0;
                update_field('price_change_count', $change_count + 1, $listing_id);
                
                // Log the price change
                $this->log_price_change($listing_id, $previous_price, $current_price);
            } else {
                // First time setting price (original price)
                update_field('original_price', $current_price, $listing_id);
                update_field('price_change_count', 0, $listing_id);
            }
            
            update_post_meta($listing_id, '_previous_price', $current_price);
        }
    }
    
    /**
     * Log price change for historical tracking
     */
    private function log_price_change(int $listing_id, float $old_price, float $new_price): void {
        $price_history = get_field('price_history', $listing_id) ?: [];
        
        $price_history[] = [
            'date' => current_time('Y-m-d H:i:s'),
            'old_price' => $old_price,
            'new_price' => $new_price,
            'change_amount' => $new_price - $old_price,
            'change_percent' => round((($new_price - $old_price) / $old_price) * 100, 2)
        ];
        
        update_field('price_history', $price_history, $listing_id);
    }
}

// Initialize the calculator
add_action('init', function() {
    Listing_Calculator::get_instance();
});