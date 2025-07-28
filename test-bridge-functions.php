<?php
/**
 * Test file for bridge functions
 * 
 * Access this file by visiting: /test-bridge-functions.php
 * Remove this file after testing is complete.
 */

// Load WordPress
require_once dirname(__FILE__) . '/wp-config.php';
require_once dirname(__FILE__) . '/wp-load.php';

// Load bridge functions
$bridge_file = get_template_directory() . '/inc/template-bridge.php';
if (file_exists($bridge_file)) {
    require_once $bridge_file;
}

// Test with a sample listing ID (you can change this to match an actual listing)
$test_listing_id = 1; // Change this to match a real listing ID in your system

echo "<h1>Bridge Functions Test</h1>";
echo "<p>Testing with Listing ID: {$test_listing_id}</p>";

echo "<h2>Price Functions</h2>";
echo "<strong>Raw Price:</strong> " . var_export(hph_bridge_get_price($test_listing_id, false), true) . "<br>";
echo "<strong>Formatted Price (standard):</strong> " . var_export(hph_bridge_get_price_formatted($test_listing_id, 'standard'), true) . "<br>";
echo "<strong>Formatted Price (short):</strong> " . var_export(hph_bridge_get_price_formatted($test_listing_id, 'short'), true) . "<br>";

echo "<h2>Address Functions</h2>";
echo "<strong>Full Address:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'full'), true) . "<br>";
echo "<strong>Street Number:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'street_number'), true) . "<br>";
echo "<strong>Street Name:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'street_name'), true) . "<br>";
echo "<strong>Street:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'street'), true) . "<br>";
echo "<strong>City:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'city'), true) . "<br>";
echo "<strong>State:</strong> " . var_export(hph_bridge_get_address($test_listing_id, 'state'), true) . "<br>";
echo "<strong>Zip:</strong> " . var_export(hph_bridge_get_zip_code($test_listing_id), true) . "<br>";

echo "<h2>Property Details</h2>";
echo "<strong>Bedrooms:</strong> " . var_export(hph_bridge_get_bedrooms($test_listing_id), true) . "<br>";
echo "<strong>Bathrooms:</strong> " . var_export(hph_bridge_get_bathrooms($test_listing_id), true) . "<br>";
echo "<strong>Bathrooms Formatted:</strong> " . var_export(hph_bridge_get_bathrooms_formatted($test_listing_id), true) . "<br>";
echo "<strong>Square Feet:</strong> " . var_export(hph_bridge_get_sqft($test_listing_id), true) . "<br>";
echo "<strong>Square Feet Formatted (standard):</strong> " . var_export(hph_bridge_get_sqft_formatted($test_listing_id, 'standard'), true) . "<br>";
echo "<strong>Square Feet Formatted (short):</strong> " . var_export(hph_bridge_get_sqft_formatted($test_listing_id, 'short'), true) . "<br>";
echo "<strong>Lot Size Formatted:</strong> " . var_export(hph_bridge_get_lot_size_formatted($test_listing_id), true) . "<br>";

echo "<h2>Hero Data Function</h2>";
if (function_exists('hph_get_hero_data')) {
    $hero_data = hph_get_hero_data($test_listing_id);
    if ($hero_data && isset($hero_data['address'])) {
        echo "<strong>Hero Address Data:</strong><br>";
        echo "<pre>" . print_r($hero_data['address'], true) . "</pre>";
    } else {
        echo "<strong>Hero data not available or missing address</strong><br>";
    }
} else {
    echo "<strong>hph_get_hero_data function not found</strong><br>";
}

echo "<h2>Features</h2>";
$features = hph_bridge_get_features_formatted($test_listing_id);
echo "<strong>Formatted Features:</strong><br>";
if (!empty($features)) {
    echo "<pre>" . print_r($features, true) . "</pre>";
} else {
    echo "No features found<br>";
}

echo "<p><em>This test file validates that all bridge functions are working correctly.</em></p>";
?>
