<?php
/**
 * Test Marketing Suite AJAX Handler
 */

// Load WordPress
require_once 'wp-config.php';
require_once 'wp-load.php';

// Must be logged in
if (!is_user_logged_in()) {
    echo "<p>Please log in to test AJAX handlers.</p>";
    exit;
}

echo "<h1>Marketing Suite AJAX Handler Test</h1>\n";

// Check if the class exists and is loaded
echo "<h2>Class Availability</h2>\n";
$class_exists = class_exists('HappyPlace\\Dashboard\\Marketing_Suite_Generator');
echo "<p>Marketing_Suite_Generator class: " . ($class_exists ? '<span style="color: green;">EXISTS</span>' : '<span style="color: red;">NOT FOUND</span>') . "</p>\n";

if ($class_exists) {
    // Check if AJAX handler is registered
    echo "<h2>AJAX Handler Registration</h2>\n";
    $action_registered = has_action('wp_ajax_hph_generate_marketing_suite');
    echo "<p>wp_ajax_hph_generate_marketing_suite: " . ($action_registered ? '<span style="color: green;">REGISTERED</span>' : '<span style="color: red;">NOT REGISTERED</span>') . "</p>\n";
    
    // Get available listings for testing
    echo "<h2>Available Listings</h2>\n";
    try {
        $generator = \HappyPlace\Dashboard\Marketing_Suite_Generator::get_instance();
        $listings = $generator->get_listings();
        echo "<p>Found " . count($listings) . " listings</p>\n";
        
        if (!empty($listings)) {
            echo "<ul>\n";
            foreach (array_slice($listings, 0, 5) as $listing) {
                echo "<li>ID: {$listing->ID} - {$listing->post_title}</li>\n";
            }
            echo "</ul>\n";
            
            // Test data preparation with first listing
            if (count($listings) > 0) {
                $test_listing_id = $listings[0]->ID;
                echo "<h2>Test Data Preparation</h2>\n";
                echo "<p>Testing with listing ID: {$test_listing_id}</p>\n";
                
                try {
                    // Simulate request data
                    $test_request = [
                        'campaign_type' => 'listing',
                        'formats' => ['full_flyer'],
                        'template' => 'parker_group'
                    ];
                    
                    // Use reflection to access private method for testing
                    $reflection = new ReflectionClass($generator);
                    $method = $reflection->getMethod('prepare_listing_data');
                    $method->setAccessible(true);
                    
                    $listing_data = $method->invoke($generator, $test_listing_id, 'listing', $test_request);
                    echo "<p><span style='color: green;'>✓ Data preparation successful</span></p>\n";
                    echo "<pre>" . print_r($listing_data, true) . "</pre>\n";
                    
                } catch (Exception $e) {
                    echo "<p><span style='color: red;'>✗ Data preparation failed: " . $e->getMessage() . "</span></p>\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "<p><span style='color: red;'>Error: " . $e->getMessage() . "</span></p>\n";
    }
} else {
    echo "<p>Cannot test further without the class being available.</p>\n";
}

// Check nonces
echo "<h2>Nonce Test</h2>\n";
$nonces = [
    'marketing_suite_nonce' => wp_create_nonce('marketing_suite_nonce'),
    'hph_dashboard_nonce' => wp_create_nonce('hph_dashboard_nonce'),
    'hph_ajax_nonce' => wp_create_nonce('hph_ajax_nonce')
];

foreach ($nonces as $action => $nonce) {
    echo "<p>{$action}: {$nonce}</p>\n";
}

// AJAX URL test
echo "<h2>AJAX Configuration</h2>\n";
echo "<p>AJAX URL: " . admin_url('admin-ajax.php') . "</p>\n";
echo "<p>Current user can 'read': " . (current_user_can('read') ? 'YES' : 'NO') . "</p>\n";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
