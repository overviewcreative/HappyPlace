<?php
/**
 * Bridge Function Manager (Phase 1 Day 5-7)
 * 
 * Manages the enhanced bridge functions and ensures compatibility
 *
 * @package HappyPlace
 * @subpackage Bridge
 */

namespace HappyPlace\Bridge;

if (!defined('ABSPATH')) {
    exit;
}

class Bridge_Function_Manager
{
    private static ?self $instance = null;

    public static function get_instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        // Load enhanced bridge functions
        add_action('init', [$this, 'load_enhanced_bridge_functions'], 5);
        
        // Add bridge function testing capabilities
        add_action('init', [$this, 'maybe_test_bridge_functions'], 10);
        
        // Add cache management
        add_action('acf/save_post', [$this, 'clear_listing_cache'], 30);
    }

    /**
     * Load enhanced bridge functions
     */
    public function load_enhanced_bridge_functions(): void
    {
        // Load the enhanced bridge functions
        require_once plugin_dir_path(__FILE__) . 'enhanced-listing-bridge.php';
        
        // Log successful loading
        if (function_exists('hph_get_listing_price')) {
            error_log("✅ Enhanced bridge functions loaded successfully");
        } else {
            error_log("❌ Enhanced bridge functions failed to load");
        }
    }

    /**
     * Test bridge functions if requested
     */
    public function maybe_test_bridge_functions(): void
    {
        if (isset($_GET['test_bridge_functions']) && current_user_can('administrator')) {
            add_action('init', [$this, 'run_bridge_function_tests'], 20);
        }
    }

    /**
     * Run comprehensive bridge function tests
     */
    public function run_bridge_function_tests(): void
    {
        echo '<div style="background: white; padding: 20px; margin: 20px; border: 1px solid #ccc; font-family: monospace;">';
        echo '<h2>🔗 Bridge Function Test Results</h2>';
        
        // Create a test listing
        $test_post = wp_insert_post([
            'post_title' => 'Bridge Function Test Listing',
            'post_type' => 'listing',
            'post_status' => 'draft'
        ]);
        
        if ($test_post) {
            // Set v2 test data
            update_field('price', 750000, $test_post);
            update_field('original_price', 800000, $test_post);
            update_field('square_footage', 2500, $test_post);
            update_field('bathrooms_full', 3, $test_post);
            update_field('bathrooms_half', 1, $test_post);
            update_field('bathrooms_total', 3.5, $test_post);
            update_field('bedrooms', 4, $test_post);
            update_field('lot_size', 0.5, $test_post);
            update_field('lot_sqft', 21780, $test_post);
            update_field('listing_status', 'Active', $test_post);
            update_field('days_on_market', 45, $test_post);
            update_field('street_address', '456 N Oak Avenue', $test_post);
            update_field('city', 'Wilmington', $test_post);
            update_field('state', 'DE', $test_post);
            update_field('zip_code', '19801', $test_post);
            update_field('county', 'New Castle', $test_post);
            update_field('property_type', 'Single Family', $test_post);
            
            // Test all bridge functions
            echo '<h3>📋 Core Functions</h3>';
            $this->test_function('hph_get_listing_price', [$test_post, false], 750000, 'Current price');
            $this->test_function('hph_get_original_price', [$test_post, false], 800000, 'Original price');
            $this->test_function('hph_get_listing_status', [$test_post], 'Active', 'Listing status');
            $this->test_function('hph_get_days_on_market', [$test_post], 45, 'Days on market');
            
            echo '<h3>🏠 Property Features</h3>';
            $this->test_function('hph_get_bedrooms', [$test_post], 4, 'Bedrooms');
            $this->test_function('hph_get_bathrooms', [$test_post, 'total'], 3.5, 'Total bathrooms');
            $this->test_function('hph_get_bathrooms', [$test_post, 'full'], 3, 'Full bathrooms');
            $this->test_function('hph_get_bathrooms', [$test_post, 'half'], 1, 'Half bathrooms');
            $this->test_function('hph_get_property_type', [$test_post], 'Single Family', 'Property type');
            
            echo '<h3>📍 Address & Location</h3>';
            $address = hph_get_listing_address($test_post, false);
            echo "<strong>Address Components:</strong><br>";
            echo "   Street: {$address['street']}<br>";
            echo "   City: {$address['city']}<br>";
            echo "   State: {$address['state']}<br>";
            echo "   ZIP: {$address['zip']}<br>";
            
            $this->test_function('hph_get_county', [$test_post], 'New Castle', 'County');
            
            echo '<h3>📊 Calculated Fields</h3>';
            $lot_details = hph_get_lot_details($test_post);
            echo "<strong>Lot Details:</strong><br>";
            echo "   Acres: {$lot_details['acres']}<br>";
            echo "   Square Feet: {$lot_details['square_feet']}<br>";
            
            $market_metrics = hph_get_market_metrics($test_post);
            echo "<strong>Market Metrics:</strong><br>";
            echo "   Current Price: \${$market_metrics['current_price']}<br>";
            echo "   Original Price: \${$market_metrics['original_price']}<br>";
            echo "   Days on Market: {$market_metrics['days_on_market']}<br>";
            
            echo '<h3>🏗️ Comprehensive Data</h3>';
            $features = hph_get_listing_features($test_post);
            echo "<strong>Property Features Count:</strong> " . count($features) . " fields<br>";
            
            $listing_data = hph_get_listing_data($test_post);
            echo "<strong>Complete Listing Data:</strong> " . count($listing_data) . " data points<br>";
            
            // Clean up
            wp_delete_post($test_post, true);
            
            echo '<p style="color: green; font-weight: bold;">✅ All bridge functions operational!</p>';
        } else {
            echo '<p style="color: red;">❌ Failed to create test listing</p>';
        }
        
        echo '</div>';
        exit;
    }

    /**
     * Helper function to test individual functions
     */
    private function test_function($function_name, $args, $expected, $description): void
    {
        if (function_exists($function_name)) {
            $result = call_user_func_array($function_name, $args);
            $passed = ($result == $expected);
            $status = $passed ? '✅' : '❌';
            echo "<strong>{$description}:</strong> {$result} {$status}<br>";
            if (!$passed) {
                echo "   Expected: {$expected}<br>";
            }
        } else {
            echo "<strong>{$description}:</strong> ❌ Function not found<br>";
        }
    }

    /**
     * Clear listing cache when listings are updated
     */
    public function clear_listing_cache($post_id): void
    {
        if (get_post_type($post_id) !== 'listing') {
            return;
        }
        
        // Clear all cached data for this listing
        $cache_keys = [
            'listing_data_v2_' . $post_id,
            'listing_features_v2_' . $post_id,
            'listing_images_v2_' . $post_id
        ];
        
        foreach ($cache_keys as $key_prefix) {
            wp_cache_delete($key_prefix, 'hph_listings');
            // Also clear wildcards by flushing the group
            wp_cache_flush_group('hph_listings');
        }
        
        error_log("🧹 Cleared listing cache for post {$post_id}");
    }

    /**
     * Get bridge function compatibility status
     */
    public function get_compatibility_status(): array
    {
        $functions_to_check = [
            'hph_get_listing_price',
            'hph_get_original_price',
            'hph_get_price_per_sqft',
            'hph_get_days_on_market',
            'hph_get_listing_status',
            'hph_get_listing_address',
            'hph_get_address_components',
            'hph_get_listing_features',
            'hph_get_property_type',
            'hph_get_bedrooms',
            'hph_get_bathrooms',
            'hph_get_lot_details',
            'hph_get_room_summary',
            'hph_get_market_metrics',
            'hph_get_county',
            'hph_bridge_get_coordinates',
            'hph_get_listing_data',
            'hph_get_listing_images'
        ];
        
        $status = [
            'total_functions' => count($functions_to_check),
            'loaded_functions' => 0,
            'missing_functions' => [],
            'all_loaded' => false
        ];
        
        foreach ($functions_to_check as $function) {
            if (function_exists($function)) {
                $status['loaded_functions']++;
            } else {
                $status['missing_functions'][] = $function;
            }
        }
        
        $status['all_loaded'] = ($status['loaded_functions'] === $status['total_functions']);
        
        return $status;
    }

    /**
     * Add function mapping documentation
     */
    public function get_field_mapping(): array
    {
        return [
            'v1_to_v2_mapping' => [
                'listing_price' => 'price',
                'listing_status' => 'listing_status',
                'listing_bedrooms' => 'bedrooms',
                'listing_bathrooms' => 'bathrooms_total',
                'listing_square_feet' => 'square_footage',
                'listing_lot_size' => 'lot_size',
                'listing_year_built' => 'year_built',
                'listing_street_address' => 'street_address',
                'listing_city' => 'city',
                'listing_state' => 'state',
                'listing_zip_code' => 'zip_code',
                'listing_latitude' => 'latitude',
                'listing_longitude' => 'longitude'
            ],
            'v2_new_fields' => [
                'original_price',
                'price_per_sqft', 
                'days_on_market',
                'bathrooms_full',
                'bathrooms_half',
                'bathrooms_total',
                'lot_sqft',
                'street_number',
                'street_name',
                'street_suffix',
                'unparsed_address',
                'county',
                'property_type',
                'property_style'
            ]
        ];
    }
}

// Initialize the bridge function manager
add_action('init', function() {
    Bridge_Function_Manager::get_instance();
});
